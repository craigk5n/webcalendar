# Import and Export

WebCalendar supports importing and exporting calendar data in several
formats, with iCalendar (`.ics`) as the primary standard.

## Table of Contents

- [Export](#export)
- [Import](#import)
- [Remote Subscriptions](#remote-subscriptions)
- [Programmatic Access](#programmatic-access)

## Export

Access export at `export.php` from the navigation menu.

### Supported Export Formats

| Format | Extension | Description |
|--------|-----------|-------------|
| iCalendar | `.ics` | Standard calendar format (RFC 5545). Recommended. |
| vCalendar | `.vcs` | Older calendar standard. Use for legacy applications. |
| Pilot-datebook CSV | `.csv` | Palm Pilot CSV format. Legacy. |
| Pilot-datebook text | `.txt` | Palm Pilot text format. Legacy. |

### Export Options

- **Date range** — export events within a specific period
- **Which calendar** — export your events or another user's (if
  permitted)
- **Include repeating events** — expand recurrence rules or keep as-is

### Subscribable iCalendar Feed

WebCalendar publishes iCalendar feeds that external applications can
subscribe to. The feed URL format is:

```
https://yourserver.com/webcalendar/publish.php?user=USERNAME
```

This can be added to Google Calendar, Apple Calendar, Outlook, or any
application that supports iCalendar subscriptions.

## Import

Access import at `import.php` from the navigation menu.

### Supported Import Formats

- **iCalendar** (`.ics`) — the primary import format
- Files from Outlook, Google Calendar, Apple Calendar, and other
  standards-compliant applications

### Import Process

1. Click **Import** in the navigation.
2. Select the file to upload.
3. Choose whether to overwrite existing events or add as new.
4. Review the import summary.

### Import Notes

- Events are assigned to the currently logged-in user.
- Repeating events with recurrence rules (RRULE) are supported.
- Timezone information in the iCalendar file is respected.
- Attachments in iCalendar files are not imported.
- Import data is logged in the `webcal_import` and `webcal_import_data`
  tables for reference.

## Remote Subscriptions

WebCalendar can subscribe to external iCalendar feeds and display them
on your calendar. This is configured per-user in preferences.

Remote calendar data can be refreshed manually or via the cron tool:

```bash
php tools/reload_remotes.php
```

## Programmatic Access

For automated data access, see:

- [MCP Server](mcp-server.md) — AI assistant integration with
  `list_events`, `search_events`, and `add_event` tools
