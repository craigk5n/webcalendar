<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression test for the iCal import UID-match authorization.
 *
 * import_data() matched an existing event by UID using only
 * weu.cal_login = $login, ignoring participation status, so a participant who
 * had declined (or been removed from) an event could re-import the same UID
 * and rewrite that event (webcal_entry UPDATE) and its participants' statuses.
 * The match now requires an active (A/W) participation.
 *
 * Procedural function -> source-structure assertion (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   includes/xcal.php:import_data:uid-match-update-skips-edit-authz (medium)
 */
final class ImportUidMatchAuthTest extends TestCase
{
  public function test_uid_match_requires_active_participation(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/xcal.php');
    self::assertMatchesRegularExpression(
      "/weu\.cal_status\s+IN\s*\(\s*\\\\?'A\\\\?'\s*,\s*\\\\?'W\\\\?'\s*\)/",
      $src,
      'the import UID match must require an active (A/W) participation.'
    );
  }
}
