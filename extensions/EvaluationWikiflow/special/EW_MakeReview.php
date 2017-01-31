<?php
/**
 * Make review
 * @author Enrico Daga
 */



/**
 * Static launcher
 */
function ewfRunEWMakeReview() {
    EWMakeReview::run();
}
 
class EWMakeReview extends SpecialPage {
	function EWMakeReview() {
		SpecialPage::SpecialPage("MakeReview", '', true, 'ewfRunEWMakeReview');
		wfLoadExtensionMessages(EW_MESSAGES);
	}
	function run() {
		global $wgRequest, $wgOut;
		$about = $wgRequest->getVal('about');
		
		$showform = false;
		if($about!=null){
			$about_title = Title::newFromText($about);
			$about_article = new Article($about_title);
			$showform = true;
		}
		
		/**
		 * If exists $about check fot a correct title/article
		 */
		if( $about != null){        		
			if( (! $about_title == null ) && (  $about_article->exists() ) ){
        		EWMakeReview::makeReview($about_title);
        		return true;
        	}else{
        		$wgOut->addHtml(wfMsg('articledoesnotexists'));
        		$showform = true;
        	}
		}else{
			$showform = true;
		}
		if($showform==true){
			/**
			 * Print make review form
			 */
			EWMakeReview::printMakereviewForm();
		}
		return false;
	}
	function makeReview($title){
		/**
		 * If user has grant 
		 */
		global $wgUser,$wgOut;
		if(! $wgUser->isAllowed(EW_ACTION_MAKE) ){
			$wgOut->permissionRequired(EW_ACTION_MAKE);
			return false;
		}
		$article = new Article($title);
		$article->loadContent();
		
		$revisionid = $article->getRevIdFetched();
		
		// Check if article is in a toEvaluate category
		if(!EWArticle::isToEvaluate($article)){
			global $wgOut;
			$wgOut->addHtml(wfMsg('articlenotvalid'));
			return false;
		}
		
		$ewarticle = new EWArticle($article);		

		// Find SF form
		$formforreview = $ewarticle->getFormForReview();
		
		if($formforreview == null){
			global $wgOut;
			$wgOut->errorPage('error','error:formnotdefined');
			return false;
		}
		
		$reviewtitle = EWMakeReview::createReviewTitle($title, $wgUser);
		
		$wgOut->addHtml(ewfSandboxParse("__NOEDITSECTION__\n==" . $reviewtitle->getText() . "==\n" ) );
		
		// Insert SF Form
		global $sfgIP, $ewgIP;
		require_once ($sfgIP . '/specials/SF_AddData.php' );
		require_once ($ewgIP . '/includes/EW_InputPrinter.php' );
		
		EWInputPrinter::setAboutpage($title);
		EWInputPrinter::setAboutrevision($revisionid);
		EWInputPrinter::setReviewer($wgUser);
		
		printAddForm($formforreview->getText(), $reviewtitle->getFullText(),array());
		
		// Overwriting page title
		$wgOut->setPageTitle( wfMsg('makereview') );
		
		return true;
	}
	/**
	 * The content of this function is partially copied from Semantic Forms
	 *
	 * @param Title $title
	 * @param String $username
	 * @param Title $form
	 * @param int $namespace
	 * @param unknown_type $mode
	 * @return unknown
	 */
	private function createReviewTitle(Title $title,User $user, $mode = EW_MODE_INCREMENTAL){
		$pagetitle = $title->getText();
		$username = $user->getName();
		$targettext = $username . "_about_" . $pagetitle;
		if($mode == EW_MODE_TIMESTAMP){
			$targettext = $targettext ."_(" . date("D, d M Y, H:i") . ")";
		}elseif($mode == EW_MODE_INCREMENTAL){
			if(Title::newFromText($targettext,EW_NS_REVIEWS)->exists()){
				$title_number = 2;
				$target_title = null;
				do {
					$target_title = Title::newFromText($targettext . "_" . $title_number,EW_NS_REVIEWS);
					$title_number = $title_number + 1;
				} while ($target_title->exists());
				$targettext = $target_title->getText();
			}
		}
		return Title::newFromText( $targettext ,EW_NS_REVIEWS);
	}
	function printMakereviewForm(){
		global $wgUser;
		global $wgOut;
		
		$about_label = wfMsg('about');
		$button_label = wfMsg('makereview');
		$button_disabled = ($wgUser->isAllowed(EW_ACTION_MAKE)) ? '' : 'DISABLED="DISABLED"';
		$title_value = "Special:MakeReview";
		$outputform =<<<END
		<form method="GET">
		<input type="hidden" name="title" value="$title_value" $button_disabled/>
		<p><b>$about_label</b> <input type="text" name="about" size="50" $button_disabled/> 
		 <input type="submit" value="$button_label" $button_disabled/></p>
		</form>
END;
		$wgOut->addHtml($outputform);
	}
}
?>