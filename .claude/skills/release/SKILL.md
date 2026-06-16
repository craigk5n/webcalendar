---
name: release
description: Create a WebCalendar release — bump version, update CHANGELOG.md, verify DB upgrade SQL, generate GitHub release notes, commit/merge/tag/push to trigger CI, then publish curated release notes. Use when the user asks to "cut a release", "tag a release", "ship vX.Y.Z", or similar.
---

# WebCalendar Release Skill

Drives the WebCalendar release process end-to-end. Most version-string updates are already automated by `bump_version.sh`; this skill orchestrates the surrounding steps that aren't (CHANGELOG, upgrade-SQL verification, branch merge, tag, GitHub release notes).

## When to Activate

User asks to:
- "Cut a release" / "ship a release" / "tag vX.Y.Z"
- "Bump the version and release"
- "Create release vX.Y.Z"

## Inputs

- **New version** (optional): `vX.Y.Z`. If omitted, auto-increment patch from current version.
- **Release type** (implied by version arg): patch / minor / major.

If the user gave no version, ask: "Auto-bump patch from `<current>` to `<next-patch>`, or do you want a different version?"

---

## Step 1 — Pre-flight checks

Run these checks. Halt and report on any failure.

```bash
# Must be on master
git rev-parse --abbrev-ref HEAD                     # expect: master

# Clean working tree
git status --porcelain                              # expect: empty

# Up to date with remote
git fetch origin
git log HEAD..origin/master --oneline               # expect: empty

# Current version
./bump_version.sh -p
```

If on a different branch or working tree is dirty: stop and ask the user how to proceed. Do **not** auto-stash or auto-checkout.

Compute the new version:
- If user supplied `vX.Y.Z`, use that.
- Otherwise: `bump_version.sh` will auto-increment the patch when called with no arg.

State the planned version explicitly to the user before continuing.

---

## Step 2 — Run local tests

Per maintainer policy, verify locally before triggering CI:

```bash
./tests/compile_test.sh                             # PHP syntax + composer.lock drift
vendor/bin/phpunit -c tests/phpunit.xml             # PHPUnit suite
```

Halt on any failure. Do not "fix and retry" — surface the failure to the user.

---

## Step 3 — Bump the version

```bash
./bump_version.sh                                   # auto-patch bump
# OR
./bump_version.sh vX.Y.Z                            # explicit version
```

This updates: `wizard/shared/default_config.php`, `wizard/shared/upgrade_matrix.php`, `includes/config.php` (`$PROGRAM_VERSION` + `$PROGRAM_DATE`), `composer.json`, `composer.lock`, `.npmrc`, `wizard/shared/tables-*.sql`, `wizard/shared/tables-sqlite*.php`, `wizard/shared/upgrade-sql.php` (adds empty placeholder entry), and the four wizard files (`index.php`, `headless.php`, `wizard.js`, `WizardState.php`).

Confirm by running `./bump_version.sh -p` — should show the new version.

---

## Step 4 — Update CHANGELOG.md (auto)

`bump_version.sh` does **not** touch `CHANGELOG.md`. This skill must.

Open `CHANGELOG.md`. The file uses Keep a Changelog format with an `## [Unreleased]` section near the top (after the intro paragraphs). Two edits:

**A. Rename `## [Unreleased]` → `## [vX.Y.Z] - YYYY-MM-DD`**

Use today's date in `YYYY-MM-DD` format (compute from `date +%Y-%m-%d`).

**B. Insert a fresh `## [Unreleased]` section directly above the new versioned heading**

The fresh section must contain empty subsections so future contributors can drop entries in. Template:

```markdown
## [Unreleased]

### Added

### Changed

### Fixed

### Removed

```

(Yes — empty subsections are intentional. They guide future PRs.)

After editing, show the user the diff for `CHANGELOG.md` so they can confirm before committing.

**If the existing `[Unreleased]` section is empty** (no entries under any subsection): stop and ask the user. A release with no changelog entries is suspicious — they may want to add entries or skip the release.

---

## Step 5 — Verify `wizard/shared/upgrade-sql.php`

`bump_version.sh` appends a placeholder entry like:

```php
  [
    'version' => 'vX.Y.Z',
    'default-sql' => ''
  ],
```

Open `wizard/shared/upgrade-sql.php`, locate the entry for the **new** version, and inspect its `default-sql` (and any `postgresql-sql` / `sqlite3-sql` / `upgrade-function` keys).

Two cases:

**Case A — `default-sql` is non-empty (DB schema changes for this release):**
Show the user the SQL block(s) and ask: "Confirm these are the correct DB upgrade statements for vX.Y.Z?" Wait for confirmation before proceeding.

**Case B — `default-sql` is empty:**
Ask the user explicitly: "No DB schema changes detected for vX.Y.Z. Confirm there are genuinely no DB changes for this release? (If there should be, edit `wizard/shared/upgrade-sql.php` now.)"

The empty-entry-as-marker convention matches prior no-DB-change releases (see v1.9.14 and v1.9.15 entries). Leaving the empty entry in place is the correct way to record "no DB changes for this version" — do **not** delete the entry.

---

## Step 6 — Generate GitHub release notes

Write release notes to `/tmp/release-notes-vX.Y.Z.md`.

Source material, in order of priority:
1. The new `## [vX.Y.Z]` section from `CHANGELOG.md` (just renamed in Step 4) — primary content.
2. Commit log since the previous tag, for cross-reference: `git log <prev-tag>..HEAD --oneline`. Use this to spot-check that the changelog is complete; if commits clearly missing from the changelog reference issues/PRs (`fix(...): ... (#NNN)`), include them.

**Format** (matches recent releases like v1.9.15):

```markdown
# WebCalendar vX.Y.Z

<one-paragraph release summary — pull from CHANGELOG.md intro or write a 1-2 sentence summary>

## Added
- ...

## Changed
- ...

## Fixed
- ...

## Removed
- ...

## Verifying this release

WebCalendar releases ship a signed manifest. See `docs/release-signing.md` for verification instructions.
```

Drop empty subsections. Keep PR/issue references (`(#NNN)`) intact — they render as links on GitHub.

Show the user the generated notes and ask for edits before continuing.

---

## Step 7 — Confirm before committing

Before any `git commit`, show the user:
- `git status` — files staged/changed
- `git diff --stat` — summary of changes
- The `CHANGELOG.md` diff (Step 4)
- The release notes preview (Step 6)
- The new version (`./bump_version.sh -p`)

Wait for explicit "go" / "yes" / equivalent confirmation. Do **not** assume.

---

## Step 8 — Commit, tag

**Stage explicitly — never `git add -A`.** The repo often has untracked dev files; `-A` would sweep them into the release commit. Stage only the files this release touches:

```bash
# Files bump_version.sh touches + CHANGELOG.md.
# If a release skips bump_version.sh (e.g. shipping a previously-prepared version),
# stage only what actually changed (commonly just CHANGELOG.md).
git add \
  CHANGELOG.md \
  wizard/shared/default_config.php \
  wizard/shared/upgrade_matrix.php \
  includes/config.php \
  composer.json composer.lock \
  .npmrc \
  wizard/shared/tables-*.sql \
  wizard/shared/tables-sqlite*.php \
  wizard/shared/upgrade-sql.php \
  wizard/index.php wizard/headless.php wizard/wizard.js wizard/WizardState.php

# Sanity-check what's staged before committing
git diff --cached --stat

git commit -m "chore(release): vX.Y.Z"

# Tag the release commit at master HEAD
git tag vX.Y.Z
```

Note: the repo's release model uses **fast-forward of `release` to `master`**, not a true merge. The `release` branch is a moving pointer at the most recent shipped commit. Don't create a merge commit unless `release` has divergent commits (it shouldn't).

---

## Step 9 — Push (atomic)

Push master, fast-forward `release` to master, and the tag — all in one network round-trip so CI sees consistent state:

```bash
git push origin master master:release vX.Y.Z
```

Pushing to `release` is what triggers `.github/workflows/release.yml`: full CI suite → build zip → cosign signing → GitHub release. The tag must exist at push time so `actions/create-release@v1` reuses it instead of attempting to mint a new one.

**If `release` is far behind master** (it can drift between releases), the FF will include all intermediate commits. Confirm with the user before pushing if `git rev-list --count origin/release..origin/master` is unexpectedly high.

---

## Step 10 — Wait for CI, then publish curated release notes

```bash
# Find the run for the just-pushed commit (note: gh run list does NOT support --branch)
RUN_ID=$(gh run list --workflow=release.yml --limit=5 --json databaseId,headSha,status \
  | jq -r --arg sha "$(git rev-parse master)" '.[] | select(.headSha==$sha) | .databaseId' | head -1)
gh run watch "$RUN_ID" --exit-status
```

If CI fails: surface the failure URL to the user and stop. Do not retry automatically.

Once the workflow succeeds, `actions/create-release@v1` will have created a GitHub release with a placeholder body ("Release of WebCalendar vX.Y.Z"). Replace it with the curated notes.

**Preferred (`gh` ≥ 2.20):**

```bash
gh release edit vX.Y.Z \
  --title "WebCalendar vX.Y.Z" \
  --notes-file /tmp/release-notes-vX.Y.Z.md
```

**Fallback for older `gh` (< 2.20 — no `release edit` subcommand):**

```bash
RELEASE_ID=$(gh api /repos/craigk5n/webcalendar/releases/tags/vX.Y.Z -q .id)
jq -Rs '{body: .}' < /tmp/release-notes-vX.Y.Z.md \
  | gh api -X PATCH "/repos/craigk5n/webcalendar/releases/$RELEASE_ID" --input -
```

Detect via `gh release edit --help 2>&1 | grep -q "unknown command"` and pick the right path.

Verify:

```bash
gh release view vX.Y.Z
```

---

## Step 11 — Report

Tell the user:
- Release URL: `gh release view vX.Y.Z --json url -q .url`
- Tag pushed: `vX.Y.Z`
- Branches updated: `master`, `release`
- CI run: pass/fail link
- Anything left manual (e.g., announcement post, Docker tag)

---

## Halt conditions (escalate to user, don't auto-fix)

- Working tree dirty or not on `master`
- Local tests fail
- `[Unreleased]` section in CHANGELOG.md is empty
- `upgrade-sql.php` entry needs human confirmation (Step 5)
- CI workflow fails on `release` branch
- `gh` not authenticated
- Tag `vX.Y.Z` already exists upstream

## Things this skill deliberately does NOT do

- Modify `release.yml` or migrate off `actions/create-release@v1`
- Auto-write SQL into `upgrade-sql.php` (that's a human judgment call about schema changes)
- Skip pre-flight checks even if the user is in a hurry
- Force-push, amend, or rewrite history
- Delete the `[Unreleased]` placeholder entry from `upgrade-sql.php` for no-DB-change releases — the empty entry is the canonical marker

## Reference: what `bump_version.sh` already handles

(Documented here so we don't re-do its work.)

| File | What's updated |
|---|---|
| `wizard/shared/default_config.php` | `WEBCAL_PROGRAM_VERSION` |
| `wizard/shared/upgrade_matrix.php` | `$PROGRAM_VERSION` |
| `includes/config.php` | `$PROGRAM_VERSION`, `$PROGRAM_DATE` |
| `composer.json` + `composer.lock` | `version` field |
| `.npmrc` | `init-version` |
| `wizard/shared/tables-*.sql` | `WEBCAL_PROGRAM_VERSION` INSERT |
| `wizard/shared/tables-sqlite*.php` | `WEBCAL_PROGRAM_VERSION` INSERT |
| `wizard/shared/upgrade-sql.php` | appends empty placeholder entry |
| `wizard/index.php` | `const PROGRAM_VERSION` |
| `wizard/headless.php` | `const PROGRAM_VERSION` |
| `wizard/wizard.js` | `programVersion` fallback |
| `wizard/WizardState.php` | `programVersion` fallback |

## Reference: stale instructions to ignore

The legacy installer code referenced `upgrade-*.sql` files with `/*upgrade_vX.Y.Z */` C-style comments, plus `ChangeLog` and `NEWS` files. **All of this is obsolete:**
- `upgrade-*.sql` files were replaced by `wizard/shared/upgrade-sql.php` in v1.9.12.
- `ChangeLog` and `NEWS` were replaced by `CHANGELOG.md` (Keep a Changelog format).
