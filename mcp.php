<?php
/**
 * MCP Server for WebCalendar
 *
 * This file implements a Model Context Protocol server that allows AI assistants
 * to query WebCalendar data using API tokens for authentication.
 *
 * Supports both STDIO (local) and HTTP (remote) transport.
 *
 * STDIO usage: php mcp.php (with MCP_TOKEN environment variable)
 * HTTP usage: POST requests with Content-Type: application/json
 *
 * Example MCP client configurations:
 *
 * STDIO (Claude Desktop):
 * {
 *   "mcpServers": {
 *     "webcalendar": {
 *       "command": "php",
 *       "args": ["/absolute/path/to/webcalendar/mcp.php"],
 *       "env": {
 *         "MCP_TOKEN": "your_api_token_here"
 *       }
 *     }
 *   }
 * }
 *
 * HTTP (Custom MCP Hub) - RECOMMENDED approach:
 * {
 *   "mcpServers": {
 *     "webcalendar": {
 *       "url": "https://your-domain.com/webcalendar/mcp.php",
 *       "headers": {
 *         "X-MCP-Token": "your_api_token_here"
 *       }
 *     }
 *   }
 * }
 *
 * HTTP (Alternative - may not work with all Apache configurations):
 * {
 *   "mcpServers": {
 *     "webcalendar": {
 *       "url": "https://your-domain.com/webcalendar/mcp.php",
 *       "headers": {
 *         "Authorization": "Bearer your_api_token_here"
 *       }
 *     }
 *   }
 * }
 */

/**
 * Handle CORS headers for preflight and actual requests
 */
function handleCorsHeaders() {
    $settings = load_settings();
    $cors_origins = $settings['MCP_CORS_ORIGINS'] ?? '';

    // Set basic CORS headers
    header('Access-Control-Allow-Origin: ' . ($cors_origins === '*' ? '*' : $cors_origins));
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-MCP-Token');
    header('Access-Control-Max-Age: 86400'); // 24 hours

    // For preflight requests, just return the headers
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }
}

/**
 * Handle MCP HTTP requests using custom JSON-RPC implementation
 */
function handleMcpHttpRequest($user_login) {
    // Debug: log the request
    error_log('MCP HTTP Request received. User: ' . $user_login . ', Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'none'));

    if (empty($user_login)) {
        header('Content-Type: application/json');
        echo json_encode([
            'jsonrpc' => '2.0',
            'id' => null,
            'error' => [
                'code' => -32600,
                'message' => 'Authentication required'
            ]
        ]);
        return;
    }

    header('Content-Type: application/json');

    try {
        $input = file_get_contents('php://input');
        $request = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
            throw new Exception('Invalid JSON-RPC version');
        }

        $tools = new WebCalendarMcpTools($user_login);

        // Route the request and build the JSON-RPC response. The dispatch
        // logic lives in mcp_dispatch_request() (includes/functions.php) so it
        // can be unit-tested without HTTP/STDIO transport.
        $response = mcp_dispatch_request($request, $tools);

        echo json_encode($response);

    } catch (Exception $e) {
        $error_response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'] ?? null,
            'error' => [
                'code' => -32700,
                'message' => $e->getMessage()
            ]
        ];
        echo json_encode($error_response);
    }
}

// Load minimal WebCalendar components for MCP
// Detect MCP HTTP requests
$is_mcp_http_request = $_SERVER['REQUEST_METHOD'] === 'POST' &&
                      isset($_SERVER['CONTENT_TYPE']) &&
                      strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;

$is_cors_preflight = $_SERVER['REQUEST_METHOD'] === 'OPTIONS' &&
                    isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);

// Debug logging (only in development)
if (getenv('WEBCALENDAR_DEBUG') === 'true') {
    error_log('MCP Request Debug: Method=' . $_SERVER['REQUEST_METHOD'] .
              ', Content-Type=' . ($_SERVER['CONTENT_TYPE'] ?? 'none') .
              ', is_mcp_http=' . ($is_mcp_http_request ? 'true' : 'false') .
              ', is_preflight=' . ($is_cors_preflight ? 'true' : 'false') .
              ', sapi=' . php_sapi_name());
}

// Handle CORS preflight requests
if ($is_cors_preflight) {
    handleCorsHeaders();
    exit;
}

if ($is_mcp_http_request || php_sapi_name() === 'cli') {
    // Handle MCP requests (HTTP or STDIO) - minimal WebCalendar initialization
    // Follow the pattern from upcoming.php but skip WebCalendar class to avoid file phase issues
    foreach ( [
        'config',
        'dbi4php',
        'functions',
        'translate',
      ] as $i ) {
      require_once 'includes/' . $i . '.php';
    }

    // Load MCP loader (replaces full composer autoloader for now)
    if ( file_exists ( 'includes/mcp-loader.php' ) ) {
      require_once 'includes/mcp-loader.php';
    }

    // Load and initialize configuration
    do_config();

    // Load global settings (this is needed for load_settings to work)
    load_global_settings();

    // Set CORS headers for HTTP requests
    if ($is_mcp_http_request) {
        handleCorsHeaders();
    }

    // Check if MCP is enabled
    if (!is_mcp_enabled()) {
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "Error: MCP server is not enabled\n");
            exit(1);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'MCP server is not enabled']);
            exit;
        }
    }
} else {
    // Handle regular web requests - full WebCalendar initialization
    require_once 'includes/init.php';
    // Show MCP information page
    print_header();
    echo '<h2>MCP Server</h2>';
    echo '<p>This endpoint provides MCP (Model Context Protocol) server access.</p>';
    echo '<p>For remote MCP clients, send POST requests with <code>Content-Type: application/json</code>.</p>';
    echo '<p>For local access, use: <code>php mcp.php</code> with MCP_TOKEN environment variable.</p>';
    print_trailer();
    exit;
}

// Get the API token from various sources (Bearer token, env var, query param)
$token = '';
$token_source = 'none';

// Check Authorization header for Bearer token (try multiple variations)
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ??
               $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ??
               $_SERVER['Authorization'] ??
               $_SERVER['REDIRECT_Authorization'] ??
               getenv('HTTP_AUTHORIZATION') ??
               getenv('Authorization') ?? '';

if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
    $token = trim($matches[1]); // Trim whitespace
    $token_source = 'bearer';
} elseif (preg_match('/^([a-f0-9]{64})$/i', $auth_header, $matches)) {
    // Direct token in Authorization header
    $token = trim($matches[1]);
    $token_source = 'bearer-direct';
}

// Fallback to other methods.
// NOTE: the ?token= query-string method was removed: tokens in URLs leak into
// web-server access logs, Referer headers and browser history. Use the
// Authorization / X-MCP-Token headers or the MCP_TOKEN env var instead.
if (empty($token)) {
    if (isset($_SERVER['HTTP_X_MCP_TOKEN'])) {
        $token = $_SERVER['HTTP_X_MCP_TOKEN'];
        $token_source = 'x-mcp-token';
    } elseif (getenv('MCP_TOKEN')) {
        $token = getenv('MCP_TOKEN');
        $token_source = 'env';
    }
}

// Optional debug logging. NEVER log token material or the Authorization header
// — those are credentials and routinely end up in lower-privilege log
// aggregators. Only the non-sensitive token source is logged, and only when
// WEBCALENDAR_DEBUG=true is set in the environment (it is NOT triggerable via a
// request parameter such as ?debug).
if (getenv('WEBCALENDAR_DEBUG') === 'true') {
    error_log("MCP Request: token_source='$token_source', sapi=" . php_sapi_name());
}

if (empty($token)) {
  $error_msg = 'API token required. Use Authorization: Bearer <token> header, X-MCP-Token header, or MCP_TOKEN env var';
  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, "Error: $error_msg\n");
    exit(1);
  } else {
    // For HTTP, parse the request to get the ID and return proper JSON-RPC error
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    $request_id = $request['id'] ?? null;
    header('Content-Type: application/json');
    echo json_encode([
      'jsonrpc' => '2.0',
      'id' => $request_id,
      'error' => [
        'code' => -32600,
        'message' => $error_msg
      ]
    ]);
    exit;
  }
}

// Validate token and get user
$user_login = validate_mcp_token($token);
if (!$user_login) {
  $error_msg = 'Invalid API token';
  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, "Error: $error_msg\n");
    exit(1);
  } else {
    // For HTTP, parse the request to get the ID and return proper JSON-RPC error
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    $request_id = $request['id'] ?? null;
    header('Content-Type: application/json');
    echo json_encode([
      'jsonrpc' => '2.0',
      'id' => $request_id,
      'error' => [
        'code' => -32600,
        'message' => $error_msg
      ]
    ]);
    exit;
  }
}

// Check rate limit
if (check_mcp_rate_limit($user_login)) {
  $error_msg = 'Rate limit exceeded';
  if (php_sapi_name() === 'cli') {
    fwrite(STDERR, "Error: $error_msg\n");
    exit(1);
  } else {
    // For HTTP, parse the request to get the ID and return proper JSON-RPC error
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    $request_id = $request['id'] ?? null;
    header('Content-Type: application/json');
    echo json_encode([
      'jsonrpc' => '2.0',
      'id' => $request_id,
      'error' => [
        'code' => -32603,
        'message' => $error_msg
      ]
    ]);
    exit;
  }
}

// Set up user context
$login = $user_login;
$user = $user_login;

// Load user preferences and settings
load_user_preferences();
load_global_settings();

// Import MCP SDK
if ( ! file_exists( 'includes/mcp-loader.php' ) || ! class_exists( 'Mcp\Server' ) ) {
  if ( php_sapi_name() === 'cli' ) {
    fwrite( STDERR, "Error: MCP SDK not found. Run 'composer require mcp/sdk' to install.\n" );
  } else {
    header( 'Content-Type: application/json' );
    echo json_encode( [
      'error' => 'MCP server is not available',
      'message' => 'The MCP SDK PHP package must be installed. Run: composer require mcp/sdk'
    ] );
  }
  exit( 1 );
}

require_once 'includes/mcp-loader.php';

use Mcp\Capability\Attribute\McpTool;

// Define MCP Tools
class WebCalendarMcpTools
{
    public function __construct(private string $userLogin) {}

    #[McpTool(description: 'List events for a user within a date range')]
    public function list_events(string $start_date, string $end_date): array
    {
        // Validate dates
        if (!preg_match('/^\d{8}$/', $start_date) || !preg_match('/^\d{8}$/', $end_date)) {
            return ['error' => 'Dates must be in YYYYMMDD format'];
        }

        // Events are stored in GMT but returned to the client in the user's
        // local timezone, matching what the web UI shows (see mcp_gmt_to_local).
        // A local-evening event can be stored under the next GMT day (and vice
        // versa), so widen the GMT query by one day on each side and then filter
        // to the requested range by local date.
        global $TIMEZONE;
        $tz = $TIMEZONE ?? date_default_timezone_get();
        $query_start = mcp_shift_date($start_date, -1);
        $query_end = mcp_shift_date($end_date, 1);

        // Query events
        $events = [];
        $sql = "SELECT e.cal_id, e.cal_name, e.cal_date, e.cal_time, e.cal_duration,
                       e.cal_description, e.cal_location, e.cal_priority
                FROM webcal_entry e
                INNER JOIN webcal_entry_user eu ON e.cal_id = eu.cal_id
                WHERE eu.cal_login = ? AND e.cal_date BETWEEN ? AND ?
                ORDER BY e.cal_date, e.cal_time";

        $res = dbi_execute($sql, [$this->userLogin, $query_start, $query_end]);
        if ($res) {
            while ($row = dbi_fetch_row($res)) {
                $local = mcp_gmt_to_local($row[2], $row[3], $tz);
                // Drop events the widened window pulled in that fall outside the
                // requested range once converted to the user's local date.
                if ($local['date'] < $start_date || $local['date'] > $end_date) {
                    continue;
                }
                $events[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'date' => $local['date'],
                    'time' => $local['time'],
                    'duration' => $row[4],
                    'description' => $row[5],
                    'location' => $row[6],
                    'priority' => $row[7]
                ];
            }
            dbi_free_result($res);
        }

        // Re-sort by local date/time; the widening + conversion can reorder rows
        // relative to the GMT-ordered query.
        usort($events, function ($a, $b) {
            return [$a['date'], $a['time']] <=> [$b['date'], $b['time']];
        });

        return ['events' => $events];
    }

    #[McpTool(description: 'Get basic information about the authenticated user')]
    public function get_user_info(): array
    {
        $sql = "SELECT cal_firstname, cal_lastname, cal_email FROM webcal_user WHERE cal_login = ?";
        $res = dbi_execute($sql, [$this->userLogin]);
        if ($res) {
            $row = dbi_fetch_row($res);
            dbi_free_result($res);
            return [
                'login' => $this->userLogin,
                'firstname' => $row[0] ?? '',
                'lastname' => $row[1] ?? '',
                'email' => $row[2] ?? ''
            ];
        }
        return ['error' => 'User not found'];
    }

    #[McpTool(description: 'Search events by keyword in name or description')]
    public function search_events(string $keyword, int $limit = 50): array
    {
        if (empty($keyword)) {
            return ['error' => 'Keyword is required'];
        }

        // Validate and clamp limit to reasonable range
        $limit = max(1, min(100, $limit));

        $events = [];
        $sql = "SELECT e.cal_id, e.cal_name, e.cal_date, e.cal_time, e.cal_description
                FROM webcal_entry e
                INNER JOIN webcal_entry_user eu ON e.cal_id = eu.cal_id
                WHERE eu.cal_login = ? AND (e.cal_name LIKE ? OR e.cal_description LIKE ?)
                ORDER BY e.cal_date DESC, e.cal_time DESC
                LIMIT " . (int)$limit;

        // Return times in the user's local timezone (see list_events).
        global $TIMEZONE;
        $tz = $TIMEZONE ?? date_default_timezone_get();

        $search_term = '%' . $keyword . '%';
        $res = dbi_execute($sql, [$this->userLogin, $search_term, $search_term]);
        if ($res) {
            while ($row = dbi_fetch_row($res)) {
                $local = mcp_gmt_to_local($row[2], $row[3], $tz);
                $events[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'date' => $local['date'],
                    'time' => $local['time'],
                    'description' => $row[4]
                ];
            }
            dbi_free_result($res);
        }

        // Preserve the SQL's newest-first ordering after local conversion.
        usort($events, function ($a, $b) {
            return [$b['date'], $b['time']] <=> [$a['date'], $a['time']];
        });

        return ['events' => $events, 'total' => count($events)];
    }

    #[McpTool(description: 'Add a new basic event (no repeating), timed or untimed')]
    public function add_event(string $name, string $date, string $time = '-1', string $description = '', string $location = '', int $duration = 0): array
    {
        // Check if write access is enabled
        if (!is_mcp_write_enabled()) {
            return ['error' => 'MCP write access is not enabled'];
        }

        // Validate date
        if (!preg_match('/^\d{8}$/', $date)) {
            return ['error' => 'Date must be in YYYYMMDD format'];
        }

        if (empty($name)) {
            return ['error' => 'Event name is required'];
        }

        // Time: -1 (untimed/all-day) or HHMMSS in the GMT frame.
        $cal_time = -1;
        if ((string)$time !== '' && (string)$time !== '-1') {
            if (!preg_match('/^\d{1,6}$/', (string)$time)) {
                return ['error' => 'Time must be in HHMMSS format or -1 for untimed'];
            }
            $cal_time = (int)$time;
        }

        // Generate a new event ID and insert it.
        //
        // webcal_entry.cal_id is a plain INT PRIMARY KEY with no
        // auto-increment or sequence on any supported backend, so the
        // application assigns it as MAX(cal_id) + 1. Two concurrent callers
        // can compute the same id, so we insert with a non-fatal, quiet call
        // and retry on collision: the duplicate primary key makes the INSERT
        // return false, and we recompute the id and try again. This is
        // portable across SQLite, MySQL and PostgreSQL without any
        // dialect-specific locking.
        $now = date('Ymd');
        $mod_time = date('His');
        $sql = "INSERT INTO webcal_entry (cal_id, cal_name, cal_date, cal_time, cal_duration, cal_description, cal_location, cal_create_by, cal_mod_date, cal_mod_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $event_id = null;
        $max_attempts = 5;
        for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
            $res = dbi_execute('SELECT MAX(cal_id) FROM webcal_entry');
            if (!$res) {
                return ['error' => 'Failed to create event'];
            }
            $row = dbi_fetch_row($res);
            $candidate_id = ($row[0] ?? 0) + 1;
            dbi_free_result($res);

            // Non-fatal + quiet: a duplicate-key collision returns false so we
            // can retry instead of aborting the whole request.
            $res = dbi_execute(
                $sql,
                [$candidate_id, $name, $date, $cal_time, $duration, $description, $location, $this->userLogin, $now, $mod_time],
                false,
                false
            );

            if ($res) {
                $event_id = $candidate_id;
                break;
            }
        }

        if ($event_id === null) {
            return ['error' => 'Failed to create event'];
        }

        // Add user participation
        dbi_execute("INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) VALUES (?, ?, 'A')", [$event_id, $this->userLogin]);

        // Log activity
        activity_log($event_id, $this->userLogin, $this->userLogin, 'M', 'MCP: Event created');

        return ['success' => true, 'event_id' => $event_id];
    }

    #[McpTool(description: 'List the user\'s busy time blocks in a date range (GMT)')]
    public function get_availability(string $start_date, string $end_date): array
    {
        if (!preg_match('/^\d{8}$/', $start_date) || !preg_match('/^\d{8}$/', $end_date)) {
            return ['error' => 'Dates must be in YYYYMMDD format'];
        }

        $sql = "SELECT e.cal_id, e.cal_name, e.cal_date, e.cal_time, e.cal_duration
                FROM webcal_entry e
                INNER JOIN webcal_entry_user eu ON e.cal_id = eu.cal_id
                WHERE eu.cal_login = ? AND e.cal_date BETWEEN ? AND ?
                ORDER BY e.cal_date, e.cal_time";

        $busy = [];
        $all_day = [];
        $res = dbi_execute($sql, [$this->userLogin, $start_date, $end_date]);
        if ($res) {
            while ($row = dbi_fetch_row($res)) {
                // Untimed/all-day events block the whole day; report separately.
                if ((int)$row[3] === -1) {
                    $all_day[] = ['id' => $row[0], 'name' => $row[1], 'date' => $row[2]];
                    continue;
                }
                $busy[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'date' => $row[2],
                    'time' => $row[3],
                    'duration' => (int)$row[4]
                ];
            }
            dbi_free_result($res);
        }

        // Times are GMT (storage frame); recurring occurrences beyond the base
        // date are not yet expanded (documented limitation).
        return ['busy' => $busy, 'all_day' => $all_day, 'timezone' => 'GMT'];
    }

    #[McpTool(description: 'Check whether a proposed slot overlaps existing timed events (GMT)')]
    public function check_conflicts(string $date, string $time, int $duration): array
    {
        if (!preg_match('/^\d{8}$/', $date)) {
            return ['error' => 'Date must be in YYYYMMDD format'];
        }
        if (!preg_match('/^\d{1,6}$/', (string)$time)) {
            return ['error' => 'Time must be in HHMMSS format'];
        }

        $start_min = mcp_datetime_to_min($date, $time);
        $end_min = $start_min + max(0, (int)$duration);

        // Widen by a day on each side so events that span midnight are seen.
        $prev = mcp_shift_date($date, -1);
        $next = mcp_shift_date($date, 1);

        $sql = "SELECT e.cal_id, e.cal_name, e.cal_date, e.cal_time, e.cal_duration
                FROM webcal_entry e
                INNER JOIN webcal_entry_user eu ON e.cal_id = eu.cal_id
                WHERE eu.cal_login = ? AND e.cal_date BETWEEN ? AND ? AND e.cal_time != -1
                ORDER BY e.cal_date, e.cal_time";

        $events = [];
        $res = dbi_execute($sql, [$this->userLogin, $prev, $next]);
        if ($res) {
            while ($row = dbi_fetch_row($res)) {
                $s = mcp_datetime_to_min($row[2], $row[3]);
                $events[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'date' => $row[2],
                    'time' => $row[3],
                    'duration' => (int)$row[4],
                    'start' => $s,
                    'end' => $s + max(0, (int)$row[4])
                ];
            }
            dbi_free_result($res);
        }

        $conflicts = array_map(
            fn($c) => [
                'id' => $c['id'],
                'name' => $c['name'],
                'date' => $c['date'],
                'time' => $c['time'],
                'duration' => $c['duration']
            ],
            mcp_find_conflicts($start_min, $end_min, $events)
        );

        return ['has_conflict' => !empty($conflicts), 'conflicts' => $conflicts];
    }

    #[McpTool(description: 'Add a recurring event described by an RFC 5545 RRULE')]
    public function add_recurring_event(
        string $name,
        string $date,
        string $rrule,
        string $time = '-1',
        int $duration = 0,
        string $description = '',
        string $location = ''
    ): array {
        if (!is_mcp_write_enabled()) {
            return ['error' => 'MCP write access is not enabled'];
        }
        if (empty($name)) {
            return ['error' => 'Event name is required'];
        }
        if (!preg_match('/^\d{8}$/', $date)) {
            return ['error' => 'Date must be in YYYYMMDD format'];
        }

        // Time: -1 (untimed) or HHMMSS in the GMT frame.
        $cal_time = -1;
        if ((string)$time !== '' && (string)$time !== '-1') {
            if (!preg_match('/^\d{1,6}$/', (string)$time)) {
                return ['error' => 'Time must be in HHMMSS format or -1 for untimed'];
            }
            $cal_time = (int)$time;
        }

        // Validate the RRULE against the WebCalendar-supported subset and map
        // it to webcal_entry_repeats columns (defense in depth: never trust
        // the client, even though the agent validates too).
        $validated = mcp_validate_rrule($rrule);
        if (!$validated['valid']) {
            return ['error' => 'Invalid RRULE: ' . $validated['error']];
        }
        $repeat_cols = mcp_rrule_to_repeat_columns($validated['parts']);

        // Insert the base event, assigning cal_id as MAX(cal_id)+1 with a
        // retry-on-collision loop (same pattern/rationale as add_event).
        $now = date('Ymd');
        $mod_time = date('His');
        $sql = "INSERT INTO webcal_entry (cal_id, cal_name, cal_date, cal_time, cal_duration, cal_description, cal_location, cal_create_by, cal_mod_date, cal_mod_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $event_id = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $res = dbi_execute('SELECT MAX(cal_id) FROM webcal_entry');
            if (!$res) {
                return ['error' => 'Failed to create event'];
            }
            $row = dbi_fetch_row($res);
            $candidate_id = ($row[0] ?? 0) + 1;
            dbi_free_result($res);

            $res = dbi_execute(
                $sql,
                [$candidate_id, $name, $date, $cal_time, $duration, $description, $location, $this->userLogin, $now, $mod_time],
                false,
                false
            );
            if ($res) {
                $event_id = $candidate_id;
                break;
            }
        }
        if ($event_id === null) {
            return ['error' => 'Failed to create event'];
        }

        // Participation row.
        dbi_execute(
            "INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) VALUES (?, ?, 'A')",
            [$event_id, $this->userLogin]
        );

        // Insert the recurrence row. If it fails, roll back the base event so
        // we never leave an orphan non-repeating entry behind.
        $repeat_cols = array_merge(['cal_id' => $event_id], $repeat_cols);
        $cols = array_keys($repeat_cols);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $rsql = 'INSERT INTO webcal_entry_repeats (' . implode(', ', $cols)
            . ') VALUES (' . $placeholders . ')';
        $rres = dbi_execute($rsql, array_values($repeat_cols));
        if (!$rres) {
            dbi_execute('DELETE FROM webcal_entry_user WHERE cal_id = ?', [$event_id]);
            dbi_execute('DELETE FROM webcal_entry WHERE cal_id = ?', [$event_id]);
            return ['error' => 'Failed to store recurrence rule'];
        }

        activity_log($event_id, $this->userLogin, $this->userLogin, 'M', 'MCP: Recurring event created');

        return [
            'success' => true,
            'event_id' => $event_id,
            'cal_type' => $repeat_cols['cal_type']
        ];
    }

    #[McpTool(description: 'Update fields of an event the user created')]
    public function update_event(
        int $event_id,
        ?string $name = null,
        ?string $date = null,
        $time = null,
        $duration = null,
        ?string $description = null,
        ?string $location = null
    ): array {
        if (!is_mcp_write_enabled()) {
            return ['error' => 'MCP write access is not enabled'];
        }
        if ($event_id <= 0) {
            return ['error' => 'A valid event_id is required'];
        }

        $owner = $this->eventOwner($event_id);
        if ($owner === null) {
            return ['error' => 'Event not found'];
        }
        if ($owner !== $this->userLogin) {
            return ['error' => 'Not authorized to modify this event'];
        }

        // Only the provided fields are updated (null means "leave unchanged").
        $sets = [];
        $vals = [];
        if ($name !== null) {
            $sets[] = 'cal_name = ?';
            $vals[] = $name;
        }
        if ($date !== null) {
            if (!preg_match('/^\d{8}$/', $date)) {
                return ['error' => 'Date must be in YYYYMMDD format'];
            }
            $sets[] = 'cal_date = ?';
            $vals[] = $date;
        }
        if ($time !== null) {
            if ((string)$time !== '-1' && !preg_match('/^\d{1,6}$/', (string)$time)) {
                return ['error' => 'Time must be in HHMMSS format or -1 for untimed'];
            }
            $sets[] = 'cal_time = ?';
            $vals[] = (int)$time;
        }
        if ($duration !== null) {
            $sets[] = 'cal_duration = ?';
            $vals[] = (int)$duration;
        }
        if ($description !== null) {
            $sets[] = 'cal_description = ?';
            $vals[] = $description;
        }
        if ($location !== null) {
            $sets[] = 'cal_location = ?';
            $vals[] = $location;
        }

        if (empty($sets)) {
            return ['error' => 'No fields to update'];
        }

        $sets[] = 'cal_mod_date = ?';
        $vals[] = date('Ymd');
        $sets[] = 'cal_mod_time = ?';
        $vals[] = date('His');
        $vals[] = $event_id;

        $res = dbi_execute(
            'UPDATE webcal_entry SET ' . implode(', ', $sets) . ' WHERE cal_id = ?',
            $vals
        );
        if (!$res) {
            return ['error' => 'Failed to update event'];
        }

        activity_log($event_id, $this->userLogin, $this->userLogin, 'M', 'MCP: Event updated');
        return ['success' => true, 'event_id' => $event_id];
    }

    #[McpTool(description: 'Delete an event the user created')]
    public function delete_event(int $event_id): array
    {
        if (!is_mcp_write_enabled()) {
            return ['error' => 'MCP write access is not enabled'];
        }
        if ($event_id <= 0) {
            return ['error' => 'A valid event_id is required'];
        }

        $owner = $this->eventOwner($event_id);
        if ($owner === null) {
            return ['error' => 'Event not found'];
        }
        if ($owner !== $this->userLogin) {
            return ['error' => 'Not authorized to delete this event'];
        }

        // Remove the event and every row that references it, including any
        // recurrence rule and its exceptions/inclusions.
        dbi_execute('DELETE FROM webcal_entry WHERE cal_id = ?', [$event_id]);
        dbi_execute('DELETE FROM webcal_entry_user WHERE cal_id = ?', [$event_id]);
        dbi_execute('DELETE FROM webcal_entry_repeats WHERE cal_id = ?', [$event_id]);
        dbi_execute('DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?', [$event_id]);

        activity_log($event_id, $this->userLogin, $this->userLogin, 'M', 'MCP: Event deleted');
        return ['success' => true, 'event_id' => $event_id];
    }

    /**
     * Return the login that created an event (cal_create_by), or null if the
     * event does not exist. Used for the update/delete ownership check.
     */
    private function eventOwner(int $event_id): ?string
    {
        $res = dbi_execute(
            'SELECT cal_create_by FROM webcal_entry WHERE cal_id = ?',
            [$event_id]
        );
        if (!$res) {
            return null;
        }
        $row = dbi_fetch_row($res);
        dbi_free_result($res);
        return $row ? $row[0] : null;
    }
}

// Handle transport based on execution context
if (php_sapi_name() === 'cli') {
    // STDIO transport: read newline-delimited JSON-RPC messages from stdin and
    // dispatch each through mcp_dispatch_request() -- the same handler the HTTP
    // transport uses -- so STDIO and HTTP advertise and route tools identically
    // (single source of truth: mcp_list_tools). The loop lives in
    // mcp_run_stdio_loop() (includes/functions.php) so it can be unit-tested
    // with in-memory streams.
    $tools = new WebCalendarMcpTools($user_login);
    $stdin = fopen('php://stdin', 'r');
    if ($stdin === false) {
        fwrite(STDERR, "Error: unable to open stdin\n");
        exit(1);
    }

    mcp_run_stdio_loop($stdin, STDOUT, $tools);

    fclose($stdin);
    exit(0);
}

// HTTP transport for web requests -- custom JSON-RPC handler.
handleMcpHttpRequest($user_login);
exit; // handleMcpHttpRequest handles the response and exits
?>
