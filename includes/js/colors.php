<?php
global $color;
?>

<script type="text/javascript">
function sendColor ( color ) {
  window.opener.document.prefform.<?php echo $color?>.value= color;
  window.close ();
}
</script>