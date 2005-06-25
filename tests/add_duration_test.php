<?php
  require_once('../includes/functions.php');

  class TestOfFunctions extends UnitTestCase {

    function testAddDuration() {
      $this->assertEqual ( add_duration ( '123456', '50' ), '132400' );
    }
  }
?>
