<?php
/*
 * All functions related to AJAX and JSON processing.=
 *
 * @author Craig Knudsen <craig@k5n.us>
 * @copyright Craig Knudsen, <craig@k5n.us>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */


/**
 * Send an object back to the AJAX request. This represents a successful
 * AJAX request. Using a common return structure for all of our AJAX
 * responses make them easier to handle in the client-side JavaScript.
 *
 * @param  string   $objectName    The name of the object we are sending
 * @param  object   $object        The object to send
 * @param  boolean $sendPlainText  (Optional) Set to true to use plain/text
 *                                 as the Content-type.
 */
function ajax_send_object ( $objectName, $object, $sendPlainText=false ) {
  // Plain text can be helpful for debugging in the browser.
  if ( $sendPlainText )
    Header ( 'Content-Type: text/plain' );
  else
    Header ( 'Content-Type: text/json' );
  $ret = [
    "error" => 0,
    "status" => 'OK',
    "message" => '',
    $objectName => $object];
  echo json_encode($ret);
  return true;
}

/**
 * Send a objects back to the AJAX request. This represents a successful
 * AJAX request. Using a common return structure for all of our AJAX
 * responses make them easier to handle in the client-side JavaScript.
 *
 * @param  array   $objects  array of objects with the object name as
 *                           the key.
 * @param  boolean $sendPlainText  (Optional) Set to true to use plain/text
 *                           as the Content-type.
 */
function ajax_send_objects ( $objectArray, $sendPlainText=false ) {
  // Plain text can be helpful for debugging in the browser.
  if ( $sendPlainText )
    Header ( 'Content-Type: text/plain' );
  else
    Header ( 'Content-Type: text/json' );
  $ret = [
    "error" => 0,
    "status" => 'OK',
    "message" => ''];
  foreach ( $objectArray as $name => $value ) {
    $ret[$name] = $value;
  }
  echo json_encode($ret);
  return true;
}

/**
 * Send a success message back to our AJAX client.
 * Using a common return structure for all of our AJAX
 * responses make them easier to handle in the client-side JavaScript.
 *
 * @param  boolean $sendPlainText  (Optional) Set to true to use plain/text
 *        as the Content-type.
 */
function ajax_send_success($sendPlainText = false, $message = '')
{
  // Plain text can be helpful for debugging in the browser.
  if ($sendPlainText)
    Header('Content-Type: text/plain');
  else
    Header('Content-Type: text/json');
  $ret = [
    "error" => 0,
    "status" => 'OK',
    "message" => $message
  ];
  echo json_encode($ret);
  return true;
}

/**
 * Send a failure/fault back to the AJAX request. This represents a failed
 * AJAX request.
 *
 * @param  string   $errorMessage  The error message to send back to
 *        the user. This may be displayed to the
 *        end user and should be translated into
 *        the proper user language.
 * @param  boolean $sendPlainText  (Optional) Set to true to use plain/text
 *        as the Content-type.
 */
function ajax_send_error($errorMessage, $sendPlainText = false)
{
  // Plain text can be helpful for debugging in the browser.
  if ($sendPlainText)
    Header('Content-Type: text/plain');
  else
    Header('Content-Type: text/json');
  $ret = [
    "error" => 1,
    "status" => 'ERROR',
    "message" => $errorMessage
  ];
  echo json_encode($ret);
  return true;
}


?>
