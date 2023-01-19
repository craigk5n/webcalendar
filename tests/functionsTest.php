<?php


use PHPUnit\Framework\TestCase;

include "includes/functions.php";


/**
 * Unit tests for includes/functions.php
 * @covers Email
 */
final class FunctionsTest extends TestCase
{
  public function test_activate_urls() {
    $text = 'Sample Text';
    $res = activate_urls ( $text );
    $this->assertEquals ( $text, $res );

    $text = 'Sample Text http://www.cnn.com';
    $res = activate_urls ( $text );
    $this->assertEquals ( 'Sample Text <a href="http://www.cnn.com">http://www.cnn.com</a>', $res );

    $text = 'Sample Text https://www.cnn.com';
    $res = activate_urls ( $text );
    $this->assertEquals ( 'Sample Text <a href="https://www.cnn.com">https://www.cnn.com</a>', $res );
  }

  public function test_add_dstfree_time() {
    // Pick a timezone that observes DST
    date_default_timezone_set ( "America/New_York" );
    // 03/09/2019 @ 12:00pm (UTC) = day before DST starts
    // adding 1 day should be 23 hours diff
    $time = 1552132800;
    $res = add_dstfree_time ( $time, 86400 );
    $diff = $res - $time;
    $hours = $diff / 3600;
    $this->assertEquals ( 23, $hours );
    // add another day and we should get 24 hours
    $time += 3600 * 24;
    $res = add_dstfree_time ( $time, 86400 );
    $diff = $res - $time;
    $hours = $diff / 3600;
    $this->assertEquals ( 24, $hours );
  }

  public function test_add_duration() {
    $hourIn = 100000; // 10AM
    $res = add_duration ( $hourIn, 60 ); // Add 1 hour
    $this->assertEquals ( '110000', $res );

    $hourIn = 100000; // 10AM
    $res = add_duration ( $hourIn, 120 ); // Add 1 hour
    $this->assertEquals ( '120000', $res );

    $hourIn = 140000; // 2PM
    $res = add_duration ( $hourIn, 480 ); // Add 1 hour
    $this->assertEquals ( '220000', $res );

    // duration that crosses midnight
    $hourIn = 233000; // 1130PM
    $res = add_duration ( $hourIn, 60 ); // Add 1 hour

    // Longer event that crosses midnight
    $hourIn = 160000; // 4PM
    $res = add_duration ( $hourIn, 60 * 24 ); // Add 24 hours
    $this->assertEquals ( '160000', $res );

    // Multi-day duration
    $hourIn = 160000; // 4PM
    $res = add_duration ( $hourIn, 60 * 24 * 7 ); // Add 7 days
    $this->assertEquals ( '160000', $res );
  }

  // Unit tests for bump_local_timestamp
  public function test_bump_local_timestamp() {
    date_default_timezone_set ( "America/New_York" );
    // Noon NY time on Jan 1 2016
    $jan1_ts = mktime ( 12, 0, 0, 1, 1, 2016 );
    // echo "jan1_ts = " . date('r',$jan1_ts) . "\n";
    $this->assertEquals ( '12', date('H', $jan1_ts) );
    $this->assertEquals ( '2016', date('Y', $jan1_ts) );

    // params for bump_local_timestamp:
    //   current_unixtime, hourchange, minchange, secchage,
    //   monthchange, daychange, yearchange

    // Add 1 hour
    $newtime = bump_local_timestamp( $jan1_ts, 1, 0, 0, 0, 0, 0 );
    $this->assertEquals ( '13', date('H', $newtime) );

    // Add 1 year
    $newtime = bump_local_timestamp( $jan1_ts, 0, 0, 0, 0, 0, 1 );
    $this->assertEquals ( '2017', date('Y', $newtime) );

    // Add 1 month
    $newtime = bump_local_timestamp( $jan1_ts, 0, 0, 0, 1, 0, 0 );
    $this->assertEquals ( '02', date('m', $newtime) );

    // Add 1 day
    $newtime = bump_local_timestamp( $jan1_ts, 0, 0, 0, 0, 1, 0 );
    //echo "Time: " . date('r', $newtime ) . "\n";
    $this->assertEquals ( '02', date('d', $newtime) );

    // Daylight savings 2016 was March 13, aneded on November 6

    // Add day for about a week around the change and make sure
    // the hour stays at 12PM.
    $start = mktime ( 12, 0, 0, 3, 7, 2016 ); // March 7
    for ( $i = 0; $i < 14; $i++ ) {
      $newtime = bump_local_timestamp( $start, 0, 0, 0, 0, $i, 0 );
      $expDay = sprintf ( "%02d", ( 7 + $i ) );
      //echo "Time: " . date('r', $newtime ) . "\n";
      $this->assertEquals ( '12', date('H', $newtime) );
      $this->assertEquals ( $expDay, date('d', $newtime) );
    }
  
    // Do the same for DST ending
    $start = mktime ( 12, 0, 0, 11, 1, 2016 ); // Nov 1
    for ( $i = 0; $i < 14; $i++ ) {
      $newtime = bump_local_timestamp( $start, 0, 0, 0, 0, $i, 0 );
      $expDay = sprintf ( "%02d", ( 1 + $i ) );
      //echo "Time: " . date('r', $newtime ) . "\n";
      $this->assertEquals ( '12', date('H', $newtime) );
      $this->assertEquals ( $expDay, date('d', $newtime) );
    }
  }

  // TODO: test_build_entry_label

  // Unit tests for calc_time_slot
  // Function paramters:
  // string $time        Input time in HHMMSS format
  // bool   $round_down  Should we change 1100 to 1059?
  public function test_calc_time_slot() {
    date_default_timezone_set ( "America/New_York" );
    global $TIME_SLOTS;

    $TIME_SLOTS = 24; // 1 slot per hour
    $this->assertEquals ( 0, calc_time_slot ( '000000', false ) );
    $this->assertEquals ( 0, calc_time_slot ( '005900', false ) );
    $this->assertEquals ( 0, calc_time_slot ( '010000', true ) );
    $this->assertEquals ( 1, calc_time_slot ( '010000', false ) );
    $this->assertEquals ( 12, calc_time_slot ( '120000', false ) );
    $this->assertEquals ( 12, calc_time_slot ( '123000', false ) );
    $this->assertEquals ( 12, calc_time_slot ( '125959', false ) );
    $this->assertEquals ( 23, calc_time_slot ( '230000', false ) );
    $this->assertEquals ( 23, calc_time_slot ( '235900', false ) );
    $this->assertEquals ( 23, calc_time_slot ( '240000', true ) );

    $TIME_SLOTS = 48; // 1 slot per half hour
    $this->assertEquals ( 0, calc_time_slot ( '000000', false ) );
    $this->assertEquals ( 1, calc_time_slot ( '005900', false ) );
    $this->assertEquals ( 1, calc_time_slot ( '010000', true ) );
    $this->assertEquals ( 2, calc_time_slot ( '010000', false ) );
    $this->assertEquals ( 24, calc_time_slot ( '120000', false ) );
    $this->assertEquals ( 25, calc_time_slot ( '123000', false ) );
    $this->assertEquals ( 25, calc_time_slot ( '125959', false ) );
    $this->assertEquals ( 46, calc_time_slot ( '230000', false ) );
    $this->assertEquals ( 47, calc_time_slot ( '235900', false ) );
    $this->assertEquals ( 47, calc_time_slot ( '240000', true ) );
  }

  function test_encode_decode() {
    global $offsets;

    // The offsets are Normally calculated in WebCalendar.class.php based on install password
    $offsets = [1, 34, 240, 101];
    $STR1 = "How now brown cow";
    $this->assertEquals($STR1, decode_string(encode_string($STR1)));
  }

  function test_html2rgb() {
    $rgb = html2rgb('#ffffff');
    $this->assertEquals (255, $rgb[0]);
    $this->assertEquals (255, $rgb[1]);
    $this->assertEquals (255, $rgb[2]);
    $rgb = html2rgb('#000000');
    $this->assertEquals (0, $rgb[0]);
    $this->assertEquals (0, $rgb[1]);
    $this->assertEquals (0, $rgb[2]);
    $rgb = html2rgb('#c0c0c0');
    $this->assertEquals (192, $rgb[0]);
    $this->assertEquals (192, $rgb[1]);
    $this->assertEquals (192, $rgb[2]);
  }

  function test_rgb2html() {
    $this->assertEquals ('#ffffff', rgb2html (255, 255, 255));
    $this->assertEquals ('#000000', rgb2html (0, 0, 0));
    $this->assertEquals ('#c0c0c0', rgb2html (192, 192, 192));
    $this->assertEquals ('#ff0000', rgb2html (255, 0, 0));
  }
  

}
