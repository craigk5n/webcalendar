<?php
/**
 * Main page for install/config of db settings.
 * This page is used to create/update includes/settings.php and it also supports
 * docker installs that use env vars for settings.
 *
 * Release Update Procedures:
 *   - Change the version using the bump_version.sh script.
 *     This will update the version number in all the required places.
 *   - Make sure the last entry in all the upgrade-*.sql files reference
 *     this same version. For example, for "v1.0.0", there should be a
 *     comment of the format:    /*upgrade_v1.0.0 */
       /* ( Don't remove this line as it leads to nested C-Style comments )
 *     If there are NO db changes, then you should just modify the
 *     the last comment to be the new version number. If there are
 *     db changes, you should create a new entry in the *.sql files
 *     that detail the SQL to upgrade and also update sql/upgrade-sql.php and
 *     sql/upgrade_matrix.php.
 *
 * About Version Numbers:
 *   From now on, we should only be using "vN.N.N" format for versions.
 *   (No more "v.1.12+CVS", for example.)
 *
 * Security:
 *   The first time this page is accessed, there are no security precautions.
 *   The user is prompted to generate a config password. From then on, users must
 *   know this password to make any changes to the settings in settings.php.
 *
 */

require_once '../includes/config.php';
require_once '../includes/dbi4php.php';
require_once '../includes/formvars.php';
require_once '../includes/load_assets.php';
require_once '../includes/translate.php';
require_once 'default_config.php';
require_once 'install_functions.php';
require_once 'sql/upgrade_matrix.php';

$debugInstaller = false; // Set to true to get more details on the installer pages

do_config(true);
ini_set('session.cookie_lifetime', 3600);  // 3600 seconds = 1 hour
session_name('WebCalendar-Install-' . __DIR__);
session_start();
if (empty($_SESSION['initialized'])) {
    // New session.  Load the current settings found in either env vars or includes/settings.php
    // into $_SESSION.
    setSettingsInSession();
    $_SESSION['initialized'] = 1;
}
$validUser = (isset($_SESSION['validUser']) && !empty($_SESSION['validUser'])) ? true : false;
$phpSettingsAcked = (isset($_SESSION['phpSettingsAcked']) && !empty($_SESSION['phpSettingsAcked'])) ? true : false;

function tryDbConnect()
{
    global $settings, $db_database;
    if (!isset($_SESSION['db_host']) || !isset($_SESSION['db_login']) || !isset($_SESSION['db_database'])) {
        return false;
    }
    $c = @dbi_connect(
        $_SESSION['db_host'],
        $_SESSION['db_login'],
        $_SESSION['db_password'],
        $_SESSION['db_database'],
        false
    );
    return !empty($c);
}

function getAdminUserCount()
{
    $count = 0;
    $sql = 'SELECT cal_value FROM webcal_config WHERE cal_setting = ?';
    $res = dbi_execute(
        'SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = ?',
        ['Y'],
        false,
        false
    );
    if ($res) {
        $row = dbi_fetch_row($res);
        if ($row) {
            $count = $row[0];
        }
    }
    return $count;
}

function getNextStepAction($action)
{
    global $steps;
    for ($i = 0; $i < count($steps) - 1; $i++) {
        if ($steps[$i]['step'] == $action) {
            return $steps[$i + 1]['step'];
        }
    }
    echo "NULL!";
    exit;
    return null;
}

function getStepUrl($action)
{
    return basename($_SERVER['PHP_SELF']) . '?action=' . htmlentities($action);
}

function getNextStepUrl($action)
{
    return basename($_SERVER['PHP_SELF']) . '?action=' . getNextStepAction($action);
}

function getStepName($action)
{
    global $steps;
    for ($i = 0; $i < count($steps); $i++) {
        if ($steps[$i]['step'] == $action) {
            return $steps[$i]['name'];
        }
    }
    return null;
}

function printNextPageButton($action, $extraHtml = '')
{
    $nextStep = getNextStepAction($action);
    $description = getStepName($nextStep);
    echo '<a href="' . getNextStepUrl($action) . '" class="btn btn-primary" ' .
        $extraHtml . '>' . translate('Next') . ': ' . $description . '</a>';
}

function printSubmitButton($action, $extraHtml = '', $buttonLabel = '')
{
    if (empty($buttonLabel)) {
        $buttonLabel = translate('Submit');
    }
    $nextStep = getNextStepAction($action);
    echo '<button type="submit" class="btn btn-primary" ' .
        $extraHtml . '>' . $buttonLabel . '</button>';
}

function redirectToAction($action)
{
    $nextUrl = getStepUrl($action);
    header("Location: $nextUrl");
    echo "<html><a href=\"$nextUrl\">Redirect</a></html>";
    exit;
}

function redirectToNextAction()
{
    global $action;

    // Sub-pages should redirect to $nextUrl after a successful form submission
    $nextUrl = getNextStepUrl($action);
    header("Location: $nextUrl");
    echo "<html><a href=\"$nextUrl\">Redirect</a></html>";
    exit;
}

function redirectToFurthestAvailableAction()
{
    global $steps;

    $lastStep = null;
    foreach ($steps as $step) {
        if ($step['complete']) {
            $lastStep = $step['step'];
        }
    }
    // Sub-pages should redirect to $nextUrl after a successful form submission
    $nextUrl = getNextStepUrl($lastStep);
    header("Location: $nextUrl");
    echo "<html><a href=\"$nextUrl\">Redirect</a></html>";
    exit;
}

// Below we analyze the current status to determine which steps are complete.
// If this is a start of a new session, we either read from includes/settings.php or
// get settings from env vars (which takes precedant).  The values get stored in
// the session ($_SESSION).  As the user makes changes to settings, those changes
// will only apply to $_SESSION values until the user presses the "Save Settings" button
// as part of the "Database Configuration" step.
// Note: That button is not available when using env vars since those settings must be
// changed outside of the WebCalendar file system.

$usingEnv = getenv('WEBCALENDAR_USE_ENV') ? true : false;
$versionTooltip = str_replace('XXX', '8.0', 'Only PHP XXX or later is supported by WebCalendar');
$php_settings = [
    // Description, required setting/value, found value, is-correct, tooltip explanation
    [
        translate('PHP Version'), '8.0+', phpversion(), version_compare(phpversion(), '8.0', '>='),
        $versionTooltip
    ],
    [
        translate('GD Module'), 'Installed', function_exists('imagepng') ? translate('Installed') : translate('Not found'),
        function_exists('gd_info'),
        translate('GD module required for gradient background colors')
    ],
    [
        translate('Allow File Uploads'), 'ON', get_php_setting('file_uploads'), get_php_setting('file_uploads') == 'ON',
        translate('File uploads are required to upload category icons')
    ],
    [
        translate('Allow URL fopen'), 'ON', get_php_setting('allow_url_fopen'), get_php_setting('allow_url_fopen') == 'ON',
        translate('Remote URL fopen is required to load remote calendars')
    ],
    [
        translate('Safe Mode'), 'OFF', get_php_setting('safe_mode'), get_php_setting('safe_mode') == 'OFF',
        translate('Safe Mode needs to be disabled to allow setting env variables to specify the timezone')
    ]
];
//echo "<pre>"; print_r($php_settings); echo "</pre>";
// Has the user modified the App Settings so they are different than settings.php
if (empty($_SESSION['appSettingsModified'])) {
    $appSettingsModified = false;
} else {
    $appSettingsModified = true;
    $_SESSION['appSettingsModified'] = 1;
}
// Can we connect?
$connectError = '';
$canConnectDb = tryDbConnect();
if (!$canConnectDb)
  $connectError = dbi_error ();
$emptyDatabase = $canConnectDb ?  isEmptyDatabase() : true;
$unsavedDbSettings = !empty($_SESSION['unsavedDbSettings']); // Keep track if Db settings were modified by not yet saved
$reportedDbVersion = 'Unknown';
$adminUserCount = 0;
$databaseExists = false;
$databaseCurrent = false;
$settingsSaved = true; // True if a valid settings.php found unless user changes settings
if ($canConnectDb) {
    $reportedDbVersion = getDbVersion();
    $detectedDbVersion = getDatabaseVersionFromSchema();
    if ($debugInstaller) {
        //echo "Db Version: $dbV <br>";
    }
    $adminUserCount = getAdminUserCount();
    if ($detectedDbVersion != $reportedDbVersion) {
        // The version set in webcal_config is different than what we found when using
        // SQL queries to examine the schema.  Override the webcal_config setting
        // with what we found.  This is likely from a missed SQL update in a prior
        // install update.
        // Show we alert the user?
    }
    if ($detectedDbVersion != 'Unknown') {
        $databaseExists = true;
        $databaseCurrent = ($detectedDbVersion == $PROGRAM_VERSION);
    } else if ($_SESSION['db_type'] == 'sqlite3') {
        // This is good enough to consider the sqlite3 database created.  There is no separate create database function.
        $databaseExists = true;
    }
}
// Check PHP settings, set to true only if ALL settings are correct
$phpSettingsCorrect = true;
if ($debugInstaller && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "<h3>App Settings</h3><ul>\n";
}
foreach ($php_settings as $setting) {
    if ($debugInstaller && $_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "<li> $setting[0]: $setting[3] </li>\n";
    }
    if (!$setting[3]) {
        $phpSettingsCorrect = false;
    }
}
if ($debugInstaller && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "</ul>\n";
}
$appSettingsCorrect = isset($_SESSION['readonly']) && isset($_SESSION['user_inc']) && isset($_SESSION['use_http_auth']) && isset($_SESSION['single_user'])
    && isset($_SESSION['mode']);

// If we found an existing db, then this is an update rather than an install.
$installType =  ($detectedDbVersion == 'Unknown') ? 'Install' : 'Upgrade';

$steps = [
    ["step" => "welcome", "name" => "Welcome / Status", "complete" => true],
    ["step" => "auth", "name" => "Authentication", "complete" => isset($_SESSION["validUser"])],
    ["step" => "phpsettings", "name" => "PHP Settings", "complete" => $phpSettingsCorrect || !empty($phpSettingsAcked)],
    ["step" => "appsettings", "name" => "Application Settings", "complete" => $appSettingsCorrect],
    ["step" => "dbsettings", "name" => "Database Configuration", "complete" => $canConnectDb && !$unsavedDbSettings],
    ["step" => "createdb", "name" => "Create Database", "complete" => $databaseExists && !$unsavedDbSettings],
    ["step" => "dbtables", "name" => "Create/Update Tables", "complete" => $databaseCurrent && !$unsavedDbSettings],
    ["step" => "dbload", "name" => "Load Defaults", "complete" => !$emptyDatabase],
    ["step" => "adminuser", "name" => "Create Admin User", "complete" => $adminUserCount > 0 && !$unsavedDbSettings],
    ["step" => "finish", "name" => "Completion", "complete" => false]
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'welcome';
    // Make sure we received the CSRF token
    if (empty($_POST['csrf_form_key'])) {
      $_SESSION['alert'] = translate('Your form post was either missing a required session token or timed out.');
      redirectToAction($action);
    }
} else {
    $action = $_GET['action'] ?? 'welcome';
}

if ($action == 'logout') { // This is helpful when developing/debugging the installer
    session_destroy();
    redirectToAction('welcome');
}
// Use array_column to get all 'name' values from the $steps array
$validActions = array_column($steps, 'step');
// Check if $action is one of the valid actions
if (in_array($action, $validActions)) {
    // $action is valid
} else {
    // $action is invalid or not provided
    $action = 'welcome'; // switch back to first page
}

// Make sure all prior steps are valid before allowing the user to access a later step.
// Example: don't let user access the appsettings page if they have not logged in on the auth page.
foreach ($steps as $astep) {
    if ($astep['step'] == $action) {
        // This is the step the user is requesting.
        // No prior not-complete steps.  Let them proceed.
        break;
    }
    if (!$astep['complete']) {
        // We found a not-completed step before the one the user is requesting.
        // Redirect the user back to this step.
        // This could happen if the user waits over an hour before proceeding and the session times out,
        // causing them to no longer be logged in ('auth' action).
        redirectToAction($astep['step']);
    }
}

// TODO: Handle form POST here...
$error = '';
// If the form handler fails, the $error variable should be set.
// If it succeeds, it should do a redirect and exit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The request is a POST request
    // Handle the POST data
    $filename = basename('install_' . $action . '_handler.php');
    if (file_exists($filename)) {
        include_once($filename);
    } else {
        $error =  "Missing file: $filename";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php
if ($debugInstaller) {
    echo "phpSettingsAcked: $phpSettingsAcked <br>";
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebCalendar Installer</title>
    <?php
    $ASSETS = str_replace('src="pub', 'src="../pub', $ASSETS);
    $ASSETS = str_replace('href="pub', 'href="../pub', $ASSETS);
    echo $ASSETS; // prints out the HTML <script> tags for bootstrap and jquery
    ?>
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <?php
    if ($debugInstaller) {
        echo "<h3>SESSION</h3><pre>";
        print_r($_SESSION);
        echo "</pre>";
    }
    ?>
    <div class="container mt-5">
        <div class="titlecontainer">
            <img src="webcal_installer_sm.png" alt="install icon">
            <h1 class="heading">WebCalendar <?php echo $installType; ?></h1>
        </div>

        <?php
        // Get status for each step
        $settingsStorage = getenv('WEBCALENDAR_USE_ENV') ? translate("Environment variables") : "includes/settings.php";
        $dbConnectionStatus = $canConnectDb ? "Can connect" : ("Cannot connect" . (empty($connectError) ? "" : ": " . $connectError));
        $setupVersion = $PROGRAM_VERSION;
        $phpVersionStatus = (version_compare(phpversion(), '8.0', '>=')) ? "Supported" : "Not supported";
        ?>

        <!-- Start of the row for two columns -->
        <div class="row">

            <!-- Column for Install/Upgrade Steps -->
            <div class="col-md-6">

                <h2 class="mt-4"><?php echo $installType; ?> Steps</h2>
                <ul class="list-unstyled">
                    <?php
                    $step = $steps[0];
                    $priorStepsTrue = true;
                    for ($i = 0; $i < count($steps); $i++) {
                        $astep = $steps[$i];
                        echo "<li> ";
                        if ($astep['complete']) {
                            echo ' <img src="../images/bootstrap-icons/check-circle.svg" alt="X"> ';
                        } else {
                            echo ' <img src="../images/bootstrap-icons/circle.svg" alt="-"> ';
                        }
                        if ($astep['step'] == $action) {
                            $step = $astep;
                            echo '<strong>';
                        } else {
                            // Provide clickable link if all links up to this step have been completed,
                            // allowing user to go back.
                            if ($priorStepsTrue) {
                                echo '<a href="' . basename($_SERVER['PHP_SELF']) . '?action=' . $astep['step'] . '">';
                            }
                        }
                        echo htmlentities($astep['name']);
                        if ($priorStepsTrue) {
                            echo '</a>';
                        }
                        if ($astep['step'] == $action) {
                            echo '</strong>&nbsp;&nbsp; <img src="../images/bootstrap-icons/arrow-left.svg" alt="<---">';
                        }
                        echo "</li>\n";
                        if (!$astep['complete']) {
                            $priorStepsTrue = false;
                        }
                    }
                    ?>
                </ul>

            </div>

            <!-- Column for System Status -->
            <div class="col-md-6">
                <h2 class="mt-4">System Status</h2>

                <p>
                    Settings storage: <strong><?= $settingsStorage; ?></strong><br>
                    Database connection: <strong><?= $dbConnectionStatus; ?></strong><br>
                    Number of admin users: <strong><?= $adminUserCount; ?></strong><br>
                    WebCalendar version (installer): <strong><?= $setupVersion; ?></strong><br>
                    WebCalendar version (database): <strong><?= $detectedDbVersion; ?></strong><br>
                    PHP version: <strong><?= $phpVersionStatus; ?> (<?= phpversion(); ?>)</strong><br>
                </p>
            </div>

        </div> <!-- End of the row -->

        <div class="mt-5">
            <h3><?php echo $step['name']; ?></h3>
            <form id="<?php echo htmlentities($action); ?>_form" method="POST" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
                <?php
                echo csrf_form_key ();
                if ($error) {
                    echo "<div class='alert alert-danger'>" . htmlentities($error) . "</div>";
                }
                ?>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php
                $filename = basename('install_' . $action . '.php');
                if (file_exists($filename)) {
                    include_once($filename);
                } else {
                    echo "Missing file: $filename <br>";
                    echo "Error: unsupported action '" . htmlentities($action) . "'";
                }
                ?>
            </form>
        </div>
    </div>

    <?php
    if (!empty($_SESSION['alert'])) {
    ?>
        <div class="modal fade" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="alertModalLabel"><?php etranslate('Info'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo $_SESSION['alert']; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>


    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
            <?php
            if (!empty($_SESSION['alert'])) {
            ?>
                $('#alertModal').modal('show');
            <?php
                $_SESSION['alert'] = null;
            }
            ?>
        });
    </script>

    <br>
</body>

</html>
