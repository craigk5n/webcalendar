<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for the security-audit needs-validation leads that are
 * safely fixable in source (run-1).
 *
 *   - ajax.php echoed the 'page' request parameter unescaped into a text/html
 *     response with no CSP (reflected XSS).
 *   - rss.php placed event name/description/category inside <![CDATA[ ... ]]>
 *     with no handling of ']]>', letting a crafted field close the CDATA and
 *     inject markup into the feed.
 *   - The release signing job ran a third-party action (shivammathur/setup-php)
 *     by mutable tag alongside RELEASE_SIGNING_KEY.
 *   - The LDAP backend's user_valid_crypt() returned true for any input, so a
 *     remember-me cookie was accepted without verification.
 *
 * @covers ::cdata_escape
 */
final class AuditLeftoversTest extends TestCase
{
  public function test_cdata_escape_neutralizes_terminator(): void
  {
    self::assertSame('a]]]]><![CDATA[>b', cdata_escape('a]]>b'));
    self::assertSame('plain text', cdata_escape('plain text'));
    // A naive breakout payload no longer closes the CDATA section.
    self::assertStringNotContainsString(
      ']]><script',
      '<![CDATA[' . cdata_escape(']]><script>alert(1)</script>') . ']]>'
    );
  }

  public function test_ajax_escapes_reflected_page(): void
  {
    $src = file_get_contents(__DIR__ . '/../ajax.php');
    self::assertMatchesRegularExpression(
      '/htmlspecialchars\s*\(\s*\$page/',
      $src,
      'ajax.php must escape the reflected page parameter.'
    );
  }

  public function test_rss_cdata_fields_are_escaped(): void
  {
    $src = file_get_contents(__DIR__ . '/../rss.php');
    foreach (['getName', 'getDescription'] as $getter) {
      self::assertMatchesRegularExpression(
        '/cdata_escape\s*\(\s*\$\w+\[\$j\]->' . $getter . '/',
        $src,
        "rss.php must CDATA-escape $getter output."
      );
    }
    self::assertStringContainsString('cdata_escape ( $category )', $src);
  }

  public function test_release_action_is_sha_pinned(): void
  {
    $src = file_get_contents(__DIR__ . '/../.github/workflows/release.yml');
    self::assertMatchesRegularExpression(
      '#shivammathur/setup-php@[0-9a-f]{40}#',
      $src,
      'the third-party setup-php action must be pinned to a commit SHA.'
    );
    self::assertDoesNotMatchRegularExpression(
      '#shivammathur/setup-php@v\d+\s#',
      $src,
      'setup-php must not be referenced by a mutable tag.'
    );
  }

  public function test_ldap_remember_me_fails_closed(): void
  {
    $body = SourceText::phpFunction(
      file_get_contents(__DIR__ . '/../includes/user-ldap.php'),
      'user_valid_crypt'
    );
    self::assertNotNull($body);
    self::assertStringContainsString('return false', $body,
      'LDAP user_valid_crypt must not accept an unverifiable cookie.');
    self::assertStringNotContainsString('return true', $body);
  }
}
