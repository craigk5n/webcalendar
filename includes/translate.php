<?php
/* Language translation functions.
 *
 * The idea is very much stolen from the GNU translate C library.
 *
 * We load a translation file and store it in the global array $translations.
 * If a cache dir is enabled (in $settings[]), then we serialize $translations
 * and store it as a file in the cache dir. The next call will unserialize the
 * cached file rather than re-parse the file.
 *
 * Although there is a PHP gettext () function, I prefer to use this home-grown
 * translate function since it is simpler to work with.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: translate.php,v 1.68.2.13 2012/02/20 16:23:09 cknudsen Exp $
 * @package WebCalendar
 */

/* Performs html_entity_decode style conversion for php < 4.3
 * Borrowed from http://us2.php.net/manual/en/function.html-entity-decode.php
 *
 * @param string $string Text to convert
 * @paran bool   $ignore Ignore the charset when decoding
 *
 * #return string The converted text string
 */
function unhtmlentities ( $string ) {
  global $charset;

  // TODO:  Not sure what to do here re: UTF-8 encoding.

  // html_entity_decode available PHP 4 >= 4.3.0, PHP 5.
  if ( function_exists ( 'html_entity_decode' ) )
    return html_entity_decode ( $string, ENT_QUOTES );
  else { // For PHP < 4.3.
    // Replace numeric entities.
    $string =
    preg_replace ( '~&#x([0-9a-f]+);~ei', 'chr ( hexdec ( "\\1" ) )', $string );
    // Replace literal entities.
    return strtr (
      preg_replace ( '~&#([0-9]+);~e', 'chr ( \\1 )', $string ),
      array_flip ( get_html_translation_table ( HTML_ENTITIES, ENT_QUOTES ) ) );
  }
}
/* Read in a language file and cache it if we can.
 *
 * @param string $in_file   The name of the language file to read.
 * @param string $out_file  Name of the cache file.
 * @param bool   $strip     Do we want to call stripslashes?
 *                          It may cause problems with Japanese translations.
 */

function read_trans_file ( $in_file, $out_file = '', $strip = true ) {
  global $can_save, $new_install, $translations;

  $fp = fopen ( $in_file, 'r', false );
  if ( ! $fp )
    die_miserable_death ( 'Could not open language file: ' . $in_file );

  $inInstallTrans = false;
  $installationTranslations = array ();

  while ( ! feof ( $fp ) ) {
    $buffer = trim ( fgets ( $fp, 4096 ) );
    if ( strlen ( $buffer ) == 0 )
      continue;

    if ( get_magic_quotes_runtime () && $strip )
      $buffer = stripslashes ( $buffer );

    // Convert quotes to entities.
    $buffer =
    str_replace ( array ( '"', "'" ), array ( '&quot;', '&#39;' ), $buffer );

    // Skip comments.
    if ( substr ( $buffer, 0, 1 ) == '#' ) {
      if ( substr ( $buffer, 0, 7 ) == '# Page:' )
        $inInstallTrans = ( substr ( $buffer, 9, 7 ) == 'install' );

      continue;
    }

    // Skip installation translations unless we're running install/index.php.
    if ( $inInstallTrans && ! $new_install )
      continue;

    $pos = strpos ( $buffer, ':' );
    $abbrev = trim ( substr ( $buffer, 0, $pos ) );
    $temp = trim ( substr ( $buffer, $pos + 1 ) );

    // If the translation is the same as the English text,
    // tools/update_translation.pl should signify this with an "=" sign
    // in the user's language file so they don't show as << MISSING >>.
    if ( $temp !== '=' ) {
      if ( $inInstallTrans && $new_install )
        $installationTranslations[$abbrev] = $temp;
      else
        $translations[$abbrev] = $temp;
    }
  }
  fclose ( $fp );

  if ( stristr ( $in_file, 'english' ) )
    ksort ( $translations );

  // We want to cache all the non-installation phrases...
  if ( $can_save && ! empty ( $out_file ) ) {
    $fd = @fopen ( $out_file, 'wb', false );
    if ( ! empty ( $fd ) ) {
      fwrite ( $fd, serialize ( $translations ) );
      fclose ( $fd );
      chmod ( $out_file, 0666 );
    }
  }
  // but, we still need them in the array if we ARE installing.
  if ( $new_install )
    $translations = array_merge ( $translations, $installationTranslations );
}

/* Unloads $translations so we can translate a different language.
 *
 * @param string $new_language New language file to load (just the base filename,
 *                             no directory or file suffix. Example:  "French")
 */
function reset_language ( $new_language ) {
  global $fullname, $lang, $lang_file,
  $PUBLIC_ACCESS_FULLNAME, $translation_loaded, $translations;
  if ( $new_language == 'none' )
    $new_language = get_browser_language ();

  if ( $new_language != $lang || ! $translation_loaded ) {
    $lang = $new_language;
    $lang_file = 'translations/' . $lang . '.txt';
    $translation_loaded = false;
    load_translation_text ();
  }
  $PUBLIC_ACCESS_FULLNAME = translate ( 'Public Access' );
  if ( $fullname == 'Public Access' )
    $fullname = $PUBLIC_ACCESS_FULLNAME;
}

/* Loads all the language translation into an array for quick lookup.
 *
 * <b>Note:</b> There is no need to call this manually.
 * It will be invoked by {@link translate () } the first time it is called.
 */
function load_translation_text () {
  global $lang_file, $settings, $translation_loaded, $translations;

  if ( $translation_loaded ) // No need to run this twice.
    return;
  $eng_file = 'translations/English-US.txt';
  $lang_cache = substr ( $lang_file, strrpos ( $lang_file, '/' ) + 1 );
  $lang_file_2 = '';

  if ( defined ( '__WC_BASEDIR' ) ) {
    if ( ! file_exists ( $lang_file ) )
      $lang_file_2 = __WC_BASEDIR . '/' . $lang_file;

    if ( file_exists ( $lang_file_2 ) )
      $lang_file = $lang_file_2;

    if ( ! file_exists ( $lang_file ) )
      $lang_file = 'translations/' . $lang_cache;

  }
  if ( ! file_exists ( $lang_file ) )
      $lang_file = $eng_file;

  if ( ! file_exists ( $lang_file ) )
    die_miserable_death ( 'Cannot find language file: ' . $lang_file );

  $cached_base_file = $cached_file = $cachedir = '';
  $can_save = false;

  if ( ! file_exists ( $eng_file ) )
    $eng_file = '../' . $eng_file;

  // Check for 'cachedir' in settings. If found, then we will save
  // the parsed translation file there as a serialized array.
  // Ensure we use the proper cachedir name.
  if ( ! empty ( $settings['cachedir'] ) && is_dir ( $settings['cachedir'] ) )
    $cachedir = $settings['cachedir'];
  else
  if ( ! empty ( $settings['db_cachedir'] ) && is_dir ( $settings['db_cachedir'] ) )
    $cachedir = $settings['db_cachedir'];

  if ( ! empty ( $cachedir ) && function_exists ( 'file_get_contents' ) ) {
    $cached_base_file = $cached_file = $cachedir . '/translations/';
    $cached_base_file .= 'English-US.txt';
    $cached_file .= $lang_cache;
    $cache_tran_dir = dirname ( $cached_file );

    if ( ! is_dir ( $cache_tran_dir ) ) {
      @mkdir ( $cache_tran_dir, 0777 );
      @chmod ( $cache_tran_dir, 0777 );
      /*
      // Do we really want to die if we can't save the cache file?
      // Or should we just run without it?
      if ( ! is_dir ( $cache_tran_dir ) )
        die_miserable_death ( 'Error creating translation cache directory: "'
           . $cache_tran_dir
           . '"<br /><br />Please check the permissions of the directory: "'
           . $cachedir . '"' );
 */
    }

    $can_save = ( is_writable ( $cache_tran_dir ) );
  }

  $new_install = ( ! strstr ( $_SERVER['SCRIPT_NAME'], 'install/index.php' ) );
  $translations = array ();

  // First set default $translations[]
  // by reading the base English-US.txt file or it's cache.
  if ( empty ( $cached_base_file ) )
    read_trans_file ( $eng_file );
  else {
    if ( ! file_exists ( $cached_base_file ) ||
        filemtime ( $eng_file ) > filemtime ( $cached_base_file ) )
      read_trans_file ( $eng_file, $cached_base_file );
    else
      // Cache is newer.
      $translations = unserialize ( file_get_contents ( $cached_base_file ) );
  }

  // Then, if language is not English,
  // read in the user's language file to overwrite the array.
  // This will ensure that any << MISSING >> phrases at least have a default.
  if ( $lang_file !== $eng_file ) {
    if ( empty ( $cached_file ) )
      read_trans_file ( $lang_file );
    else {
      if ( ! file_exists ( $cached_file ) ||
          ( filemtime ( $lang_file ) > filemtime ( $cached_file ) ) )
        read_trans_file ( $lang_file, $cached_file );
      else
        // Cache is newer.
        $translations = unserialize ( file_get_contents ( $cached_file ) );
    }
  }

  $translation_loaded = true;
}

/* Gets browser-specified language preference.
 *
 * param bool $pref true is we want to simply display value
 *                  without affecting translations.
 *
 * @return string Preferred language
 * @ignore
 */
function get_browser_language ( $pref = false ) {
  global $browser_languages, $HTTP_ACCEPT_LANGUAGE;

  $ret = '';
  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) &&
      isset ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) )
    $HTTP_ACCEPT_LANGUAGE = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) )
    return ( $pref ? translate ( 'Browser Language Not Found' ) : 'English-US' );
  else {
    $langs = explode ( ',', $HTTP_ACCEPT_LANGUAGE );
    for ( $i = 0, $cnt = count ( $langs ); $i < $cnt; $i++ ) {
      $l = strtolower ( trim ( preg_replace ( '/;.*/', '', $langs[$i] ) ) );
      $ret .= "\"$l\" ";
      if ( ! empty ( $browser_languages[$l] ) )
        return $browser_languages[$l];
    }
  }
  return ( strlen ( $HTTP_ACCEPT_LANGUAGE ) && $pref == true
    ? $HTTP_ACCEPT_LANGUAGE . ' ( ' . translate ( 'not supported' ) . ' )'
    : 'English-US' );
}

function translation_exists ( $str )
{
  global $translation_loaded, $translations;
  if ( ! $translation_loaded )
    return false;
  return ( empty ( $translations[$str] ) ? false : true );
}

/* Translates a string from the default English usage to another language.
 *
 * The first time that this is called, the translation file will be loaded
 * (with {@link load_translation_text () }).
 *
 * @param string $str    Text to translate
 * @param string $decode Do we want to envoke html_entity_decode?
 *                       We currently only use this with javascript alerts.
 *
 * @return string The translated text, if available. If no translation is
 *                avalailable, then the original untranslated text is returned.
 */
function translate ( $str, $decode = '' ) {
  global $translation_loaded, $translations;

  //Set $blink to true to aid in finding missing translations
  $blink = false;

  if ( ! $translation_loaded )
    load_translation_text ();

  $str = trim ( $str );
  return ( empty ( $translations[$str] )
    ? ($blink ? '<blink>' . $str . '</blink>' : $str )
    : ( $decode ? unhtmlentities ( $translations[$str] ) : $translations[$str] ) );
}


/* Translates text and prints it.
 *
 * This is just an abbreviation for:
 *
 * <code>echo translate ( $str )</code>
 *
 * @param string $str    Text to translate and print
 * @param string $decode Do we want to envoke html_entity_decode
 * @uses translate
 */
function etranslate ( $str, $decode = '' ) {
  echo translate ( $str, $decode );
}

/* Translates and removes HTML from text, and returns it.
 *
 * This is useful for tooltips, which barf on HTML.
 *
 * <b>Note:</b>  {@link etooltip ()} prints the result
 * rather than return the value.
 *
 * @param string $str Text to translate
 * @return string The translated text with all HTML removed
 */
function tooltip( $str, $decode = '' ) {
  $ret = translate( $str, $decode );
  $ret = preg_replace( '/<[^>]+>/', '', $ret );
  return preg_replace( '/"/', "'", $ret );
}

/* Translates and removes HTML from text, and prints it.
 *
 * This is useful for tooltips, which barf on HTML.
 *
 * <b>Note:</b>  {@link tooltip ()} returns the result
 * rather than print the value.
 *
 * @param string $str Text to translate and print
 * @uses tooltip
 */
function etooltip ( $str, $decode = '' ) {
  echo tooltip ( $str, $decode );
}

/* Generate translated array of language names
 *
 * The first is the name presented to users while the second is the filename
 * (without the ".txt") that must exist in the translations subdirectory.
 * Only called from admin.php and pref.php.
 *
 * @uses translate
 */
function define_languages () {
  global $languages;

  $languages = array (
    translate ( 'English' ) => 'English-US', // translate ( 'English-US' )
    translate ( 'Afrikaans' ) => 'Afrikaans',
    translate ( 'Albanian' ) => 'Albanian',
    translate ( 'Arabic' ) . ' (UTF8)' => 'Arabic_utf8',
    translate ( 'Basque' ) => 'Basque',
    translate ( 'Bulgarian' ) => 'Bulgarian',
    translate ( 'Catalan' ) => 'Catalan',
    translate ( 'Chinese (Simplified/GB2312)' ) => 'Chinese-GB2312',
    translate ( 'Chinese (Traditional/Big5)' ) => 'Chinese-Big5',
    translate ( 'Croatian' ) . ' (UTF8)' => 'Croatian_utf8',
    translate ( 'Czech' ) => 'Czech',
    translate ( 'Czech' ) . ' (UTF8)'  => 'Czech',
    translate ( 'Danish' ) => 'Danish',
    translate ( 'Dutch' ) => 'Dutch',
    translate ( 'Dutch' ) . ' (UTF-8)' => 'Dutch_utf8',
    translate ( 'Elven' ) => 'Elven',
    translate ( 'Estonian' ) => 'Estonian',
    translate ( 'Finnish' ) => 'Finnish',
    translate ( 'French' ) . ' (UTF8)' => 'French-UTF8',
    translate ( 'French' ) => 'French',
    translate ( 'Galician' ) => 'Galician',
    translate ( 'German' ) => 'German',
    translate ( 'German' ) . ' (UTF-8)' => 'German_utf8',
    translate ( 'Greek' ) => 'Greek',
    translate ( 'Hebrew' ) . ' (UTF-8)' => 'Hebrew_utf8',
    translate ( 'Holo (Taiwanese)' ) => 'Holo-Big5',
    translate ( 'Hungarian' ) => 'Hungarian',
    translate ( 'Icelandic' ) => 'Icelandic',
    translate ( 'Indonesian' ) => 'Bahasa_Indonesia',
    translate ( 'Italian' ) => 'Italian',
    translate ( 'Japanese' ) . ' (EUC-JP)' => 'Japanese-eucjp',
    translate ( 'Japanese' ) . ' (SHIFT JIS)' => 'Japanese-sjis',
    translate ( 'Japanese' ) . ' (UTF-8)' => 'Japanese',
    translate ( 'Korean' ) => 'Korean',
    translate ( 'Lithuanian' ) => 'Lithuanian',
    translate ( 'Norwegian' ) => 'Norwegian',
    translate ( 'Polish' ) => 'Polish',
    translate ( 'Polish' ) . ' (UTF-8)'  => 'Polish-utf8',
    translate ( 'Portuguese' ) => 'Portuguese',
    translate ( 'Portuguese/Brazil' ) => 'Portuguese_BR',
    translate ( 'Portuguese/Brazil' ) . ' (UTF-8)' => 'Portuguese_BR_utf8',
    translate ( 'Romanian' ) => 'Romanian',
    translate ( 'Russian' ) . ' (UTF-8)' => 'Russian_utf8',
    translate ( 'Russian' ) => 'Russian',
    translate ( 'Serbian' ) . ' (UTF-8)' => 'Serbian_utf8',
    translate ( 'Slovak' ) . ' (UTF-8)' => 'Slovak_utf8',
    translate ( 'Slovenian' ) => 'Slovenian',
    translate ( 'Spanish' ) => 'Spanish',
    translate ( 'Swedish' ) => 'Swedish',
    translate ( 'Turkish' ) => 'Turkish',
    translate ( 'Welsh' ) => 'Welsh'
    // Add new languages here!
    );
    //Sort languages in translated order
    asort ( $languages );
    //make sure Browser Defined is first in list
    $browser_defined = array ( translate ( 'Browser-defined' ) => 'none');
    $languages = array_merge ( $browser_defined, $languages );
}

/* Converts language names to their abbreviation.
 *
 * @param string $name Name of the language (such as "French")
 *
 * @return string The abbreviation ("fr" for "French")
 */
function languageToAbbrev ( $name ) {
  global $browser_languages;

  foreach ( $browser_languages as $abbrev => $langname ) {
    if ( $langname == $name )
      return $abbrev;
  }
  return false;
}

/*
If the user sets "Browser-defined" as their language setting, then use the
$HTTP_ACCEPT_LANGUAGE settings to determine the language. The array below
maps browser language abbreviations into our available language files.
NOTE:  These should all be lowercase on the left side even though the proper
listing is like "en-US"!  Not sure what the abbreviation is?  Check out:
http://www.geocities.com/click2speak/languages.html
*/
$browser_languages = array (
  'af' => 'Afrikaans',
  'ar' => 'Arabic',
  'bg' => 'Bulgarian',
  'ca' => 'Catalan',
  'cs' => 'Czech',
  'cy' => 'Welsh',
  'da' => 'Danish',
  'de' => 'German',
  'de-at' => 'German', // German/Austria
  'de-ch' => 'German', // German/Switzerland
  'de-de' => 'German', // German/German
  // 'Elven' doesn't have a code abbreviation.
  'ee' => 'Estonian',
  'el' => 'Greek',
  'en' => 'English-US',
  'en-ca' => 'English-US',
  'en-gb' => 'English-US',
  'en-us' => 'English-US',
  'es' => 'Spanish',
  'eu' => 'Basque',
  'fi' => 'Finnish',
  'fr' => 'French',
  'fr-ca' => 'French', // French/Canada
  'fr-ch' => 'French', // French/Swiss
  'gl' => 'Galician',
  'he' => 'Hebrew',
  'hr' => 'Croatian',
  'hu' => 'Hungarian',
  'id' => 'Bahasa_Indonesia',
  'is' => 'Icelandic',
  'it' => 'Italian',
  'it-ch' => 'Italian', // Italian/Switzerland
  'ja' => 'Japanese',
  'ko' => 'Korean',
  'lt' => 'Lithuanian',
  'nl' => 'Dutch',
  'no' => 'Norwegian',
  'pl' => 'Polish',
  'pt' => 'Portuguese',
  'pt-br' => 'Portuguese_BR', // Portuguese/Brazil
  'ro' => 'Romanian',
  'ru' => 'Russian',
  'ru-ru' => 'Russian', // Safari reports this
  'sk' => 'Slovak',
  'sl' => 'Slovenian',
  'sq' => 'Albanian',
  'sr' => 'Serbian',
  'sv' => 'Swedish',
  'tr' => 'Turkish',
  'zh' => 'Chinese-GB2312', // Simplified Chinese
  'zh-cn' => 'Chinese-GB2312',
  'zh-min-nan-tw' => 'Holo-Big5',
  'zh-tw' => 'Chinese-Big5', // Traditional Chinese
  );

/*
General purpose translations that may be used elsewhere
as variables and not picked up by update_translation.pl

translate ( 'event' ) translate ( 'journal' )

Because not everyone uses these symbols for numbers:
translate ( '0' ) translate ( '1' ) translate ( '2' ) translate ( '3' )
translate ( '4' ) translate ( '5' ) translate ( '6' ) translate ( '7' )
translate ( '8' ) translate ( '9' )
*/

?>
