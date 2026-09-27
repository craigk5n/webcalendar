<?php

declare(strict_types=1);

/**
 * Reading source as code rather than as text.
 *
 * Structural tests in this suite search source files for the mechanism they
 * are guarding -- a condition, a required argument, a mount option. Searching
 * the raw text does not work, because the file usually explains the very thing
 * being looked for in a comment right next to it, and the explanation
 * satisfies the search.
 *
 * That defect appeared six times in one day, four of them in guards that had
 * looked fine for months:
 *
 *   DestructiveTestGuardTest passed with the refusal replaced by `if false`,
 *   because each runner's comment block named includes/settings.php and
 *   WEBCAL_TEST_ALLOW_DESTRUCTIVE. Its ordering check compared against that
 *   comment, so it could never fail at all.
 *
 *   WizardRemovableTest reported includes/default_config.php as depending on
 *   wizard/, because a comment there says wizard/WizardDatabase.php requires
 *   it -- a dependency in the opposite direction.
 *
 *   ActivityLogConstantsTest reported LOG_NEWUSEREMAIL as undefined. It is a
 *   typo in a docblock listing the constants.
 *
 *   CliDbCheckTest, SeleniumWaitsTest and SandboxIsolationTest each asserted
 *   that some mechanism was *not* used, and each found its own comment
 *   explaining why it was not used.
 *
 * Six implementations of the same stripping, none of them tested, is how that
 * kept happening. This is one implementation, tested in SourceTextTest, so the
 * rule is a property of the suite instead of something to remember.
 *
 * Every method takes and returns a string, so nothing here touches the disk.
 */
final class SourceText
{
  /**
   * PHP with its comments removed.
   *
   * Rejoining the tokens verbatim reproduces the file exactly, minus the
   * comments: token_get_all() keeps whitespace as T_WHITESPACE, so offsets
   * inside the result stay meaningful for ordering assertions.
   */
  public static function php(string $source): string
  {
    $out = '';

    foreach (@token_get_all($source) as $token) {
      if (is_array($token)) {
        if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
          continue;
        }
        $out .= $token[1];
        continue;
      }
      $out .= $token;
    }

    return $out;
  }

  /**
   * One function's declaration and body, comments removed.
   *
   * includes/functions.php is 6600 lines and its pages redirect on include,
   * so a unit test cannot load either. Lifting the one function out and
   * eval()ing it exercises the shipped code rather than a copy that drifts.
   *
   * Braces are walked rather than matched with a pattern because a regex
   * cannot balance them. Interpolation of the "{$var}" kind would defeat the
   * walk -- that form opens with T_CURLY_OPEN and closes with a plain '}' --
   * so this counts brace *tokens* rather than characters, which handles it.
   *
   * @return string|null null when the file does not declare $name
   */
  public static function phpFunction(string $source, string $name): ?string
  {
    return self::walk($source, $name, false);
  }

  /**
   * The same function from its opening brace, so the signature is excluded.
   *
   * The distinction matters. UserResetPasswordTest asserts that nothing but
   * the --stdin flag reads the argument list, and the signature
   * `function wc_read_or_generate_password(array $argv)` names $argv, so
   * including it would make that assertion impossible to state.
   *
   * @return string|null null when the file does not declare $name
   */
  public static function phpFunctionBody(string $source, string $name): ?string
  {
    return self::walk($source, $name, true);
  }

  private static function walk(string $source, string $name,
    bool $bodyOnly): ?string
  {
    $tokens = @token_get_all($source);
    $start = null;
    $depth = 0;
    $out = '';

    foreach ($tokens as $i => $token) {
      if ($start === null) {
        if (!is_array($token) || $token[0] !== T_FUNCTION) {
          continue;
        }
        // The next meaningful token has to be the name we want.
        for ($j = $i + 1; $j < count($tokens); $j++) {
          $next = $tokens[$j];
          if (is_array($next) && $next[0] === T_WHITESPACE) {
            continue;
          }
          if (is_array($next) && $next[0] === T_STRING && $next[1] === $name) {
            $start = $i;
          }
          break;
        }
        if ($start === null) {
          continue;
        }
      }

      $isOpen = $token === '{'
        || (is_array($token) && $token[0] === T_CURLY_OPEN)
        || (is_array($token) && $token[0] === T_DOLLAR_OPEN_CURLY_BRACES);

      $text = is_array($token) ? $token[1] : $token;
      if (is_array($token)
        && ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
        $text = '';
      }
      // When only the body was asked for, start collecting at the brace that
      // opens it and skip the signature entirely.
      if (!$bodyOnly || $depth > 0 || $isOpen) {
        $out .= $text;
      }

      if ($token === '{'
        || (is_array($token) && $token[0] === T_CURLY_OPEN)
        || (is_array($token) && $token[0] === T_DOLLAR_OPEN_CURLY_BRACES)) {
        $depth++;
      } elseif ($token === '}') {
        $depth--;
        if ($depth === 0) {
          return $out;
        }
      }
    }

    return null;
  }

  /**
   * A shell script with its comment lines removed.
   *
   * Whole lines only. A trailing `# ...` is left alone, because a '#' inside a
   * quoted string is ordinary text and telling the two apart needs a parser.
   * Blank lines are kept so that positions in the result still correspond to
   * the shape of the original.
   */
  public static function shell(string $source): string
  {
    return self::withoutHashComments($source);
  }

  /**
   * YAML with its comment lines removed. Same rule as shell().
   */
  public static function yaml(string $source): string
  {
    return self::withoutHashComments($source);
  }

  /**
   * Python with its docstrings and comment lines removed.
   *
   * Triple-quoted blocks are treated as docstrings. That is true of every
   * Python file in this repository and is not true of Python in general, so
   * this belongs to these tests rather than to the language.
   */
  public static function python(string $source): string
  {
    $source = (string) preg_replace('/"""[\s\S]*?"""/', '', $source);
    $source = (string) preg_replace("/'''[\s\S]*?'''/", '', $source);

    return self::withoutHashComments($source);
  }

  private static function withoutHashComments(string $source): string
  {
    $out = [];

    foreach (explode("\n", $source) as $line) {
      if (preg_match('/^\s*#/', $line) === 1) {
        $out[] = '';
        continue;
      }
      $out[] = $line;
    }

    return implode("\n", $out);
  }
}
