<?php
global $form, $fmonth, $fday, $fyear;
if (preg_match("/\/includes\//", $PHP_SELF)) {
  die ("You can't access this file directly!");
}
?>

<script type="text/javascript">
<!-- <![CDATA[
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
//]]> -->
</script>
