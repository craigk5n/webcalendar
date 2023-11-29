<?php
$passwordSet = !empty($settings['install_password']);
if (!$passwordSet) {
  // Not yet set
?> <p>
    <?php
    etranslate('If your installation is accessible to anyone untrusted, you should secure the installation pages by restricting access. The easiest way to do this is by providing a passphrase.');
    echo "</p>\n<p>";
    etranslate('Once the installation is complete, you will need not this passphrase again until you want to upgrade your installation. Please take note of your password because there is no way to recover it.');
    ?>
  </p>
  <div class="form-group">
    <label for="hint" data-toggle="tooltip" data-placement="top" title="<?php etranslate('This password hint will be provided when the you login at a later time.') ?>">
      Password hint:
    </label>
    <input type="text" class="form-control" id="hint" name="hint" placeholder="<?php etranslate('Enter a hint to remember your password'); ?>">
    <label for="password" data-toggle="tooltip" data-placement="top" title="<?php etranslate('This passphrase was provided during the initial installation.'); ?>">
      Enter your installation passphrase:
    </label>
    <input type="password" class="form-control" id="password" name="password" required placeholder="<?php etranslate('Enter your passphrase'); ?>">
    <label for="password2" data-toggle="tooltip" data-placement="top" title="<?php etranslate('This passphrase was provided during the initial installation.'); ?>">
      Enter your installation passphrase (again):
    </label>
    <input type="password" class="form-control" id="password2" name="password2" required placeholder="<?php etranslate('Enter your passphrase again'); ?>">
  </div>
  </p>
<?php
  printSubmitButton($action, null, translate('Save'));
} else {
  // Password already set.  Prompt for it.
  $install_password_hint = $settings['install_password_hint'] ?? null;
?>
  <table style="border: 0;">
    <?php if ($install_password_hint) { ?>
      <tr>
        <td style="vertical-align: middle;">
          <label for="hint" data-toggle="tooltip" data-placement="top" title="<?php etranslate('This password hint was provided when the password was set.') ?>">
            <?php echo translate('Password hint') . ': '; ?>
          </label>
        </td>
        <td>
          <input type="text" readonly class="form-control" id="hint" name="hint" value="<?php echo htmlentities($install_password_hint); ?>">
        </td>
      </tr>
    <?php } ?>
    <tr>
      <td style="vertical-align: middle;">
        <label for="password" data-toggle="tooltip" data-placement="top" title="<?php etranslate('This passphrase was provided during the initial installation.'); ?>">
          <?php echo translate('Installation passphrase') . ': '; ?>
        </label>
      </td>
      <td>
        <input type="password" class="form-control" id="password" name="password" required placeholder="<?php etranslate('Enter your passphrase'); ?>">
      </td>
    </tr>
  </table>


<?php
  printSubmitButton($action, null, translate('Login'));
}
?>
