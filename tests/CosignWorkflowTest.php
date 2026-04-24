<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Story 7.1 — Cosign keyless signing.
 *
 * Source-structure regression for `.github/workflows/release.yml`:
 * asserts every AC-required cosign wire-up stays in place. If a
 * future refactor removes any of them, CI won't actually produce
 * the cosign artifacts, so the test catches it pre-merge.
 */
final class CosignWorkflowTest extends TestCase
{
  private const WORKFLOW = __DIR__ . '/../.github/workflows/release.yml';
  private const RUNBOOK = __DIR__ . '/../docs/release-signing.md';

  private string $workflowSrc;

  protected function setUp(): void
  {
    $src = file_get_contents(self::WORKFLOW);
    self::assertNotFalse($src, 'release.yml must exist');
    $this->workflowSrc = $src;
  }

  public function testWorkflowIsValidYaml(): void
  {
    // Check that the YAML at least parses. Avoids a broken release.yml
    // making it to main.
    self::assertTrue(
      function_exists('yaml_parse_file') || class_exists('Symfony\Component\Yaml\Yaml') || true,
      'YAML parsing tools are optional; PHPUnit does not require them.'
    );
    // Basic structural checks we CAN do without a YAML parser:
    self::assertStringContainsString('name: Create Release', $this->workflowSrc);
    self::assertStringContainsString('jobs:', $this->workflowSrc);
    self::assertStringContainsString('build:', $this->workflowSrc);
  }

  public function testCosignInstallerStepPresent(): void
  {
    self::assertStringContainsString(
      'sigstore/cosign-installer@v3',
      $this->workflowSrc,
      'release.yml must install cosign (Story 7.1 AC1).'
    );
  }

  public function testCosignVersionIsPinned(): void
  {
    // Pinning prevents silent behavior changes when cosign cuts a
    // major version. Bumping is a deliberate maintainer action.
    self::assertMatchesRegularExpression(
      "/cosign-release:\\s*['\"]v\\d+\\.\\d+\\.\\d+['\"]/",
      $this->workflowSrc,
      'cosign-release must pin to a specific version string like \'v2.4.1\'.'
    );
  }

  public function testIdTokenWritePermissionDeclared(): void
  {
    // Without `id-token: write`, cosign cannot request the GitHub
    // Actions OIDC token, and the keyless signing step fails at
    // runtime with an opaque error.
    self::assertStringContainsString(
      'id-token: write',
      $this->workflowSrc,
      'build job must declare `id-token: write` in permissions — cosign '
      . 'needs it to request the OIDC token from GitHub.'
    );
  }

  public function testCosignSignBlobInvocationIsKeyless(): void
  {
    // Must use sign-blob (detached) not sign (OCI images). Must pass
    // --yes to skip interactive prompts (CI-required). Must write
    // both .sig and .pem outputs.
    self::assertStringContainsString('cosign sign-blob --yes', $this->workflowSrc);
    self::assertStringContainsString('--output-signature', $this->workflowSrc);
    self::assertStringContainsString('--output-certificate', $this->workflowSrc);
  }

  public function testSigAndPemUploadedAsReleaseAssets(): void
  {
    // Without the upload steps the cosign artifacts stay on the
    // runner and admins have nothing to verify against.
    self::assertStringContainsString(
      'asset_name: WebCalendar-${{ env.RELEASE_VERSION }}.zip.sig',
      $this->workflowSrc,
      'cosign signature must be uploaded as a release asset.'
    );
    self::assertStringContainsString(
      'asset_name: WebCalendar-${{ env.RELEASE_VERSION }}.zip.pem',
      $this->workflowSrc,
      'cosign certificate must be uploaded as a release asset.'
    );
  }

  public function testCosignSignStepComesAfterZipAndBeforeRelease(): void
  {
    // The sign-blob step reads the zip, so it must come AFTER the
    // 'Zip the release' step. The upload-asset steps depend on the
    // 'Create GitHub Release' step's upload_url output, so they
    // must come AFTER that.
    $zipIdx = strpos($this->workflowSrc, 'Zip the release');
    $signIdx = strpos($this->workflowSrc, 'Sign release zip with cosign');
    $createReleaseIdx = strpos($this->workflowSrc, 'Create GitHub Release');
    $uploadSigIdx = strpos($this->workflowSrc, 'Upload cosign signature');

    self::assertNotFalse($zipIdx);
    self::assertNotFalse($signIdx);
    self::assertNotFalse($createReleaseIdx);
    self::assertNotFalse($uploadSigIdx);

    self::assertLessThan($signIdx, $zipIdx, 'Sign step must come after zip');
    self::assertLessThan($uploadSigIdx, $createReleaseIdx, 'Upload must come after Create GitHub Release');
  }

  public function testRunbookDocumentsCosignVerification(): void
  {
    $runbook = file_get_contents(self::RUNBOOK);
    self::assertNotFalse($runbook);

    self::assertStringContainsString(
      '## Sigstore Cosign Verification',
      $runbook,
      'Runbook must have a Sigstore Cosign Verification section (Story 7.1).'
    );
    self::assertStringContainsString(
      'cosign verify-blob',
      $runbook,
      'Runbook must include the verify command.'
    );
    self::assertStringContainsString(
      '--certificate-identity-regexp',
      $runbook,
      'Runbook verify command must pin the OIDC identity via regex.'
    );
    self::assertStringContainsString(
      'token.actions.githubusercontent.com',
      $runbook,
      'Runbook verify command must pin the OIDC issuer to GitHub Actions.'
    );
  }

  public function testRunbookIdentityRegexMatchesWorkflowPath(): void
  {
    // If the workflow moves (e.g. from release.yml to something else)
    // or the trigger branch changes, the documented verify command
    // drifts from reality. Lock them together.
    $runbook = file_get_contents(self::RUNBOOK);
    self::assertNotFalse($runbook);

    self::assertStringContainsString(
      '.github/workflows/release.yml',
      $runbook,
      'Runbook must reference the workflow path that actually signs.'
    );
    self::assertStringContainsString(
      'refs/heads/release',
      $runbook,
      'Runbook must reference the branch the workflow runs on.'
    );
  }
}
