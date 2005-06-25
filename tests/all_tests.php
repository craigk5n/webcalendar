<?php
  if ( ! defined ( 'SIMPLETEST_ROOT' ) ) {
    define ( 'SIMPLETEST_ROOT', '../../simpletest/' );
  }

  require_once(SIMPLETEST_ROOT . 'unit_tester.php');
  require_once(SIMPLETEST_ROOT . 'reporter.php');

  $test = &new GroupTest('All tests');
  $test->addTestFile('add_duration_test.php');
  $test->run(new HtmlReporter());
?>
