<?php
/*
 * $Id$
 *
 * Description:
 *	Generate a gradient image for use as a background image.
 *	Required gd module.
 *
 * Input Parameters:
 *	height - Height of output image (width is 1)
 *	colors - Number of colors to generate
 *	base - Base color specified as "base=FFC0C0" or "base=#FFC0C0"
 *
 * Security:
 *	No security restrictions by user.
 *	Limit height parameter to 600.
 */

$MAX_HEIGHT = 600;
$MIN_COLORS = 4;
$MAX_COLORS = 256;
$DEFAULT_COLORS = 32;
$PERCENT = 15; // Percent to change brightness

$width = 5; // could be 1, doesn't really matter since browser will stretch it

// Get a value from a GET URL
function getGetValue ( $name ) {
  if ( ! empty ( $_GET[$name] ) )
    return $_GET[$name];
  if ( ! isset ( $HTTP_GET_VARS ) )
    return null;
  if ( ! isset ( $HTTP_GET_VARS[$name] ) )
    return null;
  return ( $HTTP_GET_VARS[$name] );
}

// Convert a hex value to an integer
function hextoint ( $val ) {
  if ( empty ( $val ) )
    return 0;
  switch ( strtoupper ( $val ) ) {
    case "0": return 0;
    case "1": return 1;
    case "2": return 2;
    case "3": return 3;
    case "4": return 4;
    case "5": return 5;
    case "6": return 6;
    case "7": return 7;
    case "8": return 8;
    case "9": return 9;
    case "A": return 10;
    case "B": return 11;
    case "C": return 12;
    case "D": return 13;
    case "E": return 14;
    case "F": return 15;
  }
  return 0;
}

$height = getGetValue ( "height" );
if ( empty ( $height ) )
  $height = 50;
if ( $height > $MAX_HEIGHT )
  $height = $MAX_HEIGHT;

$numcolors = getGetValue ( "colors" );
if ( empty ( $numcolors ) )
  $numcolors = $DEFAULT_COLORS;
else {
  if ( preg_match ( "/^\d+$/", $numcolors ) ) {
    if ( $numcolors < $MIN_COLORS )
      $numcolors = $MIN_COLORS;
    else if ( $numcolors > $MAX_COLORS )
      $numcolors = $MAX_COLORS;
  } else {
    $numcolors = $DEFAULT_COLORS;
  }
}

$percent = getGetValue ( "percent" );
if ( ! empty ( $percent ) ) {
  if ( preg_match ( "/^\d+$/", $percent ) ) {
    if ( $percent > 0 && $percent < 100 )
      $PERCENT = $percent;
  }
}

// Get base color
$red = $green = $blue = 192;
$base = getGetValue ( "base" );
if ( empty ( $base ) )
  $base = "C0C0C0";

if ( preg_match ( "/^#(\S+)/", $base, $matches ) ) {
  $base = $matches[1];
}
if ( preg_match ( "/^[0-9a-fA-F]+$/", $base ) ) {
  // valid color
} else {
  // Invalid color
  $base = "C0C0C0";
}

if ( strlen ( $base ) == 6 ) {
  $red = 16 * hextoint ( substr ( $base, 0, 1 ) ) +
    hextoint ( substr ( $base, 1, 1 ) );
  $green = 16 * hextoint ( substr ( $base, 2, 1 ) ) +
    hextoint ( substr ( $base, 3, 1 ) );
  $blue = 16 * hextoint ( substr ( $base, 4, 1 ) ) +
    hextoint ( substr ( $base, 5, 1 ) );
} else if ( strlen ( $base ) == 3 ) {
  $red = 16 * hextoint ( substr ( $base, 0, 1 ) );
  $green = 16 * hextoint ( substr ( $base, 1, 1 ) );
  $blue = 16 * hextoint ( substr ( $base, 2, 1 ) );
} else {
  // Invalid color specification
}

//echo "Base=$base<br />";
//echo "Red=$red<br />Green=$green<br />Blue=$blue<br />\n"; exit;
$image = imagecreate ( $width, $height );

// Allocate array of colors
$colors = array ();
$delta = 256 * $PERCENT / 100;
$deltared = $deltagreen = $deltablue = $delta;
if ( $red + $deltared > 255 )
  $deltared = 255 - $red;
if ( $green + $deltagreen > 255 )
  $deltagreen = 255 - $green;
if ( $blue + $deltablue > 255 )
  $deltablue = 255 - $blue;
//echo "deltared=$deltared<br />deltagreen=$deltagreen<br />deltablue=$deltablue<br />";
//echo "red=$red<br />green=$green<br />blue=$blue<br />";
for ( $i = 0; $i < $numcolors; $i++ ) {
  $thisdelta = ceil ($delta * $i / $numcolors);
  //echo "thisdelta=$thisdelta<br />";
  $thisred = $red + ( $deltared * $i / $numcolors );
  if ( $thisread > 255 )
    $thisread = 255;
  $thisgreen = $green + ( $deltagreen * $i / $numcolors );
  if ( $thisgreen > 255 )
    $thisgreen = 255;
  $thisblue = $blue + ( $deltablue * $i / $numcolors );
  if ( $thisblue > 255 )
    $thisblue = 255;
  $thisred = floor ( $thisred );
  $thisgreen = floor ( $thisgreen );
  $thisblue = floor ( $thisblue );
  $colors[$i] = imagecolorallocate ( $image, $thisred, $thisgreen, $thisblue );
  //echo "Color $i: $thisred $thisgreen $thisblue<br />";
}

for ( $i = 0; $i < $height; $i++ ) {
  // Which color for this line?
  $ind = floor ( $numcolors * $i / $height );
  if ( $ind >= $numcolors )
    $ind = $numcolors;
  $y = $height - $i - 1;
  imageline ( $image, 0, $y, $width - 1, $y,
    $colors[$ind] );
  //echo "Line $i, color $ind<br />";
}
//exit;

if ( function_exists ( "imagepng" ) ) {
  Header ( "Content-type: image/png" );
  imagepng ( $image );
} else if ( function_exists ( "imagegif" ) ) {
  Header ( "Content-type: image/gif" );
  imagegif ( $image );
} else {
  echo "No image formats supported!<br />\n";
}
imagedestroy ( $image );
?>
