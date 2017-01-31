<?php
/**
 * Assigned reviews
 * @author Enrico Daga
 */



/**
 * Static launcher
 */
function ewfRunEWAssignedReviews() {
    EWAssignedReviews::run();
}
 
class EWAssignedReviews extends SpecialPage {
	function EWAssignedReviews() {
		SpecialPage::SpecialPage("AssignedReviews", '', true, 'ewfRunEWAssignedReviews');
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
		EWAssignedReviews::printAssignedReviews(null,false);
	}
	function printAssignedReviews( $articleid = null, $pending = null){
		
		$conditions="";
		
		if( $articleid != null ){
			$conditions = "where `ewa_page_id`=" . $articleid;
		}

		if( $pending != null ){
			$conditions = "where `ewa_pending`=" . $pending;
		}
		
		$db = wfGetDB(DB_SLAVE);

		$tableassigned=EW_TABLE_ASSIGNED;
		$sql =<<<END
		select 
		`ewa_page_id`,
		`ewa_namespace_id`,
		`ewa_page_title`,
		`ewa_revision_id`,
		`ewa_reviewer_id`,
		`ewa_reviewer_text`,
		`ewa_manager_id`,
		`ewa_manager_text`,
		`ewa_timestamp`,
		`ewa_pending`
		from `$tableassigned`
		$conditions
		ORDER BY `ewa_timestamp` DESC
		
END;

		$res = $db->doQuery($sql);
		$wikitable =<<<END
		{| class="ewtable"
		|-
		! Namespace
		! Page
		! Reviewer
		! Assigned by
		! Since
		! Pending

END;

		while( ( $row = $db->fetchRow( $res ) ) != null ){
			
			$title = Title::newFromText($row[2],$row[1]);
			$namespace = $title->getNsText();
			$titletext = $title->getText();
			$titlefull = $title->getFullText();
			$username = $row[5]; 
			$managername = $row[7];
			$timestampf = date("d M Y, \\hH:i",strtotime($row[8]));
			$pending = ($row[9] == true) ? 'Yes' : 'No';
			
			$wikirow=<<<END
			|-
			| $namespace
			| [[$titlefull|$titletext]]
			| [[User:$username]]
			| [[User:$managername]]
			| $timestampf
			| $pending
			
END;
			$wikitable .= $wikirow;
		}
		$wikitable .= "\n|}\n";
		global $wgOut;
		$wgOut->addHtml(ewfSandboxParse($wikitable));
	}
}
?>