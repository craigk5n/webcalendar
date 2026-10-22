<?php

declare(strict_types=1);

use WebCalendar\Helpers\DateDisplay;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/dbi4php.php';
require_once __DIR__ . '/../app/Helpers/ConfigService.php';
require_once __DIR__ . '/../app/Helpers/DateDisplay.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DateDisplayTest extends TestCase
{
  private SQLite3 $connection;

  protected function setUp(): void
  {
    $GLOBALS['db_type'] = 'sqlite3';
    $this->connection = dbi_connect('', '', '', ':memory:', false);
    $this->connection->exec('CREATE TABLE webcal_config (
      cal_setting VARCHAR(50) PRIMARY KEY, cal_value VARCHAR(100))');
  }

  protected function tearDown(): void
  {
    dbi_close($this->connection);
  }

  public static function timeFormats(): array
  {
    return [
      'enabled: on the hour' => ['Y', '10:00', '10:00'],
      'enabled: meridiem suffix' => ['Y', '2:00 PM', '2:00 PM'],
      'enabled: nonzero minutes' => ['Y', '10:30', '10:30'],
      'disabled: on the hour' => ['N', '10:00', '10'],
      'disabled: meridiem suffix' => ['N', '2:00 PM', '2 PM'],
      'disabled: nonzero minutes' => ['N', '10:30', '10:30'],
      'missing: on the hour' => [null, '10:00', '10'],
      'missing: meridiem suffix' => [null, '2:00 PM', '2 PM'],
      'missing: nonzero minutes' => [null, '10:30', '10:30'],
    ];
  }

  /** @dataProvider timeFormats */
  public function testShortTimeUsesDatabaseConfiguration(?string $setting,
    string $time, string $expected): void
  {
    if ($setting !== null) {
      dbi_execute('INSERT INTO webcal_config (cal_setting, cal_value) VALUES (?, ?)',
        ['DISPLAY_MINUTES', $setting]);
    }
    // The helper's configuration comes from the database, not this legacy global.
    $GLOBALS['DISPLAY_MINUTES'] = $setting === 'Y' ? 'N' : 'Y';

    self::assertSame($expected, DateDisplay::getShortTime($time));
  }
}
