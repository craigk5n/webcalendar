<?php
global $color;
if (preg_match("/colors\.php/", $PHP_SELF)) {
    die ("You can't access this file directly!");
}
?>

<script type="text/javascript">
<!-- <![CDATA[
function sendColor ( color ) {
  window.opener.document.prefform.<?php echo $color?>.value= color;
  window.close ();
}
//]]> -->
</script>
