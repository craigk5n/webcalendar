<?php
/**
 * Description:
 *  This page will take look for possible security issues with
 *  this installation of WebCalendar.
 *
 * Input Parameters:
 *  None
 *
 * Security:
 *  User must be an admin user
 *  AND, if user access control is enabled, they must have access to
 *  'Security Audit'.
 */
require_once 'includes/init.php';

if (!$is_admin || (access_is_enabled()
  && !access_can_access_function(ACCESS_SECURITY_AUDIT))) {
  die_miserable_death(print_not_auth());
}

$phpinfo = getGetValue('phpinfo');
if ($phpinfo == '1') {
  print_header ( [], '', '', true );
  phpinfo();
  print_trailer(false, true, true);
  exit;
}
clearstatcache();
print_header();
?>

<h2><?php etranslate('Security Audit'); ?></h2>

<div>
  <?php etranslate('list potential security issues'); ?>
</div>
<div><a class="btn btn-secondary mt-2 text-right" href="#" onclick="window.open('security_audit.php?phpinfo=1', 'phpinfo',
  'dependent,menubar,scrollbars,height=500,width=600,innerHeight=520,outerWidth=620')"><?php etranslate('View your current PHP settings'); ?>...</a>
</div>


<table class="table table-striped mt-2 table-responsive" id="securityAudit">
  <thead>
    <tr>
      <th scope="col"><?php etranslate('Security Issue'); ?></th>
      <th scope="col"><?php etranslate('Status'); ?></th>
      <th scope="col"><?php etranslate('Details'); ?></th>
    </tr>
  </thead>

  <?php
  // Make sure they aren't still using the default admin username/password.
  print_issue(
    translate('Default admin user password'),
    (user_valid_login('admin', 'admin') == false),
    translate('You should change the password of the default admin user.')
  );

  // Is the wizard directory still present?
  print_issue(
    translate('Wizard directory exists'),
    (!is_dir('wizard')),
    translate('The wizard/ directory is still present. It is password-protected, but you may restrict access for extra security.') . ' '
    . translate('Options: restrict permissions') . ' (<code>chmod 000 wizard/</code>), '
    . translate('move outside web root, or remove it') . ' (<code>rm -rf wizard/</code>).'
  );

  // Is the main directory still writable?
  // Just see if we get an error trying to append to it.
  $wcDir = '.';
  $wcName = 'WebCalendar toplevel director';
  if (preg_match('/(.*).security_audit.php/', __FILE__, $matches)) {
    //$wcDir  = $matches[1] . '\\';
    $wcName = basename(realpath($wcDir));
  }

  $filePerms   = translate('File permissions XXX');
  $noWriteItem = translate('item XXX should not be writable');

  print_issue(
    str_replace('XXX', $wcName, $filePerms),
    (!is__writable($wcDir)),
    str_replace('XXX', htmlentities(realpath($wcDir)), $noWriteItem)
  );

  // Is the includes directory still writable?
  // Just see if we get an error trying to append to it.
  print_issue(
    str_replace('XXX', 'includes', $filePerms),
    (!is__writable('includes')),
    str_replace('XXX', get_wc_path('includes'), $noWriteItem)
  );

  // Is the includes/settings.php file still writable?
  // Unfortunately, some of the PHP file permissions calls have bugs,
  // so just see if we get an error trying to append to it.
  $fd   = @fopen('includes/settings.php', 'a+b');
  $isOk = true;
  if ($fd > 0) {
    // Error: should not be allowed to write!
    fclose($fd);
    $isOk = false;
  }
  print_issue(
    str_replace('XXX', 'includes/settings.php', $filePerms),
    $isOk,
    str_replace('XXX', get_wc_path('includes/settings.php'), $noWriteItem)
  );

  // If email or reminders are not enabled, tell them to remove the file.
  $isOk = (!file_exists('tools/send_reminders.php'));
  if ($SEND_EMAIL != 'Y') {
    // Reminders are disabled!
    print_issue(
      str_replace(
        'XXX',
        'tools/send_reminders.php',
        translate('File exists XXX')
      ),
      $isOk,
      translate('Because you have email disabled, you should remove this file.')
    );
  } else {
    // Is tools/send_reminders.php in the 'standard' location?
    print_issue(
      str_replace(
        'XXX',
        'tools/send_reminders.php',
        translate('File location XXX')
      ),
      $isOk,
      str_replace(
        'XXX',
        get_wc_path('tools/send_reminders.php'),
        translate('remove XXX if not using')
      )
    );
  }

  $sysSettingsXXX = translate('System Settings XXX');

  // Is UAC enabled?
  print_issue(
    str_replace('XXX', translate('User Access Control'), $sysSettingsXXX),
    access_is_enabled(),
    translate('consider enabling UAC')
  );

  // If Public Access enabled, make sure approvals are on
  if ($PUBLIC_ACCESS == 'Y') {
    print_issue(
      str_replace(
        'XXX',
        translate('Public access new events require approval'),
        $sysSettingsXXX
      ),
      ($PUBLIC_ACCESS_CAN_ADD != 'Y' || $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y'),
      translate('recommend approving new public events')
    );

    print_issue(
      str_replace(
        'XXX',
        translate('Require CAPTCHA validation for public access new events'),
        $sysSettingsXXX
      ),
      ($ENABLE_CAPTCHA == 'Y'),
      translate('recommend using CAPTCHA')
    );
  }

  // Is db cache directory a subdirectory of WebCalendar?
  $isOk = true;
  if (!empty($settings['db_cachedir']) && $wcDir != '.') {
    $cache = str_replace('\\', '/', $settings['db_cachedir']);
    $wcDir = str_replace('\\', '/', $wcDir);
    if (
      strncmp($cache, $wcDir, strlen($wcDir)) == 0
      && strlen($wcDir) < strlen($cache)
    ) {
      // Using a webcalendar subdirectory for db cache.
      $isOk = false;
    }
  }
  print_issue(
    translate('Database cache directory location'),
    $isOk,
    translate('db cache should be inaccessible')
  );

  $phpSettingsXXX  = translate('PHP Settings XXX');
  $recommendXXXOff = translate('recommend setting XXX Off');
  $recommendXXXOn = translate('recommend setting XXX On');

  // Check for expose_php.
  print_issue(
    str_replace('XXX', 'expose_php', $phpSettingsXXX),
    (ini_get('expose_php') == 0),
    str_replace('XXX', 'expose_php', $recommendXXXOff)
  );

  // Check for register globals.
  print_issue(
    str_replace('XXX', 'register_globals', $phpSettingsXXX),
    (ini_get('register_globals') == 0),
    str_replace('XXX', 'register_globals', $recommendXXXOff)
  );

  // Check for allow_url_fopen.
  // Recommended setting is off when remote calendars are not enabled.
  print_issue(
    str_replace('XXX', 'allow_url_fopen', $phpSettingsXXX),
    (ini_get('allow_url_fopen') == 0 || $REMOTES_ENABLED == 'Y'),
    translate('recommend setting allow_url_fopen Off')
  );

  // Check for allow_url_include.
  print_issue(
    str_replace('XXX', 'allow_url_include', $phpSettingsXXX),
    (ini_get('allow_url_include') == 0),
    str_replace('XXX', 'allow_url_include', $recommendXXXOff)
  );

  // Don't display PHP errors/warnings to end users through the browser.
  // (But this is okay for developer settings...)
  print_issue(
    str_replace('XXX', 'display_errors', $phpSettingsXXX),
    (ini_get('display_errors') == 0),
    str_replace('XXX', 'display_errors', $recommendXXXOff)
  );

  // PHP errors/warnings should be logged.
  print_issue(
    str_replace('XXX', 'log_errors', $phpSettingsXXX),
    (ini_get('log_errors') == 1),
    str_replace('XXX', 'log_errors', $recommendXXXOn)
  );

  // PHP session security.
  print_issue(
    str_replace('XXX', 'session.use_strict_mode', $phpSettingsXXX),
    (ini_get('session.use_strict_mode') == 1),
    str_replace('XXX', 'session.use_strict_mode', $recommendXXXOn)
  );
  print_issue(
    str_replace('XXX', 'session.cookie_httponly', $phpSettingsXXX),
    (ini_get('session.cookie_httponly') == 1),
    str_replace('XXX', 'session.cookie_httponly', $recommendXXXOn)
  );

  ?>
</table>

<?php
// File integrity section — verifies the signed MANIFEST.sha256 against the
// installed tree. See SECURITY_AUDIT_STATUS.md (issue #233, Story 3.5).
render_file_integrity_section();

echo print_trailer();

exit;

/* functions ... */
/**
 * print_issue (needs description)
 */
function print_issue($description, $isOk, $help)
{

  if ($isOk) {
    $img = '<img class="button-icon-inverse" src="images/bootstrap-icons/check-circle.svg">';
    $help = '&nbsp;';
  } else {
    $img = '<img class="button-icon-inverse" src="images/bootstrap-icons/exclamation-triangle-fill.svg">';
  }

  echo '<tr><td>' . $description . '</td>' .
    '<td>' . $img . '</td>' .
    '<td>' . $help . '</td></tr>' . "\n";
}

/**
 * Get the full path to a file located in the webcalendar directory.
 */
function get_wc_path($filename)
{
  if (preg_match('/(.*)security_audit.php/', __FILE__, $matches))
    return $matches[1] . $filename;
  else
    // Oops. This file is not named security_audit.php
    die_miserable_death('Crap! Someone renamed security_audit.php');
}
/**
 * Render the "File integrity" section (signed-manifest feature, issue #233).
 *
 * This is a new section added after the existing audit table. It:
 *   1. Checks for the three signed-manifest artifacts at install root.
 *      If any are missing, shows a soft notice and returns.
 *   2. Verifies the MANIFEST.sha256 Ed25519 signature. On failure, shows
 *      the reason and RETURNS — never displays scan results, because
 *      an unverified manifest cannot be trusted.
 *   3. On successful verification: parses the manifest, walks the
 *      install tree, classifies every file (MATCH/MODIFIED/MISSING/EXTRA),
 *      and renders three tables grouped by kind.
 *
 * Noise-filter support (Story 4.2) is not yet wired — this section always
 * shows every finding. The AC for Story 3.5 defers filtering to 4.2.
 */
function render_file_integrity_section(): void
{
  // Require the Security namespace classes — no composer autoloader
  // wiring per D10.
  require_once __DIR__ . '/includes/classes/Security/ReleaseKeyGenerator.php';
  require_once __DIR__ . '/includes/classes/Security/VerifyResult.php';
  require_once __DIR__ . '/includes/classes/Security/ManifestVerifier.php';
  require_once __DIR__ . '/includes/classes/Security/ManifestData.php';
  require_once __DIR__ . '/includes/classes/Security/ManifestParser.php';
  require_once __DIR__ . '/includes/classes/Security/ScanEntryKind.php';
  require_once __DIR__ . '/includes/classes/Security/ScannedFile.php';
  require_once __DIR__ . '/includes/classes/Security/ScanReport.php';
  require_once __DIR__ . '/includes/classes/Security/ExcludeRules.php';
  require_once __DIR__ . '/includes/classes/Security/InstallationScanner.php';
  require_once __DIR__ . '/includes/classes/Security/Severity.php';
  require_once __DIR__ . '/includes/classes/Security/SeverityClassifier.php';

  $rootDir = __DIR__;
  $manifestPath = $rootDir . '/MANIFEST.sha256';
  $sigPath = $rootDir . '/MANIFEST.sha256.sig';
  $pubkeyPath = $rootDir . '/release-signing-pubkey.pem';

  echo '<h3 class="mt-4">' . htmlspecialchars(
    translate('File integrity'),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
  ) . '</h3>';

  if (!is_file($manifestPath) || !is_file($sigPath) || !is_file($pubkeyPath)) {
    echo '<div class="alert alert-secondary">' . htmlspecialchars(
      translate('Manifest files not present (install may be from source or pre-1.9.x release)'),
      ENT_QUOTES | ENT_SUBSTITUTE,
      'UTF-8'
    ) . '</div>';
    return;
  }

  $verify = WebCalendar\Security\ManifestVerifier::verify(
    $manifestPath,
    $sigPath,
    $pubkeyPath
  );

  if (!$verify->valid) {
    $msg = str_replace(
      'XXX',
      $verify->reason,
      translate('Manifest signature FAILED: XXX')
    );
    echo '<div class="alert alert-danger"><strong>'
      . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '</strong></div>';
    // Trust boundary: a tampered manifest cannot be trusted to describe
    // what "should" be on disk, so never show scan results.
    return;
  }

  echo '<div class="alert alert-success">'
    . htmlspecialchars(
      translate('Manifest signature valid'),
      ENT_QUOTES | ENT_SUBSTITUTE,
      'UTF-8'
    )
    . '</div>';

  try {
    $manifest = WebCalendar\Security\ManifestParser::parse($manifestPath);
  } catch (Throwable $e) {
    echo '<div class="alert alert-danger">'
      . htmlspecialchars(
        'Manifest parse error: ' . $e->getMessage(),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
      )
      . '</div>';
    return;
  }

  // Defaults (D9) + any admin-supplied extras from the
  // SECURITY_AUDIT_EXTRA_EXCLUDES setting (Story 4.1).
  // webcal_config values land in $GLOBALS via load_global_settings().
  $extraExcludes = $GLOBALS['SECURITY_AUDIT_EXTRA_EXCLUDES'] ?? null;
  $excludes = WebCalendar\Security\ExcludeRules::withDefaults(
    is_string($extraExcludes) ? $extraExcludes : null
  );

  $report = WebCalendar\Security\InstallationScanner::scan(
    $manifest,
    $rootDir,
    $excludes
  );

  $summary = str_replace(
    'XXX',
    (string) $report->matchedCount,
    translate('Scanned XXX files against manifest')
  );
  echo '<p>' . htmlspecialchars(
    $summary . ' (v' . $manifest->version . ', '
      . $manifest->buildTimestamp->format('Y-m-d') . ')',
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
  ) . '</p>';

  render_integrity_table(translate('Modified files'), $report->modified);
  render_integrity_table(translate('Missing files'), $report->missing);
  render_integrity_table(translate('Extra files'), $report->extra);

  if ($report->modified === [] && $report->missing === [] && $report->extra === []) {
    echo '<div class="alert alert-success">'
      . htmlspecialchars(
        translate('No file integrity issues detected.'),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
      )
      . '</div>';
  }
}

/**
 * @param list<WebCalendar\Security\ScannedFile> $files
 */
function render_integrity_table(string $heading, array $files): void
{
  if ($files === []) {
    return;
  }

  $encHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  echo '<h4 class="mt-3">' . $encHeading
    . ' <span class="badge bg-secondary">' . count($files) . '</span></h4>';
  echo '<table class="table table-sm table-striped table-responsive">';
  echo '<thead><tr>'
    . '<th>' . htmlspecialchars(translate('Path'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</th>'
    . '<th>' . htmlspecialchars(translate('Severity'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</th>'
    . '<th>' . htmlspecialchars(translate('Action'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</th>'
    . '</tr></thead><tbody>';
  foreach ($files as $f) {
    $sev = WebCalendar\Security\SeverityClassifier::classify($f);
    echo '<tr>'
      . '<td><code>' . htmlspecialchars($f->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></td>'
      . '<td>' . severity_badge_html($sev) . '</td>'
      . '<td>' . htmlspecialchars(action_hint_for($f->kind), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>'
      . '</tr>';
  }
  echo '</tbody></table>';
}

function severity_badge_html(WebCalendar\Security\Severity $s): string
{
  switch ($s) {
    case WebCalendar\Security\Severity::CRITICAL:
      return '<span class="badge bg-danger">'
        . htmlspecialchars(translate('Critical'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</span>';
    case WebCalendar\Security\Severity::WARN:
      return '<span class="badge bg-warning text-dark">'
        . htmlspecialchars(translate('Warning'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</span>';
    case WebCalendar\Security\Severity::INFO:
      return '<span class="badge bg-info text-dark">'
        . htmlspecialchars(translate('Info'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</span>';
  }
  return '';
}

function action_hint_for(WebCalendar\Security\ScanEntryKind $k): string
{
  switch ($k) {
    case WebCalendar\Security\ScanEntryKind::MODIFIED:
      return translate('Restore from release zip if not intentional');
    case WebCalendar\Security\ScanEntryKind::MISSING:
      return translate('Restore from release zip');
    case WebCalendar\Security\ScanEntryKind::EXTRA:
      return translate('Review contents and remove if not legitimate');
  }
  return '';
}

/**
 * Determine if a directory or file is writable
 */
function is__writable($path)
{
  //Will work despite Windows ACLs bug.
  //NOTE: use a trailing slash for folders!!!
  //see http://bugs.php.net/bug.php?id=27609
  //see http://bugs.php.net/bug.php?id=30931

  if ($path[strlen($path) - 1] == '/') // recursively return a temporary file path
    return is__writable($path . uniqid(mt_rand()) . '.tmp');
  else if (@is_dir($path))
    return is__writable($path . '/' . uniqid(mt_rand()) . '.tmp');

  // Check tmp file for read/write capabilities.
  $rm = @file_exists($path);
  $f = @fopen($path, 'a');
  if ($f === false)
    return false;

  @fclose($f);
  if (!$rm)
    @unlink($path);

  return true;
}
?>
