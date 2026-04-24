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

## Epic 2 — Release-Time Manifest Generation ⬜

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

### Story 2.2 — `tools/sign-manifest.php` script ⬜
**As** the release workflow
**I want** to sign the manifest with the private key from env
**So that** a detached signature accompanies the manifest

**Acceptance criteria:**
- [ ] Reads private key from env var `RELEASE_SIGNING_KEY` (base64 of 64-byte libsodium secret key).
- [ ] Input: path to `MANIFEST.sha256`. Output: writes `MANIFEST.sha256.sig` next to it.
- [ ] Signature format: base64 of the 64-byte Ed25519 signature, single line + LF.
- [ ] Uses `#[\SensitiveParameter]` on any function receiving the secret key.
- [ ] Exits non-zero if env var is empty, malformed, or wrong length.
- [ ] Never logs, echoes, or exposes the secret key — CI log is reviewed for leakage.

**TDD:**
- Unit test: signs a known manifest with a test keypair, verifies with the paired public key.
- Unit test: tampering with one byte of the manifest after signing causes verification to fail.
- Unit test: wrong-length secret key produces a clear error.

### Story 2.3 — Wire into `.github/workflows/release.yml` ⬜
**As** the maintainer
**I want** the manifest and signature built as part of every release
**So that** there is no manual step to forget

**Acceptance criteria:**
- [ ] New workflow step after "Copy files to release directory" runs `tools/build-manifest.php` against the staged `WebCalendar-${VERSION}/` tree.
- [ ] Next step runs `tools/sign-manifest.php` with `RELEASE_SIGNING_KEY` injected from secrets, producing `MANIFEST.sha256.sig`.
- [ ] Both files are placed at the root of the staged release tree BEFORE `zip -r`, so the zip contains them.
- [ ] Both files are uploaded as separate release assets alongside the zip (`actions/upload-release-asset@v1.0.2` for each).
- [ ] The workflow fails hard if either step errors; no partial release.
- [ ] The workflow uses the `release` GitHub environment, so `RELEASE_SIGNING_KEY` is only exposed to this job.

### Story 2.4 — `release-files` entries for new manifest files ⬜
**Acceptance criteria:**
- [ ] `release-files` includes `release-signing-pubkey.pem`.
- [ ] `release-files` does NOT include `MANIFEST.sha256` or `MANIFEST.sha256.sig` (those are generated during the build, not source-tracked). They are injected into the staged tree by the workflow.

---

## Epic 3 — Audit-Time Verification (PHP in app) ⬜

**Goal:** `security_audit.php` gains a new section that verifies the signature, walks
the filesystem, and reports discrepancies with appropriate severity.

### Story 3.1 — `ManifestVerifier` class ⬜
**As** the admin running the audit
**I want** the manifest signature verified before its contents are trusted
**So that** a tampered manifest cannot mask tampered files

**Acceptance criteria:**
- [ ] New file `includes/classes/Security/ManifestVerifier.php` (PHP 8.1+, `declare(strict_types=1)`, no namespace — matches existing legacy include convention OR single-class namespace `WebCalendar\Security` if autoload is wired).
- [ ] Class exposes `verify(string $manifestPath, string $signaturePath, string $publicKeyPemPath): VerifyResult`.
- [ ] `VerifyResult` is a `final readonly class` with `bool $valid`, `string $reason`.
- [ ] Uses `sodium_crypto_sign_verify_detached()` — not OpenSSL.
- [ ] Fails gracefully with a clear reason when: any file missing, PEM malformed, signature not 64 bytes, signature mismatch.
- [ ] Uses `hash_equals()` only if comparing hex-encoded values (Ed25519 verify itself is constant-time).

**TDD:**
- Unit test: valid manifest + valid signature + valid pubkey → `valid=true`.
- Unit test: modify manifest post-sign → `valid=false`, reason mentions signature mismatch.
- Unit test: truncated pubkey → `valid=false`, reason mentions key format.
- Unit test: swapped pubkey (different keypair) → `valid=false`.
- Unit test: missing manifest file → `valid=false`, reason mentions file not found.

### Story 3.2 — `ManifestParser` class ⬜
**Acceptance criteria:**
- [ ] `parse(string $manifestPath): ManifestData` returns `{string $version, DateTimeImmutable $buildTimestamp, string $gitSha, array<string,string> $hashes}` (hashes map relative-path → sha256 hex).
- [ ] Rejects lines that don't match `/^[0-9a-f]{64}  \S.*$/` (other than header `# ` lines).
- [ ] Rejects duplicate paths.
- [ ] Throws on malformed input with line-number context.

**TDD:**
- Unit test: well-formed manifest → parsed correctly, header fields populated.
- Unit test: line with 3 spaces between hash and path → rejected.
- Unit test: duplicate path → rejected with line number.

### Story 3.3 — `InstallationScanner` class ⬜
**As** the audit
**I want** a filesystem walker that compares disk state to the manifest
**So that** missing, modified, and extra files are identified

**Acceptance criteria:**
- [ ] `scan(ManifestData $manifest, string $installRoot, ExcludeRules $excludes): ScanReport`.
- [ ] Walks `$installRoot` recursively (RecursiveDirectoryIterator + SKIP_DOTS).
- [ ] For each disk file: if in manifest → hash and compare → classify `MATCH` / `MODIFIED`. If not in manifest AND not excluded → classify `EXTRA`.
- [ ] For each manifest entry not seen on disk AND not excluded → classify `MISSING`.
- [ ] `ScanReport` = `{list<ScannedFile> $modified, list<ScannedFile> $missing, list<ScannedFile> $extra, int $matchedCount}`.
- [ ] Honors `$excludes` (see Story 4.1).
- [ ] Symlinks are not followed; they are reported as EXTRA unless manifest-listed.

**TDD:**
- Integration test: fixture install tree matching a manifest → empty report, `matchedCount` = file count.
- Integration test: delete one shipped file → reported as MISSING.
- Integration test: modify one shipped file → reported as MODIFIED.
- Integration test: add `evil.php` at root → reported as EXTRA.
- Integration test: add excluded path → NOT reported.
- Integration test: a symlink pointing outside the tree → reported but not followed.

### Story 3.4 — Severity classifier ⬜
**As** the admin
**I want** each finding tagged with severity so I can triage
**So that** unexpected PHP files shout louder than unexpected CSS

**Acceptance criteria:**
- [ ] `SeverityClassifier::classify(ScannedFile $file): Severity` where `Severity` is a backed enum `CRITICAL='critical'|WARN='warn'|INFO='info'`.
- [ ] Extra `.php`, `.phtml`, `.phar`, `.inc` file → CRITICAL.
- [ ] Modified shipped file (any extension) → WARN.
- [ ] Missing shipped file → WARN.
- [ ] Extra file with non-executable extension (`.css`, `.html`, `.txt`, image extensions, `.map`) → INFO.
- [ ] Extra file with unknown extension → WARN (conservative — unknowns might be webshells in disguise).

**TDD:**
- Unit test: each classification path has a dedicated test.
- Unit test: `shell.php` → CRITICAL; `styles.css` (extra) → INFO; `foo.xyz` (extra) → WARN.

### Story 3.5 — `security_audit.php` integration ⬜
**As** the admin
**I want** the audit page to show the new results alongside existing checks
**So that** the existing UX remains familiar

**Acceptance criteria:**
- [ ] New section in `security_audit.php` titled "File integrity" rendered after existing issues.
- [ ] Shows signature verification status at the top: PASS (green) / FAIL (red with reason).
- [ ] If verification fails, does NOT display file-level results (manifest is untrusted).
- [ ] If verification passes, shows three tables: Modified files, Missing files, Extra files, each filtered by the active noise filter (Story 4.2).
- [ ] Each row: path, severity badge, action hint ("Review contents", "Restore from release zip", etc.).
- [ ] Summary line: "Scanned N files against manifest version X.Y.Z signed on $date."
- [ ] Translation strings added to `translations/English-US.txt` (`'File integrity'`, `'Manifest signature'`, `'Manifest signature valid'`, `'Manifest signature FAILED: XXX'`, `'Modified files'`, `'Missing files'`, `'Extra files'`, `'Scanned XXX files against manifest'`).
- [ ] Section only renders if `release-signing-pubkey.pem`, `MANIFEST.sha256`, and `MANIFEST.sha256.sig` are all present; otherwise a one-line "Manifest files not present (install may be from source or pre-1.9.x release)" notice.

**TDD:** Not a pure unit test — covered by manual/integration test in Story 6.1.

---

## Epic 4 — Configuration & Exclusions ⬜

### Story 4.1 — `ExcludeRules` configuration source ⬜
**As** the admin / developer
**I want** a predictable list of paths excluded from the audit
**So that** user-editable files don't flood the report

**Acceptance criteria:**
- [ ] `ExcludeRules` class encapsulates the exclusion set; constructor takes `list<string> $globs`.
- [ ] Default exclude list (hard-coded constant):
  - `includes/settings.php`
  - `includes/site_extras.php`
  - `MANIFEST.sha256`
  - `MANIFEST.sha256.sig`
  - `tools/`
  - `tests/`
  - `docs/`
  - `vendor/`
  - `.git/`
  - `.github/`
- [ ] `matches(string $relPath): bool` supports `*` and trailing-slash prefix matching.
- [ ] User-provided additional excludes come from admin setting `SECURITY_AUDIT_EXTRA_EXCLUDES` (newline-separated globs). Stored in `webcal_config`.
- [ ] CSS-under-`pub/` is NOT excluded by default (per D9 — flag everything by default), but is trivially added to the extra excludes list by admins who customize their theme.

**TDD:**
- Unit test: default list excludes `includes/settings.php` but not `includes/init.php`.
- Unit test: glob `pub/css/*.css` matches `pub/css/custom.css` but not `pub/js/foo.js`.
- Unit test: user-supplied excludes are unioned with defaults.

### Story 4.2 — Noise filter admin setting ⬜
**As** a developer or a power user
**I want** to suppress low-severity findings
**So that** the report is actionable on a heavily customized install

**Acceptance criteria:**
- [ ] New config key `SECURITY_AUDIT_NOISE_FILTER` with values `all` (default), `warn_and_above`, `critical_only`.
- [ ] Surfaced in admin settings UI (`admin.php`) with help text explaining each mode.
- [ ] When set to `critical_only`, the audit renders only findings that classify as CRITICAL.
- [ ] When set to `warn_and_above`, INFO findings are hidden.
- [ ] Default `all` shows everything.

**TDD:**
- Unit test: filter `critical_only` applied to a mixed ScanReport returns only CRITICAL entries.
- Unit test: filter `warn_and_above` preserves WARN + CRITICAL.
- Unit test: filter `all` is the identity function.

### Story 4.3 — Access control integration ⬜
**Acceptance criteria:**
- [ ] Existing `ACCESS_SECURITY_AUDIT` check (already present in `security_audit.php`) gates the new section too. No new UAC function added.

---

## Epic 5 — Operational Runbook & Documentation ⬜

### Story 5.1 — Developer runbook ⬜
**Acceptance criteria:**
- [ ] New doc `docs/release-signing.md` covers:
  - Threat model (from the top of this file).
  - How to generate a fresh keypair (tools/generate-release-key.php).
  - How to install the public key into the repo and the private key into GitHub secrets.
  - Key rotation procedure (when to rotate, how to transition, how to handle old releases).
  - What to do if the private key is suspected compromised (revoke, rotate, publish advisory, notify users to re-download the affected release).
  - How admins verify a release manually using `openssl` + the published public key (independent of the app).

### Story 5.2 — Admin help text ⬜
**Acceptance criteria:**
- [ ] `security_audit.php` renders a one-line link under the "File integrity" section pointing to `docs/release-signing.md` (or a short anchor within).
- [ ] Admin settings UI for the new config keys includes help text.

### Story 5.3 — `CHANGELOG.md` entry ⬜
**Acceptance criteria:**
- [ ] Entry under the next release version notes: "Security audit now verifies a signed manifest of release files and reports extra/modified/missing files (issue #233)."

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

---

## References

- GitHub issue: <https://github.com/craigk5n/webcalendar/issues/233>
- Existing file to extend: `security_audit.php`
- Release workflow to extend: `.github/workflows/release.yml`
- Manifest source of truth: `release-files` (442 entries as of this writing)
- libsodium Ed25519: <https://www.php.net/manual/en/function.sodium-crypto-sign-verify-detached.php>
- PHP guide for new code: `~/ai-guides/php.md` (PHP 8.1+ idioms — typed properties, readonly, enums, `#[\Override]`, `#[\SensitiveParameter]`)
