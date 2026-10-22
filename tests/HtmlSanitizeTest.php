<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/htmlsanitize.php';

/**
 * Tests for the dependency-free rich-text HTML sanitizer (XSS-3).
 *
 * The sanitizer is an allow-list control: safe formatting must survive, and
 * every script/handler/dangerous-scheme vector must be neutralized.
 */
final class HtmlSanitizeTest extends TestCase
{
  /** @dataProvider safeContentPreserved */
  public function testSafeContentIsPreserved(string $html, string $mustContain): void
  {
    self::assertStringContainsString($mustContain, sanitize_html($html));
  }

  public static function safeContentPreserved(): array
  {
    return [
      'bold'        => ['<b>hi</b>', '<b>hi</b>'],
      'italic/em'   => ['<i>a</i><em>b</em>', '<em>b</em>'],
      'paragraph'   => ['<p>text</p>', '<p>text</p>'],
      'http link'   => ['<a href="http://example.com">x</a>', 'href="http://example.com"'],
      'https link'  => ['<a href="https://example.com/a?b=c">x</a>', 'href="https://example.com/a?b=c"'],
      'mailto link' => ['<a href="mailto:a@b.com">m</a>', 'href="mailto:a@b.com"'],
      'relative'    => ['<a href="/page.php?id=1">r</a>', 'href="/page.php?id=1"'],
      'anchor'      => ['<a href="#top">t</a>', 'href="#top"'],
      'list'        => ['<ul><li>one</li></ul>', '<li>one</li>'],
      'heading'     => ['<h2>Title</h2>', '<h2>Title</h2>'],
      'table'       => ['<table><tr><td colspan="2">c</td></tr></table>', 'colspan="2"'],
      'image http'  => ['<img src="https://x/y.png" alt="pic">', 'src="https://x/y.png"'],
      'utf8'        => ['<p>café — naïve 日本語</p>', '日本語'],
      'plain text'  => ['just text', 'just text'],
      'line break'  => ['a<br>b', '<br'],
    ];
  }

  /** @dataProvider xssVectors */
  public function testDangerousContentIsNeutralized(string $html, array $mustNotContain): void
  {
    $out = sanitize_html($html);
    foreach ($mustNotContain as $needle) {
      self::assertStringNotContainsString($needle, $out, "leaked: $needle  (output: $out)");
    }
  }

  public static function xssVectors(): array
  {
    return [
      'script tag'        => ['<script>alert(1)</script>', ['<script', 'alert(1)']],
      'img onerror'       => ['<img src=x onerror="alert(1)">', ['onerror', 'alert(1)']],
      'svg onload'        => ['<svg onload="alert(1)"></svg>', ['<svg', 'onload', 'alert(1)']],
      'body onload'       => ['<body onload=alert(1)>x', ['onload', '<body']],
      'a javascript'      => ['<a href="javascript:alert(1)">x</a>', ['javascript:', 'alert(1)']],
      'a JaVaScRiPt'      => ['<a href="JaVaScRiPt:alert(1)">x</a>', ['alert(1)']],
      'obfuscated scheme' => ["<a href=\"java\tscript:alert(1)\">x</a>", ['alert(1)']],
      'img data uri'      => ['<img src="data:text/html,<script>alert(1)</script>">', ['data:', '<script']],
      'iframe'            => ['<iframe src="https://evil"></iframe>', ['<iframe']],
      'object'            => ['<object data="x"></object>', ['<object']],
      'embed'             => ['<embed src="x">', ['<embed']],
      'style attr'        => ['<div style="x:expression(alert(1))">y</div>', ['style', 'expression']],
      'style tag'         => ['<style>body{}</style>', ['<style', 'body{']],
      'onclick span'      => ['<span onclick="alert(1)">z</span>', ['onclick', 'alert(1)']],
      'onmouseover'       => ['<p onmouseover="alert(1)">z</p>', ['onmouseover']],
      'attr breakout'     => ['<a href="http://x" onmouseover="alert(1)">y</a>', ['onmouseover', 'alert(1)']],
      'nested script'     => ['<p>ok<script>alert(1)</script></p>', ['<script', 'alert(1)']],
      'form'              => ['<form action="x"><input></form>', ['<form', '<input']],
      'html comment'      => ['<!-- <script>alert(1)</script> -->ok', ['<script', 'alert(1)']],
      'meta refresh'      => ['<meta http-equiv="refresh" content="0;url=javascript:alert(1)">', ['<meta', 'javascript']],
      'base tag'          => ['<base href="javascript:alert(1)//">', ['<base', 'javascript']],
    ];
  }

  public function testUnknownTagIsUnwrappedButTextKept(): void
  {
    // marquee is dangerous (removed with content)...
    self::assertStringNotContainsString('gone', sanitize_html('<marquee>gone</marquee>'));
    // ...but an unknown, non-dangerous tag keeps its text.
    $out = sanitize_html('<bogus>keepme</bogus>');
    self::assertStringContainsString('keepme', $out);
    self::assertStringNotContainsString('<bogus', $out);
  }

  public function testScriptContentIsRemovedNotEscaped(): void
  {
    // The script body must be GONE, not merely escaped (escaped script text in
    // a description is harmless but the body should not survive at all).
    $out = sanitize_html('<script>steal(document.cookie)</script>');
    self::assertStringNotContainsString('steal(document.cookie)', $out);
  }

  public function testEmptyAndNull(): void
  {
    self::assertSame('', sanitize_html(''));
    self::assertSame('', sanitize_html(null));
  }

  public function testNoExecutableAttributesSurviveAnywhere(): void
  {
    $out = sanitize_html(
      '<div><p onclick=x()><a href="javascript:y()" onmouseover=z()>'
      . '<img src=q onerror=w()></a></p></div>'
    );
    foreach (['onclick', 'onmouseover', 'onerror', 'javascript:'] as $bad) {
      self::assertStringNotContainsString($bad, $out);
    }
    // The benign structure should remain.
    self::assertStringContainsString('<div>', $out);
    self::assertStringContainsString('<img', $out);
  }
}
