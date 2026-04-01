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

## Remote Calendars (Inbound)

WebCalendar can consume external iCalendar or hCalendar feeds and
display them on your calendar. This works through nonuser calendars:

1. An admin enables **Allow remote calendars** in System Settings.
2. Under **Admin** > **NonUser Calendars**, create a nonuser calendar
   and provide the remote iCalendar URL.
3. Users add the nonuser calendar as a **layer** to see the remote
   events on their own calendar.

Remote calendars can be refreshed manually from the admin interface
or automatically via cron:

```bash
# Reload all remote calendars every hour
0 * * * * /usr/bin/php /path/to/webcalendar/tools/reload_remotes.php
```

The calendar format (iCalendar or hCalendar) is auto-detected from the
URL and content.

## Remote Subscriptions (Outbound)

WebCalendar can publish calendar data for external applications to
subscribe to. An admin must enable **Allow remote subscriptions** in
System Settings.

- **`publish.php`** — provides a read-only iCalendar feed that
  external calendar clients can subscribe to. See
  [User Guide: Remote Subscriptions](user-guide.md#remote-subscriptions)
  for the feed URL format.
- **`icalclient.php`** — provides two-way iCalendar synchronization
  with desktop calendar applications.

## Programmatic Access

For automated data access, see:

- [MCP Server](mcp-server.md) — AI assistant integration with
  `list_events`, `search_events`, and `add_event` tools
