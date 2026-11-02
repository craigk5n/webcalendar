<?php

declare(strict_types=1);

namespace WebCalendar\Services;

final class CalendarIdentity
{
  public function authenticate(string $username, string $password): ?array
  {
    $GLOBALS['error'] = '';
    if (!$this->isUser($username) || !user_valid_login($username, $password)) {
      return null;
    }
    return $this->find($username);
  }

  public function find(string $username): ?array
  {
    unset($GLOBALS['cached_user_var']);
    $GLOBALS['api_enabled'] = 'Y';
    if (!$this->isUser($username) || !user_load_variables($username, 'api_') || $GLOBALS['api_enabled'] !== 'Y') {
      return null;
    }
    return [
      'username' => $username,
      'name' => $GLOBALS['api_fullname'] ?? $username,
      'email' => $GLOBALS['api_email'] ?? '',
    ];
  }

  public function activate(string $username): void
  {
    $GLOBALS['login'] = $GLOBALS['user'] = $username;
    user_load_variables($username, '');
    $GLOBALS['is_admin'] = ($GLOBALS['is_admin'] ?? 'N') === 'Y';
    load_global_settings();
    load_user_preferences();
    access_init();
  }

  private function isUser(string $username): bool
  {
    return !in_array($username, ['__public__', '__default__'], true)
      && !str_starts_with($username, $GLOBALS['NONUSER_PREFIX']);
  }
}
