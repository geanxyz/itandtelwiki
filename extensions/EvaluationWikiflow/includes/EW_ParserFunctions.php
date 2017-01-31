<?php
class EWParserFunctions{
	function run_revisionid(&$parser,$pagename = ""){
		$id = $this->getRevisionId($pagename);
		return array($id);
	}
	function run_iscurrent(&$parser,$number,$pagename = ""){
		$currentid = $this->getRevisionid($pagename);
		$ret = ($number==$currentid)?1:0;
		return array($ret);
	}
	
	function getRevisionId($pagename=""){
		global $wgParser, $wgTitle;
		if ($pagename == ""){
			if($wgTitle!=null){
				$article = new Article( $wgTitle );
			}else return null;
		}else{
			$pagetitle=Title::newFromText($pagename);
			if($pagetitle != null)
				$article = new Article( $pagetitle  );
			else 
				return 0;
		}
		$article->loadContent();
		return $article->mRevIdFetched ;	
	}
	function run_iswaiting(&$parser,$pagename = ""){
		global $wgTitle;
		if ($pagename == ""){
			$article = new Article( $wgTitle );
		}else{
			$pagetitle=Title::newFromText($pagename);
			if($pagetitle != null)
				$article = new Article( $pagetitle  );
			else 
				return 0;
		}
		$ewa = new EWArticle($article);
		return $ewa->waiting();
	}
	function run_isassigned(&$parser,$pagename = ""){
		global $wgTitle;
		if ($pagename == ""){
			$article = new Article( $wgTitle );
		}else{
			$pagetitle=Title::newFromText($pagename);
			if($pagetitle != null)
				$article = new Article( $pagetitle  );
			else 
				return 0;
		}
		$ewa = new EWArticle($article);
		return $ewa->assigned();
	}
	function run_needsedit(&$parser,$pagename = ""){
		global $wgTitle;
		if ($pagename == ""){
			$article = new Article( $wgTitle );
		}else{
			$pagetitle=Title::newFromText($pagename);
			if($pagetitle != null)
				$article = new Article( $pagetitle  );
			else 
				return 0;
		}
		$ewa = new EWArticle($article);
		return $ewa->needsEdit();
	}
	function run_iscertified(&$parser,$pagename = ""){
		global $wgTitle;
		if ($pagename == ""){
			if($wgTitle!=null){
				$article = new Article( $wgTitle );
			}else{
				return null;
			}
		}else{
			$pagetitle=Title::newFromText($pagename);
			if($pagetitle != null)
				$article = new Article( $pagetitle  );
			else 
				return 0;
		}
		$ewa = new EWArticle($article);
		return $ewa->certified();
	}
	function run_reviewabout(&$parser, $pagename = ""){
		$output = "[[". EW_PROPERTY_REVIEW_ABOUT . '::' . $pagename."|]]";
		wfDebugLog('ew',$output);
		return array($output, 'noparse' => false, 'isHTML' => false);
	}
}
?>