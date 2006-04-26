<?php
/**
 * $Id$
 *
 * Language translation functions.
 *
 * The idea is very much stolen from the GNU translate C library.
 *
 * We load a translation file and store it in the global variable
 * $translations.  If a cache dir is enabled (in $settings[]), then
 * we serialize $translations and store it in a file in the cache dir.
 * The next call will unserialize the cached file rather than re-parse
 * the file.
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

/** Performs html_entity_decode style conversion for php < 4.3
 * Borrowed from http://us2.php.net/manual/en/function.html-entity-decode.php
 *
 * @param string $string  Text to convert
 *
 * #return string The converted text string        
 */
function unhtmlentities ( $string ) {
  // html_entity_decode available PHP 4 >= 4.3.0, PHP 5
  if ( function_exists ( 'html_entity_decode' ) ) {
    return html_entity_decode ( $string );  
  } else { // for php < 4.3
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr(\\1)', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
  }
}

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
  if ( $new_language == 'none' )
    $new_language = get_browser_language ();
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
  global $lang_file, $translations, $basedir, $PUBLIC_ACCESS_FULLNAME,
    $fullname, $settings;
  $translations = array ();
  if ( ! empty ( $basedir ) ) {
    $lang_file_2 = "$basedir/$lang_file";
    if ( file_exists ( $lang_file_2 ) )
      $lang_file = $lang_file_2;
  }
  if ( ! file_exists ( $lang_file ) ) {
    die_miserable_death ( "Cannot find language file: $lang_file" );
  }
  // Check for 'cachedir' in settings.  If found, then we will save
  // the parsed translation file there as a serialized array.
  $cached_file = '';
  $save_to_cache = false;
  $use_cached = false;
  if ( ! empty ( $settings['cachedir'] ) &&
    is_dir ( $settings['cachedir'] ) ) {
    $cached_file = $settings['cachedir'] . '/' . $lang_file;
    $cache_tran_dir = dirname ( $cached_file );
    if ( ! is_dir ( $cache_tran_dir ) ) {
      @mkdir ( $cache_tran_dir, 0777 );
      @chmod ( $cache_tran_dir, 0777 );
    }
    if ( ! is_dir ( $cache_tran_dir ) ) {
      die_miserable_death ( 'Error creating cached translation directory: ' .
        $cache_tran_dir . "<br/><br/>" .
        'Please check the permissions of the following directory: ' .
        $settings['cachedir'] );
    }
    if ( ! file_exists ( $cached_file ) ) {
      $save_to_cache = true;
    } else {
      $mod_orig = filemtime ( $lang_file );
      $mod_cached = filemtime ( $cached_file );
      if ( $mod_orig > $mod_cached ) {
        // translation was updated.  reload/reparse and save.
        $save_to_cache = true;
      } else {
        // cached is more recent
        $use_cached = true;
      }
    }
  }
  if ( $use_cached ) {
    $translations = unserialize ( file_get_contents ( $cached_file ) );
    // boy, that was easy ;-)
  } else {
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
    if ( ! empty ( $cached_file ) && $save_to_cache ) {
      $fd = @fopen ( $cached_file, "w+b", false );
      if ( ! empty ( $fd ) ) {
        fwrite ( $fd, serialize ( $translations ) );
        fclose ( $fd );
        chmod ( $cached_file, 0666 );
      } else {
        // Could not write to cachedir
        die_miserable_death ( 'Error writing translation cache file: ' .
          $cached_file );
      }
    }
  }
}


/**
 * Gets browser-specified language preference.
 *
 * param  bool $pref  true is we want to simply display value
 *                     without affecting translations.
 * @return string Preferred language
 *
 * @ignore
 */
function get_browser_language ( $pref=false ) {
  global $HTTP_ACCEPT_LANGUAGE, $browser_languages;
  $ret = "";
  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) &&
    isset ( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
    $HTTP_ACCEPT_LANGUAGE = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
  if (  empty ( $HTTP_ACCEPT_LANGUAGE ) ) {
    if ( $pref == false ) {
      return "English-US";
    }else {
      return translate ( "Browser Language Not Found" );
    }
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
  if ( strlen ( $HTTP_ACCEPT_LANGUAGE )  && $pref == true)
    return $HTTP_ACCEPT_LANGUAGE . " ( " . translate ( "not supported" ) . " )";
  else
    return "English-US";
} 

/**
 * Translates a string from the default English usage to some other language.
 *
 * The first time that this is called, the translation file will be loaded
 * (with {@link load_translation_text()}).
 *
 * @param string $str    Text to translate
 * @param string $decode Do we want to envoke html_entity_decode
 *                       We currently only use this with javascript alerts
 *
 * @return string The translated text, if available.  If no translation is
 *                avalailable, then the original untranslated text is returned.
 */
function translate ( $str, $decode='' ) {
  global $translations, $translation_loaded;

  if ( ! $translation_loaded ) {
    $translation_loaded = true;
    load_translation_text ();
  }

  $str = trim ( $str );
  if ( ! empty ( $translations[$str] ) )
    if ( $decode == true ) {
      return  unhtmlentities ( $translations[$str] );
    } else {
      return  $translations[$str] ;
    }
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
 * @param string $str    Text to translate and print
 * @param string $decode Do we want to envoke html_entity_decode
 *
 * @uses translate
 */
function etranslate ( $str, $decode='' ) {
  echo translate ( $str, $decode );
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
function tooltip ( $str, $decode='' ) {
  $ret = translate ( $str, $decode );
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
      "Chinese (Traditional/Big5)" => "Chinese-Big5",
      "Chinese (Simplified/GB2312)" => "Chinese-GB2312",
      "Czech" => "Czech",
      "Danish" => "Danish",
      "Dutch" =>"Dutch",
      "Estonian" => "Estonian",
      "Finnish" =>"Finnish",
      "French" =>"French",
      "Galician" => "Galician",
      "German" =>"German",
      "Greek" =>"Greek",
      "Holo (Taiwanese)" => "Holo-Big5",
      "Hungarian" =>"Hungarian",
      "Icelandic" => "Icelandic",
      "Italian" => "Italian",
      "Japanese(UTF-8)" => "Japanese",
      "Japanese(SHIFT JIS)" => "Japanese-sjis",
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
      "en-ca" => "English-US",
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
      "el" =>"Greek",
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
      "ru-ru" =>"Russian", //Safari reports this
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
    // translate("Chinese (Traditional/Big5)")
    // translate("Chinese (Simplified/GB2312)")
    // translate("Czech")
    // translate("Danish")
    // translate("Dutch")
    // translate("Estonian")
    // translate("Finnish")
    // translate("French")
    // translate("Galician")
    // translate("German")
    // translate("Greek")
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
    
//General purpose translations that may be used elsewhere
//as variables and not picked up by update_translation.pl
   // translate ( "task" );
   // translate ( "event" );
   // translate ( "journal" );
   
?>
