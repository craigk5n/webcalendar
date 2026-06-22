<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/xcal.php';

/**
 * Unit tests for webcal_validate_remote_url() — the anti-SSRF control that
 * guards remote calendar / hCalendar fetching (CRIT-5).
 *
 * Before this control, a user-supplied remote-calendar URL was fetched with
 * @fopen(), which honors URL wrappers (file://, php://, ...) and any host,
 * turning the feature into an arbitrary local-file-read / SSRF primitive.
 *
 * All cases here are deterministic and network-free: rejections are decided by
 * scheme/format or by IP-literal classification (no DNS lookup), and the single
 * positive case uses a public IP literal so it does not depend on DNS.
 */
final class RemoteUrlValidationTest extends TestCase
{
  /**
   * @dataProvider blockedUrls
   */
  public function testBlockedUrlsAreRejected(string $url): void
  {
    $err = '';
    self::assertFalse(
      webcal_validate_remote_url($url, $err),
      "URL must be rejected: $url"
    );
    self::assertNotSame('', $err, 'A rejection must populate an error message.');
  }

  public static function blockedUrls(): array
  {
    return [
      'file scheme (local file read)' => ['file:///etc/passwd'],
      'php wrapper'                    => ['php://filter/resource=/etc/passwd'],
      'ftp scheme'                     => ['ftp://example.com/cal.ics'],
      'gopher scheme'                  => ['gopher://example.com/'],
      'dict scheme'                    => ['dict://example.com:11211/'],
      'loopback IPv4'                  => ['http://127.0.0.1/cal.ics'],
      'loopback by name resolves'      => ['http://127.0.0.1:8080/x'],
      'cloud metadata link-local'      => ['http://169.254.169.254/latest/meta-data/'],
      'private 10/8'                   => ['http://10.0.0.5/cal.ics'],
      'private 192.168/16'             => ['https://192.168.1.1/cal.ics'],
      'private 172.16/12'              => ['http://172.16.0.9/cal.ics'],
      'no scheme'                      => ['example.com/cal.ics'],
      'garbage'                        => ['not a url'],
      'empty'                          => [''],
    ];
  }

  public function testPublicUrlIsAllowed(): void
  {
    // Public IP literal — passes without any DNS lookup.
    $err = '';
    self::assertTrue(
      webcal_validate_remote_url('https://8.8.8.8/calendar.ics', $err),
      "A public https URL must be allowed (err: $err)"
    );
  }

  public function testWebcalSchemeIsNormalizedAndAllowed(): void
  {
    // webcal:// is normalized to http:// internally; with a public host it
    // should validate. Use a public IP literal to stay network-free.
    $err = '';
    self::assertTrue(
      webcal_validate_remote_url('webcal://8.8.8.8/calendar.ics', $err),
      "webcal:// to a public host must be allowed (err: $err)"
    );
  }
}
