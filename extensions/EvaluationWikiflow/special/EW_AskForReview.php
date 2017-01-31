<?php
/**
 * AskForReview
 * @author Enrico Daga
 */


/**
 * Static launcher
 */
function ewfRunEWAskForReview() {
    EWAskForReview::run();
}
 
class EWAskForReview extends SpecialPage {
        function EWAskForReview() {
			SpecialPage::SpecialPage("AskForReview", '', true, 'ewfRunEWAskForReview');
			wfLoadExtensionMessages(EW_MESSAGES);
        }
 
        function run() {
        	global $wgRequest,$wgOut;
        	$page_title = $wgRequest->getVal('page_title');
        	
        	if($page_title != null){
        		$title = Title::newFromText($page_title);
        		$article = new Article($title);
        		if( (! $title == null ) && (  $article->exists() ) ){
        			EWAskForReview::askForReview($title);
        		}else{
        			$wgOut->addHtml(wfMsg('articledoesnotexists'));
        		}
        	}
        	EWAskForReview::printAskForReviewForm();
        }
        function printAskForReviewForm(){
        	global $wgOut,$wgUser;

        	$title_label = wfMsg('article');
        	$askforreview_label = wfMsg('askforreview');
        	$askforreview_allowed = ($wgUser->isAllowed(EW_ACTION_ASK));
        	
        	$askforreview_disabled = ($askforreview_allowed) ? "" : "DISABLED=DISABLED";
        	
        	$outputhtml =<<<END
			<form class="efForm" method="GET">
        	<p><b>$title_label</b> <input $askforreview_disabled type="text" size="40" value="" name="page_title"/></p>
        	<p><button type="submit" $askforreview_disabled>$askforreview_label</button></p>
        	</form>
END;
			$wgOut->addHtml($outputhtml);
        }
        /**
         * Execute actions ask for review
         * this is public because is used also from action=askforreview hook...
         *
         * @return boolean
         */
        function askForReview($title){
        	global $wgUser,$wgOut;
            if( ! $wgUser->isAllowed(EW_ACTION_ASK) ){
            	$wgOut->permissionRequired(EW_ACTION_ASK);
            	return false;
            }
            if( EWArticle::isToEvaluate( new Article($title) ) ){
            	$article = EWArticle::newFromTitle($title);
            	if( (! $article->waiting()) && $article->saveWaiting() ) {
            		$wgOut->addHtml(wfMsg('waitingsaved'));
            		$wgOut->redirect($title->getFullUrl());
            		
            		// Notify action
            		EWNotifier::notifyActionAskForReview($wgUser,$title);
            		return true;
            	}else{
            		$wgOut->addHtml(wfMsg('articlealreadywaiting'));
            	}
            }else{
            	$wgOut->addHtml(wfMsg('isnottoevaluate'));
            	return false;
            }
            return false;     	
        }
}

?>