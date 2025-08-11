<table class="table table-striped table-bordered">
  <thead>
    <tr>
      <th><?php etranslate('Setting Description'); ?></th>
      <th><?php etranslate('Required Setting'); ?></th>
      <th><?php etranslate('Current Setting'); ?></th>
      <th><?php etranslate('Status'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php
    foreach ($php_settings as $setting) {
      $description = $setting[0];
      $requiredValue = $setting[1];
      $foundValue = $setting[2];
      $isCorrect = $setting[3];
      $tooltip = $setting[4];

      echo "<tr>";
      echo "<td data-toggle='tooltip' title='" . htmlentities($tooltip) . "'>" . htmlentities($description) . "</td>";
      echo "<td>" . htmlentities($requiredValue) . "</td>";
      echo "<td>" . htmlentities($foundValue) . "</td>";
      echo "<td " . ($isCorrect ? "table-success" : 'class="table-danger"') . "'>" . ($isCorrect ? translate("Correct") : translate("Incorrect")) . "</td>";
      echo "</tr>";
    }
    ?>
  </tbody>
</table>

<script>
  function testPHPInfo() {
    $('#wcTestPHPInfo').modal('show');
  }
</script>
<input name="action" type="button" class="btn btn-secondary" value="<?php etranslate('Detailed PHP Info') ?>..." onClick="testPHPInfo()" />
<?php
$html = '';
$buttonLabel = translate('Next');
if (!$phpSettingsAcked && !$phpSettingsCorrect) {
  $text = translate('Some WebCalendar function may be limited.  Are you sure?');
  $html = 'onclick="return confirm(\'' . $text . '\')"';
  $buttonLabel = translate('Acknowledge');
}
printSubmitButton($action, $html, $buttonLabel);
?>

<!-- Bootstrap Modal -->
<div class="modal fade" id="wcTestPHPInfo" tabindex="-1" role="dialog" aria-labelledby="wcTestPHPInfoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="wcTestPHPInfoLabel"><?php etranslate('PHP Info'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Iframe with full width and height -->
        <iframe src="index.php?action=phpinfo" style="inline-size: 100%; block-size: 500px;" frameborder="0"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php etranslate('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
