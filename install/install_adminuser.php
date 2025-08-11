<p>
  <?php
  $msg = translate("You have XXX admin accounts.");
  $msg = str_replace('XXX', $adminUserCount, $msg);
  $defaultAdminExists = false;
  $res = dbi_execute("SELECT COUNT(*) FROM webcal_user WHERE cal_login = 'admin'", [], false, true);
  if ($res) {
    $row = dbi_fetch_row ($res);
    $defaultAdminExists = ($row[0] > 0);
    dbi_free_result($res);
  }
  echo $msg;
  ?>
</p>

<?php
  if (!$defaultAdminExists) {
    printSubmitButton($action, null, translate('Create Default Admin User'));
    echo "&nbsp;";
  }
  if ($adminUserCount > 0) {
    printNextPageButton($action);
  }
?>
