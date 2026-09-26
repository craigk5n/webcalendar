<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Immutable result of `ManifestVerifier::verify()`.
 *
 * The feature spec (STATUS.md, Story 3.1) asks for a `final readonly
 * class`. That needs PHP 8.2, which was above the floor when this was
 * written, so it uses the equivalent: `final class` with `readonly` on
 * each promoted property. The floor moved to 8.2 on 2026-09-24 and this
 * was left alone — the two forms are semantically identical, since any
 * attempt to write to `$valid` or `$reason` after construction raises a
 * fatal Error either way.
 */
final class VerifyResult
{
  public function __construct(
    public readonly bool $valid,
    public readonly string $reason
  ) {}
}
