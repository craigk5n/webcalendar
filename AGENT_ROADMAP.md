# Agent Roadmap

Making WebCalendar (legacy, v1.9.x) work better with AI coding agents.

**Status:** proposed, 2026-09-24
**Applies to:** this repository only. Agent-facing *features* belong in WCTNG.

---

## Position

WebCalendar legacy is a co-equal product for the foreseeable future. It has a
large installed base, 109 translations, Oracle/DB2/ODBC support, and runs on
shared hosting where Docker, Symfony, Redis and Mercure are not options. WCTNG
(`webcalendar-api` + `webcalendar-web`) reaches roughly 90% feature parity and
will take over new development, but it will not take over these users soon, and
some of them never.

Legacy is therefore in **maintenance mode with an active support load**. It took
51 commits in September 2026. That rate is unlikely to drop much.

This roadmap optimizes for three things, in priority order:

1. **Support** — cut the round-trips needed to diagnose a broken install.
2. **Maintenance** — cut the time an agent needs to land a correct bug fix.
3. **Modernization** — where it serves 1 and 2, and only there.

### Not in scope, and why

WCTNG already implements these. Building them here duplicates working software.

| Idea | Where it already exists |
|------|------------------------|
| REST API with token auth | `webcalendar-api` — Symfony 7, JWT, OpenAPI |
| OpenAPI spec / self-description | `webcalendar-openapi` |
| Operator CLI (install, reminders, tenants) | `webcalendar-api/bin/console` |
| MCP tool expansion | `McpController` (7 tools); legacy `mcp.php` has 9 and is not behind |
| Legacy data migration | `webcalendar:import-legacy --dsn=...` reads the legacy DB directly |

Legacy needs to do nothing to support migration. The importer auto-detects
schema version from the live database.

---

## Design constraints

These are properties of this repository that any plan has to respect.

- **No Composer autoload at runtime.** `release-files` contains zero `vendor/`
  entries. `includes/mcp-loader.php` is a hand-rolled loader. New code either
  has no dependencies or extends that loader.
- **No PDO.** `includes/dbi4php.php` uses native `mysqli`/`pgsql`/`sqlite3`.
  `webcalendar-core` is PDO-only, so it cannot be a dependency here. The
  dependency runs the other way: core keeps a read-only `legacy/` reference copy.
- **Releases ship a manifest.** Anything that must reach users goes in
  `release-files`, or it is absent from the ZIP (see #667).
- **`.htaccess` may be a no-op.** Debian/Ubuntu default `AllowOverride None`
  neutralizes it. Nothing may depend on it for protection.
- **PHP floor is 8.2**, settled 2026-09-24 and enforced by
  `tests/PhpFloorConsistencyTest.php`. New code may use `readonly class`, typed
  class constants and `#[\Override]`. PHP 8.2 loses security support on
  31 December 2026, so this wants revisiting during 2026.
- **Code must not read as AI-generated.** `webcalendar-core/AI-SIGNALS.md` is the
  standard; core was audited against it with 45+ fixes. This repo has never been
  audited and has taken more agent-written code.
- **Follow `includes/classes/Security/`.** Final classes, enums, value objects,
  constructor promotion, no globals, unit tested. The pattern is established;
  new work matches it rather than inventing a second style.

---

## Pillar A — Support diagnostics — LANDED 2026-09-24

**Goal:** a user pastes one block of text and the maintainer, or an agent, knows
what is wrong without a back-and-forth.

Today `InstallationScanner` produces a structured `ScanReport`, but
`security_audit.php` renders it as HTML to a logged-in admin. A user with a
broken install often cannot log in. `.github/ISSUE_TEMPLATE/bug_report.md` asks
them to hand-type version, PHP version, DB type and web server, which they get
wrong or skip.

### A1. `includes/classes/Diagnostics/`

A report collector following the `Security/` pattern. Gathers:

- WebCalendar version, DB schema version, install method
- PHP version, SAPI, loaded extensions relevant to WebCalendar
- Database type and server version
- Config values, filtered through an allowlist
- `ScanReport` from the existing `InstallationScanner`
- Manifest verification result from `ManifestVerifier`
- Writability of the directories WebCalendar needs

Output is a value object that renders to JSON or plain text.

### A2. Redaction

Non-negotiable and tested. The report must never carry the DB password, the
install/wizard password, SMTP credentials, `cal_api_token` values, or session
identifiers. Redaction is an allowlist, not a denylist: unknown config keys are
omitted rather than included.

`tests/DiagnosticsRedactionTest.php` asserts a report built from a settings file
containing known secrets contains none of them.

### A3. Delivery

Two surfaces, one collector:

- `php bin/webcal diagnose --json` — works when the web UI does not.
- A button in `security_audit.php` that copies the same report to the clipboard.

Both ship in `release-files`.

### A4. Issue template

Replace the hand-typed Environment block in `bug_report.md` with a fenced block
and the command to produce it.

**Effort:** medium. The scanner and verifier already exist; this is collection,
redaction and two thin renderers.

---

## Pillar B — Maintenance dev loop

**Goal:** an agent proves a fix without docker-compose, a browser and screenshots.

### B1. `bin/webcal`

A dispatcher, not a framework. First line after the shebang refuses non-CLI:

```php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
```

Commands for this pillar: `diagnose`, `seed`, `reset`, `config get|set`.

`bin/webcal` ships (Pillar A needs `diagnose` in users' hands). `seed` and
`reset` refuse to run unless the target is a development database, checked
explicitly rather than by convention.

### B2. `bin/webcal seed` and `reset`

SQLite only (see Decisions). Named scenarios that produce a known database
state: a populated month,
overlapping events for conflict testing, a repeating series with exceptions, a
multi-user group with layered access. `reset` returns to empty.

This is the item that most changes how fast an agent works. Verifying a
recurrence fix currently means installing, clicking through the UI and reading a
screenshot. It should mean two commands and a diff.

### B3. `make check`

One target wrapping `tests/compile_test.sh`, phpcs, phpstan and phpunit, with
machine-readable output and a meaningful exit code. WCTNG's `composer check`
already has this shape; copy it.

### B4. Codemap

`includes/functions.php` is ~6600 lines and 160+ functions. Agents grep it from
scratch every session. A generated `docs/CODEMAPS/functions.md` mapping function
to line to one-line purpose pays for itself immediately.

Generated, not hand-written, so it cannot drift.

**Effort:** B1–B3 small. B4 small once the generator exists.

---

## Pillar C — Executable guardrails

**Goal:** a rule an agent can violate is a rule that gets violated. Rules that
run in CI do not.

`tests/WizardRemovableTest.php` is the existing precedent: it enforces that
nothing outside `wizard/` requires a file from `wizard/`, because admins are told
they may delete it. WCTNG has the same idea at larger scale
(`bin/check-sensitive-params.php`, `check-clock-injection.php`,
`check-schema-drift.php`).

Three worth adding here:

### C1. CLI-only guard — LANDED 2026-09-24

Every file in `bin/` and `tools/` must refuse the web SAPI.

All 10 `tools/*.php` were unguarded, and three ship in `release-files`:
`send_reminders.php`, `reload_remotes.php` and `convert_passwords.php`. The last
rewrites stored password hashes and was reachable over HTTP on any server where
`AllowOverride None` neutralizes `.htaccess`, which is the Debian and Ubuntu
default.

Landed: a `PHP_SAPI` check answering 403 at the top of all 10 scripts,
`tests/CliOnlyGuardTest.php` (presence plus ordering, with the ordering case
verified against a deliberately misplaced guard), and the removal of the
`wget`-over-HTTP reminder cron from `docs/admin-guide.md`.

Follow-up, same day: hosts with no shell lost their only way to run reminders,
so an admin-configurable web trigger was added for `send_reminders.php` alone.
It stays shut until an administrator generates a token, stores only the token's
SHA-256 hash, and logs each triggered run. The other nine scripts remain
unconditionally command-line only.

### C2. AI-signal lint

Port the mechanical checks from `AI-SIGNALS.md`: second-person comments, bookend
section markers, trivial docblocks that restate the signature, and the buzzword
list. Advisory at first so the existing backlog does not block CI, enforcing on
changed files after a cleanup pass.

### C3. Release manifest completeness

A new runtime file that is not in `release-files` is absent from the ZIP. That
caused #667. A test that walks runtime requires and asserts each target is
listed would have caught it.

**Effort:** small each. High leverage per line.

---

## Pillar D — Bounded modernization

Only where it serves support or maintenance.

### D1. Event write path

`mcp.php` forked event-creation logic from `edit_entry_handler.php`. Two copies
exist and have already drifted; `tests/McpAddEventRaceConditionTest.php` and
`McpRruleValidationTest.php` exist because of bugs in the fork. A CLI that writes
events would make three.

Extract the shared write path into `includes/classes/Event/` following the
`Security/` pattern. `mcp.php` and `bin/webcal` call it. `edit_entry_handler.php`
migrates when someone is already working there, not as a separate project.

Method names and argument shapes mirror `webcalendar-core`'s `EventService` where
that costs nothing, so a later port is mechanical rather than a redesign.

### D2. Everything new follows `includes/classes/`

No new code in `functions.php`. This is the only rule that makes the codebase
smaller over time instead of larger.

**Effort:** D1 medium and the only item here with real regression risk. It needs
tests before the refactor, not after.

---

## Sequence

| Order | Item | Why here |
|-------|------|----------|
| 1 | B1 `bin/webcal` skeleton + SAPI guard | Everything else hangs off it |
| 2 | ~~C1 CLI-only guard test~~ | **Done 2026-09-24.** Landed ahead of B1, since the exposure was live |
| 3 | ~~A1–A4 diagnostics~~ | **Done 2026-09-24.** Shipped with a minimal `bin/webcal.php` rather than the full B1 dispatcher |
| 4 | B3 `make check` | Makes the rest verifiable |
| 5 | B2 seed/reset | Biggest maintenance win, wants `make check` first |
| 6 | C3 manifest completeness | Cheap, prevents a repeat of #667 |
| 7 | B4 codemap | Independent, do when convenient |
| 8 | D1 event write path | Last; needs tests and the most care |
| 9 | C2 AI-signal lint | Needs a cleanup pass to be enforceable |

Items 1–4 are worth doing regardless of what happens with WCTNG. Items 8–9 are
worth reconsidering if legacy's timeline shortens.

---

## Future work

Considered and deferred. Recorded so the reasoning is not relitigated.

### Structured error envelope

Legacy prints HTML on error, which agents cannot parse. A `{code, message, hint,
docs_url}` envelope would help. Deferred because it touches every error path in
98 root PHP files, and the support value is largely captured by Pillar A at a
fraction of the cost. Revisit if diagnostics prove insufficient.

### MCP expansion

Legacy `mcp.php` covers events, availability and conflicts. Missing: groups,
categories, access control, reminders, tasks, journals, ICS import/export. Also
absent: MCP *resources* (so clients can attach calendar context without a tool
call), MCP *prompts*, and tool annotations (`readOnlyHint`/`destructiveHint`) that
would let clients auto-approve reads.

Deferred because WCTNG's `McpController` is where MCP investment compounds.
Reconsider for any tool legacy users cannot get elsewhere.

Worth noting in the other direction: legacy's MCP has `get_user_info`,
`check_conflicts` and `add_recurring_event`, which WCTNG lacks. That is a
finding for WCTNG, not work for this repo.

### Repo hygiene

`git status` shows ~40 untracked files at root (`all-files`, `all-files2`,
`debug.txt`, `files`, `git-files`, `.php-cs-fixer.cache`, several
`settings.php.*`). Every agent session reads that noise. A `.gitignore` pass is
nearly free and was left out of the sequence only because it is not a
prerequisite for anything.

Also: root holds 98 PHP files. A `docs/` move would help agents orient, and
would be disruptive to anyone carrying local patches.

### Observability

`bin/webcal log tail --json` over `webcal_entry_log`, plus PHP error log
surfacing. Partially covered by Pillar A. Revisit if support traffic shows
diagnostics alone does not explain failures.

### Config introspection

`includes/default_config.php` defines 164 keys, with their descriptions
embedded in `admin.php`'s UI markup. A machine-readable catalog (key, type,
default, description, since-version) would let an agent answer configuration
questions without reading `admin.php`. Real value, but it needs the descriptions
extracted from presentation first, which is its own project.

---

## Decisions

### `seed` targets SQLite only (2026-09-24)

`bin/webcal seed` and `reset` support SQLite and nothing else. It covers the
agent verification loop, and cross-backend behaviour is already covered by
`tests/CrossDatabaseCompatibilityTest.php` and the wizard CI runs against MySQL
and PostgreSQL. Revisit only if a bug class shows up that SQLite cannot
reproduce.

### No checksum on the diagnostics report (2026-09-24)

Rejected. A checksum cannot do the job it appears to do, and costs something real.

The report is generated on the user's machine, so any signing key ships with the
release and is available to them. That makes a checksum an integrity check
against accident, never an authenticity check against intent.

Against accident it is redundant: the report is JSON, so a truncated or mangled
paste fails to parse and is detected already. What remains is a user editing a
value and leaving valid JSON, which a plain digest catches only if they do not
recompute it.

The cost is not theoretical. Users legitimately redact hostnames, internal
addresses and organization names before pasting into a public issue. A checksum
reclassifies every one of those as tampering, and each becomes a false alarm to
adjudicate. It also adds a field to paste correctly and needs a
`diagnose --verify` path to mean anything.

Instead:

- `schema_version` and `generated_at` are included in every report.
- A terminal `report_complete` field marks a fully written report, for anyone not
  willing to rely on parse success.
- `ManifestVerifier` output already carries a signature-backed statement about
  whether the installation itself is unmodified, verified against the release
  public key. That is the tampering question that matters for support, and it is
  already part of A1.

The only approach that beats a pasted report is having the instance transmit it
directly. That is a phone-home, with the consent and privacy obligations that
implies, and it is not appropriate here.
