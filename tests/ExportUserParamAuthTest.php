<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for the export_handler.php cross-user export hole.
 *
 * export_handler.php took 'user' from POST and exported that login's events
 * -- including private and confidential ones -- with no authorization check.
 * It must now refuse unless the requester is an admin or may view that
 * calendar, and export_get_event_entry() must return only the target's public
 * events (plus events the requester participates in) for a non-admin
 * cross-user export.
 *
 * Procedural handler -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   export_handler.php:user-param-missing-access_user_calendar (high)
 */
final class ExportUserParamAuthTest extends TestCase
{
  public function test_export_handler_authorizes_cross_user_requests(): void
  {
    $src = SourceText::php(file_get_contents(__DIR__ . '/../export_handler.php'));
    self::assertMatchesRegularExpression(
      "/access_user_calendar\s*\(\s*'view'\s*,\s*\\\$user\s*\)/",
      $src,
      'export_handler.php must check view access to the requested calendar.'
    );
    self::assertStringContainsString(
      'die_miserable_death',
      $src,
      'export_handler.php must refuse an unauthorized cross-user export.'
    );
  }

  public function test_export_query_masks_other_users_private_events(): void
  {
    $body = SourceText::phpFunction(
      file_get_contents(__DIR__ . '/../includes/xcal.php'),
      'export_get_event_entry'
    );
    self::assertNotNull($body);
    self::assertMatchesRegularExpression(
      "/we\.cal_access\s*=\s*'P'/",
      $body,
      'a non-admin cross-user export must be limited to public events.'
    );
    self::assertStringContainsString(
      'is_admin',
      $body,
      'the public-only restriction must be lifted only for admins.'
    );
  }
}
