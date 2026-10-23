<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the assistant/nonuser-admin delegation holes.
 *
 *   doc.php granted $can_view on the delegation flags ($is_nonuser_admin /
 *   $is_assistant) with no link between the blob's event and the account the
 *   delegation is over, so any user could name themselves their own assistant
 *   (via assistant_edit_handler.php) and download any event's attachment or
 *   comment. The grant must be tied to the blob's event belonging to $user.
 *
 *   assistant_edit_handler.php ran for __public__ and nonuser sessions and
 *   inserted any users[] value (including the boss itself or a non-existent
 *   login), letting an anonymous visitor grant moderation over the public
 *   calendar.
 *
 * These handlers are procedural and exit, so -- as with
 * BlobAndApprovalAccessTest -- we assert the source invariants that keep the
 * fixes in place.
 *
 * Covers security-audit run-1:
 *   doc.php:request-user-assistant-flag-grants-any-blob (high)
 *   assistant_edit_handler.php:boss:public-and-nonuser-session-grant (medium)
 */
final class DocBlobDelegationAuthTest extends TestCase
{
  private string $doc;
  private string $asst;

  protected function setUp(): void
  {
    $this->doc = file_get_contents(__DIR__ . '/../doc.php');
    $this->asst = file_get_contents(__DIR__ . '/../assistant_edit_handler.php');
  }

  public function test_doc_delegation_is_tied_to_the_event_owner(): void
  {
    // The delegation query must check the blob's event against $user.
    self::assertMatchesRegularExpression(
      '/\[\s*\$id\s*,\s*\$user\s*,\s*\$user\s*\]/',
      $this->doc,
      'doc.php must verify the blob event belongs to $user before granting '
        . 'an assistant/nonuser-admin delegation override.'
    );
  }

  public function test_doc_has_no_unconditional_delegation_grant(): void
  {
    self::assertDoesNotMatchRegularExpression(
      '/\$is_admin\s*\|\|\s*\$is_nonuser_admin\s*\|\|\s*\$is_assistant\s*\)\s*\n\s*\$can_view\s*=\s*true/',
      $this->doc,
      'doc.php must not grant blob access on the delegation flags alone.'
    );
  }

  public function test_assistant_handler_refuses_public_and_nonuser(): void
  {
    self::assertMatchesRegularExpression(
      "/\\\$login\s*==\s*'__public__'.*print_not_auth/s",
      $this->asst,
      'assistant_edit_handler.php must refuse __public__ / nonuser sessions.'
    );
  }

  public function test_assistant_handler_validates_users_entries(): void
  {
    // Self-assistant, pseudo-users and unknown logins must be skipped.
    self::assertStringContainsString('user_load_variables', $this->asst);
    self::assertMatchesRegularExpression(
      "/'__public__'/",
      $this->asst,
      'assistant_edit_handler.php must reject __public__ as an assistant.'
    );
  }
}
