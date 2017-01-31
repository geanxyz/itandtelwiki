<?php
/**
 * Assignreview
 * @author Enrico Daga
 */



/**
 * Static launcher
 */
function ewfRunEWAssignReview() {
    EWAssignReview::run();
}
 
class EWAssignReview extends SpecialPage {
        function EWAssignReview() {
			SpecialPage::SpecialPage("AssignReview", '', true, 'ewfRunEWAssignReview');
			wfLoadExtensionMessages(EW_MESSAGES);
        }
 
        function run() {
			global $wgRequest,$wgOut;
        	$page_title = $wgRequest->getVal('page_title');
        	$reviewer_name = $wgRequest->getVal('reviewer_name');
        	
        	if($page_title != null && $reviewer_name != null){
        		$title = Title::newFromText($page_title);
        		$article = new Article($title);
        		/**
        		 * if article exists
        		 */
        		if( (! $title == null ) && (  $article->exists() ) ){
        			/**
        			 * If is a valid article
        			 */
        			$ewarticle=new EWArticle($article);
        			if( $ewarticle->toEvaluate() ){
        			    /**
	        			 * If user exists and is valid for this action
	        			 */
	        			$reviewer = User::newFromName($reviewer_name);
	        			if( $reviewer != null && $reviewer->isAllowed(EW_ACTION_MAKE) ){
	        				/**
	        				 * Check if is not already assigned to this user and that assignment is pending
	        				 */
	        				if(!$ewarticle->hasPendingAssignment($reviewer)){
	        				EWAssignReview::assignReview($ewarticle,$reviewer);
	        				$wgOut->addHtml(wfMsg('reviewassigned') . ": " . $reviewer->getName());
	        				$wgOut->redirect($ewarticle->getArticle()->getTitle()->getFullUrl() );
	        				$page_title = null;
	        				$reviewer_name = null;
	        				}else{
	        					$wgOut->addHtml(wfMsg('alreadyassigned'));	
	        				}
	        			}else{
	        				$wgOut->addHtml(wfMsg('reviewernotvalid'));
	        			}        				
        			}else{
        				$wgOut->addHtml(wfMsg('articlenotvalid'));
        			}
        		}else{
        			$wgOut->addHtml(wfMsg('articledoesnotexists'));
        		}
        	}
        	EWAssignReview::printAssignReviewForm($page_title, $reviewer_name);
        }
        private function printAssignReviewForm($page_value = '', $reviewer_value = ''){
        	global $wgUser,$wgOut;
        	
        	$page_label = wfMsg('article');
        	
        	$reviewer_label = wfMsg('reviewer');
        	$assignreview_disabled = ( $wgUser->isAllowed(EW_ACTION_ASSIGN) ) ? "" : "DISABLED=DISABLED";
        	$assignreview_label = wfMsg('assignreview');
        	
        	$title_value = Title::newFromText('AssignReview',NS_SPECIAL)->getFullText();
        	
        	$reviewerlist=ewfGetAllReviewers();
        	
        	$reviewersoptions="";
        	foreach($reviewerlist as $reviewerUser){
        		$userName=$reviewerUser->getName();
        		$userRealName=$reviewerUser->getRealName();
        		$selected=($userName==$reviewer_value)?"SELECTED":"";
        		$reviewerOption="<option id=\"reviewer_".$userName."\" ". $selected . " value=\"".$userName."\">".$userRealName . " (" . $userName . ")</option>";
        		$reviewersoptions.="\n".$reviewerOption;
        	}
        	
        	$outputhtml=<<<END
			<form method="GET">
			<input type="hidden" name="title" value="$title_value"/>
			<p><b>$page_label</b> <input type="text" name="page_title" value="$page_value" size="40" $assignreview_disabled/></p>
			<p><b>$reviewer_label</b> <select id="reviewer_name" name="reviewer_name"  $assignreview_disabled>$reviewersoptions</select></p>
			<p><input type="submit" value="$assignreview_label" $assignreview_disabled/></p>
			</form>
END;
			$wgOut->addHtml($outputhtml);
        }
        /**
         * This function is private because does not check anything...
         *
         * @param EWArticle $ewarticle
         * @param User $user
         */
        private function assignReview($ewarticle,$user){
        	$ewarticle->saveAssigned($user);
        }
}
?>