<?php
/**
 * @author Enrico Daga
 */

// Set input type hook for EW_INPUT_REVIEWER and EW_INPUT_REVIEWABOUT
global $sfgFormPrinter;
$sfgFormPrinter->setInputTypeHook(EW_INPUT_REVIEWER, array( 'EWInputPrinter', 'printInputReviewer'), array() );
$sfgFormPrinter->setInputTypeHook(EW_INPUT_ABOUTPAGE, array( 'EWInputPrinter', 'printInputAboutpage'), array() );
$sfgFormPrinter->setInputTypeHook(EW_INPUT_ABOUTREVISION, array( 'EWInputPrinter', 'printInputAboutrevision'), array() );

class EWInputPrinter {
	/**
	 * TODO
	 * How to do with EDIT!!!???
	 * @var unknown_type
	 */
	private static $mReviewer = null; // This must be type User
	private static $mAboutpage = null; // This must be type Title
	private static $mAboutrevision = null; // This must be type int, is the revid of the revision
	
	public static function setReviewer(User $reviewer){
		EWInputPrinter::$mReviewer = $reviewer;
	}
	public static function setAboutpage(Title $about){
		EWInputPrinter::$mAboutpage = $about;
	}
	public static function setAboutrevision($revisionid){
		EWInputPrinter::$mAboutrevision = $revisionid;
	}	
	private static function getReviewer(){
		return EWInputPrinter::$mReviewer;
	}
	private static function getAboutpage(){
		return EWInputPrinter::$mAboutpage;
	}	
	private static function getAboutrevision(){
		return EWInputPrinter::$mAboutrevision;
	}

	/**
	 * Input for reviewer
	 *
	 * @param unknown_type $cur_value
	 * @param unknown_type $input_name
	 * @param unknown_type $is_mandatory
	 * @param unknown_type $is_disabled
	 * @param unknown_type $other_args
	 * @return unknown
	 */
	static function printInputReviewer($cur_value, $input_name, $is_mandatory, $is_disabled, $other_args) {
		global $sfgTabIndex;
		$input_value = EWInputPrinter::getReviewer()->getName();
		if(!isset($other_args['hidens'])) $input_value = Title::newFromText($input_value,NS_USER)->getFullText(); 
		$disabled_text = ($is_disabled) ? "DISABLED=\"DISABLED\"": "";
		$outputhtml =<<<END
		 <input type="hidden" value="$input_value" tabindex="$sfgTabIndex" name="$input_name" $disabled_text/>
		<b>$input_value</b>
END;

		return array($outputhtml, null);
	}
	
	/**
	 * Input for page
	 *
	 * @param unknown_type $cur_value
	 * @param unknown_type $input_name
	 * @param unknown_type $is_mandatory
	 * @param unknown_type $is_disabled
	 * @param unknown_type $other_args
	 * @return unknown
	 */
	static function printInputAboutpage($cur_value, $input_name, $is_mandatory, $is_disabled, $other_args) {
		global $sfgTabIndex;
		
		if(isset($other_args['hidens'])) {
			$input_value = EWInputPrinter::getAboutpage()->getText();
		}else{
			$input_value = EWInputPrinter::getAboutpage()->getFullText();
		}
		$disabled_text = ($is_disabled) ? "DISABLED=\"DISABLED\"": "";
		$outputhtml =<<<END
		 <input type="hidden" value="$input_value" tabindex="$sfgTabIndex" name="$input_name" $disabled_text/>
		<b>$input_value</b>
END;

		return array($outputhtml, null);
	}	

	static function printInputAboutrevision($cur_value, $input_name, $is_mandatory, $is_disabled, $other_args) {
		global $sfgTabIndex;
		$input_value = EWInputPrinter::getAboutrevision();
		$disabled_text = ($is_disabled) ? "DISABLED=\"DISABLED\"": "";
		$outputhtml =<<<END
		 <input type="hidden" value="$input_value" tabindex="$sfgTabIndex" name="$input_name" $disabled_text/>
		<b>$input_value</b>
END;

		return array($outputhtml, null);
	}	
}
?>