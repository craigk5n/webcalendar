<?php

/**
 * SQLite Test Helper for WebCalendar MCP Server Testing
 * 
 * This class provides utilities for setting up and managing SQLite test databases
 * for MCP server testing. It handles database creation, schema setup, and test data
 * population.
 */
class McpTestHelper {
    private $db;
    private $dbPath;
    
    public function __construct($dbPath = null) {
        // Use a default path if not specified
        $this->dbPath = $dbPath ?: sys_get_temp_dir() . '/webcalendar_test.db';
        
        // Remove existing test database if it exists
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        
        // Create database connection
        $this->db = new SQLite3($this->dbPath);
        $this->db->enableExceptions(true);
        
        // Set up the database schema
        $this->setupDatabase();
    }
    
    public function getDb() {
        return $this->db;
    }
    
    public function getDbPath() {
        return $this->dbPath;
    }
    
    public function cleanup() {
        if ($this->db) {
            $this->db->close();
        }
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }
    
    private function setupDatabase() {
        // Read and execute the test schema
        $schemaSql = file_get_contents(__DIR__ . '/fixtures/test_schema.sql');
        $this->db->exec($schemaSql);
        
        // Set up global database variables for WebCalendar functions
        $this->setupGlobalVariables();
    }
    
    public function setupGlobalVariables() {
        // Set up global variables that WebCalendar functions expect
        global $db_type, $db_host, $db_database, $db_login, $db_password, $db_table_prefix;
        
        $db_type = 'sqlite3';
        $db_host = '';
        $db_database = $this->dbPath;
        $db_login = '';
        $db_password = '';
        $db_table_prefix = 'webcal_';
        
        // Include the WebCalendar database functions
        require_once __DIR__ . '/../includes/dbi4php.php';
    }
    
    /**
     * Create a test API token for a user
     */
    public function createApiToken($userLogin, $token) {
        $stmt = $this->db->prepare(
            'UPDATE webcal_user SET cal_api_token = :token WHERE cal_login = :login'
        );
        $stmt->bindValue(':token', $token, SQLITE3_TEXT);
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->execute();
        return $this->db->changes() > 0;
    }
    
    /**
     * Get a user's API token
     */
    public function getApiToken($userLogin) {
        $stmt = $this->db->prepare(
            'SELECT cal_api_token FROM webcal_user WHERE cal_login = :login'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['cal_api_token'] : null;
    }
    
    /**
     * Create a test event
     */
    public function createEvent($createBy, $date, $name, $description = '', $time = 0, $duration = 0) {
        $stmt = $this->db->prepare(
            'INSERT INTO webcal_entry (cal_create_by, cal_date, cal_name, cal_description, cal_time, cal_duration) 
             VALUES (:create_by, :date, :name, :description, :time, :duration)'
        );
        $stmt->bindValue(':create_by', $createBy, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':time', $time, SQLITE3_INTEGER);
        $stmt->bindValue(':duration', $duration, SQLITE3_INTEGER);
        $stmt->execute();
        
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Associate a user with an event
     */
    public function associateUserWithEvent($eventId, $userLogin, $status = 'A') {
        $stmt = $this->db->prepare(
            'INSERT OR REPLACE INTO webcal_entry_user (cal_id, cal_login, cal_status) 
             VALUES (:event_id, :login, :status)'
        );
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->execute();
        return $this->db->changes() > 0;
    }
    
    /**
     * Get events for a user within a date range
     */
    public function getEventsForUser($userLogin, $startDate, $endDate) {
        $stmt = $this->db->prepare(
            'SELECT e.* FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = :login AND e.cal_date BETWEEN :start_date AND :end_date 
             ORDER BY e.cal_date, e.cal_time'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->bindValue(':start_date', $startDate, SQLITE3_INTEGER);
        $stmt->bindValue(':end_date', $endDate, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $events = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }
        return $events;
    }
    
    /**
     * Search a user's events by keyword in the event name.
     *
     * An empty keyword matches all of the user's events (LIKE '%%').
     */
    public function searchEventsForUser($userLogin, $searchTerm) {
        $stmt = $this->db->prepare(
            'SELECT e.* FROM webcal_entry e
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id
             WHERE u.cal_login = :login AND e.cal_name LIKE :search
             ORDER BY e.cal_date, e.cal_time'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->bindValue(':search', "%$searchTerm%", SQLITE3_TEXT);
        $result = $stmt->execute();

        $events = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }
        return $events;
    }

    /**
     * Set MCP server configuration
     */
    public function setMcpConfig($setting, $value) {
        $stmt = $this->db->prepare(
            'INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES (:setting, :value)'
        );
        $stmt->bindValue(':setting', $setting, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_TEXT);
        $stmt->execute();
        return $this->db->changes() > 0;
    }
    
    /**
     * Get MCP server configuration
     */
    public function getMcpConfig($setting) {
        $stmt = $this->db->prepare(
            'SELECT cal_value FROM webcal_config WHERE cal_setting = :setting'
        );
        $stmt->bindValue(':setting', $setting, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['cal_value'] : null;
    }
    
    /**
     * Simulate concurrent event creation for race condition testing
     */
    public function simulateConcurrentEventCreation($userLogin, $eventCount, $concurrentUsers = 3) {
        $results = [];
        $threads = [];
        
        // Create multiple concurrent "threads" using PHP processes
        for ($i = 0; $i < $concurrentUsers; $i++) {
            $pid = pcntl_fork();
            
            if ($pid === -1) {
                throw new Exception("Could not fork process");
            } elseif ($pid) {
                // Parent process
                $threads[] = $pid;
            } else {
                // Child process - create events
                $eventsCreated = 0;
                for ($j = 0; $j < $eventCount; $j++) {
                    $date = 20240601 + $j; // Sequential dates
                    $name = "Concurrent Event " . uniqid() . " - User $i";
                    $eventId = $this->createEvent($userLogin, $date, $name);
                    $this->associateUserWithEvent($eventId, $userLogin);
                    $eventsCreated++;
                    usleep(1000); // Small delay to simulate real concurrency
                }
                exit($eventsCreated);
            }
        }
        
        // Wait for all threads to complete
        foreach ($threads as $pid) {
            $status = 0;
            $childPid = pcntl_wait($status);
            if ($childPid > 0) {
                $results[] = pcntl_waitpid($childPid, $status);
            }
        }
        
        return $results;
    }
    
    /**
     * Get the count of events for a user
     */
    public function getEventCountForUser($userLogin) {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = :login'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['count'] : 0;
    }
    
    /**
     * Clear all events for a user (for test cleanup)
     */
    public function clearUserEvents($userLogin) {
        // First delete from webcal_entry_user
        $stmt1 = $this->db->prepare(
            'DELETE FROM webcal_entry_user WHERE cal_login = :login'
        );
        $stmt1->bindValue(':login', $userLogin, SQLITE3_TEXT);
$stmt1->execute();
        
        return $this->db->changes();
    }
}

/**
 * Helper function to create a test database instance
 * 
 * @return McpTestHelper
 */
function createTestDatabase() {
    return new McpTestHelper();
}

/**
 * Helper function to clean up test database
 * 
 * @param McpTestHelper $helper
 */
function cleanupTestDatabase($helper) {
    $helper->cleanup();
}