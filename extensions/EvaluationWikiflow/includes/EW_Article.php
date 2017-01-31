<?php
/**
 * EvaluationArticle
 * 
 */
class EWArticle {
	private $mArticle=null;
	private $mToEvaluate=null;
	private $mWaiting=null;
	private $mAssigned=null;
	private $mWaitingID=null;
	private $mAssignedID=null;
	private $mWaitingTime=null;
	private $mAssignmentMatrix=null;
	private $mSemanticData=null;
	private $mCategoriesToEvaluate=null;
	private $mNeedsEdit=null;
	private $mCertified=null;
	private $mCertifiedID=null;
	/**
	 * Constructor
	 *
	 * @param Article $article
	 * @return EWArticle
	 */
	function EWArticle(Article $article){
		$this->mArticle=$article;
		$this->waiting();
		$this->assigned();
		$this->needsEdit();
		$this->certified();
	}
	/**
	 * Returns the Article object for this EWArticle
	 * @return Article
	 */
	public function getArticle(){return $this->mArticle;}
	/**
	 * If article is in a category to evaluate
	 * @return boolean
	 */
	public function toEvaluate(){
		if( $this->mToEvaluate == null){
			$this->mToEvaluate = EWArticle::isToEvaluate($this->mArticle);
		}
		return $this->mToEvaluate;
	}
	
	public function getCategoriesToEvaluate(){
		if($this->mCategoriesToEvaluate != null){
			$this->setCategoriesToEvaluate();
		}
		return $this->mCategoriesToEvaluate;
	}
	/**
	 * Load the array mCategoriesToEvaluate 
	 */
	private function setCategoriesToEvaluate(){
		$this->mCategoriesToEvaluate = null;

		$categories=ewfGetCategories();
		
		foreach($categories as $category){
			$cf = new Categoryfinder;
			$id = $this->mArticle->getID();
			$cf->seed(array($id),array($category),'OR');
			if( in_array( $id, $cf->run() ) ){
				$this->mCategoriesToEvaluate[] = $category;
			}
		}
	}
	/**
	 * This function checks if the article is waiting for review.
	 * @return boolean
	 */
	public function waiting(){
	
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select (
				EW_TABLE_WAITING,
				array('eww_id'),
				array('eww_pending' => true, 'eww_page_id' => $this->mArticle->getID() ),
				'EWArticle->waiting'
		);
		if($res->numRows()==0){
			$this->mWaiting = 0;
		}else{
			$this->mWaiting = 1;
		}		
		$db->freeResult( $res );
	
		return $this->mWaiting;
	}
	/**
	 * This function checks if, since last review, no editing has been done on the article.
	 * Returns true if needs to be edited/certified, false if some work has been done on it.
	 * @return boolean
	 */
	public function needsEdit(){
		
		if($this->certified()){
			$this->mNeedsEdit = 0;
			return $this->mNeedsEdit;
		}
		
		$db = wfGetDB(DB_SLAVE);
		
		$revisiontitle = $this->getArticle()->getTitle();
		$revision = Revision::newFromTitle( $revisiontitle );
		
		if( ! $revision instanceof Revision ){
			$this->mNeedsEdit = 0;
			return $this->mNeedsEdit;
		}
		$prev_revision = $revision->getPrevious();
		if( ! $prev_revision instanceof Revision ){
			$this->mNeedsEdit = 0;
			return $this->mNeedsEdit;
		}		
		$res = $db->select(
			EW_TABLE_REVIEW,
			array('ewr_aboutrevision_id'),
			array('ewr_aboutrevision_id' => $prev_revision->getId() )
		);
		
		if($res->numRows()==0)
				$this->mNeedsEdit = 0;
		else
				$this->mNeedsEdit = 1;		
		$db->freeResult( $res );
		
		return $this->mNeedsEdit;
	}
	/**
	 * This functions checks if the article has some pending assignments
	 * @return boolean
	 */
	public function assigned() {

		$db = wfGetDB(DB_SLAVE);
		$res = $db->select (
				EW_TABLE_ASSIGNED,
				array('ewa_id'),
				array('ewa_pending' => true, 'ewa_page_id' => $this->mArticle->getID() ),
				'EWArticle->assigned'
		);
		if($res->numRows()==0)
				$this->mAssigned = 0;
		else
				$this->mAssigned = 1;
		$db->freeResult( $res );
		
		return $this->mAssigned;
	}
	/**
	 * If the article has been certified, this function returns the ID of the related certified article.
	 * @return int
	 */
	public function getCertifiedID(){
		return $this->mCertifiedID;
	}
	/**
	 * This function checks whether this article has been certified or not.
	 * @return boolean
	 */
	public function certified() {

		$db = wfGetDB(DB_SLAVE);
		//$table = EW_TABLE_CERTIFIED;
		$table = "mw_ew_certified";
		$articleid=$this->mArticle->getID();
		

		$selectq =<<<END
		select `ewc_id`, `ewc_new_page_id` from `$table` where
		(`ewc_old_page_id` = $articleid)
END;

		$res = $db->query($selectq);
		
		if($res->numRows()==0)
			$this->mCertified = 0;
		else{
			$row = $db->fetchRow($res);
			$this->mCertifiedID = $row[1];
			$this->mCertified = 1;
		}
		$db->freeResult( $res );
		
		return $this->mCertified;
	}	
	/**
	 * Returns all reviewers about this article
	 * @return Array<String>
	 */
	public function getAllReviewers(){
		return $this->myReviewers();
	}
	/**
	 * Returns all reviewers about this article
	 * @return Array<String>
	 */
	public function getAllReviewersID(){
		return $this->myReviewersID();
	}	
	/**
	 * Loads all data about assignments for this article
	 * and store it in private mAssignmentMatrix variable
	 * ********************************************
	 * *****IMPORTANT: DO NOT CHANGE FIELD ORDER***
	 * ********************************************
	 */
	private function loadAssignmentMatrix(){
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select (
			EW_TABLE_ASSIGNED,
			array(
				'ewa_id',
				'ewa_waiting_id',
				'ewa_revision_id',
				'ewa_reviewer_id',
				'ewa_reviewer_text',
				'ewa_manager_id',
				'ewa_manager_text',
				'ewa_pending',
				'ewa_timestamp'
			),
			array('ewa_page_id' => $this->mArticle->getID() ),
			'EWArticle->loadAssignmentMatrix'
		);
		$matrix = array();
		while( ($row = $db->fetchRow($res) )  != null ){
			$matrix[] = $row;
		}
		$this->mAssignmentMatrix = $matrix;
		$db->freeResult( $res );
		return;
	}
	/**
	 * Extract reviewers from assignmentMatrix private var
	 * @return Array<String>
	 */
	private function myReviewers(){
		if($this->mAssignmentMatrix == null){
			$this->loadAssignmentMatrix();
		}
		$reviewers = array();
		foreach($this->mAssignmentMatrix as $row){
			$reviewers[] = $row[4];
		}
		return $reviewers;
	}
	/**
	 * Extract reviewers from assignmentMatrix private var
	 * @return Array<String>
	 */
	private function myReviewersID(){
		if($this->mAssignmentMatrix == null){
			$this->loadAssignmentMatrix();
		}
		$reviewers = array();
		foreach($this->mAssignmentMatrix as $row){
			$reviewers[] = $row[3];
		}
		return $reviewers;
	}	
	/**
	 * This function checks whether this article has some assignments to the specified User
	 * @param User $user
	 * @return boolean
	 */
	public function hasPendingAssignment(User $user){
		if($this->mAssignmentMatrix == null) $this->loadAssignmentMatrix();
		$analyzer = new EWAssignmentAnalyzer($this->mAssignmentMatrix);
		return $analyzer->hasPendingAssignment($user);
	}
	/**
	 * This function returns the pending assignment id.
	 * @param User $user
	 * @return int
	 */
	public function getPendingAssignmentID(User $user){
		if($this->mAssignmentMatrix == null) $this->loadAssignmentMatrix();
		$analyzer = new EWAssignmentAnalyzer($this->mAssignmentMatrix);
		return $analyzer->getPendingAssignmentID($user);		
	}
	/**
	 * Save the article as waiting for review
	 * Submitter is the current $wgUser
	 * Returns true if process goes on normally, false if not.
	 * @return boolean
	 */
	public function saveWaiting(){
		if( $this->waiting() ) return false;
		if(! $this->editWaiting() ) return false;
		
		global $wgUser;
		// We have to create a new article in order to have the correct revision id updated by editWaiting() function!
		$article = new Article(Title::newFromID($this->mArticle->getID()));
		$page_id = $article->getID();
		$title = $article->getTitle();
		$namespace_id = $title->getNamespace();
		
		#$article->loadLastEdit(); Seems this does not work anymore, I substituted with fetchContent
		
		$article->fetchContent();
		$revision_id = $article->getRevIdFetched();
			
		$user_text = $wgUser->getName();
		$user_id = $wgUser->getID();
		$db = wfGetDB( DB_MASTER );
		$timestamp = $db->timestamp();
		$db->insert(
			EW_TABLE_WAITING,
			array(
				'eww_page_id' => $page_id,
				'eww_namespace_id' => $namespace_id,
				'eww_page_title' => $title->getText(),
				'eww_revision_id' => $revision_id,
				'eww_user_id' => $user_id,
				'eww_user_text' => $user_text,
				'eww_timestamp' => $timestamp
			),
			'EWArticle->saveWaiting'
		);
		
		return $this->waiting();
	}
	/**
	 * Edit and save the article as 'EW_CATEGORY_WAITING'
	 *
	 * @return boolean
	 */
	private function editWaiting(){
		$newcontent=$this->mArticle->fetchContent();
		$newcontent .= "\n[[Category:" . EW_CATEGORY_WAITING . "]]";
		return ($this->mArticle->doEdit($newcontent,wfMsg('waitingsaved') , EDIT_MINOR ) );
	}
	/**
	 * Return false if article is not waiting for review
	 *
	 * @return unknown
	 */
	public function getWaitingID(){
		if($this->mWaitingID == null){
			if($this->setWaitingStatus()){
				return $this->mWaitingID;
			}else{
				return false;
			}
		}else{
			return $this->mWaitingID;
		}
	}
	/**
	 * Set internal variables for waiting article
	 *
	 * @return unknown
	 */
	private function setWaitingStatus(){
		/**
		 * Only if article is in the right category
		 */
		if(!$this->toEvaluate()) return false;
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select(
			EW_TABLE_WAITING,
			array(
				'eww_id',
				'max(eww_timestamp)'
			),
			array(
				'eww_page_id' => $this->mArticle->getID(),
				'eww_pending' => true
			),
			'EWArticle->setWaitingStatus',
			array('GROUP BY'=>'eww_id')
		);
		if($res->numRows()>0){
			$row = $db->fetchRow($res);
			$this->mWaitingID = $row[0];
			$this->mWaitingTime = $row[1];
			$db->freeResult( $res );
			return true;
		}else{
			return false;
		}		
	}

	/**
	 * Assign the article to a reviewer
	 *
	 * @param User $reviewer
	 * @return unknown
	 */
	public function saveAssigned($reviewer){
		if(! $this->editAssigned() ) return false;
		
		global $wgUser;
		// We have to create a new article in order to have the correct revision id updated by editWaiting() function!
		$article = new Article(Title::newFromID($this->mArticle->getID()));
		$page_id = $article->getID();
		$title = $article->getTitle();
		$namespace_id = $title->getNamespace();
		$article->fetchContent();
		$revision_id = $article->getRevIdFetched();
		$reviewer_text = $reviewer->getName();
		$reviewer_id = $reviewer->getID();
		$manager_text = $wgUser->getName();
		$manager_id = $wgUser->getID();
		$db = wfGetDB( DB_MASTER );
		$timestamp = $db->timestamp();
		
		$waiting_id = ($this->waiting())?$this->getWaitingID():0;
		
		$db->insert(
			EW_TABLE_ASSIGNED,
			array(
				'ewa_page_id' => $page_id,
				'ewa_waiting_id' => $waiting_id,
				'ewa_namespace_id' => $namespace_id,
				'ewa_page_title' => $title->getText(),
				'ewa_revision_id' => $revision_id,
				'ewa_reviewer_id' => $reviewer_id,
				'ewa_reviewer_text' => $reviewer_text,
				'ewa_manager_id' => $manager_id,
				'ewa_manager_text' => $manager_text,
				'ewa_timestamp' => $timestamp
			),
			'EWArticle->saveAssigned'
		);
		
		EWNotifier::notifyActionAssignReview($wgUser,$reviewer,Title::newFromID($this->mArticle->getID()));
		
		return $this->assigned();
	}
	private function movePage(Title $newTitle, $comment, $move_talkpage=true,$move_subpages = true){
		$pagename = $this->getArticle()->getTitle()->getText();
		$oldns = $this->getArticle()->getTitle()->getNamespace();
		$newns = $newTitle->getNamespace();
		// Move article's page
		$this->getArticle()->getTitle()->moveTo( $newTitle, true, $comment);
		// Move talk page
		if($move_talkpage)
			$this->getArticle()->getTitle()->getTalkPage()->moveTo( $newTitle->getTalkPage(), true, wfMsg('movecertified'));
		// Move all subpages
		if($move_subpages){
			$db = wfGetDB(DB_SLAVE);
			$selectsubpages=<<<END
	
			select `page_title`
			from `page`
			where `page_namespace`=$oldns
			and `page_title` LIKE '$pagename/%'

END;
			$res=$db->doQuery($selectsubpages);
			
			while( ($row = $db->fetchRow($res) )  != null ){
				// Move subpage(s)
				$subpage_name=$row[0];
				$new_page_name=$newTitle->getText();
				$new_subpage_name = str_replace($pagename,$new_page_name,$subpage_name);
				$subpage_title = Title::newFromText($subpage_name,$oldns);
				$new_subpage_title = Title::newFromText($new_subpage_name,$newns);
				$subpage_title->moveTo( $new_subpage_title, true, $comment);
				// Move talk page
				if($move_talkpage)
					$subpage_title->getTalkPage()->moveTo( $new_subpage_title->getTalkPage(), true, $comment);
			}
			$db->freeResult($res);
		}		
	}
	/**
	 * Protects single page
	 *
	 * @param Article $article
	 * @param string $comment
	 * @return boolean
	 */
	static private function protectSinglePage(Article &$article, $comment ){

		if(!$article->exists()) return false;
		
		$title = $article->getTitle();
		$id = $article->getID();
		
		$encodedExpiry='infinity';
		$limit['edit']='sysop';
		$limit['move']='sysop';
		
		$dbw = wfGetDB(DB_MASTER);
		
		# Update restrictions table
		foreach( $limit as $action => $restrictions ) {
			if ($restrictions != '' ) {
				$dbw->replace( 'page_restrictions', array(array('pr_page', 'pr_type')),
					array( 'pr_page' => $id, 'pr_type' => $action
						, 'pr_level' => $restrictions, 'pr_cascade' => 0
						, 'pr_expiry' => $encodedExpiry ), __METHOD__  );
			} else {
				$dbw->delete( 'page_restrictions', array( 'pr_page' => $id,
					'pr_type' => $action ), __METHOD__ );
			}
		}
		
		# Update the protection log
		$log = new LogPage( 'protect' );
		$log->addEntry( 'protect', $title, trim( $comment) );
		return true;
	}
	/*
	 * This functions protects the page (and all its subpages) form editing
	 */
	static private function protectPage(Article &$article, $summary, $protect_subpages=true, $protect_talkpage=false){
		EWArticle::protectSinglePage($article,$summary);
		$db=wfGetDB(DB_SLAVE);
		if($protect_subpages){
			$ns=$article->getTitle()->getNamespace();
			$pagename=$article->getTitle()->getText();
			$selectsubpages=<<<END
	
			select `page_title`
			from `page`
			where `page_namespace`=$ns
			and `page_title` LIKE '$pagename/%'

END;
			$res=$db->doQuery($selectsubpages);
			
			while( ($row = $db->fetchRow($res) )  != null ){
				// Copy subpage(s)
				$subpage_name=$row[0];
				$subpage_title = Title::newFromText($subpage_name,$ns);
				EWArticle::protectSinglePage(new Article($subpage_title),$summary);
				// Copy talk page
				if($protect_talkpage)
					EWArticle::protectSinglePage(new Article($subpage_title->getTalkPage()),$summary);
			}
			$db->freeResult($res);
		}
	}
	/**
	 * Copy page $from to $target
	 *
	 * @param Article $from
	 * @param Title $target
	 * @param string $summary
	 * @param boolean $overwrite
	 * @return integer // return the new ArticleID
	 */
	static private function copySinglePage(Article &$from,Title &$target,$summary,$overwrite=false){
		if($target->exists() && !$overwrite) return false;
		if(!$from->exists()) return false;
		$newarticle = new Article($target);
		$newcontent = $from->fetchContent();
		$flags = ( $newarticle->exists() ) ? EDIT_UPDATE : EDIT_NEW;
		$flags &= EDIT_FORCE_BOT;
		$newarticle->doEdit($newcontent,$summary, $flags);
		return $newarticle->getID();
	}
	static private function copyPage(Article &$from, Title &$target,$summary,$copy_subpages=true,$copy_talkpage=true,$overwrite=false){
		
		$newpageid=EWArticle::copySinglePage( $from, $target, $summary, $overwrite );
		$oldns = $from->getTitle()->getNamespace();
		$newns = $target->getNamespace();
		$pagename=$from->getTitle()->getText();
		$db = wfGetDB(DB_SLAVE);
		if($copy_subpages){
			$selectsubpages=<<<END
	
			select `page_title`
			from `page`
			where `page_namespace`=$oldns
			and `page_title` LIKE '$pagename/%'

END;
			$res=$db->doQuery($selectsubpages);
			
			while( ($row = $db->fetchRow($res) )  != null ){
				// Copy subpage(s)
				$subpage_name=$row[0];
				$new_page_name=$target->getText();
				$new_subpage_name=str_replace($pagename,$new_page_name,$subpage_name);;
				$subpage_title = Title::newFromText($subpage_name,$oldns);
				$new_subpage_title = Title::newFromText($new_subpage_name,$newns);
				EWArticle::copySinglePage(new Article($subpage_title),$new_subpage_title,$summary,$overwrite);
				// Copy talk page
				if($copy_talkpage)
					EWArticle::copySinglePage(new Article($subpage_title->getTalkPage()),$new_subpage_title->getTalkPage(),$summary,$overwrite);
			}
			$db->freeResult($res);
		}
		return $newpageid;
	}
	/**
	 * Certify action
	 *
	 * @param Title $newTitle
	 * @param unknown_type $mode // can be EW_MODE_MOVE_PAGE or EW_MODE_LOCK_AND_COPY
	 * @return unknown
	 */
	public function saveCertified(Title $newTitle, $mode){
		$pageid =$this->getArticle()->getID();
		
		$db = wfGetDB(DB_MASTER);
		$timestamp = $db->timestamp();
		
		// Update Waiting table, set pending=true
		$db->update(
			EW_TABLE_WAITING, 
			array(
				'eww_pending' => false
			), 
			array(
				'eww_page_id' => $pageid
			),
			'EWReview->saveCertified'
			);
							
		// Assignment table: if article is certified, all pending assignments are set to false
		$db->update(
			EW_TABLE_ASSIGNED, 
			array(
				'ewa_pending' => false
			), 
			array(
				'ewa_page_id' => $pageid
			),
			'EWReview->saveCertified'
			);		

		// Preparing content of evaluated article
		$update_messages = wfMsg('contentcertified');

		// Before editing article
		$content = $this->getArticle()->fetchContent();
		$oldrevisionid = $this->getArticle()->getRevIdFetched();
		$oldns = $this->getArticle()->getTitle()->getNamespace();
	
		$updateassigned_content = EWArticle::removeAnnotationAssigned($content);
		if($content != $updateassigned_content )
			$update_messages .= " ". wfMsg('assignedremoved');
			
		// Remove waiting article
		$updatewaiting_content = EWArticle::removeAnnotationWaiting($updateassigned_content);
		if($updateassigned_content != $updatewaiting_content )
			$update_messages .= " ". wfMsg('waitingremoved');	
		
		// Remove categories toEvaluate
		$categories=ewfGetCategories();
		$removecategories_content = $updatewaiting_content;
		foreach ( $categories as $category ) {
			$string2find = "[[Category:" . $category . "]]";
			$removecategories_content = str_replace($string2find,'',$removecategories_content);
		}
		
		$newns = $newTitle->getNamespace();
		$oldpagetitle = $this->getArticle()->getTitle()->getFullText();
		$newpagetitle = $newTitle->getFullText();
		
		if( $mode == EW_MODE_MOVE_PAGE ){
			if( strcmp($newpagetitle,$oldpagetitle) != 0 ){
				$this->movePage($newTitle,wfMsg('movecertified'));				
			}else $newpagetitle = $oldpagetitle;
			// Add category Certified
			$newcontent = EWArticle::appendAnnotationCertified($removecategories_content);
			// Edits the article with the new content
			$this->mArticle->doEdit( $newcontent, $update_messages );
			// Save in database
			// After editing article
			$content = $this->getArticle()->fetchContent();
			$newrevisionid = $this->getArticle()->getRevIdFetched();	
		}elseif($mode == EW_MODE_LOCK_AND_COPY){
			// Add category Certified
			$newcontent = EWArticle::appendAnnotationCertified($removecategories_content);
			wfDebugLog('EvaluationWorkflow',"NEW CONTENT: ". $newcontent);
			$this->mArticle->doEdit( $newcontent, $update_messages );
			$newpageid=EWArticle::copyPage( $this->getArticle(), $newTitle, wfMsg('lockedandcopyed'),true,false,true);
			wfDebugLog('EvaluationWorkflow',"NEW PAGE ID: ". $newpageid);
			EWArticle::protectPage( $this->getArticle(), wfMsg('lockedandcopyed') );
			$newarticle=new Article(Title::newFromID($newpageid));
			$content = $newarticle->fetchContent();
			$newrevisionid=$newarticle->getRevIdFetched();
			wfDebugLog('EvaluationWorkflow',"NEW CONTENT: " . $content);
		}

		if(!isset($newpageid)) $newpageid=$pageid;
		
		
		global $wgUser;
		$managerid=$wgUser->getID();
		$managertext=$wgUser->getName();
		
		// Inserts certified content
		$db->insert(
			EW_TABLE_CERTIFIED,
			array(
				'ewc_old_page_id' => $pageid,
				'ewc_new_page_id' => $newpageid,
				'ewc_old_revision_id' => $oldrevisionid,
				'ewc_new_revision_id' => $newrevisionid,
				'ewc_old_page_title' => $oldpagetitle,
				'ewc_new_page_title' => $newpagetitle,
				'ewc_old_namespace_id' => $oldns,
				'ewc_new_namespace_id' => $newns,
				'ewc_manager_id' => $managerid,
				'ewc_manager_text' => $managertext,
				'ewc_timestamp' => $timestamp
			),
			'EWReview::saveCertified'
		);
		
		// Notify action 'certified'
		EWNotifier::notifyActionCertify($wgUser, $mode, $newTitle, $this->getArticle()->getTitle());
		
		return $this->certified();
	}
	/**
	 * Edit and save the article as 'EW_CATEGORY_ASSIGNED'
	 *
	 * @return boolean
	 */	
	private function editAssigned(){
		$newcontent=$this->mArticle->fetchContent();
		$newcontent .= "\n[[Category:" . EW_CATEGORY_ASSIGNED . "]]";
		return ( $this->mArticle->doEdit( $newcontent, wfMsg( 'editassignedmessage' ), EDIT_MINOR ) );
	}
	/**
	 * get values from the property Form for review
	 *
	 * @return Array<string>
	 */
	function getFormForReview(){
		if( $this->mCategoriesToEvaluate == null ){
			$this->setCategoriesToEvaluate();
		}
		$store=smwfGetStore();
		$form_page = null;
		foreach( $this->mCategoriesToEvaluate as $category ){
		
			$category_title = Title::newFromText( $category, NS_CATEGORY );
			
			// if smw 1.4 or higher
			if (class_exists('SMWPropertyValue')) {
				$default_form_property = SMWPropertyValue::makeProperty( str_replace(' ','_',EW_PROPERTY_FORM_FOR_REVIEW ));
				$res = $store->getPropertyValues( $category_title, $default_form_property );
				
				if ( isset( $res[0] ) ){
					$form_page[] = $res[0]->getTitle();
				}
			}else{
				// otherwise
				$category_semdata = $store->getSemanticData( $category_title );
				$propertyformvalues = $category_semdata->getPropertyValues( EW_PROPERTY_FORM_FOR_REVIEW );
				foreach($propertyformvalues as $v) $form_page[]= $v->getTitle();
			}
		}
		if( $form_page == null){
			return null;
		}
		
		// Only first value is valid, how different?
		// TODO multiple form for reviews support (using alternate forms?)
		return $form_page[0];
	}
	/**
	 * 
	 */
	private function loadSemanticData(){
		$this->mSemanticData = smwfGetStore()->getSemanticData($this->mArticle->getTitle);
	}
	/**
	 * Alternative constructor
	 *
	 * @param $title Title
	 * @return new EvaluationArticle
	 */
	static function newFromTitle( $title ){
		return new EWArticle(new Article($title));
	}
	/**
	 * Check if an article is in a category to evaluate
	 *
	 * @param Article $article
	 * @return boolean
	 */
	static function isToEvaluate($article){
		$cf = new Categoryfinder;
		$id = $article->getID();
		$categories=ewfGetCategories();
		
		//if there are no categogries return false
		if(count($categories)==0) return false;
		//else, check
		$cf->seed(array($id),$categories,'OR');
		return ( in_array( $id, $cf->run() ) );
	}
	/**
	 * Adds annotation EW_CATEGORY_WAITING
	 * to wiki text only if it is not yet...
	 *
	 * @param String $wikitext
	 * @return String wikitext
	 */
	static function appendAnnotationWaiting($wikitext){
		$string2find = "[[Category:" . EW_CATEGORY_WAITING . "]]";
		return EWArticle::appendAnnotation($string2find,$wikitext);
	}
	/**
	 * Adds annotation EW_CATEGORY_ASSIGNED
	 * to wiki text only if it is not yet...
	 *
	 * @param String $wikitext
	 * @return String wikitext
	 */
	static function appendAnnotationAssigned($wikitext){
		$string2find = "[[Category:" . EW_CATEGORY_ASSIGNED . "]]";
		return EWArticle::appendAnnotation($string2find,$wikitext);
	}
	/**
	 * Adds annotation EW_CATEGORY_CERTIFIED
	 * to wiki text only if it is not yet...
	 *
	 * @param String $wikitext
	 * @return String wikitext
	 */
	static function appendAnnotationCertified($wikitext){
		$string2find = "[[Category:" . EW_CATEGORY_CERTIFIED . "]]";
		return EWArticle::appendAnnotation($string2find,$wikitext);
	}
	/**
	 * Adds annotation to text only if it is not yet...
	 *
	 * @param String $string2find
	 * @param String $wikitext
	 * @return String wikitext
	 */
	private static function appendAnnotation($string2find,$wikitext){
		if(!strpos($wikitext,$string2find)===false){
			return $wikitext;
		}else{
			return $wikitext . $string2find;
		}		
	}
	/**
	 * Remove
	 */
	static function removeAnnotationWaiting($wikitext){
		$string2find = "[[Category:" . EW_CATEGORY_WAITING . "]]";
		return EWArticle::removeAnnotation($string2find,$wikitext);
	}
	/**
	 * Remove annotation EW_CATEGORY_ASSIGNED
	 * to wiki text only if it is not yet...
	 *
	 * @param String $wikitext
	 * @return String wikitext
	 */
	static function removeAnnotationAssigned($wikitext){
		$string2find = "[[Category:" . EW_CATEGORY_ASSIGNED . "]]";
		return EWArticle::removeAnnotation($string2find,$wikitext);
	}
	/**
	 * Remove annotation from text
	 *
	 * @param String $string2find
	 * @param String $wikitext
	 * @return String wikitext
	 */
	private static function removeAnnotation($string2find,$wikitext){
		return str_replace($string2find,"",$wikitext);		
	}	
}
?>
