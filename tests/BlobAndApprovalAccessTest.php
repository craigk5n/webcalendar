<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for P1 access-control fixes:
 *
 *   AC-5  list_unapproved.php — the approval/reject/delete loop trusted an
 *         attacker-supplied participant login and an unvalidated status code,
 *         letting any user change another user's participation status (IDOR).
 *   AC-6  doc.php — a blanket "any logged-in user" grant (gated only on
 *         PUBLIC_ACCESS_OTHERS, which defaults to 'Y') let any user download
 *         any attachment/comment blob by guessing its id.
 *   FILE-3 doc.php — the uploaded filename was placed raw (and unquoted) in the
 *         Content-Disposition header (response splitting), $disp was never
 *         applied, and the client-supplied MIME type was served inline
 *         (stored XSS via text/html or image/svg+xml).
 *
 * Source-structure tests: these handlers are procedural and exit, so we assert
 * the invariants that keep the fixes in place.
 */
final class BlobAndApprovalAccessTest extends TestCase
{
  private string $listUnapproved;
  private string $doc;

  protected function setUp(): void
  {
    $lu = file_get_contents(__DIR__ . '/../list_unapproved.php');
    $dc = file_get_contents(__DIR__ . '/../doc.php');
    self::assertNotFalse($lu, 'list_unapproved.php must exist');
    self::assertNotFalse($dc, 'doc.php must exist');
    $this->listUnapproved = $lu;
    $this->doc = $dc;
  }

  public function testApprovalLoopAuthorizesTargetUser(): void
  {
    self::assertMatchesRegularExpression(
      '/function\s+can_process_unapproved_for\s*\(/',
      $this->listUnapproved,
      'list_unapproved.php must define an authorization helper.'
    );
    // update_status() must only be called after the authorization check.
    self::assertMatchesRegularExpression(
      '/can_process_unapproved_for\s*\(\s*\$app_user\s*\)\s*\)\s*\n?\s*update_status/',
      $this->listUnapproved,
      'update_status() must be guarded by can_process_unapproved_for($app_user).'
    );
  }

  public function testApprovalActionIsWhitelisted(): void
  {
    self::assertMatchesRegularExpression(
      "/in_array\s*\(\s*\\\$process_action\s*,\s*\[\s*'A'\s*,\s*'R'\s*,\s*'D'\s*\]/",
      $this->listUnapproved,
      'process_action must be whitelisted to A/R/D before use.'
    );
  }

  public function testBlobBlanketGrantRemoved(): void
  {
    // The unconditional grant `if (... PUBLIC_ACCESS_OTHERS == 'Y') $can_view = true;`
    // that sat before the participant/group checks must be gone.
    self::assertDoesNotMatchRegularExpression(
      "/\(\s*\\\$login\s*!=\s*'__public__'\s*\)\s*&&\s*\(\s*\\\$PUBLIC_ACCESS_OTHERS\s*==\s*'Y'\s*\)\s*\)\s*\{\s*\\\$can_view\s*=\s*true/s",
      $this->doc,
      'doc.php must not blanket-grant blob access to every logged-in user.'
    );
  }

  public function testDocSanitizesFilenameAndDisposition(): void
  {
    // Filename is run through basename() and CR/LF/quote stripping.
    self::assertMatchesRegularExpression(
      '/basename\s*\(\s*\(string\)\s*\$filename\s*\)/',
      $this->doc,
      'doc.php must basename() the filename before using it in a header.'
    );
    self::assertStringContainsString(
      'Content-Disposition: ' . "'",
      $this->doc,
      'doc.php must apply a disposition (inline/attachment) and quote the filename.'
    );
    // The old raw, dispositionless header must be gone.
    self::assertStringNotContainsString(
      "Header ( 'Content-Disposition: filename=' . \$filename )",
      $this->doc,
      'The unsanitized Content-Disposition header must be removed.'
    );
  }

  public function testDocUsesInlineMimeAllowList(): void
  {
    self::assertMatchesRegularExpression(
      '/\$inlineSafe\s*=/',
      $this->doc,
      'doc.php must restrict inline rendering to an allow-list of inert MIME types.'
    );
    self::assertMatchesRegularExpression(
      "/\\\$mimetype\s*=\s*'application\/octet-stream'/",
      $this->doc,
      'Non-allow-listed blobs must be served as application/octet-stream.'
    );
  }
}
