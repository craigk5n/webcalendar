<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use WebCalendar\Api\Exceptions\EventWriteException;
use WebCalendar\Api\Requests\EventWriteRequest;

final class CalendarEventWriter
{
  private const MAX_WRITE_ATTEMPTS = 5;
  private const RETRY_DELAY_MICROSECONDS = 20000;
  private const MAX_EVENT_ID = 2147483647;
  private const DEFAULT_PRIORITY = 5;
  private const UNTIMED_TIME = -1;
  private const ALL_DAY_TIME = 0;
  private const SQLITE_PRIMARY_CODE_MASK = 0xff;
  private const SQLITE_CONTENTION_CODES = [5, 6];
  private const MYSQL_CONTENTION_CODES = [1205, 1213];
  private const POSTGRES_ABORT_STATES = ['40001', '40P01'];
  private const POSTGRES_CONTENTION_STATES = [...self::POSTGRES_ABORT_STATES, '55P03'];

  public function __construct(private readonly CalendarEventAccess $access, private readonly CalendarEventConflicts $conflicts) {}

  public function create(string $username, array $input, ?string $calendar = null): array
  {
    $calendar ??= $username;
    $this->activate($username, $calendar);
    return $this->transaction($username, function () use ($username, $calendar, $input): array {
      $selected = $this->activate($username, $calendar);
      $timezone = new DateTimeZone($selected['timezone']);
      $fields = EventWriteRequest::validateSchedule($input, $timezone);
      $id = $this->nextId('webcal_entry', 'cal_id');
      $row = array_merge($this->columns($fields), [
        'cal_id' => $id, 'cal_create_by' => $calendar, 'cal_type' => 'E',
        'cal_access' => $fields['access'] ?? $GLOBALS['DEFAULT_VISIBILITY'], 'cal_priority' => self::DEFAULT_PRIORITY,
        'cal_name' => $fields['title'], 'cal_description' => EventWriteRequest::description($fields['description'] ?? ''),
        'cal_location' => $fields['location'] ?? null,
      ]);
      if (!$this->access->canEditEvent($calendar, $row)) { throw new AuthorizationException(); }
      $this->conflicts->check($calendar, $fields, $timezone, 0, $username);
      DB::table('webcal_entry')->insert($row);
      DB::table('webcal_entry_user')->insert(['cal_id' => $id, 'cal_login' => $calendar, 'cal_status' => 'A', 'cal_percent' => 0]);
      $this->log($id, $username, LOG_CREATE, $calendar);
      return $this->representation($row, $timezone) + ['calendar_id' => $calendar, 'calendar_name' => $selected['name']];
    });
  }

  public function update(string $username, int $id, array $input, ?string $calendar = null): array
  {
    $calendar ??= $username;
    $this->activate($username, $calendar);
    if ($id < 1 || $id > self::MAX_EVENT_ID) { throw new NotFoundHttpException(); }
    return $this->transaction($username, function () use ($username, $calendar, $id, $input): array {
      $selected = $this->activate($username, $calendar);
      $timezone = new DateTimeZone($selected['timezone']);
      $found = DB::table('webcal_entry as e')->join('webcal_entry_user as u', 'u.cal_id', '=', 'e.cal_id')
        ->where('e.cal_id', $id)->where('u.cal_login', $calendar)->whereIn('u.cal_status', ['A', 'W'])
        ->select('e.*')->lockForUpdate()->first();
      if (!$found) { throw new NotFoundHttpException(); }
      $row = (array) $found;
      if (!$this->access->canEditEvent($calendar, $row)) { throw new AuthorizationException(); }
      if (isset($input['access']) && !$this->access->canEditEvent($calendar, array_replace($row, ['cal_access' => $input['access']]))) {
        throw new AuthorizationException();
      }
      if ($calendar === $username && $row['cal_create_by'] !== $username) { throw new AuthorizationException(); }
      if (!isset($this->access->editableIds($calendar, [$id], $calendar !== $username)[$id])) {
        throw new EventWriteException(Response::HTTP_UNPROCESSABLE_ENTITY, 'unsupported_event',
          'Only your own accepted, unshared, non-recurring events can be edited.');
      }
      $changes = [];
      if (array_intersect(array_keys($input), EventWriteRequest::SCHEDULE_FIELDS)) {
        $fields = array_replace($this->representation($row, $timezone), $input);
        $fields = EventWriteRequest::validateSchedule($fields, $timezone, isset($input['date']) && !array_key_exists('starts_at', $input));
        $this->conflicts->check($calendar, $fields, $timezone, $id, $username);
        $changes = $this->columns($fields);
      }
      foreach (['title' => 'cal_name', 'location' => 'cal_location', 'access' => 'cal_access'] as $field => $column) {
        if (array_key_exists($field, $input)) { $changes[$column] = $input[$field]; }
      }
      if (array_key_exists('description', $input)) { $changes['cal_description'] = EventWriteRequest::description($input['description'] ?? ''); }
      $changes = array_merge($changes, $this->modified());
      $this->rearmReminder($id, $row, array_merge($row, $changes));
      DB::table('webcal_entry')->where('cal_id', $id)->update($changes);
      $this->log($id, $username, LOG_UPDATE, $calendar);
      return $this->representation(array_merge($row, $changes), $timezone) + ['calendar_id' => $calendar, 'calendar_name' => $selected['name']];
    });
  }

  private function transaction(string $username, \Closure $write): array
  {
    $connection = DB::connection();
    if ($connection->transactionLevel() !== 0) { throw new \LogicException('Event writes require an outermost transaction.'); }
    for ($attempt = 0; $attempt < self::MAX_WRITE_ATTEMPTS; $attempt++) {
      $connection->beginTransaction();
      $pdo = $connection->getPdo();
      $committing = false;
      try {
        if ($connection->getDriverName() === 'sqlite') {
          // Acquire write intent before any snapshot reads, including on PHP 8.2/8.3.
          DB::table('webcal_api_users')->where('username', $username)->update(['username' => $username]);
        }
        // Shared across actors and nonuser calendars; acquire before account/event locks.
        if (!DB::table('webcal_config')->where('cal_setting', 'WEBCAL_PROGRAM_VERSION')->lockForUpdate()->first()) {
          throw new \RuntimeException('The installation version setting is required for event writes.');
        }
        if (!DB::table('webcal_api_users')->where('username', $username)->lockForUpdate()->first()) {
          throw new AuthorizationException();
        }
        $saved = $write();
        $committing = true;
        $connection->commit();
        return $saved;
      } catch (Throwable $exception) {
        $confirmedAbort = $connection->getDriverName() === 'pgsql' && $exception instanceof \PDOException
          && in_array($exception->errorInfo[0] ?? null, self::POSTGRES_ABORT_STATES, true);
        $canRetryCommit = !$committing || $pdo->inTransaction() || $confirmedAbort;
        $connection->rollBack();
        if (!$canRetryCommit || $pdo->inTransaction() || $connection->transactionLevel() !== 0 || $connection->getPdo() !== $pdo) { throw $exception; }
        $collision = $exception instanceof QueryException && $this->isCollision($exception);
        $transient = $this->isTransient($exception, $connection->getDriverName());
        if (!$collision && !$transient) { throw $exception; }
        if ($attempt === self::MAX_WRITE_ATTEMPTS - 1) {
          throw new EventWriteException(Response::HTTP_SERVICE_UNAVAILABLE, 'calendar_busy',
            'The calendar is busy. Please try again shortly.');
        }
        usleep(self::RETRY_DELAY_MICROSECONDS * (2 ** $attempt));
      }
    }
    throw new \LogicException('Event transaction did not complete.');
  }

  private function isTransient(Throwable $exception, string $driver): bool
  {
    $info = $exception instanceof \PDOException ? ($exception->errorInfo ?? []) : [];
    $state = $info[0] ?? null;
    $code = (int) ($info[1] ?? 0);
    return match ($driver) {
      'sqlite' => in_array($code & self::SQLITE_PRIMARY_CODE_MASK, self::SQLITE_CONTENTION_CODES, true),
      'mysql' => in_array($code, self::MYSQL_CONTENTION_CODES, true),
      'pgsql' => in_array($state, self::POSTGRES_CONTENTION_STATES, true),
      default => false,
    };
  }

  private function activate(string $username, string $calendar): array
  {
    $selected = (new CalendarSelection())->activate($username, $calendar);
    if (!$this->access->canWrite()) { throw new AuthorizationException(); }
    $this->assertTransactionalStorage();
    return $selected;
  }

  private function assertTransactionalStorage(array $required = ['webcal_config', 'webcal_api_users', 'webcal_entry', 'webcal_entry_user', 'webcal_entry_log']): void
  {
    if (DB::connection()->getDriverName() !== 'mysql') { return; }
    $tables = DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
      ->whereIn('TABLE_NAME', $required)->pluck('ENGINE', 'TABLE_NAME');
    if ($tables->count() !== count($required) || $tables->contains(fn ($engine) => strcasecmp((string) $engine, 'InnoDB') !== 0)) {
      throw new EventWriteException(Response::HTTP_SERVICE_UNAVAILABLE, 'transactional_storage_required',
        'API event writes require InnoDB for ' . implode(', ', $required) . '.');
    }
  }

  private function rearmReminder(int $id, array $before, array $after): void
  {
    $reminder = DB::table('webcal_reminders')->where('cal_id', $id)->first();
    if (!$reminder || (int) $reminder->cal_date !== 0) { return; }
    $endRelative = $reminder->cal_related !== 'S';
    if ($this->reminderAnchor($before, $endRelative) === $this->reminderAnchor($after, $endRelative)) { return; }
    $this->assertTransactionalStorage(['webcal_reminders']);
    DB::table('webcal_reminders')->where('cal_id', $id)->update(['cal_times_sent' => 0, 'cal_last_sent' => 0]);
  }

  private function reminderAnchor(array $row, bool $endRelative): int
  {
    $date = sprintf('%08d', $row['cal_date']);
    $year = (int) substr($date, 0, 4);
    $month = (int) substr($date, 4, 2);
    $day = (int) substr($date, 6, 2);
    $time = max(0, (int) $row['cal_time']);
    $hour = intdiv($time, 10000);
    $minute = intdiv($time, 100) % 100;
    $duration = max(0, (int) $row['cal_duration']);
    // Event::getEndDateTimeTS uses UTC even for floating events.
    if ($endRelative) { return gmmktime($hour, $minute + $duration, 0, $month, $day, $year); }
    $floating = (int) $row['cal_time'] < 0 || ($time === self::ALL_DAY_TIME && $duration === EventWriteRequest::MINUTES_PER_DAY);
    if (!$floating) { return gmmktime($hour, $minute, 0, $month, $day, $year); }
    $timezone = date_default_timezone_get();
    try {
      date_default_timezone_set($GLOBALS['api_reminder_timezone']);
      return mktime(0, 0, 0, $month, $day, $year);
    } finally { date_default_timezone_set($timezone); }
  }

  private function columns(array $fields): array
  {
    if ($fields['all_day'] || $fields['untimed']) {
      $date = (int) str_replace('-', '', $fields['date']);
      $time = $fields['untimed'] ? self::UNTIMED_TIME : self::ALL_DAY_TIME;
    } else {
      $utc = (new DateTimeImmutable($fields['starts_at']))->setTimezone(new DateTimeZone('UTC'));
      $date = (int) $utc->format('Ymd');
      $time = (int) $utc->format('His');
    }
    return array_merge(['cal_date' => $date, 'cal_time' => $time, 'cal_duration' => (int) $fields['duration_minutes']], $this->modified());
  }

  private function modified(): array
  {
    return ['cal_mod_date' => (int) gmdate('Ymd'), 'cal_mod_time' => (int) gmdate('His')];
  }

  private function representation(array $row, DateTimeZone $timezone): array
  {
    return array_merge(CalendarEventData::fromRow($row, $timezone), [
      'can_edit' => $this->access->canWrite() && $this->access->canEditEvent($GLOBALS['user'], $row),
    ]);
  }

  private function nextId(string $table, string $column): int
  {
    $id = (int) DB::table($table)->max($column) + 1;
    if ($id > self::MAX_EVENT_ID) { throw new \RuntimeException('The calendar ID range is exhausted.'); }
    return $id;
  }

  private function log(int $id, string $username, string $type, string $calendar): void
  {
    DB::table('webcal_entry_log')->insert(['cal_log_id' => $this->nextId('webcal_entry_log', 'cal_log_id'),
      'cal_entry_id' => $id, 'cal_login' => $username, 'cal_user_cal' => $calendar,
      'cal_type' => $type, 'cal_date' => (int) gmdate('Ymd'), 'cal_time' => (int) gmdate('His'), 'cal_text' => 'Mobile API']);
  }

  private function isCollision(QueryException $exception): bool
  {
    $code = $exception->errorInfo[0] ?? null;
    return in_array($code, ['23000', '23505'], true)
      && (str_contains($exception->getSql(), 'insert into "webcal_entry"')
        || str_contains($exception->getSql(), 'insert into `webcal_entry`')
        || str_contains($exception->getSql(), 'insert into "webcal_entry_log"')
        || str_contains($exception->getSql(), 'insert into `webcal_entry_log`'))
      && (str_contains($exception->getMessage(), 'UNIQUE constraint failed')
        || str_contains($exception->getMessage(), 'Duplicate entry') || $code === '23505');
  }
}
