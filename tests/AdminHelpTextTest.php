<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Story 5.2 — Admin help text.
 *
 * Source-structure regression test: asserts that BOTH the
 * file-integrity section in `security_audit.php` AND the admin
 * Security Audit fieldset in `admin.php` include a visible link
 * to the release-signing runbook.
 *
 * If a future refactor removes the link from either surface this
 * test breaks the build, so admins always have a one-click path
 * to the runbook.
 */
final class AdminHelpTextTest extends TestCase
{
  private const DOCS_URL = 'https://github.com/craigk5n/webcalendar/blob/master/docs/release-signing.md';
  private const AUDIT_PHP = __DIR__ . '/../security_audit.php';
  private const ADMIN_PHP = __DIR__ . '/../admin.php';
  private const RUNBOOK_PATH = __DIR__ . '/../docs/release-signing.md';

  public function testSecurityAuditLinksToRunbook(): void
  {
    $src = file_get_contents(self::AUDIT_PHP);
    self::assertNotFalse($src);
    self::assertStringContainsString(
      self::DOCS_URL,
      $src,
      'security_audit.php must include a link to docs/release-signing.md so '
      . 'admins have a one-click path from the file-integrity section to the '
      . 'runbook (Story 5.2 AC1).'
    );
  }

  public function testAdminSecurityAuditFieldsetLinksToRunbook(): void
  {
    $src = file_get_contents(self::ADMIN_PHP);
    self::assertNotFalse($src);
    self::assertStringContainsString(
      self::DOCS_URL,
      $src,
      'admin.php must include a link to docs/release-signing.md in the '
      . 'Security Audit fieldset so admins configuring the new settings '
      . 'have immediate access to the runbook (Story 5.2 AC2).'
    );
  }

  public function testRunbookFileActuallyExists(): void
  {
    // Defensive: if someone deletes docs/release-signing.md without
    // also removing the UI links, the links are dead. Catch that here.
    self::assertFileExists(
      self::RUNBOOK_PATH,
      'docs/release-signing.md must exist — admin UI links to it.'
    );
  }

  public function testDocsLinksUseNewTabAndSafeRel(): void
  {
    // noopener + noreferrer is the standard hardening pair for
    // target="_blank" links — prevents reverse-tabnabbing attacks.
    foreach ([self::AUDIT_PHP, self::ADMIN_PHP] as $file) {
      $src = file_get_contents($file);
      self::assertNotFalse($src);
      // Find the line with the docs URL and its surrounding anchor.
      $needle = self::DOCS_URL;
      $pos = strpos($src, $needle);
      self::assertNotFalse($pos, "Docs URL not found in $file");

      // Extract ~200 chars around the URL to inspect the anchor.
      $window = substr($src, max(0, $pos - 150), 350);
      self::assertStringContainsString('target="_blank"', $window, $file);
      self::assertStringContainsString('rel="noopener', $window, $file);
    }
  }
}
