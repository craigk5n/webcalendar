# WebCalendar v1.9.16 Administration Guide

## Table of Contents

- [Admin Interface](#admin-interface)
- [User Management](#user-management)
- [Groups](#groups)
- [Access Control](#access-control)
- [Authentication Systems](#authentication-systems)
- [System Settings](#system-settings)
- [User Preferences](#user-preferences)
- [Email Reminders](#email-reminders)
- [Key Admin Settings](#key-admin-settings)
- [LDAP Configuration](#ldap-configuration)
- [Configuring as an Event Calendar](#configuring-as-an-event-calendar)
- [Embedding Events on External Sites](#embedding-events-on-external-sites)
- [Custom Event Fields (Site Extras)](#custom-event-fields-site-extras)
- [Backup and Restore](#backup-and-restore)

---

## Admin Interface

The administration interface is located at `admin.php` and is accessible only to users with admin privileges. From this interface, administrators can manage system-wide settings, users, groups, and access controls.

## User Management

Administrators create and manage user accounts through the admin interface. Users can be assigned to one or more groups for shared calendar access and permission management.

User accounts are stored in the `webcal_user` table when using database (native) authentication.

## Groups

Groups allow administrators to organize users for shared calendar access. Groups are managed through two database tables:

- `webcal_group` -- defines each group
- `webcal_group_user` -- maps users to groups

Administrators can create groups, add or remove members, and assign group-level permissions through the admin interface.

## Access Control

WebCalendar provides two levels of access control:

**Function-level access** (`webcal_access_function` table): Controls access to 28 individual application functions (UAC). Administrators can grant or restrict specific capabilities per user.

**Calendar-level access** (`webcal_access_user` table): Controls which users can view or manage other users' calendars.

Both levels are configured through the admin interface.

## Authentication Systems

The authentication system is selected during installation (via the wizard) and stored in `includes/settings.php` as the `$user_inc` variable. The following authentication backends are available:

| Setting Value | Backend | Description |
|---|---|---|
| `user.php` | Database/Native | Authenticates against the `webcal_user` table. Default. |
| `user-ldap.php` | LDAP/Active Directory | Authenticates against an LDAP or Active Directory server. |
| `user-imap.php` | IMAP | Authenticates against an IMAP mail server. |
| `user-nis.php` | NIS/Yellow Pages | Authenticates against a NIS directory. |
| `user-app-joomla.php` | Joomla | Integrates with Joomla user management. |

To change the authentication backend after installation, update the `$user_inc` value in `includes/settings.php` or re-run the installation wizard.

## LDAP Configuration

To use LDAP authentication:

1. Verify PHP has the `ldap` extension: `php -m | grep ldap`
2. Set `$user_inc = 'user-ldap.php';` in `includes/settings.php`
3. Configure LDAP variables in `includes/auth-settings.php`

### Required Settings

| Variable | Description | Example |
|----------|-------------|---------|
| `$ldap_server` | LDAP server hostname or IP | `'ldap.company.com'` |
| `$ldap_port` | LDAP port | `'389'` |
| `$ldap_base_dn` | Base DN for user searches | `'ou=people,dc=company,dc=com'` |
| `$ldap_login_attr` | Attribute used for login | `'uid'` (or `'cn'`) |

### Optional Settings

| Variable | Description | Default |
|----------|-------------|---------|
| `$ldap_admin_dn` | Bind DN for searching (leave empty for anonymous bind) | `''` |
| `$ldap_admin_pwd` | Bind password | `''` |
| `$ldap_admin_group_name` | DN of group with WebCalendar admin rights | `''` |
| `$ldap_admin_group_type` | Group type: `posixgroup`, `groupofnames`, or `groupofuniquenames` | `'posixgroup'` |
| `$ldap_admin_group_attr` | Group membership attribute | `'memberuid'` |
| `$ldap_user_filter` | LDAP filter to limit user search | `'(objectclass=person)'` |
| `$ldap_start_tls` | Use STARTTLS | `false` |
| `$set_ldap_version` | Set protocol version explicitly | `false` |
| `$ldap_version` | LDAP protocol version | `'3'` |

### Attribute Mapping

The `$ldap_user_attr` array in `auth-settings.php` maps LDAP attributes
to WebCalendar fields:

```php
$ldap_user_attr = [
  'uid',        // login
  'sn',         // lastname
  'givenname',  // firstname
  'cn',         // fullname
  'mail'        // email
];
```

Adjust these to match your LDAP schema.

### SSL / TLS

- **LDAPS:** Set `$ldap_server = 'ldaps://ldap.company.com';`
- **STARTTLS:** Set `$ldap_start_tls = true;` and
  `$set_ldap_version = true;` (requires LDAPv3)

### Limitations

- Group functionality in WebCalendar does not integrate with LDAP groups.
- Users cannot change their password through WebCalendar when using LDAP.
- Admin users cannot create new users through the WebCalendar interface.

## System Settings

System-wide settings are stored in the `webcal_config` table and managed through the `admin.php` interface. Changes take effect immediately.

## User Preferences

Individual user preferences are stored in the `webcal_user_pref` table. Users manage their own preferences through the preferences interface. Administrators can set default preference values that apply to new users.

## Email and Reminders

### Email Configuration

Before reminders or notifications will work, configure email in
**Admin** > **System Settings** > **Email** tab:

1. Set **Email enabled** to **Yes**.
2. Set **Default sender address** (e.g., `calendar@yourcompany.com`).
   Some mail servers reject messages where the From domain doesn't
   match the SMTP server.
3. Set **Email Mailer** to one of: PHP mail, SMTP, or sendmail.
4. If using SMTP, configure:
   - **SMTP Host name(s)** — server hostname or IP
   - **SMTP Port Number** — typically 25, 465 (SSL), or 587 (STARTTLS)
   - **SMTP Authentication** — enable if your server requires it, then
     set **SMTP Username** and **SMTP Password**

Test your configuration:

```bash
php tools/send_test_email.php
```

### Notification Types

WebCalendar sends two kinds of email:

- **Notifications** — sent immediately when events are added, updated,
  or deleted on a user's calendar. Controlled per-user in Preferences.
- **Reminders** — sent before an event at a time configured by the
  user. Requires a cron job (see below).

### Setting Up Reminder Cron Job

The reminder script checks for pending reminders and sends emails.
It requires the PHP CLI binary.

```bash
# Run every 15 minutes
*/15 * * * * /usr/bin/php /var/www/html/webcalendar/tools/send_reminders.php
```

Adjust the PHP binary path and WebCalendar installation path for your
environment. Run `which php` to find the correct path.

If you don't have PHP CLI available, you can use `wget` as an
alternative:

```bash
*/15 * * * * wget -q -O /dev/null https://yourserver/webcalendar/tools/send_reminders.php
```

### Remote Calendar Sync Cron Job

If you use remote (subscribed) iCalendar feeds via nonuser calendars,
set up a cron job to refresh them periodically:

```bash
# Reload remote calendars every hour
0 * * * * /usr/bin/php /var/www/html/webcalendar/tools/reload_remotes.php
```

## Key Admin Settings

The following categories of settings are available through the admin interface:

- **Single-user vs multi-user mode** -- Run as a personal calendar or a shared multi-user system.
- **Public access calendar** -- Allow anonymous (unauthenticated) users to view a public calendar.
- **Event approval workflow** -- Require admin approval before events appear on shared calendars.
- **Categories** -- Define event categories for organization and filtering.
- **Custom event fields (site extras)** -- Add custom fields to the event entry form. See [Custom Event Fields](#custom-event-fields-site-extras) below.
- **Nonuser calendars** -- Create calendars for rooms, resources, or other non-person entities.
- **MCP server** -- Enable or disable the Model Context Protocol server for AI assistant integration. Controlled by the `MCP_SERVER_ENABLED` and `MCP_RATE_LIMIT` settings.

## Configuring as an Event Calendar

WebCalendar can serve as a public-facing event calendar for an
organization. To set this up:

1. Log in as an admin user (default: username `admin`, password `admin`).
2. Go to **Admin** > **System Settings**.
3. Under the **Public Access** tab, set **Allow public access** to **Yes**.
4. Optionally set **Public access can add events** to **Yes** if you
   want anonymous users to submit events.
5. If anonymous submissions are enabled, set **Public access new events
   require approval** to **Yes** so an admin reviews them first.

To add events to the public calendar:

1. Log in and create an event.
2. Add **Public User** as a participant.
3. If approvals are required, go to **Unapproved Events** and approve
   the event for Public User.

Anonymous visitors access the public calendar from the login page via
the "Access public calendar" link, or directly at `index.php`.

### Adding Holidays

Import a public iCalendar (`.ics`) holiday file via **Import**. To
share holidays with all users without each importing separately:

1. Enable **Nonuser Calendars** in System Settings.
2. Create a nonuser calendar (e.g., "US Holidays") under **Admin** >
   **Users** > **NonUser Calendars**.
3. Import the holiday file into that calendar.
4. Users add the nonuser calendar as a layer to see holidays on their
   own calendar.

### Multiple Public Calendars

You cannot have multiple public calendars directly, but you can
emulate them:

- Create global **categories** and link users to category-filtered views.
- Create multiple **nonuser calendars** and enable public access viewing
  of other users' calendars. Link to specific nonuser calendars or
  create views combining them.

## Embedding Events on External Sites

WebCalendar provides two embeddable pages for displaying calendar data
on external websites via `<iframe>`.

### Upcoming Events List

`upcoming.php` displays a list of upcoming events. Embed it with:

```html
<iframe src="https://yourserver/webcalendar/upcoming.php"
  style="width: 400px; height: 400px;"></iframe>
```

**URL parameters:**

| Parameter | Description | Default |
|-----------|-------------|---------|
| `days` | Number of days ahead to search | 30 |
| `max` | Maximum events to display | 10 |
| `user` | Calendar user login to display | `__public__` |
| `cat` | Filter by category ID | (none) |

Example: `upcoming.php?days=60&max=20&user=joe`

**Configuration:** Edit variables at the top of `upcoming.php` to set
defaults for `$numDays`, `$maxEvents`, `$username`,
`$allow_user_override`, `$load_layers`, and `$display_link`.

Requires **Public Access** to be enabled unless you set
`$public_must_be_enabled = false` in the file.

### Mini Calendar

`minical.php` displays a small monthly calendar. Embed it with:

```html
<iframe src="https://yourserver/webcalendar/minical.php"
  style="width: 250px; height: 200px;"></iframe>
```

Configuration options are at the top of `minical.php`.

## Custom Event Fields (Site Extras)

WebCalendar supports adding custom fields to the event entry form.
These are configured by editing `includes/site_extras.php` and are
stored in the `webcal_site_extras` database table.

### Configuration

Edit `includes/site_extras.php` and define entries in the `$site_extras`
array. Each entry is an array with these elements:

| Index | Purpose | Example |
|-------|---------|---------|
| 0 | Unique field name (used in DB) | `"RoomLocation"` |
| 1 | Display label shown to users | `"Location"` |
| 2 | Field type constant | `EXTRA_SELECTLIST` |
| 3 | Arg 1 (type-dependent, see below) | `["Room A", "Room B"]` |
| 4 | Arg 2 (type-dependent, see below) | `0` |
| 5 | Display visibility flags | `EXTRA_DISPLAY_ALL` |

The special value `'FIELDSET'` as the first array entry wraps all
custom fields in an HTML fieldset on the edit form.

### Field Types

| Type | Description | Arg 1 | Arg 2 |
|------|-------------|-------|-------|
| `EXTRA_TEXT` | Single line text input | Input size | (unused) |
| `EXTRA_MULTILINETEXT` | Multi-line textarea | Columns | Rows |
| `EXTRA_URL` | Text input displayed as a link | Link target (`_blank`, etc.) | (unused) |
| `EXTRA_DATE` | Date picker | (unused) | (unused) |
| `EXTRA_EMAIL` | Text input displayed as mailto link | (unused) | (unused) |
| `EXTRA_USER` | Dropdown of calendar users | (unused) | (unused) |
| `EXTRA_RADIO` | Radio button group | Associative array of options | Default key |
| `EXTRA_SELECTLIST` | Selection dropdown | Array of options | 0=single, >0=multi+max size |
| `EXTRA_CHECKBOX` | Checkbox | Checked value | Default state |

### Display Visibility

Control where each field appears using bitmask flags:

| Flag | Where it displays |
|------|-------------------|
| `EXTRA_DISPLAY_POPUP` | Mouse-over popups |
| `EXTRA_DISPLAY_VIEW` | Event detail page |
| `EXTRA_DISPLAY_EMAIL` | Email notifications |
| `EXTRA_DISPLAY_REMINDER` | Reminders |
| `EXTRA_DISPLAY_REPORT` | Reports |
| `EXTRA_DISPLAY_WS` | Web services |
| `EXTRA_DISPLAY_ALL` | All of the above |

Combine flags with `|` (bitwise OR):
```php
EXTRA_DISPLAY_POPUP | EXTRA_DISPLAY_VIEW  // popups and detail page only
```

### Example

```php
$site_extras = [
  'FIELDSET',
  [
    "RoomLocation",
    "Location",
    EXTRA_SELECTLIST,
    ["None", "Room 101", "Room 102", "Conf Room 8"],
    0,
    EXTRA_DISPLAY_ALL
  ],
  [
    "NeedLunch",
    "Lunch",
    EXTRA_CHECKBOX,
    'Y',
    'Y',
    EXTRA_DISPLAY_POPUP | EXTRA_DISPLAY_VIEW
  ],
];
```

### Translation

If your installation supports multiple languages, add the display
labels to the appropriate translation files. Use
`tools/check_translation.pl` to verify completeness.

## Backup and Restore

### Database

Back up the database using the appropriate tool for your database backend:

**MySQL:**
```bash
mysqldump -u USERNAME -p DATABASE_NAME > webcalendar_backup.sql
```

**PostgreSQL:**
```bash
pg_dump -U USERNAME DATABASE_NAME > webcalendar_backup.sql
```

To restore, import the SQL dump using your database client.

### Files

Back up the following files and directories:

- `includes/settings.php` -- Database connection and authentication configuration.
- `icons/` -- Any custom icons that have been uploaded.

These files are not regenerated by the application and must be preserved separately from the database.
