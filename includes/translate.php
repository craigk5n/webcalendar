<?php
/**
 * Language translation functions.
 *
 * The idea is very much stolen from the GNU translate C library.
 *
 * We load a translation file and store it in the global array $translations.
 * If a cache dir is enabled (in $settings[]), then we serialize $translations
 * and store it as a file in the cache dir. The next call will unserialize the
 * cached file rather than re-parse the file.
 *
 * Although there is a PHP gettext() function, I prefer to use this home-grown
 * translate function since it is simpler to work with.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://k5n.us/webcalendar
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 */

/**
 * Performs html_entity_decode style conversion for php < 4.3
 * Borrowed from http://us2.php.net/manual/en/function.html-entity-decode.php
 *
 * @param string $string Text to convert
 * @param bool   $ignore Ignore the charset when decoding
 *
 * #return string The converted text string
 */
function unhtmlentities ( $string ) {
  global $charset;

  // TODO: Not sure what to do here re: UTF-8 encoding.

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
/**
 * Read in a language file and cache it if we can.
 *
 * @param string $in_file   The name of the language file to read.
 * @param string $out_file  Name of the cache file.
 * @param bool   $strip     Do we want to call stripslashes?
 *                          It may cause problems with Japanese translations.
 */

function read_trans_file ( $in_file, $out_file = '', $strip = true ): array {
  global $can_save, $new_install, $translations;

  // Prevent directory traversal attack CWE-23
  $basename = basename($in_file);
  // Now let's see if the file really exists
  $path_to_folder = __DIR__ . '/../translations/';
  $files_in_folder = scandir($path_to_folder);
  if (!in_array($basename, $files_in_folder)) {
    die_miserable_death('Invalid Request');
  }

  // Open translations file
  $fp = fopen($path_to_folder . $basename, 'r');
  if ( ! $fp )
    die_miserable_death ( 'Could not open language file: ' . $path_to_folder . $basename );

  $translations = [];
  $inInstallTrans = false;
  $installationTranslations = [];

  while (!feof($fp)) {
    $line = fgets($fp);
    $line = trim($line);

    if (empty($line)) {
      continue;
    }

    if ($strip) {
      $line = stripslashes($line);
    }

    // Convert quotes to entities.
    $line = str_replace ( ['"', "'"], ['&quot;', '&#39;'], $line );

    // Skip comments
    if (substr($line, 0, 1) === '#') {
      // Check if it's a # Page: comment
     if (substr($line, 0, 7) === '# Page:') {
        // Set $inInstallTrans based on whether it's install page
        $inInstallTrans = stristr(substr($line, 8), 'install') !== false;
      }
      continue;
    }

    // Skip installation translations unless we're running install/index.php.
    if ( $inInstallTrans && ! $new_install )
      continue;

    $pos = strpos ( $line, ':' );
    $abbrev = trim ( substr ( $line, 0, $pos ) );
    $temp = trim ( substr ( $line, $pos + 1 ) );

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
  return $translations;
}

/**
 * Unloads $translations so we can translate a different language.
 *
 * @param string $new_language New language file to load (just the base filename,
 *                             no directory or file suffix. Example: "French")
 */
function reset_language ( $new_language ) {
  global $fullname, $lang, $lang_file,
  $PUBLIC_ACCESS_FULLNAME, $translation_loaded, $translations;

  if ( $new_language == 'none' || $new_language == 'Browser-defined' )
    $new_language = get_browser_language();

  if ( $new_language != $lang || ! $translation_loaded ) {
    $lang = $new_language;
    $lang_file = 'translations/' . $lang . '.txt';
    $translation_loaded = false;
    load_translation_text();
  }
  $PUBLIC_ACCESS_FULLNAME = translate ( 'Public Access' );
  if ( $fullname == 'Public Access' )
    $fullname = $PUBLIC_ACCESS_FULLNAME;
}

/**
 * Loads all the language translation into an array for quick lookup.
 *
 * <b>Note:</b> There is no need to call this manually.
 * It will be invoked by {@link translate() } the first time it is called.
 */
function load_translation_text() {
  global $lang_file, $settings, $translation_loaded, $translations;

  if ( $translation_loaded ) // No need to run this twice.
    return;

  if ( empty ( $lang_file ) )
    $lang_file = "translations/English-US.txt";

  $lang_cache = substr ( $lang_file, strrpos ( $lang_file, '/' ) + 1 );
  $cached_base_file =
  $cached_file =
  $cachedir =
  $lang_file_2 = '';

  if ( defined ( '__WC_BASEDIR' ) ) {
    if ( ! file_exists ( $lang_file ) )
      $lang_file_2 = __WC_BASEDIR . $lang_file;

    if ( file_exists ( $lang_file_2 ) )
      $lang_file = $lang_file_2;

    if ( ! file_exists ( $lang_file ) )
      $lang_file = 'translations/' . $lang_cache;
  }
  if ( ! file_exists ( $lang_file ) )
    die_miserable_death ( 'Cannot find language file: ' . $lang_file );

  $can_save = false;

  $eng_file = 'translations/English-US.txt';
  if ( ! file_exists ( $eng_file ) )
    $eng_file = __WC_BASEDIR . $eng_file;

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
    }

    $can_save = ( is_writable ( $cache_tran_dir ) );
  }

  $new_install = ( ! strstr ( $_SERVER['SCRIPT_NAME'], 'install/index.php' ) );
  $translations = [];

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

/**
 * Gets browser-specified language preference.
 *
 * param bool $pref true is we want to simply display value
 *                  without affecting translations.
 *
 * @return string Preferred language
 * @ignore
 */
function get_browser_language ( $pref = false ) {
  global $browser_languages, $HTTP_ACCEPT_LANGUAGE;

  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) &&
      isset ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) )
    $HTTP_ACCEPT_LANGUAGE = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) )
    // If this is true, can we translate without knowing which language?
    // return ( $pref ? translate ( 'Browser Language Not Found' ) : 'English-US' );
    return ( $pref ? 'Browser Language Not Found' : 'English-US' );
  else {
    $langs = explode ( ',', $HTTP_ACCEPT_LANGUAGE );
    for ( $i = 0, $cnt = count ( $langs ); $i < $cnt; $i++ ) {
      $l = strtolower( trim( preg_replace( '/;.*/', '', $langs[$i] ) ) );

      if ( ! empty( $browser_languages[$l] ) )
        return $browser_languages[$l];
    }
  }
  return ( strlen ( $HTTP_ACCEPT_LANGUAGE ) && $pref == true
    ? $HTTP_ACCEPT_LANGUAGE . ' ' . translate ( '(not supported)' )
    : 'English-US' );
}

function translation_exists ( $str )
{
  global $translation_loaded, $translations;
  if ( ! $translation_loaded )
    return false;
  return ( empty ( $translations[$str] ) ? false : true );
}

/**
 * Translates a string from the default English usage to another language.
 *
 * The first time that this is called, the translation file will be loaded
 * (with {@link load_translation_text() }).
 *
 * @param string   $str     Text to translate
 * @param string   $decode  Do we want to invoke html_entity_decode?
 *                          We currently only use this with javascript alerts.
 * @param string   $type    ('' = alphabetic, A = alphanumeric,
 *                          D = date, N = numeric)
 *
 * @return string The translated text, if available. If no translation is
 *                available, then the original untranslated text is returned.
 */
function translate ( $str, $decode = '', $type = '' ) {
  global $LANGUAGE, $translation_loaded, $translations;
  if (empty($LANGUAGE))
    return $str;
  if ( ! $translation_loaded )
    load_translation_text();

  if ( $type == '' || $type == 'A' ) {
    // Translate these because even English may be abbreviated.
    $str = trim ( $str );

    if ( empty ( $str ) )
      return false;

    if ( ! empty ( $translations[$str] ) )
      // $public_access, and maybe other things,
      // getting translated more than once which is not supposed to happen.
      $str = ( $decode
        ? unhtmlentities ( $translations[$str] ) : $translations[$str] );
  }
  if ( strpos ( strtolower ( $LANGUAGE ), 'english' ) === false ) {
    // Only translate if not English.
    if ( $type == 'D' ) {
      for ( $i = 0; $i < 12; $i++ ) {
        // Translate month names. Full then abbreviation.
        $f = mktime ( 0, 0, 0, $i + 1 );
        $tmp = date ( 'F', $f );
        if ( $tmp != $translations[$tmp] )
          $str = str_replace ( $tmp, $translations[$tmp], $str );

        $tmp = date ( 'M', $f );
        if ( $tmp != $translations[$tmp] )
          $str = str_replace ( $tmp, $translations[$tmp], $str );

        if ( $i < 7 ) {
          // Might as well translate day names while we're here.
          $f = mktime ( 0, 0, 0, 1, $i + 1 );
          $tmp = date ( 'l', $f );
          if ( $tmp != $translations[$tmp] )
            $str = str_replace ( $tmp, $translations[$tmp], $str );

          $tmp = date ( 'D', $f );
          if ( $tmp != $translations[$tmp] )
            $str = str_replace ( $tmp, $translations[$tmp], $str );
        }
      }
    }
    if ( $type != '' ) {
      // Translate number symbols.
      for ( $i = 0; $i < 10; $i++ ) {
        $tmp = (string) $i;
        if (! empty($translations[$tmp]) && $tmp != $translations[$tmp])
          $str = str_replace ( $tmp, $translations[$tmp], $str );
      }
    }
  }

  return $str;
}

/**
 * Translates text and prints it.
 *
 * This is just an abbreviation for:
 *
 * <code>echo translate ( $str )</code>
 *
 * @param string   $str     Text to translate and print
 * @param string   $decode  Do we want to invoke html_entity_decode
 * @param string   $type    (A = alphabetic, D = date, N = numeric)
 *
 * @uses translate
 */
function etranslate ( $str, $decode = '', $type = 'A' ) {
  echo translate ( $str, $decode, $type );
}

/**
 * Translates and removes HTML from text, and returns it.
 *
 * This is useful for tooltips, which barf on HTML.
 *
 * <b>Note:</b>  {@link etooltip()} prints the result
 * rather than return the value.
 *
 * @param string $str Text to translate
 * @return string The translated text with all HTML removed (unless allowHtml
 * is set to true)
 */
function tooltip( $str, $decode = '', $allowHtml=false ) {
  $ret = translate( $str, $decode );
  if(!$allowHtml) {
    $ret = preg_replace( '/<[^>]+>/', '', $ret );
  }
  return htmlspecialchars($ret);
}

/**
 * Translates and removes HTML from text, and prints it.
 *
 * This is useful for tooltips, which barf on HTML.
 *
 * <b>Note:</b> {@link tooltip()} returns the result
 * rather than print the value.
 *
 * @param string $str Text to translate and print
 * @uses tooltip
 */
function etooltip ( $str, $decode = '', $allowHtml=false ) {
  echo tooltip ( $str, $decode, $allowHtml );
}

/**
 * Generate translated array of language names
 *
 * The first is the name presented to users while the second is the filename
 * (without the ".txt") that must exist in the translations subdirectory.
 * Only called from admin.php and pref.php.
 *
 * @uses translate
 */
function define_languages() {
  global $languages;

  $languages = [
    translate ( 'English' ) => 'English-US', // translate ( 'English-US' )
    translate ( 'Afrikaans' ) => 'Afrikaans',
    translate ( 'Albanian' ) => 'Albanian',
    translate ( 'Amharic' ) => 'Amharic',
    translate ( 'Arabic' ) => 'Arabic',
    translate ( 'Azerbaijani' ) => 'Azerbaijani',
    translate ( 'Basque' ) => 'Basque',
    translate ( 'Belarusian' ) => 'Belarusian',
    translate ( 'Bengali' ) => 'Bengali',
    translate ( 'Bosnian' ) => 'Bosnian',
    translate ( 'Bulgarian' ) => 'Bulgarian',
    translate ( 'Burmese' ) => 'Burmese',
    translate ( 'Catalan' ) => 'Catalan',
    translate ( 'Cebuano' ) => 'Cebuano',
    translate ( 'Chichewa' ) => 'Chichewa',
    translate ( 'Chinese (Simplified/GB2312)' ) => 'Chinese-Simplified',
    translate ( 'Chinese (Traditional/Big5)' ) => 'Chinese-Traditional',
    translate ( 'Corsican' ) => 'Corsican',
    translate ( 'Croatian' ) => 'Croatian',
    translate ( 'Czech' ) => 'Czech',
    translate ( 'Danish' ) => 'Danish',
    translate ( 'Dutch' ) => 'Dutch',
    translate ( 'Esperanto' ) => 'Esperanto',
    translate ( 'Estonian' ) => 'Estonian',
    translate ( 'Filipino' ) => 'Filipino',
    translate ( 'Finnish' ) => 'Finnish',
    translate ( 'French' ) => 'French',
    translate ( 'Frisian' ) => 'Frisian',
    translate ( 'Galician' ) => 'Galician',
    translate ( 'Georgian' ) => 'Georgian',
    translate ( 'German' ) => 'German',
    translate ( 'Greek' ) => 'Greek',
    translate ( 'Gujarati' ) => 'Gujarati',
    translate ( 'Haitian' ) => 'Haitian',
    translate ( 'Hausa' ) => 'Hausa',
    translate ( 'Hawaiian' ) => 'Hawaiian',
    translate ( 'Hebrew' ) => 'Hebrew',
    translate ( 'Hindi' ) => 'Hindi',
    translate ( 'Hmong' ) => 'Hmong',
    // translate ( 'Holo (Taiwanese)' ) => 'Holo-Big5',
    translate ( 'Hungarian' ) => 'Hungarian',
    translate ( 'Icelandic' ) => 'Icelandic',
    translate ( 'Igbo' ) => 'Igbo',
    translate ( 'Indonesian' ) => 'Indonesian',
    translate ( 'Irish' ) => 'Irish',
    translate ( 'Italian' ) => 'Italian',
    // translate ( 'Japanese' ) . ' (EUC-JP)' => 'Japanese-eucjp',
    // translate ( 'Japanese' ) . ' (SHIFT JIS)' => 'Japanese-sjis',
    translate ( 'Japanese' ) => 'Japanese',
    translate ( 'Javanese' ) => 'Javanese',
    translate ( 'Kannada' ) => 'Kannada',
    translate ( 'Kazakh' ) => 'Kazakh',
    translate ( 'Khmer' ) => 'Khmer',
    translate ( 'Kinyarwanda' ) => 'Kinyarwanda',
    translate ( 'Kiswahili' ) => 'Kiswahili',
    translate ( 'Korean' ) => 'Korean',
    translate ( 'Kurdish' ) => 'Kurdish',
    translate ( 'Kyrgyz' ) => 'Kyrgyz',
    translate ( 'Lao' ) => 'Lao',
    translate ( 'Latin' ) => 'Latin',
    translate ( 'Latvian' ) => 'Latvian',
    translate ( 'Lithuanian' ) => 'Lithuanian',
    translate ( 'Luxembourgish' ) => 'Luxembourgish',
    translate ( 'Macedonian' ) => 'Macedonian',
    translate ( 'Malagasy' ) => 'Malagasy',
    translate ( 'Malay' ) => 'Malay',
    translate ( 'Malayalam' ) => 'Malayalam',
    translate ( 'Maltese' ) => 'Maltese',
    translate ( 'Maori' ) => 'Maori',
    translate ( 'Marathi' ) => 'Marathi',
    translate ( 'Mongolian' ) => 'Mongolian',
    translate ( 'Nepali' ) => 'Nepali',
    translate ( 'Norwegian' ) => 'Norwegian',
    translate ( 'Odia' ) => 'Odia',
    translate ( 'Pashto' ) => 'Pashto',
    translate ( 'Persian' ) => 'Persian',
    translate ( 'Polish' ) => 'Polish',
    translate ( 'Portuguese' ) => 'Portuguese',
    translate ( 'Portuguese/Brazil' ) => 'Portuguese_BR',
    translate ( 'Punjabi' ) => 'Punjabi',
    translate ( 'Romanian' ) => 'Romanian',
    translate ( 'Russian' ) => 'Russian',
    translate ( 'Samoan' ) => 'Samoan',
    translate ( 'Scots' ) => 'Scots',
    translate ( 'Serbian' ) => 'Serbian',
    translate ( 'Sesotho' ) => 'Sesotho',
    translate ( 'Shona' ) => 'Shona',
    translate ( 'Sindhi' ) => 'Sindhi',
    translate ( 'Sinhala' ) => 'Sinhala',
    translate ( 'Slovak' ) => 'Slovak',
    translate ( 'Slovenian' ) => 'Slovenian',
    translate ( 'Somali' ) => 'somali',
    translate ( 'Spanish' ) => 'Spanish',
    translate ( 'Sundanese' ) => 'Sundanese',
    translate ( 'Swedish' ) => 'Swedish',
    translate ( 'Tajik' ) => 'Tajik',
    translate ( 'Tamil' ) => 'Tamil',
    translate ( 'Tatar' ) => 'Tatar',
    translate ( 'Telugu' ) => 'Telugu',
    translate ( 'Thai' ) => 'Thai',
    translate ( 'Turkish' ) => 'Turkish',
    translate ( 'Turkmen' ) => 'Turkmen',
    translate ( 'Ukrainian' ) => 'Ukrainian',
    translate ( 'Urdu' ) => 'Urdu',
    translate ( 'Uyghur' ) => 'Uyghur',
    translate ( 'Uzbek' ) => 'Uzbek',
    translate ( 'Vietnamese' ) => 'Vietnamese',
    translate ( 'Welsh' ) => 'Welsh',
    translate ( 'Xhosa' ) => 'Xhosa',
    translate ( 'Yiddish' ) => 'Yiddish',
    translate ( 'Yoruba' ) => 'Yoruba',
    translate ( 'Zulu' ) => 'Zulu',
    // Add new languages here!
  ];
  //Sort languages in translated order
  asort ( $languages );
  //make sure Browser Defined is first in list
  $browser_defined = [translate ( 'Browser-defined' ) => 'none'];
  $languages = array_merge ( $browser_defined, $languages );
}

/**
 * Converts language names to their abbreviation.
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

/**
 * If the user sets "Browser-defined" as their language setting, then use the
 * $HTTP_ACCEPT_LANGUAGE settings to determine the language. The array below
 * maps browser language abbreviations into our available language files.
 *
 * Not sure what the 2-letter abbreviation is? Check out:
 * https://w3schools.com/tags/ref_language_codes.asp
 *
 * List of all locale codes (??-??) with country and language details,
 * including ISO 3166 and ISO 639 codes, timezones, capital, currency, and more.
 * https://cdn.simplelocalize.io
 */

 /* These are code => language file // (country) */
 $browser_languages = [
  // 'aa-ER' => 'Afar', //                (Nakfa)
  // 'aa'    => 'Afar',
  'af-NA' => 'Afrikaans', //           (Namibia)
  'af-ZA' => 'Afrikaans', //           (South Africa)
  'af'    => 'Afrikaans',
  'am-ET' => 'Amharic', //             (Ethiopia)
  'am'    => 'Amharic',
  'ar-AE' => 'Arabic', //              (United Arab Emirates)
  'ar-BH' => 'Arabic', //              (Bahrain)
  'ar-DJ' => 'Arabic', //              (Djibouti)
  'ar-DZ' => 'Arabic', //              (Algeria)
  'ar-EG' => 'Arabic', //              (Egypt)
  'ar-ER' => 'Arabic', //              (Eritrea)
  'ar-IL' => 'Arabic', //              (Israel)
  'ar-IQ' => 'Arabic', //              (Iraq)
  'ar-JO' => 'Arabic', //              (Jordan)
  'ar-KM' => 'Arabic', //              (Comoros)
  'ar-KW' => 'Arabic', //              (Kuwait)
  'ar-LB' => 'Arabic', //              (Lebanon)
  'ar-LY' => 'Arabic', //              (Libya)
  'ar-MA' => 'Arabic', //              (Morocco)
  'ar-MR' => 'Arabic', //              (Mauritania)
  'ar-OM' => 'Arabic', //              (Oman)
  'ar-PS' => 'Arabic', //              (Palestine)
  'ar-QA' => 'Arabic', //              (Qatar)
  'ar-SA' => 'Arabic', //              (Saudi Arabia)
  'ar-SD' => 'Arabic', //              (Sudan)
  'ar-SO' => 'Arabic', //              (Somalia)
  'ar-SY' => 'Arabic', //              (Syria)
  'ar-TD' => 'Arabic', //              (Chad)
  'ar-TN' => 'Arabic', //              (Tunisia)
  'ar-YE' => 'Arabic', //              (Yemen)
  'ar'    => 'Arabic',
  // 'ay-BO' => 'Aymara', //              (Bolivia)
  // 'ay'    => 'Aymara',
  'az-AZ' => 'Azerbaijani', //         (Azerbaijan)
  'az'    => 'Azerbaijani',
  'be-BY' => 'Belarusian', //          (Belarus)
  'be'    => 'Belarusian',
  'bg-BG' => 'Bulgarian', //           (Bulgaria)
  'bg'    => 'Bulgarian',
  // 'bi-VU' => 'Bislama', //             (Vanuatu)
  // 'bi'    => 'Bislama',
  'bn-BD' => 'Bengali', //             (Bangladesh)
  'bn'    => 'Bengali',
  'bs-BA' => 'Bosnian', //             (Bosnia and Herzegovina)
  'bs-ME' => 'Bosnian', //             (Montenegro)
  'bs'    => 'Bosnian',
  // 'byn-ER' => 'Bilen', //              (Eritrea)
  // 'byn'   => 'Bilen',
  'ca-AD' => 'Catalan', //             (Andorra)
  'ca'    => 'Catalan',
  'ceb'   => 'Cebuano',
  // 'ch-GU' => 'Chamorro', //            (Guam)
  // 'ch-MP' => 'Chamorro', //            (Northern Mariana Islands)
  // 'ch'    => 'Chamorro',
  'co'    => 'Corsican',
  'cs-CZ' => 'Czech', //               (Czechia)
  'cs'    => 'Czech',
  'cy'    => 'Welsh',
  'da-DK' => 'Danish', //              (Denmark)
  'da'    => 'Danish',
  'de-AT' => 'German', //              (Austria)
  'de-BE' => 'German', //              (Belgium)
  'de-CH' => 'German', //              (Switzerland)
  'de-DE' => 'German', //              (Germany)
  'de-LI' => 'German', //              (Liechtenstein)
  'de-LU' => 'German', //              (Luxembourg)
  'de-VA' => 'German', //              (Vatican City)
  'de'    => 'German',
  // 'dv-MV' => 'Divehi', //              (Maldives)
  // 'dv'    => 'Divehi',
  // 'dz-BT' => 'Dzongkha', //            (Bhutan)
  // 'dz'    => 'Dzongkha',
  'ee'    => 'Estonian',
  'el-CY' => 'Greek', //               (Cyprus)
  'el-GR' => 'Greek', //               (Greece)
  'el'    => 'Greek',
  'en-AG' => 'English-US', //          (Antigua and Barbuda)
  'en-AI' => 'English-US', //          (Anguilla)
  'en-AQ' => 'English-US', //          (Antarctica)
  'en-AS' => 'English-US', //          (American Samoa)
  'en-AU' => 'English-US', //          (Australia)
  'en-BB' => 'English-US', //          (Barbados)
  'en-BM' => 'English-US', //          (Bermuda)
  'en-BS' => 'English-US', //          (Bahamas)
  'en-BW' => 'English-US', //          (Botswana)
  'en-BZ' => 'English-US', //          (Belize)
  'en-CA' => 'English-US', //          (Canada)
  'en-CC' => 'English-US', //          (Cocos 'Keeling' Islands)
  'en-CK' => 'English-US', //          (Cook Islands)
  'en-CM' => 'English-US', //          (Cameroon)
  'en-CW' => 'English-US', //          (Curacao)
  'en-CX' => 'English-US', //          (Christmas Island)
  'en-DM' => 'English-US', //          (Dominica)
  'en-ER' => 'English-US', //          (Eritrea)
  'en-FJ' => 'English-US', //          (Fiji)
  'en-FK' => 'English-US', //          (Falkland Islands)
  'en-FM' => 'English-US', //          (Federated States of Micronesia)
  'en-GB' => 'English-US', //          (United Kingdom)
  'en-GD' => 'English-US', //          (Grenada)
  'en-GG' => 'English-US', //          (Guernsey)
  'en-GH' => 'English-US', //          (Ghana)
  'en-GI' => 'English-US', //          (Gibraltar)
  'en-GM' => 'English-US', //          (Gambia)
  'en-GS' => 'English-US', //          (South Georgia and South Sandwich Islands)
  'en-GU' => 'English-US', //          (Guam)
  'en-GY' => 'English-US', //          (Guyana)
  'en-HK' => 'English-US', //          (Hong Kong)
  'en-HM' => 'English-US', //          (Heard Island and McDonald Islands)
  'en-IE' => 'English-US', //          (Ireland)
  'en-IM' => 'English-US', //          (Isle of Man)
  'en-IN' => 'English-US', //          (India)
  'en-IO' => 'English-US', //          (British Indian Ocean Territory)
  'en-JE' => 'English-US', //          (Jersey)
  'en-JM' => 'English-US', //          (Jamaica)
  'en-KE' => 'English-US', //          (Kenya)
  'en-KI' => 'English-US', //          (Kiribati)
  'en-KN' => 'English-US', //          (Saint Kitts and Nevis)
  'en-KY' => 'English-US', //          (Cayman Islands)
  'en-LC' => 'English-US', //          (Saint Lucia)
  'en-LR' => 'English-US', //          (Liberia)
  'en-LS' => 'English-US', //          (Lesotho)
  'en-MF' => 'English-US', //          (Saint Martin)
  'en-MH' => 'English-US', //          (Marshall Islands)
  'en-MP' => 'English-US', //          (Northern Mariana Islands)
  'en-MS' => 'English-US', //          (Montserrat)
  'en-MT' => 'English-US', //          (Malta)
  'en-MU' => 'English-US', //          (Mauritius)
  'en-MW' => 'English-US', //          (Malawi)
  'en-NA' => 'English-US', //          (Namibia)
  'en-NF' => 'English-US', //          (Norfolk Island)
  'en-NG' => 'English-US', //          (Nigeria)
  'en-NR' => 'English-US', //          (Nauru)
  'en-NU' => 'English-US', //          (Niue)
  'en-NZ' => 'English-US', //          (New Zealand)
  'en-PG' => 'English-US', //          (Papua New Guinea)
  'en-PH' => 'English-US', //          (Philippines)
  'en-PK' => 'English-US', //          (Pakistan)
  'en-PN' => 'English-US', //          (Pitcairn Islands)
  'en-PR' => 'English-US', //          (Puerto Rico)
  'en-PW' => 'English-US', //          (Palau)
  'en-RW' => 'English-US', //          (Rwanda)
  'en-SB' => 'English-US', //          (Solomon Islands)
  'en-SC' => 'English-US', //          (Seychelles)
  'en-SD' => 'English-US', //          (Sudan)
  'en-SG' => 'English-US', //          (Singapore)
  'en-SH' => 'English-US', //          (Saint Helena, Ascension and Tristan da Cunha)
  'en-SL' => 'English-US', //          (Sierra Leone)
  'en-SS' => 'English-US', //          (South Sudan)
  'en-SX' => 'English-US', //          (Sint Maarten)
  'en-SZ' => 'English-US', //          (Eswatini)
  'en-TC' => 'English-US', //          (Turks and Caicos Islands)
  'en-TK' => 'English-US', //          (Tokelau)
  'en-TO' => 'English-US', //          (Tonga)
  'en-TT' => 'English-US', //          (Trinidad and Tobago)
  'en-TV' => 'English-US', //          (Tuvalu)
  'en-TZ' => 'English-US', //          (Tanzania)
  'en-UG' => 'English-US', //          (Uganda)
  'en-UM' => 'English-US', //          (United States Minor Outlying Islands)
  'en-US' => 'English-US', //          (United States of America)
  'en-VC' => 'English-US', //          (Saint Vincent and the Grenadines)
  'en-VG' => 'English-US', //          (British Virgin Islands)
  'en-VI' => 'English-US', //          (Virgin Islands)
  'en-VU' => 'English-US', //          (Vanuatu)
  'en-WS' => 'English-US', //          (Samoa)
  'en-ZA' => 'English-US', //          (South Africa)
  'en-ZM' => 'English-US', //          (Zambia)
  'en-ZW' => 'English-US', //          (Zimbabwe)
  'en'    => 'English-US',
  'eo'    => 'Esperanto',
  'es-AR' => 'Spanish', //             (Argentina)
  'es-BO' => 'Spanish', //             (Bolivia)
  'es-BZ' => 'Spanish', //             (Belize)
  'es-CL' => 'Spanish', //             (Chile)
  'es-CO' => 'Spanish', //             (Colombia)
  'es-CR' => 'Spanish', //             (Costa Rica)
  'es-CU' => 'Spanish', //             (Cuba)
  'es-DO' => 'Spanish', //             (Dominican Republic)
  'es-EC' => 'Spanish', //             (Ecuador)
  'es-EH' => 'Spanish', //             (Western Sahara)
  'es-ES' => 'Spanish', //             (Spain)
  'es-GQ' => 'Spanish', //             (Equatorial Guinea)
  'es-GT' => 'Spanish', //             (Guatemala)
  'es-GU' => 'Spanish', //             (Guam)
  'es-HN' => 'Spanish', //             (Honduras)
  'es-MX' => 'Spanish', //             (Mexico)
  'es-NI' => 'Spanish', //             (Nicaragua)
  'es-PA' => 'Spanish', //             (Panama)
  'es-PE' => 'Spanish', //             (Peru)
  'es-PR' => 'Spanish', //             (Puerto Rico)
  'es-PY' => 'Spanish', //             (Paraguay)
  'es-SV' => 'Spanish', //             (El Salvador)
  'es-UY' => 'Spanish', //             (Uruguay)
  'es-VE' => 'Spanish', //             (Venezuela)
  'es'    => 'Spanish',
  'et-EE' => 'Estonian', //            (Estonia)
  'et'    => 'Estonian',
  'eu'    => 'Basque',
  'fa-IR' => 'Persian', //             (Iran)
  'fa'    => 'Persian',
  // 'fan-GQ' => 'Fang', //               (Equatorial Guinea)
  // 'fan'   => 'Fang',
  // 'ff-BF' => 'Fula', //                (Burkina Faso)
  // 'ff-GN' => 'Fula', //                (Guinea)
  // 'ff'    => 'Fula',
  'fi-FI' => 'Finnish', //             (Finland)
  'fi'    => 'Finnish',
  'fil'   => 'Filipino',
  'fj-FJ' => 'Fijian', //              (Fiji)
  'fj'    => 'Fijian',
  // 'fo-FO' => 'Faroese', //             (Faroe Islands)
  // 'fo'    => 'Faroese',
  'fr-BE' => 'French', //              (Belgium)
  'fr-BF' => 'French', //              (Burkina Faso)
  'fr-BI' => 'French', //              (Burundi)
  'fr-BJ' => 'French', //              (Benin)
  'fr-BL' => 'French', //              (Saint Barthelemy)
  'fr-CA' => 'French', //              (Canada)
  'fr-CD' => 'French', //              (Democratic Republic of the Congo)
  'fr-CF' => 'French', //              (Central African Republic)
  'fr-CG' => 'French', //              (Republic of the Congo)
  'fr-CH' => 'French', //              (Switzerland)
  'fr-CI' => 'French', //              (Ivory Coast)
  'fr-CM' => 'French', //              (Cameroon)
  'fr-DJ' => 'French', //              (Djibouti)
  'fr-FR' => 'French', //              (France)
  'fr-GA' => 'French', //              (Gabon)
  'fr-GF' => 'French', //              (French Guiana)
  'fr-GG' => 'French', //              (Guernsey)
  'fr-GN' => 'French', //              (Guinea)
  'fr-GP' => 'French', //              (Guadeloupe)
  'fr-GQ' => 'French', //              (Equatorial Guinea)
  'fr-HT' => 'French', //              (Haiti)
  'fr-JE' => 'French', //              (Jersey)
  'fr-KM' => 'French', //              (Comoros)
  'fr-LB' => 'French', //              (Lebanon)
  'fr-LU' => 'French', //              (Luxembourg)
  'fr-MC' => 'French', //              (Principality of Monaco)
  'fr-MF' => 'French', //              (Saint Martin)
  'fr-MG' => 'French', //              (Madagascar)
  'fr-ML' => 'French', //              (Mali)
  'fr-MQ' => 'French', //              (Martinique)
  'fr-NC' => 'French', //              (New Caledonia)
  'fr-NE' => 'French', //              (Niger)
  'fr-PF' => 'French', //              (French Polynesia)
  'fr-PM' => 'French', //              (Saint Pierre and Miquelon)
  'fr-RE' => 'French', //              (Reunion)
  'fr-RW' => 'French', //              (Rwanda)
  'fr-SC' => 'French', //              (Seychelles)
  'fr-SN' => 'French', //              (Senegal)
  'fr-TD' => 'French', //              (Chad)
  'fr-TF' => 'French', //              (French Southern and Antarctic Lands)
  'fr-TG' => 'French', //              (Togo)
  'fr-VA' => 'French', //              (Vatican City)
  'fr-VU' => 'French', //              (Vanuatu)
  'fr-WF' => 'French', //              (Wallis and Futuna)
  'fr-YT' => 'French', //              (Mayotte)
  'fr'    => 'French',
  'fy'    => 'Frisian',
  'ga-IE' => 'Irish', //               (Ireland)
  'ga'    => 'Irish',
  'gd'    => 'Scots',
  'gl'    => 'Galician',
  // 'gn-AR' => 'Guaraní', //             (Argentina)
  // 'gn-PY' => 'Guaraní', //             (Paraguay)
  // 'gn'    => 'Guaraní',
  'gu'    => 'Gujarati',
  // 'gv-IM' => 'Manx', //                (Isle of Man)
  // 'gv'    => 'Manx',
  'ha'    => 'Hausa',
  'haw'   => 'Hawaiian',
  'he-IL' => 'Hebrew', //              (Israel)
  'he'    => 'Hebrew',
  'hi-IN' => 'Hindi', //               (India)
  'hi'    => 'Hindi',
  'hif-FJ' => 'Hindi', //              (Fiji)
  'hif'   => 'Hindi',
  'hmn'   => 'Hmong',
  'hr-BA' => 'Croatian', //            (Bosnia and Herzegovina)
  'hr-HR' => 'Croatian', //            (Croatia)
  'hr-ME' => 'Croatian', //            (Montenegro)
  'hr'    => 'Croatian',
  'ht-HT' => 'Haitian', //             (Haiti)
  'ht'    => 'Haitian',
  'hu-HU' => 'Hungarian', //           (Hungary)
  'hu'    => 'Hungarian',
  // 'hy-AM' => 'Armenian', //            (Armenia)
  // 'hy-CY' => 'Armenian', //            (Cyprus)
  // 'hy'    => 'Armenian',
  'id-ID' => 'Indonesian', //          (Indonesia)
  'id'    => 'Indonesia',
  'id'    => 'Indonesian',
  'ig'    => 'Igbo',
  'is-IS' => 'Icelandic', //           (Iceland)
  'is'    => 'Icelandic',
  'it-CH' => 'Italian', //             (Switzerland)
  'it-IT' => 'Italian', //             (Italy)
  'it-SM' => 'Italian', //             (San Marino)
  'it-VA' => 'Italian', //             (Vatican City)
  'it'    => 'Italian',
  'ja-JP' => 'Japanese', //            (Japan)
  'ja'    => 'Japanese',
  'ji'    => 'Yiddish',
  'jv'    => 'Javanese',
  'ka-GE' => 'Georgian', //            (Georgia)
  'ka'    => 'Georgian',
  // 'kg-CD' => 'Kongo', //               (Democratic Republic of the Congo)
  // 'kg'    => 'Kongo',
  'kk-KZ' => 'Kazakh', //              (Kazakhstan)
  'kk'    => 'Kazakh',
  // 'kl-GL' => 'Greenlandic', //         (Greenland)
  // 'kl'    => 'Greenlandic',
  'km-KH' => 'Khmer', //               (Cambodia)
  'km'    => 'Khmer',
  'kn'    => 'Kannada',
  'ko-KP' => 'Korean', //              (North Korea)
  'ko-KR' => 'Korean', //              (South Korea)
  'ko'    => 'Korean',
  'ku-IQ' => 'Kurdish', //             (Iraq)
  'ku'    => 'Kurdish',
  // 'kun-ER' => 'Kunama', //             (Eritrea)
  // 'kun'   => 'Kunama',
  'ky-KG' => 'Kyrgyz', //              (Kyrgyzstan)
  'ky'    => 'Kyrgyz',
  'la-VA' => 'Latin', //               (Vatican City)
  'la'    => 'Latin',
  'lb-LU' => 'Luxembourgish', //       (Luxembourg)
  'lb'    => 'Luxembourgish',
  // 'ln-CD' => 'Lingala', //             (Democratic Republic of the Congo)
  // 'ln-CG' => 'Lingala', //             (Republic of the Congo)
  // 'ln'    => 'Lingala',
  'lo-LA' => 'Lao', //                 (Laos)
  'lo'    => 'Lao',
  'lt-LT' => 'Lithuanian', //          (Lithuania)
  'lt'    => 'Lithuanian',
  // 'lu-CD' => 'Luba-Katanga', //        (Democratic Republic of the Congo)
  // 'lu'    => 'Luba-Katanga',
  'lv-LV' => 'Latvian', //             (Latvia)
  'lv'    => 'Latvian',
  'mg-MG' => 'Malagasy', //            (Madagascar)
  'mg'    => 'Malagasy',
  // 'mh-MH' => 'Marshallese', //         (Marshall Islands)
  // 'mh'    => 'Marshallese',
  'mi-NZ' => 'Maori', //               (New Zealand)
  'mi'    => 'Maori',
  'mk-MK' => 'Macedonian', //          (North Macedonia)
  'mk'    => 'Macedonian',
  'ml'    => 'Malayalam',
  'mn-MN' => 'Mongolian', //           (Mongolia)
  'mn'    => 'Mongolian',
  'mr'    => 'Marathi',
  'ms-BN' => 'Malay', //               (Brunei)
  'ms-MY' => 'Malay', //               (Malaysia)
  'ms-SG' => 'Malay', //               (Singapore)
  'ms'    => 'Malay',
  'mt-MT' => 'Maltese', //             (Malta)
  'mt'    => 'Maltese',
  'my-MM' => 'Burmese', //             (Myanmar)
  'my'    => 'Burmese',
  // 'na-NR' => 'Nauruan', //             (Nauru)
  // 'na'    => 'Nauruan',
  'nb-BV' => 'Norwegian', // Bokmål    (Bouvet Island)
  'nb-NO' => 'Norwegian', // Bokmål    (Norway)
  'nb'    => 'Norwegian',
  // 'nd-ZW' => 'Northern Ndebele', //    (Zimbabwe)
  // 'nd'    => 'Northern Ndebele',
  'ne-NP' => 'Nepali', //              (Nepal)
  'ne'    => 'Nepali',
  'nl-AW' => 'Dutch', //               (Aruba)
  'nl-BE' => 'Dutch', //               (Belgium)
  'nl-BQ' => 'Dutch', //               (Caribbean Netherlands)
  'nl-CW' => 'Dutch', //               (Curacao)
  'nl-MF' => 'Dutch', //               (Saint Martin)
  'nl-NL' => 'Dutch', //               (Netherlands)
  'nl-SR' => 'Dutch', //               (Suriname)
  'nl-SX' => 'Dutch', //               (Sint Maarten)
  'nl'    => 'Dutch',
  'nn-BV' => 'Norwegian', // Nynorsk   (Bouvet Island)
  'nn-NO' => 'Norwegian', // Nynorsk   (Norway)
  'nn'    => 'Norwegian',
  'no-BV' => 'Norwegian', //           (Bouvet Island)
  'no-NO' => 'Norwegian', //           (Norway)
  'no-SJ' => 'Norwegian', //           (Svalbard)
  'no'    => 'Norwegian',
  // 'nr-ZA' => 'Southern Ndebele', //    (South Africa)
  // 'nr'    => 'Southern Ndebele',
  // 'nrb-ER' => 'Nara', //               (Eritrea)
  // 'nrb'   => 'Nara',
  'ny-MW' => 'Chichewa', //            (Malawi)
  'ny'    => 'Chichewa',
  'or'    => 'Odia',
  'pa-AW' => 'Punjabi', //             (Aruba)
  'pa-CW' => 'Punjabi', //             (Curacao)
  'pa'    => 'Punjabi',
  'pl-PL' => 'Polish', //              (Poland)
  'pl'    => 'Polish',
  'ps-AF' => 'Pashto', //              (Afghanistan)
  'ps'    => 'Pashto',
  'pt-AO' => 'Portuguese', //          (Angola)
  'pt-BR' => 'Portuguese_BR', //       (Brazil)
  'pt-CV' => 'Portuguese', //          (Cape Verde)
  'pt-GQ' => 'Portuguese', //          (Equatorial Guinea)
  'pt-GW' => 'Portuguese', //          (Guinea-Bissau)
  'pt-MO' => 'Portuguese', //          (Macao)
  'pt-MZ' => 'Portuguese', //          (Mozambique)
  'pt-PT' => 'Portuguese', //          (Portugal)
  'pt-ST' => 'Portuguese', //          (Sao Tome and Principe)
  'pt-TL' => 'Portuguese', //          (East Timor)
  'pt'    => 'Portuguese',
  // 'qu-BO' => 'Quechua', //             (Bolivia)
  // 'qu'    => 'Quechua',
  'rar-CK' => 'Maori', //              (Cook Islands)
  'rar'   => 'Maori',
  // 'rm-CH' => 'Romansh', //             (Switzerland)
  // 'rm'    => 'Romansh',
  // 'rn-BI' => 'Kirundi', //             (Burundi)
  // 'rn'    => 'Kirundi',
  'ro-MD' => 'Romanian', //            (Moldova)
  'ro-RO' => 'Romanian', //            (Romania)
  'ro'    => 'Romanian',
  // 'rtm-FJ' => 'Rotuman', //            (Fiji)
  // 'rtm'   => 'Rotuman',
  'ru-AQ' => 'Russian', //             (Antarctica)
  'ru-BY' => 'Russian', //             (Belarus)
  'ru-KG' => 'Russian', //             (Kyrgyzstan)
  'ru-KZ' => 'Russian', //             (Kazakhstan)
  'ru-RU' => 'Russian', //             (Russia)
  'ru-TJ' => 'Russian', //             (Tajikistan)
  'ru-TM' => 'Russian', //             (Turkmenistan)
  'ru-UZ' => 'Russian', //             (Uzbekistan)
  'ru'    => 'Russian',
  'rw-RW' => 'Kinyarwanda', //         (Rwanda)
  'rw'    => 'Kinyarwanda',
  'sd'    => 'Sindhi',
  // 'sg-CF' => 'Sango', //               (Central African Republic)
  // 'sg'    => 'Sango',
  'si-LK' => 'Sinhala', //             (Sri Lanka)
  'si'    => 'Sinhala',
  'sk-CZ' => 'Slovak', //              (Czechia)
  'sk-SK' => 'Slovak', //              (Slovakia)
  'sk'    => 'Slovak',
  'sl-SI' => 'Slovenian', //           (Slovenia)
  'sl'    => 'Slovenian',
  'sm-AS' => 'Samoan', //              (American Samoa)
  'sm-WS' => 'Samoan', //              (Samoa)
  'sm'    => 'Samoan',
  'sn-ZW' => 'Shona', //               (Zimbabwe)
  'sn'    => 'Shona',
  'so-SO' => 'Somali', //              (Somalia)
  'so'    => 'Somali',
  'sq-AL' => 'Albanian', //            (Albania)
  'sq-ME' => 'Albanian', //            (Montenegro)
  'sq-XK' => 'Albanian', //            (Kosovo)
  'sq'    => 'Albanian',
  'sr-BA' => 'Serbian', //             (Bosnia and Herzegovina)
  'sr-ME' => 'Serbian', //             (Montenegro)
  'sr-RS' => 'Serbian', //             (Serbia)
  'sr-XK' => 'Serbian', //             (Kosovo)
  'sr'    => 'Serbian',
  // 'ss-SZ' => 'Swati', //               (Eswatini)
  // 'ss-ZA' => 'Swati', //               (South Africa)
  // 'ss'    => 'Swati',
  // 'ssy-ER' => 'Saho', //               (Eritrea)
  // 'ssy'   => 'Saho',
  // 'st-LS' => 'Southern Sotho', //      (Lesotho)
  // 'st-ZA' => 'Southern Sotho', //      (South Africa)
  // 'st'    => 'Southern Sotho',
  'su'    => 'Sundanese',
  'sv-AX' => 'Swedish', //             (Åland Islands)
  'sv-FI' => 'Swedish', //             (Finland)
  'sv-SE' => 'Swedish', //             (Sweden)
  'sv'    => 'Swedish',
  'sw-CD' => 'Kiswahili', //           (Democratic Republic of the Congo)
  'sw-KE' => 'Kiswahili', //           (Kenya)
  'sw-TZ' => 'Kiswahili', //           (Tanzania)
  'sw-UG' => 'Kiswahili', //           (Uganda)
  'sw'    => 'Kiswahili',
  'ta-LK' => 'Tamil', //               (Sri Lanka)
  'ta-SG' => 'Tamil', //               (Singapore)
  'ta'    => 'Tamil',
  'te'    => 'Telugu',
  'tg-TJ' => 'Tajik', //               (Tajikistan)
  'tg'    => 'Tajik',
  'th-TH' => 'Thai', //                (Thailand)
  'th'    => 'Thai',
  // 'ti-ER' => 'Tigrinya', //            (Eritrea)
  // 'ti'    => 'Tigrinya',
  // 'tig-ER' => 'Tigre', //              (Eritrea)
  // 'tig'   => 'Tigre',
  'tk-AF' => 'Turkmen', //             (Afghanistan)
  'tk-TM' => 'Turkmen', //             (Turkmenistan)
  'tk'    => 'Turkmen',
  // 'tn-BW' => 'Tswana', //              (Botswana)
  // 'tn-ZA' => 'Tswana', //              (South Africa)
  // 'tn'    => 'Tswana',
  // 'to-TO' => 'Tonga', //               (Tonga Islands)
  // 'to'    => 'Tonga',
  'tr-CY' => 'Turkish', //             (Cyprus)
  'tr-TR' => 'Turkish', //             (Turkey)
  'tr'    => 'Turkish',
  // 'ts-ZA' => 'Tsonga', //              (South Africa)
  // 'ts'    => 'Tsonga',
  'tt'    => 'Tatar',
  'ug'    => 'Uyghur',
  'uk-UA' => 'Ukrainian', //           (Ukraine)
  'uk'    => 'Ukrainian',
  'ur-PK' => 'Urdu', //                (Pakistan)
  'ur'    => 'Urdu',
  'uz-AF' => 'Uzbek', //               (Afghanistan)
  'uz-UZ' => 'Uzbek', //               (Uzbekistan)
  'uz'    => 'Uzbek',
  // 've-ZA' => 'Venda', //               (South Africa)
  // 've'    => 'Venda',
  'vi-VN' => 'Vietnamese', //          (Vietnam)
  'vi'    => 'Vietnamese',
  'vo'    => 'Yoruba',
  'xh-ZA' => 'Xhosa', //               (South Africa)
  'xh'    => 'Xhosa',
  'zh-min-nan-TW' => 'Chinese-Traditional', // (Taiwan)
  'zh-TW' => 'Chinese-Traditional', // (Taiwan)
  'zh'    => 'Chinese-Simplified',
  'zu-ZA' => 'Zulu', //                (South Africa)
  'zu'    => 'Zulu',
// I am guessing about these. May be 'Traditional'.
  'zh-CN' => 'Chinese-Simplified', // (China)
  'zh-HK' => 'Chinese-Simplified', // (Hong Kong)
  'zh-MO' => 'Chinese-Simplified', // (Macao)
  'zh-SG' => 'Chinese-Simplified'  // (Singapore)
];

/*
General purpose translations that may be used elsewhere
as variables and not picked up by update_translation.pl

translate ( 'event' ) translate ( 'journal' )

I want to get the 2-letter codes accessable in each tranlations/*.txt
translate ( 'ISO Language Code' )

Because not everyone uses these symbols for numbers:
translate ( '0' ) translate ( '1' ) translate ( '2' ) translate ( '3' )
translate ( '4' ) translate ( '5' ) translate ( '6' ) translate ( '7' )
translate ( '8' ) translate ( '9' )

These are for Latin and Saudi Arabic. Maybe others.
translate ( '10' ) translate ( '11' ) translate ( '12' ) translate ( '13' )
translate ( '14' ) translate ( '15' ) translate ( '16' ) translate ( '17' )
translate ( '18' ) translate ( '19' ) translate ( '20' ) translate ( '21' )
translate ( '22' ) translate ( '23' ) translate ( '24' ) translate ( '25' )
translate ( '26' ) translate ( '27' ) translate ( '28' ) translate ( '29' )
translate ( '30' ) translate ( '31' ) translate ( '40' ) translate ( '50' )
translate ( '60' ) translate ( '70' ) translate ( '80' ) translate ( '90' )
translate ( '100' ) translate ( '200' ) translate ( '300' ) translate ( '400' )
translate ( '500' ) translate ( '600' ) translate ( '700' ) translate ( '800' )
translate ( '900' ) translate ( '1000' ) translate ( '2000' )
translate ( '2023' ) translate ( '2024' ) translate ( '2025' )
translate ( '3000' ) translate ( '3999' ) translate ( '4000' )
translate ( '5000' ) translate ( '6000' ) translate ( '7000' )
translate ( '8000' )  translate ( '9000' )translate ( '10000' )
translate ( '20000' ) translate ( '30000' ) translate ( '40000' )
translate ( '50000' ) translate ( '60000' ) translate ( '70000' )
translate ( '80000' ) translate ( '90000' ) translate ( '100000' )
translate ( '200000' ) translate ( '300000' ) translate ( '400000' )
translate ( '500000' ) translate ( '600000' ) translate ( '700000' )
translate ( '800000' ) translate ( '900000' ) translate ( '1000000' )

Will be using these in the translations/*.txt files to better organize things
translate ( 'Date Formats' ) translate ( 'ISO Language Code' )
translate ( 'Number Symbols' ) translate ( 'Names (and Abbreviations)' )
translate ( 'Old phrases not currently in the code' )
*/
?>
