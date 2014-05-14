<?php
/**
 * ARC2 JSON-LD Serializer
 *
 * @author    Bart van Leeuwen
 * @license W3C Software License
 * @homepage <http://arc.semsol.org/>
 * @package   ARC2
 * @version   2014-05-14
 */

ARC2::inc('RDFSerializer');

class ARC2_JSONLDSerializer extends ARC2_RDFSerializer {

	function __construct($a, &$caller) {
		parent::__construct($a, $caller);
	}

	function __init() {
		parent::__init();
		$this->content_header = 'application/json-ld';
	}


	function getSerializedIndex($index, $raw = 0) {
		return json_encode($this->jsonldFromARC2Index($index));
	}

	private function jsonldFromARC2Index($index)
	{
		$subjects = array();

		foreach($index as $subject => $props)
		{
			if(!isset($subjects[$subject]))
			{
				$s= new stdClass();
				$s->{'@id'} = $subject;
				$subjects[$subject] = $s ;
			}
			foreach($props as $prop => $objs)
			{
				foreach($objs as $obj)
				{
					if(!isset($subjects[$subject]->$prop))
					{
						$subjects[$subject]->$prop = array();
					}
					if($obj['type'] === 'literal' && isset($obj['datatype']))
					{
						$o = new stdClass();
						$o->{'@type'} = $obj['datatype'];
						$o->{'@literal'} = $obj['value'];
						$subjects[$subject]->$prop = $o;
					}
					elseif($obj['type'] === 'literal')
					{
							
						if(isset($obj['lang']))
							$subjects[$subject]->$prop = array('@value'=>$obj['value'],
									'@language'=>$obj['lang']);
						else
							$subjects[$subject]->$prop = $obj['value'];
					}
					else
					{
						$o = new stdClass();
						$o->{'@id'}= $obj['value'];
						//$o->datatype = '@iri';
						$subjects[$subject]->$prop = $o;
					}
				}
			}
		}

		$output = array();
		foreach($subjects as $subject => $value)
		{
			$output[] = $value;
		}
		return $output;
	}
}