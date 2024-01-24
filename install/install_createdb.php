<p>
  <?php
  if ($databaseExists) {
    $msg = translate("Your XXX database named 'YYY' exists.  You may go on to the next step.");
    $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
    $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
    echo $msg;
  } else {
    // Database does not exist.  Allow user to try to create it here.
    $msg = translate("Your XXX database named 'YYY' does not exist yet.");
    $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
    $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
    echo $msg;
  }
  ?>
</p>

<?php
if ($databaseExists) {
  printNextPageButton($action);
} else {
  printSubmitButton($action, null, translate('Create Database'));
}
?>