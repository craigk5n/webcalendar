<?php

declare(strict_types=1);

namespace WebCalendar\Dev;

/**
 * Named database states for the development loop.
 *
 * Verifying a recurrence or conflict fix otherwise means installing,
 * clicking through the UI and reading a screenshot. With these it means two
 * commands and a diff.
 *
 * Every method returns SQL rather than executing it: the statements can then
 * be asserted in a unit test with no database, and the one place that talks
 * to a database is bin/webcal.php. Seeded rows use ids from SEED_ID_BASE up
 * and logins prefixed `seed_`, so `reset` can identify its own work without
 * touching anything an operator created.
 */
final class Seeder
{
  /** Seeded rows live well above anything an install creates by hand. */
  public const SEED_ID_BASE = 900000;

  public const LOGIN_PREFIX = 'seed_';

  /**
   * @return array<string, string> scenario name => one-line description
   */
  public static function scenarios(): array
  {
    return [
      'month' => 'One event on most days of the base month, for view and paging checks.',
      'conflicts' => 'Three events overlapping the same hour, plus one that only touches the edges.',
      'repeats' => 'A daily series with two exception dates and a weekly series with an end date.',
      'groups' => 'Two users in a group, each owning events, with layers pointing at each other.',
    ];
  }

  public static function exists(string $scenario): bool
  {
    return array_key_exists($scenario, self::scenarios());
  }

  /**
   * Statements that build a scenario, as [sql, params] pairs.
   *
   * @param int $baseDate YYYYMMDD the scenario is anchored to
   * @return list<array{0: string, 1: list<mixed>}>
   */
  public static function statements(string $scenario, int $baseDate): array
  {
    return match ($scenario) {
      'month' => self::month($baseDate),
      'conflicts' => self::conflicts($baseDate),
      'repeats' => self::repeats($baseDate),
      'groups' => self::groups($baseDate),
      default => throw new \InvalidArgumentException(
        'Unknown scenario: ' . $scenario
      ),
    };
  }

  /**
   * Statements that remove everything seeding creates, and every calendar
   * entry besides. `reset` means empty, not "empty of my rows".
   *
   * @return list<array{0: string, 1: list<mixed>}>
   */
  public static function resetStatements(): array
  {
    $out = [];
    foreach ([
      'webcal_entry_repeats_not', 'webcal_entry_repeats', 'webcal_entry_user',
      'webcal_entry_categories', 'webcal_entry_ext_user', 'webcal_entry_log',
      'webcal_reminders', 'webcal_blob', 'webcal_entry',
    ] as $table) {
      $out[] = ['DELETE FROM ' . $table, []];
    }

    // Only seeded accounts and groups; an operator's own users survive.
    $like = self::LOGIN_PREFIX . '%';
    $out[] = ['DELETE FROM webcal_group_user WHERE cal_login LIKE ?', [$like]];
    $out[] = ['DELETE FROM webcal_user_layers WHERE cal_login LIKE ? OR cal_layeruser LIKE ?', [$like, $like]];
    $out[] = ['DELETE FROM webcal_group WHERE cal_group_id >= ?', [self::SEED_ID_BASE]];
    $out[] = ['DELETE FROM webcal_user_pref WHERE cal_login LIKE ?', [$like]];
    $out[] = ['DELETE FROM webcal_user WHERE cal_login LIKE ?', [$like]];

    return $out;
  }

  /**
   * @return list<array{0: string, 1: list<mixed>}>
   */
  private static function month(int $baseDate): array
  {
    $out = [];
    $first = (int) (substr((string) $baseDate, 0, 6) . '01');
    $id = self::SEED_ID_BASE;

    for ($day = 1; $day <= 28; $day++) {
      if ($day % 4 === 0) {
        continue; // leave gaps so "no events" rendering is exercised too
      }
      $date = $first + ($day - 1);
      $out = array_merge($out, self::event(
        $id++,
        $date,
        90000 + ($day % 8) * 10000,
        60,
        'Seed day ' . $day,
        'admin'
      ));
    }

    return $out;
  }

  /**
   * @return list<array{0: string, 1: list<mixed>}>
   */
  private static function conflicts(int $baseDate): array
  {
    $id = self::SEED_ID_BASE + 100;

    return array_merge(
      self::event($id++, $baseDate, 100000, 60, 'Conflict A 10:00-11:00', 'admin'),
      self::event($id++, $baseDate, 103000, 60, 'Conflict B 10:30-11:30', 'admin'),
      self::event($id++, $baseDate, 104500, 30, 'Conflict C 10:45-11:15', 'admin'),
      // Ends exactly when A starts, so an off-by-one in the overlap test shows.
      self::event($id++, $baseDate, 90000, 60, 'Adjacent 09:00-10:00', 'admin'),
      // Starts exactly when A ends.
      self::event($id, $baseDate, 110000, 60, 'Adjacent 11:00-12:00', 'admin')
    );
  }

  /**
   * @return list<array{0: string, 1: list<mixed>}>
   */
  private static function repeats(int $baseDate): array
  {
    $daily = self::SEED_ID_BASE + 200;
    $weekly = $daily + 1;

    $out = array_merge(
      self::event($daily, $baseDate, 140000, 60, 'Daily series', 'admin', 'M'),
      self::event($weekly, $baseDate, 160000, 60, 'Weekly series', 'admin', 'M')
    );

    $out[] = ['INSERT INTO webcal_entry_repeats ( cal_id, cal_type, cal_frequency, cal_end ) VALUES ( ?, ?, ?, ? )',
      [$daily, 'daily', 1, null]];
    $out[] = ['INSERT INTO webcal_entry_repeats ( cal_id, cal_type, cal_frequency, cal_end ) VALUES ( ?, ?, ?, ? )',
      [$weekly, 'weekly', 1, $baseDate + 28]];

    // Two deleted occurrences, the case that breaks naive expansion.
    foreach ([2, 5] as $offset) {
      $out[] = ['INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) VALUES ( ?, ?, ? )',
        [$daily, $baseDate + $offset, 1]];
    }

    return $out;
  }

  /**
   * @return list<array{0: string, 1: list<mixed>}>
   */
  private static function groups(int $baseDate): array
  {
    $groupId = self::SEED_ID_BASE;
    $alice = self::LOGIN_PREFIX . 'alice';
    $bob = self::LOGIN_PREFIX . 'bob';
    $out = [];

    foreach ([[$alice, 'Alice', 'Seed'], [$bob, 'Bob', 'Seed']] as [$login, $first, $last]) {
      $out[] = ['INSERT INTO webcal_user ( cal_login, cal_lastname, cal_firstname, cal_is_admin, cal_email, cal_enabled ) VALUES ( ?, ?, ?, ?, ?, ? )',
        [$login, $last, $first, 'N', $login . '@example.invalid', 'Y']];
    }

    $out[] = ['INSERT INTO webcal_group ( cal_group_id, cal_owner, cal_name, cal_last_update ) VALUES ( ?, ?, ?, ? )',
      [$groupId, $alice, 'Seed Group', $baseDate]];
    foreach ([$alice, $bob] as $login) {
      $out[] = ['INSERT INTO webcal_group_user ( cal_group_id, cal_login ) VALUES ( ?, ? )',
        [$groupId, $login]];
    }

    // Each sees the other as a layer, so layered rendering has something to do.
    $out[] = ['INSERT INTO webcal_user_layers ( cal_layerid, cal_login, cal_layeruser, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )',
      [$groupId, $alice, $bob, '#3366cc', 'Y']];
    $out[] = ['INSERT INTO webcal_user_layers ( cal_layerid, cal_login, cal_layeruser, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )',
      [$groupId + 1, $bob, $alice, '#cc3366', 'Y']];

    $id = self::SEED_ID_BASE + 300;
    $out = array_merge($out,
      self::event($id++, $baseDate, 90000, 60, 'Alice standup', $alice),
      self::event($id, $baseDate, 130000, 60, 'Bob review', $bob));

    return $out;
  }

  /**
   * One event plus its participant row. Duration is minutes,
   * matching webcal_entry.cal_duration.
   *
   * @return list<array{0: string, 1: list<mixed>}>
   */
  private static function event(
    int $id,
    int $date,
    int $time,
    int $durationMinutes,
    string $name,
    string $login,
    string $type = 'E'
  ): array {
    return [
      ['INSERT INTO webcal_entry ( cal_id, cal_create_by, cal_date, cal_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, cal_name, cal_description ) '
        . 'VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )',
        [$id, $login, $date, $time, $date, $time, $durationMinutes, 5, $type, 'P', $name, 'Created by bin/webcal.php seed.']],
      ['INSERT INTO webcal_entry_user ( cal_id, cal_login, cal_status, cal_percent ) VALUES ( ?, ?, ?, ? )',
        [$id, $login, 'A', 0]],
    ];
  }
}
