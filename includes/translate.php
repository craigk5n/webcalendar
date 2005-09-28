<?php
/**
 * Language translation functions.
 *
 * The idea is very much stolen from the GNU translate C library.
 *
 * Although there is a PHP gettext() function, I prefer to use this home-grown
 * translate function since it is simpler to work with.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */




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
  $fp = fopen ( $lang_file, "r", false );
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
 * Gets browser-specified language preference.
 *
 * @return string Preferred language
 *
 * @ignore
 */
function get_browser_language () {
  global $HTTP_ACCEPT_LANGUAGE, $browser_languages;
  $ret = "";
  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) &&
    isset ( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
    $HTTP_ACCEPT_LANGUAGE = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
  if (  empty ( $HTTP_ACCEPT_LANGUAGE ) ) {
    return "none";
  } else {
    $langs = explode ( ",", $HTTP_ACCEPT_LANGUAGE );
    for ( $i = 0; $i < count ( $langs ); $i++ ) {
     $l = strtolower ( trim ( ereg_replace(';.*', '', $langs[$i] ) ) );
      $ret .= "\"$l\" ";
      if ( ! empty ( $browser_languages[$l] ) ) {
        return $browser_languages[$l];
      }
    }
  }
  //if ( strlen ( $HTTP_ACCEPT_LANGUAGE ) )
  //  return "none ($HTTP_ACCEPT_LANGUAGE not supported)";
  //else
    return "none";
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
    //return "<blink>$str</blink>";
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

   // Language options  The first is the name presented to users while
    // the second is the filename (without the ".txt") that must exist
    // in the translations subdirectory.
    $languages = array (
      "Browser-defined" =>"none",
      "English" =>"English-US",
      "Basque" => "Basque",
      "Bulgarian" => "Bulgarian",
      "Catalan" => "Catalan",
      "Chinese (Traditonal/Big5)" => "Chinese-Big5",
      "Chinese (Simplified/GB2312)" => "Chinese-GB2312",
      "Czech" => "Czech",
      "Danish" => "Danish",
      "Dutch" =>"Dutch",
      "Estonian" => "Estonian",
      "Finnish" =>"Finnish",
      "French" =>"French",
      "Galician" => "Galician",
      "German" =>"German",
      "Holo (Taiwanese)" => "Holo-Big5",
      "Hungarian" =>"Hungarian",
      "Icelandic" => "Icelandic",
      "Italian" => "Italian",
      "Japanese(UTF-8)" => "Japanese",
      "Japanese(SHIFT JIS)" => "Japanese-sjis.txt",
      "Japanese(EUC-JP)" => "Japanese-eucjp",
      "Korean" =>"Korean",
      "Norwegian" => "Norwegian",
      "Polish" => "Polish",
      "Portuguese" =>"Portuguese",
      "Portuguese/Brazil" => "Portuguese_BR",
      "Romanian" =>"Romanian",
      "Russian" => "Russian",
      "Spanish" =>"Spanish",
      "Swedish" =>"Swedish",
      "Turkish" =>"Turkish",
      "Welsh" => "Welsh"
      // add new languages here!  (don't forget to add a comma at the end of
      // last line above.)
    );

    // If the user sets "Browser-defined" as their language setting, then
    // use the $HTTP_ACCEPT_LANGUAGE settings to determine the language.
    // The array below translates browser language abbreviations into
    // our available language files.
    // NOTE: These should all be lowercase on the left side even though
    // the proper listing is like "en-US"!
    // Not sure what the abbreviation is?  Check out the following URL:
    // http://www.geocities.com/click2speak/languages.html
    $browser_languages = array (
      "eu" => "Basque",
      "bg" => "Bulgarian",
      "ca" => "Catalan",
      "zh" => "Chinese-GB2312",    // Simplified Chinese
      "zh-cn" => "Chinese-GB2312",
      "zh-tw" => "Chinese-Big5",   // Traditional Chinese
      "cs" => "Czech",
      "en" => "English-US",
      "en-us" => "English-US",
      "en-gb" => "English-US",
      "da" => "Danish",
      "nl" =>"Dutch",
      "ee" => "Estonian",
      "fi" =>"Finnish",
      "fr" =>"French",
      "fr-ch" =>"French", // French/Swiss
      "fr-ca" =>"French", // French/Canada
      "gl" => "Galician",
      "de" =>"German",
      "de-at" =>"German", // German/Austria
      "de-ch" =>"German", // German/Switzerland
      "de-de" =>"German", // German/German
      "hu" => "Hungarian",
      "zh-min-nan-tw" => "Holo-Big5",
      "is" => "Icelandic",
      "it" => "Italian",
      "it-ch" => "Italian", // Italian/Switzerland
      "ja" => "Japanese",
      "ko" =>"Korean",
      "no" => "Norwegian",
      "pl" => "Polish",
      "pt" =>"Portuguese",
      "pt-br" => "Portuguese_BR", // Portuguese/Brazil
      "ro" =>"Romanian",
      "ru" =>"Russian",
      "es" =>"Spanish",
      "sv" =>"Swedish",
      "tr" =>"Turkish",
      "cy" => "Welsh"
    );
  
    // The following comments will be picked up by update_translation.pl so
    // translators will be aware that they also need to translate language names.
    //
    // translate("English")
    // translate("English-US")
    // translate("Basque")
    // translate("Bulgarian")
    // translate("Catalan")
    // translate("Chinese (Traditonal/Big5)")
    // translate("Chinese (Simplified/GB2312)")
    // translate("Czech")
    // translate("Danish")
    // translate("Dutch")
    // translate("Estonian")
    // translate("Finnish")
    // translate("French")
    // translate("Galician")
    // translate("German")
    // translate("Holo (Taiwanese)")
    // translate("Hungarian")
    // translate("Icelandic")
    // translate("Italian")
    // translate("Japanese")
    // translate("Korean")
    // translate("Norwegian")
    // translate("Polish")
    // translate("Portuguese")
    // translate("Portuguese/Brazil")
    // translate("Romanian")
    // translate("Russian")
    // translate("Spanish")
    // translate("Swedish")
    // translate("Turkish")
    // translate("Welsh")
?>
