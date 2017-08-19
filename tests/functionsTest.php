<?php


use PHPUnit\Framework\TestCase;


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
    // TODO
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


}
