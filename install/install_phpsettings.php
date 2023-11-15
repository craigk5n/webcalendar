<table class="table table-striped table-bordered">
  <thead>
    <tr>
      <th><?php etranslate('Setting Description');?></th>
      <th><?php etranslate('Required Setting');?></th>
      <th><?php etranslate('Current Setting');?></th>
      <th><?php etranslate('Status');?></th>
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