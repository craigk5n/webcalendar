<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../wizard/shared/upgrade_matrix.php";

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

    $text = 'Sample Text http://k5n.us';
    $res = activate_urls ( $text );
    $this->assertEquals ( 'Sample Text <a href="http://k5n.us">http://k5n.us</a>', $res );

    $text = 'Sample Text https://www.k5n.us';
    $res = activate_urls ( $text );
    $this->assertEquals ( 'Sample Text <a href="https://www.k5n.us">https://www.k5n.us</a>', $res );

    $text = 'Sample Text https://www.k5n.us/webcalendar';
    $res = activate_urls ( $text );
    $this->assertEquals ( 'Sample Text <a href="https://www.k5n.us/webcalendar">https://www.k5n.us/webcalendar</a>', $res );
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
    $time += 86400;
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
    $res = add_duration ( $hourIn, 1440 ); // Add 24 hours
    $this->assertEquals ( '160000', $res );

    // Multi-day duration
    $hourIn = 160000; // 4PM
    $res = add_duration ( $hourIn, 10080 ); // Add 7 days
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
    //   current_unixtime, hourchange, minchange, secchange,
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

    // Daylight savings 2016 was March 13, amended on November 6

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

  function test_upgrade_requires_db_changes() {
    $this->assertTrue(upgrade_requires_db_changes('mysql', 'v1.3.0', 'v1.9.1'));
    $this->assertTrue(upgrade_requires_db_changes('mysql', 'v1.3.0', 'v1.9.8'));
    $this->assertTrue(upgrade_requires_db_changes('mysql', 'v1.3.0', 'v1.9.9'));
    $this->assertFalse(upgrade_requires_db_changes('mysql', 'v1.9.1', 'v1.9.2'));
    $this->assertFalse(upgrade_requires_db_changes('mysql', 'v1.9.2', 'v1.9.5'));
    $this->assertFalse(upgrade_requires_db_changes('mysql', 'v1.9.3', 'v1.9.5'));
    $this->assertTrue(upgrade_requires_db_changes('mysql', 'v1.9.5', 'v1.9.6'));
    $this->assertFalse(upgrade_requires_db_changes('mysql', 'v1.9.7', 'v1.9.8'));
  }

  // ===========================================================================
  // INPUT SANITIZATION TESTS - SECURITY CRITICAL
  // ===========================================================================

  /**
   * Test clean_html() - HTML entity encoding for XSS prevention
   *
   * SECURITY CONCERN: This function is critical for preventing XSS attacks.
   * It uses htmlspecialchars() with ENT_QUOTES and additionally encodes
   * parentheses to prevent JavaScript injection via onclick handlers.
   *
   * REFACTORING NOTE: Consider whether this is sufficient for all contexts.
   * Modern best practice is context-aware encoding (HTML body vs attribute
   * vs JavaScript vs URL). This function only handles HTML body context.
   */
  public function test_clean_html_basic() {
    // Basic HTML entities
    $this->assertEquals('&lt;script&gt;', clean_html('<script>'));
    $this->assertEquals('&lt;/script&gt;', clean_html('</script>'));

    // Quotes are encoded (ENT_QUOTES flag)
    $this->assertEquals('&quot;test&quot;', clean_html('"test"'));
    $this->assertEquals('&#039;test&#039;', clean_html("'test'"));

    // Ampersand encoding
    $this->assertEquals('&amp;', clean_html('&'));

    // Parentheses are specifically encoded (for onclick protection)
    $this->assertEquals('&#40;&#41;', clean_html('()'));
  }

  /**
   * Test clean_html() with XSS attack vectors
   *
   * SECURITY CONCERN: These tests verify common XSS payloads are neutralized.
   * This is not exhaustive - XSS attack vectors evolve constantly.
   *
   * NOTE: clean_html() encodes characters but doesn't remove them. The encoded
   * result is safe for HTML display but the original text is still recognizable.
   * Verify that HTML special chars are encoded, not that strings disappear.
   */
  public function test_clean_html_xss_vectors() {
    // Script injection - tags should be encoded
    $xss = '<script>alert("XSS")</script>';
    $cleaned = clean_html($xss);
    $this->assertStringNotContainsString('<script>', $cleaned);
    $this->assertStringContainsString('&lt;script&gt;', $cleaned);
    // Parentheses should be encoded
    $this->assertStringNotContainsString('alert(', $cleaned);
    $this->assertStringContainsString('alert&#40;', $cleaned);

    // Event handler injection - quotes are encoded
    $xss = '<img src=x onerror="alert(1)">';
    $cleaned = clean_html($xss);
    // The tag itself is encoded
    $this->assertStringNotContainsString('<img', $cleaned);
    $this->assertStringContainsString('&lt;img', $cleaned);

    // JavaScript URL - quotes and special chars encoded
    $xss = '<a href="javascript:alert(1)">click</a>';
    $cleaned = clean_html($xss);
    $this->assertStringNotContainsString('<a href=', $cleaned);
    $this->assertStringContainsString('&lt;a', $cleaned);
  }

  /**
   * Test clean_html() preserves safe content
   */
  public function test_clean_html_preserves_safe_content() {
    $this->assertEquals('Hello World', clean_html('Hello World'));
    $this->assertEquals('Test 123', clean_html('Test 123'));
    $this->assertEquals('user@example.com', clean_html('user@example.com'));
  }

  /**
   * Test clean_int() - Non-digit removal
   *
   * SECURITY CONCERN: Used to sanitize numeric input. Be aware that:
   * 1. Returns empty string for non-numeric input (not 0)
   * 2. Removes negative signs - cannot handle negative numbers
   * 3. Removes decimal points - cannot handle floats
   *
   * REFACTORING NOTE: Consider whether the function name is misleading.
   * It removes non-digits, but doesn't validate that the result is a
   * valid integer (could overflow, could be empty).
   */
  public function test_clean_int_basic() {
    $this->assertEquals('123', clean_int('123'));
    $this->assertEquals('123', clean_int('abc123def'));
    $this->assertEquals('123456', clean_int('123-456'));
    $this->assertEquals('', clean_int('abc'));
  }

  /**
   * Test clean_int() edge cases
   *
   * CONCERN: Negative numbers lose their sign
   */
  public function test_clean_int_edge_cases() {
    // Negative sign is stripped - THIS MAY BE UNEXPECTED BEHAVIOR
    $this->assertEquals('5', clean_int('-5'));

    // Decimal point is stripped
    $this->assertEquals('123', clean_int('1.23'));

    // Spaces and special chars
    $this->assertEquals('123', clean_int(' 1 2 3 '));

    // Empty input
    $this->assertEquals('', clean_int(''));

    // Leading zeros preserved (may cause octal interpretation issues later)
    $this->assertEquals('007', clean_int('007'));
  }

  /**
   * Test clean_whitespace() - Whitespace removal
   */
  public function test_clean_whitespace() {
    $this->assertEquals('HelloWorld', clean_whitespace('Hello World'));
    $this->assertEquals('abc', clean_whitespace("a\tb\nc"));
    $this->assertEquals('test', clean_whitespace('  test  '));
    $this->assertEquals('', clean_whitespace('   '));
  }

  /**
   * Test clean_word() - Non-word character removal
   *
   * SECURITY CONCERN: Used for sanitizing identifiers. Note that \W
   * in regex removes everything except [a-zA-Z0-9_]. This means:
   * 1. Removes hyphens, periods, spaces
   * 2. Removes unicode characters (may break internationalization)
   */
  public function test_clean_word() {
    $this->assertEquals('HelloWorld', clean_word('Hello World'));
    $this->assertEquals('test_123', clean_word('test_123'));
    $this->assertEquals('abc', clean_word('a-b.c'));
    $this->assertEquals('', clean_word('!@#$%'));
  }

  /**
   * Test clean_word() with unicode
   *
   * CONCERN: Unicode characters are stripped, which may break
   * internationalized usernames or identifiers.
   */
  public function test_clean_word_unicode() {
    // Accented characters are stripped
    $this->assertEquals('caf', clean_word('café'));
    // This may be problematic for international users
  }

  // ===========================================================================
  // DATE/TIME UTILITY TESTS
  // ===========================================================================

  /**
   * Test is_weekend() with day numbers (0-6)
   *
   * BEHAVIOR NOTE: The function accepts both day numbers (0-6) and
   * timestamps. Values < 7 are treated as day numbers.
   */
  public function test_is_weekend_day_numbers() {
    global $WEEKEND_START;

    // Default weekend is Saturday (6) and Sunday (0)
    $WEEKEND_START = 6;

    $this->assertTrue(is_weekend(0));   // Sunday
    $this->assertFalse(is_weekend(1));  // Monday
    $this->assertFalse(is_weekend(2));  // Tuesday
    $this->assertFalse(is_weekend(3));  // Wednesday
    $this->assertFalse(is_weekend(4));  // Thursday
    $this->assertFalse(is_weekend(5));  // Friday
    $this->assertTrue(is_weekend(6));   // Saturday
  }

  /**
   * Test is_weekend() with timestamps
   */
  public function test_is_weekend_timestamps() {
    global $WEEKEND_START;
    $WEEKEND_START = 6;

    date_default_timezone_set("America/New_York");

    // Jan 6, 2024 is a Saturday
    $saturday = mktime(12, 0, 0, 1, 6, 2024);
    $this->assertTrue(is_weekend($saturday));

    // Jan 7, 2024 is a Sunday
    $sunday = mktime(12, 0, 0, 1, 7, 2024);
    $this->assertTrue(is_weekend($sunday));

    // Jan 8, 2024 is a Monday
    $monday = mktime(12, 0, 0, 1, 8, 2024);
    $this->assertFalse(is_weekend($monday));
  }

  /**
   * Test is_weekend() with different WEEKEND_START values
   *
   * BEHAVIOR NOTE: Some cultures have different weekend definitions.
   * For example, in some Middle Eastern countries, the weekend is
   * Friday-Saturday instead of Saturday-Sunday.
   */
  public function test_is_weekend_different_cultures() {
    global $WEEKEND_START;

    // Friday-Saturday weekend (some Middle Eastern countries)
    $WEEKEND_START = 5;
    $this->assertTrue(is_weekend(5));   // Friday
    $this->assertTrue(is_weekend(6));   // Saturday
    $this->assertFalse(is_weekend(0));  // Sunday (workday)
  }

  /**
   * Test is_weekend() edge cases
   *
   * CONCERN: Empty string returns false, but 0 (Sunday) is valid.
   * The function checks strlen() specifically to handle this.
   */
  public function test_is_weekend_edge_cases() {
    global $WEEKEND_START;
    $WEEKEND_START = 6;

    $this->assertFalse(is_weekend(''));
    $this->assertTrue(is_weekend(0));  // 0 is valid (Sunday)
    $this->assertTrue(is_weekend('0')); // String '0' should also work
  }

  /**
   * Test isLeapYear()
   */
  public function test_isLeapYear() {
    // Standard leap years (divisible by 4)
    $this->assertTrue(isLeapYear(2024));
    $this->assertTrue(isLeapYear(2020));
    $this->assertTrue(isLeapYear(2016));

    // Non-leap years
    $this->assertFalse(isLeapYear(2023));
    $this->assertFalse(isLeapYear(2022));
    $this->assertFalse(isLeapYear(2021));

    // Century rule: divisible by 100 but not 400 = NOT leap
    $this->assertFalse(isLeapYear(1900));
    $this->assertFalse(isLeapYear(2100));

    // 400-year rule: divisible by 400 = leap year
    $this->assertTrue(isLeapYear(2000));
    $this->assertTrue(isLeapYear(1600));
  }

  /**
   * Test isLeapYear() edge cases
   *
   * CONCERN: Function requires 4-digit year. Invalid years return false.
   */
  public function test_isLeapYear_edge_cases() {
    // 2-digit years return false
    $this->assertFalse(isLeapYear(24));

    // 5-digit years return false
    $this->assertFalse(isLeapYear(10000));

    // Null uses current year (tested implicitly)
    $result = isLeapYear(null);
    $this->assertIsBool($result);
  }

  /**
   * Test hextoint() - Hexadecimal to integer conversion
   *
   * CONCERN: This function is redundant - PHP has hexdec().
   * Consider replacing during refactoring.
   */
  public function test_hextoint() {
    $this->assertEquals(0, hextoint('0'));
    $this->assertEquals(9, hextoint('9'));
    $this->assertEquals(10, hextoint('A'));
    $this->assertEquals(10, hextoint('a'));  // Case insensitive
    $this->assertEquals(15, hextoint('F'));
    $this->assertEquals(15, hextoint('f'));

    // Invalid input
    $this->assertEquals(0, hextoint(''));
    $this->assertEquals(0, hextoint('G'));  // Invalid hex digit
  }

  /**
   * Test getShortTime()
   */
  public function test_getShortTime() {
    global $DISPLAY_MINUTES;

    // When DISPLAY_MINUTES is off, strip :00
    $DISPLAY_MINUTES = 'N';
    $this->assertEquals('10', getShortTime('10:00'));
    $this->assertEquals('2 PM', getShortTime('2:00 PM'));
    $this->assertEquals('10:30', getShortTime('10:30'));  // Keep non-:00

    // When DISPLAY_MINUTES is on, keep everything
    $DISPLAY_MINUTES = 'Y';
    $this->assertEquals('10:00', getShortTime('10:00'));
    $this->assertEquals('10:30', getShortTime('10:30'));
  }

  /**
   * Test gregorianToISO() - Gregorian to ISO week date conversion
   *
   * ISO 8601 defines weeks as starting on Monday, with week 1 being
   * the week containing the first Thursday of the year.
   *
   * CONCERN: This function has complex week calculation logic borrowed
   * from PEAR. The WEEK_START global affects results. The weekday
   * numbering appears to differ from standard ISO 8601 (which uses 1=Monday).
   *
   * DOCUMENTED BEHAVIOR: The weekday value in the result does NOT match
   * ISO 8601 standard (1=Monday through 7=Sunday). The actual mapping
   * needs investigation during refactoring.
   */
  public function test_gregorianToISO_basic() {
    global $WEEK_START;
    $WEEK_START = 1;  // Monday

    // Jan 1, 2024 is a Monday, Week 1
    // Function returns weekday=4 for Monday (not ISO standard weekday=1)
    $result = gregorianToISO(1, 1, 2024);
    $this->assertMatchesRegularExpression('/^2024-01-\d$/', $result);

    // Jan 15, 2024 is Monday of Week 3
    $result = gregorianToISO(15, 1, 2024);
    $this->assertMatchesRegularExpression('/^2024-03-\d$/', $result);

    // Verify consistent weekday for same day-of-week
    $jan1 = gregorianToISO(1, 1, 2024);   // Monday
    $jan8 = gregorianToISO(8, 1, 2024);   // Monday
    $jan15 = gregorianToISO(15, 1, 2024); // Monday
    // All Mondays should have same weekday digit
    $this->assertEquals(substr($jan1, -1), substr($jan8, -1));
    $this->assertEquals(substr($jan1, -1), substr($jan15, -1));
  }

  /**
   * Test gregorianToISO() year boundary edge cases
   *
   * CONCERN: Dates at year boundaries can belong to week 52/53 of
   * previous year or week 1 of next year. This is often confusing.
   */
  public function test_gregorianToISO_year_boundaries() {
    global $WEEK_START;
    $WEEK_START = 1;  // Monday

    // Dec 31, 2024 - this could be week 1 of 2025
    $result = gregorianToISO(31, 12, 2024);
    // Just verify it returns valid format
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d$/', $result);

    // Jan 1, 2023 was a Sunday - might be week 52 of 2022
    $result = gregorianToISO(1, 1, 2023);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d$/', $result);
  }

  // ===========================================================================
  // COLOR CONVERSION TESTS
  // ===========================================================================

  /**
   * Test html2rgb() edge cases
   *
   * CONCERN: Function expects # prefix but behavior without it is unclear.
   */
  public function test_html2rgb_edge_cases() {
    // Without # prefix
    $rgb = html2rgb('ffffff');
    // Should still work (function strips leading char if present)
    $this->assertIsArray($rgb);

    // Short hex (3 chars) - NOT SUPPORTED
    // $rgb = html2rgb('#fff'); // This may not work correctly
  }

  /**
   * Test rgb2html() edge cases
   *
   * CONCERN: Function doesn't validate input ranges. Values > 255
   * or < 0 produce invalid HTML colors.
   */
  public function test_rgb2html_edge_cases() {
    // Values at boundaries
    $this->assertEquals('#000000', rgb2html(0, 0, 0));
    $this->assertEquals('#ffffff', rgb2html(255, 255, 255));

    // CONCERN: Out of range values not validated
    // These produce potentially invalid HTML colors
    // $result = rgb2html(256, 0, 0);  // May produce #1000000
    // $result = rgb2html(-1, 0, 0);   // May produce invalid
  }

  // ===========================================================================
  // ADDITIONAL UTILITY TESTS
  // ===========================================================================

  /**
   * Test activate_urls() with edge cases
   *
   * SECURITY CONCERN: This function creates HTML links from URLs in text.
   * It should not activate URLs that could be dangerous (javascript:, data:).
   */
  public function test_activate_urls_edge_cases() {
    // Multiple URLs
    $text = 'Visit http://example.com and https://example.org';
    $result = activate_urls($text);
    $this->assertStringContainsString('href="http://example.com"', $result);
    $this->assertStringContainsString('href="https://example.org"', $result);

    // URL with query string
    $text = 'http://example.com?foo=bar&baz=qux';
    $result = activate_urls($text);
    $this->assertStringContainsString('href="http://example.com?foo=bar&baz=qux"', $result);

    // URL with port number
    $text = 'http://localhost:8080/path';
    $result = activate_urls($text);
    $this->assertStringContainsString('href="http://localhost:8080/path"', $result);
  }

  /**
   * Test activate_urls() security - should not activate dangerous protocols
   *
   * SECURITY CONCERN: Verify javascript: and data: URLs are not activated.
   */
  public function test_activate_urls_dangerous_protocols() {
    // These should NOT become clickable links
    $text = 'javascript:alert(1)';
    $result = activate_urls($text);
    $this->assertStringNotContainsString('href="javascript:', $result);

    $text = 'data:text/html,<script>alert(1)</script>';
    $result = activate_urls($text);
    $this->assertStringNotContainsString('href="data:', $result);
  }

  // ===========================================================================
  // DOCUMENTED CONCERNS FOR REFACTORING
  // ===========================================================================

  /**
   * DOCUMENTATION: Functions that need attention during refactoring
   *
   * 1. clean_int() - Misleading name, strips negative signs, returns string not int
   * 2. clean_word() - Strips unicode, may break internationalization
   * 3. hextoint() - Redundant, use PHP's hexdec() instead
   * 4. gregorianToISO() - Complex borrowed code, needs thorough review
   * 5. check_for_conflicts() - Large function (150+ lines), needs decomposition
   * 6. activate_urls() - Should validate protocol whitelist
   * 7. html2rgb()/rgb2html() - Missing input validation
   * 8. get_all_dates() - 300+ lines, uses > instead of >= for start date matching
   *
   * See RepeatingEventsTest.php for more documented concerns about date functions.
   */
  public function test_placeholder_for_documentation() {
    // This test exists only to document concerns
    $this->assertTrue(true);
  }
}
