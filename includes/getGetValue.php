<?php
/* Gets the value resulting from an HTTP GET method.
 *
 * Since this function is used in more than one place, with different names,
 *  let's make it a seperate 'include' file on it's own.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * If you need to enforce a specific input format (such as numeric input), then
 * use the {@link getValue()} function.
 *
 * @param string  $name  Name used in the HTML form or found in the URL
 *
 * @return string        The value used in the HTML form (or URL)
 *
 * @see getPostValue
 */
function getGetValue ( $name ) {
  global $HTTP_GET_VARS;

  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) ) {
    $_GET[$name] = ( get_magic_quotes_gpc () != 0
      ? $_GET[$name] : addslashes ( $_GET[$name] ) );
    $HTTP_GET_VARS[$name] = $_GET[$name];
    return $_GET[$name];
  } else
  if ( ! isset ( $HTTP_GET_VARS ) || ! isset ( $HTTP_GET_VARS[$name] ) )
    return null;

  return ( $HTTP_GET_VARS[$name] );
}

?>
