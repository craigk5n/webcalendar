#!/usr/bin/php -q
<?php
/**
 * Convert WebCalendar database from latin1 to UTF-8.
 *
 * This script fixes mojibake caused by upgrading to v1.9.15+ where the
 * database connection now uses utf8mb4, but existing data was stored as
 * latin1 bytes and/or UTF-8 bytes in latin1 columns.
 *
 * Older databases (especially ones upgraded across many versions) often
 * contain a MIX of both situations in the same column:
 *
 *   - Genuine latin1 bytes, e.g. "ä" stored as 0xE4, "ß" stored as 0xDF.
 *     These are NOT valid UTF-8 on their own.
 *   - UTF-8 bytes wrongly stored in a latin1 column ("double encoded"),
 *     e.g. "ä" stored as 0xC3 0xA4. These already ARE valid UTF-8.
 *
 * The naive "convert column to binary then reinterpret as utf8mb4" trick
 * only works for the second case. On genuine latin1 bytes it fails with
 * "Incorrect string value: '\xE4...'" (see issue #649).
 *
 * This script therefore decides per value, not per column:
 *   1. ALTER the column to a binary type (preserves raw bytes losslessly).
 *   2. For each distinct value:
 *        - already valid UTF-8  -> leave the bytes untouched
 *        - not valid UTF-8      -> treat as latin1 and transcode to UTF-8
 *   3. ALTER the column to utf8mb4 (all bytes are now valid UTF-8).
 *   4. Set the table default charset to utf8mb4.
 *
 * Inherent ambiguity: a genuine-latin1 value whose bytes happen to also be
 * valid UTF-8 (e.g. a user literally typed "Ã¤") is indistinguishable from
 * double-encoded data and is left as-is. This is unavoidable and standard.
 *
 * Note on "latin1": MySQL's latin1 is really Windows-1252. This script
 * transcodes non-UTF-8 bytes as ISO-8859-1, which is identical to
 * Windows-1252 for the 0xA0-0xFF range (covers ä, ö, ü, ß, é, etc.).
 * Bytes in 0x80-0x9F (smart quotes, euro sign) are rare in this data and
 * will map to their Unicode C1/Latin-1 code points; adjust manually if needed.
 *
 * Usage:
 *   php tools/convert_latin1_to_utf8.php [--dry-run] [--force]
 *
 * Options:
 *   --dry-run   Show what would be changed without modifying data
 *   --force     Skip confirmation prompt
 *
 * Database connection is read from includes/settings.php, or from these
 * environment variables if set (useful for containers and testing):
 *   WEBCALENDAR_DB_TYPE, WEBCALENDAR_DB_HOST, WEBCALENDAR_DB_DATABASE,
 *   WEBCALENDAR_DB_LOGIN, WEBCALENDAR_DB_PASSWORD
 *
 * MySQL/MariaDB only. Back up your database before running this script.
 *
 * See: https://github.com/craigk5n/webcalendar/issues/626
 *      https://github.com/craigk5n/webcalendar/issues/649
 */

// mysqli throws exceptions by default on PHP 8.1+. Turn that off so we can
// handle errors per column and keep going instead of dying mid-conversion.
mysqli_report(MYSQLI_REPORT_OFF);

$dryRun = in_array('--dry-run', $argv ?? []);
$force = in_array('--force', $argv ?? []);

// Load DB connection settings: environment variables take precedence over
// includes/settings.php (settings.php uses a "key: value" comment format).
$db_type = $db_host = $db_database = $db_login = $db_password = '';

if (getenv('WEBCALENDAR_DB_TYPE') !== false) {
  $db_type = getenv('WEBCALENDAR_DB_TYPE');
  $db_host = getenv('WEBCALENDAR_DB_HOST') ?: 'localhost';
  $db_database = getenv('WEBCALENDAR_DB_DATABASE') ?: '';
  $db_login = getenv('WEBCALENDAR_DB_LOGIN') ?: '';
  $db_password = getenv('WEBCALENDAR_DB_PASSWORD') ?: '';
} else {
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
}

// Only MySQL/MariaDB is supported by this script.
if (empty($db_type) || !in_array($db_type, ['mysql', 'mysqli'])) {
  echo "This script only supports MySQL/MariaDB (db_type=$db_type).\n";
  echo "For PostgreSQL, see the comments at the end of this script.\n";
  exit(1);
}

$c = @new mysqli($db_host, $db_login, $db_password, $db_database);
if ($c->connect_errno) {
  echo "Error connecting to database: " . $c->connect_error . "\n";
  exit(1);
}
$c->set_charset('utf8mb4');

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

// Map TEXT types to their BLOB equivalents for the binary conversion step.
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

/**
 * Decide the corrected UTF-8 bytes for a raw stored value.
 *
 * Already-valid-UTF-8 bytes are returned unchanged (covers ASCII and
 * double-encoded data). Anything else is treated as latin1/Windows-1252
 * and transcoded to UTF-8.
 *
 * @param string $bytes Raw bytes as stored in the column.
 * @return string Corrected UTF-8 bytes.
 */
function correctedUtf8($bytes)
{
  if ($bytes === '' || mb_check_encoding($bytes, 'UTF-8')) {
    return $bytes;
  }
  return mb_convert_encoding($bytes, 'UTF-8', 'ISO-8859-1');
}

// Step 1: Show a sample and detect the encoding situation.
echo "=== WebCalendar Latin1 to UTF-8 Conversion Tool ===\n\n";

// CAST(... AS BINARY) returns the raw stored bytes regardless of the
// column's declared charset or the connection charset.
$result = $c->query(
  "SELECT cal_id, HEX(CAST(cal_name AS BINARY)) AS hex_name "
  . "FROM webcal_entry WHERE cal_name <> '' LIMIT 5"
);
if ($result && $result->num_rows > 0) {
  echo "Sample data from webcal_entry (raw stored bytes):\n";
  while ($row = $result->fetch_assoc()) {
    $name = @hex2bin($row['hex_name']);
    echo "  id={$row['cal_id']} hex={$row['hex_name']}"
      . ($name !== false ? " name=\"$name\"" : '') . "\n";
  }
  echo "\n";
} else {
  echo "No entries found in webcal_entry. Nothing to convert.\n";
  exit(0);
}

// Classify every cal_name value into ASCII / valid-UTF-8-multibyte / latin1.
$asciiCount = $utf8Count = $latin1Count = 0;
$result = $c->query(
  "SELECT DISTINCT CAST(cal_name AS BINARY) AS b FROM webcal_entry "
  . "WHERE cal_name IS NOT NULL AND LENGTH(cal_name) > 0"
);
while ($result && ($row = $result->fetch_row())) {
  $b = $row[0];
  if (mb_check_encoding($b, 'ASCII')) {
    $asciiCount++;
  } elseif (mb_check_encoding($b, 'UTF-8')) {
    $utf8Count++;     // already valid UTF-8 (or double-encoded) -> keep
  } else {
    $latin1Count++;   // genuine latin1 bytes -> transcode
  }
}

echo "Distinct webcal_entry.cal_name values:\n";
echo "  pure ASCII (unchanged):                 $asciiCount\n";
echo "  already valid UTF-8 (kept as-is):       $utf8Count\n";
echo "  genuine latin1 bytes (will transcode):  $latin1Count\n\n";

// Step 2: Show plan and confirm.
echo "This script converts each text column per value:\n";
echo "  1. ALTER COLUMN to a binary type (preserves raw bytes)\n";
echo "  2. Per distinct value: keep if valid UTF-8, else transcode latin1->UTF-8\n";
echo "  3. ALTER COLUMN to utf8mb4 (all bytes are now valid UTF-8)\n";
echo "  4. ALTER TABLE default charset to utf8mb4\n\n";

$totalOps = 0;
foreach ($tables as $table => $columns) {
  if (@$c->query("SELECT 1 FROM $table LIMIT 0") === false) {
    continue;
  }
  $totalOps += count($columns);
}

echo "Tables to process: " . count($tables) . ", Column operations: $totalOps\n";

if ($dryRun) {
  echo "\n*** DRY RUN MODE - no changes will be made ***\n";
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
$transcodedValues = 0;

foreach ($tables as $table => $columns) {
  if (@$c->query("SELECT 1 FROM $table LIMIT 0") === false) {
    echo "  Skipping $table (does not exist)\n";
    continue;
  }

  echo "\nProcessing $table...\n";

  foreach ($columns as [$colName, $colType]) {
    if (@$c->query("SELECT $colName FROM $table LIMIT 0") === false) {
      echo "  Skipping $table.$colName (does not exist)\n";
      continue;
    }

    $binaryType = getBinaryType($colType);
    if ($binaryType === null) {
      echo "  Skipping $table.$colName (unknown type: $colType)\n";
      continue;
    }

    // Dry run: just report how many distinct values would be transcoded.
    if ($dryRun) {
      $would = 0;
      $res = $c->query(
        "SELECT DISTINCT CAST($colName AS BINARY) AS b FROM $table "
        . "WHERE $colName IS NOT NULL AND LENGTH($colName) > 0"
      );
      while ($res && ($row = $res->fetch_row())) {
        if (correctedUtf8($row[0]) !== $row[0]) {
          $would++;
        }
      }
      echo "  $table.$colName: would transcode $would distinct latin1 value(s)\n";
      continue;
    }

    echo "  Converting $colName...";

    // Step A: switch the column to binary so it can hold arbitrary bytes.
    if (!$c->query("ALTER TABLE $table MODIFY $colName $binaryType")) {
      $err = "  ERROR on $table.$colName (to binary): " . $c->error;
      echo " FAILED\n$err\n";
      $errors[] = $err;
      continue;
    }

    // Step B: fix only the values that are not already valid UTF-8.
    $stmt = $c->prepare("UPDATE $table SET $colName = ? WHERE $colName = ?");
    $res = $c->query(
      "SELECT DISTINCT $colName AS b FROM $table "
      . "WHERE $colName IS NOT NULL AND LENGTH($colName) > 0"
    );
    $colTranscoded = 0;
    $colError = null;
    while ($res && ($row = $res->fetch_row())) {
      $old = $row[0];
      $new = correctedUtf8($old);
      if ($new === $old) {
        continue;
      }
      $stmt->bind_param('ss', $new, $old);
      if (!$stmt->execute()) {
        $colError = "  ERROR on $table.$colName (transcode): " . $stmt->error;
        break;
      }
      $colTranscoded++;
    }
    if ($stmt) {
      $stmt->close();
    }
    if ($colError !== null) {
      echo " FAILED\n$colError\n";
      $errors[] = $colError;
      // Leave the column binary; raw bytes are intact and the run is re-runnable.
      continue;
    }

    // Step C: reinterpret the now-uniformly-valid UTF-8 bytes as utf8mb4.
    if (!$c->query("ALTER TABLE $table MODIFY $colName $colType CHARACTER SET utf8mb4")) {
      $err = "  ERROR on $table.$colName (to utf8mb4): " . $c->error;
      echo " FAILED\n$err\n";
      $errors[] = $err;
      continue;
    }

    echo " OK ($colTranscoded transcoded)\n";
    $converted++;
    $transcodedValues += $colTranscoded;
  }

  // Convert the table default charset (so new rows default to utf8mb4).
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
  echo "Converted $converted columns, transcoded $transcodedValues distinct value(s).\n";
  if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $err) {
      echo "  $err\n";
    }
    echo "\nColumns that failed were left as a binary type with their raw\n";
    echo "bytes intact. Fix the reported issue and re-run this script.\n";
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
