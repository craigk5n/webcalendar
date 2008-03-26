<?php
/* Language translation functions.
 *
 * The idea is very much stolen from the GNU translate C library.
 *
 * We load a translation file and store it in the global array $translations.
 * If a cache dir is enabled (in $settings[]), then we serialize $translations
 * and store it as a file in the cache dir.  The next call will unserialize the
 * cached file rather than re-parse the file.
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
    return html_entity_decode ( $string, ENT_QUOTES, 'UTF-8' );
  else { // For PHP < 4.3.
    // Replace numeric entities.
    $string = preg_replace ( '~&#x([0-9a-f]+);~ei', 'chr ( hexdec ( "\\1" ) )',
      $string );
    $string = preg_replace ( '~&#([0-9]+);~e', 'chr ( \\1 )', $string );
    // Replace literal entities.
    $trans_tbl = get_html_translation_table ( HTML_ENTITIES, ENT_QUOTES );
    $trans_tbl = array_flip ( $trans_tbl );
    return strtr ( $string, $trans_tbl );
  }
}

/* Unloads $translations so we can translate a different language.
 *
 * @param string $new_language New language file to load (just the base filename,
 *                             no directory or file suffix. Example:  "French")
 */
function reset_language ( $new_language ) {
  global $lang, $lang_file, $translation_loaded;

  if ( $new_language == 'none' || $new_language == 'Browser-defined')
    $new_language = get_browser_language ();

  if ( $new_language != $lang || ! $translation_loaded ) {
    $translation_loaded = false;
    $lang = $new_language;
    $lang_file = 'translations/' . $lang . '.txt';
  }
}

/* Loads all the language translation into an array for quick lookup.
 *
 * <b>Note:</b> There is no need to call this manually.
 * It will be invoked by {@link translate ()} the first time it is called.
 */
function load_translation_text () {
  global $lang_file, $translation_loaded;

  if ( $translation_loaded == true ) // No need to run this twice.
    return;

  $translations = array ();
  if ( defined ( '_WC_PUB_CACHE' ) ) {
    $cached_file = _WC_PUB_CACHE . '/' . $lang_file;
  } else {
    $cached_file = $use_cached = false;
  }

  if ( defined ( '_WC_BASE_DIR' ) ) {
    $base_dir = _WC_BASE_DIR;
    $lang_file_2 = _WC_BASE_DIR . "/$lang_file";
    if ( ! empty ( $cached_file ) )
      $cached_file = _WC_BASE_DIR . "/$cached_file";

    if ( file_exists ( $lang_file_2 ) )
      $lang_file = $lang_file_2;
  }
  if ( ! file_exists ( $lang_file ) )
    die_miserable_death ( 'Cannot find language file: ' . $lang_file );

  //  We will save the parsed translation file as a serialized array.
  $save_to_cache = $use_cached = false;

  if ( @function_exists ( 'file_get_contents' ) && $cached_file ) {

    if ( ! @file_exists ( $cached_file ) )
      $save_to_cache = true;
    else {
      if ( @filemtime ( $lang_file ) > @filemtime ( $cached_file ) )
        // Translation was updated. reload/reparse and save.
        $save_to_cache = true;
      else
        // Cache is more recent.
        $use_cached = true;
    }
  }
  if ( $use_cached && $cached_file )
    $translations = unserialize ( @file_get_contents ( $cached_file ) );
  // boy, that was easy ;-)
  else {
    $fp = @fopen ( $lang_file, 'r', false );
    if ( ! $fp )
      die_miserable_death ( 'Could not open language file: ' . $lang_file );

    $inInstallTrans = false;
    $isInstall = strstr ( $_SERVER['SCRIPT_NAME'], 'install/index.php' );
    while ( ! feof ( $fp ) ) {
      $buffer = trim ( fgets ( $fp, 4096 ) );

      if ( strlen ( $buffer ) == 0 )
        continue;
      // stripslashes may cause problems with Japanese translations.
      // If so, we may have to make this configurable.
      if ( get_magic_quotes_runtime () )
        $buffer = stripslashes ( $buffer );

      // Convert quotes to entities.
      $buffer = str_replace ( '"', '&quot;', $buffer );
      $buffer = str_replace ( "'", '&#39;', $buffer );
      // Skip installation translations unless we're running install/index.php
      if ( substr ( $buffer, 0, 7 ) == '# Page:' ) {
        $inInstallTrans = ( substr ( $buffer, 0, 15 ) == '# Page: install'
          || substr ( $buffer, 0, 12 ) == '# Page: step');
        continue;
      }
      if ( ( substr ( $buffer, 0, 1 ) == '#' || $inInstallTrans && !
            $isInstall ) )
        continue;
      $pos = strpos ( $buffer, ':' );
      $abbrev = trim ( substr ( $buffer, 0, $pos ) );

      $translations[$abbrev] = trim ( substr ( $buffer, $pos + 1 ) );
    }
    fclose ( $fp );

    if ( ! empty ( $cached_file ) && $save_to_cache ) {
      $fd = @fopen ( $cached_file, 'w+b', false );

      if ( ! empty ( $fd ) ) {
        fwrite ( $fd, serialize ( $translations ) );
        fclose ( $fd );
        chmod ( $cached_file, 0666 );
      } else
        // Could not write to cachedir.
        die_miserable_death ( 'Error writing translation cache file: '
           . $cached_file );
    }
  }
  return $translations;
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
    return ( $pref == false
      ? 'English-US' : translate ( 'Browser Language Not Found' ) );
  else {
    $langs = explode ( ',', $HTTP_ACCEPT_LANGUAGE );
    for ( $i = 0, $cnt = count ( $langs ); $i < $cnt; $i++ ) {
      $l = strtolower ( trim ( ereg_replace ( ';.*', '', $langs[$i] ) ) );
      $ret .= "\"$l\" ";
      if ( ! empty ( $browser_languages[$l] ) )
        return $browser_languages[$l];
    }
  }
  return ( strlen ( $HTTP_ACCEPT_LANGUAGE ) && $pref == true
    ? $HTTP_ACCEPT_LANGUAGE . ' ( ' . translate ( 'not supported' ) . ' )'
    : 'English-US' );
}

/* Translates a string from the default English usage to another language.
 *
 * The first time that this is called, the translation file will be loaded
 * (with {@link load_translation_text () }).
 *
 * @param string $str     Text to translate
 * @param string $options  True:envoke html_entity_decode
 *                       We currently only use this with javascript alerts.
 *                         L2: Pad left 2 depending on translated length
 *                         R2: Pad right 2
 *                         P2: Pad left 2 and right 2
 *
 * @return string The translated text, if available.  If no translation is
 *                avalailable, then the original untranslated text is returned.
 */
function translate ( $str, $options = '' ) {
  global $translation_loaded;

  static $translations;
  //Set $blink to true to aid in finding missing translations
  $blink = ( true & ( _WC_RUN_MODE == 'dev' ) );

  $decode = ( strpos ( $options, 'D' ) ? true : false );
  $tooltip = ( strpos ( $options, 'T' ) ? true : false );

  if ( ! $translation_loaded || empty ($translations ) ) {
    $translations = load_translation_text ();
    if ( ! empty ($translations ) )
      $translation_loaded = true;
  }

  $str = trim ( $str );
  $retval = ( ! empty ( $translations[$str] )
    ? ( $decode ? unhtmlentities ( $translations[$str] ) : $translations[$str] )
    : ( $blink ? '<blink>' . $str . '</blink>': $str ) );

  if ( $tooltip && ! $blink  ) {
    $retval = eregi_replace ( '<[^>]+>', '', $retval );
    $retval = eregi_replace ( '"', "'", $retval );
  }

  if ( $options ) {
    $opts = explode ( ',', $options );
    foreach ( $opts as $opt ) {
      $opt = trim ( $opt );
      if ( substr ( $opt,0,1 ) == 'L' ) {
        $retval = str_repeat( '&nbsp;', substr ( $opt,1 ) ) . $retval;
      } else if ( substr ( $opt,0,1 ) == 'R' ) {
        $retval .= str_repeat( '&nbsp;', substr ( $opt,1 ) );
      } else if ( substr ( $opt,0,1 ) == 'P' ) {
        $padstr = str_repeat( '&nbsp;',
          ( substr ( $opt,1 ) - (int) strlen ( $retval ) ) /2 );
        $retval = $padstr . $retval .$padstr;
      }
    }
  }

  return $retval;
}


function template_translate(&$tpl_source, &$smarty)
{
  preg_match_all("/__(.*)__/U", $tpl_source, $match_trans);
  $cnt = 0;
  foreach (  $match_trans[1] as $match ) {
    //? and / scroggs the preg_replace function
    $match = explode ( '@', $match );
    $pmatch = str_replace ( '?', '\?' , trim ( $match[0] ) );
    $pmatch = str_replace ( '/', '\/' , $pmatch );
    $pattern =  "/__" .$pmatch
      . ( isset ( $match[1] ) ? '@' . $match[1] : '' ). "__/U";
    $tpl_source = preg_replace( $pattern,
      translate ( $match[0], $match[1] ), $tpl_source);
    $cnt++;
  }
  return $tpl_source;

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
 *
 * @param string $str Text to translate
 * @return string The translated text with all HTML removed
 */
function tooltip ( $str, $decode = '') {
  $ret = translate ( $str, $decode );
  $ret = eregi_replace ( '<[^>]+>', '', $ret );
  return eregi_replace ( '"', "'", $ret );
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
    translate ( 'Danish' ) => 'Danish',
    translate ( 'Dutch' ) => 'Dutch',
    translate ( 'Elven' ) => 'Elven',
    translate ( 'Estonian' ) => 'Estonian',
    translate ( 'Finnish' ) => 'Finnish',
    translate ( 'French' ) . ' (UTF8)' => 'French-UTF8',
    translate ( 'French' ) => 'French',
    translate ( 'Galician' ) => 'Galician',
    translate ( 'German' ) => 'German',
    translate ( 'Greek' ) => 'Greek',
    translate ( 'Hebrew' ) . ' (UTF-8)' => 'Hebrew_utf8',
    translate ( 'Holo (Taiwanese)' ) => 'Holo-Big5',
    translate ( 'Hungarian' ) => 'Hungarian',
    translate ( 'Icelandic' ) => 'Icelandic',
    translate ( 'Italian' ) => 'Italian',
    translate ( 'Japanese' ) . ' (EUC-JP)' => 'Japanese-eucjp',
    translate ( 'Japanese' ) . ' (SHIFT JIS)' => 'Japanese-sjis',
    translate ( 'Japanese' ) . ' (UTF-8)' => 'Japanese',
    translate ( 'Korean' ) => 'Korean',
    translate ( 'Lithuanian' ) => 'Lithuanian',
    translate ( 'Norwegian' ) => 'Norwegian',
    translate ( 'Polish' ) => 'Polish',
    translate ( 'Portuguese' ) => 'Portuguese',
    translate ( 'Portuguese/Brazil' ) => 'Portuguese_BR',
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

  return $languages;
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
$HTTP_ACCEPT_LANGUAGE settings to determine the language.  The array below
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

Not everyone uses these symbols to represent numbers.
TODO:  Translate numbers in the program itself.
translate ('0') // zero
translate ('1') translate ('2') translate ('3') translate ('4') translate ('5')
translate ('6') translate ('7') translate ('8') translate ('9')

translate ( 'event' ) translate ( 'journal' )
*/

?>
