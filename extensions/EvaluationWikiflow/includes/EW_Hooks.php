<?php
/**
 * Functions on MW's hooks
 */

/**
 * To prevent manual deleting of 
 * Evaluation Workflow's annotations
 * If article has a pending assignement, check if the correct category is not deleted.
 * I don'tknow if it is the right way, but probably is better then check it after article is saved and do another edit to correct the problem
 */
function ewfOnHookArticleSaveCheckAssigned( &$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags ){
	$earticle = new EWArticle($article);
	//If it is in the category submitted to evaluation workflow and action is not evaluation
	if($earticle->toEvaluate()){
		// If has an assigned review, append annotation
		if($earticle->assigned()){
			$text = EWArticle::appendAnnotationAssigned($text);
		}
	}
	// Must return true
	return true;
}
/**
 * If article has a pending request for a review, check if the correct category is not deleted.
 * the comment is the same as the one above...
 */
function ewfOnHookArticleSaveCheckWaiting( &$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags ){
	// If article is waiting, do not delete related category
	$earticle = new EWArticle($article);
	// If it is in the category to evaluate
	if($earticle->toEvaluate()){
		// If is waiting
		if($earticle->waiting()){
			$text = EWArticle::appendAnnotationWaiting($text);
		}
	}
	// Must return true
	return true;
}
/**
 * If article has certified content, 
 * check if the correct category is not deleted.
 * The comment is the same as the one above...
 */
function ewfOnHookArticleSaveCheckCertified(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags){
	/**
	 * If article is certified, do not delete/add related category
	 */
	$earticle = new EWArticle( $article );
	if( $earticle->certified() ){
		$text = EWArticle::appendAnnotationCertified( $text );
	}
	// Must return true
	return true;
}
/**
 * Save Review in the ewf database
 * - check if some of its properties is in the $ewgPropertyReviewAbout array and its object is in the category to evaluate. 
 *   If yes it is a review!
 * - save new review
 *
 */
function ewfOnHookArticleSaveCompleteReview(&$article, &$user){

	// If article is a new review 
	if( EW_NS_REVIEWS == $article->getTitle()->getNamespace() ){

		// If it is already saved...
		if( EWReview::isReview( $article ) ) return true;
	
		// If user has rights
		if(! $user->isAllowed(EW_ACTION_MAKE)){
			return true;
		}
		// if smw 1.4 or higher
		if ( class_exists('SMWPropertyValue') ) {
			$reviewaboutproperty = SMWPropertyValue::makeUserProperty(EW_PROPERTY_REVIEW_ABOUT);
			$res = smwfGetStore()->getPropertyValues( $article->getTitle(), $reviewaboutproperty );
			
			if ( is_array($res) ){
				$value = array_shift($res);
				EWReview::saveNewReview( $article, new EWArticle( new Article($value->getTitle()) ), $user );
			}
				
		}else{
			$semdata = smwfGetStore()->getSemanticData( $article->getTitle() );
			$properties = $semdata->getProperties();		
			global $ewgPropertyReviewAbout;
			foreach( $properties as $property ){
				if( ! $property instanceof Title ) continue;
				
				// If the property is in the $ewgPropertyReviewAbout array then it is a Review
				if( in_array($property->getText(), $ewgPropertyReviewAbout ) ){
					$propertyvalues = $semdata->getPropertyValues($property);
					
				}
				// Object property must have a single value!
				foreach($propertyvalues as $value){
					$objectvalue = $value;
				}
				$objectvaluetitle = $objectvalue->getTitle();
				EWReview::saveNewReview( $article, new EWArticle( new Article($objectvaluetitle) ), $user );
				return true;
			}
		}
	}
	// Must return true
	return true;
}
function ewfOnHookArticleViewHeader( &$article ){
	wfLoadExtensionMessages(EW_MESSAGES);
	if( EWReview::isReview($article) ){
		global $wgOut;
		
		$subtitle_message=wfMsg('reviewabout');
		
		$review = EWReview::createFromArticle($article);

		$aboutid=$review->getAboutID();
		$about_title = Title::newFromID( $aboutid );
		
		$subtitle_message = ewfSandboxParse( $subtitle_message . ' [[' . $about_title->getFullText() . ']]');
		$wgOut->setSubtitle($subtitle_message );
		
	}else {
		global $wgOut;
		
		$ewarticle = new EWArticle($article);
		$subtitlehtml='';
		if($ewarticle->waiting()){
			$subtitlehtml .= wfMsg('articlewaitingforreview');	
		}
		if($ewarticle->assigned()){
			if($ewarticle->waiting()){$subtitlehtml .= '<br>';}
			$subtitlehtml .=  wfMsg('articlereviewassigned');
		}
		if($ewarticle->needsEdit()){
			if($ewarticle->waiting() || $ewarticle->assigned()){$subtitlehtml .= '<br>';}
			$subtitlehtml .=  wfMsg('needsedit');
		}
		if( $ewarticle->certified() ){
			$subtitlehtml =  wfMsg('contentcertified');
		}
		$wgOut->setSubtitle($subtitlehtml);
	}
	// must return true
	return true;
}

function ewfSpecialBeforeHeaderHook($specialPage, $par, $funct){
	global $ewgSpecialPages;
	
	if(! in_array ($specialPage->getName() , $ewgSpecialPages ) ){
		return true;
	}
	global $wgOut;
	$evaluationlabel = wfMsg('evaluationworkflow');
	$askforreview_label=wfMsg('askforreview');
	$assignreview_label=wfMsg('assignreview');
	$makereview_label=wfMsg('makereview');
	$certify_label=wfMsg('certify');
	$waitingforreview_label=wfMsg('waitingforreview');
	$assignedreviews_label=wfMsg('assignedreviews');
	
	
	$pagelinks =<<<END
	$evaluationlabel<br/>
	[[Special:AskForReview|$askforreview_label]] |
	[[Special:AssignReview|$assignreview_label]] |
	[[Special:MakeReview|$makereview_label]] | 
	[[Special:Certify|$certify_label]] 
	&nbsp; &nbsp;	&nbsp; &nbsp;	&nbsp; &nbsp;
	[[Special:WaitingForReview|$waitingforreview_label]] |
	[[Special:AssignedReviews|$assignedreviews_label]]
END;

	$wgOut->setSubtitle(ewfSandboxParse($pagelinks) );
	return true;
}
function ewfAddActionContentHook( &$content_actions ) {
    global $wgTitle;
    
    if ( $wgTitle->getNamespace() != NS_SPECIAL ) {
    	
    	global $wgRequest, $wgRequest, $wgUser;
	    $article = new Article( $wgTitle );
    	
    	/**
	     * Show bar only if user can view and article is in the right category
	     * or is a certified article
	     */
    	$ewa = new EWArticle($article);
		
		if( ! ( $ewa->toEvaluate() || $ewa->certified() ) ) return true;
		if( ! $wgUser->isAllowed(EW_ACTION_VIEW) ) return true;
    	
		$label_msg_key = $ewa->certified() ? 'evalhistory' : 'evaluation';
    	
		$action = $wgRequest->getText( 'action' );    	
    	wfLoadExtensionMessages(EW_MESSAGES);
        $content_actions['evaluation'] = array(
            'class' => ($action == 'evaluation' || $action == 'askforreview' ) ? 'selected' : false,
            'text' => wfMsg( $label_msg_key ),
            'href' => $wgTitle->getLocalUrl( 'action=evaluation' )
        );
    }

    return true;
}
function ewfAddactActionHook( $action, &$wgArticle ) {
    global $wgOut;
    
    $title = $wgArticle->getTitle(); 

    if( $action == 'evaluation' || $action == 'askforreview' || $action == 'assignreview' || $action == 'makereview'){
    	wfLoadExtensionMessages(EW_MESSAGES);
    }
    
    if ( $action == 'askforreview' ){
		EWAskForReview::askForReview($title);
    }
   
    if ( $action == 'evaluation' || $action == 'askforreview'){
		$wgOut->setPageTitle( wfMsg('evaluation') . ': ' . $title->getFullText() );
		EWEvaluation::run();
		return false;
    }
    return false;
}
function ewfParserFunctionsLanguageGetMagic( &$magicWords, $langCode ){
	switch ( $langCode ) {
		default:
			$magicWords['revisionid']           = array( 0, 'revisionid' );
			$magicWords['iscurrent']            = array( 0, 'iscurrent' );			
			$magicWords['iswaiting']            = array( 0, 'iswaiting' );
			$magicWords['isassigned']           = array( 0, 'isassigned' );
			$magicWords['iscertified']          = array( 0, 'iscertified' );
			$magicWords['needsedit']            = array( 0, 'needsedit' );
			$magicWords['reviewabout']          = array( 0, 'reviewabout' );
	}
	return true;	
}

function ewfInitProperties(){
	
	SMWPropertyValue::registerProperty(EW_PROPERTY_FORM_FOR_REVIEW_ID, '__spf', EW_PROPERTY_FORM_FOR_REVIEW, true);
	SMWPropertyValue::registerProperty(EW_PROPERTY_REVIEW_ABOUT_ID, '_wpg', EW_PROPERTY_REVIEW_ABOUT, true);
	return true;
}
?>