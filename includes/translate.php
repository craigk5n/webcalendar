<?php
/*
 * $Id$
 *
 * File Description:
 *	Functions here are used to support translating this application into
 *	multiple languages.  The idea is very much stolen from the GNU
 *	translate C library.
 *
 * Comments:
 *	Although there is a PHP gettext() function, I prefer to use this
 *	home-grown translate function since it is simpler to work with.
 */

if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}



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


/** reset_language
  * Description:
  *	Unload translations so we can switch languages and translate into
  *	a different language).
  * Parameters:
  *	$new_language - new language file to load (just the base filename,
  *	  no directory or file suffix.  Example: "French")
  */
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



/** load_translation_text
  * Description:
  *	Load all the language translation into an array for quick lookup.
  *	<br/>Note: There is no need to call this manually.  It will be
  *	invoked by the translate function the first time it is called.
  */
function load_translation_text () {
  global $lang_file, $translations, $basedir, $PUBLIC_ACCESS_FULLNAME, $fullname;
  $translations = array ();
  if ( strlen ( $basedir ) ) {
    $lang_file_2 = "$basedir/$lang_file";
    if ( file_exists ( $lang_file_2 ) )
      $lang_file = $lang_file_2;
  }
  if ( ! file_exists ( $lang_file ) ) {
    die_miserable_death ( "Cannot find language file: $lang_file" );
  }
  $fp = fopen ( $lang_file, "r" );
  if ( ! $fp ) {
    die_miserable_death ( "Could not open language file: $lang_file" );
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



/** translate
  * Description:
  *	Translate a string from the default English usage to some
  *	other language.  The first time that this is called, the translation
  *	file will be loaded (with the load_translation_text function).
  * Parameters:
  *	$str - text to translate
  * Returns:
  *	The translated text, if available.  If no translation is avalailable,
  *	then the original untranslated text is returned.
  */
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



/** etranslate
  * Description:
  *	Translate text and print it.
  *	This is just an abbreviation for: echo translate ( $str )
  * Parameters:
  *	$str - input text to translate and print
  */
function etranslate ( $str ) {
  echo translate ( $str );
}

/** tooltip
  * Description:
  *	Translate text and remove and HTML from it.
  *	This is useful for tooltips, which barf on HTML.
  *	<br/>Note: The etooltip function will print the result
  *	rather than return the value.
  * Parameters:
  *	$str - the input text to translate
  * Returns:
  *	The translated text with all HTML removed
  */
function tooltip ( $str ) {
  $ret = translate ( $str );
  $ret = eregi_replace ( "<[^>]+>", "", $ret );
  $ret = eregi_replace ( "\"", "'", $ret );
  return $ret;
}

/** etooltip
  * Description:
  *	Translate text and remove and HTML from it.
  *	This is useful for tooltips, which barf on HTML.
  *	<br/>Note: The tooltip function will return the result
  *	rather than print the value.
  * Parameters:
  *	$str - the input text to translate and print
  */
function etooltip ( $str ) {
  echo tooltip ( $str );
}



?>
