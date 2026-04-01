#!/usr/bin/php -q
<?php
/**
 * Convert WebCalendar database from latin1 to UTF-8.
 *
 * This script fixes mojibake caused by upgrading to v1.9.15+ where the
 * database connection now uses utf8mb4, but existing data was stored as
 * latin1 bytes (or UTF-8 bytes in latin1 columns).
 *
 * Usage:
 *   php tools/convert_latin1_to_utf8.php [--dry-run] [--force]
 *
 * Options:
 *   --dry-run   Show what would be changed without modifying data
 *   --force     Skip confirmation prompt
 *
 * MySQL only. Back up your database before running this script.
 *
 * See: https://github.com/craigk5n/webcalendar/issues/626
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$force = in_array('--force', $argv ?? []);

// Load settings directly — avoids WebCalendar class phase map restrictions.
// settings.php uses a "key: value" comment format, not PHP variables.
$settingsFile = __DIR__ . '/../includes/settings.php';
if (!file_exists($settingsFile)) {
  echo "Error: includes/settings.php not found.\n";
  exit(1);
}
$settingsContent = file_get_contents($settingsFile);
$settings = [];
foreach (['db_type', 'db_host', 'db_database', 'db_login', 'db_password'] as $key) {
  if (preg_match('/' . $key . ':\s*(.*)/', $settingsContent, $m)) {
    $settings[$key] = trim($m[1]);
  }
}
$db_type = $settings['db_type'] ?? '';
$db_host = $settings['db_host'] ?? 'localhost';
$db_database = $settings['db_database'] ?? '';
$db_login = $settings['db_login'] ?? '';
$db_password = $settings['db_password'] ?? '';

// Only MySQL/MariaDB is supported by this script.
if (empty($db_type) || !in_array($db_type, ['mysql', 'mysqli'])) {
  echo "This script only supports MySQL/MariaDB (db_type=$db_type).\n";
  echo "For PostgreSQL, see the comments at the end of this script.\n";
  exit(1);
}

$c = new mysqli($db_host, $db_login, $db_password, $db_database);
if ($c->connect_error) {
  echo "Error connecting to database: " . $c->connect_error . "\n";
  exit(1);
}

// Tables and their text columns that may contain user-entered non-ASCII data.
$tables = [
  'webcal_entry' => [
    ['cal_name', 'VARCHAR(80)'],
    ['cal_description', 'TEXT'],
    ['cal_location', 'VARCHAR(100)'],
  ],
  'webcal_user' => [
    ['cal_firstname', 'VARCHAR(25)'],
    ['cal_lastname', 'VARCHAR(25)'],
    ['cal_address', 'VARCHAR(75)'],
    ['cal_title', 'VARCHAR(75)'],
  ],
  'webcal_categories' => [
    ['cat_name', 'VARCHAR(80)'],
  ],
  'webcal_group' => [
    ['cal_name', 'VARCHAR(50)'],
  ],
  'webcal_view' => [
    ['cal_name', 'VARCHAR(50)'],
  ],
  'webcal_nonuser_cals' => [
    ['cal_firstname', 'VARCHAR(25)'],
    ['cal_lastname', 'VARCHAR(25)'],
  ],
  'webcal_blob' => [
    ['cal_name', 'VARCHAR(255)'],
    ['cal_description', 'VARCHAR(128)'],
  ],
  'webcal_entry_ext_user' => [
    ['cal_fullname', 'VARCHAR(50)'],
  ],
  'webcal_entry_log' => [
    ['cal_text', 'TEXT'],
  ],
  'webcal_site_extras' => [
    ['cal_data', 'TEXT'],
  ],
  'webcal_report' => [
    ['cal_report_name', 'VARCHAR(50)'],
  ],
  'webcal_import' => [
    ['cal_name', 'VARCHAR(50)'],
  ],
  'webcal_config' => [
    ['cal_value', 'VARCHAR(100)'],
  ],
  'webcal_user_pref' => [
    ['cal_value', 'VARCHAR(100)'],
  ],
];

// Map TEXT types to their BLOB equivalents for the binary conversion trick.
$textToBlobType = [
  'VARCHAR' => 'VARBINARY',
  'TEXT'    => 'BLOB',
  'MEDIUMTEXT' => 'MEDIUMBLOB',
  'LONGTEXT' => 'LONGBLOB',
];

/**
 * Given a SQL type like "VARCHAR(80)" or "TEXT", return the binary equivalent.
 */
function getBinaryType($sqlType)
{
  global $textToBlobType;
  $upper = strtoupper($sqlType);
  if (preg_match('/^(VARCHAR)\((\d+)\)$/i', $upper, $m)) {
    return 'VARBINARY(' . $m[2] . ')';
  }
  foreach ($textToBlobType as $text => $blob) {
    if ($upper === $text) {
      return $blob;
    }
  }
  return null;
}

// Step 1: Detect encoding situation by reading without charset override.
echo "=== WebCalendar Latin1 to UTF-8 Conversion Tool ===\n\n";

// Temporarily set connection to latin1 to read raw bytes.
$c->set_charset('latin1');

// Check a sample row from webcal_entry.
$result = $c->query("SELECT cal_id, cal_name, HEX(cal_name) AS hex_name FROM webcal_entry WHERE cal_name != '' LIMIT 5");
if ($result && $result->num_rows > 0) {
  echo "Sample data from webcal_entry (read as latin1):\n";
  while ($row = $result->fetch_assoc()) {
    echo "  id={$row['cal_id']} name=\"{$row['cal_name']}\" hex={$row['hex_name']}\n";
  }
  echo "\n";
} else {
  echo "No entries found in webcal_entry. Nothing to convert.\n";
  exit(0);
}

// Detect whether we have UTF-8 bytes in latin1 columns (most common case
// when the app never set a connection charset).
// Check for the telltale C3xx pattern (UTF-8 encoded chars in U+00C0-U+00FF range).
$result = $c->query(
  "SELECT COUNT(*) AS cnt FROM webcal_entry "
  . "WHERE HEX(cal_name) REGEXP 'C3[89AB][0-9A-F]'"
);
$row = $result->fetch_assoc();
$utf8InLatin1Count = (int)$row['cnt'];

$result = $c->query("SELECT COUNT(*) AS cnt FROM webcal_entry");
$row = $result->fetch_assoc();
$totalCount = (int)$row['cnt'];

echo "Entries with likely UTF-8 bytes in latin1 column: $utf8InLatin1Count / $totalCount\n\n";

// Restore utf8mb4 for the conversion.
$c->set_charset('utf8mb4');

if ($utf8InLatin1Count === 0) {
  echo "No UTF-8-in-latin1 data detected. Your data may already be correctly encoded.\n";
  echo "If you still see mojibake, the issue may be elsewhere (browser, HTTP headers, etc.).\n";
  echo "Run with --force to convert table charsets anyway.\n";
  if (!$force) {
    exit(0);
  }
}

// Step 2: Show plan and confirm.
echo "This script will convert text columns using the binary conversion method:\n";
echo "  1. ALTER COLUMN to binary type (preserves raw bytes)\n";
echo "  2. ALTER COLUMN back to text type with utf8mb4 charset\n";
echo "  3. ALTER TABLE default charset to utf8mb4\n\n";

$totalOps = 0;
foreach ($tables as $table => $columns) {
  // Check if table exists.
  $check = @$c->query("SELECT 1 FROM $table LIMIT 0");
  if ($check === false) {
    continue;
  }
  foreach ($columns as $col) {
    $totalOps++;
  }
}

echo "Tables to process: " . count($tables) . ", Column operations: $totalOps\n";

if ($dryRun) {
  echo "\n*** DRY RUN MODE - showing SQL that would be executed ***\n\n";
}

if (!$dryRun && !$force) {
  echo "\nHave you backed up your database? Type 'yes' to proceed: ";
  $input = trim(fgets(STDIN));
  if ($input !== 'yes') {
    echo "Aborted.\n";
    exit(0);
  }
}

// Step 3: Convert each table.
$errors = [];
$converted = 0;

foreach ($tables as $table => $columns) {
  // Check if table exists.
  $check = @$c->query("SELECT 1 FROM $table LIMIT 0");
  if ($check === false) {
    echo "  Skipping $table (does not exist)\n";
    continue;
  }

  echo "\nProcessing $table...\n";

  foreach ($columns as [$colName, $colType]) {
    // Check if column exists.
    $colCheck = @$c->query("SELECT $colName FROM $table LIMIT 0");
    if ($colCheck === false) {
      echo "  Skipping $table.$colName (does not exist)\n";
      continue;
    }

    $binaryType = getBinaryType($colType);
    if ($binaryType === null) {
      echo "  Skipping $table.$colName (unknown type: $colType)\n";
      continue;
    }

    // Step A: Convert to binary type.
    $sql1 = "ALTER TABLE $table MODIFY $colName $binaryType";
    // Step B: Convert back to text type with utf8mb4.
    $sql2 = "ALTER TABLE $table MODIFY $colName $colType CHARACTER SET utf8mb4";

    if ($dryRun) {
      echo "  $sql1;\n";
      echo "  $sql2;\n";
    } else {
      echo "  Converting $colName...";
      if (!$c->query($sql1)) {
        $err = "  ERROR on $table.$colName (to binary): " . $c->error;
        echo " FAILED\n$err\n";
        $errors[] = $err;
        continue;
      }
      if (!$c->query($sql2)) {
        $err = "  ERROR on $table.$colName (to utf8mb4): " . $c->error;
        echo " FAILED\n$err\n";
        $errors[] = $err;
        continue;
      }
      echo " OK\n";
      $converted++;
    }
  }

  // Convert the table default charset.
  $sql = "ALTER TABLE $table DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
  if ($dryRun) {
    echo "  $sql;\n";
  } else {
    $c->query($sql);
  }
}

echo "\n=== Done ===\n";
if ($dryRun) {
  echo "Dry run complete. Re-run without --dry-run to apply changes.\n";
} else {
  echo "Converted $converted columns.\n";
  if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $err) {
      echo "  $err\n";
    }
  } else {
    echo "No errors. Your data should now display correctly.\n";
  }
}

/*
 * PostgreSQL notes:
 * -----------------
 * PostgreSQL databases are typically already in UTF-8 (the default encoding).
 * If you have mojibake in PostgreSQL, the data was likely inserted as latin1
 * bytes into a UTF-8 database. You can fix individual rows with:
 *
 *   UPDATE webcal_entry
 *   SET cal_name = convert_from(convert_to(cal_name, 'LATIN1'), 'UTF8')
 *   WHERE cal_name != convert_from(convert_to(cal_name, 'LATIN1'), 'UTF8');
 *
 * Repeat for each affected column. Test on a few rows first.
 */
