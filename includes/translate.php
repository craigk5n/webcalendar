<?php
/**
 * Language translation functions.
 *
 * The idea is very much stolen from the GNU translate C library.
 *
 * Although there is a PHP gettext() function, I prefer to use this home-grown
 * translate function since it is simpler to work with.
 *
 * @version $Id$
 * @package WebCalendar
 */


if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
  die ( "You can't access this file directly!" );
}


if ( empty ( $LANGUAGE ) ) {
  $LANGUAGE = '';
}

// If set to use browser settings, use the user's language preferences
// from their browser.
$lang = $LANGUAGE;
if ( $LANGUAGE == "Browser-defined" || $LANGUAGE == "none" ) {
  $lang = get_browser_language ();
  if ( $lang == "none" )
    $lang = "";
}
if ( strlen ( $lang ) == 0 || $lang == 'none' ) {
  $lang = "English-US"; // Default
}

$lang_file = "translations/" . $lang . ".txt";

$translation_loaded = false;

$PUBLIC_ACCESS_FULLNAME = "Public Access"; // default


/**
 * Unloads translations so we can switch languages and translate into a
 * different language.
 *
 * @param string $new_language New language file to load (just the base
 *                             filename, no directory or file suffix.  Example:
 *                             "French")
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



/**
 * Loads all the language translation into an array for quick lookup.
 *
 * <b>Note:</b> There is no need to call this manually.  It will be invoked by
 * {@link translate()} the first time it is called.
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
    //  stripslashes may cause problems with Japanese translations
   // if so, we may have to make this configurable.
    if ( get_magic_quotes_runtime() ) {
      $buffer = stripslashes ( $buffer );
    }
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
  $PUBLIC_ACCESS_FULLNAME = translate ("Public Access" );
  if ( $fullname == "Public Access" ) {
    $fullname = $PUBLIC_ACCESS_FULLNAME;
  }
}



/**
 * Translates a string from the default English usage to some other language.
 *
 * The first time that this is called, the translation file will be loaded
 * (with {@link load_translation_text()}).
 *
 * @param string $str Text to translate
 *
 * @return string The translated text, if available.  If no translation is
 *                avalailable, then the original untranslated text is returned.
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



/**
 * Translates text and prints it.
 *
 * This is just an abbreviation for:
 *
 * <code>echo translate ( $str )</code>
 *
 * @param string $str Text to translate and print
 *
 * @uses translate
 */
function etranslate ( $str ) {
  echo translate ( $str );
}

/**
 * Translates and removes HTML from text, and returns it.
 *
 * This is useful for tooltips, which barf on HTML.
 *
 * <b>Note:</b> {@link etooltip()} will print the result rather than return the
 * value.
 *
 * @param string $str Text to translate
 *
 * @return string The translated text with all HTML removed
 */
function tooltip ( $str ) {
  $ret = translate ( $str );
  $ret = eregi_replace ( "<[^>]+>", "", $ret );
  $ret = eregi_replace ( "\"", "'", $ret );
  return $ret;
}

/**
 * Translates and removes HTML from text, and prints it.
 *
 * This is useful for tooltips, which barf on HTML.
 *
 * <b>Note:</b> {@link tooltip()} will return the result rather than print
 * the value.
 *
 * @param string $str Text to translate and print
 *
 * @uses tooltip
 */
function etooltip ( $str ) {
  echo tooltip ( $str );
}

?>
