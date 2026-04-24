<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Immutable result of `ManifestVerifier::verify()`.
 *
 * The feature spec (STATUS.md, Story 3.1) asks for a `final readonly
 * class`, which requires PHP 8.2+. Our shipping floor is 8.1, so we
 * use the 8.1-compatible equivalent: `final class` with `readonly`
 * on each promoted property. Semantically identical — any attempt
 * to write to `$valid` or `$reason` after construction raises a
 * fatal Error, just like a 8.2 `readonly class` would.
 */
final class VerifyResult
{
  public function __construct(
    public readonly bool $valid,
    public readonly string $reason
  ) {}
}
