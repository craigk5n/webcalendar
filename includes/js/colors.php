<?php
global $color;
?>

<script language="JavaScript">
function sendColor ( color ) {
  window.opener.document.prefform.<?php echo $color?>.value= color;
  window.close ();
}
</script>
