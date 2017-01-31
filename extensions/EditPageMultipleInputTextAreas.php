<?php
 
if ( ! defined( 'MEDIAWIKI' ) )
        die();
 
//---------- Note Author--- 11-2007-------------------
// The Editforms extension is developed to enable 
// different types of input for a Wiki. In this way
// a Wiki could be build up much faster, but also 
// with more quality and clearer structure.
//
// See http://www.leerwiki.nl for either updates
// or other extensions such as the Ajax Rating Script-,
// Image shadow- or EditpageMultipleInputboxes extension. 
// good luck with your Wiki! 
// B.Vahrmeijer
//----------------------------------------------------
 
$wgExtensionCredits['other'][] = array(
'name' => 'AddInputField',
'author' => 'Michael Raberger',
'url' => 'http://www.itandtel.at',
'version' => '0.1',
'description' => 'some description',
);
 
global $wgHooks;

//####### Used Hooks ######################
$wgHooks['EditPage::showEditForm:fields'][] = 'fnMyCustomEdit';
$wgHooks['ArticleSaveComplete'][] = 'fnMyHook';
 
########## Hook 1 #################
function fnMyCustomEdit( $editpage, $output )
{
	global $wgOut;
	
	$res = "<div style='background-color: #fbe3e4; padding: 7px; margin-bottom: 7px; border-color: #666; border-style: dashed; border-width: 1px'>Datum letzte &Uuml;berpr&uuml;fung: <input type='text' name='mr_check' value='' /> <span style='color: #666; font-size: 10px; font-style: italic'>(tt.mm.jjjj)</span></div>";
	
	$output->addHTML($res);
	
	return true;
}

########## Hook 2 #################
function fnMyHook( $article, $user, $text, $summary, $minor, $watchthis, $sectionanchor, $flags, $status )
{
	
	if($_POST['mr_check'] != "")
	{
		$article_id = $article->getID();
		
		$dbw = wfGetDB( DB_MASTER );
		
		$table = "mr_check";
		$a     = array();
	
		$crdate = explode(".", $_POST['mr_check']);
		$crdate = mktime(0, 0, 0, $crdate[1], $crdate[0], $crdate[2]);
		
		$a['pid']      = $article_id;
		$a['crdate']   = $crdate;
		$a['cruserid'] = $user->getId();
		
		$dbw->insert( $table, $a );	
	}	
	
	return true;
}

$wgHooks['ParserFirstCallInit'][] = 'efExampleParserFunction_Setup';
# Add a hook to initialise the magic word
$wgHooks['LanguageGetMagic'][]       = 'efExampleParserFunction_Magic';
 
function efExampleParserFunction_Setup( $parser ) {
	# Set a function hook associating the "example" magic word with our function
	$parser->setFunctionHook( 'mr_check', 'efExampleParserFunction_Render' );
	return true;
}
 
function efExampleParserFunction_Magic( $magicWords, $langCode ) {
        # Add the magic word
        # The first array element is whether to be case sensitive, in this case (0) it is not case sensitive, 1 would be sensitive
        # All remaining elements are synonyms for our parser function
        $magicWords['mr_check'] = array( 0, 'mr_check' );
        # unless we return true, other parser functions extensions won't get loaded.
        return true;
}
 
function efExampleParserFunction_Render( $parser, $param1 = '', $param2 = '' )
{
	$dbw = wfGetDB( DB_MASTER );
	$dbw->begin();

	
	$output = "";

	
	/*$res = $dbw->select('mr_check',                        // $table
						 array('pid', 'crdate'),               // $vars (columns of the table)
						 '',                                  // &conds
						 '',                                  // $fname = 'Database::select',
						 array('ORDER BY' => 'crdate ASC')); */
	
	//$res = $dbw->query( "SELECT * FROM mw_page", 'Database::select' );
	
	# The parser function itself
	# The input parameters are wikitext with templates expanded
	# The output should be wikitext too
	//$output = "param1 is $param1 and param2 is $param2";
	
	$output .= '<table width="100%" cellpadding="3" cellspacing="1" border="1" class="sortable">';
	$output .= '<tr style="font-weight: bold">
				<td>Dokumentenname</td>
				<td>Zuletzt &uuml;berpr&uuml;ft am</td>
				<td>&Uuml;berpr&uuml;ft durch</td>
				</tr>';
	
	$res = $dbw->query( "SELECT * FROM mw_mr_check" );
				
	while( $row = $dbw->fetchObject( $res ) )
	{
		$res2 = $dbw->query( "SELECT * FROM mw_page WHERE page_id = '" .$row->pid. "' LIMIT 1" );
		
		$res3 = $dbw->query( "SELECT * FROM mw_user WHERE user_id = '" .$row->cruserid. "' LIMIT 1" );

		while( $row2 = $dbw->fetchObject( $res2 ) )
		{
			while( $row3 = $dbw->fetchObject( $res3 ) )
			{
				$output .= '<tr>
							<td>[[' .$row2->page_title. ']]</td>
							<td>' .date("d.m.Y", $row->crdate). '</td>
							<td>' .$row3->user_name. '</td>
							</tr>';
			}
		}
	}
				
				
	$output .= '</table><br><br>';
	
	
	return $output;
}
