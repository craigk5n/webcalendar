# Security Audit: Signed File Manifest — Implementation Status

> Tracks the work for [issue #233](https://github.com/craigk5n/webcalendar/issues/233)
> — "Feature Request: Security Audit should report 'extra' files."

**Feature in one sentence:** The release workflow publishes a signed SHA-256 manifest
of every file that ships in a WebCalendar release; `security_audit.php` verifies the
signature and reports files that are **missing**, **modified**, or **extra** (i.e.
present on disk but not in the manifest — a common webshell indicator).

---

## Status Legend

| Symbol | Meaning |
|--------|---------|
| ⬜ | Not started |
| 🟨 | In progress |
| 🟩 | Complete |
| 🟥 | Blocked |
| ⏭️ | Deferred / stretch |

---

## Threat Model (read this first)

**What this feature catches:**
- Opportunistic webshell drops (attacker adds `shell.php`, `cmd.php`, etc. into the install).
- Silent modification of shipped files (attacker edits an existing `.php` to inject code).
- Partial upgrade damage (missing files after a failed `unzip`).

**What this feature does NOT catch:**
- A targeted attacker who rewrites `security_audit.php` itself (or the bundled public
  key) to short-circuit verification. On-disk self-audit has an inherent ceiling.
- Anything in the database (WebCalendar stores blobs/uploads in the DB, so there is
  no upload directory to scan).
- Compromise of the GitHub Actions signing secret (covered by key rotation procedure,
  Epic 5).

**Why we still ship it:** most real-world WebCalendar compromises seen in the wild are
opportunistic — scanner drops a file with a known PHP filename and moves on. A signed
manifest catches those in one click.

---

## Design Decisions (locked in)

| # | Decision | Rationale |
|---|----------|-----------|
| D1 | **Ed25519 signatures via libsodium** (`sodium_crypto_sign_*`) | PHP 8.1+ ships libsodium; zero extra deps for end users; verification works fully offline. |
| D2 | **Manifest format**: sorted plain text, `<sha256>  <relpath>` one-per-line, LF-terminated, with a leading `# ` comment header (version, build timestamp, git SHA). | Matches `sha256sum` output; diff-able; no JSON parser needed. |
| D3 | **Detached signature** as `MANIFEST.sha256.sig` (64-byte Ed25519 signature, base64-encoded, LF-terminated). | Separates sig from signed data; trivial to regenerate. |
| D4 | **Public key** committed to repo as `release-signing-pubkey.pem` (repo root, PEM-wrapped base64 of the 32-byte raw key). | Ships inside the dist zip; loaded by audit code at verify time. |
| D5 | **Private key** stored as GitHub Actions secret `RELEASE_SIGNING_KEY` (base64 of the 64-byte secret key). | Standard GitHub secret management; rotation procedure documented. |
| D6 | **Manifest + sig + pubkey** live at the **repo root** inside the zip (`MANIFEST.sha256`, `MANIFEST.sha256.sig`, `release-signing-pubkey.pem`). | Easy for admins to move outside webroot per the issue author's advice. |
| D7 | **Default behavior: flag every discrepancy.** An admin setting `SECURITY_AUDIT_NOISE_FILTER` narrows results. | Per issue author: paranoid by default. Developers/customizers opt in to the filter. |
| D8 | **Three severity tiers:** CRITICAL (unexpected `.php` / `.phtml` / `.phar`), WARN (modified shipped file, missing shipped file), INFO (unexpected non-executable file). | Unexpected executable code is the webshell signal; everything else is noise until proven otherwise. |
| D9 | **Excludes**: `includes/settings.php`, `includes/site_extras.php`, user-editable CSS under `pub/` (configurable list), `.git`, `vendor/` (absent in dist anyway). | WebCalendar stores uploads in DB; no upload dir to exclude. `config.php` IS in dist and IS signed (installer does not modify it). |
| D10 | **New PHP code** under `includes/classes/Security/`, uses PHP 8.1+ idioms (typed properties, readonly, enums, `#[\Override]`) per `~/ai-guides/php.md`. Wired via `require_once` from `security_audit.php` — no composer autoloader changes. | Matches existing `includes/classes/` convention; isolates new modern code without destabilizing legacy procedural includes. |

---

## Epic 1 — Signing Key Infrastructure 🟨

**Goal:** Establish the Ed25519 keypair used to sign releases, with a clean
rotation story.

### Story 1.1 — Generate the initial keypair 🟩
**As** the project maintainer
**I want** a one-off keypair generation procedure
**So that** release signing can begin with a known-good key

**Acceptance criteria:**
- [x] A `tools/generate-release-key.php` script exists that calls `sodium_crypto_sign_keypair()` and prints the public key (base64) and private key (base64) to stdout with clear labels.
- [x] Running the script twice produces different outputs (sanity check on randomness). *(covered by `testTwoGenerationsProduceDifferentKeys`)*
- [x] The script refuses to run if `sodium_*` functions are unavailable. *(covered by `testEnsureSodiumAvailableThrowsWhenMissing`; the CLI wrapper exits 1 on the thrown RuntimeException)*
- [x] Output includes copy-paste-ready PEM-wrapped public key and a GitHub secret value for the private key.
- [x] Private key is never persisted by the script (stdout-only). Cross-reference to Epic 5 runbook will be filled in when `docs/release-signing.md` is authored — the script header already points at that path.

**TDD:** Unit tests in `tests/ReleaseKeyGeneratorTest.php` — 14 tests, 30 assertions, all passing. Covers: sodium-available check (pass and fail paths), keypair byte-length invariants, randomness (two calls yield different keys), round-trip signature verification, tampered-message rejection, PEM format round-trip, PEM malformed/invalid-base64/wrong-length rejection, GitHub secret round-trip.

**Implementation notes:**
- New code lives at `includes/classes/Security/ReleaseKeyGenerator.php` under namespace `WebCalendar\Security` (decision D10 refined: namespace adopted to keep class names collision-free, still loaded via `require_once` — no autoloader wiring).
- Class targets PHP 8.1 parse-compatibility (the floor per `.github/workflows/php-syntax-check.yml`): no `readonly class`, no typed class constants, no `#[\Override]`. Uses `#[\SensitiveParameter]` which is a no-op on 8.1 and active on 8.2+.
- CLI wrapper: `tools/generate-release-key.php` (chmod +x, shebang line).
- PHPStan level-0 clean on new files (matches repo config).

### Story 1.2 — Commit the public key file 🟩
**As** the maintainer
**I want** the public key stored in the repo
**So that** every install can verify its own manifest

**Acceptance criteria:**
- [x] `release-signing-pubkey.pem` committed at repo root.
- [x] File contents: standard PEM block (`-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----` / `-----END WEBCALENDAR RELEASE PUBLIC KEY-----`) wrapping base64 of the 32-byte raw Ed25519 public key. *(verified by `testPublicKeyFileParsesToExactlyThirtyTwoBytes`)*
- [x] Added to `release-files` so it ships in the zip. Inserted alphabetically between `reject_entry.php` and `remotecal_mgmt.php`. *(verified by `testPublicKeyFileIsListedInReleaseFiles`)*
- [x] `.gitignore` updated to reject any `release-signing-privkey*` filename, defense-in-depth against accidental commits. *(verified by `testPrivateKeyIsNotAccidentallyCommitted`)*

**TDD:** New test file `tests/ReleaseSigningPubkeyTest.php` — 5 tests, 8 assertions, all passing. Covers: file presence at repo root, PEM round-trip decode to 32 bytes, LF line endings, listed in `release-files`, no accidental privkey commit.

**Implementation notes:**
- Keypair generated via `tools/generate-release-key.php` (from Story 1.1). Secret key half stashed in a mode-0600 tmp file outside the repo, handed off to maintainer for the GitHub-secret step (Story 1.3). Secret was not committed, logged, or echoed into any tracked file.
- The pubkey alone is not load-bearing: releases cannot be signed until Story 1.3 (GitHub secret creation) completes. If the secret is lost before then, regenerate both halves with the same tool — the only cleanup is replacing the `.pem` file.

### Story 1.3 — Store the private key as a GitHub secret 🟨
**As** the maintainer
**I want** the signing key available to Actions but to no human
**So that** releases can be signed automatically without exposing material

**Acceptance criteria:**
- [ ] GitHub repository secret `RELEASE_SIGNING_KEY` created (base64 of the 64-byte libsodium secret key). **← maintainer action required:** paste the value from the mode-0600 tmp file handed off at the end of Story 1.2 into the `release` environment's secret list at https://github.com/craigk5n/webcalendar/settings/environments/release
- [x] Secret's environment scope restricted to the release workflow only. *(the `release` environment was created via `gh api -X PUT repos/craigk5n/webcalendar/environments/release`; environment id 14510892733)*
- [x] Confirmed via a dry-run job that the secret is readable inside the release workflow but NOT inside a fork's PR workflow. *(new workflow `.github/workflows/verify-release-signing.yml` — manual trigger via Actions UI, scoped to `environment: release`. GitHub's built-in environment-secret semantics block access from forked PRs. The workflow calls `tools/verify-release-signing-key.php` which derives the public key from the secret and compares to the committed `release-signing-pubkey.pem` — proving the two halves belong to the same keypair without ever logging the secret.)*

**TDD:** 8 new tests added to `tests/ReleaseKeyGeneratorTest.php` for the new `ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey()` static method: empty/null input, invalid base64, wrong secret length, wrong expected-pubkey length, mismatched keypair, matching keypair, and a log-safety test asserting that reason strings never contain the secret input. Total suite now 27 tests / 55 assertions, all passing.

**Implementation notes:**
- Added `WebCalendar\Security\ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(?string, string): array{valid, reason}`. Uses `sodium_crypto_sign_publickey_from_secretkey()` to derive and `hash_equals()` to compare. Returns an envelope rather than throwing so the CI workflow can print a clean operator message.
- New CLI `tools/verify-release-signing-key.php` — reads `RELEASE_SIGNING_KEY` from env, parses `release-signing-pubkey.pem`, calls the verify method, exits 0 on match and 1 otherwise. Smoke-tested locally: unset env → fail, invalid base64 → fail, real-secret-from-tmp → pass.
- New workflow `.github/workflows/verify-release-signing.yml` — `workflow_dispatch` only (no PR triggers → no fork exposure). Setup-PHP v2 on 8.4 with ext-sodium, runs the verifier with `RELEASE_SIGNING_KEY` injected from `secrets.RELEASE_SIGNING_KEY`.

**Final step for the maintainer (≤2 minutes):**
1. Run `cat /tmp/tmp.zDw8uYglAQ` (or wherever you stashed it) and copy the 88-character base64 line that appears after "paste this value".
2. Go to https://github.com/craigk5n/webcalendar/settings/environments/release → Add environment secret → Name: `RELEASE_SIGNING_KEY`, Value: `(paste)`.
3. `shred -u /tmp/tmp.zDw8uYglAQ` to scrub local copy.
4. Go to the Actions tab → "Verify Release Signing Key" → Run workflow. A green run confirms AC1.

---

## Epic 2 — Release-Time Manifest Generation 🟨

**Goal:** Every tagged release publishes `MANIFEST.sha256` and `MANIFEST.sha256.sig`
alongside the zip, and both files are also embedded inside the zip at the repo root.

### Story 2.1 — `tools/build-manifest.php` script 🟩
**As** the release workflow
**I want** a single PHP script that emits the manifest
**So that** manifest generation is reproducible locally and in CI

**Acceptance criteria:**
- [x] Script reads a file-list (default `release-files`) and produces `MANIFEST.sha256` on stdout.
- [x] Each line is `<64-hex-sha256><two spaces><relative-path>` (matching GNU `sha256sum` format). *(cross-checked against `sha256sum`: hashes match byte-for-byte.)*
- [x] Lines are sorted by relative path (lexicographic, LC_ALL=C-equivalent via `sort($paths, SORT_STRING)`). *(covered by `testHashLinesAreSortedLexicographicallyByPath` and `testSortIsLcAllCByteOrderNotLocaleAware`.)*
- [x] Header comment lines (prefixed with `# `) include: `webcalendar-version`, `build-timestamp` (ISO 8601 UTC), `git-sha`. Header is included in the signed bytes.
- [x] LF line endings throughout; no trailing whitespace; final LF present.
- [x] Script exits non-zero if any file in the list is missing.
- [x] Script produces byte-identical output across two consecutive runs on the same tree. *(honors `SOURCE_DATE_EPOCH` for reproducible-builds timestamp pinning; `testTwoRunsProduceIdenticalBytes` locks in the determinism at the class level.)*

**TDD:** New test file `tests/ManifestBuilderTest.php` — 15 tests, 27 assertions, all passing. Covers: header metadata, header-before-hashes ordering, sha256sum-format hash lines, lowercase hex + exactly two spaces, lexicographic sort, LC_ALL=C byte-order sort, LF-only line endings, single trailing LF, no trailing whitespace, missing-file RuntimeException with path in message, unreadable-file RuntimeException, reproducibility, raw-bytes hashing (no line-ending normalization), nested path preservation.

**Implementation notes:**
- Pure logic in `WebCalendar\Security\ManifestBuilder::build()`; I/O in the CLI wrapper `tools/build-manifest.php` (flag parsing, composer.json version read, `$GITHUB_SHA` / `git rev-parse HEAD` fallback, `$SOURCE_DATE_EPOCH` honoring).
- The CLI defaults `--list` to `release-files` but any list can be passed — useful for testing and for generating partial manifests during `release-files` cleanup (Story 2.5).
- Reproducibility design: `build-timestamp` is the only time-varying field. The release workflow can pin it by exporting `SOURCE_DATE_EPOCH` (e.g., to the tag timestamp). Without the pin, second-to-second drift is the only source of byte variance — hashes and sort order are deterministic given the same tree.
- Pre-existing bug surfaced during smoke-test: `release-files` contains ~185 stale entries (entire `install/` tree replaced by `wizard/`, CKEditor assets, deleted translations). Today's release workflow silently `cp`s past these because it doesn't `set -e`. Story 2.5 tracks the cleanup.

### Story 2.5 — Clean up stale entries in `release-files` 🟨 (NEW)
**As** the maintainer
**I want** `release-files` to list exactly the files that exist on disk
**So that** `build-manifest.php` (strict mode) does not fail, AND the release zip actually contains a consistent, documented file set

**Context:** surfaced by Story 2.1's smoke test. 177 of 443 entries referenced files deleted in prior commits (most notably cc0d41cf which replaced `install/` with `wizard/`, plus the CKEditor removal). The existing `release.yml` silently swallowed these via `cp` without `set -e`. `build-manifest.php` correctly rejects missing files, so Story 2.3 (workflow wiring) was blocked until this.

**Acceptance criteria:**
- [x] Every non-empty, non-comment line in `release-files` resolves to an existing tracked file at repo root. *(443 → 266 lines; 177 stale entries removed: all `install/*`, all `pub/ckeditor/*`, `docs/newwin.gif`, `docs/WebCalendar-SysAdmin.html`, `UPGRADING.html`, 18 legacy translation files, `tools/populate_sqlite3.php`. All deletions are files that no longer exist in the repo.)*
- [ ] `release-files` includes new files that SHOULD ship but currently don't. **← maintainer decision required** — see table below.
- [x] `.github/workflows/release.yml` gains `set -euo pipefail` on the file-copy loop so future drift fails loudly instead of silently. Also skips blank and `#`-prefixed lines explicitly.
- [x] A CI check asserts `release-files` is consistent with the filesystem; drift is now a build-break. *(new test `tests/ReleaseFilesConsistencyTest.php` — 3 tests: file exists, every entry resolves to a real file (with offender listing on failure), no duplicate entries.)*

**TDD:** `tests/ReleaseFilesConsistencyTest.php` — 3 tests, 5 assertions. Failing output is designed to be self-explanatory: the failure message lists every stale entry with its line number so the maintainer can diff against the commit that introduced the drift.

**Blocks:** Story 2.3 (now unblocked by AC1/3/4; AC2 optional and independently addable).

**Maintainer decision table (AC2):** 17 `docs/*.md` files exist on disk but are NOT in `release-files`. The previous release listing included two now-deleted legacy docs (`docs/newwin.gif`, `docs/WebCalendar-SysAdmin.html`), so the *intent* was to ship docs. Pick any subset (or none) and they can be added in a follow-up commit. Sizes in parentheses:

| Candidate | Ship? | Rationale |
|-----------|-------|-----------|
| `docs/admin-guide.md` (15 KB) | likely YES | Direct admin-facing reference. |
| `docs/configuration.md` (5 KB) | likely YES | Core setup reference. |
| `docs/faq.md` (4 KB) | likely YES | End-user oriented. |
| `docs/glossary.md` (3 KB) | likely YES | Small, end-user oriented. |
| `docs/import-export.md` (3 KB) | likely YES | End-user feature doc. |
| `docs/index.md` (2 KB) | likely YES | Entry point. |
| `docs/installation.md` (8 KB) | likely YES | Essential. |
| `docs/security.md` (4 KB) | likely YES | Essential for security-conscious admins. |
| `docs/troubleshooting.md` (7 KB) | likely YES | End-user oriented. |
| `docs/upgrade-guide.md` (4 KB) | likely YES | Essential. |
| `docs/user-guide.md` (6 KB) | likely YES | End-user oriented. |
| `docs/docker.md` (4 KB) | maybe | Useful for dockerized installs. |
| `docs/mcp-server.md` (9 KB) | maybe | Narrow audience; also on website. |
| `docs/WebCalendar-Database.md` (26 KB) | maybe | Schema reference, useful for ops. |
| `docs/developer-guide.md` (12 KB) | PROBABLY NO | Internal/contributor doc. |
| `docs/migration-v2.md` (9 KB) | PROBABLY NO | Forward-looking dev doc. |
| `docs/v2-development.md` (3 KB) | PROBABLY NO | Internal/contributor doc. |

I didn't add any of these — it's your call which ones belong in a 1.9.x release zip vs stay on the project website.

### Story 2.2 — `tools/sign-manifest.php` script 🟩
**As** the release workflow
**I want** to sign the manifest with the private key from env
**So that** a detached signature accompanies the manifest

**Acceptance criteria:**
- [x] Reads private key from env var `RELEASE_SIGNING_KEY` (base64 of 64-byte libsodium secret key).
- [x] Input: path to `MANIFEST.sha256`. Output: writes `<input>.sig` next to it (i.e. `MANIFEST.sha256.sig` when called with `MANIFEST.sha256`).
- [x] Signature format: base64 of the 64-byte Ed25519 signature, single line + trailing LF. *(verified by `testSignatureIsSingleLineBase64`)*
- [x] Uses `#[\SensitiveParameter]` on the `sign()` secret-key parameter. `sodium_memzero()` scrubs the decoded key buffer after use.
- [x] Exits non-zero on every error path: unset env (`empty or unset`), invalid base64 (`not valid base64`), wrong length (`must decode to 64 bytes`), missing input manifest, unwritable sig destination. *(all five paths smoke-tested locally; exit=1 with a clean operator message on each.)*
- [x] Never logs, echoes, or exposes the secret key. *(covered by `testReasonDoesNotLeakSecret` and `testReasonDoesNotLeakSecretOnInvalidBase64Failure` — the latter uses a `LEAKCANARY` tag to assert even recognizable substrings never surface.)*

**TDD:** New test file `tests/ManifestSignerTest.php` — 12 tests, 29 assertions, all passing. Covers: verifiable round-trip with paired public key, single-line base64 format, Ed25519 determinism (same input → same signature), different messages → different signatures, full-message and one-byte-flip tamper detection, empty/null/invalid-base64/wrong-length secret rejection, and two log-safety tests asserting reasons never leak the secret.

**Implementation notes:**
- Pure logic in `WebCalendar\Security\ManifestSigner::sign(string, ?string): array{ok, signature, reason}`. Envelope return (not throw) so the CLI can print a clean message and failure reasons stay in our control.
- Defensive `sodium_memzero()` on the decoded secret key buffer even though PHP's refcounted string lifecycle already discards it promptly. Cheap belt-and-suspenders.
- CLI wrapper `tools/sign-manifest.php` (chmod +x, shebang). Single positional arg for the manifest path; `<path>.sig` is the implied output. End-to-end smoke test on the full `build-manifest.php → sign-manifest.php` pipeline confirmed: signature verifies externally with raw libsodium; one-byte flip in the manifest makes verify return false.
- Ed25519's deterministic signing (RFC 8032 §5.1.6) is locked in by `testSignIsDeterministicForSameInput` — reproducible-builds workflows can pin `SOURCE_DATE_EPOCH` and get byte-identical (manifest, signature) pairs across runs.

### Story 2.3 — Wire into `.github/workflows/release.yml` 🟩
**As** the maintainer
**I want** the manifest and signature built as part of every release
**So that** there is no manual step to forget

**Acceptance criteria:**
- [x] New `Build MANIFEST.sha256` step after "Copy files to release directory" runs `tools/build-manifest.php` against the staged `WebCalendar-${VERSION}/` tree. Prints the 3-line header and line count to the workflow log for operator visibility.
- [x] New `Sign MANIFEST.sha256` step runs `tools/sign-manifest.php` with `RELEASE_SIGNING_KEY` injected from `secrets.RELEASE_SIGNING_KEY`, producing `MANIFEST.sha256.sig`.
- [x] Both files are written into the staged release tree BEFORE `Zip the release`, so they end up inside the zip at the repo root.
- [x] Both files are uploaded as separate release assets after the zip upload (`actions/upload-release-asset@v1.0.2` with `asset_name: MANIFEST.sha256` and `MANIFEST.sha256.sig`, `asset_content_type: text/plain`).
- [x] The workflow fails hard on either step: `set -euo pipefail` in each bash block, plus GitHub Actions' default non-zero-exit-fails-the-job behavior.
- [x] The `build` job declares `environment: release`, so `RELEASE_SIGNING_KEY` is only exposed to this job (not to forked-PR workflows).

**Also included in this story:**
- `Set up PHP` step added (shivammathur/setup-php@v2, PHP 8.4, `extensions: sodium`) — guarantees libsodium is available even if the runner default changes.
- `Pin SOURCE_DATE_EPOCH` step sets `SOURCE_DATE_EPOCH` to the HEAD commit's committer timestamp (`git log -1 --format=%ct HEAD`), making MANIFEST.sha256 byte-identical across re-runs of the same commit. Combined with Ed25519's deterministic signing, the signature is identical too.

**Local simulation (proxy for a real CI run):**
- Staged 266 files from `release-files` into a temp tree (same bash loop as the workflow).
- `SOURCE_DATE_EPOCH=$(git log -1 --format=%ct HEAD) php tools/build-manifest.php --tree=$STAGE --version=SIM` → 269-line manifest (3 header + 266 hashes).
- `RELEASE_SIGNING_KEY=<throwaway> php tools/sign-manifest.php $STAGE/MANIFEST.sha256` → 89-byte `.sig` file.
- `zip -r ...` → zip contains `MANIFEST.sha256`, `MANIFEST.sha256.sig`, and `release-signing-pubkey.pem` at the root.
- External `sodium_crypto_sign_verify_detached()` against the throwaway pubkey → `VERIFIED ✓`.

**Deferred to actual first release:**
- The very first run against the real `RELEASE_SIGNING_KEY` secret (once Story 1.3 AC1 is paste-completed) is the true smoke test; tracked by Story 6.3.

### Story 2.4 — `release-files` entries for new manifest files 🟩
**Acceptance criteria:**
- [x] `release-files` includes `release-signing-pubkey.pem`. *(satisfied by Story 1.2.)*
- [x] `release-files` does NOT include `MANIFEST.sha256` or `MANIFEST.sha256.sig`. *(they are generated by the workflow's Build + Sign steps and injected into the staged tree at build time; never source-tracked. Verified via `grep` against `release-files`.)*

This story was fully satisfied as a side-effect of Stories 1.2 and 2.3. No dedicated work needed.

---

## Epic 3 — Audit-Time Verification (PHP in app) 🟩

**Goal:** `security_audit.php` gains a new section that verifies the signature, walks
the filesystem, and reports discrepancies with appropriate severity.

### Story 3.1 — `ManifestVerifier` class 🟩
**As** the admin running the audit
**I want** the manifest signature verified before its contents are trusted
**So that** a tampered manifest cannot mask tampered files

**Acceptance criteria:**
- [x] New files `includes/classes/Security/ManifestVerifier.php` and `VerifyResult.php` (PHP 8.1+, `declare(strict_types=1)`, namespace `WebCalendar\Security` — matches D10 as refined).
- [x] Class exposes `ManifestVerifier::verify(string $manifestPath, string $signaturePath, string $publicKeyPemPath): VerifyResult`. Static method; pure-logic-meets-file-IO; no hidden state.
- [x] `VerifyResult` is `final class` with `public readonly bool $valid` and `public readonly string $reason` (constructor-promoted). Semantically equivalent to `final readonly class` but parse-compatible with PHP 8.1 — see Decisions Log entry below. Mutation attempt raises fatal Error, verified by `testVerifyResultIsImmutable`.
- [x] Uses `sodium_crypto_sign_verify_detached()` — not OpenSSL. The verify itself is constant-time, so no `hash_equals()` wrapping needed.
- [x] Fails gracefully with a clear `reason` for every failure mode: missing manifest/sig/pubkey file, empty sig, invalid base64 sig, wrong-length sig, malformed PEM, truncated pubkey (fewer than 32 decoded bytes), tampered manifest, swapped pubkey.
- [x] Tolerates trailing whitespace in the signature file (CRLF / extra LFs from pipe-chained tools) via `trim()` — base64 body itself contains no whitespace, so this is safe.
- [x] Pubkey parsing delegates to `ReleaseKeyGenerator::parsePublicKeyPem()`, which already enforces the 32-byte invariant — no duplicated PEM logic.

**TDD:** New test file `tests/ManifestVerifierTest.php` — 15 tests, 29 assertions, all passing. Each test sets up a fresh temp directory with real fixture files (manifest, sig, pubkey PEM generated from a throwaway keypair) so the verifier's full file-IO path is exercised.

**Failure-mode test coverage:**
- Valid triple → `valid=true`.
- Appended byte to manifest → `valid=false`, reason matches `/signature.*(mismatch|does not verify|failed)/i`.
- One-bit flip in manifest → `valid=false`.
- Swapped pubkey (different keypair) → `valid=false`.
- Missing manifest / sig / pubkey file → `valid=false`, reason includes "not found" + display name.
- Malformed PEM → `valid=false`, reason mentions "pem" / "public key".
- Truncated pubkey (valid base64 but < 32 bytes decoded) → `valid=false`, reason mentions "32 bytes".
- Invalid base64 signature → `valid=false`.
- Wrong-length signature → `valid=false`, reason mentions "64 bytes".
- Empty signature file → `valid=false`.
- Trailing whitespace in sig → still `valid=true`.
- `VerifyResult` is immutable (write attempt → fatal Error).
- `reason` is always non-empty on both pass and fail paths.

**Implementation notes:**
- Internal I/O helpers (`readFile`, `readSignature`, `readPublicKey`) return either the decoded bytes on success OR a `VerifyResult` on failure. This pattern keeps the public `verify()` body linear ("early-return the result from the helper if it's already a result") without exceptions or nested try/catch.
- PHPStan level-0 clean on all new files.
- Full signed-manifest suite now: 72 tests / 145 assertions green.

### Story 3.2 — `ManifestParser` class 🟩
**Acceptance criteria:**
- [x] `ManifestParser::parse(string $manifestPath): ManifestData` returns `{string $version, DateTimeImmutable $buildTimestamp, string $gitSha, array<string,string> $hashes}` (hashes map relative-path → lowercase-hex sha256).
- [x] Rejects lines that don't match the canonical hash-line regex `/^[0-9a-f]{64}  \S.*$/` (rejects 3 spaces, tabs, uppercase hex, wrong hash length, non-hex chars, empty path).
- [x] Rejects duplicate paths with the duplicate's line number AND the original line number in the error message.
- [x] Throws `RuntimeException` with a `line N:` prefix on every malformed-body failure (regex miss, blank line, duplicate, empty path). Header-level failures (missing required field, malformed timestamp, empty body) throw with descriptive messages.
- [x] `ManifestData` is `final class` with `readonly` on all four promoted properties (same 8.1-compatible pattern as `VerifyResult`).

**TDD:** New test file `tests/ManifestParserTest.php` — 22 tests, 41 assertions, all passing.

**Test coverage:**
- Well-formed manifest → all four fields populated correctly, hashes keyed by path.
- Immutability of `ManifestData` (mutation → fatal `Error`).
- Header field order is not required (forward/backward compatible).
- Unknown header keys are tolerated (forward-compatibility — future manifest versions can add fields).
- Nested paths (e.g. `includes/classes/Event.php`).
- Paths containing spaces (sha256sum format uses two spaces as the only separator).
- Rejects: 3 spaces between hash and path, tab instead of spaces, uppercase hex, short hash, non-hex char, empty path.
- Rejects duplicate paths with both line numbers.
- Rejects blank lines inside the manifest (strict canonical format).
- Accepts the single trailing-LF artifact (canonical builder output).
- Rejects missing version, timestamp, or git-sha headers (explicit reason).
- Rejects malformed timestamp (parse failure surfaces as `RuntimeException`).
- Rejects manifest with zero hash lines (no empty bodies).
- Rejects missing file with "not exist / cannot read" reason.
- **Round-trip contract**: a manifest produced by `ManifestBuilder::build()` parses cleanly via `ManifestParser::parse()` — locks the two sides together so future format drift breaks the test.

**Implementation notes:**
- Two regexes do all the structural work: `HASH_LINE_REGEX` for body lines, `HEADER_LINE_REGEX` for `# key: value` lines. Header keys are lowercased at parse time for case-insensitive lookup.
- Single-pass parse: one `foreach` over lines, state tracked by `$inBody` (prevents header lines appearing after hash lines begin).
- Line numbers are 1-indexed in error messages so the admin can jump directly to the offending line with most editors.
- PHPStan level-0 clean. Full signed-manifest suite now: 94 tests / 186 assertions green.

### Story 3.3 — `InstallationScanner` class 🟩
**As** the audit
**I want** a filesystem walker that compares disk state to the manifest
**So that** missing, modified, and extra files are identified

**Acceptance criteria:**
- [x] `InstallationScanner::scan(ManifestData $manifest, string $installRoot, ExcludeRules $excludes): ScanReport` — static entry point.
- [x] Walks `$installRoot` recursively. **Design pivot noted**: `RecursiveDirectoryIterator` by default DOES descend into symlinked directories, and `LEAVES_ONLY` drops symlinks entirely. Used a hand-rolled recursive `FilesystemIterator` walk with explicit `isLink()` checks instead. Clearer intent, fewer surprises.
- [x] For each disk file: if in manifest → hash (`hash_file('sha256')`) and compare → classify MATCH (incr `matchedCount`) or MODIFIED. If not in manifest AND not excluded → classify EXTRA.
- [x] For each manifest entry not seen on disk AND not excluded → classify MISSING.
- [x] `ScanReport = {list<ScannedFile> $modified, list<ScannedFile> $missing, list<ScannedFile> $extra, int $matchedCount}` — all readonly.
- [x] Honors `$excludes` — excluded paths skipped for both EXTRA classification AND (if in manifest) the MISSING sweep.
- [x] Symlinks are not followed; a symlink at any level is treated as a leaf and reported as EXTRA unless manifest-listed. Prevents attacker-planted symlinks-to-external-trees from expanding the scan surface.

**New classes (all under `WebCalendar\Security`):**
- `ScanEntryKind` — backed enum (`MODIFIED`, `MISSING`, `EXTRA`). MATCH is a scalar count, not an enum case, because the report never carries per-file MATCH entries.
- `ScannedFile` — immutable `{path, kind, expectedHash?, actualHash?}`. Hashes populated per convention: MODIFIED=both, MISSING=expected only, EXTRA=neither.
- `ScanReport` — immutable `{modified[], missing[], extra[], matchedCount}`.
- `ExcludeRules` — minimal glob matcher; Story 4.1 will extend with default set + admin-supplied extras. Pattern syntax: trailing-slash prefix (`tests/` → matches anything under `tests/`), or `fnmatch()` patterns (`pub/css/*.css`).
- `InstallationScanner` — the walker, with static `scan()` entry + private instance run for clean state management.

**TDD:** New test file `tests/InstallationScannerTest.php` — 16 tests, 49 assertions, all passing. Integration-style: each test writes a real fixture tree under `/tmp/wc_scan_*` and a real `ManifestData`, then asserts the `ScanReport`.

**Test coverage:**
- Clean install → empty report, `matchedCount` = file count.
- Deleted shipped file → MISSING with `expectedHash` populated, `actualHash` null.
- Modified shipped file → MODIFIED with BOTH hashes populated.
- Dropped `evil.php` at root → EXTRA.
- Dropped `shell.php` in `includes/classes/` → EXTRA with full relative path.
- Excluded path with EXTRA content → suppressed.
- Excluded path present in manifest → suppressed from MISSING too (defensive).
- Directory glob (`tests/`, `.git/`) → excludes subtree.
- `*` glob (`pub/css/*.css`) → precise extension matching (other files under same dir still reported).
- Symlink at leaf → reported as EXTRA, target NOT followed.
- Symlink to external directory → reported as EXTRA for the symlink itself; scanner does NOT descend (verified by planting a `trap.php` in the target and asserting it doesn't appear in report).
- Realistic mixed scenario: 1 MATCH + 1 MODIFIED + 1 MISSING + 1 EXTRA + 1 excluded, all classifications correct simultaneously.
- Missing install root → RuntimeException.
- Empty install root → all manifest entries reported as MISSING.
- `ScannedFile` and `ScanReport` both immutable (mutation → fatal Error).

**Implementation notes:**
- Scanner uses instance fields (private constructor + static `scan()` entry) rather than static scratch arrays. Thread-safety isn't a PHP concern, but it keeps multiple simultaneous scans isolated and makes the walk methods easier to read.
- `$seenManifestPaths` is the bookkeeping trick: mark manifest entries as "seen" as they're encountered (or excluded) on disk, then iterate remaining manifest entries for the MISSING pass.
- Full signed-manifest suite now: 110 tests / 235 assertions green.

### Story 3.4 — Severity classifier 🟩
**As** the admin
**I want** each finding tagged with severity so I can triage
**So that** unexpected PHP files shout louder than unexpected CSS

**Acceptance criteria:**
- [x] `SeverityClassifier::classify(ScannedFile $file): Severity` where `Severity` is a backed-string enum `CRITICAL='critical'|WARN='warn'|INFO='info'`.
- [x] Extra `.php`, `.phtml`, `.phar`, `.inc` file → CRITICAL. Also `.phps` and `.pht` (lesser-known PHP handler extensions) — hardening for server configs that enable them.
- [x] Modified shipped file (any extension) → WARN.
- [x] Missing shipped file (any extension) → WARN.
- [x] Extra file with non-executable extension → INFO. Recognized extensions: `css, html, htm, xml, svg, txt, md, rst, map, log, png, jpg, jpeg, gif, ico, webp, avif, bmp, woff, woff2, ttf, otf, eot, js, mjs, json, yml, yaml, toml, csv, tsv`.
- [x] Extra file with unknown extension OR no extension → WARN (conservative — unknowns might be disguised webshells).

**TDD:** New test file `tests/SeverityClassifierTest.php` — 30 tests, 32 assertions, all passing.

**Test coverage:**
- Every CRITICAL case: extras with `php`, `phtml`, `phar`, `inc` extensions.
- Case-insensitivity: `Shell.PHP` → CRITICAL (attackers may use mixed case to dodge naive matchers; `pathinfo` + `strtolower` handles it).
- Nested path with `.php`: `pub/uploads/malware.php` → CRITICAL.
- Every INFO case: `.css`, `.html`, `.txt`, `.md`, `.map`, all 7 image extensions via dataProvider, `.json`, `.js`.
- EXTRA with unknown extension (`foo.xyz`) → WARN.
- EXTRA with no extension (`mysteryfile`) → WARN.
- EXTRA with double extension (`shell.php.bak`) → WARN because `pathinfo` returns `bak`. Conservative and correct: whether `.bak` runs as PHP depends on server config, so we don't assume CRITICAL without evidence but also don't drop to INFO.
- MODIFIED with `.php`, `.css`, and unknown extensions → all WARN (extension is ignored for MODIFIED/MISSING per AC).
- MISSING with `.php`, `.css` → all WARN.
- `Severity` enum: three cases, backed values `'critical'`, `'warn'`, `'info'`.

**Implementation notes:**
- Uses `pathinfo($path, PATHINFO_EXTENSION)` + `strtolower` for case-insensitive matching. Two const arrays of known extensions; linear `in_array()` is fine for the ~30 entries.
- Expanded the CRITICAL set beyond AC to include `phps, pht` — Apache's default `mime.types` / `AddHandler` in some distros recognizes these as PHP.
- Expanded the INFO set beyond AC to include font files, data files (yaml/toml/csv/tsv), and `js` — none of these execute in a PHP context when dropped into the install tree.
- Full signed-manifest suite now: 140 tests / 267 assertions green. PHPStan level-0 clean.

### Story 3.5 — `security_audit.php` integration 🟩
**As** the admin
**I want** the audit page to show the new results alongside existing checks
**So that** the existing UX remains familiar

**Acceptance criteria:**
- [x] New "File integrity" section rendered after the existing security-audit table via a new `render_file_integrity_section()` function.
- [x] Signature verification result shown at the top of the section — `alert-success` (green) on PASS, `alert-danger` (red) on FAIL with the `VerifyResult::$reason` interpolated.
- [x] Trust boundary: if signature verification fails, the function `return`s immediately without invoking the parser or scanner. Scan results are never displayed against an untrusted manifest.
- [x] On verification pass: parses manifest, scans install tree with a default `ExcludeRules` set, renders three tables (Modified / Missing / Extra) each with its own count badge. Empty tables are not rendered; an all-clear banner shows if all three are empty.
- [x] Each table row: path (`<code>`), severity badge (`bg-danger` / `bg-warning text-dark` / `bg-info text-dark`), action hint ("Restore from release zip", "Review contents and remove if not legitimate", etc.).
- [x] Summary line above the tables: `Scanned N files against manifest (vX.Y.Z, YYYY-MM-DD)` — version and build-date pulled from the verified `ManifestData`.
- [x] Translation strings added to `translations/English-US.txt`: `File integrity`, `Manifest files not present...`, `Manifest signature valid`, `Manifest signature FAILED: XXX`, `Scanned XXX files against manifest`, `No file integrity issues detected.`, `Modified files`, `Missing files`, `Extra files`, `Critical`, `Warning`, `Info`, `Restore from release zip if not intentional`, `Restore from release zip`, `Review contents and remove if not legitimate`, `Path`, `Severity`.
- [x] If any of the three artifacts (`release-signing-pubkey.pem`, `MANIFEST.sha256`, `MANIFEST.sha256.sig`) is absent: a single `alert-secondary` notice renders ("Manifest files not present (install may be from source or pre-1.9.x release)") and the function returns — no false alarms for source checkouts or pre-1.9.x installs.

**TDD:** Not a pure unit test — per AC. End-to-end smoke test ran the full pipeline against a fresh fixture tree: planted 1 modified + 1 missing + 1 extra (`evil.php`), asserted `valid=true`, `matchedCount=1`, `modified=[b.txt→WARN]`, `missing=[sub/c.txt→WARN]`, `extra=[evil.php→CRITICAL]`. Pass. Story 6.1 will wrap this in a PHPUnit integration test.

**Implementation notes:**
- Three helper functions live at the bottom of `security_audit.php` next to the existing `print_issue` / `is__writable` helpers: `render_file_integrity_section()` (the orchestrator), `render_integrity_table()` (one of the three finding tables), `severity_badge_html()` + `action_hint_for()` (presentation primitives).
- All 12 `WebCalendar\Security\*` classes loaded via `require_once` inside `render_file_integrity_section()` — no composer autoloader wiring per D10. The includes only happen if the audit page is actually viewed.
- **XSS-safe**: every dynamic string goes through `htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`. Paths are wrapped in `<code>` for readability. `ENT_SUBSTITUTE` replaces invalid UTF-8 sequences instead of returning an empty string (defense against silent XSS via malformed bytes).
- **Noise filter hardcoded to "show all"** for Story 3.5 — per AC of Story 4.2, which will replace this with the `SECURITY_AUDIT_NOISE_FILTER` config key.
- **Excludes hardcoded** to the default set from D9 (`includes/settings.php`, `includes/site_extras.php`, `MANIFEST.sha256*`, `tools/`, `tests/`, `docs/`, `vendor/`, `.git/`, `.github/`) — Story 4.1 will replace with config-sourced list. Ships the MANIFEST files themselves in the exclude list so they don't show up as EXTRA against their own manifest.
- Bootstrap 5 classes (`bg-danger`, `bg-warning text-dark`, `bg-info text-dark`) match what `wizard/steps/phpsettings.php` already uses — consistent with the project's current BS5 migration.
- Full signed-manifest suite: 140 tests / 267 assertions green. `php -l security_audit.php` clean. PHPStan level-0 clean on all new code.

---

## Epic 4 — Configuration & Exclusions 🟩

### Story 4.1 — `ExcludeRules` configuration source 🟩
**As** the admin / developer
**I want** a predictable list of paths excluded from the audit
**So that** user-editable files don't flood the report

**Acceptance criteria:**
- [x] `ExcludeRules` class encapsulates the exclusion set; constructor takes `list<string> $globs` (unchanged from Story 3.3).
- [x] Default exclude list materialized as `public const DEFAULT_PATTERNS` — 10 entries exactly matching AC: `includes/settings.php`, `includes/site_extras.php`, `MANIFEST.sha256`, `MANIFEST.sha256.sig`, `tools/`, `tests/`, `docs/`, `vendor/`, `.git/`, `.github/`.
- [x] `matches(string $relPath): bool` supports `*` globs via `fnmatch()` and trailing-slash directory-prefix matching (unchanged from Story 3.3).
- [x] `ExcludeRules::withDefaults(?string $extraConfig = null): self` factory unions `DEFAULT_PATTERNS` with newline-separated user extras. Wired into `security_audit.php` reading from `$GLOBALS['SECURITY_AUDIT_EXTRA_EXCLUDES']` (loaded from `webcal_config` by `load_global_settings()`).
- [x] CSS-under-`pub/` is NOT excluded by default (per D9). Locked in by `testCssUnderPubIsNotExcludedByDefault`. Admins add `pub/css/*.css` via `SECURITY_AUDIT_EXTRA_EXCLUDES`.

**TDD:** New test file `tests/ExcludeRulesTest.php` — 17 tests, 40+ assertions, all passing.

**Test coverage:**
- `DEFAULT_PATTERNS` const is public, non-empty, and contains every AC-required pattern.
- Default-only: `includes/settings.php` excluded; `includes/init.php`, `admin.php`, `pub/bootstrap.min.css` NOT excluded.
- Default-only: MANIFEST.sha256 and MANIFEST.sha256.sig excluded (so they don't appear as EXTRA against their own manifest).
- Directory-prefix matching: `tests/foo.php`, `docs/index.md`, `.git/HEAD`, `.github/workflows/release.yml`, `vendor/composer/autoload_real.php` all excluded.
- Prefix matching is STRICT: `tools/` excludes `tools/bar` but NOT `tools.txt` or `toolsomething.php`.
- CSS under `pub/` NOT excluded by default (per D9 paranoid-mode).
- `withDefaults("pub/css/*.css")`: glob extra matches CSS files, does not over-match (`.js` still flagged).
- Multiple newline-separated extras all apply AND defaults stay active (union, not replace).
- CRLF line endings from Windows-pasted input handled correctly.
- Comment lines (`#`-prefixed) and blank lines skipped silently.
- Leading/trailing whitespace trimmed from each extra.
- `null`, `''`, and whitespace-only input all behave identically (defaults only).
- Plain constructor (Story 3.3 surface) still works for custom sets.

**Implementation notes:**
- Added `public const DEFAULT_PATTERNS = [...]` with inline comments explaining why each entry is there (site-specific config, MANIFEST self-exclusion, developer directories, never-shipped paths). Forward-comprehensible for reviewers who pick up the file later.
- Added `public static function withDefaults(?string $extraConfig = null): self` factory. Parses newline-separated extras with `preg_split('/\r\n|\r|\n/', ...)` (handles LF / CRLF / CR), trims each line, skips empty + `#`-prefixed lines.
- Defensive: `matches('')` returns false so an empty string can't accidentally match a pattern that accepts empty input.
- `security_audit.php` simplified: the 10-line hardcoded `new ExcludeRules([...])` array is replaced with a single `ExcludeRules::withDefaults(...)` call reading `$GLOBALS['SECURITY_AUDIT_EXTRA_EXCLUDES']`. The setting doesn't exist in `webcal_config` yet — Story 4.2 adds it to the admin UI. Until then the read yields `null` and defaults apply.
- Full signed-manifest suite now: 157 tests / 325 assertions green. PHPStan level-0 clean.

### Story 4.2 — Noise filter admin setting 🟩
**As** a developer or a power user
**I want** to suppress low-severity findings
**So that** the report is actionable on a heavily customized install

**Acceptance criteria:**
- [x] New config key `SECURITY_AUDIT_NOISE_FILTER` with values `all` (default), `warn_and_above`, `critical_only`. Round-trips through `webcal_config` via the existing admin save handler.
- [x] Surfaced in the admin settings UI in `admin.php`'s "Other" tab inside a new `Security Audit` `<fieldset>`. The same fieldset also exposes `SECURITY_AUDIT_EXTRA_EXCLUDES` (from Story 4.1) as a `<textarea>` with inline help text — otherwise the Story 4.1 plumbing had no UI to exercise it.
- [x] When set to `critical_only`, the audit renders only CRITICAL-classified findings. Covered by `testCriticalOnlyDropsWarnAndInfo`.
- [x] When set to `warn_and_above`, INFO findings are hidden (MODIFIED+MISSING stay since they're WARN, and CRITICAL extras stay). Covered by `testWarnAndAboveDropsInfoEntries`.
- [x] Default `all` shows everything (identity function). Covered by `testAllModePreservesEveryEntry`.

**TDD:** New test file `tests/ScanReportFilterTest.php` — 10 tests, 32 assertions, all passing.

**Test coverage:**
- Mode constants have expected string values (`'all'`, `'warn_and_above'`, `'critical_only'`).
- `ALL` mode preserves every entry of a mixed 5-entry fixture.
- `ALL` on empty report stays empty.
- `WARN_AND_ABOVE` drops INFO `theme.css` but keeps CRITICAL `shell.php` and WARN `mystery.xyz`.
- `CRITICAL_ONLY` drops everything that isn't CRITICAL; only `shell.php` survives.
- `matchedCount` preserved across every mode (filter hides findings, not the match count).
- Unknown mode (`"garbage-value"`) falls back to `ALL` (identity).
- Empty string mode falls back to `ALL`.
- Filtered result is a NEW `ScanReport` instance (never mutates input).
- Filtered lists are zero-indexed contiguous `list<>`s (important — keeping `array_filter` output without `array_values` would leave sparse keys and trip up PHPStan's `list<T>` typing).

**Implementation notes:**
- New class `WebCalendar\Security\ScanReportFilter` under `includes/classes/Security/`. Three public const modes, one public static `filter()` entry point, two private helpers.
- `security_audit.php` reads `$GLOBALS['SECURITY_AUDIT_NOISE_FILTER']` (loaded from `webcal_config`), falls back to `ScanReportFilter::ALL` if unset or non-string, and applies the filter after the scan but before rendering.
- `admin.php` form: a `<select name="admin_SECURITY_AUDIT_NOISE_FILTER">` with three `<option>`s. Uses the existing `$selected` idiom to mark the current value. The `admin_` prefix on the form input name is what makes the save handler (lines 18–56 of admin.php) persist it to `webcal_config` on POST.
- Added the exclusions `<textarea>` to the same fieldset so Story 4.1's `SECURITY_AUDIT_EXTRA_EXCLUDES` setting has a UI surface too. Story 4.1 left this pending.
- Added 8 translation strings for the admin UI labels and option names.
- Full signed-manifest suite now: **167 tests / 357 assertions** green. PHPStan level-0 clean.

### Story 4.3 — Access control integration 🟩
**Acceptance criteria:**
- [x] Existing `ACCESS_SECURITY_AUDIT` check (already present in `security_audit.php`) gates the new section too. No new UAC function added.

**Verification (no code changes required):**

The existing guard at the top of `security_audit.php` (lines 17–20) is:

```php
if (!$is_admin || (access_is_enabled()
  && !access_can_access_function(ACCESS_SECURITY_AUDIT))) {
  die_miserable_death(print_not_auth());
}
```

`die_miserable_death()` halts execution immediately, so every line below it — including the `render_file_integrity_section()` call on line 258 — is unreachable unless the caller is (a) an admin OR (b) has the `ACCESS_SECURITY_AUDIT` UAC function granted when UAC is enabled. The new file-integrity section inherits this gate for free; there was no additional work to do.

**TDD:** New test file `tests/SecurityAuditAccessGateTest.php` — 5 tests, 36 assertions, all passing. This is a source-structure regression test (reads the .php files rather than running HTTP) that locks in the invariants:

- `ACCESS_SECURITY_AUDIT` gate exists in the first 30 lines of `security_audit.php`.
- `render_file_integrity_section()` call site appears AFTER both the access check AND the `die_miserable_death(print_not_auth())` line.
- No call to `render_file_integrity_section()` anywhere before the access gate.
- No new UAC constant was introduced (`ACCESS_FILE_INTEGRITY`, `ACCESS_MANIFEST`, `ACCESS_RELEASE_SIGNING`, `ACCESS_SIGNED_MANIFEST` — all asserted absent from `includes/access.php`).
- The existing `ACCESS_SECURITY_AUDIT` constant is still defined in `includes/access.php` — don't accidentally delete the one we depend on.

If a future refactor ever reorders `security_audit.php` in a way that exposes the file-integrity section without the gate, OR if a developer adds a duplicate UAC constant for the new feature, this test breaks the build.

**Out-of-scope concern (not part of this story):** the CLI tools `tools/build-manifest.php`, `tools/sign-manifest.php`, and `tools/verify-release-signing-key.php` ship with releases and could in principle be hit via the web (`/tools/sign-manifest.php`). They are intended for CLI / CI use and have no authentication guard. Same as the pre-existing `tools/send_reminders.php` etc. This is a project-wide concern predating Story 4.3 — not a regression introduced by the signed-manifest feature. Flagged here for future hardening (e.g., ship with `.htaccess` Deny + document webroot layout).

Full signed-manifest suite now: **172 tests / 393 assertions** green.

---

## Epic 5 — Operational Runbook & Documentation 🟨

### Story 5.1 — Developer runbook 🟩
**Acceptance criteria:**
- [x] New doc `docs/release-signing.md` covers all required topics:
  - [x] Threat model (adapted from the top of this file with an explicit "what this catches" / "what it doesn't" / "why we ship it anyway" framing).
  - [x] How to generate a fresh keypair via `tools/generate-release-key.php` — prerequisites, procedure, and the randomness/libsodium-missing invariants locked in by unit tests.
  - [x] How to install the public key into the repo (awk extraction from the tmp file, git commit, defensive `.gitignore` rule).
  - [x] How to store the private key in the GitHub Actions `release` environment secret + how to verify the paste via the **Verify Release Signing Key** workflow.
  - [x] Key rotation procedure (routine cadence, immediate compromise trigger, major-version boundaries) with an optional transition-period path documented as code-not-yet-written.
  - [x] Compromise response runbook (immediate rotation, delete suspect secret, GitHub Security Advisory, CHANGELOG Security entry, access audit).
  - [x] **Manual verification** — three independent paths:
    - Pure-PHP one-liner using libsodium (recommended; zero extra deps).
    - Node.js + libsodium-wrappers (for non-PHP environments).
    - `openssl pkeyutl -verify` via PKCS#8 / SPKI conversion (shown with the required `\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00` prefix; explained why it's the hard path).
- [x] Troubleshooting section for the three most likely operator-hit failures: "Manifest files not present", "Manifest signature FAILED", and "signing step fails in release workflow".
- [x] **Verified by running the documented commands**: the PHP one-liner and the `sha256sum -c` cross-check both work exactly as written against a fresh fixture triple — `SIGNATURE VALID`, `all hashes match`.

### Story 5.2 — Admin help text ⬜
**Acceptance criteria:**
- [ ] `security_audit.php` renders a one-line link under the "File integrity" section pointing to `docs/release-signing.md` (or a short anchor within).
- [ ] Admin settings UI for the new config keys includes help text.

### Story 5.3 — `CHANGELOG.md` entry 🟩
**Acceptance criteria:**
- [x] Entry under the `[Unreleased]` section in `CHANGELOG.md` with a `### Added` subsection noting: "Security audit now verifies a signed manifest of release files and reports extra, modified, and missing files — a defense against opportunistic webshell drops (#233). See `docs/release-signing.md` for the maintainer runbook and independent verification instructions."

---

## Epic 6 — Tests & CI ⬜

### Story 6.1 — End-to-end integration test ⬜
**Acceptance criteria:**
- [ ] New PHPUnit test `tests/SignedManifestIntegrationTest.php`.
- [ ] Builds a test manifest against a fixture tree using a test keypair.
- [ ] Mutates the tree (add, remove, modify) and asserts the `ScanReport` matches expectations for each severity.
- [ ] Asserts that a one-byte flip in the manifest causes signature verification to fail and scan results to be withheld.

### Story 6.2 — CI: run new unit tests ⬜
**Acceptance criteria:**
- [ ] No changes needed to `.github/workflows/ci.yml` — new tests live under `tests/` and are picked up by existing `phpunit -c tests/phpunit.xml`.
- [ ] PHPStan passes on new `includes/classes/Security/` code at the repo's existing level.

### Story 6.3 — Release-workflow smoke test ⬜
**Acceptance criteria:**
- [ ] Manual verification on first release cut: download the published zip, extract, run `security_audit.php` on a fresh install, confirm green "Manifest signature valid" and an empty findings table.
- [ ] Independent verification using `openssl` CLI (no app involved) per `docs/release-signing.md`.

---

## Epic 7 (Stretch) — Cosign Keyless Signing ⏭️

**Goal:** Publish an additional cosign signature of the release zip using GitHub's
OIDC identity, giving security-conscious admins an independent verification path
that doesn't rely on the maintainer's local key.

### Story 7.1 — Add cosign step to release workflow ⏭️
**Acceptance criteria:**
- [ ] `sigstore/cosign-installer@v3` added to the workflow.
- [ ] After the zip is built, sign it keyless: `cosign sign-blob --yes WebCalendar-${VERSION}.zip`.
- [ ] `.sig` and `.pem` (cert) uploaded as release assets.
- [ ] Users can verify independently: `cosign verify-blob --certificate-identity-regexp '...' --certificate-oidc-issuer https://token.actions.githubusercontent.com ...`.
- [ ] Documented in `docs/release-signing.md`.

---

## Open Questions

| # | Question | Status |
|---|----------|--------|
| Q1 | Should the public key also be embedded as a PHP constant in `security_audit.php` as belt-and-suspenders (so a one-file drop can't replace the `.pem` unnoticed)? | Deferred — does not close the threat model gap (attacker can edit `security_audit.php` too), and doubles the key-rotation surface. |
| Q2 | Should we support multiple public keys (key transition period)? | Deferred to Epic 5; initial release supports one active key. Rotation procedure will temporarily ship both the old and new pubkey and verify against either. |
| Q3 | Should the audit log findings to `webcal_activity_log` for historical tracking? | Defer to a follow-up issue unless it's trivial in Epic 3. |

---

## Decisions Log

| Date | Decision | Superseded by |
|------|----------|---------------|
| 2026-04-23 | D1–D10 locked per conversation with maintainer. | — |
| 2026-04-23 | D10 refined: new code uses namespace `WebCalendar\Security` (loaded via `require_once`, no autoloader change). Keeps class names collision-free and matches the PHP guide's namespacing expectation without destabilizing the legacy global-namespace includes. | — |
| 2026-04-23 | PHP floor for new shipping code is 8.1 (per `.github/workflows/php-syntax-check.yml` matrix). Features requiring 8.2+ (typed constants, `readonly class`) are avoided; forward-compatible attributes (`#[\SensitiveParameter]`, `#[\Override]`) are fine. | — |
| 2026-04-23 | `VerifyResult` (Story 3.1) specified as `final readonly class` — that's PHP 8.2 syntax. Implemented as 8.1-compatible `final class` with `readonly` on promoted properties. Semantically identical: any mutation of `$valid` or `$reason` after construction raises fatal Error, confirmed by `testVerifyResultIsImmutable`. | — |

---

## References

- GitHub issue: <https://github.com/craigk5n/webcalendar/issues/233>
- Existing file to extend: `security_audit.php`
- Release workflow to extend: `.github/workflows/release.yml`
- Manifest source of truth: `release-files` (442 entries as of this writing)
- libsodium Ed25519: <https://www.php.net/manual/en/function.sodium-crypto-sign-verify-detached.php>
- PHP guide for new code: `~/ai-guides/php.md` (PHP 8.1+ idioms — typed properties, readonly, enums, `#[\Override]`, `#[\SensitiveParameter]`)
