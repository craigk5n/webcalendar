<?php

declare(strict_types=1);

namespace WebCalendar\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchRequest extends FormRequest
{
  private const MAX_QUERY_LENGTH = 200;
  private const MAX_PAGE_SIZE = 100;

  public function authorize(): bool { return true; }

  protected function prepareForValidation(): void
  {
    if (is_string($this->input('q'))) { $this->merge(['q' => trim($this->input('q'))]); }
  }

  public function rules(): array
  {
    return [
      'q' => ['required', 'string', 'max:' . self::MAX_QUERY_LENGTH],
      'cursor' => ['sometimes', 'integer', 'min:1', 'max:' . PHP_INT_MAX],
      'limit' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PAGE_SIZE],
      'user' => ['prohibited'], 'username' => ['prohibited'], 'calendar' => ['prohibited'],
    ];
  }
}
