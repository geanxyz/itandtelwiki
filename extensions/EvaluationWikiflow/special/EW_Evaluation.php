<?php
/**
 * Evaluation
 * @author Enrico Daga
 */



/**
 * Static launcher
 */
function ewfRunEWEvaluation() {
    EWEvaluation::run();
}
 
class EWEvaluation extends SpecialPage {
	function EWEvaluation() {
		SpecialPage::SpecialPage("Evaluation", '', true, 'ewfRunEWEvaluation');
		wfLoadExtensionMessages(EW_MESSAGES);
	}
	public static function run() {
        
        global $wgOut;
        global $wgUser;
        // Only If User is allowed to see evaluation workflow        
        if($wgUser->isAllowed(EW_ACTION_VIEW)){
        	global $wgTitle;
        	$ewa = new EWArticle( new Article( $wgTitle ) );
        	if( (! $ewa->certified() ) && $ewa->toEvaluate() )
				EWEvaluation::printEvaluationForm();
			if( $ewa->certified() ){
				$wgOut->addHtml(wfMsg('contentcertified'));
				if ( $ewa->getCertifiedID() != $ewa->getArticle()->getID() ){
					$newcontenttitle = Title::newFromID($ewa->getCertifiedID());
					$newcontentpagename = $newcontenttitle->getFullText();
					$wikimessage = wfMsg('contentcertifiedcopiedto') . "[[$newcontentpagename]]";
					$wgOut->addHtml(ewfSandboxParse($wikimessage));
				}
			}
			
			$title=$wgTitle->getFullText();  
			$otheractions=ewfSandboxParse("__NOEDITSECTION__\n==Reports==");
	        $otheractions.=<<<END
	        <ul>
	        <li><a href="?title=$title&action=evaluation&subaction=reviewrequests">See review requests table</a></li>
	        <li><a href="?title=$title&action=evaluation&subaction=assignedreviews">See assignments table</a></li>
	        <li><a href="?title=$title&action=evaluation&subaction=reviewsabout">See reviews table</a></li>
	        <li><a href="?title=$title&action=evaluation&subaction=trace">See evaluation trace</a></li>        
	        </ul>
END;
			$wgOut->addHtml($otheractions);
			
			global $wgRequest;
			$subaction=$wgRequest->getVal('subaction');
			if( $subaction == 'reviewrequests') EWEvaluation::printAskForReviewTable();
			if( $subaction == 'assignedreviews') EWEvaluation::printAssignmentsTable();
			if( $subaction == 'reviewsabout') EWEvaluation::printReviewsTable();
			if( $subaction == 'trace') EWEvaluation::printEvaluationTrace();
			
        }else{
        	$wgOut->permissionRequired(EW_ACTION_VIEW);
        }
	}
	private function printEvaluationForm(){
		global $wgUser, $wgTitle;
        
		$title = $wgTitle->getFullText();
		
		$iswaiting = EWArticle::newFromTitle($wgTitle)->waiting();
		$needsedit = EWArticle::newFromTitle($wgTitle)->needsEdit();
		
        $askforreview_label = wfMsg('askforreview');
        $assignreview_label = wfMsg('assignreview');
        $makereview_label = wfMsg('makereview');
        $certify_label = wfMsg('certify');
        
        $askforreview_allowed = ( $wgUser->isAllowed(EW_ACTION_ASK) ) ;
        $assignreview_allowed = ( $wgUser->isAllowed(EW_ACTION_ASSIGN) ) ;
        $makereview_allowed = ( $wgUser->isAllowed(EW_ACTION_MAKE) ) ;
        $certify_allowed = ( $wgUser->isAllowed(EW_ACTION_CERTIFY) ) ;
        
        $askforreview_disabled = ( $askforreview_allowed && (!$iswaiting) && (!$needsedit) ) ? '':'DISABLED=DISABLED';
        $assignreview_disabled = ( $assignreview_allowed ) ? '':'DISABLED=DISABLED';
        $makereview_disabled = ( $makereview_allowed ) ? '':'DISABLED=DISABLED';
        $certify_disabled = ( $certify_allowed ) ? '':'DISABLED=DISABLED';

        $askforreview_message = ($askforreview_allowed) ? wfMsg('askforreview') : "";
        if($iswaiting) $askforreview_message = wfMsg('articlealreadywaiting') ;
        if($needsedit) $askforreview_message = wfMsg('needsedit');
        $assignreview_message = ($assignreview_allowed) ? wfMsg('assignto') : wfMsg('norights');
        $makereview_message = wfMsg('makeyourreview') . $title;
        $certify_message = ($certify_allowed) ? wfMsg('certify'): wfMsg('norights');
        
        $outputhtml = ewfSandboxParse("__NOEDITSECTION__\n==Actions==");
        $outputhtml .=<<<END
        
		<form class="ewForm" method="GET">
		<input type="hidden" name="title" value="$title"/>
		<input type="hidden" name="action" value="askforreview"/>
		<button type="submit" $askforreview_disabled title="$askforreview_message">$askforreview_label</button>
		</form>
		<form class="ewForm" method="GET">
		<input type="hidden" name="page_title" value="$title"/>
		<input type="hidden" name="title" value="Special:AssignReview"/>
		<button type="submit" $assignreview_disabled title="$assignreview_message">$assignreview_label</button>
		</form>
		<form class="ewForm" method="GET">
		<input type="hidden" name="about" value="$title"/>
		<input type="hidden" name="title" value="Special:MakeReview"/>
		<button type="submit" $makereview_disabled title="$makereview_message">$makereview_label</button>
		</form>
		<form class="ewForm" method="GET">
		<input type="hidden" name="page_title" value="$title"/>
		<input type="hidden" name="title" value="Special:Certify"/>
		<button type="submit" $certify_disabled title="$certify_message">$certify_label</button>
		</form>		
END;

        global $wgOut;
        $wgOut->addHtml(ewfSandboxParse("__NOEDITSECTION__"));

        $wgOut->addHtml($outputhtml);        
	}
	
	/**
	 * This function prints assignment table
	 */
	private function printAssignmentsTable(){
		global $wgTitle;
		$article = new Article($wgTitle);
		$article->loadContent();
		$currentrevision = $article->getRevIdFetched();
		
		$pageid=$article->getID();
		$db = wfGetDB(DB_SLAVE);
		
		$tableassigned = EW_TABLE_ASSIGNED;
		$sql =<<<END
		select `ewa_page_id`,
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
		where `ewa_page_id`=$pageid
		order by `ewa_timestamp` desc
END;

		$res = $db->doQuery($sql);
		
		$wikitable=<<<END
		__NOEDITSECTION__
		\n===Assignments===\n

END;

		$hasassignments=false;
		while( ( $row = $db->fetchRow( $res ) ) != null ){
			if(!$hasassignments)
				$wikitable .=<<<END
		{| class="ewtable"
		|-
		! About revision
		! Reviewer
		! Assigned by
		! Since
		! Pending

END;
			$hasassignments=true;
			$title = Title::newFromText($row[2],$row[1]);

			$revisionid = $row[3];
			$titlefull = $title->getFullText();
			$username = $row[5]; 
			$managername = $row[7];
			$timestampf = date("d M Y",strtotime( substr($row[8],0,4)  . '-' . substr($row[8],4,2) . '-' . substr($row[8],6,2)  ));
			$pending = ($row[9] == true) ? 'Yes' : 'No';
			
			$revisionurl = ( $currentrevision == $revisionid ) ? "'''current'''" : "[{{fullurl:$titlefull|oldid=$revisionid}} $revisionid]";
			
			$wikirow=<<<END
			|-
			| $revisionurl
			| [[User:$username|$username]]
			| [[User:$managername|$managername]]
			| $timestampf
			| $pending
			
END;
			$wikitable .= $wikirow;
			
		}
		if($hasassignments){
			$wikitable .= "\n|}\n";
		} else $wikitable .= wfMsg('noassignments');
		
		global $wgOut;
		$wgOut->addHtml(ewfSandboxParse($wikitable));
	}
	private function printReviewsTable(){
		global $wgTitle;
		$article = new Article($wgTitle);
		$article->loadContent();
		$currentrevision = $article->getRevIdFetched();

		$articleid = $article->getID();
		$db = wfGetDB(DB_SLAVE);
		$tablereview = EW_TABLE_REVIEW;
		$sql =<<<END
		select
			`ewr_review_id`,
			`ewr_about_id`,
			`ewr_aboutrevision_id`,
			`ewr_reviewer_id`,
			`ewr_reviewer_text`,
			`ewr_timestamp`
		from `$tablereview`
		where `ewr_about_id` = $articleid
		order by `ewr_timestamp` desc
END;
		$res = $db->doQuery($sql);
		
		$wikitable=<<<END
		__NOEDITSECTION__
		\n===Reviews===\n
END;
		$hasreviews=false;
		while( ( $row = $db->fetchRow( $res ) ) != null ){
			if(! $hasreviews)
				$wikitable .=<<<END
		{| class="ewtable"
		|-
		! Review
		! About revision
		! Reviewer
		! When

END;
			
			$hasreviews=true;
			global $wgTitle;
			$titlefull = $wgTitle->getFullText();
			$revisionid = $row[2];
			$revisionurl = ( $currentrevision == $revisionid ) ? "'''current'''" : "[{{fullurl:$titlefull|oldid=$revisionid}} $revisionid]";
			
			$username = $row[4]; 
			//$timestampf = date("d M Y, \\hH:i",strtotime($row[5]));
			$timestampf = date("d M Y",strtotime( substr($row[5],0,4)  . '-' . substr($row[5],4,2) . '-' . substr($row[5],6,2) ));
			
			$reviewtitle = Title::newFromID($row[0]);
			$reviewtitlefull = $reviewtitle->getFullText();
			$reviewtitleshort = $reviewtitle->getText();
			
			$wikirow =<<<END
			|-
			| [[$reviewtitlefull|$reviewtitleshort]]
			| $revisionurl
			| [[User:$username|$username]]
			| $timestampf  
			
END;
			$wikitable .= $wikirow;
		}		
		if($hasreviews){
			$wikitable .= "\n|}\n";
		} else $wikitable .= wfMsg('noreviews');
		global $wgOut;
		$wgOut->addHtml(ewfSandboxParse($wikitable));		
	} 
	/**
	 * This function prints the review requests table
	 */
	private function printAskForReviewTable(){
		global $wgTitle;
		$article = new Article($wgTitle);
		$article->loadContent();
		$currentrevision = $article->getRevIdFetched();
		
		$pageid=$article->getID();
		$db = wfGetDB(DB_SLAVE);
		
		$tablewaiting = EW_TABLE_WAITING;
		$sql =<<<END
		select `eww_page_id`,
				`eww_namespace_id`,
				`eww_page_title`,
				`eww_revision_id`,
				`eww_user_id`,
				`eww_user_text`,
				`eww_timestamp`,
				`eww_pending`
		from `$tablewaiting`
		where `eww_page_id`=$pageid
		order by `eww_timestamp` desc
END;

		$res = $db->doQuery($sql);
		
		$wikitable =<<<END
		__NOEDITSECTION__
		\n===Review requests===\n
END;

		$hasrequests=false;
		while( ( $row = $db->fetchRow( $res ) ) != null ){
			if(! $hasrequests )		
				$wikitable .=<<<END
		{| class="ewtable"
		|-
		! About revision
		! Requested by
		! Since
		! Pending
		
END;
			$hasrequests=true;
			$title = Title::newFromText($row[2],$row[1]);

			$revisionid = $row[3];
			$titlefull = $title->getFullText();
			$username = $row[5]; 
			
			$timestampf = date("d M Y",strtotime( substr($row[6],0,4)  . '-' . substr($row[6],4,2) . '-' . substr($row[6],6,2)  ));
			$pending = ($row[7] == true) ? 'Yes' : 'No';
			
			$revisionurl = ( $currentrevision == $revisionid ) ? "'''current'''" : "[{{fullurl:$titlefull|oldid=$revisionid}} $revisionid]";
			
			$wikirow=<<<END
			|-
			| $revisionurl
			| [[User:$username|$username]]
			| $timestampf
			| $pending
			
END;
			$wikitable .= $wikirow;
		}
		if($hasrequests){
			$wikitable .= "\n|}\n";
		} else $wikitable .= wfMsg('noreviewrequested');
		global $wgOut;
		$wgOut->addHtml(ewfSandboxParse($wikitable));
	}
	
	private function printEvaluationTrace(){
		global $wgTitle;
		$article = new Article($wgTitle);
		$article->loadContent();
		$currentrevision = $article->getRevIdFetched();
		
		$titlefull = $article->mTitle->getFullText();
		
		$articleid=$article->getID();
		
		$db = wfGetDB(DB_SLAVE);
		
		$tablewaiting = EW_TABLE_WAITING;
		$tableassigned = EW_TABLE_ASSIGNED;
		$tablereview = EW_TABLE_REVIEW;
		$sql =<<<END
		select 
			1,
			`eww_revision_id`,
			`eww_user_id`,
			`eww_user_text`,
			NULL,
			`eww_timestamp`,
			`eww_pending`,
			NULL,
			NULL
		from `$tablewaiting`
		where `eww_page_id`=$articleid
union
		select
			2,
			`ewr_aboutrevision_id`,
			`ewr_reviewer_id`,
			`ewr_reviewer_text`,
			`ewr_review_id`,
			`ewr_timestamp`,
			NULL,
			NULL,
			NULL
		from `$tablereview`
		where `ewr_about_id` = $articleid		
union
		select
			3,
			`ewa_revision_id`,
			`ewa_reviewer_id`,
			`ewa_reviewer_text`,
			NULL,
			`ewa_timestamp`,
			`ewa_pending`,
			`ewa_manager_id`,
			`ewa_manager_text`
		from `$tableassigned`
		where `ewa_page_id` = $articleid
		order by 6 desc
END;

		$res = $db->doQuery($sql);
		
		$wikitable =<<<END
		
		__NOEDITSECTION__
		\n===Evaluation trace===\n

END;

		$hastrace=false;
		while( ( $row = $db->fetchRow( $res ) ) != null ){
			$hastrace=true;
			$action=$row[0];

			$revisionid = $row[1];
			$username = $row[3]; 
			
			$timestampf = date("d M Y",strtotime( substr($row[5],0,4)  . '-' . substr($row[5],4,2) . '-' . substr($row[5],6,2)  ));

			$revisionurl = ( $currentrevision == $revisionid ) ? "'''".$revisionid ."'''".  " (current)" : "[{{fullurl:$titlefull|oldid=$revisionid}} $revisionid]";
			
			switch($action){
				case 1:
					$actionlabel='requested a review about revision';
					$pending = ($row[6] == true) ? '(pending)' : ' ';
					$wikirow = "\n* ''$timestampf'' [[User:$username|$username]] $actionlabel $revisionurl ''$pending''";
					$wikitable .= $wikirow;
				break;
				case 2:
					$actionlabel='reviewed revision';
					$reviewid=$row[4];
					$reviewtitle = Title::newFromID($reviewid);
					$reviewtitlefull = $reviewtitle->getFullText();
					$reviewtitleshort = $reviewtitle->getText();	
					$reviewlink = "[[$reviewtitlefull|see review]]";
					$wikirow = "\n* ''$timestampf'' [[User:$username|$username]] $actionlabel $revisionurl: $reviewlink";
					$wikitable .= $wikirow;
				break;
				case 3:
					$pending = ($row[6] == true) ? '(pending)' : ' ';
					$managername=$row[8];
					$actionlabel='assigned review of ';
					$wikirow = "\n* ''$timestampf'' [[User:$managername|$managername]] $actionlabel $revisionurl to [[User:$username|$username]] ''$pending''";
					$wikitable .= $wikirow;
				break;
			}
			
		}
		$wikitable .= ($hastrace)?"\n\n":"\n" . wfMsg('notrace');
		global $wgOut;
		$wgOut->addHtml(ewfSandboxParse($wikitable));	
	}
}
?>