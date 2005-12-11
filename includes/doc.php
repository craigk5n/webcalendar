<?php
/**
 * All functions related to documents (comments, attachments) in
 * the webcal_blob table.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */

/**
 * Are attachments enabled in System Settings.
 *
 * @return bool True if attachments are enabled
 */
function attachments_enabled ()
{
  global $ALLOW_ATTACH;

  return ( ! empty ( $ALLOW_ATTACH ) && $ALLOW_ATTACH == 'Y' );
}


/**
 * Get an array of attachment summaries.  (Does not include
 * the actual attachment data.)  For convenience, a 'summary' field
 * is included for each attachment that summarizes and includes
 * a link to download.
 *
 * @return Array	an array of attachments
 */
function get_attachment_list_for_event ( $event_id )
{
  $ret = array ();

  assert ( $event_id > 0 );

  $res = dbi_query ( "SELECT cal_blob_id, cal_id, cal_login, " .
    "cal_name, cal_description, " .
    "cal_size, cal_mime_type, cal_type, cal_mod_date, cal_mod_time " .
    "FROM webcal_blob WHERE cal_id = $event_id AND cal_type = 'A'" .
    " ORDER BY cal_mod_date, cal_mod_time" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $x = 0;
      $a = array (
        'cal_blob_id' =>  $row[$x++],
        'cal_id' =>  $row[$x++],
        'cal_login' =>  $row[$x++],
        'cal_name' => $row[$x++],
        'cal_description' => $row[$x++],
        'cal_size' => $row[$x++],
        'cal_mime_type' => $row[$x++],
        'cal_type' => $row[$x++],
        'cal_mod_date' => $row[$x++],
        'cal_mod_time' => $row[$x++]
      );
      // for convenience, also create a summary
      $a['summary'] = '<a href="doc.php?blid=' . $a['cal_blob_id'] .
        '">' . htmlspecialchars ( $a['cal_description'] ) . '</a>' .
        ' ( ' . htmlspecialchars ( $a['cal_name'] ) . ', ';
      if ( $a['cal_size'] < 1024 )
        $a['summary'] .= $a['cal_size'] . ' ' . translate ( 'bytes' );
      else if ( $a['cal_size'] < 1024 * 1024 )
        $a['summary'] .= sprintf ( " %.1f", ( $a['cal_size'] / 1024 ) ) .
          translate ( 'kb' );
      else
        $a['summary'] .= sprintf ( " %.1f", ( $a['cal_size'] / 1024 * 1024 ) )
          . translate ( 'Mb' );
      $a['summary'] .= ', ' .
        date_to_str ( $a['cal_mod_date'], '', false, true ) . ' )';
      $ret[] = $a;
    }
    dbi_free_result ( $res );
  }

  return $ret;
}




/**
 * Are comments enabled in System Settings.
 *
 * @return bool True if comments are enabled
 */
function comments_enabled ()
{
  global $ALLOW_COMMENTS;

  return ( ! empty ( $ALLOW_COMMENTS ) && $ALLOW_COMMENTS == 'Y' );
}



/**
 * Get an array of comments for the specified event.  Unlike
 * the attachment list, this DOES load the actual content.
 *
 * $param int $event_id	Event Id
 * $param int $load_blog	Should the comment blobs be loaded?
 * @return Array	an array of comments for the event
 */
function get_comments_for_event ( $event_id, $load_blob=true )
{
  $ret = array ();

  assert ( $event_id > 0 );

  $res = dbi_query ( "SELECT cal_blob_id, cal_id, cal_login, " .
    "cal_name, cal_description, " .
    "cal_size, cal_mime_type, cal_type, cal_mod_date, cal_mod_time " .
    "FROM webcal_blob WHERE cal_id = $event_id AND cal_type = 'C'" .
    " ORDER BY cal_mod_date, cal_mod_time" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $x = 0;
      $a = array (
        'cal_blob_id' =>  $row[$x++],
        'cal_id' =>  $row[$x++],
        'cal_login' =>  $row[$x++],
        'cal_name' => $row[$x++],
        'cal_description' => $row[$x++],
        'cal_size' => $row[$x++],
        'cal_mime_type' => $row[$x++],
        'cal_type' => $row[$x++],
        'cal_mod_date' => $row[$x++],
        'cal_mod_time' => $row[$x++],
      );
      if ( $load_blob ) {
        $a['cal_blob'] = dbi_get_blob ( 'webcal_blob', 'cal_blob',
          "cal_blob_id = " . $a['cal_blob_id'] );
      }
      $ret[] = $a;
    }
    dbi_free_result ( $res );
  }

  return $ret;
}


?>

