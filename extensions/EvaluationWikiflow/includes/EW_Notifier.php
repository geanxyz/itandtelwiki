<?php
/**
 *
 * Static class that holds notification functionalities
 * @author Enrico Daga
 */
class EWNotifier{
	private static $PAGE_EMAIL_ASK="EWMailAsk";
	private static $PAGE_EMAIL_ASSIGN="EWMailAssign";
	private static $PAGE_EMAIL_MAKE="EWMailMake";
	private static $PAGE_EMAIL_CERTIFY="EWMailCertify";
	private static $NAMESPACE=8;
	/**
	 * Notification for action 'ask for review'.
	 * Recipients of this notifications are:
	 * - the User who requested the review
	 * - users who contributed to the article (if flag $ewgNotifyAllContributors=true)
	 * - reviewers: users that made a review before this request (if any) (if flag $ewgNotifyAllReviewers=true)
	 * - managers: users who have the grant of assigning reviews (default are members of the 'bureaucrat' group) (if flag $ewgNotifyAllManagers=true)
	 * 
	 * 
	 *
{{{REQUESTED_BY_REAL_NAME}}} -- the real name of the user 
{{{REQUESTED_BY_USER_NAME}}} -- the user name
{{{FULLPAGENAME}}} -- the name of the page object of the review request (with namespace)
{{{PAGENAME}}} -- the name of the page object of the review request (without namespace)
	 *  
	 * @return boolean true;
	 */
	public static function notifyActionAskForReview(User $requestedBy, Title $title){
		global $wgSitename;
		
		$title_text=$title->getFullText();
		$mail_title = $wgSitename ." notification: requested review for article " . $title_text;
		$username = $requestedBy->getName();
		
		$users = EWNotifier::prepareRecipients(EW_ACTION_ASK,$title,array($requestedBy));
				
		$mail_body=EWNotifier::getMailBody(EWNotifier::$PAGE_EMAIL_ASK);
		
		if($mail_body==null) return false;
		
		$placehoders=array(
			"{{{REQUESTED_BY_REAL_NAME}}}"=> $requestedBy->getRealName(),
			"{{{REQUESTED_BY_USER_NAME}}}"=> $requestedBy->getName(),
			"{{{FULLPAGENAME}}}"=> str_replace(' ','_',$title->getFullText()),
			"{{{PAGENAME}}}"=>$title->getText()
		);
		
		$mail_body = EWNotifier::parseBody($placehoders,$mail_body);

		EWNotifier::sendMail( $users, $mail_title, $mail_body );
		return true;
	}
	/**
     * Notification for action 'assign review'.
     * Recipients of this notifications are:
     * - the user who managed this assignment 
     * - the user that requested a review (if any) (if flag $ewgNotifyAllContributors=true);
     * - the reviewer who recieved the assignment; 
{{{ASSIGNED_BY_REAL_NAME}}} -- the real name of the user who managed the assignment
{{{ASSIGNED_BY_USER_NAME}}} -- the user name of the user who managed the assignment

{{{ASSIGNED_TO_REAL_NAME}}} -- the real name of the reviewer
{{{ASSIGNED_TO_USER_NAME}}} -- the user name of the reviewer

{{{FULLPAGENAME}}} -- the name of the page object of the review request (with namespace)
{{{PAGENAME}}} -- the name of the page object of the review request (without namespace)
     */
	public static function notifyActionAssignReview(User $assignedBy,User $assignedTo, Title $toReview){
		global $wgSitename;
		
		$title_text=$toReview->getFullText();
		$mail_title = $wgSitename ." notification: assigned review for article " . $title_text;
		
		$users = EWNotifier::prepareRecipients(EW_ACTION_ASSIGN,$toReview,array($assignedBy,$assignedTo));

		$mail_body=EWNotifier::getMailBody(EWNotifier::$PAGE_EMAIL_ASSIGN);
		
		if($mail_body==null) return false;
		
		$placehoders=array(
			"{{{ASSIGNED_BY_REAL_NAME}}}"=> $assignedBy->getRealName(),
			"{{{ASSIGNED_BY_USER_NAME}}}"=> $assignedBy->getName(),
			"{{{ASSIGNED_TO_REAL_NAME}}}"=> $assignedTo->getRealName(),
			"{{{ASSIGNED_TO_USER_NAME}}}"=> $assignedTo->getName(),
			"{{{FULLPAGENAME}}}"=> str_replace(' ','_',$toReview->getFullText()),
			"{{{PAGENAME}}}"=>$toReview->getText()
		);
		
		$mail_body = EWNotifier::parseBody($placehoders,$mail_body);

		EWNotifier::sendMail( $users, $mail_title, $mail_body );
		
		return true;
	}
	/**
	 * Notification for action 'make review'.
	 * Recipients of this notifications are:
	 * - the user who made the review
	 * - the user that requested a review (if any)
	 * - users who contributed to the article  (if flag $ewgNotifyAllContributors=true)
	 * - manager who assigned the review (if any) (if flag $ewgNotifyAllManagers=true)
 
{{{REVIEWER_REAL_NAME}}} -- the real name of the user 
{{{REVIEWER_USER_NAME}}} -- the user name

{{{FULLPAGENAME}}} -- the name of the page object of the review (with namespace)
{{{PAGENAME}}} -- the name of the page object of the review (without namespace)

{{{REVIEW_FULLPAGENAME}}} -- the name of the review page (with namespace)
{{{REVIEW_PAGENAME}}} -- the name of the review page (without namespace)

	 **/
	public static function notifyActionMakeReview(User $reviewer, Title $about, Title $review){
		
		global $wgSitename;
		
		$title_text=$about->getFullText();
		$review_text=$review->getFullText();
		$mail_title = $wgSitename ." notification: article " . $about->getText() . " has been reviewed by " . $reviewer->getName();
		
		$users = EWNotifier::prepareRecipients(EW_ACTION_MAKE,$about,array($reviewer));
		
		$mail_body=EWNotifier::getMailBody(EWNotifier::$PAGE_EMAIL_MAKE);
		
		if($mail_body==null) return false;
		
		$placehoders=array(
			"{{{REVIEWER_REAL_NAME}}}"=> $reviewer->getRealName(),
			"{{{REVIEWER_USER_NAME}}}"=> $reviewer->getName(),
			"{{{FULLPAGENAME}}}"=> str_replace(' ','_',$about->getFullText()),
			"{{{PAGENAME}}}"=>$about->getText(),
			"{{{REVIEW_FULLPAGENAME}}}"=> str_replace(' ','_',$review->getFullText()),
			"{{{REVIEW_PAGENAME}}}"=>$review->getText()		
		);
		
		$mail_body = EWNotifier::parseBody($placehoders,$mail_body);
		EWNotifier::sendMail( $users, $mail_title, $mail_body );
		return true;
	}
	/**
	 * Notification for action 'certify'
	 * Recipients of this notifications are:
	 * - users who contributed to the article (if flag $ewgNotifyAllReviewers=true)
	 * - users who made reviews of the article (if flag $ewgNotifyAllContributors=true)
	 * - users who made assignments about the article (if flag $ewgNotifyAllManagers=true)
 
{{{FULLPAGENAME}}} -- the name of the page certified (with namespace)
{{{PAGENAME}}} -- the name of the page certified (without namespace)

{{{CERTIFIED_FULLPAGENAME}}} -- the name of the page certified (with namespace)
{{{CERTIFIED_PAGENAME}}} -- the name of the page certified (without namespace)
	 */
	public static function notifyActionCertify(User $certifiedBy, $method,Title $new,Title $old){
		global $wgSitename;
		
		$title_text=$old->getFullText();
		$mail_title = $wgSitename ." notification: article " . $title_text . " has been certified.";
		
		$users = EWNotifier::prepareRecipients(EW_ACTION_CERTIFY,$old,array($certifiedBy));
		
		$mail_body=EWNotifier::getMailBody(EWNotifier::$PAGE_EMAIL_CERTIFY);
		
		if($mail_body==null) return false;
		
		$placehoders=array(
			"{{{FULLPAGENAME}}}"=> str_replace(' ','_',$old->getFullText()),
			"{{{PAGENAME}}}"=> $old->getText(),
			"{{{CERTIFIED_FULLPAGENAME}}}"=> str_replace(' ','_',$new->getFullText()),
			"{{{CERTIFIED_PAGENAME}}}"=>$new->getText()		
		);
		
		$mail_body = EWNotifier::parseBody($placehoders,$mail_body);
		
		EWNotifier::sendMail( $users, $mail_title, $mail_body );
		return true;
	}
	
	private static function sendMail($recipients, $msg_title,$msg_text ){
		//return UserMailer::send($addresses, $this->from, $this->subject, $body, $this->replyto);
		foreach ($recipients as $user){
			if( EWNotifier::mailUser($user,$msg_title,$msg_text) ){
				wfDebug(__METHOD__." SUCCESS: Mail notification to " . $user->getName() ." with title \"" . $msg_title ."\"");
			}else {
				wfDebug(__METHOD__." FAILED: Mail notification to " . $user->getName() ." with title \"" . $msg_title ."\"");
			}
		}
	}
	/**
	 * Mail user 
	 * @param User user 
	 * @param string title_msg_key the key message for the title of the email
	 * @param email_text the body of the email
	 * @return mixed true on success, WikiError on failure
	 */
	private static function mailUser($user,$msg_title,$msg_body){
		if( $user->canSendEmail() ){
			$res=$user->sendMail( $msg_title, $msg_body );
			if( $res instanceof WikiError){
				wfDebug(__METHOD__." ERROR: " . $res->toString());
				return false;
			}else return true;
		}else return false;
	}
	/*
	 * @return array of Users object
	 */
	private static function prepareRecipients($action, Title $title, $addusers=array() ){
		global $ewgNotifyAllContributors;
		global $ewgNotifyAllReviewers;
		global $ewgNotifyAllManagers;
		global $ewgNotifyGroups;
		
		$mail_recipients=array();
		
		$mail_recipients=array_merge($mail_recipients,$addusers);

		// If all article contributors have to be notified
		if( $ewgNotifyAllContributors!=null && $ewgNotifyAllContributors==true )
			$mail_recipients = array_merge( $mail_recipients, ewfGetContributors($title) );
			
		// If all reviewers have to be notified
		if( $ewgNotifyAllReviewers!=null && $ewgNotifyAllReviewers==true )
			$mail_recipients = array_merge( $mail_recipients, ewfGetReviewers($title) );
		
		// If all managers have to be notified
		if( $ewgNotifyAllManagers!=null && $ewgNotifyAllManagers==true )
			$mail_recipients = array_merge( $mail_recipients, ewfGetManagers($title) );
		
		if( $ewgNotifyGroups != null ){
			$notifs=$ewgNotifyGroups[$action];
			if( $notifs != null && count( $notifs ) != 0 ){
				$members=ewfGetMembers($notifs);
				$mail_recipients=array_merge($mail_recipients,$members);
			}
		}
		
		$users=ewfArrayUserUnique($mail_recipients);
		
		return $users;
	}
	private function getMailBody($pagename){
		$title=Title::newFromText($pagename,EWNotifier::$NAMESPACE);
		$bodya=new Article($title);
		$content=$bodya->fetchContent();
		return $content;
	}
	/**
	 * Replace placeholders occurrances with correspondent values
	 * @return string 
	 */
	private function parseBody($placeholders=array(),$body_text){
		foreach ($placeholders as $placeholder=>$value){
			$body_text = str_replace($placeholder,$value,$body_text);
		}
		//return ewfSandboxParse($body_text,Title::newFromText('MailBody',EWNotifier::$NAMESPACE));
		return $body_text;
	}
}
?>