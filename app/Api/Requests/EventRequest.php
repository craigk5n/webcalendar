<?php

declare(strict_types=1);

namespace WebCalendar\Api\Requests;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class EventRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'start_date' => ['required', 'date_format:Y-m-d'],
      'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
      'user' => ['prohibited'],
      'username' => ['prohibited'],
      'calendar' => ['prohibited'],
    ];
  }

  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator): void {
      if ($validator->errors()->isNotEmpty()) {
        return;
      }
      $days = (new DateTimeImmutable($this->input('start_date')))->diff(new DateTimeImmutable($this->input('end_date')))->days + 1;
      if ($days > config('webcalendar.max_event_range_days')) {
        $validator->errors()->add('end_date', 'The date range is too large.');
      }
    });
  }
}
