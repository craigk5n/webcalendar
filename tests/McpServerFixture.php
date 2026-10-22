<?php

declare(strict_types=1);

/**
 * Per-run ports and file paths for the MCP tests that start a web server.
 *
 * Six of these classes used to hard-code a port (8099 to 8104), a SQLite file
 * and a server log path. All three are machine-wide, so two test runs deleted
 * each other's database and fought over the ports, and the failures landed in
 * unrelated tests. tests/bootstrap.php holds a lock because of it.
 *
 * Asking the operating system for a free port and giving each run its own
 * files removes the collision at the source.
 */
final class McpServerFixture
{
  /**
   * A port nothing is listening on.
   *
   * Binding to port 0 makes the kernel choose one, which is the only way to
   * ask without guessing. The socket is closed before the port is handed to
   * `php -S`, so there is a window in which something else could take it;
   * that is unavoidable short of passing the socket itself, and the callers
   * already verify the server answers before running any test.
   */
  public static function freePort(): int
  {
    $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
      throw new RuntimeException(
        'could not reserve a port for the test server: ' . $errstr
      );
    }

    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);

    $port = (int) substr($name, (int) strrpos($name, ':') + 1);
    if ($port <= 0) {
      throw new RuntimeException('could not read back the reserved port');
    }

    return $port;
  }

  /**
   * A path in the temp directory unique to this process and call, so parallel
   * runs and repeated runs cannot share one.
   */
  public static function tempPath(string $prefix, string $suffix): string
  {
    return sys_get_temp_dir() . '/' . $prefix . '-' . getmypid() . '-'
      . bin2hex(random_bytes(4)) . $suffix;
  }
}
