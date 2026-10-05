<?php
/**
 * Loads the MCP SDK that ships with WebCalendar.
 *
 * The SDK and its dependencies live in includes/classes/mcp-sdk/, copied out
 * of vendor/ by `make mcp-sdk` (tools/build-mcp-sdk.php) so that the release
 * zip and the Docker image carry them without running Composer. The
 * autoloader there is generated with the bundle. On a development checkout
 * Composer's own autoloader may also know these classes; the two agree on
 * versions, and whichever is asked first loads the class.
 *
 * This require is unconditional on purpose. A missing bundle is a packaging
 * error, and a silent fallback is how issue #796 went unnoticed.
 */

require_once __DIR__ . '/classes/mcp-sdk/autoload.php';
