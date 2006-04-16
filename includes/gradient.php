<?php
/*
 * $Id$
 *
 * Description:
 *  Generate a gradient image for use as a background image.
 *  Requires gd module.
 *
 * Input Parameters:
 *  height    Height of output image (ignored for horizontal gradient)
 *   width    Width of output image (ignored for vertical gradient)
 *  colors    Number of colors to generate
 *  direction  Direction gradient should go
 *      Currently limited to multiples of 90 degrees
 *      0 means left-to-right, 90 means bottom-to-top,
 *      180 means right-to-left, 270 means top-to-bottom
 *
 * Notes:
 *  One of the following two pairs of input parameters should be used
 *  to specify the colors for the gradient:
 *    1. color1, color2 - End colors of gradient specified
 *       as "color1=RRGGBB" or "color1=RGB"
 *    2. base, percent - `base' is specified the same way as
 *       `color1' and `color2'
 *       `percent' is the amount the components of `base'
 *       should be increased
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
 *             DD (hex) | 85 + (50% of 255) = 203 (dec)
 *      Blue: 66 + (50% of FF) =
 *            EE (hex) | 102 + (50% of 255) = 230 (dec)
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

 
$MAX_HEIGHT = 600;
$MAX_WIDTH = 600;
$MIN_COLORS = 4;
$MAX_COLORS = 256;
$DEFAULTS = array(
              'colors' => 32,
              'direction' => 90,
              'height' => 50,
              'width' => 50,
              'color1' => 'ccc',
              'color2' => 'eee',
              'percent' => 15
            );

if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}

//are we calling this file directly with GET parameters  
if ( ! empty ( $_GET ) && ! empty ( $PHP_SELF ) && 
  preg_match ( "/\/includes\/gradient.php/", $PHP_SELF ) ) {
  
  $direction = get_GetValue ( 'direction' );
  $height = get_GetValue ( 'height' );
  $width = get_GetValue ( 'width' );
  $numcolors = get_GetValue ( 'colors' );
  $base = get_GetValue ( 'base' );
  $percent = get_GetValue ( 'percent' );
    
  create_image ( '', $base, $height, $percent, $width, $direction, $numcolors );
}

// Get a value from a GET URL
function get_GetValue ( $name ) {
  global $HTTP_GET_VARS;

  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) ) {
  $_GET[$name] = ( get_magic_quotes_gpc () != 0? $_GET[$name]: addslashes ( $_GET[$name]) );
    $HTTP_GET_VARS[$name] = $_GET[$name];
  return $_GET[$name];
  } else if ( ! isset ( $HTTP_GET_VARS ) ) {
    return null;
  } else if ( ! isset ( $HTTP_GET_VARS[$name] ) ){
    return null;
 }
  return ( $HTTP_GET_VARS[$name] );
}

/*
 * Turn an HTML color (like 'AABBCC') into an array of decimal RGB values
 *
 * Parameters:
 *  $color - HTML color specification in 'RRGGBB' or 'RGB' format
 *
 * Return value:
 *  array('red' => $red_val, 'green' => $green_val, 'blue' => $blue_val)
 */
function colorToRGB ( $color ) {
  if ( strlen ( $color ) == 6 ) {
    $red = hexdec ( substr ( $color, 0, 2 ) );
    $green = hexdec ( substr ( $color, 2, 2 ) );
    $blue = hexdec ( substr ( $color, 4, 2 ) );
  } else if ( strlen ( $color ) == 3 ) {
    $red_hex = substr ( $color, 0, 1 );
    $red_hex .= $red_hex;

    $green_hex = substr ( $color, 1, 1 );
    $green_hex .= $green_hex;

    $blue_hex = substr ( $color, 2, 1 );
    $blue_hex .= $blue_hex;

    $red = hexdec ( $red_hex );
    $green = hexdec ( $green_hex );
    $blue = hexdec ( $blue_hex );
  } else {
    // Invalid color specification
    return false;
  }

  return array('red' => $red, 'green' => $green, 'blue' => $blue);
}

function background_css ( $base, $height = '', $percent = '' ) {
  global $ENABLE_GRADIENTS;
  $ret = $type = '';

  if ( function_exists ( "imagepng" ) ) {
    $type = ".png";
  } else if ( function_exists ( "imagepng" ) ){
    $type = ".gif";
  }
  if ( $type != '' ) {
    if ( ! file_exists( 'images/cache' ) || ! is_writable ( 'images/cache' ) ) {
      $ret = "background: $base url(\"includes/gradient.php?base=" . substr ( $base, 1 );
      if ( $height != '' )  $ret .= "&height=$height";
      if ( $percent != '' ) $ret .= "&percent=$percent";
      $ret .= "\") repeat-x;\n";
    } else  {
      $hgt = ( $height != ''?  "-" . $height : '');
      $pct = ( $percent != ''? "-" . $percent : '' );
      $file_name ="images/cache/". substr ( $base,1,6 ). $hgt . $pct . $type;
      if ( !file_exists ( $file_name ) ) 
      $tmp = create_image  ( $file_name, $base, $height, $percent );
      $ret = "background: $base url( $file_name ) repeat-x;\n";
    }

  
  } else {
    $ret = "background-color: $base;\n";
  }

  return $ret;
}


function create_image ( $file_name, $base='', $height='', $percent='', $width='', 
  $direction='', $numcolors='' ) {
  global $_GET, $MAX_HEIGHT, $MAX_WIDTH, $MIN_COLORS, $MAX_COLORS, $DEFAULTS;
  
  if ( $base == '' ) {
    $color1 = get_GetValue ( 'color1' );
    $color2 = get_GetValue ( 'color2' );
  } else {
    $color1 = $base;
    $color2 = $color1;
  }    
  if ( $color1 == '' ) {
    $color1 = colorToRGB ( $DEFAULTS['color1'] );
  } else {
    if ( preg_match ( "/^#?([0-9a-fA-F]{3,6})/", $color1, $matches ) ) {
      $color1 = colorToRGB ( $matches[1] );
    } else {
      $color1 = colorToRGB ( $DEFAULTS['color1'] );
    }
  }
  if ( $color2 == '' ) {
    $color2 = colorToRGB ( $DEFAULTS['color2'] );
  } else {
    if ( preg_match ( "/^#?([0-9a-fA-F]{3,6})/", $color2, $matches ) ) {
      $color2 = colorToRGB ( $matches[1] );
    } else {
      $color2 = colorToRGB ( $DEFAULTS['color2'] );
    }
  }  
  if ( empty ( $height ) ) {
    $height = $DEFAULTS['height'];
  }

  if ( $height > $MAX_HEIGHT ) {
    $height = $MAX_HEIGHT;
  }
  
  if ( $direction == '' || ( $direction % 90 ) != 0 ) {
    $direction = $DEFAULTS['direction'];
  } else {
    while ( $direction > 360 ) {
      $direction -= 360;
    }
  }

  if ( $direction == 90 || $direction == 270 ) {
    // Vertical gradient
  
    if ( empty ( $height ) ) {
      $height = $DEFAULTS['height'];
    }
  
    if ( $height > $MAX_HEIGHT ) {
      $height = $MAX_HEIGHT;
    }
  
    $width = 1;
  } else {
    // Horizontal gradient
    if ( empty ( $width ) ) {
      $width = $DEFAULTS['width'];
    }
  
    if ( $width > $MAX_WIDTH ) {
      $width = $MAX_WIDTH;
    }
  
    $height = 1;
  }

  if ( empty ( $numcolors ) ) {
    $numcolors = $DEFAULTS['colors'];
  } else {
    if ( preg_match ( '/^\d+$/', $numcolors ) ) {
      if ( $numcolors < $MIN_COLORS ) {
        $numcolors = $MIN_COLORS;
      } else if ( $numcolors > $MAX_COLORS ) {
        $numcolors = $MAX_COLORS;
      }
    } else {
      $numcolors = $DEFAULTS['colors'];
    }
  }

  if ( $percent == '' || $percent < 0 || $percent > 100 ) {
    $percent = $DEFAULTS['percent'];
  }

  $color2['red'] = min ( $color2['red'] + $percent * 255 / 100 , 255 );
  $color2['green'] = min ( $color2['green'] + $percent * 255 / 100, 255 );
  $color2['blue'] = min ( $color2['blue'] + $percent * 255 / 100, 255 );

  $image = imagecreate ( $width, $height );
  
  // Allocate array of colors
  $colors = array ();
  
  $deltared = $color2['red'] - $color1['red'];
  $deltagreen = $color2['green'] - $color1['green'];
  $deltablue = $color2['blue'] - $color1['blue'];

  for ( $i = 0; $i < $numcolors; $i++ ) {
    $thisred =
      min ( $color1['red'] + ( $deltared * $i / ( $numcolors - 1 ) ), 255 );
  
    $thisgreen =
      min ( $color1['green'] + ( $deltagreen * $i / ( $numcolors - 1 ) ), 255 );
  
    $thisblue =
      min ( $color1['blue'] + ( $deltablue * $i / ( $numcolors - 1 ) ), 255 );
  
    $thisred = floor ( $thisred );
    $thisgreen = floor ( $thisgreen );
    $thisblue = floor ( $thisblue );
    
    $colors[$i] = imagecolorallocate ( $image, $thisred, $thisgreen, $thisblue );
  }

  switch ( $direction ) {
    case 0:
      $x1 = 0;
      $x2 = 0;
  
      $y1 = 0;
      $y2 = $height - 1;
  
      $dx = 1;
      $dy = 0;
  
      $dim = $width;
      break;
    case 90:
      $x1 = 0;
      $x2 = $width - 1;
  
      $y1 = $height - 1;
      $y2 = $height - 1;
  
      $dx = 0;
      $dy = -1;
  
      $dim = $height;
      break;
    case 180:
      $x1 = $width - 1;
      $x2 = $width - 1;
  
      $y1 = 0;
      $y2 = $height - 1;
  
      $dx = -1;
      $dy = 0;
  
      $dim = $width;
      break;
    case 270:
      $x1 = 0;
      $x2 = $width - 1;
  
      $y1 = 0;
      $y2 = 0;
  
      $dx = 0;
      $dy = 1;
  
      $dim = $height;
      break;
  }

  $i = 0;
  while ( $x1 >= 0 && $x1 < $width
        && $x2 >= 0 && $x2 < $width
        && $y1 >= 0 && $y1 < $height
        && $y2 >= 0 && $y2 < $height ) {

  // Which color for this line?
  $ind = floor ( $numcolors * $i / $dim );
  if ( $ind >= $numcolors ) {
    $ind = $numcolors;
  }

  imageline ( $image, $x1, $y1, $x2, $y2, $colors[$ind] );

  $x1 += $dx;
  $y1 += $dy;

  $x2 += $dx;
  $y2 += $dy;

  $i++;
}

  if ( function_exists ( "imagepng" ) ) {
    if ( $file_name == '' ) {
      header ( "Content-type: image/png" );
      imagepng($image);  
    } else {
      imagepng($image, $file_name);
    }
  } else if ( function_exists ( "imagegif" ) ) {
    if ( $file_name == '' ) {
      header ( "Content-type: image/gif" );
      imagegif ( $image );
    } else {
      imagegif ( $image, $file_name );
    }
  } else {
    echo "No image formats supported!<br />\n";
  }

  imagedestroy ( $image );
  return;
}
?>
