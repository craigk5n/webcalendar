<?php

declare(strict_types=1);

namespace WebCalendar\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RenewalRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'refresh_token' => ['required', 'string', 'max:256', 'regex:/\A[1-9][0-9]*\|[a-f0-9]{64}\z/'],
      'rotation_secret' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
    ];
  }
}
