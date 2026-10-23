<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression test for del_entry.php authorization.
 *
 * $can_edit defaulted to ( $is_admin || $readonly != 'Y' ), i.e. true for any
 * non-readonly user, so the per-event participant/owner check could only ever
 * widen access. A non-participant could also POST override=1 to insert a
 * webcal_entry_repeats_not exclusion, hiding an occurrence of another user's
 * repeating event. $can_edit now starts false, and override_repeat requires
 * owner-level authority.
 *
 * Procedural page -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   del_entry.php:can_edit-default-true:repeats_not (medium)
 */
final class DelEntryAuthTest extends TestCase
{
  private string $src;

  protected function setUp(): void
  {
    $this->src = file_get_contents(__DIR__ . '/../del_entry.php');
  }

  public function test_can_edit_is_not_default_true(): void
  {
    self::assertDoesNotMatchRegularExpression(
      '/\$can_edit\s*=\s*\(\s*\$is_admin\s*\|\|\s*\$readonly\s*!=\s*\'Y\'\s*\)/',
      $this->src,
      '$can_edit must not default to true for every non-readonly user.'
    );
  }

  public function test_override_repeat_requires_owner_authority(): void
  {
    self::assertMatchesRegularExpression(
      '/\$override_repeat\s*&&\s*!\s*\(\s*\$is_admin\s*\|\|\s*\$my_event\s*\)/',
      $this->src,
      'a repeats_not exclusion must require owner/admin authority.'
    );
  }
}
