<p>
  <?php
  if ($emptyDatabase) {
    $msg = translate("Your XXX database named 'YYY' is empty and requires loading of default data.");
    $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
    $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
    echo $msg;
  } else {
    // Database does not exist.  Allow user to try to create it here.
    $msg = translate("Your XXX database named 'YYY' has already been loaded with default data.");
    $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
    $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
    echo $msg;
  }
  ?>
</p>

<?php
  if ($emptyDatabase) {
    printSubmitButton($action, null, translate('Load Defaults'));
  } else {
    printNextPageButton($action);
  }
?>
