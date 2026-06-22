<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for P1 MCP token hardening:
 *
 *   API-1  Tokens were stored in clear text in cal_api_token. They must be
 *          stored only as a SHA-256 hash.
 *   API-2  The token was generated client-side and the server stored whatever
 *          arrived in $_POST['mcp_api_token'] (any chosen/weak value). Tokens
 *          must be generated server-side with a CSPRNG.
 *   API-3  mcp.php logged token material / the Authorization header (and the
 *          partial-token log was triggerable via ?debug). The ?token= query
 *          auth method (tokens in URLs) was also removed.
 *
 * Source-structure tests over the token code.
 */
final class McpTokenHardeningTest extends TestCase
{
  private string $pref;
  private string $functions;
  private string $mcp;

  protected function setUp(): void
  {
    $this->pref      = (string) file_get_contents(__DIR__ . '/../pref.php');
    $this->functions = (string) file_get_contents(__DIR__ . '/../includes/functions.php');
    $this->mcp       = (string) file_get_contents(__DIR__ . '/../mcp.php');
  }

  public function testTokenGeneratedServerSideAndHashed(): void
  {
    self::assertMatchesRegularExpression(
      '/\$mcp_new_token\s*=\s*bin2hex\s*\(\s*random_bytes\s*\(\s*32\s*\)\s*\)/',
      $this->pref,
      'pref.php must generate the token server-side with random_bytes(32).'
    );
    self::assertMatchesRegularExpression(
      "/hash\s*\(\s*'sha256'\s*,\s*\\\$mcp_new_token\s*\)/",
      $this->pref,
      'pref.php must store only the SHA-256 hash of the token.'
    );
  }

  public function testClientSuppliedTokenNoLongerStored(): void
  {
    self::assertStringNotContainsString(
      "\$token = \$_POST['mcp_api_token']",
      $this->pref,
      'pref.php must not store a client-supplied token value.'
    );
  }

  public function testValidationLooksUpByHash(): void
  {
    self::assertMatchesRegularExpression(
      "/\\\$tokenHash\s*=\s*hash\s*\(\s*'sha256'\s*,\s*\\\$actualToken\s*\)/",
      $this->functions,
      'validate_mcp_token() must hash the presented token before lookup.'
    );
  }

  public function testNoTokenMaterialLogged(): void
  {
    // The Authorization header and full/partial token must never be logged.
    self::assertStringNotContainsString(
      "auth_header='\$auth_header'",
      $this->mcp,
      'mcp.php must not log the Authorization header.'
    );
    self::assertStringNotContainsString(
      "token='\$token'",
      $this->mcp,
      'mcp.php must not log the token.'
    );
    self::assertStringNotContainsString(
      "substr(\$token, 0, 8)",
      $this->mcp,
      'mcp.php must not log a partial token.'
    );
  }

  public function testDebugAndQueryTokenPathsRemoved(): void
  {
    self::assertStringNotContainsString(
      "isset(\$_GET['debug'])",
      $this->mcp,
      'The attacker-flippable ?debug logging trigger must be removed.'
    );
    self::assertStringNotContainsString(
      "\$_GET['token']",
      $this->mcp,
      'The ?token= query-string auth method must be removed (tokens leak in URLs).'
    );
  }
}
