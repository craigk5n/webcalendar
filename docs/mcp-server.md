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
- [Configuration](#configuration)
- [Usage Examples](#usage-examples)

## Overview

The MCP server (`mcp.php`) exposes calendar operations as tools that AI
assistants (Claude, ChatGPT, etc.) can call. It supports both local
(STDIO) and remote (HTTP) transport.

## Requirements

- WebCalendar v1.9.16 or later
- PHP 8.0+
- MCP SDK package (`mcp/sdk` via Composer)
- `MCP_SERVER_ENABLED` set to `Y` in admin settings

## Setup

### 1. Enable in Admin Settings

In the WebCalendar admin panel (`admin.php`), set:

- `MCP_SERVER_ENABLED` = `Y`
- `MCP_RATE_LIMIT` = requests per minute (default varies)

### 2. Generate API Token

Each user generates their own token in their preferences page. This
token authenticates MCP requests as that user.

### 3. Configure Your AI Assistant

For STDIO transport (local, e.g., Claude Code):

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

## Authentication

| Transport | Method |
|-----------|--------|
| STDIO | `MCP_TOKEN` environment variable |
| HTTP | `X-MCP-Token` header or `Authorization: Bearer <token>` header |

## Available Tools

### list_events

List events within a date range.

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `start_date` | string | Yes | Start date (YYYYMMDD) |
| `end_date` | string | Yes | End date (YYYYMMDD) |

### get_user_info

Get information about the authenticated user.

**Parameters:** None

**Returns:** Login, name, email.

### search_events

Search events by keyword.

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `keyword` | string | Yes | Search term |
| `limit` | integer | No | Max results (default 50) |

### add_event

Create a new (non-repeating) event.

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `name` | string | Yes | Event title |
| `date` | string | Yes | Event date (YYYYMMDD) |
| `description` | string | No | Event description |
| `location` | string | No | Event location |
| `duration` | integer | No | Duration in minutes |

**Returns:** Created event ID.

## Transport Modes

### STDIO (Local)

Run the MCP server as a subprocess. The AI assistant communicates via
stdin/stdout.

```bash
MCP_TOKEN=your-token php mcp.php
```

### HTTP (Remote)

The MCP server also accepts HTTP POST requests with JSON-RPC payloads.
CORS headers are supported for browser-based clients.

```bash
curl -X POST https://yourserver.com/webcalendar/mcp.php \
  -H "Content-Type: application/json" \
  -H "X-MCP-Token: your-token" \
  -d '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"get_user_info"},"id":1}'
```

## Configuration

Admin settings in `webcal_config`:

| Setting | Description |
|---------|-------------|
| `MCP_SERVER_ENABLED` | `Y` to enable, `N` to disable |
| `MCP_RATE_LIMIT` | Max requests per minute per user |

## Usage Examples

### Claude Code

Add to your MCP configuration:

```json
{
  "mcpServers": {
    "webcalendar": {
      "command": "php",
      "args": ["/var/www/html/webcalendar/mcp.php"],
      "env": {
        "MCP_TOKEN": "your-api-token"
      }
    }
  }
}
```

Then ask Claude: "What events do I have this week?" or "Add a meeting
with the team tomorrow at 2pm."
