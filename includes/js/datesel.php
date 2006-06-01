<?php /* $Id$  */ 
defined( '_ISVALID' ) or die( "You can't access this file directly!" );

$form = $arinc[3];
$fmonth = $arinc[4];
$fday = $arinc[5];
$fyear = $arinc[6];
?>

function sendDate ( date ) {
  year = date.substring ( 0, 4 );
  month = date.substring ( 4, 6 );
  day = date.substring ( 6, 8 );
  window.opener.document.<?php echo $form ?>.<?php echo $fday ?>.selectedIndex = day - 1;
  window.opener.document.<?php echo $form ?>.<?php echo $fmonth ?>.selectedIndex = month - 1;
  for ( i = 0; i < window.opener.document.<?php echo $form ?>.<?php echo $fyear ?>.length; i++ ) {
    if ( window.opener.document.<?php echo $form ?>.<?php echo $fyear ?>.options[i].value == year ) {
      window.opener.document.<?php echo $form ?>.<?php echo $fyear ?>.selectedIndex = i;
    }
  }
  window.close ();
}
