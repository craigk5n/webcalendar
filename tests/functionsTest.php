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

}
