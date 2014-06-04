<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 SPARQL 1.1 
author:   Bart van Leeuwen
version:  2012-05-03
*/

ARC2::inc('SPARQLParser');

class ARC2_SPARQL11Parser extends ARC2_SPARQLParser {

  function __construct($a, &$caller) {
    parent::__construct($a, $caller);
  }
  
  function __init() {
    parent::__init();
  }

  /* +1 */
  
  function xQuery($v) {
  /*	var_dump($v);
	return(array(null,""));*/
  	list($r, $v) = $this->xPrologue($v);
  	
  	
  	if($this->x('DELETE|LOAD|WITH|INSERT|CLEAR',$v))
  	{
  		return array(array("type"=>"insert"),"");
  	}
  	elseif($this->x('DESCRIBE',$v))
  		return array(array("type"=>"describe"),"");
  	elseif($this->x('CONSTRUCT',$v))
  		return array(array("type"=>"construct"),"");
  	else
  		return array(array("type"=>"select"),"");
  	
  
  }

  function parse($q, $src = '', $iso_fallback = 'ignore') {
  	$this->setDefaultPrefixes();
  	$this->base = $src ? $this->calcBase($src) : ARC2::getRequestURI();
  	$this->r = array(
  			'base' => '',
  			'vars' => array(),
  			'prefixes' => array()
  	);
  	$this->unparsed_code = $q;
 
  	list($r, $v) = $this->xQuery($q);
  	
  	if ($r) {
  		$this->r['query'] = $r;
  		$this->unparsed_code = trim($v);
  	}
  	elseif (!$this->getErrors() && !$this->unparsed_code) {
  		$this->addError('Query not properly closed');
  	}

    $this->r['prefixes'] = $this->prefixes;
  	$this->r['base'] = $this->base;
  	/* remove trailing comments */
  	while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $this->unparsed_code, $m)) $this->unparsed_code = $m[2];
  	if ($this->unparsed_code && !$this->getErrors()) {
  		$rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($this->unparsed_code, 0, 30));
  		$msg = trim($rest) ? 'Could not properly handle "' . $rest . '"' : 'Syntax error, probably an incomplete pattern';
  		$this->addError($msg);
  	}
  }
  
}  
