<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for view_entry.php access-control gaps.
 *
 *   - The rssuser parameter set $rss_view, which bypasses the can_view exit,
 *     with no restriction on the event's access class or status, exposing any
 *     event on an RSS-enabled user's calendar. rss_view must now reveal only
 *     what the feed would: an approved event whose cal_access rssuser's
 *     USER_REMOTE_ACCESS permits, on rssuser's own calendar.
 *   - Private/confidential masking blanked only name and description, leaving
 *     location, URL, category, site extras, attachments and comments visible.
 *
 * Procedural page -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   view_entry.php:rss_view-bypasses-can_view (medium)
 *   view_entry.php:private-mask-omits-location-url-comments (low)
 */
final class ViewEntryAccessTest extends TestCase
{
  private string $src;

  protected function setUp(): void
  {
    $this->src = SourceText::php(file_get_contents(__DIR__ . '/../view_entry.php'));
  }

  public function test_rss_view_limited_to_feed_contents(): void
  {
    self::assertMatchesRegularExpression(
      '/in_array\s*\(\s*\$cal_access\s*,\s*\$allowed\s*\)/',
      $this->src,
      'rss_view must restrict the event access class to what the feed allows.'
    );
    self::assertMatchesRegularExpression(
      '/\$user\s*!=\s*\$rssuser/',
      $this->src,
      'rss_view must not serve a calendar other than rssuser\'s.'
    );
  }

  public function test_masked_event_hides_location_url_and_category(): void
  {
    self::assertMatchesRegularExpression(
      "/\\\$location\s*=\s*\\\$url\s*=\s*\\\$category\s*=\s*''/",
      $this->src,
      'a private/confidential event must blank location, URL and category.'
    );
  }

  public function test_masked_event_hides_comments(): void
  {
    self::assertMatchesRegularExpression(
      '/Doc::commentsEnabled\(\)\s*&&\s*!\s*\$is_private\s*&&\s*!\s*\$is_confidential/',
      $this->src,
      'comments must be suppressed for a private/confidential event.'
    );
  }
}
