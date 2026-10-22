<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Assigns a `Severity` to a `ScannedFile` based on its kind and
 * (for EXTRA entries) its filename extension (signed-manifest
 * feature, GitHub issue #233).
 *
 * Rules (AC of Story 3.4):
 *   - MODIFIED or MISSING (any extension)       → WARN.
 *   - EXTRA + executable-PHP extension          → CRITICAL.
 *   - EXTRA + known non-executable / asset ext  → INFO.
 *   - EXTRA + unknown extension or no extension → WARN (conservative).
 *
 * The .php/.phtml/.phar/.inc set reflects what PHP and common
 * Apache/Nginx handlers parse as PHP. Attackers may disguise
 * webshells under other names (e.g. shell.php.bak), but whether
 * that runs depends on server config — WARN is the correct default
 * for anything we can't prove is safe.
 */
final class SeverityClassifier
{
  /**
   * File extensions (lowercase, no leading dot) that PHP's SAPI layer
   * or typical server configs will execute as PHP code. An EXTRA file
   * with one of these is the strongest webshell signal.
   */
  private const EXECUTABLE_EXTENSIONS = [
    'php', 'phtml', 'phar', 'inc', 'phps', 'pht',
  ];

  /**
   * File extensions (lowercase, no leading dot) that we confidently
   * classify as static assets / documentation / client-side content.
   * An EXTRA with one of these is usually admin customization.
   */
  private const NON_EXECUTABLE_EXTENSIONS = [
    // Stylesheets and markup
    'css', 'html', 'htm', 'xml', 'svg',
    // Text and docs
    'txt', 'md', 'rst', 'map', 'log',
    // Images
    'png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'avif', 'bmp',
    // Fonts
    'woff', 'woff2', 'ttf', 'otf', 'eot',
    // Client-side / data
    'js', 'mjs', 'json', 'yml', 'yaml', 'toml', 'csv', 'tsv',
  ];

  public static function classify(ScannedFile $file): Severity
  {
    if ($file->kind === ScanEntryKind::MODIFIED || $file->kind === ScanEntryKind::MISSING) {
      return Severity::WARN;
    }

    // EXTRA: classify by extension.
    $ext = strtolower(pathinfo($file->path, PATHINFO_EXTENSION));

    if ($ext === '') {
      return Severity::WARN; // no extension — conservative
    }

    if (in_array($ext, self::EXECUTABLE_EXTENSIONS, true)) {
      return Severity::CRITICAL;
    }

    if (in_array($ext, self::NON_EXECUTABLE_EXTENSIONS, true)) {
      return Severity::INFO;
    }

    return Severity::WARN;
  }
}
