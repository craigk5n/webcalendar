<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/xcal.php";

/**
 * Regression test for the remote-calendar SSRF guard.
 *
 * webcal_validate_remote_url() rejected internal IPv4 ranges via
 * FILTER_FLAG_NO_PRIV_RANGE|NO_RES_RANGE, but an IPv4-mapped IPv6 literal such
 * as ::ffff:127.0.0.1 passed as a "public" IPv6 address, so an attacker could
 * still reach loopback / link-local / cloud-metadata endpoints. The guard now
 * normalizes mapped/compatible/NAT64 IPv6 to the embedded IPv4 first.
 *
 * Covers security-audit run-1:
 *   webcal_validate_remote_url:ipv4-mapped-ipv6-bypass (medium)
 */
final class RemoteUrlSsrfTest extends TestCase
{
  /** @dataProvider blockedUrls */
  public function test_internal_targets_are_blocked(string $url): void
  {
    $err = '';
    self::assertFalse(
      webcal_validate_remote_url($url, $err),
      "SSRF target not blocked: $url"
    );
  }

  public static function blockedUrls(): array
  {
    return [
      'ipv4-loopback'           => ['http://127.0.0.1/cal.ics'],
      'ipv6-loopback'           => ['http://[::1]/cal.ics'],
      'mapped-loopback'         => ['http://[::ffff:127.0.0.1]/cal.ics'],
      'mapped-link-local-meta'  => ['http://[::ffff:169.254.169.254]/latest/'],
      'mapped-private'          => ['http://[::ffff:10.0.0.5]/cal.ics'],
      'compat-private'          => ['http://[::172.16.0.1]/cal.ics'],
      'non-http-scheme'         => ['file:///etc/passwd'],
    ];
  }

  public function test_public_host_is_allowed(): void
  {
    $err = '';
    self::assertTrue(
      webcal_validate_remote_url('http://8.8.8.8/cal.ics', $err),
      "public IP should be allowed: $err"
    );
  }
}
