<?php

declare(strict_types=1);

namespace WebCalendar\Services;

function user_valid_login($username, $password, $silent = false, &$proof = null): bool
{
  $valid = \user_valid_login($username, $password, $silent, $proof);
  if ($valid && str_starts_with($GLOBALS['password_barrier_mode'], 'verified-issue')) { \barrier(); }
  return $valid;
}

function password_hash($password, $algorithm, array $options = []): string
{
  $hash = \password_hash($password, $algorithm, $options);
  if ($GLOBALS['password_barrier_mode'] === 'rehash-before') { \barrier(); }
  return $hash;
}

function dbi_execute($sql, $parameters = [], $fatal = true, $show = true)
{
  $result = \dbi_execute($sql, $parameters, $fatal, $show);
  if ($GLOBALS['password_barrier_mode'] === 'rehash-after' && str_starts_with($sql, 'UPDATE webcal_user')) { \barrier(); }
  return $result;
}
