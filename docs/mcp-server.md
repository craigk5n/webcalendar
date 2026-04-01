# MCP Server

WebCalendar includes a [Model Context Protocol](https://modelcontextprotocol.io/)
(MCP) server that enables AI assistants to interact with calendar data.

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Setup](#setup)
- [Authentication](#authentication)
- [Available Tools](#available-tools)
- [Transport Modes](#transport-modes)
- [Client Configuration Examples](#client-configuration-examples)
- [CORS Configuration](#cors-configuration)
- [Admin Settings](#admin-settings)
- [Use Cases](#use-cases)
- [Troubleshooting](#troubleshooting)

## Overview

The MCP server (`mcp.php`) exposes calendar operations as tools that AI
assistants can call. It supports both local (STDIO) and remote (HTTP)
transport, allowing integration with desktop AI apps, CLI tools, and
web-based AI services.

## Requirements

- WebCalendar v1.9.13 or later
- PHP 8.0+
- MCP SDK package (`mcp/sdk` — included via Composer)
- `MCP_SERVER_ENABLED` set to `Y` in admin settings

## Setup

### 1. Enable in Admin Settings

Log in as an admin, go to **Admin** > **System Settings**, and set:

- **MCP_SERVER_ENABLED** = `Y`
- **MCP_RATE_LIMIT** = max requests per minute per user

### 2. Generate an API Token

Each user generates their own token:

1. Go to **Preferences** (`pref.php`).
2. In the **MCP API Token** field, enter a token string (or generate
   one — any unique string works).
3. Save preferences.

The token is stored in the `cal_api_token` column of `webcal_user`.
To revoke access, clear the token field.

### 3. Configure Your AI Assistant

See [Client Configuration Examples](#client-configuration-examples)
below for specific applications.

## Authentication

The MCP server accepts tokens via multiple methods (checked in order):

| Method | Transport | Example |
|--------|-----------|---------|
| `MCP_TOKEN` env var | STDIO | `MCP_TOKEN=abc123 php mcp.php` |
| `X-MCP-Token` header | HTTP | `X-MCP-Token: abc123` |
| `Authorization: Bearer` header | HTTP | `Authorization: Bearer abc123` |
| `?token=` query parameter | HTTP | `mcp.php?token=abc123` |

The token is looked up in `webcal_user.cal_api_token` to identify the
user. All operations execute as that user with their permissions.

## Available Tools

### list_events

List events within a date range.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | string | Yes | Start date (YYYYMMDD) |
| `end_date` | string | Yes | End date (YYYYMMDD) |

### get_user_info

Get information about the authenticated user.

**Parameters:** None

**Returns:** Login, name, email.

### search_events

Search events by keyword.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `keyword` | string | Yes | Search term |
| `limit` | integer | No | Max results (default 50) |

### add_event

Create a new non-repeating event.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | Yes | Event title |
| `date` | string | Yes | Event date (YYYYMMDD) |
| `description` | string | No | Event description |
| `location` | string | No | Event location |
| `duration` | integer | No | Duration in minutes |

**Returns:** Created event ID.

## Transport Modes

### STDIO (Local)

The AI assistant launches `php mcp.php` as a subprocess and
communicates via stdin/stdout using JSON-RPC. Best for desktop apps
and CLI tools running on the same machine as WebCalendar.

```bash
MCP_TOKEN=your-token php mcp.php
```

### HTTP (Remote)

The MCP server accepts HTTP POST requests with JSON-RPC payloads.
This allows AI services running anywhere to access your calendar.

```bash
curl -X POST https://yourserver.com/webcalendar/mcp.php \
  -H "Content-Type: application/json" \
  -H "X-MCP-Token: your-token" \
  -d '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"get_user_info","arguments":{}},"id":1}'
```

Accessing `mcp.php` via GET in a browser displays a status page
confirming the server is running.

## Client Configuration Examples

### Claude Desktop

Add to `~/Library/Application Support/Claude/claude_desktop_config.json`
(macOS) or the equivalent config file on your platform:

```json
{
  "mcpServers": {
    "webcalendar": {
      "command": "php",
      "args": ["/path/to/webcalendar/mcp.php"],
      "env": {
        "MCP_TOKEN": "your-api-token"
      }
    }
  }
}
```

Restart Claude Desktop after saving. The calendar tools will appear
in Claude's tool list.

### Claude Code (CLI)

Add to your project's `.mcp.json` or `~/.claude/mcp.json`:

```json
{
  "mcpServers": {
    "webcalendar": {
      "command": "php",
      "args": ["/path/to/webcalendar/mcp.php"],
      "env": {
        "MCP_TOKEN": "your-api-token"
      }
    }
  }
}
```

### HTTP Client (Remote Access)

For AI services that support HTTP-based MCP servers:

```json
{
  "mcpServers": {
    "webcalendar": {
      "url": "https://yourserver.com/webcalendar/mcp.php",
      "headers": {
        "X-MCP-Token": "your-api-token"
      }
    }
  }
}
```

Alternative using Bearer authentication:

```json
{
  "mcpServers": {
    "webcalendar": {
      "url": "https://yourserver.com/webcalendar/mcp.php",
      "headers": {
        "Authorization": "Bearer your-api-token"
      }
    }
  }
}
```

Note: Some Apache configurations strip the `Authorization` header.
If Bearer auth doesn't work, use `X-MCP-Token` instead.

### Custom Integration

Any application that speaks MCP can integrate. The server implements
the standard MCP JSON-RPC protocol. For STDIO, launch `php mcp.php`
as a subprocess with `MCP_TOKEN` in the environment. For HTTP, POST
JSON-RPC messages to `mcp.php`.

## CORS Configuration

For HTTP transport from browser-based clients, configure allowed
origins in `webcal_config`:

| Setting | Description |
|---------|-------------|
| `MCP_CORS_ORIGINS` | Allowed origins (`*` for any, or specific domain) |

The server returns appropriate `Access-Control-Allow-Origin`,
`Access-Control-Allow-Methods`, and `Access-Control-Allow-Headers`
headers, and handles `OPTIONS` preflight requests.

## Admin Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `MCP_SERVER_ENABLED` | `Y` to enable, `N` to disable | `N` |
| `MCP_RATE_LIMIT` | Max requests per minute per user | — |
| `MCP_CORS_ORIGINS` | Allowed CORS origins for HTTP transport | — |

## Use Cases

### Personal Productivity

Ask your AI assistant natural-language questions about your calendar:

- "What meetings do I have tomorrow?"
- "Find all events with 'review' in the title"
- "Add a dentist appointment next Tuesday at 3pm for 1 hour"
- "What's on my calendar for the rest of this week?"

### Team Coordination

If the AI has access to multiple users' tokens (or a shared calendar):

- "What events are on the team calendar next week?"
- "Schedule a standup meeting for tomorrow at 9am"
- "Search for all offsite events this quarter"

### Automated Workflows

Use the HTTP transport to integrate calendar operations into scripts
and automation:

```bash
# List this week's events from a shell script
curl -s -X POST https://cal.example.com/webcalendar/mcp.php \
  -H "Content-Type: application/json" \
  -H "X-MCP-Token: $MCP_TOKEN" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
      "name": "list_events",
      "arguments": {
        "start_date": "'$(date +%Y%m%d)'",
        "end_date": "'$(date -d "+7 days" +%Y%m%d)'"
      }
    },
    "id": 1
  }'
```

### Daily Briefing

Combine with an AI assistant to generate a morning summary:

- "Give me a briefing of today's events with locations and times"
- "Are there any scheduling conflicts this week?"

## Troubleshooting

### "API token required" error

- Verify the token is set: check **Preferences** for the MCP API Token
  field.
- For STDIO: ensure `MCP_TOKEN` is in the environment.
- For HTTP: check the header is being sent (`X-MCP-Token` or
  `Authorization: Bearer`).

### "MCP server is not enabled" error

An admin must set `MCP_SERVER_ENABLED` to `Y` in System Settings.

### Apache strips Authorization header

Some Apache configurations (especially with CGI/FPM) strip the
`Authorization` header. Workarounds:

1. Use `X-MCP-Token` header instead (recommended).
2. Add to `.htaccess`:
   ```apache
   SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
   ```

### Rate limiting

If you receive rate limit errors, ask an admin to increase
`MCP_RATE_LIMIT` in System Settings, or reduce request frequency.

### No events returned

- Verify the token user has events in the requested date range.
- Check that dates use `YYYYMMDD` format (e.g., `20260401`).
- The token user's permissions determine what events are visible.
