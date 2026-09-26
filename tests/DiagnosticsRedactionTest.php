<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Diagnostics\ConfigPolicy;
use WebCalendar\Diagnostics\Report;

require_once __DIR__ . '/../includes/classes/Diagnostics/ConfigPolicy.php';
require_once __DIR__ . '/../includes/classes/Diagnostics/Report.php';

/**
 * The diagnostic report is meant to be pasted into a public issue, so what it
 * must never contain matters more than what it does.
 */
final class DiagnosticsRedactionTest extends TestCase
{
  /** Values a real installation holds that must not appear anywhere. */
  private const SECRETS = [
    'SMTP_PASSWORD' => 'hunter2-smtp',
    'REMINDER_WEB_TRIGGER_TOKEN' => 'a3f1c9d87e6b5a4938271605f4e3d2c1',
  ];

  /**
   * @return array<string, string>
   */
  private function realisticSettings(): array
  {
    return self::SECRETS + [
      'WEBCAL_PROGRAM_VERSION' => 'v1.9.23',
      'SEND_EMAIL' => 'Y',
      'SMTP_HOST' => 'mail.internal.example.org',
      'SMTP_USERNAME' => 'calendar@example.org',
      'EMAIL_FALLBACK_FROM' => 'admin@example.org',
      'CUSTOM_SCRIPT' => '<script>alert(1)</script>',
      'SELF_REGISTRATION_BLACKLIST' => 'spam.example',
      'UAC_ENABLED' => 'Y',
      'BGCOLOR' => '#ffffff',
    ];
  }

  public function testNoSecretValueSurvivesIntoTheReport(): void
  {
    $applied = ConfigPolicy::apply($this->realisticSettings());
    $report = new Report(['configuration' => $applied['shown']], '2026-09-24T00:00:00Z');

    foreach ([$report->toJson(), $report->toText()] as $rendered) {
      foreach (self::SECRETS as $key => $secret) {
        $this->assertStringNotContainsString($secret, $rendered,
          "$key leaked into a rendered report");
      }
    }
  }

  /**
   * Hostnames, addresses and admin-authored free text identify someone's
   * infrastructure or carry arbitrary content; only whether they are set.
   */
  public function testInfrastructureAndFreeTextAreReducedToPresence(): void
  {
    $applied = ConfigPolicy::apply($this->realisticSettings());
    $shown = $applied['shown'];

    foreach (['SMTP_HOST', 'SMTP_USERNAME', 'EMAIL_FALLBACK_FROM',
              'CUSTOM_SCRIPT', 'SELF_REGISTRATION_BLACKLIST'] as $key) {
      $this->assertSame('(set)', $shown[$key] ?? null,
        "$key must be reported as presence only");
    }
  }

  public function testUsefulFlagsAreReportedVerbatim(): void
  {
    $shown = ConfigPolicy::apply($this->realisticSettings())['shown'];

    $this->assertSame('v1.9.23', $shown['WEBCAL_PROGRAM_VERSION'] ?? null);
    $this->assertSame('Y', $shown['SEND_EMAIL'] ?? null);
    $this->assertSame('Y', $shown['UAC_ENABLED'] ?? null);
  }

  /**
   * The allowlist is the whole safety property: a key nobody classified is
   * left out, so a future setting holding a credential cannot leak by default.
   */
  public function testUnclassifiedKeysAreOmittedAndCounted(): void
  {
    $applied = ConfigPolicy::apply([
      'UAC_ENABLED' => 'Y',
      'BGCOLOR' => '#ffffff',
      'SOME_FUTURE_SETTING' => 'whatever',
    ]);

    $this->assertArrayNotHasKey('BGCOLOR', $applied['shown']);
    $this->assertArrayNotHasKey('SOME_FUTURE_SETTING', $applied['shown']);
    $this->assertSame(2, $applied['omitted']);
  }

  /**
   * Control case: proves the assertions above are capable of failing. A key
   * added to the allowlist by mistake is still caught by the name pattern.
   */
  public function testASecretNamedKeyIsNeverShownVerbatim(): void
  {
    $this->assertSame(ConfigPolicy::PRESENCE,
      ConfigPolicy::classify('SMTP_PASSWORD'));
    $this->assertSame(ConfigPolicy::PRESENCE,
      ConfigPolicy::classify('REMINDER_WEB_TRIGGER_TOKEN'));
    $this->assertSame(ConfigPolicy::OMITTED,
      ConfigPolicy::classify('SOME_NEW_API_KEY'));
    // ...while an ordinary flag is shown, so the test is not passing by
    // classifying everything as secret.
    $this->assertSame(ConfigPolicy::SHOWN,
      ConfigPolicy::classify('UAC_ENABLED'));
  }

  public function testReportJsonParsesAndCarriesItsSchema(): void
  {
    $report = new Report(['php' => ['version' => '8.4.25']], '2026-09-24T00:00:00Z');
    $decoded = json_decode($report->toJson(), true);

    $this->assertIsArray($decoded, 'a truncated report is detected by JSON failing to parse');
    $this->assertSame(Report::SCHEMA_VERSION, $decoded['schema_version']);
    $this->assertSame('2026-09-24T00:00:00Z', $decoded['generated_at']);
    $this->assertTrue($decoded['report_complete']);
    $this->assertSame('8.4.25', $decoded['sections']['php']['version']);
  }

  public function testTextRenderingIncludesEverySection(): void
  {
    $report = new Report([
      'php' => ['version' => '8.4.25'],
      'database' => ['type' => 'mysqli'],
      'filesystem' => [],
    ], '2026-09-24T00:00:00Z');

    $text = $report->toText();
    $this->assertStringContainsString('[php]', $text);
    $this->assertStringContainsString('[database]', $text);
    $this->assertStringContainsString('(nothing to report)', $text);
  }
}
