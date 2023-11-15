<?php
$dbTypes = [
    // Note: 'mysql' removed in PHP 7
    // Note: 'sqlite' removed in PHP 5.4
    // Note: 'mssql' removed in PHP 7
    'ibase' => 'function:ibase_connect',
    'ibm_db2' => 'function:db2_connect',
    'mysqli' => 'function:mysqli_connect',
    'odbc' => 'function:odbc_connect',
    'oracle' => 'function:oci_connect',
    'postgresql' => 'function:pg_connect',
    'sqlite3' => 'class:SQLite3'
];
function printDbSetting($name)
{
    if (!empty($_SESSION[$name])) {
        echo htmlentities($_SESSION[$name]);
    }
}
$readonlyForm = '';
$disabledForm = '';
if ($usingEnv) {
    $readonlyForm = 'readonly';
    $disabledForm = 'disabled';
?>
    <div class="alert alert-info" role="alert">
        <h4 class="alert-heading">Warning</h4>
        <span id="db-note">
            <?php etranslate('Because environment variables are being used to configure WebCalendar, you cannot alter the settings on this page.  You must do this externally.'); ?>
        </span>
    </div>
<?php
}
?>

<table style="border: 0;">
    <tr>
        <td><label for="dbType">Database Type:</label></td>
        <td>
            <select class="form-control" id="dbType" name="db_type" <?php echo $disabledForm; ?>>
                <?php foreach ($dbTypes as $type => $value) : ?>
                    <?php
                    list($checkType, $checkValue) = explode(':', $value);
                    $isSupported = ($checkType === 'function') ? @function_exists($checkValue) : @class_exists($checkValue);
                    ?>
                    <option value="<?= $type; ?>" <?= ($type == $_SESSION['db_type']) ? 'selected' : ''; ?> <?= (!$isSupported) ? 'disabled' : ''; ?> title="<?= (!$isSupported) ? 'Required PHP module not available' : ''; ?>">
                        <?= ucfirst($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr id="serverRow">
        <td><label for="server"><?php etranslate('Database Server'); ?>:</label></td>
        <td><input type="text" class="form-control" id="server" name="db_host" required <?php echo $readonlyForm; ?> value="<?php printDbSetting('db_host'); ?>"></td>
    </tr>
    <tr id="loginRow">
        <td><label for="login"><?php etranslate('Database Login'); ?>:</label></td>
        <td><input type="text" class="form-control" id="login" name="db_login" required <?php echo $readonlyForm; ?> value="<?php printDbSetting('db_login'); ?>"></td>
    </tr>
    <tr id="passwordRow">
        <td><label for="password"><?php etranslate('Database Password'); ?>:</label></td>
        <td><input type="password" class="form-control" id="password" name="db_password" required <?php echo $readonlyForm; ?> value="<?php printDbSetting('db_password'); ?>"></td>
    </tr>
    <tr>
        <td><label id="dbNameLabel" for="dbName"><?php etranslate('Database Name'); ?>:</label></td>
        <td><input type="text" class="form-control" id="dbName" name="db_database" required <?php echo $readonlyForm; ?> value="<?php printDbSetting('db_database'); ?>"></td>
    </tr>
    <tr>
        <td><label for="dbCacheDir"><?php etranslate('Database Cache Directory (Optional)'); ?>:</label></td>
        <td><input type="text" class="form-control" id="dbCacheDir" name="db_cachedir" <?php echo $readonlyForm; ?> value="<?php printDbSetting('db_cachedir'); ?>"></td>
    </tr>
    <tr>
        <td><label for="db_debug"><?php etranslate('Database Debugging'); ?>:</label></td>
        <td><select class="form-control" id="db_debug" name="db_debug" <?php echo $readonlyForm; ?>>
                <option value="N" <?php if (!empty($_SESSION['db_debug']) || in_array($_SESSION['db_debug'], ['N', 0, '0', 'false'])) echo ' selected '; ?>><?php etranslate('Disabled (recommended)'); ?></option>
                <option value="Y" <?php if (!empty($_SESSION['db_debug']) && in_array($_SESSION['db_debug'], ['Y', 1, '1', 'true'])) echo ' selected '; ?>><?php etranslate('Enabled'); ?></option>
            </select></td>
    </tr>
</table>
<br>
<button type="button" class="btn btn-primary" id="testSettingsBtn"><?php etranslate('Test Connection'); ?></button>
<?php
// If we have good db settings, show a "Save Settings" (settings.php) or "Next" (unmodified settings or env vars)
if (!$usingEnv) {
?>
    <button type="submit" id="save_button" class="btn btn-success disabled" name="dbaction" value="save"><?php etranslate('Save Settings'); ?></button>
<?php
}
// The "Next" button should only be used when the connections are currently valid,
// a connection test succeeded, and there are not app and/or form changes that need to be saved.
printNextPageButton($action, 'id="next_button" disabled');
?>

<script>
    var connSuccess = false;
    var changesNeedSaving = false;
    $(document).ready(function() {
        $("#testSettingsBtn").click(function() {
            var postData = {
                dbType: $("#dbType").val(),
                server: $("#server").val(),
                login: $("#login").val(),
                password: $("#password").val(),
                dbName: $("#dbName").val(),
                dbCacheDir: $("#dbCacheDir").val(),
                request: "test-db-connection",
                csrf_form_key: '<?php echo getFormKey(); ?>'
            };

            $.ajax({
                url: "install_ajax.php",
                type: "POST",
                data: postData,
                success: function(response) {
                    // Assuming the AJAX page will return a JSON object with a 'status' property
                    if (response.status == "ok") {
                        // Enable the "Save Settings" button if user made changes to the form
                        console.log("Successful connection. changesNeedSaving=" + changesNeedSaving);
                        $("#save_button").prop("disabled", changesNeedSaving ? false : true);
                        alert('<?php etranslate('Successful database connection'); ?>');
                        connSuccess = true;
                        if (!changesNeedSaving) {
                            console.log("Enabling next buton")
                            $("#save_button").prop("disabled", true);
                            $('#next_button').removeClass('disabled');
                        } else {
                            console.log("Enabling save button");
                            $('#save_button').removeClass('disabled');
                        }
                    } else {
                        $("#save_button").prop("disabled", true);
                        console.log("Connection error: " + response.error);
                        connSuccess = false;
                        alert("<?php etranslate('Failed to connect to the database. Please check your settings.'); ?>" + "\n\n" + response.error);
                    }
                },
                error: function() {
                    // This happens with 500 server error.
                    // This can happen when PHP threw an exception on the db open call and we didn't catch it.
                    alert("<?php etranslate('Failed to connect to the database. Please check your settings.'); ?>");
                }
            });
            // Call initially to set fields based on the initial dbType value
            updateFields();

            // Update fields whenever the dbType dropdown value changes
            $("#dbType").change(updateFields);
        });
        // Monitor form for changes
        var initialFormData = $("#dbsettings_form").serialize();
        $("#dbsettings_form :input").on("change input", function() {
            checkFormChanges();
        });

        function checkFormChanges() {
            var currentFormData = $("#dbsettings_form").serialize();
            if (initialFormData === currentFormData) {
                console.log('Form is same as settings.php or env; allow "Save Settings"');
                // Form is same as settings.php or env; allow "Save Settings"
                changesNeedSaving = false;
                <?php
                if (!empty($_SESSION['appSettingsModified'])) {
                    // Always require save
                ?>
                    changesNeedSaving = true; // App settings were modified on prior page
                <?php
                }
                ?>
                if (connSuccess) {
                    $("#save_button").addClass("disabled");
                    $('#next_button').removeClass('disabled');
                } else {
                    console.log("No connection yet. Save/Next disabled.")
                    $("#save_button").addClass("disabled");
                    $('#next_button').addClass('disabled');
                }
            } else {
                console.log('Form is different than settings.php; require "Test Settings" success first');
                // Form is different than settings.php; require "Test Settings" success first
                changesNeedSaving = true;
                $('#save_button').addClass('disabled');
                $('#next_button').addClass('disabled');
                // Require another test connection if they change something
                connSuccess = false;
            }
            updateFields();
        }

        function updateFields() {
            if ($("#dbType").val() === "sqlite3") {
                $("#dbNameLabel").text("<?php etranslate('SQLite3 File Path') ?>:");
                $("#loginRow, #passwordRow, #serverRow").hide();
                // Remove required attribute from server, login, and password fields for SQLite3
                $("#server, #login, #password").removeAttr("required");
            } else {
                $("#dbNameLabel").text("<?php etranslate('Database Name') ?>:");
                $("#loginRow, #passwordRow, #serverRow").show();
                // Add required attribute back to server, login, and password fields for other databases
                $("#server, #login, #password").attr("required", "required");
            }
        }

        $('#next_button').click(function(e) {
            if ($(this).hasClass('disabled')) {
                e.preventDefault(); // Prevent the default action (navigation) if the anchor has the 'disabled' class
            }
        });
        checkFormChanges();
        <?php
        // On initial page load, enable the Next button if we can already connect using what is in settings.php
        if ($canConnectDb && empty($_SESSION['appSettingsModified'])) {
        ?>
            $('#next_button').removeClass('disabled');
        <?php
        }
        ?>
    });
</script>