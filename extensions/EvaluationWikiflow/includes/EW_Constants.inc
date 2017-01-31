<?php
/**
 * Evaluation WikiFlow
 * @author Enrico Daga
 */

define ( 'EW_VERSION', '0.3' );

/**
 * Database
 */
define ( 'EW_TABLE_WAITING', 'ew_waiting' );
define ( 'EW_TABLE_ASSIGNED', 'ew_assigned' );
define ( 'EW_TABLE_REVIEW', 'ew_review' );
define ( 'EW_TABLE_CERTIFIED', 'ew_certified' );

/**
 * Permissions for Evaluation
 */
define ( 'EW_ACTION_VIEW', 'viewevaluation' );
define ( 'EW_ACTION_ASK', 'askforreview' );
define ( 'EW_ACTION_ASSIGN', 'assignreview' );
define ( 'EW_ACTION_MAKE', 'makereview' );
define ( 'EW_ACTION_CERTIFY', 'certify' );

define ( 'EW_MESSAGES', 'EvaluationWorkflow' );

$wgAvailableRights [] = EW_ACTION_VIEW;
$wgAvailableRights [] = EW_ACTION_ASK;
$wgAvailableRights [] = EW_ACTION_ASSIGN;
$wgAvailableRights [] = EW_ACTION_MAKE;
$wgAvailableRights [] = EW_ACTION_CERTIFY;

/**
 * Form
 */
define ( 'EW_INPUT_REVIEWER', 'reviewer' );
define ( 'EW_INPUT_ABOUTPAGE', 'reviewaboutpage' );
define ( 'EW_INPUT_ABOUTREVISION', 'reviewaboutrevision' );

// Local paths
global $wgScriptPath, $IP;
$ewgScriptPath = $wgScriptPath . '/extensions/EvaluationWikiflow';
$ewgIP = $IP . '/extensions/EvaluationWikiflow';

/**
 * Default permissions
 */
$wgGroupPermissions ['*'] [EW_ACTION_VIEW] = false;
$wgGroupPermissions ['user'] [EW_ACTION_VIEW] = true;
$wgGroupPermissions ['user'] [EW_ACTION_ASK] = true;
$wgGroupPermissions ['user'] [EW_ACTION_MAKE] = true;
$wgGroupPermissions ['bureaucrat'] [EW_ACTION_ASSIGN] = true;
$wgGroupPermissions ['sysop'] [EW_ACTION_CERTIFY] = true;

/**
 * Define categories and properties, if not defined in Settings puts default values
 */
global $ewgCategoryWaiting; // Category applied when an 'Ask for review' action is performed
global $ewgCategoryAssigned; // Category applied when an 'Assign review' action is performed
global $ewgCategoryReview; // All reviews belongs to this category
global $ewgCategoryCertified; // Category applied when a 'Certify' action is performed
global $ewgPropertyReviewAbout; // Property wich links a Review to the page object of the review
global $ewgReviewsNS; // Index of the review NS
global $ewgNotifyAllContributors; // If mail notification should include all contributors of an article
global $ewgNotifyAllReviewers; // If mail notification should include all reviewers of an article
global $ewgNotifyAllManagers; // If mail notification should include all managers

global $ewgNotifyGroups; // This must be an array of key(ACTION)=>value(GROUP)

global $wgIP;

if (! isset ( $ewgCategoryWaiting ))
	$ewgCategoryWaiting = 'Waiting for review';
if (! isset ( $ewgCategoryAssigned ))
	$ewgCategoryAssigned = 'Review assigned';
if (! isset ( $ewgCategoryReview ))
	$ewgCategoryReview = 'Review'; // TODO Not used yet
if (! isset ( $ewgCategoryCertified ))
	$ewgCategoryCertified = 'Certified';
if (! isset ( $ewgReviewsNS ))
	$ewgReviewsNS = 300;
if (! isset ( $ewgPropertyReviewAbout ))
	$ewgPropertyReviewAbout  = 'Review about';


// Settings for the notifications
if (! isset ( $ewgNotifyAllContributors ))
	$ewgNotifyAllContributors = false;
if (! isset ( $ewgNotifyAllReviewers ))
	$ewgNotifyAllReviewers = false;
if (! isset ( $ewgNotifyAllManagers ))
	$ewgNotifyAllManagers = false;
if (! isset ( $ewgNotifyGroups ) ){
	$ewgNotifyGroups=array();
}

define ( 'EW_CATEGORY_WAITING', $ewgCategoryWaiting );
define ( 'EW_CATEGORY_ASSIGNED', $ewgCategoryAssigned );
define ( 'EW_CATEGORY_REVIEW', $ewgCategoryReview );
define ( 'EW_CATEGORY_CERTIFIED', $ewgCategoryCertified );
define ( 'EW_PROPERTY_FORM_FOR_REVIEW_ID', '___EW_FFR' );
define ( 'EW_PROPERTY_FORM_FOR_REVIEW', 'Form for reviews' );
define ( 'EW_PROPERTY_REVIEW_ABOUT_ID', '___EW_RA' );
define ( 'EW_PROPERTY_REVIEW_ABOUT', $ewgPropertyReviewAbout );
define ( 'EW_NS_REVIEWS', $ewgReviewsNS );
define ( 'EW_NS_REVIEWS_TALK', $ewgReviewsNS + 1 );

/**
 * Some constants
 *
 */
define ( 'EW_MODE_TIMESTAMP', 1 );
define ( 'EW_MODE_INCREMENTAL', 2 );
define ( 'EW_MODE_MOVE_PAGE', 3 );
define ( 'EW_MODE_LOCK_AND_COPY', 4 );

?>
