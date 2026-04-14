# WebCalendar User Guide

**Version 1.9.16**

## Table of Contents

- [Calendar Views](#calendar-views)
- [Working with Events](#working-with-events)
  - [Creating and Editing Events](#creating-and-editing-events)
  - [Viewing Event Details](#viewing-event-details)
  - [Event Types](#event-types)
  - [Repeating Events](#repeating-events)
  - [Categories](#categories)
  - [Participants](#participants)
- [Preferences](#preferences)
- [Layers](#layers)
- [Custom Views](#custom-views)
- [Assistant and Boss Mode](#assistant-and-boss-mode)
- [Public Access](#public-access)
- [Nonuser Calendars](#nonuser-calendars)
- [Remote Subscriptions](#remote-subscriptions)
- [Import and Export](#import-and-export)
- [Notifications and Reminders](#notifications-and-reminders)
- [Language Support](#language-support)

---

## Calendar Views

WebCalendar provides several ways to view your calendar:

- **Month view** -- Displays an entire month at a glance. This is the default view for most users.
- **Week view** -- Shows a single week with more detail per day.
- **Day view** -- Focuses on a single day, showing events in a time-slot layout.
- **Year view** -- Provides a high-level overview of the full year.
- **Agenda/list view** -- Lists upcoming events in a compact, chronological format.

Switch between views using the navigation links at the top of the calendar.

## Working with Events

### Creating and Editing Events

To create a new event, click on a time slot or date in any calendar view. This opens the event editor where you can set:

- Title and description
- Date and time (or mark as all-day)
- Duration
- Location
- Category
- Participants
- Repeat rules
- Reminders

To edit an existing event, click on it to view its details, then select the edit option.

### Viewing Event Details

Click any event on the calendar to see its full details, including description, time, location, participants, and their response status.

### Event Types

WebCalendar supports four event types:

| Type | Code | Description |
|------|------|-------------|
| Event | E | A standard calendar event |
| Repeating | M | An event with a recurrence rule |
| Task | T | A to-do item with optional due date |
| Journal | J | A journal or note entry |

### Repeating Events

Events can be set to repeat on a schedule:

- **Daily** -- Every N days
- **Weekly** -- Every N weeks on selected days
- **Monthly** -- Every N months on a specific date or day-of-week
- **Yearly** -- Every N years

Exceptions can be added to skip specific dates in a repeating series.

### Categories

Categories allow you to color-code events for visual organization. Each category has a name and an associated color. Assign a category when creating or editing an event.

### Participants

Events can include multiple participants. Each participant has a response status:

| Status | Code | Meaning |
|--------|------|---------|
| Accepted | A | Participant confirmed attendance |
| Completed | C | Task marked as completed |
| Deleted | D | Entry removed for this participant |
| Pending | P | Awaiting response |
| Rejected | R | Participant declined |
| Waiting | W | On a waiting list |

## Preferences

Open the preferences page to customize your WebCalendar experience. Available settings include:

- **Timezone** -- Set your local timezone so events display at the correct time.
- **Language** -- Choose from over 30 supported languages.
- **Display settings** -- Configure which view loads by default, how many hours to show, fonts, and colors.
- **Work hours** -- Define your working hours so the calendar highlights them appropriately.

## Layers

Layers let you overlay other users' calendars on top of your own. When a layer is active, that user's events appear on your calendar in a distinct color. This is useful for seeing colleagues' availability without leaving your own calendar view.

## Custom Views

Custom views allow you to create multi-user calendar displays. For example, you can build a view that shows the schedules of everyone on your team side-by-side or merged into a single calendar.

## Assistant and Boss Mode

WebCalendar supports delegated calendar management:

- A **boss** can designate an **assistant** who is authorized to manage the boss's calendar.
- The assistant can create, edit, and respond to events on behalf of the boss.

This is configured through the user management settings.

## Public Access

If enabled by the site administrator, public access allows anonymous visitors to view a shared calendar without logging in. Public access is read-only and shows only events that have been designated as public.

## Nonuser Calendars

Nonuser calendars represent shared resources rather than people -- for example, conference rooms, projectors, or vehicles. Users can book these resources by adding them as participants to an event.

## Remote Subscriptions

WebCalendar can subscribe to external iCalendar feeds. Remote subscriptions pull events from an external URL and display them on your calendar. This is useful for integrating holidays, shared team calendars hosted elsewhere, or any standard iCal (.ics) feed.

## Import and Export

### Importing Events

Use the import feature to load events from iCalendar (.ics) files. Navigate to the import page, select your file, and choose which calendar to import into.

### Exporting Events

Export your calendar data in the following formats:

- **iCalendar (.ics)** -- The modern standard, compatible with most calendar applications.
- **vCalendar** -- An older format for legacy application compatibility.

## Notifications and Reminders

WebCalendar can send email notifications when:

- A new event is added to your calendar.
- An event you are participating in requires approval.
- An event is updated or cancelled.

Reminders can be configured per event. Set a reminder to receive an email notification at a specified time before the event starts.

## Language Support

WebCalendar includes translation files for over 100 languages. Approximately 35 of these are human-contributed translations with substantial coverage; the remainder were generated via AI-assisted bulk translation and may contain inaccuracies. To change your language, go to Preferences and select your preferred language from the dropdown. The entire interface -- menus, labels, and system messages -- will display in the selected language.
