<?php

/**
 * Categories to evaluate
 */
function ewfGetCategories(){
	global $ewgCategories;
	if(isset($ewgCategories)) return $ewgCategories;
	
	$categories = array();
	
	$store = smwfGetStore();
	$form_page = null;
	
	// get all categories
	$db=wfGetDB(DB_SLAVE);
	
	$results = $db->select (
			'page',
			array(
				'page_id',
				'page_title',
				'page_namespace'
			),
			array('page_namespace'=>NS_CATEGORY),
			'ewfGetCategories'
		);
	
	while( ($row = $db->fetchRow($results) )  != null ){
		$category=$row[1];
		$category_title = Title::newFromText($row[1],$row[2]);
		$category_title->mArticleID=$row[0];
		
		if (class_exists('SMWPropertyValue')) {
			$ffr_property = SMWPropertyValue::makeProperty(EW_PROPERTY_FORM_FOR_REVIEW_ID);
			$res = $store->getPropertyValues($category_title, $ffr_property);
			
			if (isset($res[0])){
				$form_page[] = $res[0]->getTitle();
			}
		}else{
			// otherwise
			$category_semdata = $store->getSemanticData( $category_title );
			$propertyformvalues = $category_semdata->getPropertyValues( EW_PROPERTY_FORM_FOR_REVIEW );
			foreach($propertyformvalues as $v) $form_page[]= $v->getTitle();
		}
		
		if($form_page!=null && count($form_page)!=0) {
			$categories[]=$category;
		}
		$form_page=null;
	}
	$ewgCategories=$categories;
	return $categories;
}
/**
 * Parser for 'private' use...
 *
 * @param String $wikiText
 * @return html
 */
function ewfSandboxParse($wikiText, $title=null ) {
        global $wgTitle, $wgUser;
        if($title==null) $title=$wgTitle;
        $myParser = new Parser();
        $myParserOptions = new ParserOptions();
        $myParserOptions->initialiseFromUser($wgUser);
        $result = $myParser->parse($wikiText, $title, $myParserOptions);
        return $result->getText();
}

/**
 * 
 * Contributors
 * @return array of User
 */
function ewfGetContributors(Title $title){
	if($title->getArticleId()==null) return array();
	$query = "SELECT DISTINCT `rev_user` FROM `mw_revision` WHERE `rev_page` = ". $title->getArticleId();
	$db=wfGetDB(DB_SLAVE);
	$res=$db->doQuery($query);
	$userids=array();
	while( ( $row=$db->fetchRow($res) )!=null){
		$userids[]=User::newFromId($row[0]);
	}
	return $userids;
}
/**
 * Reviewers
 *
 * @param Title $title
 * @return Array<User>
 */
function ewfGetReviewers(Title $title){
	$ewa = new EWArticle(new Article($title));
	$reviewers=array();
	foreach($ewa->getAllReviewersID() as $rid){
		$reviewers[]=User::newFromId($rid);
	}
	return $reviewers;
}
/**
 * Managers
 *
 * @param Title $title
 * @return Array<User>
 */
function ewfGetManagers(Title $title){
	if($title->getArticleId()==null) return array();
	$query = "SELECT DISTINCT `ewa_manager_id` FROM `mw_ew_assigned` WHERE `ewa_page_id` = ". $title->getArticleId();
	$db=wfGetDB(DB_SLAVE);
	$res=$db->doQuery($query);
	$userids=array();
	while( ( $row=$db->fetchRow($res) )!=null){
		$userids[]=User::newFromId($row[0]);
	}
	return $userids;
}
/**
 * Get group members
 *
 * @param array $groups
 * @return array
 */
function ewfGetMembers($groups=array()){
	if(count($groups)==0) return array();
	
	$query="";
	
	// if group 'user' is in the array, return all users
	// if group '*' is in the array, return all (regustered) users. Assignments for non-registered users does not make any sense...
	if(in_array('user',$groups) || in_array('*',$groups)){
		$query = "SELECT `user_id` FROM `mw_user`";
	}else{
		$grouplist='';
		$first=true;
		foreach($groups as $g){
			if(!$first){
				$grouplist.=',';
			}
			$grouplist .= "'$g'";
			$first=false;
		}
		$query = "SELECT DISTINCT `ug_user` FROM `mw_user_groups` where `ug_group` IN ($grouplist)";
	}
	
	$db=wfGetDB(DB_SLAVE);
	$res=$db->doQuery($query);
	$users=array();
	while( ( $row=$db->fetchRow($res) )!=null){
		$users[]=User::newFromId($row[0]);
	}
	return $users;
}
/**
 * Given an array of User objects, returns one with unique elements
 *
 * @param array $users
 * @return array
 */
function ewfArrayUserUnique($users=array()){
	$added=array();
	$users_unique=array();
	foreach( $users as $u ){
		if( ( in_array( $u->getName(), $added ) ) ) continue;
		$users_unique[]=$u;
		$added[]=$u->getName();
	}
	return $users_unique;
}
/**
 * Get all users with the EW_ACTION_MAKE rights
 * @return array
 */
function ewfGetAllReviewers(){
	global $wgGroupPermissions;
	$candidates=array();
	$groups=array();
	$allgroups=array_unique(array_keys($wgGroupPermissions));
	foreach($allgroups as $group){
		if(isset($wgGroupPermissions[$group][EW_ACTION_MAKE]) && $wgGroupPermissions[$group][EW_ACTION_MAKE]==true)
			$groups[]=$group;
	}
	$groups=array_unique($groups);
	$reviewers=array();
	$candidates=array_merge($candidates,ewfGetMembers($groups));
	foreach($candidates as $reviewer){
		if($reviewer->isAllowed(EW_ACTION_MAKE)) $reviewers[]=$reviewer;
	}
	return ewfArrayUserUnique($reviewers);
}
?>
