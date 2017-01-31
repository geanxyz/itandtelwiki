<?php
/**
 * Certify
 * @author Enrico Daga
 */



/**
 * Static launcher
 */
function ewfRunEWCertify() {
    EWCertify::run();
}
 
class EWCertify extends SpecialPage {
	function EWCertify() {
		SpecialPage::SpecialPage("Certify", '', true, 'ewfRunEWCertify');
		wfLoadExtensionMessages(EW_MESSAGES);
	}
 
	function run() {
		global $wgOut,$wgUser;
		/**
		 * If user doesn't have EW_ACTION_VIEW
		 */
		if( ! $wgUser->isAllowed(EW_ACTION_VIEW) ) {
			$wgOut->permissionRequired(EW_ACTION_VIEW);
			return;
		}
		
		/**
		 * 
		 */
		global $wgRequest;
		$page_title = $wgRequest->getVal('page_title');
		$certified_title = $wgRequest->getVal('certified_title');
		$certify_action = $wgRequest->getVal('certify_action');

		if ( $page_title && $certified_title && $certify_action ){
			if ( ! Title::newFromText( $page_title ) instanceof Title ){
			} elseif ( ! Title::newFromText( $certified_title ) instanceof Title ){
			} else {
				/**
				 * If user doesn't have EW_ACTION_CERTIFY
				 */
				if( ! $wgUser->isAllowed(EW_ACTION_CERTIFY) ) {
					$wgOut->permissionRequired(EW_ACTION_CERTIFY);
					return;
				}
				
				if(!Title::newFromText( $page_title )->exists()){
					$wgOut->errorpage('Error','articledoesnotexists');
					return;
				}
				if(Title::newFromText( $certified_title )->exists()){
					$wgOut->errorpage('Error','articlealreadyexists');
					return;
				}
				if(  
					( ! $certify_action == EW_MODE_MOVE_PAGE ) &&
					( ! $certify_action == EW_MODE_LOCK_AND_COPY ) 
				){
					return;
				}
				if ( EWCertify::certify( Title::newFromText( $page_title ), Title::newFromText( $certified_title ) , $certify_action) ){
					global $wgOut;
					$wgOut->redirect( Title::newFromText( $certified_title )->getFullURL() );
					return;
				}
			}
		}
		EWCertify::printCertifyForm();	
	}
	function printCertifyForm(){
        global $wgOut,$wgUser,$wgRequest;

        $title_label = wfMsg('article');
        $move_label = wfMsg('moveto');
        $page_title = $wgRequest->getVal('page_title');
        $certify_action = $wgRequest->getVal('certify_action');
        
        $certify_label = wfMsg('certify');
        $certify_allowed = ($wgUser->isAllowed(EW_ACTION_CERTIFY));
        
        $certify_disabled = ($certify_allowed) ? "" : "DISABLED=DISABLED";
        
        $move_action_value=EW_MODE_MOVE_PAGE;
        $copy_action_value=EW_MODE_LOCK_AND_COPY;
        
        $move_action_checked = ($certify_action==EW_MODE_MOVE_PAGE) ? "CHECKED" :"";
        $copy_action_checked = ($certify_action==EW_MODE_LOCK_AND_COPY) ? "CHECKED" :"";
        
		$move_label = wfMsg('move');
		$lockandcopy_label = wfMsg('lockandcopy');
        $target_page_label = wfMsg('targetpagename');
        
        $outputhtml =<<<END
		<form class="ewfForm" method="GET">
		<input type="hidden" name="title" value="Special:Certify"/>
		<p><b>$title_label</b> <input type="text" $certify_disabled size="40" value="$page_title" name="page_title" /></p>
		<p>
		 <input type="radio" name="certify_action" $certify_disabled value="$move_action_value" id="ew_move" $move_action_checked/><label for="ew_move">$move_label</label>
		 <input type="radio" name="certify_action" $certify_disabled value="$copy_action_value" id="ew_copy" $copy_action_checked/><label for="ew_copy">$lockandcopy_label</label>
		</p>
        <p><b>$target_page_label</b> <input type="text" $certify_disabled size="40" value="" name="certified_title"/></p>
        <p><button type="submit" $certify_disabled>$certify_label</button></p>
        </form>
END;
		$wgOut->addHtml($outputhtml);
	}
	function certify(Title $page_title, Title $certified_title,$mode){
		$certifiedArticle = new EWArticle(new Article($page_title));
		$certifiedArticle->saveCertified( $certified_title, $mode);
		return true;
	}
}
?>