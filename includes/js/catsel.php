<?php
/* $Id: catsel.php,v 1.12 2008/12/30 02:16:42 bbannon Exp $

It sucks to have 1 variable
messing up an otherwise nice javascript file...
*/
// $arinc[3] is the form name from the request; anything but a plain
// identifier would be script.
echo 'var arinctri = ' . ( preg_match( '/^[A-Za-z_]\w*$/', $arinc[3] ?? '' )
  ? $arinc[3] : 'null' ) . ';

';

?>
