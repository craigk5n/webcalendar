<?php
$form = $_GET['form'];
$month = $_GET['month'];
$day = $_GET['day'];
$year = $_GET['year'];
?>

<SCRIPT LANGUAGE="JavaScript">
function sendDate ( date ) {
  year = date.substring ( 0, 4 );
  month = date.substring ( 4, 6 );
  day = date.substring ( 6, 8 );
  window.opener.document.<?php echo $form?>.<?php echo $day?>.selectedIndex = day - 1;
  window.opener.document.<?php echo $form?>.<?php echo $month?>.selectedIndex = month - 1;
  for ( i = 0; i < window.opener.document.<?php echo $form?>.<?php echo $year?>.length; i++ ) {
    if ( window.opener.document.<?php echo $form?>.<?php echo $year?>.options[i].value == year ) {
      window.opener.document.<?php echo $form?>.<?php echo $year?>.selectedIndex = i;
    }
  }
  window.close ();
}
</SCRIPT>