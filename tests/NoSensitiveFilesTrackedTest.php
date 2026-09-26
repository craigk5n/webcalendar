<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Some files must never be committed, whatever .gitignore happens to say.
 *
 * .gitignore only helps for a path nobody has added yet: once a file is
 * tracked, git ignores the ignore rule. It is also easy to defeat by
 * accident -- `git add -A includes tools` picked up includes/settings.php.craig
 * on 2026-09-25, which holds this deployment's database credentials, and it
 * was caught by reading the staged list rather than by any rule.
 *
 * So the rule is enforced here against what git actually tracks: a live
 * configuration copy, a calendar database (real events and password hashes),
 * terraform state (contains resource attributes and sometimes secrets), or a
 * private key must not be in the repository.
 */
final class NoSensitiveFilesTrackedTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * Deliberately tracked, and correct to be:
   *   settings.php.orig       the shipped template, placeholder values only
   *   release-signing-pubkey  the public half of the signing key
   *
   * @var list<string>
   */
  private const ALLOWED = [
    'includes/settings.php.orig',
    'release-signing-pubkey.pem',
  ];

  /**
   * name => pattern, matched against every tracked path.
   *
   * @return array<string, string>
   */
  private function forbidden(): array
  {
    return [
      'a live configuration copy' =>
        '#(^|/)settings\.php($|\.)|(^|/)config\.php\.#',
      'a database' => '#\.(db|sqlite|sqlite3)$#i',
      'terraform state' => '#\.tfstate(\.|$)#',
      'a private key' =>
        '#(^|/)(id_rsa|id_ed25519)$|privkey|\.(pem|key|p12|pfx)$#i',
      'an editor or tool cache' => '#(^|/)\.(vscode|php-cs-fixer\.cache)#',
      'a backup left by an editor' => '#\.(bak|orig|save)$#',
    ];
  }

  /**
   * @return list<string>
   */
  private function trackedFiles(): array
  {
    $files = [];
    $status = 0;
    @exec('git -C ' . escapeshellarg(self::ROOT) . ' ls-files 2>/dev/null',
      $files, $status);

    if ($status !== 0 || $files === []) {
      self::markTestSkipped('git ls-files unavailable.');
    }

    return $files;
  }

  public function testNothingSensitiveIsTracked(): void
  {
    $offenders = [];

    foreach ($this->trackedFiles() as $path) {
      if (in_array($path, self::ALLOWED, true)) {
        continue;
      }
      foreach ($this->forbidden() as $what => $pattern) {
        if (preg_match($pattern, $path) === 1) {
          $offenders[] = "$path ($what)";
          break;
        }
      }
    }

    sort($offenders);

    self::assertSame([], $offenders, count($offenders) . ' file(s) that must '
      . "not be in the repository are tracked. Remove them with `git rm "
      . "--cached`, and rotate anything they exposed:\n  "
      . implode("\n  ", $offenders));
  }

  /**
   * The allow list has to stay short and each entry has to still exist, or it
   * quietly becomes a hole the next time one of these names is reused.
   */
  public function testEveryAllowedExceptionStillExists(): void
  {
    foreach (self::ALLOWED as $path) {
      self::assertFileExists(self::ROOT . '/' . $path,
        "$path is allowed as an exception but no longer exists; drop it from "
        . 'the list rather than leaving the exception open');
    }
  }

  /**
   * .gitignore is the first line of defence, and these are the entries that
   * would have prevented the near miss.
   */
  public function testTheIgnoreRulesCoverTheObviousCases(): void
  {
    $ignore = file_get_contents(self::ROOT . '/.gitignore');
    self::assertIsString($ignore);

    foreach (['includes/settings.php.*', '*.sqlite3', '*.db', '.terraform/']
      as $rule) {
      self::assertStringContainsString($rule, $ignore,
        ".gitignore must carry the $rule rule");
    }

    self::assertStringContainsString('!includes/settings.php.orig', $ignore,
      'the shipped template must stay visible to git');
  }
}
