<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Api\Exceptions\EventWriteException;
use WebCalendar\Services\CalendarEventWriter;
use WebCalendar\Services\CalendarIdentity;

if (PHP_SAPI !== 'cli' || getenv('WEBCALENDAR_DB_TYPE') !== 'postgresql'
    || getenv('WEBCALENDAR_DB_DATABASE') !== 'api_smoke_db') {
  throw new RuntimeException('This fixture requires the disposable PostgreSQL api_smoke_db installation.');
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';
define('WEBCALENDAR_API_BOOTSTRAP', true);
require dirname(__DIR__, 2) . '/includes/api-bootstrap.php';

$scenario = (object) ['enabled' => false, 'exhaust' => false, 'conflict' => false, 'attempts' => 0, 'failures' => [], 'sentinel' => 0];
$other = new PDO('pgsql:host=' . getenv('WEBCALENDAR_DB_HOST') . ';dbname=api_smoke_db',
  getenv('WEBCALENDAR_DB_LOGIN'), getenv('WEBCALENDAR_DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
Connection::resolverFor('pgsql', static function ($pdo, $database, $prefix, $config) use ($scenario, $other) {
  return new class($pdo, $database, $prefix, $config, $scenario, $other) extends PostgresConnection {
    public function __construct($pdo, $database, $prefix, $config, private readonly object $scenario, private readonly PDO $other)
    {
      parent::__construct($pdo, $database, $prefix, $config);
    }

    public function beginTransaction()
    {
      parent::beginTransaction();
      if (!$this->scenario->enabled) { return; }
      $this->scenario->attempts++;
      if ($this->scenario->exhaust || $this->scenario->attempts === 1) {
        $this->other->beginTransaction();
        $this->other->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        // Establish a predicate read before the API inserts its new event.
        $this->other->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn();
      }
    }

    public function commit()
    {
      if ($this->scenario->enabled && $this->other->inTransaction()) {
        // The API has read this appointment while the other connection read its insertion range.
        $this->other->prepare('UPDATE webcal_entry SET cal_location = ?, cal_time = ? WHERE cal_id = ?')
          ->execute(['Concurrent change ' . $this->scenario->attempts, $this->scenario->conflict ? 160000 : 140000, $this->scenario->sentinel]);
        $this->other->commit();
      }
      try { parent::commit(); }
      catch (PDOException $exception) {
        $this->scenario->failures[] = [$exception->errorInfo[0] ?? null, $this->getPdo()->inTransaction(), $this->transactionLevel()];
        throw $exception;
      }
    }
  };
});

$app = require dirname(__DIR__, 2) . '/app/Api/bootstrap.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
check(DB::table('webcal_api_users')->where('username', 'api-smoke')->exists(), 'Run a token login for the disposable api-smoke account first.');
foreach (['TIMEZONE' => 'America/New_York', 'ALLOW_CONFLICTS' => 'N', 'LIMIT_APPTS' => 'N'] as $setting => $value) {
  DB::table('webcal_config')->updateOrInsert(['cal_setting' => $setting], ['cal_value' => $value]);
}
(new CalendarIdentity())->activate('api-smoke');
DB::statement('SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL SERIALIZABLE');
$writer = $app->make(CalendarEventWriter::class);

function check(bool $condition, string $message): void
{
  if (!$condition) { throw new RuntimeException($message); }
}

try {
  foreach (['recover' => '2031-01-20', 'exhaust' => '2031-01-21', 'conflict' => '2031-01-22'] as $mode => $date) {
    $exhaust = $mode === 'exhaust';
    $conflict = $mode === 'conflict';
    $scenario->enabled = false;
    $sentinel = $writer->create('api-smoke', ['title' => 'Serialization sentinel', 'date' => $date,
      'all_day' => false, 'untimed' => false, 'starts_at' => $date . 'T09:00:00-05:00', 'duration_minutes' => 60]);
    $scenario->sentinel = $sentinel['id'];
    $scenario->exhaust = $exhaust;
    $scenario->conflict = $conflict;
    $scenario->attempts = 0;
    $scenario->failures = [];
    $entries = DB::table('webcal_entry')->count();
    $logs = DB::table('webcal_entry_log')->count();
    $scenario->enabled = true;
    $saved = null;
    $rejection = null;
    try {
      $saved = $writer->create('api-smoke', ['title' => 'Commit-abort fixture', 'date' => $date,
        'all_day' => false, 'untimed' => false, 'starts_at' => $date . 'T11:00:00-05:00', 'duration_minutes' => 60]);
    } catch (EventWriteException $exception) { $rejection = $exception; }
    $scenario->enabled = false;
    check($scenario->attempts === ($exhaust ? 5 : 2), 'Unexpected whole-transaction attempt count.');
    check(count($scenario->failures) === ($exhaust ? 5 : 1), 'Expected actual PostgreSQL commit aborts.');
    foreach ($scenario->failures as $failure) {
      check($failure === ['40001', false, 1], 'Expected a commit-time 40001 with no active PDO transaction: ' . json_encode($failure));
    }
    check(DB::connection()->transactionLevel() === 0 && !DB::connection()->getPdo()->inTransaction(), 'Transaction cleanup was incomplete.');
    $savedCount = $exhaust || $conflict ? 0 : 1;
    check(DB::table('webcal_entry')->count() === $entries + $savedCount, 'Aborted or duplicate entry persisted.');
    check(DB::table('webcal_entry_log')->count() === $logs + $savedCount, 'Aborted or duplicate activity log persisted.');
    if ($exhaust) {
      check($saved === null && $rejection?->getStatusCode() === Response::HTTP_SERVICE_UNAVAILABLE, 'Expected bounded calendar_busy rejection.');
      check($rejection->errorCode === 'calendar_busy', 'Wrong rejection code.');
    } elseif ($conflict) {
      check($saved === null && $rejection?->getStatusCode() === Response::HTTP_CONFLICT && $rejection->errorCode === 'schedule_conflict',
        'The retry did not revalidate the committed schedule.');
    } else { check($saved !== null && $rejection === null, 'A confirmed commit abort was not retried successfully.'); }
  }
  echo "PostgreSQL confirmed COMMIT abort: fresh retry, schedule revalidation, cleanup, exhaustion and atomic entry/log checks passed.\n";
} finally {
  if ($other->inTransaction()) { $other->rollBack(); }
}
