<?php
/**
 * Calculate the moonphases for a given year and month.
 *
 * code borrowed from http://www.zend.com/codex.php?eid=830&single=1
 * modified to accept month parameter and return 1 month of moon phases
 * and return array in Ymd format
 * Converted from Basic by Roger W. Sinnot, Sky & Telescope, March 1985.
 * Converted from javascript by Are Pedersen 2002
 * Javascript found at http://www.stellafane.com/moon_phase/moon_phase.htm
 *
 * Required for gif images to be present in the images folder
 * newmoon.gif, fullmoon.gif, firstmoon.gif, lastmoon.gif
 *
 * @param int $date   YYYYMMDD
 *
 * #returns array  $key = phase name, $val = Ymd value
 */
function calculateMoonPhases( $date ) {
  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $R1 = 3.14159265 / 180;
  $U = false;
  $s = ''; // Formatted Output String
  $K0 = intval(($year-1900)*12.3685) +( $month -1);
  $T = ($year-1899.5) / 100;
  $T2 = $T*$T; $T3 = $T*$T*$T;
  $J0 = 2415020 + 29*$K0;
  $F0 = 0.0001178*$T2 - 0.000000155*$T3;
  $F0 += (0.75933 + 0.53058868*$K0);
  $F0 -= (0.000837*$T + 0.000335*$T2);
  $M0 = $K0*0.08084821133;
  $M0 = 360*($M0 - intval($M0)) + 359.2242;
  $M0 -= 0.0000333*$T2;
  $M0 -= 0.00000347*$T3;
  $M1 = $K0*0.07171366128;
  $M1 = 360*($M1 - intval($M1)) + 306.0253;
  $M1 += 0.0107306*$T2;
  $M1 += 0.00001236*$T3;
  $B1 = $K0*0.08519585128;
  $B1 = 360*($B1 - intval($B1)) + 21.2964;
  $B1 -= 0.0016528*$T2;
  $B1 -= 0.00000239*$T3;
  for ( $K9=0; $K9 <= 6; $K9=$K9+0.5 ) {
    $J = $J0 + 14*$K9; $F = $F0 + 0.765294*$K9;
    $K = $K9/2;
    $M5 = ($M0 + $K*29.10535608)*$R1;
    $M6 = ($M1 + $K*385.81691806)*$R1;
    $B6 = ($B1 + $K*390.67050646)*$R1;
    $F -= 0.4068*sin($M6);
    $F += (0.1734 - 0.000393*$T)*sin($M5);
    $F += 0.0161*sin(2*$M6);
    $F += 0.0104*sin(2*$B6);
    $F -= 0.0074*sin($M5 - $M6);
    $F -= 0.0051*sin($M5 + $M6);
    $F += 0.0021*sin(2*$M5);
    $F += 0.0010*sin(2*$B6-$M6);
    $F += 0.5 / 1440;
    $J += intval($F); $F -= intval($F);
    //Convert from JD to Calendar Date
    $julian=$J+round($F);
    if ( function_exists ( 'jdtogregorian' ) ){
      $s = date ( 'Ymd', strtotime ( jdtogregorian ($julian) ) );
    } else {
      $s = jd_to_greg ($julian);
    } 
    //half K
    if (($K9-floor($K9))>0){
        if (!$U){
            $phases[$s]='<img src="images/lastmoon.gif" title="' 
			  . translate ( 'Last Quarter') . '"/>';
        }else{
            $phases[$s]='<img src="images/firstmoon.gif" title="' 
			  . translate ( 'First Quarter') . '"/>';
        }
        
    }else{
        if ( !$U ){
            $phases[$s]='<img src="images/newmoon.gif" title="' 
			  . translate ( 'New Moon') . '"/>';
        }else{
            $phases[$s]='<img src="images/fullmoon.gif" title="' 
			  . translate ( 'Full Moon') . '"/>';
        }
        $U = !$U;
    }
  } // Next
  return $phases;
} //End MoonPhase

//function borrowed from http://us3.php.net/manual/en/function.jdtogregorian.php
//used if calendar functions are not compiled in php
function jd_to_greg($julian) {
   $julian = $julian - 1721119;
   $calc1 = 4 * $julian - 1;
   $year = floor($calc1 / 146097);
   $julian = floor($calc1 - 146097 * $year);
   $day = floor($julian / 4);
   $calc2 = 4 * $day + 3;
   $julian = floor($calc2 / 1461);
   $day = $calc2 - 1461 * $julian;
   $day = floor(($day + 4) / 4);
   $calc3 = 5 * $day - 3;
   $month = floor($calc3 / 153);
   $day = $calc3 - 153 * $month;
   $day = floor(($day + 5) / 5);
   $year = 100 * $year + $julian;

   if ($month < 10) {
       $month = $month + 3;
   }
   else {
       $month = $month - 9;
       $year = $year + 1;
   }
   return sprintf ( "%04d%02d%02d", $year, $month, $day);
}
?>
