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

## System Settings

System-wide settings are stored in the `webcal_config` table and managed through the `admin.php` interface. Changes take effect immediately.

## User Preferences

Individual user preferences are stored in the `webcal_user_pref` table. Users manage their own preferences through the preferences interface. Administrators can set default preference values that apply to new users.

## Email Reminders

WebCalendar can send email reminders for upcoming events. The reminder system is driven by a command-line script that should be executed on a regular schedule via cron.

**Reminder script:** `tools/send_reminders.php`

**Example cron entry** (runs every 15 minutes):

```
*/15 * * * * /usr/bin/php /var/www/html/webcalendar/tools/send_reminders.php
```

Adjust the PHP binary path and WebCalendar installation path to match your environment.

**Testing email configuration:** Use `tools/send_test_email.php` to verify that outgoing email is working correctly before relying on reminders.

## Key Admin Settings

The following categories of settings are available through the admin interface:

- **Single-user vs multi-user mode** -- Run as a personal calendar or a shared multi-user system.
- **Public access calendar** -- Allow anonymous (unauthenticated) users to view a public calendar.
- **Event approval workflow** -- Require admin approval before events appear on shared calendars.
- **Categories** -- Define event categories for organization and filtering.
- **Custom event fields (site extras)** -- Add custom fields to the event entry form.
- **Nonuser calendars** -- Create calendars for rooms, resources, or other non-person entities.
- **MCP server** -- Enable or disable the Model Context Protocol server for AI assistant integration. Controlled by the `MCP_SERVER_ENABLED` and `MCP_RATE_LIMIT` settings.

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
