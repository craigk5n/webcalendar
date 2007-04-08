<?php
class_exists ( 'UnitTestCase' ) or die ( 'Run from "all_tests.php"' );
require_once ( '../includes/functions.php' );

class TestOfFunctions extends UnitTestCase {
  function testAddDuration () {
    $this->assertEqual ( add_duration ( '123456', '50' ), '132400' );
    $this->assertNotEqual ( add_duration ( '123456', '1490' ), '132400' );
  }
}

?>
