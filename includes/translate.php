<?php

if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}

// Functions here are used to support translating this application into
// multiple languages.  The idea is very much stolen from the GNU translate
// C library.  I implemneted this before I realized that there was a gettext()
// function added to PHP3 and PHP4.  Rather than using the built-in PHP, I'll
// stick with my implementation since it works with older PHP3.


// If set to use browser settings.
$lang = $LANGUAGE;
if ( $LANGUAGE == "Browser-defined" || $LANGUAGE == "none" ) {
  $lang = get_browser_language ();
  if ( $lang == "none" )
    $lang = "";
}

if ( strlen ( $lang ) == 0 )
  $lang = "English-US"; // Default

$lang_file = "translations/" . $lang . ".txt";

$translation_loaded = false;

$PUBLIC_ACCESS_FULLNAME = "Public Access"; // default


// Unload translations so we can switch languages and translate into
// a different language).
function reset_language ( $new_language ) {
  global $lang_file, $translations, $basedir, $lang, $translation_loaded;

  if ( $new_language != $lang || ! $translation_loaded ) {
    $translations = array ();
    $lang = $new_language;
    $lang_file = "translations/" . $lang . ".txt";
    load_translation_text ();
    $translation_loaded = true;
  }

}



// Load all the language translation into an array for quick lookup.
function load_translation_text () {
  global $lang_file, $translations, $basedir, $PUBLIC_ACCESS_FULLNAME, $fullname;
  $translations = array ();
  if ( strlen ( $basedir ) ) {
    $lang_file_2 = "$basedir/$lang_file";
    if ( file_exists ( $lang_file_2 ) )
      $lang_file = $lang_file_2;
  }
  if ( ! file_exists ( $lang_file ) ) {
    echo "Error: cannot find language file: $lang_file";
    exit;
  }
  $fp = fopen ( $lang_file, "r" );
  if ( ! $fp ) {
    echo "Error: could not open language file: $lang_file";
    exit;
  }
  while ( ! feof ( $fp ) ) {
    $buffer = fgets ( $fp, 4096 );
    $buffer = trim ( $buffer );
    if ( substr ( $buffer, 0, 1 ) == "#" || strlen ( $buffer ) == 0 )
      continue;
    $pos = strpos ( $buffer, ":" );
    $abbrev = substr ( $buffer, 0, $pos );
    $abbrev = trim ( $abbrev );
    $trans = substr ( $buffer, $pos + 1 );
    $trans = trim ( $trans );
    $translations[$abbrev] = $trans;
    //echo "Abbrev: $abbrev<br />Trans: $trans<br />\n";
  }
  fclose ( $fp );

  $PUBLIC_ACCESS_FULLNAME = translate("Public Access");
  if ( $fullname == "Public Access" ) {
    $fullname = $PUBLIC_ACCESS_FULLNAME;
  }
}



// Translate a string from the default English usage to some other language
function translate ( $str ) {
  global $translations, $translation_loaded;

  if ( ! $translation_loaded ) {
    $translation_loaded = true;
    load_translation_text ();
  }

  $str = trim ( $str );
  if ( ! empty ( $translations[$str] ) )
    return $translations[$str];
  else {
    // To help in translating, use the following to help identify text that
    // has not been translated
    // return "<blink>$str</blink>";
    return $str;
  }
}



// this is just an abbreviation for: echo translate ( $str )
function etranslate ( $str ) {
  echo translate ( $str );
}

// a version of etranslate that strips HTML out.  Useful for tooltips
// which will barf on HTML.
function tooltip ( $str ) {
  $ret = translate ( $str );
  $ret = eregi_replace ( "<[^>]+>", "", $ret );
  $ret = eregi_replace ( "\"", "'", $ret );
  return $ret;
}

function etooltip ( $str ) {
  echo tooltip ( $str );
}



?>
