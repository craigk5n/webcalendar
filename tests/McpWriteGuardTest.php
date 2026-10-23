<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression test for the MCP write-tool authorization guard.
 *
 * The MCP write tools (add_event, add_recurring_event, update_event,
 * delete_event) checked only is_mcp_write_enabled(); they ignored the site
 * read-only mode and the UAC "Edit Event" function permission that the web UI
 * enforces. A shared writeDenied() gate now applies all three, and the MCP
 * bootstrap loads includes/access.php so the UAC check is available.
 *
 * Covers security-audit run-1:
 *   mcp.php:write-tools-skip-uac-event-edit-and-readonly (medium)
 */
final class McpWriteGuardTest extends TestCase
{
  private string $src;

  protected function setUp(): void
  {
    $this->src = file_get_contents(__DIR__ . '/../mcp.php');
  }

  public function test_guard_checks_readonly_and_uac(): void
  {
    self::assertStringContainsString('function writeDenied', $this->src);
    self::assertStringContainsString('ACCESS_EVENT_EDIT', $this->src,
      'the guard must consult the UAC Edit Event permission.');
    self::assertMatchesRegularExpression(
      "/\\\$readonly[^;]*===\s*'Y'/",
      $this->src,
      'the guard must refuse writes in read-only mode.'
    );
  }

  public function test_all_four_write_tools_use_the_guard(): void
  {
    self::assertSame(
      4,
      substr_count($this->src, '$this->writeDenied()'),
      'every write tool must call writeDenied() first.'
    );
  }

  public function test_bootstrap_loads_access(): void
  {
    self::assertMatchesRegularExpression(
      "/'access'\s*,/",
      $this->src,
      'the MCP bootstrap must load includes/access.php for the UAC check.'
    );
  }
}
