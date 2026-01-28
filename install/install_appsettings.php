<?php
$baseNote = translate("When choosing XXX, changes will need to be made to the file auth-settings.php before user authentication will work properly.");
$ldapNote = str_replace("XXX", "LDAP", $baseNote);
$nisNote = str_replace("XXX", "NIS", $baseNote);
$imapNote = str_replace("XXX", "IMAP", $baseNote);
$joomlaNote = str_replace("XXX", "Joomla", $baseNote);
$readonly = ' ';
$disabled =  ' ';
if ($usingEnv) {
    $readonly = 'readonly';
    $disabled = 'disabled';
}
if (empty($_SESSION['user_inc'])) {
    $_SESSION['user_inc'] = 'user.php';
}
// echo "appSettingsModified: " . ($appSettingsModified?"true":"false") . " <br>";
?>
<div class="alert alert-info" role="alert" <?php
                                            if (!$usingEnv) {
                                                echo ' style="display:none;"';
                                            }
                                            ?>>
    <h4 class="alert-heading">Warning</h4>
    <span id="auth-note">
        <?php
        if ($usingEnv) {
            echo translate('You are using environment variables for your settings and must make changes externally.');
        }
        if (!$appSettingsCorrect) {
            echo '<br>' . translate('You cannot continue until you set the proper environment variables.');
        }
        ?>
    </span>
</div>

<table class="table">
    <tbody>
        <tr>
            <td><label data-toggle="tooltip" title="<?php etranslate('Specify how users will be prompted for username and password.'); ?>"><?php echo translate('User Authentication'); ?>:</label></td>
            <td>
                <select class="form-control" id="user_inc" name="user_inc" <?php echo $disabled; ?>>
                    <option value="user.php" <?php
                                                if ($_SESSION['user_inc'] == 'user.php' && ($_SESSION['use_http_auth']??'') != 'true') {
                                                    echo ' selected ';
                                                }
                                                ?>>
                        <?php
                        echo translate('Web-based via WebCalendar (default)');
                        ?>
                    </option>
                    <option value="http" <?php
                                            if ($_SESSION['user_inc'] == 'user.php' && ($_SESSION['use_http_auth']??'') == 'true') {
                                                echo " selected ";
                                            }
                                            ?>>
                        <?php
                        if (empty($PHP_AUTH_USER)) {
                            echo translate('Web Server (not detected)');
                        } else {
                            echo translate('Web Server (detected)');
                        }
                        ?>
                    </option>

                    <option value="none" <?php
                                            if (($_SESSION['single_user']??'') == 'Y') {
                                                echo " selected ";
                                            }
                                            ?>>
                        <?php etranslate('Single-User Mode'); ?>
                    </option>
                </select>
            </td>
        </tr>
        <tr id="userDbRow">
            <td><label data-toggle="tooltip" title="<?php etranslate('Specify where the user login and password will be verified against.  If an option is disabled, the required PHP module was not found.'); ?>"><?php echo translate('User Database'); ?>:</label></td>
            <td>
                <select class="form-control" id="user_db" name="user_db" <?php echo $disabled; ?>>
                    <option value="user.php" <?php
                                                if ($_SESSION['user_inc'] == 'user.php') {
                                                    echo ' selected';
                                                }
                                                ?>><?php etranslate('WebCalendar User Database (default)'); ?></option>

                    <option value="user-ldap.php" <?php
                                                    if (!function_exists('ldap_connect')) {
                                                        echo ' disabled ';
                                                    } else {
                                                        if ($_SESSION['user_inc'] == 'user-ldap.php') {
                                                            echo ' selected';
                                                        }
                                                    }
                                                    ?>>LDAP</option>

                    <option value="user-nis.php" <?php
                                                    if (!function_exists('yp_match')) {
                                                        echo ' disabled ';
                                                    } else {
                                                        if ($_SESSION['user_inc'] == 'user-nis.php') {
                                                            echo ' selected';
                                                        }
                                                    }
                                                    ?>>NIS</option>

                    <option value="user-imap.php" <?php
                                                    if (!function_exists('imap_open')) {
                                                        echo ' disabled ';
                                                    } else {
                                                        if ($_SESSION['user_inc'] == 'user-imap.php') {
                                                            echo ' selected';
                                                        }
                                                    }
                                                    ?>>IMAP</option>

                    <option value="user-joomla.php" <?php
                                                    if ($_SESSION['user_inc'] == 'user-joomla.php') {
                                                        echo ' selected';
                                                    }
                                                    ?>>Joomla</option>
                </select>
            </td>
        </tr>

        <tr id="singleUserLoginRow" style="display:none;">
            <td><label data-toggle="tooltip" title="<?php etranslate('Enter login for single user mode.'); ?>"><?php etranslate('Single User Login'); ?>:</label></td>
            <td>
                <input type="text" class="form-control" name="single_user_login" <?php echo $readonly; ?>>
            </td>
        </tr>
        <tr>
            <td><label data-toggle="tooltip" title="<?php etranslate('Set calendar to readonly mode. Default is false.'); ?>"><?php etranslate('Read-Only'); ?>:</label></td>
            <td>
                <input type="checkbox" name="readonly" value="1" <?php echo $disabled; ?> <?php
                                                                                            if (!empty($_SESSION['readonly']) && $_SESSION['readonly'] == 'Y') {
                                                                                                echo ' checked ';
                                                                                            }
                                                                                            ?>>
            </td>
        </tr>
        <tr>
            <td><label data-toggle="tooltip" title="<?php etranslate('Mode selection (Production or Development). Default is Production. Development mode will enable verbose errors in the browser.'); ?>"><?php etranslate('Run Environment'); ?>:</label></td>
            <td>
                <select class="form-control" name="mode" <?php echo $disabled; ?>>
                    <option value="prod" <?php
                                            if (empty($_SESSION['mode']) || $_SESSION['mode'] != 'dev') {
                                                echo " selected ";
                                            }
                                            ?>>Production</option>
                    <option value="dev" <?php
                                        if (!empty($_SESSION['mode']) && $_SESSION['mode'] == 'dev') {
                                            echo " selected ";
                                        }
                                        ?>>Development</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>
<script>
    function handlePulldownUpdate() {
        if ($('#user_inc').val() == 'none') {
            $('#singleUserLoginRow').show();
            $('#userDbRow').hide();
            $('.alert').hide();
        } else {
            $('#singleUserLoginRow').hide();
            $('#userDbRow').show();
            if ($('#user_db').val() == 'user-ldap.php') {
                $('#auth-note').text('<?php echo $ldapNote; ?>');
                $('.alert').show();
            } else if ($('#user_db').val() == 'user-nis.php') {
                $('#auth-note').text('<?php echo $nisNote; ?>');
                $('.alert').show();
            } else if ($('#user_db').val() == 'user-imap.php') {
                $('#auth-note').text('<?php echo $imapNote; ?>');
                $('.alert').show();
            } else if ($('#user_db').val() == 'user-joomla.php') {
                $('#auth-note').text('<?php echo $joomlaNote; ?>');
                $('.alert').show();
            } else {
                $('.alert').hide();
            }
        }
    }
    $(document).ready(function() {
        // Attach the event handler to the 'form_user_inc' dropdown
        $('#user_inc').change(function() {
            handlePulldownUpdate();
        });
        $('#user_db').change(function() {
            handlePulldownUpdate();
        });

        // Initial check (in case the form is reloaded with the dropdown already set to 'none' or 'ldap')
        handlePulldownUpdate();
    });
</script>

<?php
if ($usingEnv) {
    if ($appSettingsCorrect) {
        printNextPageButton($action);
    }
} else {
    printSubmitButton($action, $html ?? null, $buttonLabel ?? null);
}
?>
