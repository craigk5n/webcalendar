<?php
  // hcal profile for hkit
  // hacked together by Ray Jones 06/26/2006

  $this->root_class = 'vevent';

  $this->classes = array (
    'dtstart',
    'dtend',
    'duration',
    'rrule', 'exdate', 'rdate',
    'summary', 'description',
    'location',
    'url',
    'class', 'categories', 'priority',
    'transp', 'status',
    'tz', 'uid'
  );

  // classes that must only appear once per event
  $this->singles = array (
    'summary'
  );

  // classes that are required (not strictly enforced - give at least one!)
  $this->required = array (
    'dtstart'
  );

  $this->att_map = array (
    'url'  => array ('A|href', 'IMG|src', 'AREA|href')
  );


  $this->callbacks = array (
    'url'  => array ($this, 'resolvePath'),
    'email'  => array ($this, 'resolveEmail')
  );



  function hKit_hcal_post($a)
  {
    foreach ($a as &$vevent){

      hKit_labels_toUpper($vevent);

    }

    return $a;

  }


  function hKit_labels_toUpper(&$vevent)
  {
    $vevent = array_change_key_case($vevent, CASE_UPPER);
  }

?>