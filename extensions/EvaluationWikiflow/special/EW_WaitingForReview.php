<?php
/**
 * Waiting for review
 * @author Enrico Daga
 */



/**
 * Static launcher
 */
function ewfRunEWWaitingForReview() {
    EWWaitingForReview::run();
}
 
class EWWaitingForReview extends SpecialPage {
	function EWWaitingForReview() {
		SpecialPage::SpecialPage("WaitingForReview", '', true, 'ewfRunEWWaitingForReview');
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
		 * Else show list o waiting pending article's
		 */
		EWWaitingForReview::printPendigWaitingForReview();
	}
	function printPendigWaitingForReview(){
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select(
			EW_TABLE_WAITING,
			array(
				'eww_page_id',
				'eww_namespace_id',
				'eww_page_title',
				'eww_revision_id',
				'eww_user_id',
				'eww_user_text',
				'eww_timestamp'
			),
			array('eww_pending'=>true),
			array('ORDER BY eww_page_title')
		);
		
		$wikitable =<<<END
		{| class="ewtable"
		|-
		! Namespace
		! Page
		! Submitted by
		! Waiting since

END;

		while( ( $row = $db->fetchRow( $res ) ) != null ){
			
			$title = Title::newFromText($row[2],$row[1]);
			$namespace = $title->getNsText();
			$titletext = $title->getText();
			$titlefull = $title->getFullText();
			$username = $row[5]; 
			$timestampf = date("d M Y, \\hH:i",strtotime($row[6]));
			$wikirow=<<<END
			|-
			| $namespace
			| [[$titlefull|$titletext]]
			| [[User:$username]]
			| $timestampf
			
END;
			$wikitable .= $wikirow;
		}
		$wikitable .= "\n|}\n";
		global $wgOut;
		$wgOut->addHtml(ewfSandboxParse($wikitable));
	}
}
?>