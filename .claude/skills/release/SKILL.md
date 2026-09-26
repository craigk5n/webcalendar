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

# No modified tracked files. Untracked local files are chronic in this
# repo (a couple of dozen scratch files, roadmaps and local configs), so
# plain --porcelain is never empty and would halt every release. The bump
# itself happens in a fresh worktree (Step 3), which is clean by
# construction; what matters here is that master has no uncommitted edits.
git status --porcelain --untracked-files=no          # expect: empty

# Up to date with remote
git fetch origin
git log HEAD..origin/master --oneline               # expect: empty

# Current version
./bump_version.sh -p
```

If on a different branch, or a tracked file is modified: stop and ask the user how to proceed. Do **not** auto-stash or auto-checkout.

**Release-manifest coverage check.** Every git-tracked file must be classified: listed in `release-files` (ships in the ZIP) or matched by `release-files-excluded` (dev-only). Past releases shipped broken because new files were committed without updating `release-files` (#667, missing translations, `export_wordpress.php`).

```bash
# Enforced invariant — fails if any tracked file is unclassified
vendor/bin/phpunit -c tests/phpunit.xml --filter ReleaseFilesConsistencyTest

# Files added since the last release, with their classification
PREV_TAG=$(git describe --tags --abbrev=0)
git diff --diff-filter=A --name-only "$PREV_TAG"..HEAD
```

Show the user the list of files added since `PREV_TAG` and, for each, whether it ships (`release-files`) or is excluded (`release-files-excluded`). Ask the user to confirm the classification of any **newly added file that landed in the excluded set** — a runtime file wrongly excluded is exactly the drift that broke past releases. If the test fails, stop: classify the offending files (with the user) before proceeding.

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

**First: is this checkout also a live deployment?** If the tree is served by a
web server — `/var/www/...`, or `includes/settings.php` points at a database
real users rely on — do not bump in it. `upgrade_requires_db_changes()` returns
true for *every* version bump by design (`includes/functions.php`, "This
ensures the wizard is triggered for every version bump"), so from the moment
`includes/config.php` names a version ahead of the database, every request
redirects to `wizard/index.php`. The site is down until the tree is switched
back.

The mechanism is confirmed: `upgrade_requires_db_changes()` returns true for
any newer version, from nested scope and on repeat calls, so the redirect
branch is the one that runs. What that costs depends on whether a request
arrives. When v1.9.24 was cut this way on 2026-09-26 the tree sat ahead of the
database for about six minutes; whether a visitor hit it in that window was
never established, so treat the exposure as "every request during the window",
not as a measured outage.

Restoring the tree is not necessarily the whole recovery, because a request
that *does* arrive can complete the wizard and upgrade the database — which is
what appears to have happened on 2026-09-26. Then the roles reverse: the
database is ahead of a restored tree, `upgrade_requires_db_changes()` finds
nothing newer, returns false, and `update_webcalendar_version_in_db()` silently
rewrites the stored version *backwards* to match the older code. A calendar can
therefore end up with its version row flip-flopping as the tree is switched,
with no record in the activity log.

**The rewrite moves the version row; it does not undo applied SQL.** That is
the part to be careful about. After someone upgrades and the tree is then
restored, the row understates the schema: it says vX.Y.(Z-1) while vX.Y.Z's
statements have already run. Upgrading again from that row re-applies them.
v1.9.24's one statement is explicitly idempotent, so this cost nothing on
2026-09-26 — but `upgrade-sql.php` contains 72 `ALTER TABLE ... ADD` statements
across its history, and every one of those fails on a second run with a
duplicate-column error.

So after any release attempt that was interrupted, reverted, or bounced between
trees, check the version row against what has actually been applied rather than
trusting the row:

```bash
php bin/webcal.php config get WEBCAL_PROGRAM_VERSION
```

and confirm it against the effect of that version's entry in
`wizard/shared/upgrade-sql.php`. `db check` compares the row to the code, so it
agrees with a row that is itself wrong.

Work in a `git worktree` instead, which leaves the served tree untouched:

```bash
git worktree add -b chore/release-vX.Y.Z /tmp/wc-release origin/master
ln -s "$PWD/vendor" /tmp/wc-release/vendor    # so the test suite can run
cd /tmp/wc-release
```

Three release-files guards `markTestSkipped` in a linked worktree (`.git` is a
file there, so their `git ls-files` probe fails), so Step 1's manifest check
must be run in the main checkout or trusted to CI.

Then bump:

```bash
./bump_version.sh                                   # auto-patch bump
# OR
./bump_version.sh vX.Y.Z                            # explicit version
```

This updates: `includes/default_config.php`, `wizard/shared/upgrade_matrix.php`, `includes/config.php` (`$PROGRAM_VERSION` + `$PROGRAM_DATE`), `composer.json`, `composer.lock`, `.npmrc`, `wizard/shared/tables-*.sql`, `wizard/shared/tables-sqlite*.php`, `wizard/shared/upgrade-sql.php` (adds empty placeholder entry), the four wizard files (`index.php`, `headless.php`, `wizard.js`, `WizardState.php`), and three documentation stamps: `README.md`'s version badge, `docs/WebCalendar-Database.md`'s `**Version:**` line, and `CLAUDE.md`'s overview line where that file exists.

The documentation stamps are not cosmetic: `tests/DocumentedVersionTest.php`
asserts all three against `includes/config.php`, so a release that bumps the
code and not the docs fails the build. They must be staged with the rest
(Step 8).

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

Note that "cut the release" earlier in the conversation is authorisation for
the release; it is not this confirmation, which is about the specific diff.

---

## Step 8 — Commit and open a pull request

**Stage explicitly — never `git add -A`.** The repo carries a couple of dozen
untracked local files; `-A` would sweep them into the release commit. Stage
only the files this release touches:

```bash
# Files bump_version.sh touches + CHANGELOG.md.
# If a release skips bump_version.sh (e.g. shipping a previously-prepared
# version), stage only what actually changed (commonly just CHANGELOG.md).
git add \
  CHANGELOG.md \
  includes/default_config.php \
  wizard/shared/upgrade_matrix.php \
  includes/config.php \
  composer.json composer.lock \
  .npmrc \
  wizard/shared/tables-*.sql \
  wizard/shared/tables-sqlite*.php \
  wizard/shared/upgrade-sql.php \
  wizard/index.php wizard/headless.php wizard/wizard.js wizard/WizardState.php \
  README.md docs/WebCalendar-Database.md

# CLAUDE.md is also updated by bump_version.sh but is untracked; nothing to
# stage, and DocumentedVersionTest skips it when it is absent. It will
# disagree with includes/config.php in any checkout that has it until that
# checkout pulls the release commit.

# Sanity-check what's staged before committing
git diff --cached --stat

git commit -m "chore(release): vX.Y.Z"
```

Then open a pull request against `master` and merge it once CI is green:

```bash
git push -u origin chore/release-vX.Y.Z
gh pr create --base master --head chore/release-vX.Y.Z \
  --title "chore(release): vX.Y.Z" --body-file /tmp/release-pr-vX.Y.Z.md
# ... wait for checks, then:
gh pr merge <N> --merge
```

Pass `--head` explicitly. `gh pr create` otherwise infers the head branch from
the current checkout, which is wrong whenever the bump was made in a worktree
or the tree has been switched back.

**Why a pull request rather than committing straight to master.** An earlier
version of this skill committed and tagged on `master` locally, then pushed
`master`, `master:release` and the tag in one call. A pull request instead
gives the release commit the same review and the same checks as everything
else in this repository: the diff gets a URL, and `master` does not move until
the suite is green on the exact commit being shipped.

The cost is that `master` gains a merge commit that does not exist locally, so
the tag cannot be created before the push. Step 9 handles that.

**What a pull request does not buy you:** nothing exercises `release.yml`
itself. It runs only on a push to `release`, so a bug in the release workflow
reaches you at release time no matter how the commit got to `master`. That is
how v1.9.24 first failed to cut — `release.yml` calls five workflows, three of
which shared a concurrency group and so cancelled one of their own jobs, and
no pull request could have shown it. Structural guards are the only cover
here; `tests/ReusableWorkflowConcurrencyTest.php` is the one for that
particular trap. Expect the first release after any `release.yml` or
reusable-workflow change to be the test of it, and read the run rather than
assuming a green PR implies a green release.

---

## Step 9 — Tag the merge commit and fast-forward `release`

After the merge, the commit to tag is `origin/master`, not anything local.
Tag it **without checking it out** — checking it out in a served tree is the
Step 3 hazard again:

```bash
git fetch origin
git tag vX.Y.Z origin/master                 # no working-tree change
git rev-parse vX.Y.Z origin/master           # confirm they match

# One round-trip: the tag, then the release fast-forward.
git push origin vX.Y.Z origin/master:refs/heads/release
```

The tag must exist at push time so `actions/create-release@v1` reuses it
instead of minting its own. `release.yml` does tag the branch itself as a
fallback and the result is the same commit, so a release cut without the tag
is not broken — but the tag then comes from the workflow rather than from a
reviewed commit, and the run's own "Check and Delete Existing Tag" step is
what makes that safe. Push the tag.

Confirm the fast-forward before pushing if `release` has drifted:

```bash
git merge-base --is-ancestor origin/release origin/master && echo "fast-forward"
git rev-list --count origin/release..origin/master
```

The `release` branch is a moving pointer at the most recent shipped commit,
not a long-lived branch — a plain fast-forward, never a merge commit. It can
legitimately be many commits behind (158, for v1.9.24); confirm with the user
if the count is unexpectedly high, but a large number is normal when releases
are infrequent.

Pushing to `release` is what triggers `.github/workflows/release.yml`: full CI
suite → build zip → manifest and cosign signing → GitHub release. It also
triggers `.github/workflows/docker.yml`, which builds a multi-arch image and
pushes four Docker Hub tags: `<user>/webcalendar:X.Y.Z`, `X.Y.Z-php8-apache`,
`latest-php8-apache` and `latest`. No manual Docker step is needed.

**Do not `git pull` in a served checkout afterwards.** `master` now names a
version ahead of that installation's database, which is the wizard redirect
from Step 3. Upgrading a live install is a deployment decision for its
administrator, separate from cutting the release; `php bin/webcal.php db check`
reports the pending state without applying anything.

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
- Docker Hub tags pushed by docker.yml (`X.Y.Z`, `X.Y.Z-php8-apache`, `latest-php8-apache`, `latest`) — spot-check with:
  `curl -s "https://auth.docker.io/token?service=registry.docker.io&scope=repository:craigk5n/webcalendar:pull"` for a token, then HEAD `https://registry-1.docker.io/v2/craigk5n/webcalendar/manifests/X.Y.Z` (the hub.docker.com tag-listing API lags; the registry API is authoritative)
- Anything left manual (e.g., announcement post)

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
| `includes/default_config.php` | `WEBCAL_PROGRAM_VERSION` |
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
| `README.md` | version badge |
| `docs/WebCalendar-Database.md` | `**Version:**` stamp |
| `CLAUDE.md` | overview line, only if the file is present |

## Reference: stale instructions to ignore

The legacy installer code referenced `upgrade-*.sql` files with `/*upgrade_vX.Y.Z */` C-style comments, plus `ChangeLog` and `NEWS` files. **All of this is obsolete:**
- `upgrade-*.sql` files were replaced by `wizard/shared/upgrade-sql.php` in v1.9.12.
- `ChangeLog` and `NEWS` were replaced by `CHANGELOG.md` (Keep a Changelog format).
