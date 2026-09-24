<?php

declare(strict_types=1);

// Command-line only. Served over HTTP this hands execution to anyone who can
// reach the URL, and .htaccess cannot be relied on: the Debian and Ubuntu
// default of AllowOverride None makes it a no-op.
if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit(basename(__FILE__) . " must be run from the command line.\n");
}

/**
 * Flags comments that read as machine-written, using the mechanical subset of
 * webcalendar-core's AI-SIGNALS.md.
 *
 * Only the checks that can be made without judgement are here. "Over-explaining
 * comments" and "unnecessary abstractions" are real signals and are left out,
 * because deciding them needs a reader and a false accusation is worse than a
 * miss.
 *
 * Scope is PHP comment tokens. Prose in Markdown is excluded deliberately: the
 * second-person rule is about comments written for a maintainer, and
 * documentation addressing its reader as "you" is correct.
 *
 * ai-signals:allow -- this block quotes the phrasing it detects.
 *
 * Scope matters more than the rules do. Run over the whole tree this reports
 * about sixty findings, and nearly all of them are legitimate: WebCalendar has
 * twenty-five years of comments that address a system administrator directly
 * ("You can test this script from the command line"), which is good writing for
 * its audience and not a machine tell. AI-SIGNALS.md's premise that humans
 * rarely write "you" holds for new application code and not for this tree.
 *
 * So the useful mode is --since, which reports only lines a change actually
 * added. New code is held to the convention; existing prose is left alone.
 *
 *   php tools/check-ai-signals.php                  whole tree, advisory
 *   php tools/check-ai-signals.php --since=REF      only lines added since REF
 *   php tools/check-ai-signals.php --since=REF --strict   exit 1 on a finding
 *   php tools/check-ai-signals.php --path=DIR       scan somewhere else
 *
 * A comment containing the marker ai-signals:allow is skipped. Code that
 * discusses these phrases has to be able to quote one, which this file found
 * out about itself on its first run in CI: the docblock below quotes an
 * example and the tool flagged it.
 */

$root = dirname(__DIR__);
$argv = $_SERVER['argv'] ?? [];
$strict = in_array('--strict', $argv, true);
$since = '';
foreach ($argv as $arg) {
  if (str_starts_with($arg, '--since=')) {
    $since = substr($arg, 8);
  }
  // --path exists so the rules can be tested against a fixture tree rather
  // than against this repository, which would make the test depend on the
  // repository's own comments.
  if (str_starts_with($arg, '--path=')) {
    $root = rtrim(substr($arg, 7), '/');
  }
}

$addedLines = null;
if ($since === '') {
  $files = ai_signal_all_php_files($root);
} else {
  $addedLines = ai_signal_added_lines($root, $since);
  $files = [];
  foreach (array_keys($addedLines) as $rel) {
    if (is_file($root . '/' . $rel)) {
      $files[] = $root . '/' . $rel;
    }
  }
}

$findings = [];
foreach ($files as $file) {
  foreach (ai_signal_scan($file) as $finding) {
    // In --since mode only lines the change introduced count. Editing a file
    // must not make its existing comments somebody's problem.
    if ($addedLines !== null
      && !in_array($finding['line'], $addedLines[$finding['file']] ?? [], true)) {
      continue;
    }
    $findings[] = $finding;
  }
}

if ($findings === []) {
  echo 'No AI-writing signals in ' . count($files) . " file(s).\n";
  exit(0);
}

$byRule = [];
foreach ($findings as $f) {
  $byRule[$f['rule']][] = $f;
}
ksort($byRule);

foreach ($byRule as $rule => $rows) {
  echo "\n" . $rule . ' (' . count($rows) . ")\n";
  foreach (array_slice($rows, 0, 12) as $row) {
    echo '  ' . $row['file'] . ':' . $row['line'] . '  ' . $row['excerpt'] . "\n";
  }
  if (count($rows) > 12) {
    echo '  ... and ' . (count($rows) - 12) . " more\n";
  }
}

echo "\n" . count($findings) . ' finding(s) across ' . count($files) . " file(s).\n";
if (!$strict) {
  echo "Advisory run; pass --strict to fail on findings.\n";
}

exit($strict ? 1 : 0);

/**
 * @return list<string>
 */
function ai_signal_all_php_files(string $root): array
{
  $skip = '#/(vendor|pub|node_modules|\.git|docs/archive|tests/fixtures|includes/classes/phpmailer|includes/classes/hKit|includes/classes/captcha)/#';
  $out = [];

  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($it as $file) {
    $path = str_replace('\\', '/', $file->getPathname());
    if (!str_ends_with($path, '.php') || preg_match($skip, $path)) {
      continue;
    }
    $out[] = $path;
  }
  sort($out);

  return $out;
}

/**
 * Line numbers each PHP file gained since $since, from a zero-context diff.
 *
 * @return array<string, list<int>>
 */
function ai_signal_added_lines(string $root, string $since): array
{
  $cmd = 'git -C ' . escapeshellarg($root) . ' diff -U0 --diff-filter=ACMR '
    . escapeshellarg($since) . '...HEAD -- "*.php" 2>/dev/null';

  $out = [];
  $file = '';
  foreach (explode("\n", (string) shell_exec($cmd)) as $line) {
    if (str_starts_with($line, '+++ b/')) {
      $file = substr($line, 6);
      $out[$file] ??= [];
      continue;
    }
    // @@ -old,count +new,count @@
    if ($file !== '' && preg_match('/^@@ -\S+ \+(\d+)(?:,(\d+))? @@/', $line, $m)) {
      $start = (int) $m[1];
      $count = isset($m[2]) ? (int) $m[2] : 1;
      for ($i = 0; $i < $count; $i++) {
        $out[$file][] = $start + $i;
      }
    }
  }

  return $out;
}

/**
 * @return list<array{rule: string, file: string, line: int, excerpt: string}>
 */
function ai_signal_scan(string $path): array
{
  $src = @file_get_contents($path);
  if ($src === false) {
    return [];
  }

  // Rules are deliberately narrow. Each one fires on a phrase a maintainer
  // would not write, not on a topic.
  $rules = [
    'second-person' => '/\b(you can|you should|you need to|you may want|you must|you\'ll|you would|if you\'re)\b/i',
    'instructional' => '/\b(note that|make sure to|be sure to|ensure that|keep in mind|it\'s worth noting|don\'t forget to|remember to)\b/i',
    'buzzword' => '/\b(robust|seamless|leverage|utilize|comprehensive|facilitate|streamline|elegant|straightforward|essentially)\b/i',
    'bookend-marker' => '/(--- *end |\}\s*\/\/\s*end\b|\/\/\s*end of (function|class|foreach|if)\b)/i',
  ];

  $findings = [];
  foreach (token_get_all($src) as $token) {
    if (!is_array($token)) {
      continue;
    }
    if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
      continue;
    }

    $text = $token[1];
    $line = $token[2];

    // An explicit, visible opt-out. A reader can see that the author made a
    // decision here rather than the check silently not applying.
    if (str_contains($text, 'ai-signals:allow')) {
      continue;
    }

    foreach ($rules as $rule => $pattern) {
      if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
        $findings[] = [
          'rule' => $rule,
          'file' => ai_signal_relative($path),
          'line' => $line + substr_count(substr($text, 0, $m[0][1]), "\n"),
          'excerpt' => ai_signal_excerpt($text, (int) $m[0][1]),
        ];
      }
    }
  }

  return $findings;
}

function ai_signal_relative(string $path): string
{
  global $root;
  $base = str_replace('\\', '/', $root) . '/';

  return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
}

function ai_signal_excerpt(string $text, int $offset): string
{
  $start = (int) strrpos(substr($text, 0, $offset), "\n");
  $line = trim(substr($text, $start, 110));
  $line = preg_replace('#^[\s*/]+#', '', $line) ?? $line;

  return trim(explode("\n", $line)[0]);
}
