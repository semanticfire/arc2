<?php
/**
 * ARC2 Turtle Serializer
 *
 * @author    Benjamin Nowack
 * @license   http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package   ARC2
 * @version   2010-11-16
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

  /*  */
  
  
  
  
  function getSerializedIndex($index, $raw = 0) {
   	return json_encode($this->jsonldFromARC2Index($index));
  }
  
function jsonldFromARC2Index($index)
{
	$subjects = array();
	
	foreach($index as $subject => $props)
	{
		if(!isset($subjects[$subject]))
		{
			$s= new stdClass();
			//$o = new stdClass();
			$s->{'@id'} = $subject;
			//$s->{'@subject'} = $o;
			//$s = array('@id'=>$subject);
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
	
	//$output['@context'] = $this->ns;
	//print_r($output);die();
	return $output;
}
  
  /*  */

}
