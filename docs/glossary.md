# WebCalendar Glossary

This glossary defines terms and concepts used within WebCalendar.

### Activity Log
A summary of recent updates to calendar data, including event creation, updates, and approval status changes.

### Assistant
A calendar user that has been designated by another calendar user (the [Boss](#boss)) to help manage their calendar. Assistants can typically view and edit the Boss's calendar.

### Boss
A calendar user that has designated another calendar user (the [Assistant](#assistant)) to help manage their calendar.

### External User
A calendar participant that does not have a calendar user account. External users can be invited to events and can receive email notifications and reminders if an email address is provided.

### Group
A mechanism for organizing users into teams. Groups can be used for shared calendar access and simplified permission management.

### Layer
A feature that allows a user to overlay another user's calendar (or a [NonUser Calendar](#nonuser-calendar)) on top of their own. This allows viewing multiple calendars simultaneously in the standard day, week, and month views.

### Multi-User Mode
A configuration where WebCalendar supports multiple individual user accounts. Users must typically log in to access their own calendar and view others (subject to permissions).

### NonUser Calendar
A calendar entity that is not associated with a specific user login. Typically used for shared resources (e.g., conference rooms, equipment) or for common event sets (e.g., company holidays) that users can add as [Layers](#layer).

### Notification
An email message sent immediately when an event is added, removed, or updated on a user's calendar by another person.

### Preferred View
The default calendar view (Day, Week, Month, or Year) presented to a user immediately after logging in. This is configurable in User Preferences.

### Public Access
A system-wide setting that allows anonymous (unauthenticated) users to view a designated public calendar.

### Reminder
An email message sent a specified amount of time before an event to remind participants. Reminders require the `send_reminders.php` script to be configured as a cron job or scheduled task.

### Single-User Mode
A configuration where WebCalendar manages only one calendar and does not require a login. Anyone accessing the installation has full privileges.

### User Access Control (UAC)
An optional, granular permission system that allows administrators to control exactly which functions and which other users' calendars each user can access.

### View
A customized page that presents the combined events of multiple selected users in a single display (different from [Layers](#layer), which are personal overlays).
