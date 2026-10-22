<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

/**
 * Functional tests for the XSS-1 hardening of activate_urls().
 *
 * activate_urls() turns URLs in text into <a href> links. It previously
 * accepted ANY scheme (so javascript://%0aalert(1) became an executable href)
 * and included quote characters in the matched URL (allowing attribute
 * breakout). It now only linkifies http/https and excludes quotes.
 */
final class ActivateUrlsHardeningTest extends TestCase
{
  public function testHttpAndHttpsStillLinkified(): void
  {
    self::assertStringContainsString(
      '<a href="http://k5n.us">',
      activate_urls('see http://k5n.us now')
    );
    self::assertStringContainsString(
      '<a href="https://example.org/x">',
      activate_urls('https://example.org/x')
    );
  }

  public function testJavascriptSchemeNotLinkified(): void
  {
    // The classic javascript://%0a... bypass (which DOES contain "://").
    $out = activate_urls('javascript://%0aalert(document.cookie)');
    self::assertStringNotContainsString('href="javascript:', $out);
    self::assertStringNotContainsString('<a ', $out);
  }

  public function testDataAndVbscriptNotLinkified(): void
  {
    self::assertStringNotContainsString(
      '<a ',
      activate_urls('data://text/html;base64,PHNjcmlwdD4=')
    );
    self::assertStringNotContainsString(
      '<a ',
      activate_urls('vbscript://msgbox(1)')
    );
  }

  public function testQuotesCannotBreakOutOfHref(): void
  {
    // A double quote must terminate the matched URL so it cannot inject a new
    // attribute / event handler into the generated anchor.
    $out = activate_urls('http://example.com/"onmouseover="alert(1)');
    self::assertStringNotContainsString('onmouseover=', substr($out, 0, strpos($out, '</a>') ?: strlen($out)));
    self::assertStringContainsString('<a href="http://example.com/">', $out);
  }
}
