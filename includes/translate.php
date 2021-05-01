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
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */

/**
 * Performs html_entity_decode style conversion for php < 4.3
 * Borrowed from http://us2.php.net/manual/en/function.html-entity-decode.php
 *
 * @param string $string Text to convert
 * @paran bool   $ignore Ignore the charset when decoding
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

function read_trans_file ( $in_file, $out_file = '', $strip = true ) {
  global $can_save, $new_install, $translations;

  $fp = fopen ( $in_file, 'r', false );
  if ( ! $fp )
    die_miserable_death ( 'Could not open language file: ' . $in_file );

  $inInstallTrans = false;
  $installationTranslations = [];

  while ( ! feof ( $fp ) ) {
    $buffer = trim ( fgets ( $fp, 4096 ) );
    if ( strlen ( $buffer ) == 0 )
      continue;

    if ( function_exists( 'get_magic_quotes_runtime' )
        && @get_magic_quotes_runtime() && $strip )
      $buffer = stripslashes ( $buffer );

    // Convert quotes to entities.
    $buffer =
    str_replace ( ['"', "'"], ['&quot;', '&#39;'], $buffer );

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

/**
 * Unloads $translations so we can translate a different language.
 *
 * @param string $new_language New language file to load (just the base filename,
 *                             no directory or file suffix. Example:  "French")
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
 * @param string   $decode  Do we want to envoke html_entity_decode?
 *                          We currently only use this with javascript alerts.
 * @param string   $type    ('' = alphabetic, A = alphanumeric,
 *                          D = date, N = numeric)
 *
 * @return string The translated text, if available. If no translation is
 *                avalailable, then the original untranslated text is returned.
 */
function translate ( $str, $decode = '', $type = '' ) {
  global $LANGUAGE, $translation_loaded, $translations;

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
        $tmp = date ( 'F', mktime ( 0, 0, 0, $i + 1 ) );
        if ( $tmp != $translations[$tmp] )
          $str = str_replace ( $tmp, $translations[$tmp], $str );

        $tmp = date ( 'M', mktime ( 0, 0, 0, $i + 1 ) );
        if ( $tmp != $translations[$tmp] )
          $str = str_replace ( $tmp, $translations[$tmp], $str );

        if ( $i < 7 ) {
          // Might as well translate day names while we're here.
          $tmp = date ( 'l', mktime ( 0, 0, 0, 1, $i + 1 ) );
          if ( $tmp != $translations[$tmp] )
            $str = str_replace ( $tmp, $translations[$tmp], $str );

          $tmp = date ( 'D', mktime ( 0, 0, 0, 1, $i + 1 ) );
          if ( $tmp != $translations[$tmp] )
            $str = str_replace ( $tmp, $translations[$tmp], $str );
        }
      }
    }
    if ( $type != '' ) {
      // Translate number symbols.
      for ( $i = 0; $i < 10; $i++ ) {
        $tmp = $i . '';
        if ( $tmp != $translations[$tmp] )
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
 * @param string   $decode  Do we want to envoke html_entity_decode
 * @param string   $type    (A = alphabetic, D = date, N = numeric)
 * @param integer  $date    Default date()
 *
 * @uses translate
 */
function etranslate ( $str, $decode = '', $type = 'A', $date = '' ) {
  echo translate ( $str, $decode, $type, $date );
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
 * @return string The translated text with all HTML removed
 */
function tooltip( $str, $decode = '' ) {
  $ret = translate( $str, $decode );
  $ret = preg_replace( '/<[^>]+>/', '', $ret );
  return preg_replace( '/"/', "'", $ret );
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
function etooltip ( $str, $decode = '' ) {
  echo tooltip ( $str, $decode );
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
//  translate ( 'Abkhazian' )        => 'Abkhazian',
//  translate ( 'Acoli' )            => 'Acoli',
//  translate ( 'Adangme' )          => 'Adangme',
//  translate ( 'Adyghe' )           => 'Adyghe',
//  translate ( 'Afar' )             => 'Afar',
//  translate ( 'Afrihili' )         => 'Afrihili',
    translate ( 'Afrikaans' )        => 'Afrikaans',
//  translate ( 'Ainu' )             => 'Ainu',
//  translate ( 'Akan' )             => 'Akan',
//  translate ( 'Akkadian' )         => 'Akkadian',
    translate ( 'Albanian' )         => 'Albanian',
//  translate ( 'Aleut' )            => 'Aleut',
//  translate ( 'Algonquian' )       => 'Algonquian',
//  translate ( 'Altai (Southern)' ) => 'Altai',
    translate ( 'Amharic' )          => 'Amharic',
//  translate ( 'Angika' )           => 'Angika',
//  translate ( 'Apache' )           => 'Apache',
    translate ( 'Arabic (Algeria)' )      => 'Arabic',
    translate ( 'Arabic (Bahrain)' )      => 'Arabic',
    translate ( 'Arabic (Egypt)' )        => 'Arabic',
    translate ( 'Arabic (Iraq)' )         => 'Arabic',
    translate ( 'Arabic (Jordan)' )       => 'Arabic',
    translate ( 'Arabic (Kuwait)' )       => 'Arabic',
    translate ( 'Arabic (Lebanon)' )      => 'Arabic',
    translate ( 'Arabic (Libya)' )        => 'Arabic',
    translate ( 'Arabic (Morocco)' )      => 'Arabic',
    translate ( 'Arabic (Oman)' )         => 'Arabic',
    translate ( 'Arabic (Qatar)' )        => 'Arabic',
    translate ( 'Arabic (Saudi Arabia)' ) => 'Arabic',
    translate ( 'Arabic (Syria)' )        => 'Arabic',
    translate ( 'Arabic (Tunisia)' )      => 'Arabic',
    translate ( 'Arabic (U.A.E.)' )       => 'Arabic',
    translate ( 'Arabic (Yemen)' )        => 'Arabic',
    translate ( 'Arabic' )                => 'Arabic',
//  translate ( 'Aragonese' )        => 'Aragonese',
//  translate ( 'Aramaic' )          => 'Aramaic',
//  translate ( 'Arapaho' )          => 'Arapaho',
//  translate ( 'Arawak' )           => 'Arawak',
    translate ( 'Armenian' )         => 'Armenian',
//  translate ( 'Assamese' )         => 'Assamese',
//  translate ( 'Asturian' )         => 'Asturian',
//  translate ( 'Athapascan' )       => 'Athapascan',
//  translate ( 'Avaric' )           => 'Avaric',
//  translate ( 'Avestan' )          => 'Avestan',
//  translate ( 'Awadhi' )           => 'Awadhi',
//  translate ( 'Aymara' )           => 'Aymara',
    translate ( 'Azerbaijani' )      => 'Azerbaijani',
//  translate ( 'Balinese' )         => 'Balinese',
//  translate ( 'Baluchi' )          => 'Baluchi',
//  translate ( 'Bambara' )          => 'Bambara',
//  translate ( 'Bamileke' )         => 'Bamileke',
//  translate ( 'Banda' )            => 'Banda',
//  translate ( 'Basa' )             => 'Basa',
//  translate ( 'Bashkir' )          => 'Bashkir',
    translate ( 'Basque' )           => 'Basque',
//  translate ( 'Beja' )             => 'Beja',
    translate ( 'Belarusian' )       => 'Belarusian',
//  translate ( 'Bemba' )            => 'Bemba',
    translate ( 'Bengali' )          => 'Bengali',
//  translate ( 'Bhojpuri' )         => 'Bhojpuri',
//  translate ( 'Bihari' )           => 'Bihari',
//  translate ( 'Bikol' )            => 'Bikol',
//  translate ( 'Bislama' )          => 'Bislama',
//  translate ( 'Blackfoot' )        => 'Blackfoot',
//  translate ( 'Blin' )             => 'Blin',
//  translate ( 'Bliss' )            => 'Bliss',
    translate ( 'Bosnian' )          => 'Bosnian',
//  translate ( 'Braj' )             => 'Braj',
//  translate ( 'Breton' )           => 'Breton',
//  translate ( 'Buginese' )         => 'Buginese',
    translate ( 'Bulgarian' )        => 'Bulgarian',
//  translate ( 'Buriat' )           => 'Buriat',
    translate ( 'Burmese' )          => 'Myanmar',
//  translate ( 'Caddo' )            => 'Caddo',
    translate ( 'Catalan' )          => 'Catalan',
    translate ( 'Cebuano' )          => 'Cebuano',
//  translate ( 'Chagatai' )         => 'Chagatai',
//  translate ( 'Chamorro' )         => 'Chamorro',
//  translate ( 'Chechen' )          => 'Chechen',
//  translate ( 'Cherokee' )         => 'Cherokee',
//  translate ( 'Cheyenne' )         => 'Cheyenne',
//  translate ( 'Chibcha' )          => 'Chibcha',
    translate ( 'Chichewa' )         => 'Chichewa',
    translate ( 'Chinese (Hong Kong)' )        => 'Chinese-Simplified',
    translate ( 'Chinese (PRC)' )               => 'Chinese-Simplified',
    translate ( 'Chinese (Simplified/GB2312)' ) => 'Chinese-Simplified',
    translate ( 'Chinese (Singapore)' )         => 'Chinese-Simplified',
    translate ( 'Chinese (Taiwan)' )            => 'Holo-Big5',
    translate ( 'Chinese (Traditional/Big5)' )  => 'Chinese-Traditional',
//  translate ( 'Chinook' )          => 'Chinook',
//  translate ( 'Chipewyan' )        => 'Chipewyan',
//  translate ( 'Choctaw' )          => 'Choctaw',
//  translate ( 'Chuukese' )         => 'Chuukese',
//  translate ( 'Chuvash' )          => 'Chuvash',
//  translate ( 'Coptic' )           => 'Coptic',
//  translate ( 'Cornish' )          => 'Cornish',
    translate ( 'Corsican' )         => 'Corsican',
//  translate ( 'Cree' )             => 'Cree',
//  translate ( 'Creek' )            => 'Creek',
    translate ( 'Croatian' )         => 'Croatian',
    translate ( 'Czech' )            => 'Czech',
//  translate ( 'Dakota' )           => 'Dakota',
    translate ( 'Danish' )           => 'Danish',
//  translate ( 'Dargwa' )           => 'Dargwa',
//  translate ( 'Delaware' )         => 'Delaware',
//  translate ( 'Dinka' )            => 'Dinka',
//  translate ( 'Dogri' )            => 'Dogri',
//  translate ( 'Dogrib' )           => 'Dogrib',
//  translate ( 'Duala' )            => 'Duala',
    translate ( 'Dutch (Belgium)' )  => 'Dutch',
    translate ( 'Dutch' )            => 'Dutch',
//  translate ( 'Dyula' )            => 'Dyula',
//  translate ( 'Dzongkha' )         => 'Dzongkha',
//  translate ( 'Edo' )              => 'Edo',
//  translate ( 'Efik' )             => 'Efik',
//  translate ( 'Ekajuk' )           => 'Ekajuk',
//  translate ( 'Elamite' )          => 'Elamite',
    translate ( 'English (Australia)' )         => 'English-US',
    translate ( 'English (Belize)' )            => 'English-US',
    translate ( 'English (Canada)' )            => 'English-US',
    translate ( 'English (Ireland)' )           => 'English-US',
    translate ( 'English (Jamaica)' )           => 'English-US',
    translate ( 'English (New Zealand)' )       => 'English-US',
    translate ( 'English (Philippines)' )       => 'English-US',
    translate ( 'English (South Africa)' )      => 'English-US',
    translate ( 'English (Trinidad & Tobago)' ) => 'English-US',
    translate ( 'English (United Kingdom)' )    => 'English-US',
    translate ( 'English (United States)' )     => 'English-US',
    translate ( 'English (Zimbabwe)' )          => 'English-US',
    translate ( 'English' )                     => 'English-US',
//  translate ( 'Erzya' )            => 'Erzya',
    translate ( 'Esperanto' )        => 'Esperanto',
    translate ( 'Estonian' )         => 'Estonian',
//  translate ( 'Ewe' )              => 'Ewe',
//  translate ( 'Ewondo' )           => 'Ewondo',
//  translate ( 'Fang' )             => 'Fang',
//  translate ( 'Fanti' )            => 'Fanti',
//  translate ( 'Faroese' )          => 'Faroese',
//  translate ( 'Fijian' )           => 'Fijian',
    translate ( 'Filipino' )         => 'Filipino',
    translate ( 'Finnish' )          => 'Finnish',
//  translate ( 'Fon' )              => 'Fon',
    translate ( 'French (Belgium)' )     => 'French',
    translate ( 'French (Canada)' )      => 'French',
    translate ( 'French (France)' )      => 'French',
    translate ( 'French (Luxembourg)' )  => 'French',
    translate ( 'French (Monaco)' )      => 'French',
    translate ( 'French (Switzerland)' ) => 'French',
    translate ( 'French' ),              => 'French',
    translate ( 'Frisian (Eastern)' )  => 'Frisian',
    translate ( 'Frisian (Northern)' ) => 'Frisian',
    translate ( 'Frisian (Western)' )  => 'Frisian',
    translate ( 'Frisian' )            => 'Frisian',
//  translate ( 'Friulian' )         => 'Friulian',
//  translate ( 'Fulah' )            => 'Fulah',
//  translate ( 'Ga' )               => 'Ga',
//  translate ( 'Galibi' )           => 'Galibi',
    translate ( 'Galician' )         => 'Galician',
//  translate ( 'Ganda' )            => 'Ganda',
//  translate ( 'Gayo' )             => 'Gayo',
//  translate ( 'Gbaya' )            => 'Gbaya',
//  translate ( 'Geez' )             => 'Geez',
    translate ( 'Georgian' )         => 'Georgian',
    translate ( 'German (Austria)' )       => 'German',
    translate ( 'German (Germany)' )       => 'German',
    translate ( 'German (Liechtenstein)' ) => 'German',
    translate ( 'German (Luxembourg)' )    => 'German',
    translate ( 'German (Standard)' )      => 'German',
    translate ( 'German (Switzerland)' )   => 'German',
    translate ( 'German' )                 => 'German',
//  translate ( 'Gilbertese' )       => 'Gilbertese',
//  translate ( 'Gondi' )            => 'Gondi',
//  translate ( 'Gorontalo' )        => 'Gorontalo',
//  translate ( 'Gothic' )           => 'Gothic',
//  translate ( 'Grebo' )            => 'Grebo',
    translate ( 'Greek' )            => 'Greek',
//  translate ( 'Greenlandic' )      => 'Greenlandic',
//  translate ( 'Guarani' )          => 'Guarani',
    translate ( 'Gujarati' )         => 'Gujarati',
//  translate ( 'Gwich&#39;in' )     => 'Gwich-in',
//  translate ( 'Haida' )            => 'Haida',
    translate ( 'Haitian (Creole)' ) => 'Haitian',
    translate ( 'Haitian' )          => 'Haitian',
    translate ( 'Hausa' )            => 'Hausa',
    translate ( 'Hawaiian' )         => 'Hawaiian',
    translate ( 'Hebrew' )           => 'Hebrew',
//  translate ( 'Herero' )           => 'Herero',
//  translate ( 'Hiligaynon' )       => 'Hiligaynon',
    translate ( 'Hindi' )            => 'Hindi',
//  translate ( 'Hiri Motu' )        => 'Hiri-Motu',
//  translate ( 'Hittite' )          => 'Hittite',
    translate ( 'Hmong' )            => 'Hmong',
    translate ( 'Holo (Taiwanese)' ) => 'Holo-Big5',
    translate ( 'Hungarian' )        => 'Hungarian',
//  translate ( 'Hupa' )             => 'Hupa',
//  translate ( 'Iban' )             => 'Iban',
    translate ( 'Icelandic' )        => 'Icelandic',
//  translate ( 'Ido' )              => 'Ido',
    translate ( 'Igbo' )             => 'Igbo',
//  translate ( 'Iloko' )            => 'Iloko',
    translate ( 'Indonesian' )       => 'Indonesian',
//  translate ( 'Ingush' )           => 'Ingush',
//  translate ( 'Inuktitut' )        => 'Inuktitut',
//  translate ( 'Inupiaq' )          => 'Inupiaq',
//  translate ( 'Iranian' )          => 'Iranian',
    translate ( 'Irish (Gaelic)' )   => 'Irish',
    translate ( 'Irish' )            => 'Irish',
//  translate ( 'Iroquoian' )        => 'Iroquoian',
    translate ( 'Italian (Switzerland)' ) => 'Italian',
    translate ( 'Italian' )               => 'Italian',
    translate ( 'Japanese' )                  => 'Japanese',
    translate ( 'Japanese' ) . ' (EUC-JP)'    => 'Japanese-eucjp',
    translate ( 'Japanese' ) . ' (SHIFT JIS)' => 'Japanese-sjis',
    translate ( 'Javanese' )         => 'Javanese',
//  translate ( 'Judeo-Arabic' )     => 'Judeo-Arabic',
//  translate ( 'Judeo-Persian' )    => 'Judeo-Persian',
//  translate ( 'Kabardian' )        => 'Kabardian',
//  translate ( 'Kabyle' )           => 'Kabyle',
//  translate ( 'Kachin' )           => 'Kachin',
//  translate ( 'Kalaallisut' )      => 'Kalaallisut',
//  translate ( 'Kalmyk' )           => 'Kalmyk',
//  translate ( 'Kamba' )            => 'Kamba',
    translate ( 'Kannada' )          => 'Kannada',
//  translate ( 'Kanuri' )           => 'Kanuri',
//  translate ( 'Kara-Kalpak' )      => 'Kara-Kalpak',
//  translate ( 'Karachay-Balkar' )  => 'Karachay-Balkar',
//  translate ( 'Karelian' )         => 'Karelian',
//  translate ( 'Kashmiri' )         => 'Kashmiri',
//  translate ( 'Kashubian' )        => 'Kashubian',
//  translate ( 'Kawi' )             => 'Kawi',
    translate ( 'Kazakh' )           => 'Kazakh',
//  translate ( 'Khasi' )            => 'Khasi',
    translate ( 'Khmer (Central)' )  => 'Khmer',
    translate ( 'Khmer' )            => 'Khmer',
//  translate ( 'Khotanese' )        => 'Khotanese',
//  translate ( 'Kikuyu' )           => 'Kikuyu',
//  translate ( 'Kimbundu' )         => 'Kimbundu',
    translate ( 'Kinyarwanda' )      => 'Kinyarwanda',
//  translate ( 'Kirghiz' )          => 'Kirghiz',
//  translate ( 'Klingon' )          => 'Klingon', // Yes, really!
//  translate ( 'Komi' )             => 'Komi',
//  translate ( 'Kongo' )            => 'Kongo',
//  translate ( 'Konkani' )          => 'Konkani',
    translate ( 'Korean (Johab)' )   => 'Korean',
    translate ( 'Korean (North)' )   => 'Korean',
    translate ( 'Korean (South)' )   => 'Korean',
    translate ( 'Korean' )           => 'Korean',
//  translate ( 'Kosraean' )         => 'Kosraean',
//  translate ( 'Kpelle' )           => 'Kpelle',
//  translate ( 'Kuanyama' )         => 'Kuanyama',
//  translate ( 'Kumyk' )            => 'Kumyk',
    translate ( 'Kurdish' )          => 'Kurdish',
//  translate ( 'Kutenai' )          => 'Kutenai',
    translate ( 'Kyrgyz' )           => 'Kyrgyz',
//  translate ( 'Ladino' )           => 'Ladino',
//  translate ( 'Lahnda' )           => 'Lahnda',
//  translate ( 'Lamba' )            => 'Lamba',
    translate ( 'Lao' )              => 'Lao',
    translate ( 'Latin' )            => 'Latin',
    translate ( 'Latvian' )          => 'Latvian',
//  translate ( 'Lezghian' )         => 'Lezghian',
//  translate ( 'Limburgan' )        => 'Limburgan',
//  translate ( 'Lingala' )          => 'Lingala',
    translate ( 'Lithuanian' )       => 'Lithuanian',
//  translate ( 'Lojban' )           => 'Lojban',
//  translate ( 'Lozi' )             => 'Lozi',
//  translate ( 'Luba-Katanga' )     => 'Luba-Katanga',
//  translate ( 'Luba-Lulua' )       => 'Luba-Lulua',
//  translate ( 'Luiseno' )          => 'Luiseno',
//  translate ( 'Lunda' )            => 'Lunda',
//  translate ( 'Luo' )              => 'Luo',
//  translate ( 'Lushai' )           => 'Lushai',
    translate ( 'Luxembourghish' )   => 'Luxembourghish',
    translate ( 'Macedonian' )       => 'Macedonian', // FYROM
//  translate ( 'Madurese' )         => 'Madurese',
//  translate ( 'Magahi' )           => 'Magahi',
//  translate ( 'Maithili' )         => 'Maithili',
//  translate ( 'Makasar' )          => 'Makasar',
    translate ( 'Malagasy' )         => 'Malagasy',
    translate ( 'Malay' )            => 'Malay',
    translate ( 'Malayalam' )        => 'Malayalam',
//  translate ( 'Maldivian' )        => 'Maldivian',
    translate ( 'Maltese' )          => 'Maltese',
//  translate ( 'Manchu' )           => 'Manchu',
//  translate ( 'Mandar' )           => 'Mandar',
//  translate ( 'Mandingo' )         => 'Mandingo',
//  translate ( 'Manipuri' )         => 'Manipuri',
//  translate ( 'Manx' )             => 'Manx',
    translate ( 'Maori' )            => 'Maori',
//  translate ( 'Mapuche' )          => 'Mapuche',
    translate ( 'Marathi' )          => 'Marathi',
//  translate ( 'Mari' )             => 'Mari',
//  translate ( 'Marshallese' )      => 'Marshallese',
//  translate ( 'Marwari' )          => 'Marwari',
//  translate ( 'Masai' )            => 'Masai',
//  translate ( 'Mayan' )            => 'Mayan',
//  translate ( 'Mende' )            => 'Mende',
//  translate ( 'Mi&#39;kmaq' )      => 'Mikmaq',
//  translate ( 'Minangkabau' )      => 'Minangkabau',
//  translate ( 'Mirandese' )        => 'Mirandese',
//  translate ( 'Mohawk' )           => 'Mohawk',
//  translate ( 'Moksha' )           => 'Moksha',
//  translate ( 'Moldavian' )        => 'Moldavian',
//  translate ( 'Mongo' )            => 'Mongo',
    translate ( 'Mongolian' )        => 'Mongolian',
//  translate ( 'Montenegrin' )      => 'Montenegrin',
//  translate ( 'Moroccan' )         => 'Moroccan',
//  translate ( 'Mossi' )            => 'Mossi',
    translate ( 'Myanmar' )          => 'Myanmar',
    translate ( 'Māori' )            => 'Maori',
//  translate ( 'N&#39;Ko' )         => 'N-Ko',
//  translate ( 'Nauru' )            => 'Nauru',
//  translate ( 'Navajo' )           => 'Navajo',
//  translate ( 'Ndebele (North)' )  => 'Ndebele',
//  translate ( 'Ndebele (South)' )  => 'Ndebele',
//  translate ( 'Ndebele' )          => 'Ndebele',
//  translate ( 'Ndonga' )           => 'Ndonga',
//  translate ( 'Neapolitan' )       => 'Neapolitan',
    translate ( 'Nepali' )           => 'Nepali',
//  translate ( 'Nias' )             => 'Nias',
//  translate ( 'Niuean' )           => 'Niuean',
//  translate ( 'Nogai' )            => 'Nogai',
//  translate ( 'Norse' )            => 'Norse',
    translate ( 'Norwegian (Bokmål)' )  => 'Norwegian',
    translate ( 'Norwegian (Nynorsk)' ) => 'Norwegian',
    translate ( 'Norwegian' )           => 'Norwegian',
//  translate ( 'Nyamwezi' )         => 'Nyamwezi',
//  translate ( 'Nyankole' )         => 'Nyankole',
//  translate ( 'Nyoro' )            => 'Nyoro',
//  translate ( 'Nzima' )            => 'Nzima',
//  translate ( 'Occitan' )          => 'Occitan',
    translate ( 'Odia' )             => 'Odia',
//  translate ( 'Ojibwa' )           => 'Ojibwa',
    translate ( 'Oriyia' )           => 'Odia',
//  translate ( 'Oromo' )            => 'Oromo',
//  translate ( 'Osage' )            => 'Osage',
//  translate ( 'Ossetic' )          => 'Ossetic',
//  translate ( 'Pahlavi' )          => 'Pahlavi',
//  translate ( 'Palauan' )          => 'Palauan',
//  translate ( 'Pali' )             => 'Pali',
//  translate ( 'Pampanga' )         => 'Pampanga',
//  translate ( 'Pangasinan' )       => 'Pangasinan',
    translate ( 'Panjabi' )          => 'Punjabi',
//  translate ( 'Papiamento' )       => 'Papiamento',
    translate ( 'Pashto' )           => 'Pashto',
//  translate ( 'Pedi' )             => 'Pedi',
    translate ( 'Persian (Farsi)' )  => 'Persian',
    translate ( 'Persian (Iran)' )   => 'Persian',
    translate ( 'Persian' )          => 'Persian',
//  translate ( 'Phoenician' )       => 'Phoenician',
//  translate ( 'Pohnpeian' )        => 'Pohnpeian',
    translate ( 'Polish' )           => 'Polish',
    translate ( 'Portuguese' )         => 'Portuguese', //    Portugal
    translate ( 'Portuguese/Brazil' )  => 'Portuguese_BR', // Brazil
    translate ( 'Punjabi (India)' )    => 'Punjabi',
    translate ( 'Punjabi (Pakistan)' ) => 'Punjabi',
    translate ( 'Punjabi' )            => 'Punjabi',
//  translate ( 'Quechua' )          => 'Quechua',
//  translate ( 'Rajasthani' )       => 'Rajasthani',
//  translate ( 'Rapanui' )          => 'Rapanui',
//  translate ( 'Rarotongan' )       => 'Rarotongan',
//  translate ( 'Rhaeto-Romanic' )   => 'Rhaeto-Romanic',
    translate ( 'Romanian' )         => 'Romanian',
//  translate ( 'Romansh' )          => 'Romansh',
//  translate ( 'Romany' )           => 'Romany',
//  translate ( 'Rundi' )            => 'Rundi',
    translate ( 'Russian (Moldavia' ))            => 'Russian',
    translate ( 'Russian (Republic of Moldova)' ) => 'Russian',
    translate ( 'Russian' )                       => 'Russian',
//  translate ( 'Sami (Inari)' )     => 'Sami',
//  translate ( 'Sami (Lappish)' )   => 'Sami',
//  translate ( 'Sami (Lule)' )      => 'Sami',
//  translate ( 'Sami (Northern)' )  => 'Sami',
//  translate ( 'Sami (Skolt)' )     => 'Sami',
//  translate ( 'Sami (Southern)' )  => 'Sami',
//  translate ( 'Sami' )             => 'Sami',
    translate ( 'Samoan' )           => 'Samoan',
    translate ( 'Samoli' )           => 'Samoli',
//  translate ( 'Sandawe' )          => 'Sandawe',
//  translate ( 'Sango' )            => 'Sango',
//  translate ( 'Sanskrit' )         => 'Sanskrit',
//  translate ( 'Santali' )          => 'Santali',
//  translate ( 'Sardinian' )        => 'Sardinian',
//  translate ( 'Sasak' )            => 'Sasak',
    translate ( 'Scots (Gaelic)' )   => 'Scots',
//  translate ( 'Selkup' )           => 'Selkup',
    translate ( 'Serbian (Upper)' )  => 'Serbian',
    translate ( 'Serbian' )          => 'Serbian',
//  translate ( 'Serer' )            => 'Serer',
    translate ( 'Sesotho' )          => 'Sesotho',
//  translate ( 'Shan' )             => 'Shan',
    translate ( 'Shona' )            => 'Shona',
//  translate ( 'Sichuan Yi' )       => 'Sichuan-Yi',
//  translate ( 'Sicilian' )         => 'Sicilian',
    translate ( 'Sindhi' )           => 'Sindhi',
    translate ( 'Sinhala' )          => 'Sinhala',
//  translate ( 'Slavic' )           => 'Slavic',
    translate ( 'Slovak' )           => 'Slovak',
    translate ( 'Slovenian' )        => 'Slovenian',
//  translate ( 'Sogdian' )          => 'Sogdian',
    translate ( 'Somali' )           => 'Somali',
//  translate ( 'Somani' )           => 'Somani',
//  translate ( 'Songhai' )          => 'Songhai',
//  translate ( 'Soninke' )          => 'Soninke',
//  translate ( 'Sorbian (Lower)' )  => 'Sorbian',
//  translate ( 'Sorbian (Upper)' )  => 'Sorbian',
//  translate ( 'Sorbian' )          => 'Sorbian',
//  translate ( 'Sotho' )            => 'Sotho',
    translate ( 'Spanish (Argentina)' )        => 'Spanish',
    translate ( 'Spanish (Bolivia)' )          => 'Spanish',
    translate ( 'Spanish (Chile)' )            => 'Spanish',
    translate ( 'Spanish (Colombia)' )         => 'Spanish',
    translate ( 'Spanish (Costa Rica)' )       => 'Spanish',
    translate ( 'Spanish (Dominican Republic)' ) => 'Spanish',
    translate ( 'Spanish (Ecuador)' )          => 'Spanish',
    translate ( 'Spanish (El Salvador)' )      => 'Spanish',
    translate ( 'Spanish (Guatemala)' )        => 'Spanish',
    translate ( 'Spanish (Honduras)' )         => 'Spanish',
    translate ( 'Spanish (Mexico)' )           => 'Spanish',
    translate ( 'Spanish (Nicaragua)' )        => 'Spanish',
    translate ( 'Spanish (Panama)' )           => 'Spanish',
    translate ( 'Spanish (Paraguay)' )         => 'Spanish',
    translate ( 'Spanish (Peru)' )             => 'Spanish',
    translate ( 'Spanish (Puerto Rico)' )      => 'Spanish',
    translate ( 'Spanish (Spain)' )            => 'Spanish',
    translate ( 'Spanish (Uruguay)' )          => 'Spanish',
    translate ( 'Spanish (Venezuela)' )        => 'Spanish',
    translate ( 'Spanish' )                    => 'Spanish',
//  translate ( 'Sranan Tongo' )     => 'Tongo',
//  translate ( 'Sukuma' )           => 'Sukuma',
//  translate ( 'Sumerian' )         => 'Sumerian',
    translate ( 'Sundanese' )        => 'Sundanese',
//  translate ( 'Susu' )             => 'Susu',
//  translate ( 'Sutu' )             => 'Sutu',
    translate ( 'Swahili' )          => 'Swahili',
//  translate ( 'Swati' )            => 'Swati',
    translate ( 'Swedish (Finland)' ) => 'Swedish',
    translate ( 'Swedish (Sweden)' )  => 'Swedish',
    translate ( 'Swedish' )           => 'Swedish',
//  translate ( 'Syriac' )           => 'Syriac',
//  translate ( 'Tagalog' )          => 'Tagalog',
//  translate ( 'Tahitian' )         => 'Tahitian',
//  translate ( 'Tai' )              => 'Tai',
    translate ( 'Taiwan' )           => 'Holo-Big5',
    translate ( 'Tajik' )            => 'Tajik',
//  translate ( 'Tamashek' )         => 'Tamashek',
    translate ( 'Tamil' )            => 'Tamil',
    translate ( 'Tatar' )            => 'Tatar',
    translate ( 'Telugu' )           => 'Telugu'
//  translate ( 'Tereno' )           => 'Tereno',
//  translate ( 'Tetum' )            => 'Tetum',
    translate ( 'Thai' )             => 'Thai',
//  translate ( 'Tibetan' )          => 'Tibetan',
//  translate ( 'Tigre' )            => 'Tigre',
//  translate ( 'Tigrinya' )         => 'Tigrinya',
//  translate ( 'Timne' )            => 'Timne',
//  translate ( 'Tlingit' )          => 'Tlingit',
//  translate ( 'Tok Pisin' )        => 'Tok',
//  translate ( 'Tokelau' )          => 'Tokelau',
//  translate ( 'Tonga (Nytmhasa)' )      => 'Tonga',
//  translate ( 'Tonga (Tonga Islands)' ) => 'Tonga',
//  translate ( 'Tonga' )                 => 'Tonga',
//  translate ( 'Tsimshian' )        => 'Tsimshian',
//  translate ( 'Tsonga' )           => 'Tsonga',
//  translate ( 'Tswana' )           => 'Tswana',
//  translate ( 'Tumbuka' )          => 'Tumbuka',
    translate ( 'Turkish' )          => 'Turkish',
    translate ( 'Turkmen' )          => 'Turkmen',
//  translate ( 'Tuvinian' )         => 'Tuvinian',
//  translate ( 'Twi' )              => 'Twi',
//  translate ( 'Udmurt' )           => 'Udmurt',
//  translate ( 'Ugaritic' )         => 'Ugaritic',
    translate ( 'Ukrainian' )        => 'Ukrainian',
//  translate ( 'Umbundu' )          => 'Umbundu',
    translate ( 'Urdu' )             => 'Urdu',
    translate ( 'Uyghur' )           => 'Uyghur',
    translate ( 'Uzbek' )            => 'Uzbek',
//  translate ( 'Vai' )              => 'Vai',
//  translate ( 'Venda' )            => 'Venda',
    translate ( 'Vietnamese' )       => 'Vietnamese',
//  translate ( 'Volapük' )          => 'Volapuk',
//  translate ( 'Votic' )            => 'Votic',
//  translate ( 'Wakashan' )         => 'Wakashan',
//  translate ( 'Walloon' )          => 'Walloon',
//  translate ( 'Waray' )            => 'Waray',
//  translate ( 'Washo' )            => 'Washo',
    translate ( 'Welsh' )            => 'Welsh',
//  translate ( 'Wolaitta' )         => 'Wolaitta',
//  translate ( 'Wolof' )            => 'Wolof',
    translate ( 'Xhosa' )            => 'Xhosa',
//  translate ( 'Yakut' )            => 'Yakut',
//  translate ( 'Yao' )              => 'Yao',
//  translate ( 'Yapese' )           => 'Yapese',
    translate ( 'Yiddish' )          => 'Yiddish',
    translate ( 'Yorùbá' )           => 'Yoruba',
//  translate ( 'Yupik' )            => 'Yupik',
//  translate ( 'Zande' )            => 'Zande',
//  translate ( 'Zapotec' )          => 'Zapotec',
//  translate ( 'Zaza' )             => 'Zaza',
//  translate ( 'Zenaga' )           => 'Zenaga',
//  translate ( 'Zhuang' )           => 'Zhuang',
    translate ( 'Zulu' )             => 'Zulu',

    // Add new languages here!
  ];
  // Sort languages in translated order.
  asort ( $languages );
  // Make sure Browser Defined is first in list.
  $languages = array_merge ( [translate ( 'Browser-defined' ) => 'none'], $languages );
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
 * Not sure what the abbreviation is? Check out:
 * http://www.metamodpro.com/browser-language-codes
 * and/or
 * https://www.loc.gov/standards/iso639-2/php/code_list.php
 *
 * Commented lines and
 * dashed codes, ie;
 *  'ar-AE' => ...
 *  'de-AT' => ...
 * in the array below are for future expansion. Maybe.
 */
$browser_languages = [
//'aa'    => 'Afar',
//'ab'    => 'Abkhazian',
//'ae'    => 'Avestan',
  'af'    => 'Afrikaans',
//'ak'    => 'Akan',
  'am'    => 'Amharic',
//'an'    => 'Aragonese',
  'ar'    => 'Arabic',
  'ar-AE' => 'Arabic', // U.A.E.
  'ar-BH' => 'Arabic', // Bahrain
  'ar-DZ' => 'Arabic', // Algeria
  'ar-EG' => 'Arabic', // Egypt
  'ar-IQ' => 'Arabic', // Iraq
  'ar-JO' => 'Arabic', // Jordan
  'ar-KW' => 'Arabic', // Kuwait
  'ar-LB' => 'Arabic', // Lebanon
  'ar-LY' => 'Arabic', // Libya
  'ar-MA' => 'Arabic', // Morocco
  'ar-OM' => 'Arabic', // Oman
  'ar-QA' => 'Arabic', // Qatar
  'ar-SA' => 'Arabic', // Saudi Arabia
  'ar-SY' => 'Arabic', // Syria
  'ar-TN' => 'Arabic', // Tunisia
  'ar-YE' => 'Arabic', // Yemen
//'as'    => 'Assamese',
//'ast'   => 'Asturian',
//'av'    => 'Avaric',
//'ay'    => 'Aymara',
  'az'    => 'Azerbaijani',
//'ba'    => 'Bashkir',
  'be'    => 'Belarusian',
  'bg'    => 'Bulgarian',
//'bh'    => 'Bihari',
//'bi'    => 'Bislama',
//'bm'    => 'Bambara',
  'bn'    => 'Bengali',
//'bo'    => 'Tibetan',
//'br'    => 'Breton',
//'bra'   => 'Braj',
  'bs'    => 'Bosnian',
  'ca'    => 'Catalan',
//'ce'    => 'Chechen',
//'ch'    => 'Chamorro',
  'co'    => 'Corsican',
//'cr'    => 'Cree',
  'cs'    => 'Czech',
//'cu'    => 'Slavic',
//'cv'    => 'Chuvash',
  'cy'    => 'Welsh',
  'da'    => 'Danish',
  'de'    => 'German',
  'de-AT' => 'German', // Austria
  'de-CH' => 'German', // Switzerland
  'de-DE' => 'German', // Germany
  'de-LI' => 'German', // Liechtenstein
  'de-LU' => 'German', // Luxembourg
//'dv'    => 'Maldivian',
//'dz'    => 'Dzongkha',
//'ee'    => 'Ewe',
  'ee'    => 'Estonian',
  'el'    => 'Greek',
  'en'    => 'English-US',
  'en-AU' => 'English-US', // Australia
  'en-BZ' => 'English-US', // Belize
  'en-CA' => 'English-US', // Canada
  'en-GB' => 'English-US', // United Kingdom
  'en-IE' => 'English-US', // Ireland
  'en-JM' => 'English-US', // Jamaica
  'en-NZ' => 'English-US', // New Zealand
  'en-PH' => 'English-US', // Philippines
  'en-TT' => 'English-US', // Trinidad & Tobago
  'en-US' => 'English-US', // United States
  'en-ZA' => 'English-US', // South Africa
  'en-ZW' => 'English-US', // Zimbabwe
  'eo'    => 'Esperanto',
  'es'    => 'Spanish',
  'es-AR' => 'Spanish', // Argentina
  'es-BO' => 'Spanish', // Bolivia
  'es-CL' => 'Spanish', // Chile
  'es-CO' => 'Spanish', // Colombia
  'es-CR' => 'Spanish', // Costa Rica
  'es-DO' => 'Spanish', // Dominican Republic
  'es-EC' => 'Spanish', // Ecuador
  'es-ES' => 'Spanish', // Spain
  'es-GT' => 'Spanish', // Guatemala
  'es-HN' => 'Spanish', // Honduras
  'es-MX' => 'Spanish', // Mexico
  'es-NI' => 'Spanish', // Nicaragua
  'es-PA' => 'Spanish', // Panama
  'es-PE' => 'Spanish', // Peru
  'es-PR' => 'Spanish', // Puerto Rico
  'es-PY' => 'Spanish', // Paraguay
  'es-SV' => 'Spanish', // El Salvador
  'es-UY' => 'Spanish', // Uruguay
  'es-VE' => 'Spanish', // Venezuela
  'et'    => 'Estonian',
  'eu'    => 'Basque',
  'fa'    => 'Persian', // Farsi
  'fa-IR' => 'Persian', // Iran
//'ff'    => 'Fulah',
  'fi'    => 'Finnish',
//'fj'    => 'Fijian',
//'fo'    => 'Faroese',
  'fr'    => 'French',
  'fr-BE' => 'French', // Belgium
  'fr-CA' => 'French', // Canada
  'fr-CH' => 'French', // Switzerland
  'fr-FR' => 'French', // France
  'fr-LU' => 'French', // Luxembourg
  'fr-MC' => 'French', // Monaco
//'fur'   => 'Friulian',
  'fy'    => 'Frisian',
  'ga'    => 'Irish',
  'gd'    => 'Scots', // Gaelic
  'gd-IE' => 'Irish', // Gaelic
  'gl'    => 'Galician',
//'gn'    => 'Guarani',
  'gu'    => 'Gujarati',
//'gv'    => 'Manx',
  'ha'    => 'Hausa',
  'he'    => 'Hebrew',
  'hi'    => 'Hindi',
//'ho'    => 'Hiri-Motu',
  'hr'    => 'Croatian',
//'hsb'   => 'Sorbian', // Upper
  'ht'    => 'Haitian',
  'hu'    => 'Hungarian',
  'hy'    => 'Armenian',
//'hz'    => 'Herero',
  'id'    => 'Indonesian',
  'ig'    => 'Igbo',
//'ii'    => 'Sichuan-Yi',
//'ik'    => 'Inupiaq',
//'io'    => 'Ido',
  'is'    => 'Icelandic',
  'it'    => 'Italian',
  'it-CH' => 'Italian', // Switzerland
//'iu'    => 'Inuktitut',
  'ja'    => 'Japanese',
  'jv'    => 'Javanese',
  'ka'    => 'Georgian',
//'kg'    => 'Kongo',
//'ki'    => 'Kikuyu',
//'kj'    => 'Kuanyama',
  'kk'    => 'Kazakh',
//'kl'    => 'Greenlandic',
  'km'    => 'Khmer',
  'kn'    => 'Kannada',
  'ko'    => 'Korean',
  'ko'    => 'Korean', // Johab
  'ko-KP' => 'Korean', // North
  'ko-KR' => 'Korean', // South
//'kr'    => 'Kanuri',
//'ks'    => 'Kashmiri',
  'ku'    => 'Kurdish',
//'kv'    => 'Komi',
//'kw'    => 'Cornish',
  'ky'    => 'Kyrgyz',
  'la'    => 'Latin',
  'lb'    => 'Luxembourgish',
//'lg'    => 'Ganda',
//'li'    => 'Limburgan',
//'ln'    => 'Lingala',
  'lo'    => 'Lao',
  'lt'    => 'Lithuanian',
//'lu'    => 'Luba-Katanga',
  'lv'    => 'Latvian',
  'mg'    => 'Malagasy',
//'mh'    => 'Marshallese',
  'mi'    => 'Maori',
  'mk'    => 'Macedonian', // FYROM
  'ml'    => 'Malayalam',
  'mo'    => 'Mongolian',
//'mo'    => 'Moldavian',
  'mr'    => 'Marathi',
  'ms'    => 'Malay',
  'mt'    => 'Maltese',
  'my'    => 'Myanmar',
//'na'    => 'Nauru',
  'nb'    => 'Norwegian', // Bokmål
//'nd'    => 'Ndebele',
  'ne'    => 'Nepali',
//'ng'    => 'Ndonga',
  'nl'    => 'Dutch',
  'nl-BE' => 'Dutch', // Belgium
  'nn'    => 'Norwegian', // Nynorsk
  'no'    => 'Norwegian',
//'nr'    => 'Ndebele',
//'nv'    => 'Navajo',
  'ny'    => 'Chichewa',
//'oc'    => 'Occitan',
//'oj'    => 'Ojibwa',
//'om'    => 'Oromo',
  'or'    => 'Oriya',
//'os'    => 'Ossetic',
  'pa'    => 'Punjabi',
  'pa-IN' => 'Punjabi', // India
  'pa-PK' => 'Punjabi', // Pakistan
//'pi'    => 'Pali',
  'pl'    => 'Polish',
  'ps'    => 'Pashto',
  'pt'    => 'Portuguese', //    Portugal
  'pt-BR' => 'Portuguese_BR', // Brazil
//'qu'    => 'Quechua',
//'rm'    => 'Rhaeto-Romanic',
//'rm'    => 'Romansh',
//'rn'    => 'Rundi',
  'ro'    => 'Romanian',
  'ru'    => 'Russian',
  'ru-MD' => 'Russian', // Republic of Moldova
  'ru-MO' => 'Russian', // Moldavia
  'ru-RU' => 'Russian', // Safari reports this
  'rw'    => 'Kinyarwanda',
//'sa'    => 'Sanskrit',
//'sb'    => 'Sorbian',
//'sc'    => 'Sardinian',
  'sd'    => 'Sindhi',
//'se'    => 'Sami', // Northern
//'sg'    => 'Sango',
  'si'    => 'Singhala',
  'si'    => 'Sinhala',
  'sk'    => 'Slovak',
  'sl'    => 'Slovenian',
  'sm'    => 'Samoan',
  'sn'    => 'Shona',
  'so'    => 'Somali',
//'so'    => 'Somani',
  'sq'    => 'Albanian',
  'sr'    => 'Serbian',
//'ss'    => 'Swati',
  'st'    => 'Sesotho',
  'su'    => 'Sundanese',
  'sv'    => 'Swedish',
  'sv-FI' => 'Swedish', // Finland
  'sv-SV' => 'Swedish', // Sweden
  'sw'    => 'Swahili',
//'sx'    => 'Sutu',
//'sz'    => 'Sami', // Lappish
  'ta'    => 'Tamil',
  'te'    => 'Telugu',
  'tg'    => 'Tajik',
  'th'    => 'Thai',
//'ti'    => 'Tigrinya',
//'tig'   => 'Tigre',
  'tk'    => 'Turkmen',
//'tl'    => 'Tagalog',
//'tlh'   => 'Klingon', // Yes, really!
//'tn'    => 'Tswana',
//'to'    => 'Tonga',
  'tr'    => 'Turkish',
//'ts'    => 'Tsonga',
  'tt'    => 'Tatar',
//'tw'    => 'Twi',
//'ty'    => 'Tahitian',
  'ug'    => 'Uyghur',
  'uk'    => 'Ukrainian',
  'ur'    => 'Urdu',
  'uz'    => 'Uzbek',
//'ve'    => 'Venda',
  'vi'    => 'Vietnamese',
//'vo'    => 'Volapuk',
//'wa'    => 'Walloon',
//'wo'    => 'Wolof',
  'xh'    => 'Xhosa',
  'ji'    => 'Yiddish', // Code depends where you look.
  'yi'    => 'Yiddish', // Code depends where you look.
  'yo'    => 'Yoruba',
//'za'    => 'Zhuang',
  'zh'    => 'Chinese-Simplified',
  'zh-CN' => 'Chinese-Simplified', // PRC
  'zh-HK' => 'Chinese-Simplified', // Hong Kong
  'zh-min-nan-tw' => 'Holo-Big5', //  Taiwan
  'zh-SG' => 'Chinese-Simplified' //  Singapore
  'zh-TW' => 'Holo-Big5' //           Taiwan
  'zu'    => 'Zulu',
];

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
