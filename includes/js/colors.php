<?php
global $color;
if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}
$color = clean_word($color);
?>

<script type="text/javascript">
<!-- <![CDATA[
function sendColor ( color ) {
	var thisInput = window.opener.document.prefform.<?php echo $color; ?>;

  thisInput.value = color;
	if (thisInput.onkeyup) {
		// This updates the color swatch for this color input.  It relies on the
		// <input>s of the prefform having onkeyup="updateColor(this);" as an
		// attribute
		thisInput.onkeyup();
	}
  window.close ();
}
//]]> -->
</script>