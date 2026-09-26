<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * What the documentation claims about this release has to be true.
 *
 * Documentation drifts silently, and an agent reading it treats it as ground
 * truth. Found on 2026-09-26, all of it in tracked files and most of it
 * shipped:
 *
 *   README.md's version badge said v1.9.16. The program was v1.9.23, seven
 *   releases later.
 *   docs/admin-guide.md was titled "WebCalendar v1.9.16 Administration
 *   Guide". It ships.
 *   docs/WebCalendar-Database.md said v1.9.13.
 *   docs/mcp-server.md documented four MCP tools. mcp.php registers seven, so
 *   an assistant reading the manual concluded it could not update or delete an
 *   event, and nothing said that writing needs MCP_WRITE_ACCESS at all.
 *
 * Only claims about the *current* version are checked. A changelog entry or a
 * roadmap section naming an old release is doing its job, which is why
 * docs/admin-guide.md lost its version stamp rather than gaining a rule: a
 * general guide gains nothing from one, and stamping it guarantees the drift.
 */
final class DocumentedVersionTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * The one source of truth, read from where the application reads it.
   */
  private function programVersion(): string
  {
    $config = file_get_contents(self::ROOT . '/includes/config.php');
    self::assertIsString($config);

    self::assertSame(1, preg_match("/\\\$PROGRAM_VERSION\s*=\s*'([^']+)'/",
      SourceText::php($config), $m),
      'includes/config.php must assign $PROGRAM_VERSION exactly once');

    return $m[1];
  }

  private function read(string $rel): string
  {
    $s = file_get_contents(self::ROOT . '/' . $rel);
    self::assertIsString($s, "could not read $rel");

    return $s;
  }

  public function testTheReadmeBadgeNamesThisVersion(): void
  {
    self::assertSame(1, preg_match('#badge/version-(v[\d.]+)-#',
      $this->read('README.md'), $m), 'README.md must carry a version badge');

    self::assertSame($this->programVersion(), $m[1],
      'the README badge is the first version claim anyone sees');
  }

  public function testTheSchemaDocNamesThisVersion(): void
  {
    self::assertSame(1, preg_match('/\*\*Version:\*\*\s*(v[\d.]+)/',
      $this->read('docs/WebCalendar-Database.md'), $m));

    self::assertSame($this->programVersion(), $m[1],
      'the schema documentation states which release it describes');
  }

  /**
   * Whatever is labelled current has to be. This is what README.md's roadmap
   * had wrong: "### v1.9.16 (Current)", seven releases after the fact.
   */
  public function testNothingElseIsLabelledCurrent(): void
  {
    $version = $this->programVersion();
    $offenders = [];

    foreach ($this->trackedMarkdown() as $rel) {
      if (str_contains($rel, 'docs/archive/') || $rel === 'CHANGELOG.md') {
        continue;
      }
      if (!preg_match_all('/(v[\d.]+)\s*\(Current\)/i', $this->read($rel),
        $m)) {
        continue;
      }
      foreach ($m[1] as $claimed) {
        if ($claimed !== $version) {
          $offenders[] = "$rel calls $claimed current; it is $version";
        }
      }
    }

    self::assertSame([], $offenders, implode("\n  ", $offenders));
  }

  /**
   * mcp.php registers a tool with an #[McpTool] attribute, which is the
   * authoritative list. An attribute survives comment stripping -- it is
   * T_ATTRIBUTE, not T_COMMENT -- so reading the code rather than the text
   * still finds them.
   */
  public function testEveryMcpToolIsDocumented(): void
  {
    $code = SourceText::php($this->read('mcp.php'));

    self::assertGreaterThan(0,
      preg_match_all('/#\[McpTool[^\]]*\]\s*public function (\w+)/', $code, $m),
      'mcp.php must register its tools with #[McpTool]');

    $documented = $this->read('docs/mcp-server.md');
    $missing = [];

    foreach ($m[1] as $tool) {
      if (!preg_match('/^###\s+' . preg_quote($tool, '/') . '\s*$/m',
        $documented)) {
        $missing[] = $tool;
      }
    }

    self::assertSame([], $missing, count($missing) . ' MCP tool(s) the server '
      . "offers but docs/mcp-server.md does not describe. An assistant reads "
      . "that file to learn what it can do:\n  " . implode("\n  ", $missing));
  }

  /**
   * And nothing documented that the server does not offer, which sends an
   * assistant looking for a tool that is not there.
   */
  public function testNoToolIsDocumentedThatDoesNotExist(): void
  {
    $code = SourceText::php($this->read('mcp.php'));
    preg_match_all('/#\[McpTool[^\]]*\]\s*public function (\w+)/', $code, $m);

    preg_match_all('/^###\s+([a-z][a-z0-9_]*)\s*$/m',
      $this->read('docs/mcp-server.md'), $headings);

    $extra = array_diff($headings[1], $m[1]);

    self::assertSame([], array_values($extra),
      'documented as tools but not registered in mcp.php: '
      . implode(', ', $extra));
  }

  /**
   * CLAUDE.md is the file an agent reads first, and it is not tracked, so this
   * can only run where it exists. That is the reason the drift went unnoticed:
   * it said v1.9.13 and listed four MCP tools.
   */
  public function testTheAgentInstructionsNameThisVersion(): void
  {
    $path = self::ROOT . '/CLAUDE.md';
    if (!is_file($path)) {
      self::markTestSkipped('CLAUDE.md is not present in this checkout.');
    }

    $claude = (string) file_get_contents($path);
    if (!preg_match('/\(v([\d.]+)\)/', $claude, $m)) {
      self::markTestSkipped('CLAUDE.md states no version.');
    }

    self::assertSame(ltrim($this->programVersion(), 'v'), $m[1],
      'CLAUDE.md is read as ground truth by an agent before anything else');
  }

  /**
   * @return list<string>
   */
  private function trackedMarkdown(): array
  {
    $files = [];
    $status = 0;
    @exec('git -C ' . escapeshellarg(self::ROOT) . ' ls-files "*.md" 2>/dev/null',
      $files, $status);

    if ($status !== 0 || $files === []) {
      self::markTestSkipped('git ls-files unavailable.');
    }

    return $files;
  }
}
