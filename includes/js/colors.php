<?php
global $color;
?>

<SCRIPT LANGUAGE="JavaScript">
function sendColor ( color ) {
  window.opener.document.prefform.<?php echo $color?>.value= color;
  window.close ();
}
</SCRIPT>