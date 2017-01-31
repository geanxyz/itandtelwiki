<?php
// Constants
include_once 'EW_Constants.inc';

/*
 * Group permissions.
 * Defaults are:
 * $wgGroupPermissions ['*'] [EW_ACTION_VIEW] = false;
 * $wgGroupPermissions ['user'] [EW_ACTION_VIEW] = true;
 * $wgGroupPermissions ['user'] [EW_ACTION_ASK] = true;
 * $wgGroupPermissions ['user'] [EW_ACTION_MAKE] = true;
 * $wgGroupPermissions ['bureaucrat'] [EW_ACTION_ASSIGN] = true;
 * $wgGroupPermissions ['sysop'] [EW_ACTION_CERTIFY] = true;
 */
$wgGroupPermissions ['*'] [EW_ACTION_VIEW] = true;
$wgGroupPermissions ['user'] [EW_ACTION_VIEW] = true;
$wgGroupPermissions ['user'] [EW_ACTION_ASK] = true;
$wgGroupPermissions ['user'] [EW_ACTION_MAKE] = true;
$wgGroupPermissions ['bureaucrat'] [EW_ACTION_ASSIGN] = true;
$wgGroupPermissions ['sysop'] [EW_ACTION_CERTIFY] = true;

/*
 * E-mail notifications
 */
$ewgNotifyAllContributors = false;
$ewgNotifyAllReviewers = false;
$ewgNotifyAllManagers = false;
//$ewgNotifyGroups[EW_ACTION_ASK] = 'bureaucrat';
//$ewgNotifyGroups[EW_ACTION_ASSIGN] = 'bureaucrat';
//$ewgNotifyGroups[EW_ACTION_MAKE] = 'bureaucrat';
//$ewgNotifyGroups[EW_ACTION_CERTIFY] = 'bureaucrat';

// Initialization
include_once 'EW_Init.inc';
?>
