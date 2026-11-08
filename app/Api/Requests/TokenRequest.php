<?php

declare(strict_types=1);

namespace WebCalendar\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TokenRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'username' => ['required', 'string', 'max:100', 'regex:/\A[A-Za-z0-9_.@,=\-]+\z/'],
      'password' => ['required', 'string', 'max:4096'],
      'device_name' => ['required', 'string', 'max:100'],
      'abilities' => ['prohibited'],
      'renewable' => ['sometimes', 'boolean'],
    ];
  }
}
