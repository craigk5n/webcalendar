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

        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        $tools = new WebCalendarMcpTools($user_login);
        $result = null;
        $error = null;

        switch ($method) {
            case 'initialize':
                $result = [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => [
                            'listChanged' => true
                        ]
                    ],
                    'serverInfo' => [
                        'name' => 'WebCalendar MCP Server',
                        'version' => '1.0.0'
                    ]
                ];
                break;

            case 'tools/list':
                $result = [
                    'tools' => [
                        [
                            'name' => 'list_events',
                            'description' => 'List events for a user within a date range',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_date' => [
                                        'type' => 'string',
                                        'description' => 'Start date in YYYYMMDD format'
                                    ],
                                    'end_date' => [
                                        'type' => 'string',
                                        'description' => 'End date in YYYYMMDD format'
                                    ]
                                ],
                                'required' => ['start_date', 'end_date']
                            ]
                        ],
                        [
                            'name' => 'get_user_info',
                            'description' => 'Get basic information about the authenticated user',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => []
                            ]
                        ],
                        [
                            'name' => 'search_events',
                            'description' => 'Search events by keyword in name or description',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'keyword' => [
                                        'type' => 'string',
                                        'description' => 'Search keyword'
                                    ],
                                    'limit' => [
                                        'type' => 'integer',
                                        'description' => 'Maximum number of results',
                                        'default' => 50
                                    ]
                                ],
                                'required' => ['keyword']
                            ]
                        ],
                        [
                            'name' => 'add_event',
                            'description' => 'Add a new basic event (no repeating)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                        'description' => 'Event name'
                                    ],
                                    'date' => [
                                        'type' => 'string',
                                        'description' => 'Event date in YYYYMMDD format'
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                        'description' => 'Event description'
                                    ],
                                    'location' => [
                                        'type' => 'string',
                                        'description' => 'Event location'
                                    ],
                                    'duration' => [
                                        'type' => 'integer',
                                        'description' => 'Duration in minutes',
                                        'default' => 0
                                    ]
                                ],
                                'required' => ['name', 'date']
                            ]
                        ]
                    ]
                ];
                break;

            case 'tools/call':
                $tool_name = $params['name'] ?? '';
                $tool_args = $params['arguments'] ?? [];

                try {
                    switch ($tool_name) {
                        case 'list_events':
                            $result = $tools->list_events(
                                $tool_args['start_date'] ?? '',
                                $tool_args['end_date'] ?? ''
                            );
                            break;
                        case 'get_user_info':
                            $result = $tools->get_user_info();
                            break;
                        case 'search_events':
                            $result = $tools->search_events(
                                $tool_args['keyword'] ?? '',
                                $tool_args['limit'] ?? 50
                            );
                            break;
                        case 'add_event':
                            $result = $tools->add_event(
                                $tool_args['name'] ?? '',
                                $tool_args['date'] ?? '',
                                $tool_args['description'] ?? '',
                                $tool_args['location'] ?? '',
                                $tool_args['duration'] ?? 0
                            );
                            break;
                        default:
                            throw new Exception("Unknown tool: $tool_name");
                    }
                } catch (Exception $e) {
                    $error = [
                        'code' => -32603,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            default:
                $error = [
                    'code' => -32601,
                    'message' => 'Method not found'
                ];
        }

        $response = ['jsonrpc' => '2.0', 'id' => $id];
        if ($error) {
            $response['error'] = $error;
        } else {
            $response['result'] = $result;
        }

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

// Fallback to other methods
if (empty($token)) {
    if (getenv('MCP_TOKEN')) {
        $token = getenv('MCP_TOKEN');
        $token_source = 'env';
    } elseif (isset($_SERVER['HTTP_X_MCP_TOKEN'])) {
        $token = $_SERVER['HTTP_X_MCP_TOKEN'];
        $token_source = 'x-mcp-token';
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
        $token_source = 'query';
    }
}

// Optional debug logging (enable with WEBCALENDAR_DEBUG=true or ?debug=1)
if (getenv('WEBCALENDAR_DEBUG') || isset($_GET['debug'])) {
    error_log("MCP Token: source='$token_source', token='" . substr($token, 0, 8) . "...'");
}

// Fallback to other methods
if (empty($token)) {
    $token = getenv('MCP_TOKEN') ?: ($_SERVER['HTTP_X_MCP_TOKEN'] ?? ($_GET['token'] ?? ''));
}

// Optional debug logging
if (getenv('WEBCALENDAR_DEBUG') === 'true') {
    error_log("MCP Request: auth_header='$auth_header', token='$token', sapi=" . php_sapi_name());
}

if (empty($token)) {
  $error_msg = 'API token required. Use Authorization: Bearer <token> header, X-MCP-Token header, MCP_TOKEN env var, or ?token= parameter';
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

use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
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

        // Query events
        $events = [];
        $sql = "SELECT e.cal_id, e.cal_name, e.cal_date, e.cal_time, e.cal_duration,
                       e.cal_description, e.cal_location, e.cal_priority
                FROM webcal_entry e
                INNER JOIN webcal_entry_user eu ON e.cal_id = eu.cal_id
                WHERE eu.cal_login = ? AND e.cal_date BETWEEN ? AND ?
                ORDER BY e.cal_date, e.cal_time";

        $res = dbi_execute($sql, [$this->userLogin, $start_date, $end_date]);
        if ($res) {
            while ($row = dbi_fetch_row($res)) {
                $events[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'date' => $row[2],
                    'time' => $row[3],
                    'duration' => $row[4],
                    'description' => $row[5],
                    'location' => $row[6],
                    'priority' => $row[7]
                ];
            }
            dbi_free_result($res);
        }

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

        $search_term = '%' . $keyword . '%';
        $res = dbi_execute($sql, [$this->userLogin, $search_term, $search_term]);
        if ($res) {
            while ($row = dbi_fetch_row($res)) {
                $events[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'date' => $row[2],
                    'time' => $row[3],
                    'description' => $row[4]
                ];
            }
            dbi_free_result($res);
        }

        return ['events' => $events, 'total' => count($events)];
    }

    #[McpTool(description: 'Add a new basic event (no repeating)')]
    public function add_event(string $name, string $date, string $description = '', string $location = '', int $duration = 0): array
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

        // Generate new event ID
        $res = dbi_execute('SELECT MAX(cal_id) FROM webcal_entry');
        if ($res) {
            $row = dbi_fetch_row($res);
            $event_id = $row[0] + 1;
            dbi_free_result($res);
        } else {
            $event_id = 1;
        }

        // Insert event
        $sql = "INSERT INTO webcal_entry (cal_id, cal_name, cal_date, cal_time, cal_duration, cal_description, cal_location, cal_create_by, cal_mod_date, cal_mod_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $now = date('Ymd');
        $time = date('His');
        $res = dbi_execute($sql, [$event_id, $name, $date, -1, $duration, $description, $location, $this->userLogin, $now, $time]);

        if ($res) {
            // Add user participation
            dbi_execute("INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) VALUES (?, ?, 'A')", [$event_id, $this->userLogin]);

            // Log activity
            activity_log($event_id, $this->userLogin, $this->userLogin, 'M', 'MCP: Event created');

            return ['success' => true, 'event_id' => $event_id];
        }

        return ['error' => 'Failed to create event'];
    }
}

// Handle transport based on execution context
if (php_sapi_name() === 'cli') {
    // STDIO transport for CLI execution
    $server = Server::builder()
        ->setServerInfo('WebCalendar MCP Server', '1.0.0')
        ->setDiscovery(__DIR__, ['.'])
        ->addToolInstance(new WebCalendarMcpTools($user_login))
        ->build();

    $transport = new StdioTransport();
} else {
    // HTTP transport for web requests - implement custom JSON-RPC handler
    handleMcpHttpRequest($user_login);
    exit; // handleMcpHttpRequest handles the response and exits
}

try {
    $server->run($transport);
} catch (Exception $e) {
    $error_msg = 'MCP Server error: ' . $e->getMessage();
    error_log($error_msg);
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Error: $error_msg\n");
        exit(1);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
}
?></content>
<parameter name="filePath">mcp.php