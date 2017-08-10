<?php
/**
 * Description:
 *  Generate a gradient image for use as a background image.
 *  Requires gd module.
 *
 * Input Parameters:
 *  height     Height of output image (ignored for horizontal gradient)
 *   width     Width of output image (ignored for vertical gradient)
 *  colors     Number of colors to generate
 *  direction  Direction gradient should go
 *             Currently limited to multiples of 90 degrees
 *             0 means left-to-right, 90 means bottom-to-top,
 *             180 means right-to-left, 270 means top-to-bottom
 *
 * Notes:
 *  One of the following two pairs of input parameters should be used
 *  to specify the colors for the gradient:
 *    1. color1, color2 - End colors of gradient specified
 *       as "color1=RRGGBB" or "color1=RGB"
 *    2. base, percent - `base' is specified the same way as
 *       `color1' and `color2'
 *       `percent' is the amount the components of `base'
 *        should be increased
 *  For example, given "base=445566&percent=50", the starting and
 *  ending colors of the gradient will be:
 *    Start:
 *      Red: 44 (hex) | 68 (dec)
 *      Green: 55 (hex) | 85 (dec)
 *      Blue: 66 (hex) | 102 (dec)
 *    End:
 *      Red: 44 + (50% of FF) =
 *           CC (hex) | 68 + (50% of 255) = 196 (dec)
 *      Green: 55 + (50% of FF) =
 *           DD (hex) | 85 + (50% of 255) = 203 (dec)
 *      Blue: 66 + (50% of FF) =
 *           EE (hex) | 102 + (50% of 255) = 230 (dec)
 *
 *  So it is entirely equivalent to say "base=445566&percent=50" OR
 *  "color1=445566&color2=CCDDEE"
 *
 *  Since this file does not use any other WebCalendar file, it could
 *  be used by other PHP apps.
 *
 * TODO:
 *  Allow directions which are not multiples of 90 degrees so that
 *  we can have diagonal gradients.
 *
 * Security:
 *  No security restrictions by user.
 *  Limit height and width parameters to 600 so a malicious user cannot
 *  request a 10Gb image 8-)
 */

// We don't really need it if calling gradients.php standalone.
if ( file_exists ( 'includes/formvars.php' ) )
  include_once 'includes/formvars.php';
//we may be calling gradients directly, so the path will be different
if ( file_exists ( 'formvars.php' ) )
  include_once 'formvars.php';

$MIN_COLORS = 4;
$MAX_COLORS = 256;
$MAX_HEIGHT = $MAX_WIDTH = 600;
$DEFAULTS = [
  'color1' => 'ccc',
  'color2' => 'eee',
  'colors' => 32,
  'direction' => 90,
  'height' => 50,
  'percent' => 15,
  'width' => 50];

if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) && !
    empty ( $_SERVER['PHP_SELF'] ) )
  $PHP_SELF = $_SERVER['PHP_SELF'];
// are we calling this file directly with GET parameters
if ( ! empty ( $_GET ) && ! empty ( $PHP_SELF ) &&
    preg_match ( "/\/includes\/gradient.php/", $PHP_SELF ) ) {
  if ( function_exists ( 'getGetValue' ) ) {
    $base      = getGetValue ( 'base' );
    $color1    = getGetValue ( 'color1' );
    $color2    = getGetValue ( 'color2' );
    $direction = getGetValue ( 'direction' );
    $height    = getGetValue ( 'height' );
    $numcolors = getGetValue ( 'colors' );
    $percent   = getGetValue ( 'percent' );
    $width     = getGetValue ( 'width' );
  } else {
    $base      = ( ! empty ( $_GET['base'] ) ? $_GET['base'] : '' );
    $color1    = ( ! empty ( $_GET['color1'] ) ? $_GET['color1'] : '' );
    $color2    = ( ! empty ( $_GET['color2'] ) ? $_GET['color2'] : '' );
    $direction = ( ! empty ( $_GET['direction'] ) ? $_GET['direction'] : '' );
    $height    = ( ! empty ( $_GET['height'] ) ? $_GET['height'] : '' );
    $numcolors = ( ! empty ( $_GET['colors'] ) ? $_GET['colors'] : '' );
    $percent   = ( ! empty ( $_GET['percent'] ) ? $_GET['percent'] : '' );
    $width     = ( ! empty ( $_GET['width'] ) ? $_GET['width'] : '' );
  }

  create_image ( '', $base, $height, $percent, $width,
    $direction, $numcolors, $color1, $color2 );
}

/**
 * Turn an HTML color (like 'AABBCC') into an array of decimal RGB values.
 *
 * Parameters:
 *  $color - HTML color specification in 'RRGGBB' or 'RGB' format
 *
 * Return value:
 *   ['red' => $red_val, 'green' => $green_val, 'blue' => $blue_val]
 */
function colorToRGB ( $color ) {
  if ( strlen ( $color ) == 6 ) {
    $red = hexdec ( substr ( $color, 0, 2 ) );
    $green = hexdec ( substr ( $color, 2, 2 ) );
    $blue = hexdec ( substr ( $color, 4, 2 ) );
  } elseif ( strlen ( $color ) == 3 ) {
    $red_hex = substr ( $color, 0, 1 );
    $red = hexdec ( $red_hex . $red_hex );

    $green_hex = substr ( $color, 1, 1 );
    $green = hexdec ( $green_hex . $green_hex );

    $blue_hex = substr ( $color, 2, 1 );
    $blue = hexdec ( $blue_hex . $blue_hex );
  } else
    // Invalid color specification
    return false;

  return ['red' => $red, 'green' => $green, 'blue' => $blue];
}
/**
 * can_write_to_dir (needs description)
 */
function can_write_to_dir ($path)
{
  if ( $path { strlen ( $path ) - 1 } == '/' ) //Start function again with tmp file...
    return can_write_to_dir ( $path.uniqid ( mt_rand() ) . '.tmp');
  else if ( preg_match( '/\.tmp$/', $path ) ) { //Check tmp file for read/write capabilities
    if ( ! ( $f = @fopen ( $path, 'w+' ) ) )
      return false;

    fclose ( $f );
    unlink ( $path );
    return true;
  }
  else //We have a path error.
   return 0; // Or return error - invalid path...
}
/**
 * background_css (needs description)
 */
function background_css ( $base, $height = '', $percent = '' ) {
  global $ENABLE_GRADIENTS;

  $ret = $type = '';

  if ( function_exists ( 'imagepng' ) )
    $type = '.png';
  elseif ( function_exists ( 'imagegif' ) )
    $type = '.gif';

  $ret = 'background';
  if ( $type != '' && $ENABLE_GRADIENTS == 'Y' ) {
    $ret .= ': ' . $base . ' url( ';
    if ( ! file_exists ( 'images/cache' ) || ! can_write_to_dir ( 'images/cache/' ) )
      $ret .= 'includes/gradient.php?base=' . substr ( $base, 1 )
       . ( $height != '' ? '&height=' . $height : '' )
       . ( $percent != '' ? '&percent=' . $percent : '' );
    else {
      $file_name = 'images/cache/' . substr ( $base, 1, 6 )
       . ( $height != '' ? '-' . $height : '' )
       . ( $percent != ''? '-' . $percent : '' ) . $type;
      if ( ! file_exists ( $file_name ) )
        $tmp = create_image ( $file_name, $base, $height, $percent );

      $ret .= $file_name;
    }
    $ret .= ' ) repeat-x';
  } else
    $ret .= '-color: ' . $base;

  return $ret . ';';
}
/**
 * create_image (needs description)
 */
function create_image ( $file_name, $base = '', $height = '', $percent = '',
  $width = '', $direction = '', $numcolors = '', $color1 = '', $color2 = '' ) {
  global $DEFAULTS, $MAX_COLORS, $MAX_HEIGHT, $MAX_WIDTH, $MIN_COLORS;

  if ( $base != '' )
    $color1 = $color2 = $base;

  $color1 = ( $color1 == ''
    ? colorToRGB ( $DEFAULTS['color1'] )
    : ( preg_match ( "/^#?([0-9a-fA-F]{3,6})/", $color1, $matches )
      ? colorToRGB ( $matches[1] )
      : colorToRGB ( $DEFAULTS['color1'] ) ) );

  $color2 = ( $color2 == ''
    ? colorToRGB ( $DEFAULTS['color2'] )
    : ( preg_match ( "/^#?([0-9a-fA-F]{3,6})/", $color2, $matches )
      ? colorToRGB ( $matches[1] )
      : colorToRGB ( $DEFAULTS['color2'] ) ) );

  if ( empty ( $height ) )
    $height = $DEFAULTS['height'];

  if ( $height > $MAX_HEIGHT )
    $height = $MAX_HEIGHT;

  if ( $direction == '' || ( $direction % 90 ) != 0 )
    $direction = $DEFAULTS['direction'];
  else {
    while ( $direction > 360 ) {
      $direction -= 360;
    }
  }

  if ( $direction == 90 || $direction == 270 ) {
    // Vertical gradient
    if ( empty ( $height ) )
      $height = $DEFAULTS['height'];

    if ( $height > $MAX_HEIGHT )
      $height = $MAX_HEIGHT;

    $width = 1;
  } else {
    // Horizontal gradient
    if ( empty ( $width ) )
      $width = $DEFAULTS['width'];

    if ( $width > $MAX_WIDTH )
      $width = $MAX_WIDTH;

    $height = 1;
  }

  if ( empty ( $numcolors ) )
    $numcolors = $DEFAULTS['colors'];
  else {
    if ( preg_match ( '/^\d+$/', $numcolors ) ) {
      if ( $numcolors < $MIN_COLORS )
        $numcolors = $MIN_COLORS;
      else
      if ( $numcolors > $MAX_COLORS )
        $numcolors = $MAX_COLORS;
    } else
      $numcolors = $DEFAULTS['colors'];
  }

  if ( $percent == '' || $percent < 0 || $percent > 100 )
    $percent = $DEFAULTS['percent'];

  $percent *= 2.55;

  $color2['red'] = min ( $color2['red'] + $percent, 255 );
  $color2['green'] = min ( $color2['green'] + $percent, 255 );
  $color2['blue'] = min ( $color2['blue'] + $percent, 255 );

  $image = imagecreate ( $width, $height );
  // Allocate array of colors
  $colors = [];

  $deltared = $color2['red'] - $color1['red'];
  $deltagreen = $color2['green'] - $color1['green'];
  $deltablue = $color2['blue'] - $color1['blue'];

  $tmp_c = $numcolors - 1;

  for ( $i = 0; $i < $numcolors; $i++ ) {
    $thisred =
    floor ( min ( $color1['red'] + ( $deltared * $i / $tmp_c ), 255 ) );

    $thisgreen =
    floor ( min ( $color1['green'] + ( $deltagreen * $i / $tmp_c ), 255 ) );

    $thisblue =
    floor ( min ( $color1['blue'] + ( $deltablue * $i / $tmp_c ), 255 ) );

    $colors[$i] = imagecolorallocate ( $image, $thisred, $thisgreen, $thisblue );
  }

  $dim = $width;

  $dx = $dy = $i = $x1 = $y1 = 0;

  $x2 = $width - 1;
  $y2 = $height - 1;

  switch ( $direction ) {
    case 0:
      $dx = 1;

      $x2 = 0;
      break;
    case 90:
      $dim = $height;

      $dy = -1;

      $y1 = $height - 1;
      break;
    case 180:
      $dx = -1;

      $x1 = $width - 1;
      break;
    case 270:
      $dim = $height;

      $dy = 1;

      $y2 = 0;
      break;
  } while ( $x1 >= 0 && $x1 < $width
         && $x2 >= 0 && $x2 < $width
         && $y1 >= 0 && $y1 < $height
         && $y2 >= 0 && $y2 < $height ) {
    // Which color for this line?
    $ind = floor ( $numcolors * $i / $dim );
    if ( $ind >= $numcolors )
      $ind = $numcolors;

    imageline ( $image, $x1, $y1, $x2, $y2, $colors[$ind] );

    $x1 += $dx;
    $y1 += $dy;

    $x2 += $dx;
    $y2 += $dy;

    $i++;
  }

  if ( function_exists ( 'imagepng' ) ) {
    if ( $file_name == '' ) {
      header ( 'Content-type: image/png' );
      imagepng ( $image );
    } else
      imagepng ( $image, $file_name );
  } elseif ( function_exists ( 'imagegif' ) ) {
    if ( $file_name == '' ) {
      header ( 'Content-type: image/gif' );
      imagegif ( $image );
    } else
      imagegif ( $image, $file_name );
  } else
    echo 'No image formats supported!<br />' . "\n";

  imagedestroy ( $image );
  return;
}

/**
 * General purpose functions to convert RGB to HSL and HSL to RBG
 */
function  rgb2hsl ( $rgb ) {
  if ( substr ($rgb, 0,1 ) == '#' )
     $rgb = substr ( $rgb,1,6);

  $R = ( hexdec (substr ( $rgb,0,2) ) / 255 );
  $G = ( hexdec (substr ( $rgb,2,2) ) / 255 );
  $B = ( hexdec (substr ( $rgb,4,2) ) / 255 );

  $Min = min ( $R, $G, $B );    //Min. value of RGB
  $Max = max( $R, $G, $B );    //Max. value of RGB
  $deltaMax = $Max - $Min;     //Delta RGB value
  $L = ( $Max + $Min ) / 2;

  if ( $deltaMax == 0 )      //This is a gray, no chroma...
  {
     $H = 0;                  //HSL results = 0 ÷ 1
     $S = 0;
  }
  else                        //Chromatic data...
  {
     if ( $L < 0.5 )
       $S = $deltaMax / ( $Max + $Min );
     else
       $S = $deltaMax / ( 2 - $Max - $Min );

     $deltaR = ( ( ( $Max - $R ) / 6 ) + ( $deltaMax / 2 ) ) / $deltaMax;
     $deltaG = ( ( ( $Max - $G ) / 6 ) + ( $deltaMax / 2 ) ) / $deltaMax;
     $deltaB = ( ( ( $Max - $B ) / 6 ) + ( $deltaMax / 2 ) ) / $deltaMax;

     if ( $R == $Max )
       $H = $deltaB - $deltaG;
     else if ( $G == $Max )
       $H = ( 1 / 3 ) + $deltaR - $deltaB;
     else if ( $B == $Max )
      $H = ( 2 / 3 ) + $deltaG - $deltaR;

     if ( $H < 0 ) $H += 1;
     if ( $H > 1 ) $H -= 1;
  }
  return [$H, $S, $L];
}

function hsl2rgb( $hsl ) {
  if ( $hsl[1] == 0 )
  {
     $R = $hsl[2] * 255;
     $G = $hsl[2] * 255;
     $B = $hsl[2] * 255;
  }
  else
  {
     if ( $hsl[2] < 0.5 )
       $var_2 = $hsl[2] * ( 1 + $hsl[1] );
     else
       $var_2 = ( $hsl[2] + $hsl[1] ) - ( $hsl[1] * $hsl[2] );

     $var_1 = 2 * $hsl[2]- $var_2;

     $R = 255 * Hue_2_RGB( $var_1, $var_2, $hsl[0] + ( 1 / 3 ) );
     $G = 255 * Hue_2_RGB( $var_1, $var_2, $hsl[0] );
     $B = 255 * Hue_2_RGB( $var_1, $var_2, $hsl[0] - ( 1 / 3 ) );
  }
  $R = sprintf ( "%02X",round ( $R));
  $G = sprintf ( "%02X",round ( $G));
  $B = sprintf ( "%02X",round ( $B));

  $rgb = '#' . $R . $G . $B;

  return $rgb;
}

function Hue_2_RGB( $v1, $v2, $vH ) {
   if ( $vH < 0 ) $vH += 1;
   if ( $vH > 1 ) $vH -= 1;
   if ( ( 6 * $vH ) < 1 ) return ( $v1 + ( $v2 - $v1 ) * 6 * $vH );
   if ( ( 2 * $vH ) < 1 ) return ( $v2 );
   if ( ( 3 * $vH ) < 2 ) return ( $v1 + ( $v2 - $v1 ) * ( ( 2 / 3 ) - $vH ) * 6 );
   return ( $v1 );
}

/**
 * Given an RGB value, return it's luminance adjusted by scale
 * scale range = 0 to 9
 */
function rgb_luminance ( $rgb, $scale=5) {
  $luminance = [.44, .50, .56, .62, .68, .74, .80, .86, .92, .98];
  if ( $scale < 0 ) $scale = 0;
  if ( $scale > 9 ) $scale = 9;
  $new = rgb2hsl ( $rgb );
  $new[2] = $luminance[ round ( $scale )];
  $newColor = hsl2rgb( $new );
  return $newColor;
}

?>
