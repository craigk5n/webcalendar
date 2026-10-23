<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for owner-binding on views, layers and reports.
 *
 *   - layers_ajax.php save/delete used getPostValue('layeruser') as the row
 *     owner, so a user could change another user's layers. It now uses the
 *     session-bound $layer_user.
 *   - views_edit_handler.php rewrote webcal_view_user for an arbitrary view id
 *     with no ownership check; it now verifies the view is owned by $login.
 *   - edit_report_handler.php gated its owner check on single-user mode and
 *     queried the wrong column, so in multi-user mode a non-admin could edit
 *     or delete any report. The check now runs for every non-admin and uses
 *     cal_report_id.
 *
 * Procedural handlers -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   layers_ajax.php:layeruser-from-post (low)
 *   views_edit_handler.php:view_user-not-owner-bound (low)
 *   edit_report_handler.php:owner-check-gated-on-single_user (low)
 */
final class ObjectOwnershipTest extends TestCase
{
  public function test_layers_bound_to_session_user(): void
  {
    $src = file_get_contents(__DIR__ . '/../layers_ajax.php');
    self::assertSame(0, substr_count($src, "getPostValue('layeruser')"),
      'layer save/delete must not take the owner from POST.');
    self::assertStringContainsString('save_layer ( $layer_user', $src);
    self::assertStringContainsString('delete_layer ( $layer_user', $src);
  }

  public function test_view_user_list_requires_view_ownership(): void
  {
    $src = file_get_contents(__DIR__ . '/../views_edit_handler.php');
    self::assertMatchesRegularExpression(
      '/SELECT cal_view_id FROM webcal_view\s*\n?\s*WHERE cal_view_id = \? AND cal_owner = \?/',
      $src,
      'the webcal_view_user rewrite must verify view ownership first.'
    );
  }

  public function test_report_owner_check_runs_in_all_modes(): void
  {
    $src = file_get_contents(__DIR__ . '/../edit_report_handler.php');
    self::assertMatchesRegularExpression(
      '/SELECT cal_login FROM webcal_report\s*\n?\s*WHERE cal_report_id = \?/',
      $src,
      'the report owner check must query cal_report_id.'
    );
    self::assertDoesNotMatchRegularExpression(
      '/\$single_user != \'N\' &&\s*!\s*\n?\s*empty \( \$report_id \)/',
      $src,
      'the report owner check must not be gated on single-user mode.'
    );
  }
}
