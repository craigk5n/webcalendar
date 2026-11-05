<?php

declare(strict_types=1);

namespace WebCalendar\Api\Requests;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class EventWriteRequest extends FormRequest
{
  public const MINUTES_PER_DAY = 1440;
  public const SCHEDULE_FIELDS = ['date', 'starts_at', 'duration_minutes', 'all_day', 'untimed'];
  private const TITLE_LENGTH = 80;
  private const LOCATION_LENGTH = 100;
  private const DESCRIPTION_LENGTH = 65535;
  private const DATE_PATTERN = '/^(?!0000)\d{4}-\d{2}-\d{2}$/D';

  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $presence = $this->isMethod('POST') ? 'required' : 'sometimes';
    return [
      'title' => [$presence, 'required', 'string', 'max:' . self::TITLE_LENGTH, 'not_regex:/<\s*[a-z!\/?]|\x00/iu'],
      'description' => ['sometimes', 'nullable', 'string', 'max:' . self::DESCRIPTION_LENGTH, 'not_regex:/\x00/'],
      'location' => ['sometimes', 'nullable', 'string', 'max:' . self::LOCATION_LENGTH, 'not_regex:/<\s*[a-z!\/?]|\x00/iu'],
      'date' => [$presence, 'date_format:Y-m-d', 'regex:' . self::DATE_PATTERN],
      'starts_at' => ['sometimes', 'nullable', 'string', 'max:25'],
      'duration_minutes' => ['sometimes', 'integer', 'min:0', 'max:' . config('webcalendar.max_event_duration_minutes')],
      'all_day' => [$presence, 'boolean'],
      'untimed' => [$presence, 'boolean'],
      'access' => ['sometimes', Rule::in(['P', 'R', 'C'])],
    ];
  }

  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator): void {
      foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $key) {
        $validator->errors()->add($key, 'This field is not supported.');
      }
      if ($this->isMethod('PATCH') && $this->all() === []) {
        $validator->errors()->add('event', 'Provide at least one field to update.');
      }
    });
  }

  /** Validate the complete schedule after merging a patch with the locked record. */
  public static function validateSchedule(array $fields, DateTimeZone $timezone, bool $requiresStart = false): array
  {
    $fields['all_day'] = (bool) $fields['all_day'];
    $fields['untimed'] = (bool) $fields['untimed'];
    $nonTimed = $fields['all_day'] || $fields['untimed'];
    $canonicalDuration = $fields['all_day'] ? self::MINUTES_PER_DAY : 0;
    $fields['duration_minutes'] = (int) ($fields['duration_minutes'] ?? ($nonTimed ? $canonicalDuration : 0));
    $fields['starts_at'] ??= null;
    $validator = ValidatorFactory::make($fields, [
      'date' => ['required', 'date_format:Y-m-d'],
      'duration_minutes' => ['integer', 'min:0', 'max:' . config('webcalendar.max_event_duration_minutes')],
    ]);
    $validator->after(function (Validator $validator) use ($fields, $nonTimed, $canonicalDuration, $timezone, $requiresStart): void {
      if ($fields['all_day'] && $fields['untimed']) {
        $validator->errors()->add('all_day', 'An event cannot be both all-day and untimed.');
      }
      if ($nonTimed) {
        if ($fields['starts_at'] !== null) {
          $validator->errors()->add('starts_at', 'All-day and untimed events cannot have a start timestamp.');
        }
        if ($fields['duration_minutes'] !== $canonicalDuration) {
          $validator->errors()->add('duration_minutes', 'Use 1440 minutes for all-day events and zero for untimed events.');
        }
        return;
      }
      $stamp = $fields['starts_at'];
      if ($requiresStart || !is_string($stamp)
          || !preg_match('/^(?!0000)\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:00(?:Z|[+-](?:(?:0\d|1[0-3]):[0-5]\d|14:00))$/D', $stamp)) {
        $validator->errors()->add('starts_at', 'Supply a minute-precision ISO 8601 timestamp with an explicit offset when setting or moving a timed event.');
        return;
      }
      $start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP', $stamp);
      $errors = DateTimeImmutable::getLastErrors();
      if (!$start || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))
          || $start->format('Y-m-d\TH:i:s') !== substr($stamp, 0, 19)) {
        $validator->errors()->add('starts_at', 'The timestamp is not a valid date and time.');
        return;
      }
      if ($start->setTimezone($timezone)->format('Y-m-d') !== $fields['date']) {
        $validator->errors()->add('date', 'The date must match the start timestamp in the account timezone.');
      }
      if (!preg_match(self::DATE_PATTERN, $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d'))) {
        $validator->errors()->add('starts_at', 'The UTC date must remain within years 0001 through 9999.');
      }
      if ($fields['duration_minutes'] <= 0) {
        $validator->errors()->add('duration_minutes', 'Timed events require a positive duration.');
      }
      if (($GLOBALS['DISABLE_CROSSDAY_EVENTS'] ?? 'N') === 'Y' && $fields['duration_minutes'] > 0
          && $start->modify('+' . $fields['duration_minutes'] . ' minutes')->modify('-1 second')->setTimezone($timezone)->format('Y-m-d') !== $fields['date']) {
        $validator->errors()->add('duration_minutes', 'This installation does not allow timed events to cross calendar days.');
      }
      if ($start->setTimezone(new DateTimeZone('UTC'))->format('His') === '000000'
          && $fields['duration_minutes'] === self::MINUTES_PER_DAY) {
        $validator->errors()->add('duration_minutes', 'A 24-hour event starting at UTC midnight is represented as all-day by WebCalendar.');
      }
    });
    if ($validator->fails()) {
      throw new ValidationException($validator);
    }
    return $fields;
  }

  public static function description(string $description): string
  {
    $stored = ($GLOBALS['ALLOW_HTML_DESCRIPTION'] ?? 'N') === 'Y' ? sanitize_html($description) : $description;
    if (strlen($stored) > self::DESCRIPTION_LENGTH) {
      throw ValidationException::withMessages(['description' => 'The stored description exceeds the database byte limit.']);
    }
    return $stored;
  }
}
