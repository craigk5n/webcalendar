<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the P1 stored-XSS output-encoding sweep
 * (XSS-2, XSS-4, XSS-5, XSS-6, XSS-7, XSS-8).
 *
 * User-controlled strings — event/category/group/layer names, participant and
 * comment full names, custom-field (site_extras) data — were emitted into HTML
 * without escaping. These source-structure tests assert the escaping stays in
 * place and that the previously-raw sinks are gone.
 */
final class XssOutputEncodingTest extends TestCase
{
  private function src(string $rel): string
  {
    $s = file_get_contents(__DIR__ . '/../' . $rel);
    self::assertNotFalse($s, "$rel must exist");
    return $s;
  }

  public function testCategoryNamesEscaped(): void
  {
    $f = $this->src('includes/functions.php');
    // The <option> sink must no longer interpolate cat_name raw.
    self::assertStringNotContainsString(
      ">{\$V['cat_name']}</option>",
      $f,
      'functions.php must not emit cat_name raw into an <option>.'
    );
    self::assertMatchesRegularExpression(
      "/htmlspecialchars\s*\(\s*\\\$V\['cat_name'\]\s*,\s*ENT_QUOTES\s*\)/",
      $f,
      'cat_name must be escaped in the category <option> output.'
    );
  }

  public function testParticipantFullNamesEscaped(): void
  {
    $v = $this->src('view_entry.php');
    // No bare $tempfullname output remains; every use is wrapped.
    self::assertDoesNotMatchRegularExpression(
      "/'&nbsp;'\s*\.\s*\\\$tempfullname\b(?!\s*,)/",
      $v,
      'view_entry.php must not echo $tempfullname unescaped.'
    );
    self::assertStringContainsString(
      'htmlspecialchars ( $tempfullname, ENT_QUOTES )',
      $v,
      'Participant full names must be escaped in view_entry.php.'
    );
  }

  public function testSiteExtraDataEscaped(): void
  {
    $v = $this->src('view_entry.php');
    self::assertStringNotContainsString(
      "echo \$extras[\$extra_name]['cal_data'];",
      $v,
      'Custom-field cal_data must not be echoed raw.'
    );
    self::assertStringContainsString(
      "htmlspecialchars(\$extras[\$extra_name]['cal_data'], ENT_QUOTES)",
      $v,
      'Custom-field cal_data must be escaped.'
    );
    // EXTRA_URL must only hyperlink http/https.
    self::assertStringContainsString(
      "preg_match('#^https?://#i', \$extra_url)",
      $v,
      'EXTRA_URL must restrict hyperlinking to http/https schemes.'
    );
  }

  public function testSearchResultsEscaped(): void
  {
    $s = $this->src('search_handler.php');
    self::assertStringNotContainsString(
      "'\">' . \$result['text'] . '</a>",
      $s,
      'Search result text must not be emitted raw.'
    );
    self::assertStringContainsString(
      "htmlspecialchars ( \$result['text'], ENT_QUOTES )",
      $s,
      'Search result text must be escaped.'
    );
  }

  public function testDropdownsEscaped(): void
  {
    foreach (
      [
        'views_edit.php' => "\$users[\$i]['cal_fullname'], ENT_QUOTES",
        'edit_report.php' => "\$userlist[\$i]['cal_fullname'], ENT_QUOTES",
        'admin.php' => "\$views[\$i]['cal_name'], ENT_QUOTES",
      ] as $file => $needle
    ) {
      self::assertStringContainsString(
        'htmlspecialchars',
        $this->src($file),
        "$file dropdown values must be escaped."
      );
      self::assertStringContainsString(
        $needle,
        $this->src($file),
        "$file must escape the user-controlled option value/text."
      );
    }
  }

  public function testGroupsClientSideEscaping(): void
  {
    $g = $this->src('groups.php');
    self::assertMatchesRegularExpression(
      '/function\s+escapeHtml\s*\(/',
      $g,
      'groups.php must define a client-side escapeHtml().'
    );
    self::assertStringContainsString('escapeHtml(g.name)', $g);
    // The user-controlled name must no longer be inlined into the onclick.
    self::assertStringNotContainsString(
      "edit_group(\" + id + \", '\" + name +",
      $g,
      'group name must not be inlined into the onclick handler.'
    );
  }

  public function testLayersClientSideEscaping(): void
  {
    $l = $this->src('layers.php');
    self::assertMatchesRegularExpression(
      '/function\s+escapeHtml\s*\(/',
      $l,
      'layers.php must define a client-side escapeHtml().'
    );
    self::assertStringContainsString('escapeHtml(l.fullname)', $l);
    self::assertStringContainsString('escapeHtml(l.color)', $l);
  }
}
