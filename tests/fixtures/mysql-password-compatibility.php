<?php

declare(strict_types=1);

namespace WebCalendar\Services {
  function password_hash($password, $algorithm, array $options = []): string
  {
    $hash = \password_hash($password, $algorithm, $options);
    if (isset($GLOBALS['replace_password_during_rehash'])) { ($GLOBALS['replace_password_during_rehash'])(); }
    return $hash;
  }
}

namespace {
  use WebCalendar\Services\DatabasePasswordAuthentication;

  $host = getenv('WEBCALENDAR_PASSWORD_TEST_MYSQL_HOST');
  if (PHP_SAPI !== 'cli' || !$host) { throw new RuntimeException('Requires the disposable password_compatibility_test database.'); }
  $database = 'password_compatibility_test';
  $username = 'password_test';
  $password = 'synthetic-test-password';
  $db = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $db->exec('DROP TABLE IF EXISTS webcal_user');
  $db->exec('CREATE TABLE webcal_user (cal_login VARCHAR(100) PRIMARY KEY, cal_passwd VARCHAR(32) CHARACTER SET ascii COLLATE ascii_general_ci) ENGINE=InnoDB');
  require dirname(__DIR__, 2) . '/includes/dbi4php.php';
  require dirname(__DIR__, 2) . '/app/Services/DatabasePasswordAuthentication.php';
  $GLOBALS['db_type'] = 'mysqli';
  dbi_connect($host, $username, $password, $database, false);
  dbi_execute("SET SESSION sql_mode = ''");
  $legacy = md5('original');
  $db->prepare('INSERT INTO webcal_user VALUES (?, ?)')->execute(['fixture', $legacy]);

  function check(bool $condition, string $message): void
  {
    if (!$condition) { throw new RuntimeException($message); }
  }

  $proof = DatabasePasswordAuthentication::authenticate('fixture', 'original', $legacy);
  check($proof === DatabasePasswordAuthentication::subject($legacy), 'Narrow non-strict storage must retain the verified legacy proof.');
  check($db->query('SELECT cal_passwd FROM webcal_user')->fetchColumn() === $legacy, 'A narrow column must not truncate or rewrite the legacy credential.');

  $db->exec('ALTER TABLE webcal_user MODIFY cal_passwd VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci');
  $proof = DatabasePasswordAuthentication::authenticate('fixture', 'original', $legacy);
  $upgraded = $db->query('SELECT cal_passwd FROM webcal_user')->fetchColumn();
  check(password_verify('original', $upgraded), 'Widened storage must accept a valid rehash.');
  check(!password_needs_rehash($upgraded, PASSWORD_DEFAULT), 'Rehash must use current password defaults.');
  check($proof === DatabasePasswordAuthentication::subject($upgraded), 'Proof must bind the successfully stored upgrade.');

  $db->prepare('UPDATE webcal_user SET cal_passwd = ?')->execute([$legacy]);
  $replacement = strtoupper($legacy);
  $GLOBALS['replace_password_during_rehash'] = static function () use ($db, $replacement): void {
    $db->prepare('UPDATE webcal_user SET cal_passwd = ?')->execute([$replacement]);
  };
  check(DatabasePasswordAuthentication::authenticate('fixture', 'original', $legacy) === null,
    'Case-insensitive column collation must not weaken the exact-hash compare-and-swap.');
  check($db->query('SELECT cal_passwd FROM webcal_user')->fetchColumn() === $replacement,
    'A concurrent replacement must not be overwritten or restored.');
  unset($GLOBALS['replace_password_during_rehash']);
  echo "MySQL password compatibility passed: narrow non-strict storage, valid upgrades, exact-hash CAS and replacement preservation.\n";
}
