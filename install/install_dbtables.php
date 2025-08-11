<p>
  <?php
  require_once "sql/upgrade-sql.php";

  //$detectedDbVersion = "v1.1.2";
  //$databaseCurrent = false;
  $sql = '';
  $buttonLabel = translate('Upgrade Database');
  if ($databaseCurrent) {
    $msg = translate("Your XXX database named 'YYY' is up to date.  You may go on to the next step.");
    $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
    $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
    echo $msg;
  } else {
    if ($emptyDatabase) {
      $msg = translate("Your XXX database named 'YYY' is empty and needs tables created.");
      $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
      $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
      $sqlFile = getSqlFile($_SESSION['db_type'], false);
      $sql = extractSqlCommandsFromFile($sqlFile);
      $buttonLabel = translate('Create Database Tables');
    } else {
      $msg = translate("Your XXX database named 'YYY' needs upgrading from version ZZZ.");
      $msg = str_replace('XXX', $_SESSION['db_type'], $msg);
      $msg = str_replace('YYY', $_SESSION['db_database'], $msg);
      $msg = str_replace('ZZZ', $detectedDbVersion, $msg);
      $sql = getSqlUpdates($detectedDbVersion, $_SETTINGS['db_type']);
      if (empty($sql)) {
        echo "Could not find version $detectedDbVersion<br>\n";
      }
    }
    echo $msg;
  }
  ?>
</p>

<?php

if ($databaseCurrent) {
  printNextPageButton($action);
} else {
?>
  <button type="button" id="displaySqlBtn" class="btn btn-info" data-toggle="modal" data-target="#sqlModal"><?php etranslate('Display SQL'); ?></button>
<?php
  printSubmitButton($action, null, $buttonLabel);
}
?>

<!-- Modal for displaying SQL commands -->
<div class="modal fade" id="sqlModal" tabindex="-1" role="dialog" aria-labelledby="sqlModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sqlModalLabel"><?php etranslate('Upgrade SQL Commands'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="max-block-size: 70vh; overflow-y: auto;">
        <pre id="sqlContent">SQL commands will be populated here.</pre>
      </div>
      <div class="modal-footer">
        <!-- Copy Button -->
        <button type="button" class="btn btn-primary" id="copySqlContent"><?php etranslate('Copy'); ?></button>

        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php etranslate('Close'); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
  var sqlCommands = [
    <?php
    for ($i = 0; $i < count($sql); $i++) {
      if ($i > 0)
        echo ", \n";
      $str = removeWhitespaceOnlyLines($sql[$i]);
      $str = str_replace("\n", "\\n", $str);
      $str = str_replace(";;", ";", $str);
      echo "\"" . $str . ";\"";
    }
    echo "\n";
    ?>
  ];
  $(document).ready(function() {
    // Show the SQL commands in the modal when the button is clicked
    $('#displaySqlBtn').click(function() {
      $('#sqlContent').text(sqlCommands.join("\n"));
      $('#sqlModal').modal('show');
    });
    $('#copySqlContent').on('click', function() {
      const range = document.createRange();
      range.selectNode(document.getElementById('sqlContent'));
      window.getSelection().removeAllRanges();
      window.getSelection().addRange(range);

      try {
        document.execCommand('copy');
        alert('<?php etranslate('SQL content copied to clipboard'); ?>');
      } catch (err) {
        console.error('Failed to copy text: ', err);
        alert('Failed to copy SQL content to clipboard');
      }

      window.getSelection().removeAllRanges();
    });
  });
</script>
