# Command Line

WebCalendar ships a single command line entry point, `bin/webcal.php`. It
loads the same configuration the web pages load, so it works against the
installation it sits in with no arguments to point it at a database.

```bash
php bin/webcal.php help
```

Run it as a user who can read `includes/settings.php`. Anything that writes
should be run as the web server user, or the files it creates will not be
readable by the application afterwards.

## Table of Contents

- [Recovering access](#recovering-access)
- [Reporting a problem](#reporting-a-problem)
- [Settings](#settings)
- [Backup and upgrade](#backup-and-upgrade)
- [Calendar data](#calendar-data)
- [Scheduled work](#scheduled-work)
- [Development scenarios](#development-scenarios)

## Recovering access

### An administrator is locked out

Passwords are stored as bcrypt hashes, so no hand-written `UPDATE` recovers
an account.

```bash
php bin/webcal.php user reset-password --login=admin
```

The new password is generated and printed once. To choose it yourself, pipe
it in:

```bash
echo 'the-new-password' | php bin/webcal.php user reset-password \
  --login=admin --stdin
```

A password is never accepted as an argument, because arguments appear in
`ps` output and in shell history.

Installations that authenticate through LDAP, IMAP, NIS or Joomla are
refused: the password column those installations would change is not what
login checks.

### Which accounts exist

```bash
php bin/webcal.php user list
```

Each account is listed with its login, full name, whether it is an
administrator, whether it is enabled, and the address it receives mail at. On an
installation using an external authentication source the command says so,
since the accounts it can see may not be the full set.

## Reporting a problem

```bash
php bin/webcal.php diagnose
php bin/webcal.php diagnose --json
```

Prints the WebCalendar and PHP versions, the loaded extensions, the
database type and server version, which directories are writable, and the
relevant configuration. Attach it to a bug report rather than typing the
same facts by hand.

No passwords, tokens, host names or email addresses appear in the report.
See [Troubleshooting](troubleshooting.md) for what to do with it.

It runs on a broken installation: a missing `settings.php` produces a
report rather than a redirect to the wizard.

## Settings

These read and write `webcal_config`, the table behind **Admin >
Settings**. They do not touch `includes/settings.php`, which holds the
database credentials.

```bash
php bin/webcal.php config list
php bin/webcal.php config get SEND_EMAIL
php bin/webcal.php config set SEND_EMAIL N
```

`list` hides secret values; `get` prints one in full, since it was asked
for by name.

`set` writes the way **Admin > Settings** does. The point of having it on
the command line is the case where a setting is itself what stops an
administrator reaching that page.

An unrecognised name is refused with near matches suggested — a typed
`SEND_EMAILS` would otherwise store a row nothing reads.
`WEBCAL_PROGRAM_VERSION` is refused outright, because the installation
wizard reads it to decide which upgrades are still needed. `--force` lifts
both refusals.

## Backup and upgrade

### Dump the database

```bash
php bin/webcal.php db dump > backup.sql
php bin/webcal.php db dump --output=backup.sql
```

Hands the work to `mysqldump`, `pg_dump` or `sqlite3` according to the
configured backend. Credentials come from the existing configuration and
never reach the argument list, so they do not appear in `ps` output. Only
the `webcal_*` tables are included, since the database may be shared with
another application. `--output` creates the file readable only by its
owner.

### Is an upgrade pending

```bash
php bin/webcal.php db check
```

Makes the same comparison the application makes when it decides whether to
send a browser to the wizard, and applies nothing. The answer is the exit
status, for a deployment script to read:

| Exit | Meaning |
|------|---------|
| 0 | The schema matches the program |
| 1 | An upgrade is pending — run the wizard |
| 2 | The question could not be settled |

Status 2 covers a missing version row and a database *ahead* of the code,
which happens when an older WebCalendar is deployed over a schema another
copy has already upgraded. If `wizard/` has been removed, as the security
audit suggests, the answer is the conservative one and says so.

The upgrade itself is still the wizard's job. See the
[Upgrade Guide](upgrade-guide.md).

## Calendar data

### Export

```bash
php bin/webcal.php export --login=NAME > calendar.ics
php bin/webcal.php export --login=NAME --output=calendar.ics
php bin/webcal.php export --login=NAME --from=20260101 --to=20261231
```

Writes a calendar as iCalendar. Every date is included unless a range is
given. `--include-layers`, `--include-deleted` and `--category=ID` match
the options on the Export page, and the file produced is the file that page
produces.

### Import

```bash
php bin/webcal.php import --login=NAME --file=calendar.ics
```

Reads an `.ics` or `.vcs` file and reports how many events it imported,
marked deleted, skipped as conflicts and failed on.

Importing the same file twice updates the events the first import created
rather than duplicating them. `--overwrite` also marks what an earlier
import left behind as deleted. `--category=ID` assigns a category to
everything imported.

See [Import & Export](import-export.md) for the formats and their limits.

## Scheduled work

```bash
php bin/webcal.php reminders send
php bin/webcal.php remotes refresh
```

`reminders send` sends the email reminders that are due, and
`remotes refresh` reloads every calendar subscribed to a remote URL. Both
run `tools/send_reminders.php` and `tools/reload_remotes.php`, so existing
crontab entries naming those scripts keep working unchanged and there is
one discoverable entry point for the same work.

Both send mail or fetch remote URLs on a live installation, which is worth
remembering before running either by hand on a calendar with real users.

### Test the mail configuration

```bash
php bin/webcal.php email test --to=you@example.com
```

Prints the mailer, server and sender it is about to use, sends one message,
and reports the mail server's own refusal when there is one. That last part
is the reason to prefer it to sending a reminder as a test: a rejection at
the SMTP conversation says which of the two ends is wrong.

## Development scenarios

These exist to populate a calendar for development and are not part of an
installation: they are excluded from the release, so they are present only
in a git checkout.

```bash
php bin/webcal.php seed --list
php bin/webcal.php seed --scenario=month --force
php bin/webcal.php reset --force
```

`seed` loads a named scenario — a populated month, overlapping events for
conflict work, a repeating series with exception dates, or a group of users
with layers. `reset` empties the calendar without uninstalling it.

Both refuse unless the database is SQLite **and** `--force` is given, or
`WEBCAL_ALLOW_SEED=1` is set. Development mode is deliberately not accepted
as consent, because live installations set it too.

For a disposable installation to run them against, see the `make sandbox`
target described in [Docker Deployment](docker.md).
