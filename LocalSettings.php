<?php

//error_reporting(E_ALL);
//ini_set('display_errors', true);


# This file was automatically generated by the MediaWiki installer.
# If you make manual changes, please keep track in case you need to
# recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# http://www.mediawiki.org/wiki/Manual:Configuration_settings

# If you customize your file layout, set $IP to the directory that contains
# the other MediaWiki files. It will be used as a base to locate files.
if( defined( 'MW_INSTALL_PATH' ) ) {
	$IP = MW_INSTALL_PATH;
} else {
	$IP = dirname( __FILE__ );
}

$path = array( $IP, "$IP/includes", "$IP/languages" );
set_include_path( implode( PATH_SEPARATOR, $path ) . PATH_SEPARATOR . get_include_path() );

require_once( "$IP/includes/DefaultSettings.php" );

# If PHP's memory limit is very low, some operations may fail.
ini_set( 'memory_limit', '64M' );

if ( $wgCommandLineMode ) {
	if ( isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
		die( "This script must be run from the command line\n" );
	}
}
## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename         = "eww ITandTEL - Wissensdatenbank";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath       = "";
$wgScriptExtension  = ".php";

## UPO means: this is also a user preference option

$wgEnableEmail      = true;
$wgEnableUserEmail  = true; # UPO

$wgEmergencyContact = "nagios_ei@itandtel.at";
$wgPasswordSender = "nagios_ei@itandtel.at";

$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = false;

## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = getenv("MARIADB_SERVICE_HOST");
$wgDBname           = "wikidb";
$wgDBuser           = getenv("MARIADB_USER");
$wgDBpassword       = getenv("MARIADB_PASSWORD");

# MySQL specific settings
$wgDBprefix         = "mw_";

# MySQL table options to use during installation or update
$wgDBTableOptions   = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Experimental charset support for MySQL 4.1/5.0.
$wgDBmysql5 = true;

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads       = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
# $wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX           = false;

$wgLocalInterwiki   = strtolower( $wgSitename );

$wgLanguageCode = "de";

$wgSecretKey = "7c2fce70ed6bee8dae9052b2b96abc59869c27332a54efaa41e0b254f983679e";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook':
$wgDefaultSkin = 'monobook';

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
# $wgEnableCreativeCommonsRdf = true;
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";
# $wgRightsCode = ""; # Not yet used

$wgDiff3 = "/usr/bin/diff3";

# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
$wgCacheEpoch = max( $wgCacheEpoch, gmdate( 'YmdHis', @filemtime( __FILE__ ) ) );

$wgLogo	= "../images/logo-eww-itandtel.png";

$wgGroupPermissions = array();

// Implicit group for all visitors
$wgGroupPermissions['*'    ]['createaccount']   = true;
$wgGroupPermissions['*'    ]['read']            = true;
$wgGroupPermissions['*'    ]['edit']            = false;
$wgGroupPermissions['*'    ]['createpage']      = false;
$wgGroupPermissions['*'    ]['createtalk']      = false;

$wgGroupPermissions['*'    ]['viewedittab']    = true;

// Implicit group for all logged-in accounts
$wgGroupPermissions['user' ]['move']            = true;
$wgGroupPermissions['user' ]['read']            = true;
$wgGroupPermissions['user' ]['edit']            = true;
$wgGroupPermissions['user' ]['createpage']      = true;
$wgGroupPermissions['user' ]['createtalk']      = true;
$wgGroupPermissions['user' ]['upload']          = true;
$wgGroupPermissions['user' ]['reupload']        = true;
$wgGroupPermissions['user' ]['reupload-shared'] = true;
$wgGroupPermissions['user' ]['minoredit']       = true;

// Implicit group for all logged-in accounts
$wgGroupPermissions['editor' ]['move']            = true;
$wgGroupPermissions['editor' ]['read']            = true;
$wgGroupPermissions['editor' ]['edit']            = true;
$wgGroupPermissions['editor' ]['createpage']      = true;
$wgGroupPermissions['editor' ]['createtalk']      = true;
$wgGroupPermissions['editor' ]['upload']          = true;
$wgGroupPermissions['editor' ]['reupload']        = true;
$wgGroupPermissions['editor' ]['reupload-shared'] = true;
$wgGroupPermissions['editor' ]['minoredit']       = true;

// Implicit group for accounts that pass $wgAutoConfirmAge
$wgGroupPermissions['autoconfirmed']['autoconfirmed'] = true;

// Implicit group for accounts with confirmed email addresses
// This has little use when email address confirmation is off
$wgGroupPermissions['emailconfirmed']['emailconfirmed'] = true;

// Users with bot privilege can have their edits hidden
// from various log pages by default
$wgGroupPermissions['bot'  ]['bot']             = true;
$wgGroupPermissions['bot'  ]['autoconfirmed']   = true;
$wgGroupPermissions['bot'  ]['nominornewtalk']  = true;

// Most extra permission abilities go to this group
$wgGroupPermissions['sysop']['edit']            = true;
$wgGroupPermissions['sysop']['createpage']      = true;
$wgGroupPermissions['sysop']['createtalk']      = true;
$wgGroupPermissions['sysop']['block']           = true;
$wgGroupPermissions['sysop']['createaccount']   = true;
$wgGroupPermissions['sysop']['delete']          = true;
$wgGroupPermissions['sysop']['deletedhistory'] 	= true; // can view deleted history entries, but not see or restore the text
$wgGroupPermissions['sysop']['editinterface']   = true;
$wgGroupPermissions['sysop']['import']          = false;
$wgGroupPermissions['sysop']['importupload']    = false;
$wgGroupPermissions['sysop']['move']            = true;
$wgGroupPermissions['sysop']['patrol']          = true;
$wgGroupPermissions['sysop']['autopatrol']		= true;
$wgGroupPermissions['sysop']['protect']         = true;
$wgGroupPermissions['sysop']['proxyunbannable'] = true;
$wgGroupPermissions['sysop']['rollback']        = true;
$wgGroupPermissions['sysop']['trackback']       = true;
$wgGroupPermissions['sysop']['upload']          = true;
$wgGroupPermissions['sysop']['reupload']        = true;
$wgGroupPermissions['sysop']['reupload-shared'] = true;
$wgGroupPermissions['sysop']['unwatchedpages']  = true;
$wgGroupPermissions['sysop']['autoconfirmed']   = true;
$wgGroupPermissions['sysop']['upload_by_url']   = true;
$wgGroupPermissions['sysop']['ipblock-exempt']	= true;
$wgGroupPermissions['sysop']['userrights']      = true; // Permission to change users' group assignments 

$wgUseAjax = true;

require_once "$IP/extensions/WYSIWYG/WYSIWYG.php";
$wgGroupPermissions['*']['wysiwyg']=true; // for all users

//include_once("$IP/extensions/SemanticMediaWiki/includes/SMW_Settings.php");
//enableSemantics('172.31.31.98/wiki');
//include_once("$IP/extensions/SemanticForms/includes/SF_Settings.php");
//require_once("$IP/extensions/EvaluationWikiflow/includes/EW_Settings.php");
//require_once("$IP/extensions/ParserFunctions/StringFunction.php");
array_push($wgUrlProtocols, "file://");
//include_once('extensions/FlaggedRevs/FlaggedRevs.php');
require_once("extensions/NiceCategoryList.php");
// 30.1.  require_once("extensions/EditPageMultipleInputTextAreas.php");
//$ewgReviewsNS=300;
//$ewgPropertyReviewAbout  = 'Review about';

//$smwgQSubcategoryDepth=0;
//$smwgQPropertyDepth=0;
//$smwgQFeatures        = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY;
//$smwgQConceptFeatures = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY & ~SMW_CONCEPT_QUERY;
//$wgShowExceptionDetails = true;

$wgFileExtensions = array('png', 'jpg', 'jpeg', 'ppt', 'ogg', 'odt', 'pdf', 'doc', 'xls', 'docx', 'xlsx', 'dot', 'dotx', 'bmp', 'tiff', 't3x', 'zip', 'rar');
$wgMaxUploadSize = '100M';
$wgUploadSizeWarning = false;

$wgEnableMWSuggest = true;

// 30.1. require_once("{$IP}/extensions/CategoryTree/CategoryTree.php");
wfLoadExtension( 'CategoryTree' );

//$wgUseCategoryBrowser = true;

$wgCategoryTreeForceHeaders = true;
//$wgCategoryTreeMaxDepth = array(CT_MODE_PAGES => 3, CT_MODE_ALL => 2, CT_MODE_CATEGORIES => 3);
//$wgCategoryTreeSidebarRoot = "Hauptkategorie";
$wgShowExceptionDetails = true;

$wgMainCacheType = CACHE_NONE;
$wgMessageCacheType = CACHE_NONE;
$wgParserCacheType = CACHE_NONE;
$wgCachePages = false;

wfLoadExtension( 'Renameuser' );
$wgGroupPermissions['sysop']['renameuser'] = true;


$wgUpgradeKey = '0eea07f35ff88a4c';

wfLoadSkin( 'CologneBlue' );
wfLoadSkin( 'Modern' );
wfLoadSkin( 'MonoBook' );
wfLoadSkin( 'Vector' );
wfLoadExtension( 'WYSIWYG' );
wfLoadExtension( 'WikiEditor' );
wfLoadExtension( 'ParserFunctions' );
