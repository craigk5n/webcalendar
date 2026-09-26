<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Dev\DevGuard;
use WebCalendar\Dev\Seeder;

require_once __DIR__ . '/../includes/classes/Dev/DevGuard.php';
require_once __DIR__ . '/../includes/classes/Dev/Seeder.php';

/**
 * `seed` and `reset` rewrite a calendar, so the guard around them matters as
 * much as the scenarios themselves. Both classes are pure -- they return SQL
 * and decisions rather than touching a database -- so this runs without one.
 */
final class DevSeederTest extends TestCase
{
  private const SQLITE = 'sqlite3';
  private const DBFILE = '/tmp/dev.sqlite';

  public function testMysqlIsRefusedEvenWhenForced(): void
  {
    $reason = DevGuard::refuseReason('mysqli', 'webcalendar', true, '1');

    $this->assertNotNull($reason);
    $this->assertStringContainsString('only support SQLite', (string) $reason);
  }

  public function testSqliteStillNeedsAnExplicitOptIn(): void
  {
    $this->assertNotNull(
      DevGuard::refuseReason(self::SQLITE, self::DBFILE, false, ''));
  }

  public function testForceFlagAndEnvVariableBothAllowIt(): void
  {
    $this->assertNull(
      DevGuard::refuseReason(self::SQLITE, self::DBFILE, true, ''));
    $this->assertNull(
      DevGuard::refuseReason(self::SQLITE, self::DBFILE, false, '1'));
  }

  /**
   * The reason this guard does not look at `mode`. The maintainer's own live
   * installation carries `mode: dev`, so a gate on it would have admitted a
   * production calendar while reading like protection.
   */
  public function testDevModeIsNotTreatedAsConsent(): void
  {
    // Nothing in the signature accepts a mode, by design.
    $params = (new ReflectionMethod(DevGuard::class, 'refuseReason'))
      ->getParameters();
    $names = array_map(static fn($p) => $p->getName(), $params);

    $this->assertNotContains('runMode', $names,
      'gating on run mode would admit a live install that sets mode: dev');
    $this->assertNotContains('mode', $names);
  }

  public function testRefusalNamesTheDatabaseAtRisk(): void
  {
    $reason = (string) DevGuard::refuseReason(
      self::SQLITE, '/srv/live/calendar.db', false, '');

    $this->assertStringContainsString('/srv/live/calendar.db', $reason,
      'the operator has to be able to see which database was about to change');
  }

  public function testEveryAdvertisedScenarioProducesStatements(): void
  {
    foreach (array_keys(Seeder::scenarios()) as $name) {
      $statements = Seeder::statements($name, 20260301);
      $this->assertNotEmpty($statements, "scenario $name produced nothing");
      foreach ($statements as [$sql, $params]) {
        $this->assertIsString($sql);
        $this->assertIsArray($params);
        $this->assertSame(substr_count($sql, '?'), count($params),
          "placeholder/parameter mismatch in: $sql");
      }
    }
  }

  public function testUnknownScenarioThrows(): void
  {
    $this->expectException(InvalidArgumentException::class);
    Seeder::statements('nonsense', 20260301);
  }

  /**
   * Seeded rows have to be identifiable, or reset cannot tell them from an
   * operator's own data.
   */
  public function testSeededRowsUseTheReservedIdRangeAndLoginPrefix(): void
  {
    foreach (array_keys(Seeder::scenarios()) as $name) {
      foreach (Seeder::statements($name, 20260301) as [$sql, $params]) {
        if (str_contains($sql, 'INSERT INTO webcal_entry ')) {
          $this->assertGreaterThanOrEqual(Seeder::SEED_ID_BASE, $params[0],
            "scenario $name used an id outside the reserved range");
        }
        if (str_contains($sql, 'INSERT INTO webcal_user ')) {
          $this->assertStringStartsWith(Seeder::LOGIN_PREFIX, (string) $params[0],
            "scenario $name created a user without the seed prefix");
        }
      }
    }
  }

  /**
   * The conflicts scenario is only useful if the events actually overlap, and
   * if the adjacent pair actually does not.
   */
  public function testConflictScenarioOverlapsAndAlsoProvidesEdgeCases(): void
  {
    $events = [];
    foreach (Seeder::statements('conflicts', 20260301) as [$sql, $params]) {
      if (str_contains($sql, 'INSERT INTO webcal_entry ')) {
        $events[$params[10]] = ['time' => $params[3], 'minutes' => $params[6]];
      }
    }

    $this->assertCount(5, $events);
    $a = $events['Conflict A 10:00-11:00'];
    $b = $events['Conflict B 10:30-11:30'];
    // Times are HHMMSS, so A at 100000 lasting 60 minutes ends at 110000.
    $aEnds = $a['time'] + ($a['minutes'] / 60) * 10000;
    $this->assertGreaterThan($a['time'], $b['time'], 'B must start after A');
    $this->assertLessThan($aEnds, $b['time'],
      'B must start before A ends for the scenario to mean anything');
    $this->assertArrayHasKey('Adjacent 09:00-10:00', $events);
    $this->assertArrayHasKey('Adjacent 11:00-12:00', $events);
  }

  public function testRepeatsScenarioCarriesExceptionDates(): void
  {
    $exceptions = 0;
    foreach (Seeder::statements('repeats', 20260301) as [$sql, $params]) {
      if (str_contains($sql, 'webcal_entry_repeats_not')) {
        $exceptions++;
      }
    }

    $this->assertSame(2, $exceptions,
      'exception dates are the case that breaks naive recurrence expansion');
  }

  public function testResetClearsEntriesButOnlySeededAccounts(): void
  {
    $sqls = array_column(Seeder::resetStatements(), 0);
    $joined = implode(' | ', $sqls);

    $this->assertStringContainsString('DELETE FROM webcal_entry', $joined);
    $this->assertStringContainsString('DELETE FROM webcal_entry_repeats', $joined);

    foreach (Seeder::resetStatements() as [$sql, $params]) {
      if (str_contains($sql, 'webcal_user ')) {
        $this->assertStringContainsString('LIKE ?', $sql,
          'reset must not delete every user, only seeded ones');
        $this->assertSame(Seeder::LOGIN_PREFIX . '%', $params[0]);
      }
    }
    $this->assertStringNotContainsString('DELETE FROM webcal_config', $joined,
      'reset empties the calendar, it does not uninstall');
  }
}
