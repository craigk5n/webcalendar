<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class CalendarIdentity
{
  public function authenticate(string $username, string $password): ?array
  {
    $GLOBALS['error'] = '';
    if (!$this->isUser($username)) { return null; }
    $database = $GLOBALS['user_inc'] === 'user.php';
    $proof = null;
    $valid = $database ? user_valid_login($username, $password, false, $proof) : user_valid_login($username, $password);
    if (!$valid) { return null; }
    $profile = $this->find($username);
    if ($database && (!$profile || !$proof || !hash_equals($proof, $profile['subject_id']))) { return null; }
    return $profile;
  }

  public function find(string $username): ?array
  {
    unset($GLOBALS['cached_user_var']);
    $GLOBALS['api_enabled'] = 'Y';
    if (!$this->isUser($username) || !user_load_variables($username, 'api_') || $GLOBALS['api_enabled'] !== 'Y') {
      return null;
    }
    $subject = $GLOBALS['user_inc'] === 'user.php'
      ? DatabasePasswordAuthentication::subject((string) $GLOBALS['api_password'])
      : ($GLOBALS['api_subject_id'] ?? null);
    if ($subject === null) {
      throw new ServiceUnavailableHttpException(null, 'LDAP API authentication requires a readable entryUUID or objectGUID.');
    }
    return [
      'username' => $username,
      'name' => $GLOBALS['api_fullname'] ?? $username,
      'email' => $GLOBALS['api_email'] ?? '',
      'subject_id' => $subject,
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

  public function weekStart(string $username): int
  {
    $this->activate($username);
    return (int) $GLOBALS['WEEK_START'];
  }

  private function isUser(string $username): bool
  {
    return !in_array($username, ['__public__', '__default__'], true)
      && !str_starts_with($username, $GLOBALS['NONUSER_PREFIX']);
  }
}
