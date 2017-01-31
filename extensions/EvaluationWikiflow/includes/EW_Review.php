<?php
/**
 * EWReview class
 * @author Enrico Daga
 */
class EWReview{
	private $mArticle = null;
	private $mDBID = null;
	private $mWaitingID = null;
	private $mAssignedID = null;
	private $mReviewID = null;
	private $mAboutID = null;
	private $mAboutrevisionID = null;
	private $mReviewerID = null;
	private $mReviewerText = null;
	private $mTimestamp = null;
	private $mExists = null;
	/**
	 * COnstructor
	 */
	private function EWReview(Article $article){
		$this->mArticle = $article;
		$this->mExists = $article->exists();
		if($this->exists())
			$this->load();
	}
	/**
	 * Gets the Article object for this review
	 * @return Article
	 */
	public function getArticle(){return $this->mArticle;}
	/**
	 * Gets the related waiting for review id (if any)
	 * @return int
	 */
	public function getWaitingID(){return $this->mWaitingID;}
	/**
	 * Gets the related assigned id (if any)
	 */
	public function getAssignedID(){return $this->mAssignedID;}
	/**
	 * Gets the review id
	 * @return int
	 */
	public function getReviewID(){return $this->mReviewID;}
	/**
	 * Gets the ID of the Article the review is about
	 * @return int
	 */
	public function getAboutID(){return $this->mAboutID;}
	/**
	 * Gets the revision number of the article this review is about
	 * @return int
	 */
	public function getAboutrevisionID(){return $this->mAboutrevisionID;}
	/**
	 * Gets the user id of the reviewer
	 * @return
	 */
	public function getReviewerID(){return $this->mReviewerID;}
	/**
	 * Gets the text of the review
	 * @return string
	 */
	public function getReviewerText(){return $this->mReviewerText;}
	
	public function getTimestamp(){return $this->mTimestamp;}
		
	private function load(){
		$db = wfGetDB(DB_SLAVE);
		
		$articleId = $this->mArticle->getID();
		
		$selectq=<<<END

		SELECT  `ewr_id`, `ewr_waiting_id`, `ewr_assigned_id`, `ewr_review_id`, `ewr_about_id`, 
		`ewr_aboutrevision_id`, `ewr_reviewer_id`, `ewr_reviewer_text`, `ewr_timestamp`
FROM `ew_review` WHERE `ewr_review_id` = $articleId

END;
		$res = $db->query($selectq);
		if($res->numRows()>0){
			$row = $db->fetchRow($res);
			$this->mDBID = $row[0];
			$this->mWaitingID = $row[1];
			$this->mAssignedID = $row[2];
			$this->mReviewID = $row[3];
			$this->mAboutID = $row[4];
			$this->mAboutrevisionID = $row[5];
			$this->mReviewerID = $row[6];
			$this->mReviewerText = $row[7];
			$this->mTimestamp = $row[8];
			$db->freeResult( $res );
			return true;
		}else{
			return false;
		}
	}
	public function exists(){return $this->mExists;}
	/**
	 * Save new review
	 *
	 * @param Article $about
	 * @param User $reviewer
	 * @param int $revision
	 */
	static function saveNewReview(Article $review, EWArticle $aboutpage, User $reviewer){
		
		wfLoadExtensionMessages(EW_MESSAGES);
		
		$waitingid = ( $aboutpage->waiting() ) ? $aboutpage->getWaitingID() : 0;
		$assignedid = $aboutpage->getPendingAssignmentID($reviewer);
		$reviewid =  $review->getID();
		$aboutid = $aboutpage->getArticle()->getID();
		
		$aboutpage->getArticle()->loadContent();
		
		$aboutrevision = $aboutpage->getArticle()->getRevIdFetched();
		$reviewerid = $reviewer->getID();
		$reviewertext = $reviewer->getName();
		
		$db = wfGetDB(DB_MASTER);
		
		$timestamp = $db->timestamp();
		
		// Begin transaction
		$db->immediateBegin();
		
		// Inserts new review
		$db->insert(
			EW_TABLE_REVIEW,
			array(
				'ewr_waiting_id' => $waitingid,
				'ewr_assigned_id' => $assignedid,
				'ewr_review_id' => $reviewid,
				'ewr_about_id' => $aboutid,
				'ewr_aboutrevision_id' => $aboutrevision,
				'ewr_reviewer_id' => $reviewerid,
				'ewr_reviewer_text' => $reviewertext,
				'ewr_timestamp' => $timestamp
			),
			'EWReview::saveNewReview'
		);
		
		// Update Waiting table
		$db->update(
			EW_TABLE_WAITING, 
			array(
				'eww_pending' => false
			), 
			array(
				'eww_id' => $waitingid
			),
			'EWReview::saveNewReview'
			);
		
		
					
		// Assignment table
		$db->update(
			EW_TABLE_ASSIGNED, 
			array(
				'ewa_pending' => false
			), 
			array(
				'ewa_id' => $assignedid
			),
			'EWReview::saveNewReview'
			);

		$update_messages = wfMsg('reviewcreated');
			
		// Update assigned article
		$about_content = $aboutpage->getArticle()->fetchContent();
		$updateassigned_content = EWArticle::removeAnnotationAssigned($about_content);
		if($about_content != $updateassigned_content )
			$update_messages .= " ". wfMsg('assignedremoved');
			
		// Update waiting article
		$updatewaiting_content = EWArticle::removeAnnotationWaiting($updateassigned_content);
		if($updateassigned_content != $updatewaiting_content )
			$update_messages .= " ". wfMsg('waitingremoved');
		
		// Do edit
		$aboutpage->getArticle()->doEdit($updatewaiting_content,$update_messages,MINOR_EDIT);			

		// DO commit
		$db->immediateCommit();
		
		// Notify action 'make review'
		EWNotifier::notifyActionMakeReview($reviewer, $aboutpage->getArticle()->mTitle, $review->mTitle);
		return true;
	}
	/**
	 * Create from title
	 *
	 * @param Title $title
	 * @return unknown
	 */
	static function createFromTitle(Title $title){
		if( EWReview::isReview(new Article($title)) ) return new EWReview(new Article($title));
		else return null;
	}
	/**
	 * Create from article
	 *
	 * @param Article $article
	 * @return unknown
	 */
	static function createFromArticle(Article $article){
		if( EW_NS_REVIEWS == $article->getTitle()->getNamespace() ) {
			$review= new EWReview($article);
			if( $review->getAboutID() != null ) return $review;
		}
		else return null;
	}
	/**
	 * Check if article is a review
	 *
	 * @param Article $article
	 * @return Boolean
	 */
	static public function isReview(Article $article){
		$review = EWReview::createFromArticle($article);
		return ( $review != null );
	}
	/**
	 * Check weather a user's review about an article exists
	 * $strict means if consider only current article's revision or not
	 * 
	 * @param Article $article
	 * @param User $user
	 * @param boolean $strict = false
	 */
	static public function existsForArticle(Article $article,User $user,$strict = true){
		$db = wfGetDB(DB_SLAVE);
		$article->fetchContent();
		
		$condition['ewr_about_id'] =  $article->getID();
		if($strict) $condition['ewr_aboutrevision_id'] = $article->getRevIdFetched();
		$condition['ewr_reviewer_id'] = $user->getID();

		$res = $db->select(
			EW_TABLE_REVIEW,
			array('ewr_id'),
			$condition,
			'EWReview::existsForArticle'
		);
		$returnvalue = $res->numRows();
		$db->freeResult( $res );
		return $returnvalue;
	}
}
/**
 * Analyzer for assignments about an article
 * $assignment matrix has to be the private field of the EWArticle class
 * TODO more comment to this!
 * Order:
 * 	0		'ewa_id'
	1		'ewa_waiting_id'
	2		'ewa_revision_id'
	3		'ewa_reviewer_id'
	4		'ewa_reviewer_text'
	5		'ewa_manager_id'
	6		'ewa_manager_text'
	7		'ewa_pending'
	8		'ewa_timestamp'
 */
class EWAssignmentAnalyzer{
	private $mAssignmentMatrix = null;
	function EWAssignmentAnalyzer(&$assignmentMatrix){
		$this->mAssignmentMatrix = &$assignmentMatrix;
	}
	public function hasPendingAssignment(User $user){
		return ($this->getPendingAssignmentID($user) != 0);
	}
	/**
	 * If no pending assignment, returns 0
	 * @param User $user
	 * @return int
	 */
	public function getPendingAssignmentID(User $user){
		foreach($this->mAssignmentMatrix as $assignment){
			if(
				$assignment[7] == true // is pending
				&&
				$assignment[3] == $user->getID() // is user
			) return $assignment[0];
		}
		return 0;		
	}
}
?>