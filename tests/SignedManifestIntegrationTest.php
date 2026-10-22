<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ExcludeRules;
use WebCalendar\Security\InstallationScanner;
use WebCalendar\Security\ManifestBuilder;
use WebCalendar\Security\ManifestParser;
use WebCalendar\Security\ManifestSigner;
use WebCalendar\Security\ManifestVerifier;
use WebCalendar\Security\ReleaseKeyGenerator;
use WebCalendar\Security\ScanEntryKind;
use WebCalendar\Security\ScannedFile;
use WebCalendar\Security\ScanReportFilter;
use WebCalendar\Security\Severity;
use WebCalendar\Security\SeverityClassifier;

require_once __DIR__ . '/../includes/classes/Security/ReleaseKeyGenerator.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestBuilder.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestSigner.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestData.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestParser.php';
require_once __DIR__ . '/../includes/classes/Security/VerifyResult.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestVerifier.php';
require_once __DIR__ . '/../includes/classes/Security/ScanEntryKind.php';
require_once __DIR__ . '/../includes/classes/Security/ScannedFile.php';
require_once __DIR__ . '/../includes/classes/Security/ScanReport.php';
require_once __DIR__ . '/../includes/classes/Security/ExcludeRules.php';
require_once __DIR__ . '/../includes/classes/Security/InstallationScanner.php';
require_once __DIR__ . '/../includes/classes/Security/Severity.php';
require_once __DIR__ . '/../includes/classes/Security/SeverityClassifier.php';
require_once __DIR__ . '/../includes/classes/Security/ScanReportFilter.php';

/**
 * Story 6.1 — End-to-end integration test.
 *
 * Exercises every WebCalendar\Security class in one pipeline:
 *
 *   ReleaseKeyGenerator → ManifestBuilder → ManifestSigner →
 *   ManifestVerifier  → ManifestParser  → InstallationScanner →
 *   SeverityClassifier → ScanReportFilter
 *
 * Each test writes a fresh fixture tree under /tmp/wc_int_*, builds
 * a real manifest with a real throwaway keypair, signs it, then
 * mutates the tree to exercise one classification path. Regression
 * catches any future refactor that breaks the contract between two
 * of the eight classes.
 */
final class SignedManifestIntegrationTest extends TestCase
{
  /** Tree root representing an installed (and possibly tampered) release. */
  private string $root;

  /** Test keypair — fresh per test for isolation. */
  private string $secretKeyB64;
  private string $publicKeyRaw;

  /** Paths of the three signed-manifest artifacts inside $root. */
  private string $manifestPath;
  private string $sigPath;
  private string $pubkeyPath;

  /**
   * The original file set baked into the manifest. Tests mutate the
   * disk around this; the manifest stays stable.
   *
   * @var array<string, string>  relpath => original content
   */
  private array $originalFiles = [
    'index.php' => '<?php // root index',
    'includes/init.php' => '<?php // init',
    'includes/functions.php' => '<?php // functions',
    'admin.php' => '<?php // admin',
    'pub/bootstrap.min.css' => 'body{}',
    'pub/jquery.min.js' => '/* jquery */',
    'images/logo.png' => "\x89PNG\r\n\x1a\n", // tiny fake PNG header
    'translations/English-US.txt' => 'English-US: ok',
  ];

  protected function setUp(): void
  {
    $this->root = sys_get_temp_dir() . '/wc_int_' . bin2hex(random_bytes(6));
    mkdir($this->root, 0755, true);

    // Write every original file.
    foreach ($this->originalFiles as $rel => $content) {
      $this->writeFile($rel, $content);
    }

    // Fresh keypair per test.
    $kp = ReleaseKeyGenerator::generate();
    $this->secretKeyB64 = base64_encode($kp['secretKey']);
    $this->publicKeyRaw = $kp['publicKey'];

    // Place the three artifacts at the install root. Order matters:
    // the pubkey file must exist BEFORE the manifest is built, because
    // the real release workflow hashes the pubkey into the manifest
    // (it's in release-files). Mirroring that order here means the
    // pubkey is MATCH by design rather than EXTRA.
    $this->manifestPath = $this->root . '/MANIFEST.sha256';
    $this->sigPath = $this->root . '/MANIFEST.sha256.sig';
    $this->pubkeyPath = $this->root . '/release-signing-pubkey.pem';

    file_put_contents(
      $this->pubkeyPath,
      ReleaseKeyGenerator::formatPublicKeyPem($this->publicKeyRaw)
    );

    // Include the pubkey in the manifest file-list (matches the real
    // release workflow — `release-signing-pubkey.pem` is in release-files).
    $manifestFiles = array_keys($this->originalFiles);
    $manifestFiles[] = 'release-signing-pubkey.pem';

    $manifestBytes = ManifestBuilder::build(
      $this->root,
      $manifestFiles,
      '1.9.99-test',
      new DateTimeImmutable('2026-04-24T12:00:00Z'),
      'integration-sha'
    );
    file_put_contents($this->manifestPath, $manifestBytes);

    $signResult = ManifestSigner::sign($manifestBytes, $this->secretKeyB64);
    self::assertTrue($signResult['ok'], 'setup: manifest signing must succeed');
    file_put_contents($this->sigPath, $signResult['signature'] . "\n");
  }

  /** File count the scanner should classify as MATCH in the clean case. */
  private function expectedMatchedCount(): int
  {
    // Original files + release-signing-pubkey.pem (also listed in manifest).
    // MANIFEST.sha256 and MANIFEST.sha256.sig are excluded by D9 defaults,
    // so they don't contribute to matchedCount either.
    return count($this->originalFiles) + 1;
  }

  protected function tearDown(): void
  {
    if (!is_dir($this->root)) {
      return;
    }
    $rii = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($rii as $f) {
      $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($this->root);
  }

  // -- helpers -------------------------------------------------------------

  private function writeFile(string $rel, string $content): void
  {
    $full = $this->root . '/' . $rel;
    $dir = dirname($full);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }
    file_put_contents($full, $content);
  }

  /**
   * Run the full audit pipeline (verify → parse → scan → classify) and
   * return the unfiltered ScanReport. Exactly what `security_audit.php`'s
   * `render_file_integrity_section()` would do up to the noise-filter step.
   *
   * @param list<string> $extraExcludes
   */
  private function auditPipeline(array $extraExcludes = []): array
  {
    $verify = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    if (!$verify->valid) {
      return ['valid' => false, 'reason' => $verify->reason, 'report' => null];
    }
    $manifest = ManifestParser::parse($this->manifestPath);
    $excludes = new ExcludeRules(array_merge(
      ExcludeRules::DEFAULT_PATTERNS,
      $extraExcludes
    ));
    $report = InstallationScanner::scan($manifest, $this->root, $excludes);
    return ['valid' => true, 'reason' => $verify->reason, 'report' => $report];
  }

  // -- Clean install (happy path) ------------------------------------------

  public function testFullPipelineOnCleanInstallProducesEmptyReport(): void
  {
    $out = $this->auditPipeline();

    self::assertTrue($out['valid'], $out['reason']);
    self::assertCount(0, $out['report']->modified);
    self::assertCount(0, $out['report']->missing);
    self::assertCount(0, $out['report']->extra);
    self::assertSame(
      $this->expectedMatchedCount(),
      $out['report']->matchedCount,
      'Every original file must be classified MATCH.'
    );
  }

  // -- Individual failure modes --------------------------------------------

  public function testAddedPhpFileFlaggedAsCriticalExtra(): void
  {
    $this->writeFile('shell.php', '<?php system($_GET["c"]);');

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);
    self::assertCount(1, $out['report']->extra);

    $extra = $out['report']->extra[0];
    self::assertSame('shell.php', $extra->path);
    self::assertSame(ScanEntryKind::EXTRA, $extra->kind);
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify($extra),
      'An unexpected .php file must classify as CRITICAL — this is the '
      . 'primary webshell-detection signal the feature exists for.'
    );
  }

  public function testAddedCssFileFlaggedAsInfoExtra(): void
  {
    $this->writeFile('pub/css/custom.css', '.foo{}');

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);
    self::assertCount(1, $out['report']->extra);
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify($out['report']->extra[0])
    );
  }

  public function testModifiedShippedFileFlaggedAsWarnModified(): void
  {
    // Overwrite a manifest-listed file AFTER signing.
    file_put_contents(
      $this->root . '/admin.php',
      '<?php /* ATTACKER PAYLOAD */ ?>'
    );

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);
    self::assertCount(1, $out['report']->modified);

    $mod = $out['report']->modified[0];
    self::assertSame('admin.php', $mod->path);
    self::assertSame(ScanEntryKind::MODIFIED, $mod->kind);
    self::assertNotNull($mod->expectedHash);
    self::assertNotNull($mod->actualHash);
    self::assertNotSame($mod->expectedHash, $mod->actualHash);
    self::assertSame(Severity::WARN, SeverityClassifier::classify($mod));
  }

  public function testDeletedShippedFileFlaggedAsWarnMissing(): void
  {
    unlink($this->root . '/includes/functions.php');

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);
    self::assertCount(1, $out['report']->missing);

    $miss = $out['report']->missing[0];
    self::assertSame('includes/functions.php', $miss->path);
    self::assertSame(ScanEntryKind::MISSING, $miss->kind);
    self::assertNotNull($miss->expectedHash);
    self::assertNull($miss->actualHash);
    self::assertSame(Severity::WARN, SeverityClassifier::classify($miss));
  }

  // -- Tamper-detection contracts (the security-critical invariants) -------

  public function testOneByteFlipInManifestFailsVerification(): void
  {
    $bytes = file_get_contents($this->manifestPath);
    // Flip a byte in the middle (guaranteed to be a hash hex char).
    $mid = intdiv(strlen($bytes), 2);
    $bytes[$mid] = chr(ord($bytes[$mid]) ^ 0x01);
    file_put_contents($this->manifestPath, $bytes);

    $out = $this->auditPipeline();
    self::assertFalse(
      $out['valid'],
      'A one-byte flip in the manifest must cause signature verification '
      . 'to fail. This is THE core security contract.'
    );
    self::assertNull(
      $out['report'],
      'Scan results MUST NOT be produced when signature verification fails — '
      . 'a tampered manifest cannot be trusted to describe what should be on disk.'
    );
    self::assertMatchesRegularExpression(
      '/signature.*(does not verify|mismatch|failed)/i',
      $out['reason']
    );
  }

  public function testOneByteFlipInSignatureFailsVerification(): void
  {
    $sigB64 = trim(file_get_contents($this->sigPath));
    $sigRaw = base64_decode($sigB64, true);
    self::assertNotFalse($sigRaw);
    $sigRaw[10] = chr(ord($sigRaw[10]) ^ 0x01);
    file_put_contents($this->sigPath, base64_encode($sigRaw) . "\n");

    $out = $this->auditPipeline();
    self::assertFalse($out['valid']);
    self::assertNull($out['report']);
  }

  public function testSwappedPubkeyFailsVerification(): void
  {
    // Write a PEM for a DIFFERENT keypair — simulating an attacker
    // replacing the pubkey to validate their own forged manifest.
    $otherKp = ReleaseKeyGenerator::generate();
    file_put_contents(
      $this->pubkeyPath,
      ReleaseKeyGenerator::formatPublicKeyPem($otherKp['publicKey'])
    );

    $out = $this->auditPipeline();
    self::assertFalse($out['valid']);
    self::assertNull($out['report']);
  }

  // -- Mixed realistic scenario --------------------------------------------

  public function testMixedScenarioClassifiesAllFourCategoriesSimultaneously(): void
  {
    // Plant three different anomalies in parallel.
    file_put_contents(
      $this->root . '/admin.php',
      '<?php /* TAMPERED */ ?>'
    ); // MODIFIED → WARN
    unlink($this->root . '/includes/functions.php'); // MISSING → WARN
    $this->writeFile('shell.php', '<?php malware;'); // EXTRA CRITICAL

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);

    // Matched: all originals minus (admin.php modified + functions.php gone).
    self::assertSame(
      $this->expectedMatchedCount() - 2,
      $out['report']->matchedCount
    );
    self::assertCount(1, $out['report']->modified);
    self::assertCount(1, $out['report']->missing);
    self::assertCount(1, $out['report']->extra);

    $severities = array_map(
      fn(ScannedFile $f) => SeverityClassifier::classify($f)->value,
      array_merge(
        $out['report']->modified,
        $out['report']->missing,
        $out['report']->extra
      )
    );
    self::assertContains('warn', $severities);
    self::assertContains('critical', $severities);
  }

  // -- Filter wired into the pipeline --------------------------------------

  public function testCriticalOnlyFilterDropsWarnAndInfoInPipeline(): void
  {
    // Same 3-way anomaly as above + one INFO.
    file_put_contents($this->root . '/admin.php', '<?php TAMPERED;'); // WARN
    unlink($this->root . '/includes/functions.php'); // WARN
    $this->writeFile('shell.php', '<?php bad;'); // CRITICAL
    $this->writeFile('pub/css/custom.css', '.x{}'); // INFO

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);

    $filtered = ScanReportFilter::filter(
      $out['report'],
      ScanReportFilter::CRITICAL_ONLY
    );

    // critical_only: modified + missing (both WARN) should be empty;
    // only shell.php (CRITICAL EXTRA) survives.
    self::assertSame([], $filtered->modified);
    self::assertSame([], $filtered->missing);
    self::assertCount(1, $filtered->extra);
    self::assertSame('shell.php', $filtered->extra[0]->path);
    // matchedCount preserved.
    self::assertSame($out['report']->matchedCount, $filtered->matchedCount);
  }

  public function testWarnAndAboveFilterHidesInfoButKeepsWarnAndCritical(): void
  {
    $this->writeFile('shell.php', '<?php bad;'); // CRITICAL
    $this->writeFile('styles.css', '.x{}'); // INFO

    $out = $this->auditPipeline();
    $filtered = ScanReportFilter::filter(
      $out['report'],
      ScanReportFilter::WARN_AND_ABOVE
    );

    // Only shell.php survives; styles.css (INFO) gets hidden.
    self::assertCount(1, $filtered->extra);
    self::assertSame('shell.php', $filtered->extra[0]->path);
  }

  // -- Exclude rules wired into the pipeline -------------------------------

  public function testDefaultExcludesSuppressSettingsPhp(): void
  {
    // Plant a site-specific settings.php that ISN'T in the manifest.
    // Without excludes, it would surface as EXTRA. With D9 defaults,
    // it stays silent.
    $this->writeFile('includes/settings.php', '<?php // per-site config');

    $out = $this->auditPipeline();
    self::assertTrue($out['valid']);
    self::assertSame([], $out['report']->extra);
  }

  public function testCustomExcludeUnionsWithDefaults(): void
  {
    // pub/css/custom.css is NOT excluded by default (D9), so without
    // the extra pattern it shows as INFO EXTRA.
    $this->writeFile('pub/css/custom.css', '.x{}');

    $withDefaultsOnly = $this->auditPipeline();
    self::assertCount(1, $withDefaultsOnly['report']->extra);

    // Adding 'pub/css/*.css' as an admin extra suppresses it.
    $withCustomExtra = $this->auditPipeline(['pub/css/*.css']);
    self::assertSame([], $withCustomExtra['report']->extra);
  }

  // -- Runbook one-liner sanity check --------------------------------------

  public function testDocumentedRunbookOneLinerVerifiesCleanInstall(): void
  {
    // This is the exact pure-PHP one-liner from docs/release-signing.md,
    // inlined for test-runtime execution. If either side changes the
    // manifest format or signature encoding, this test breaks.
    $manifest = file_get_contents($this->manifestPath);
    $sig = base64_decode(trim(file_get_contents($this->sigPath)), true);
    $pem = file_get_contents($this->pubkeyPath);
    preg_match('/-----BEGIN.*?-----(.+?)-----END/s', $pem, $m);
    $pub = base64_decode(preg_replace('/\s+/', '', $m[1]), true);

    self::assertTrue(
      sodium_crypto_sign_verify_detached($sig, $manifest, $pub),
      'The documented runbook one-liner must verify a clean install.'
    );
  }

  public function testDocumentedSha256sumFormatMatches(): void
  {
    // Also a runbook claim: the manifest body is GNU sha256sum format.
    // Each content line should parse cleanly as "<64-hex>  <path>".
    $manifest = file_get_contents($this->manifestPath);
    $body = array_filter(
      explode("\n", $manifest),
      fn(string $line): bool => $line !== '' && !str_starts_with($line, '#')
    );
    self::assertNotEmpty($body);
    foreach ($body as $line) {
      self::assertMatchesRegularExpression(
        '/^[0-9a-f]{64}  \S/',
        $line,
        "Manifest body line does not match sha256sum format: [$line]"
      );
    }
  }
}
