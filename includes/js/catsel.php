<?php
/* $Id: catsel.php,v 1.12 2008/12/30 02:16:42 bbannon Exp $

It sucks to have 1 variable
messing up an otherwise nice javascript file...
*/
// $arinc[3] comes from the request-controlled js_cacher 'inc' path and is
// emitted into JavaScript, so force it to an integer.
echo 'var arinctri = ' . (int) ( $arinc[3] ?? 0 ) . ';

';

?>
