<?php
/**
 * Represents a list of Doc comment objects.
 *
 * @author Craig Knudsen
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://k5n.us/
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 * @subpackage Doc
 */
/**
 * A list of Doc comment objects.
 */
class CommentList extends DocList {
  /**
   * __construct
   *
   * @param  int    $event_id
   * @return CommentList The new object for the specified event.
   * @access public
   */
  function __construct ( $event_id ) {
    parent::__construct ( $event_id, 'C' );
  }
}
?>
