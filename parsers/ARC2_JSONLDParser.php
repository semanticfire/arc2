<?php
/**
 * ARC2 JSON-LD Parser
 *
 * @author Bart van Leeuwen
 * @license W3C Software License
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2014-05-14
 */

// include json-ld-php
require_once ARC2::getIncPath().'/support/php-json-ld/jsonld.php';
ARC2::inc('RDFParser');

class ARC2_JSONLDParser extends ARC2_RDFParser {

	function __construct($a, &$caller) {
		parent::__construct($a, $caller);
	}

	function getTriples() {
		return $this->v('triples', array());
	}

	function countTriples() {
		return $this->t_count;
	}

	function parse($path, $data = '', $iso_fallback = false) {
		$this->setDefaultPrefixes();
		/* reader */
		if (!$this->v('reader')) {
			ARC2::inc('Reader');
			$this->reader = new ARC2_Reader($this->a, $this);
		}
		$this->reader->setAcceptHeader('Accept: application/ld+json; q=0.9, */*; q=0.1');
		$this->reader->activate($path, $data);
		$this->base = $this->v1('base', $this->reader->base, $this->a);
		$this->r = array('vars' => array());
		$buffer = null;
		// just read all data
		while ($d = $this->reader->readStream(0)) {
			$buffer .= $d;
		}
		$context = jsonld_decode($buffer)->{'@context'};
		// if context is a string, load the remote context
		if(!is_object($context))
		{
			$contextSource = file_get_contents($context);
			$loadedCtx = jsonld_decode($contextSource);
			$context = $loadedCtx->{'@context'};
		}

		foreach($context as $key => $val)
			if( !is_object($val) )
			if($key[0]!='@' && ( $val[strlen($val)-1] == "/" ||$val[strlen($val)-1] == "#"))
			$this->setPrefix($key,$val);

		/**/
		$jsonlddata = jsonld_to_rdf(jsonld_expand(jsonld_decode($buffer)),array("base"=>$path));

		foreach($jsonlddata->{'@default'} as $val)
		{
			$newTriple=Array();
			$newTriple['type'] = 'triple';
			$newTriple['s'] = $val->subject->value;
			$newTriple['s_type'] = $this->typeConvert($val->subject->type);
			$newTriple['p'] = $val->predicate->value;
			$newTriple['p_type'] = $this->typeConvert($val->predicate->type);
			$newTriple['o'] = $val->object->value;
			$newTriple['o_type'] = $this->typeConvert($val->object->type);
			if(isset($val->object->datatype) && $val->object->datatype != "http://www.w3.org/1999/02/22-rdf-syntax-ns#langString")
				$newTriple['o_datatype'] = $val->object->datatype;
			if(isset($val->object->language))
				$newTriple['o_lang'] = $val->object->language;
			$this->triples[]=$newTriple;
			$this->t_count++;
			unset($newTriple);
		}
		return;
	}

	private function typeConvert($jsonldtype)
	{
		switch($jsonldtype)
		{
			case "IRI": return "uri";
			break;
			case "blank node": return "bnode";
			break;
			default: return $jsonldtype;
		}
	}
	
	function setDefaultPrefixes() {
		$prefixes = array(
				'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
				'owl' => 'http://www.w3.org/2002/07/owl#',
				'xsd' => 'http://www.w3.org/2001/XMLSchema#',
		);
		foreach($prefixes as $p => $u)
			$this->setPrefix($p,$u);
	}
}
?>