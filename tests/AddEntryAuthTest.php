<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression test for add_entry.php.
 *
 * add_entry.php let any session -- including the anonymous __public__
 * auto-login -- add an event to "their" calendar by id with no view
 * authorization; the resulting participant row then makes view_entry.php
 * treat it as the caller's own event, bypassing ALLOW_VIEW_OTHER/group
 * checks. It now refuses public/nonuser/read-only sessions and requires view
 * access to a non-owned event (normalizing the 'N'-truthy return).
 *
 * Procedural page -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   add_entry.php:join-without-view-authorization (medium)
 */
final class AddEntryAuthTest extends TestCase
{
  private string $src;

  protected function setUp(): void
  {
    $this->src = file_get_contents(__DIR__ . '/../add_entry.php');
  }

  public function test_public_nonuser_readonly_refused(): void
  {
    self::assertMatchesRegularExpression(
      "/\\\$login\s*==\s*'__public__'\s*\|\|\s*\\\$is_nonuser\s*\|\|\s*\\\$readonly\s*==\s*'Y'/",
      $this->src,
      'public/nonuser/read-only sessions must not add events.'
    );
  }

  public function test_view_authorization_required_for_other_users_event(): void
  {
    self::assertMatchesRegularExpression(
      "/access_user_calendar\s*\(\s*'view'\s*,\s*\\\$row\[1\]\s*\)/",
      $this->src,
      'a non-owned event must be view-authorized before it can be added.'
    );
    self::assertStringContainsString("\$acc === 'Y'", $this->src,
      'the view check must not treat the truthy string N as allow.');
  }
}
