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
use WebCalendar\Api\Exceptions\EventWriteException;
use WebCalendar\Api\Requests\EventWriteRequest;

final class CalendarEventWriter
{
  private const MAX_ID_ATTEMPTS = 5;
  private const MAX_EVENT_ID = 2147483647;
  private const DEFAULT_PRIORITY = 5;
  private const UNTIMED_TIME = -1;
  private const ALL_DAY_TIME = 0;

  public function __construct(private readonly CalendarEventAccess $access, private readonly CalendarEventConflicts $conflicts) {}

  public function create(string $username, array $input): array
  {
    $timezone = $this->activate($username);
    $fields = EventWriteRequest::validateSchedule($input, $timezone);
    $this->conflicts->check($username, $fields, $timezone);
    for ($attempt = 0; $attempt < self::MAX_ID_ATTEMPTS; $attempt++) {
      try {
        $saved = DB::transaction(function () use ($username, $fields, $timezone): array {
          $id = $this->nextId('webcal_entry', 'cal_id');
          $row = array_merge($this->columns($fields), [
            'cal_id' => $id, 'cal_create_by' => $username, 'cal_type' => 'E',
            'cal_access' => $fields['access'] ?? $GLOBALS['DEFAULT_VISIBILITY'], 'cal_priority' => self::DEFAULT_PRIORITY,
            'cal_name' => $fields['title'], 'cal_description' => EventWriteRequest::description($fields['description'] ?? ''),
            'cal_location' => $fields['location'] ?? null,
          ]);
          DB::table('webcal_entry')->insert($row);
          DB::table('webcal_entry_user')->insert(['cal_id' => $id, 'cal_login' => $username, 'cal_status' => 'A', 'cal_percent' => 0]);
          $this->log($id, $username, LOG_CREATE);
          return $this->representation($row, $timezone);
        });
        return $saved;
      } catch (QueryException $exception) {
        // Retry the entire transaction: PostgreSQL cannot continue after a key collision.
        if (!$this->isCollision($exception) || $attempt === self::MAX_ID_ATTEMPTS - 1) {
          throw $exception;
        }
      }
    }
    throw new \LogicException('Event ID allocation did not complete.');
  }

  public function update(string $username, int $id, array $input): array
  {
    $timezone = $this->activate($username);
    if ($id < 1 || $id > self::MAX_EVENT_ID) { throw new NotFoundHttpException(); }
    for ($attempt = 0; $attempt < self::MAX_ID_ATTEMPTS; $attempt++) {
      try {
        $saved = DB::transaction(function () use ($username, $id, $input, $timezone): array {
          $found = DB::table('webcal_entry as e')->join('webcal_entry_user as u', 'u.cal_id', '=', 'e.cal_id')
            ->where('e.cal_id', $id)->where('u.cal_login', $username)->whereIn('u.cal_status', ['A', 'W'])
            ->select('e.*')->lockForUpdate()->first();
          if (!$found) { throw new NotFoundHttpException(); }
          $row = (array) $found;
          if ($row['cal_create_by'] !== $username) { throw new AuthorizationException(); }
          if (!isset($this->access->editableIds($username, [$id])[$id])) {
            throw new EventWriteException(Response::HTTP_UNPROCESSABLE_ENTITY, 'unsupported_event',
              'Only your own accepted, unshared, non-recurring events can be edited.');
          }
          $changes = [];
          if (array_intersect(array_keys($input), EventWriteRequest::SCHEDULE_FIELDS)) {
            $fields = array_replace($this->representation($row, $timezone), $input);
            $fields = EventWriteRequest::validateSchedule($fields, $timezone, isset($input['date']) && !array_key_exists('starts_at', $input));
            $this->conflicts->check($username, $fields, $timezone, $id);
            $changes = $this->columns($fields);
          }
          foreach (['title' => 'cal_name', 'location' => 'cal_location', 'access' => 'cal_access'] as $field => $column) {
            if (array_key_exists($field, $input)) { $changes[$column] = $input[$field]; }
          }
          if (array_key_exists('description', $input)) { $changes['cal_description'] = EventWriteRequest::description($input['description'] ?? ''); }
          $changes = array_merge($changes, $this->modified());
          DB::table('webcal_entry')->where('cal_id', $id)->update($changes);
          $this->log($id, $username, LOG_UPDATE);
          return $this->representation(array_merge($row, $changes), $timezone);
        });
        return $saved;
      } catch (QueryException $exception) {
        if (!$this->isCollision($exception) || $attempt === self::MAX_ID_ATTEMPTS - 1) { throw $exception; }
      }
    }
    throw new \LogicException('Activity ID allocation did not complete.');
  }

  private function activate(string $username): DateTimeZone
  {
    (new CalendarIdentity())->activate($username);
    if (!$this->access->canWrite()) { throw new AuthorizationException(); }
    $this->assertTransactionalStorage();
    return new DateTimeZone($GLOBALS['TIMEZONE']);
  }

  private function assertTransactionalStorage(): void
  {
    if (DB::connection()->getDriverName() !== 'mysql') { return; }
    $tables = DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
      ->whereIn('TABLE_NAME', ['webcal_entry', 'webcal_entry_user', 'webcal_entry_log'])->pluck('ENGINE', 'TABLE_NAME');
    if ($tables->count() !== 3 || $tables->contains(fn ($engine) => strcasecmp((string) $engine, 'InnoDB') !== 0)) {
      throw new EventWriteException(Response::HTTP_SERVICE_UNAVAILABLE, 'transactional_storage_required',
        'API event writes require InnoDB for webcal_entry, webcal_entry_user and webcal_entry_log.');
    }
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
    return array_merge(CalendarEventData::fromRow($row, $timezone), ['can_edit' => true]);
  }

  private function nextId(string $table, string $column): int
  {
    $id = (int) DB::table($table)->max($column) + 1;
    if ($id > self::MAX_EVENT_ID) { throw new \RuntimeException('The calendar ID range is exhausted.'); }
    return $id;
  }

  private function log(int $id, string $username, string $type): void
  {
    DB::table('webcal_entry_log')->insert(['cal_log_id' => $this->nextId('webcal_entry_log', 'cal_log_id'),
      'cal_entry_id' => $id, 'cal_login' => $username, 'cal_user_cal' => $username,
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
