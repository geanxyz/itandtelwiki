<?php
/**
 * Includs Shared functions/classes
 */
include_once ('EW_Shared.php');
include_once ('EW_Article.php');
include_once ('EW_Review.php');
include_once ('EW_ParserFunctions.php');

/**
 * Special pages
 */
global $wgAutoloadClasses;
global $wgSpecialPages;
global $ewgIP;

$ewgSpecialPages [] = 'AskForReview';
$wgAutoloadClasses ['EWAskForReview'] = $ewgIP . '/special/' . 'EW_AskForReview.php';
$wgSpecialPages ['AskForReview'] = 'EWAskForReview';

$ewgSpecialPages [] = 'AssignReview';
$wgAutoloadClasses ['EWAssignReview'] = $ewgIP . '/special/' . 'EW_AssignReview.php';
$wgSpecialPages ['AssignReview'] = 'EWAssignReview';

$ewgSpecialPages [] = 'MakeReview';
$wgAutoloadClasses ['EWMakeReview'] = $ewgIP . '/special/' . 'EW_MakeReview.php';
$wgSpecialPages ['MakeReview'] = 'EWMakeReview';

$ewgSpecialPages [] = 'AssignedReviews';
$wgAutoloadClasses ['EWAssignedReviews'] = $ewgIP . '/special/' . 'EW_AssignedReviews.php';
$wgSpecialPages ['AssignedReviews'] = 'EWAssignedReviews';

$ewgSpecialPages [] = 'WaitingForReview';
$wgAutoloadClasses ['EWWaitingForReview'] = $ewgIP . '/special/' . 'EW_WaitingForReview.php';
$wgSpecialPages ['WaitingForReview'] = 'EWWaitingForReview';

$ewgSpecialPages [] = 'Certify';
$wgAutoloadClasses ['EWCertify'] = $ewgIP . '/special/' . 'EW_Certify.php';
$wgSpecialPages ['Certify'] = 'EWCertify';

$wgAutoloadClasses ['EWEvaluation'] = $ewgIP . '/special/' . 'EW_Evaluation.php';
$wgSpecialPages ['Evaluation'] = 'EWEvaluation';

foreach($ewgSpecialPages as $specialpage)
	$wgSpecialPageGroups[$specialpage]='evaluationwikiflow';


/**
 * Message files
 */
global $wgExtensionMessagesFiles;
$wgExtensionMessagesFiles [EW_MESSAGES] = $ewgIP . '/includes/' . 'EW_Messages.php';

// Don't know why must be here and not in the setup extension function
$wgHooks ['LanguageGetMagic'] [] = 'ewfParserFunctionsLanguageGetMagic';

function ewSetupExtension() {
	
	global $wgOut;
	global $wgExtensionCredits;
	global $ewgScriptPath;
	global $wgHooks;
	global $wgExtraNamespaces, $smwgNamespacesWithSemanticLinks, $wgContentNamespaces;
	
	$wgExtraNamespaces [EW_NS_REVIEWS] = 'Reviews';
	$wgExtraNamespaces [EW_NS_REVIEWS_TALK] = 'Reviews_talk';
	$smwgNamespacesWithSemanticLinks [EW_NS_REVIEWS] = true;
	$wgContentNamespaces [] = EW_NS_REVIEWS;
	
	require_once ('EW_Hooks.php');
	$wgHooks ['ArticleSave'] [] = 'ewfOnHookArticleSaveCheckWaiting';
	$wgHooks ['ArticleSave'] [] = 'ewfOnHookArticleSaveCheckAssigned';
	$wgHooks ['ArticleSaveComplete'] [] = 'ewfOnHookArticleSaveCompleteReview';
	$wgHooks ['ArticleViewHeader'] [] = 'ewfOnHookArticleViewHeader';
	
	$wgHooks ['SkinTemplateContentActions'] [] = 'ewfAddactionContentHook';
	$wgHooks ['UnknownAction'] [] = 'ewfAddactActionHook';
	$wgHooks ['SpecialPageExecuteBeforeHeader'] [] = 'ewfSpecialBeforeHeaderHook';
	$wgHooks ['smwInitProperties'] [] = 'ewfInitProperties';
	
	global $wgParser;
	global $ewgParserFunctions;
	$ewgParserFunctions = new EWParserFunctions ( );
	$wgParser->setFunctionHook ( 'revisionid', array (&$ewgParserFunctions, 'run_revisionid' ) );
	$wgParser->setFunctionHook ( 'iscurrent', array (&$ewgParserFunctions, 'run_iscurrent' ) );
	$wgParser->setFunctionHook ( 'iswaiting', array (&$ewgParserFunctions, 'run_iswaiting' ) );
	$wgParser->setFunctionHook ( 'isassigned', array (&$ewgParserFunctions, 'run_isassigned' ) );
	$wgParser->setFunctionHook ( 'iscertified', array (&$ewgParserFunctions, 'run_iscertified' ) );
	$wgParser->setFunctionHook ( 'needsedit', array (&$ewgParserFunctions, 'run_needsedit' ) );
	$wgParser->setFunctionHook ( 'reviewabout', array (&$ewgParserFunctions, 'run_reviewabout' ) );
	
	/**
	 * CSS
	 */
	$wgOut->addLink ( array ('rel' => 'stylesheet', 'type' => 'text/css', 'media' => "screen, projection", 'href' => $ewgScriptPath . '/skins/main.css' ) );
	
	/**
	 * Credits
	 */
	$wgExtensionCredits ['specialpage'] [] = array ('name' => 'Evaluation WikiFlow', 'version' => EW_VERSION, 'author' => 'Enrico Daga, STLab [CNR]', 'url' => 'http://www.mediawiki.org/wiki/Extension:Evaluation_WikiFlow', 'description' => 'Evaluation WikiFlow adds support for evaluation of wiki articles in a semantic mediawiki/semantic forms environment.' );

}

/**
 * Hook functions
 */
include_once ('EW_Hooks.php');
/**
 * Notifier class
 */
include_once ('EW_Notifier.php');

/**
 * Append init function
 */
$wgExtensionFunctions [] = "ewSetupExtension";


?>