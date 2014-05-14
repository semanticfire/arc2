<?php
/**
 * ARC2 SPARQL-enhanced Turtle Parser
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-11-16
*/

ARC2::inc('RDFParser');

class ARC2_JSONLDParser extends ARC2_RDFParser {

  function __construct($a, &$caller) {
    parent::__construct($a, $caller);
  }
  
  function __init() {/* reader */
    parent::__init();
    $this->state = 0;
    $this->xml = 'http://www.w3.org/XML/1998/namespace';
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->xsd = 'http://www.w3.org/2001/XMLSchema#';
    $this->nsp = array($this->xml => 'xml', $this->rdf => 'rdf', $this->xsd => 'xsd');
    $this->unparsed_code = '';
    $this->max_parsing_loops = $this->v('turtle_max_parsing_loops', 500, $this->a);
  }
  
  function getTriples() {
    return $this->v('triples', array());
  }
  
  function countTriples() {
    return $this->t_count;
  }
  
  /*  */
  
  function getUnparsedCode() {
    return $this->v('unparsed_code', '');
  }
  
  /*  */
  


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
    /* parse */
    $buffer = '';
    $more_triples = array();
    $sub_v = '';
    $sub_v2 = '';
    $loops = 0;
    $prologue_done = 0;
    while ($d = $this->reader->readStream(0)) {
      $buffer .= $d;
      $sub_v = $buffer;
      do {
        $proceed = 0;
        if (!$prologue_done) {
          $proceed = 1;
          if ((list($sub_r, $sub_v) = $this->xPrologue($sub_v)) && $sub_r) {
            $loops = 0;
            $sub_v .= $this->reader->readStream(0, 128);
            /* we might have missed the final DOT in the previous prologue loop */
            if ($sub_r = $this->x('\.', $sub_v)) $sub_v = $sub_r[1];
            if ($this->x("\@?(base|prefix)", $sub_v)) {/* more prologue to come, use outer loop */
              $proceed = 0;
            }
          }
          else {
            $prologue_done = 1;
          }
        }
        if ($prologue_done && (list($sub_r, $sub_v, $more_triples, $sub_v2) = $this->xTriplesBlock($sub_v)) && is_array($sub_r)) {
          $proceed = 1;
          $loops = 0;
          foreach ($sub_r as $t) {
            $this->addT($t);
          }
        }
      } while ($proceed);
      $loops++;
      $buffer = $sub_v;
      if ($loops > $this->max_parsing_loops) {/* most probably a parser or code bug, might also be a huge object value, though */
        $this->addError('too many loops: ' . $loops . '. Could not parse "' . substr($buffer, 0, 200) . '..."');
        break;
      }
    }
    foreach ($more_triples as $t) {
      $this->addT($t);
    }
    $sub_v = count($more_triples) ? $sub_v2 : $sub_v;
    $buffer = $sub_v;
    $this->unparsed_code = $buffer;
    $this->reader->closeStream();
    unset($this->reader);
    /* remove trailing comments */
    while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $this->unparsed_code, $m)) $this->unparsed_code = $m[2];
    if ($this->unparsed_code && !$this->getErrors()) {
      $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($this->unparsed_code, 0, 30));
      if (trim($rest)) $this->addError('Could not parse "' . $rest . '"');
    }
    return $this->done();
  }

   
  /*  */
}
