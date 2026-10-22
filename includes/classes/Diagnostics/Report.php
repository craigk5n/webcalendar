<?php

declare(strict_types=1);

namespace WebCalendar\Diagnostics;

/**
 * Immutable diagnostic report, rendered as JSON or as plain text.
 *
 * `schemaVersion` lets a reader tell which shape they are looking at, and
 * `generatedAt` how stale it is. There is deliberately no checksum: the
 * report is produced on the reporter's own machine, so any signing key
 * ships with the release and a digest could only ever detect accident, not
 * intent. JSON already detects a truncated paste by failing to parse, and a
 * checksum would reclassify legitimate redaction -- an admin removing a
 * hostname before posting -- as tampering. See AGENT_ROADMAP.md.
 */
final class Report
{
  public const SCHEMA_VERSION = 1;

  /**
   * @param array<string, array<string, string|int|bool|null>> $sections
   */
  public function __construct(
    public readonly array $sections,
    public readonly string $generatedAt
  ) {}

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return [
      'schema_version' => self::SCHEMA_VERSION,
      'generated_at' => $this->generatedAt,
      'sections' => $this->sections,
      // A truncated paste fails to parse long before this matters, but it
      // costs nothing and settles the question without a digest.
      'report_complete' => true,
    ];
  }

  public function toJson(): string
  {
    $json = json_encode(
      $this->toArray(),
      JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    return $json === false ? '{"error":"report could not be encoded"}' : $json;
  }

  /**
   * Plain text for pasting somewhere that mangles JSON, and for reading over
   * someone's shoulder.
   */
  public function toText(): string
  {
    $out = 'WebCalendar diagnostic report (schema ' . self::SCHEMA_VERSION
      . ', generated ' . $this->generatedAt . ')' . "\n";

    foreach ($this->sections as $name => $rows) {
      $out .= "\n[" . $name . "]\n";
      if ($rows === []) {
        $out .= "  (nothing to report)\n";
        continue;
      }
      $width = max(array_map('strlen', array_map('strval', array_keys($rows))));
      foreach ($rows as $label => $value) {
        $out .= '  ' . str_pad((string) $label, $width) . ' : '
          . self::scalarToText($value) . "\n";
      }
    }

    return $out;
  }

  private static function scalarToText(string|int|bool|null $value): string
  {
    if ($value === null) {
      return '(null)';
    }
    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    return (string) $value;
  }
}
