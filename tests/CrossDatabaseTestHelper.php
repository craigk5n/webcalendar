<?php

/**
 * Cross-Database Test Helper for WebCalendar MCP Server Testing
 * 
 * This class provides utilities for testing MCP server functionality across
 * different database backends (SQLite, PostgreSQL, MySQL).
 */
abstract class CrossDatabaseTestHelper {
    protected $db;
    protected $dbType;
    protected $dbPath;
    protected $dbHost;
    protected $dbDatabase;
    protected $dbLogin;
    protected $dbPassword;
    
    public function __construct($dbType, $config = []) {
        $this->dbType = $dbType;
        $this->configureDatabase($config);
        $this->createDatabase();
        $this->setupDatabase();
    }
    
    abstract protected function configureDatabase($config);
    abstract protected function createDatabase();
    abstract protected function setupDatabase();
    abstract public function createApiToken($userLogin, $token);
    abstract protected function getApiToken($userLogin);
    
    public function getDb() {
        return $this->db;
    }
    
    public function getDbPath() {
        return $this->dbPath;
    }
    
    public function getDbType() {
        return $this->dbType;
    }
    
    public function cleanup() {
        if ($this->db) {
            $this->db->close();
        }
        if ($this->dbPath && strpos($this->dbPath, ':memory:') === false && file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }
    
    public function setMcpConfig($setting, $value) {
        // Implementation varies by database
        if ($this->dbType === 'sqlite3') {
            $stmt = $this->db->prepare(
                'INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES (:setting, :value)'
            );
            $stmt->bindValue(':setting', $setting, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_TEXT);
            $stmt->execute();
            return $this->db->changes() > 0;
        } else {
            // For PostgreSQL/MySQL, use standard SQL
            $sql = "INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('$setting', '$value') "
                  . "ON CONFLICT (cal_setting) DO UPDATE SET cal_value = '$value'";
            return $this->db->exec($sql) !== false;
        }
    }
    
    /**
     * Create a test user
     */
    public function createUser($userLogin, $firstName = 'Test', $lastName = 'User', $email = 'test@example.com') {
        if ($this->dbType === 'sqlite3') {
            $stmt = $this->db->prepare(
                'INSERT OR REPLACE INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_email) 
                 VALUES (:login, :first, :last, :email)'
            );
            $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
            $stmt->bindValue(':first', $firstName, SQLITE3_TEXT);
            $stmt->bindValue(':last', $lastName, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->execute();
            return $this->db->changes() > 0;
        } else {
            $sql = "INSERT INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_email) 
                    VALUES ('$userLogin', '$firstName', '$lastName', '$email')
                    ON CONFLICT (cal_login) DO UPDATE SET 
                    cal_firstname = '$firstName', cal_lastname = '$lastName', cal_email = '$email'";
            return $this->db->exec($sql) !== false;
        }
    }
    
    public function getMcpConfig($setting) {
        if ($this->dbType === 'sqlite3') {
            $stmt = $this->db->prepare(
                'SELECT cal_value FROM webcal_config WHERE cal_setting = :setting'
            );
            $stmt->bindValue(':setting', $setting, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return $row ? $row['cal_value'] : null;
        } else {
            $result = $this->db->query("SELECT cal_value FROM webcal_config WHERE cal_setting = '$setting'");
            $row = $result->fetchArray();
            return $row ? $row[0] : null;
        }
    }
    
    /**
     * Create a test event
     */
    public function createEvent($createBy, $date, $name, $description = '', $time = 0, $duration = 0) {
        if ($this->dbType === 'sqlite3') {
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
        } else {
            $sql = "INSERT INTO webcal_entry (cal_create_by, cal_date, cal_name, cal_description, cal_time, cal_duration) 
                    VALUES ('$createBy', $date, '$name', '$description', $time, $duration)";
            $this->db->exec($sql);
            return $this->db->lastInsertId();
        }
    }
    
    /**
     * Associate a user with an event
     */
    public function associateUserWithEvent($eventId, $userLogin, $status = 'A') {
        if ($this->dbType === 'sqlite3') {
            $stmt = $this->db->prepare(
                'INSERT OR REPLACE INTO webcal_entry_user (cal_id, cal_login, cal_status) 
                 VALUES (:event_id, :login, :status)'
            );
            $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
            $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->execute();
            return $this->db->changes() > 0;
        } else {
            $sql = "INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) 
                    VALUES ($eventId, '$userLogin', '$status')
                    ON CONFLICT (cal_id, cal_login) DO UPDATE SET cal_status = '$status'";
            return $this->db->exec($sql) !== false;
        }
    }
    
    
}

/**
 * SQLite Test Helper
 */
class SQLiteTestHelper extends CrossDatabaseTestHelper {
    
    protected function configureDatabase($config) {
        $this->dbPath = $config['path'] ?? sys_get_temp_dir() . '/webcalendar_sqlite_test.db';
    }
    
    protected function createDatabase() {
        // Remove existing test database if it exists
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        
        // Create database connection
        $this->db = new SQLite3($this->dbPath);
        $this->db->enableExceptions(true);
    }
    
    protected function setupDatabase() {
        // Read and execute the test schema
        $schemaSql = file_get_contents(__DIR__ . '/fixtures/test_schema.sql');
        $this->db->exec($schemaSql);
        
        // Set up global database variables for WebCalendar functions
        $this->setupGlobalVariables();
    }
    
    protected function setupGlobalVariables() {
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
    
    public function createApiToken($userLogin, $token) {
        $stmt = $this->db->prepare(
            'UPDATE webcal_user SET cal_api_token = :token WHERE cal_login = :login'
        );
        $stmt->bindValue(':token', $token, SQLITE3_TEXT);
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->execute();
        return $this->db->changes() > 0;
    }
    
    public function getApiToken($userLogin) {
        $stmt = $this->db->prepare(
            'SELECT cal_api_token FROM webcal_user WHERE cal_login = :login'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['cal_api_token'] : null;
    }
    
    public function createUser($userLogin, $firstName = 'Test', $lastName = 'User', $email = 'test@example.com') {
        $stmt = $this->db->prepare(
            'INSERT OR REPLACE INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_email) 
             VALUES (:login, :first, :last, :email)'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->bindValue(':first', $firstName, SQLITE3_TEXT);
        $stmt->bindValue(':last', $lastName, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->execute();
        return $this->db->changes() > 0;
    }
    
    public function getEventCountForUser($userLogin) {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = :login'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? (int)$row['count'] : 0;
    }
    
    public function clearUserEvents($userLogin) {
        $stmt = $this->db->prepare(
            'DELETE FROM webcal_entry_user WHERE cal_login = :login'
        );
        $stmt->bindValue(':login', $userLogin, SQLITE3_TEXT);
        $stmt->execute();
        return $this->db->changes();
    }
    
    public function searchEventsForUser($userLogin, $searchTerm) {
        $stmt = $this->db->prepare(
            'SELECT e.* FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = :login AND e.cal_name LIKE :search'
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
}

/**
 * MySQL Test Helper
 */
class MySQLTestHelper extends CrossDatabaseTestHelper {
    
    protected function configureDatabase($config) {
        $this->dbHost = $config['host'] ?? 'localhost';
        $this->dbDatabase = $config['database'] ?? 'webcalendar_test';
        $this->dbLogin = $config['login'] ?? 'testuser';
        $this->dbPassword = $config['password'] ?? 'testpass';
    }
    
    protected function createDatabase() {
        try {
            // Create connection with database name
            $this->db = new mysqli($this->dbHost, $this->dbLogin, $this->dbPassword, $this->dbDatabase);
            
            if ($this->db->connect_error) {
                throw new Exception("Connection failed: " . $this->db->connect_error);
            }
        } catch (Exception $e) {
            throw new Exception("MySQL database connection failed: " . $e->getMessage());
        }
    }
    
    protected function setupDatabase() {
        // Read and execute the test schema
        $schemaSql = file_get_contents(__DIR__ . '/fixtures/test_schema.sql');
        
        // Convert SQLite schema to MySQL-compatible SQL
        $mysqlSchema = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $schemaSql);
        $mysqlSchema = str_replace('INTEGER', 'INT', $mysqlSchema);
        
        $this->db->multi_query($mysqlSchema);
        
        // Clear any remaining results
        while ($this->db->more_results()) {
            $this->db->next_result();
        }
        
        $this->setupGlobalVariables();
    }
    
    protected function setupGlobalVariables() {
        global $db_type, $db_host, $db_database, $db_login, $db_password, $db_table_prefix;
        
        $db_type = 'mysql';
        $db_host = $this->dbHost;
        $db_database = $this->dbDatabase;
        $db_login = $this->dbLogin;
        $db_password = $this->dbPassword;
        $db_table_prefix = 'webcal_';
        
        // Include the WebCalendar database functions
        require_once __DIR__ . '/../includes/dbi4php.php';
    }
    
    public function createApiToken($userLogin, $token) {
        $stmt = $this->db->prepare(
            'UPDATE webcal_user SET cal_api_token = ? WHERE cal_login = ?'
        );
        $stmt->bind_param('ss', $token, $userLogin);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
    
    public function getApiToken($userLogin) {
        $stmt = $this->db->prepare(
            'SELECT cal_api_token FROM webcal_user WHERE cal_login = ?'
        );
        $stmt->bind_param('s', $userLogin);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['cal_api_token'] : null;
    }
    
    public function createUser($userLogin, $firstName = 'Test', $lastName = 'User', $email = 'test@example.com') {
        $stmt = $this->db->prepare(
            'INSERT INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_email) 
             VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             cal_firstname = VALUES(cal_firstname), 
             cal_lastname = VALUES(cal_lastname), 
             cal_email = VALUES(cal_email)'
        );
        $stmt->bind_param('ssss', $userLogin, $firstName, $lastName, $email);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
    
    public function getEventCountForUser($userLogin) {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = ?'
        );
        $stmt->bind_param('s', $userLogin);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? (int)$row['count'] : 0;
    }
    
    public function clearUserEvents($userLogin) {
        $stmt = $this->db->prepare(
            'DELETE FROM webcal_entry_user WHERE cal_login = ?'
        );
        $stmt->bind_param('s', $userLogin);
        $stmt->execute();
        return $stmt->affected_rows;
    }
    
    public function searchEventsForUser($userLogin, $searchTerm) {
        $stmt = $this->db->prepare(
            'SELECT e.* FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = ? AND e.cal_name LIKE ?'
        );
        $stmt->bind_param('ss', $userLogin, "%$searchTerm%");
        $stmt->execute();
        $result = $stmt->get_result();
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        return $events;
    }
}

/**
 * PostgreSQL Test Helper
 */
class PostgreSQLTestHelper extends CrossDatabaseTestHelper {
    
    protected function configureDatabase($config) {
        $this->dbHost = $config['host'] ?? 'localhost';
        $this->dbDatabase = $config['database'] ?? 'webcalendar_test';
        $this->dbLogin = $config['login'] ?? 'testuser';
        $this->dbPassword = $config['password'] ?? 'testpass';
    }
    
    protected function createDatabase() {
        try {
            $connectionString = "host=$this->dbHost dbname=$this->dbDatabase user=$this->dbLogin password=$this->dbPassword";
            $this->db = pg_connect($connectionString);
            
            if (!$this->db) {
                throw new Exception("PostgreSQL connection failed");
            }
        } catch (Exception $e) {
            throw new Exception("PostgreSQL database connection failed: " . $e->getMessage());
        }
    }
    
    protected function setupDatabase() {
        // Read and execute the test schema
        $schemaSql = file_get_contents(__DIR__ . '/fixtures/test_schema.sql');
        
        // Convert SQLite schema to PostgreSQL-compatible SQL
        $pgSchema = str_replace('AUTOINCREMENT', 'SERIAL', $schemaSql);
        $pgSchema = str_replace('INTEGER', 'INTEGER', $pgSchema);
        $pgSchema = str_replace('VARCHAR(255)', 'VARCHAR(255)', $pgSchema);
        
        pg_query($this->db, $pgSchema);
        
        $this->setupGlobalVariables();
    }
    
    protected function setupGlobalVariables() {
        global $db_type, $db_host, $db_database, $db_login, $db_password, $db_table_prefix;
        
        $db_type = 'postgresql';
        $db_host = $this->dbHost;
        $db_database = $this->dbDatabase;
        $db_login = $this->dbLogin;
        $db_password = $this->dbPassword;
        $db_table_prefix = 'webcal_';
        
        // Include the WebCalendar database functions
        require_once __DIR__ . '/../includes/dbi4php.php';
    }
    
    public function createApiToken($userLogin, $token) {
        $stmt = pg_prepare($this->db, 'update_api_token', 
            'UPDATE webcal_user SET cal_api_token = $1 WHERE cal_login = $2'
        );
        pg_execute($this->db, 'update_api_token', [$token, $userLogin]);
        return pg_affected_rows($this->db) > 0;
    }
    
    public function getApiToken($userLogin) {
        $stmt = pg_prepare($this->db, 'get_api_token', 
            'SELECT cal_api_token FROM webcal_user WHERE cal_login = $1'
        );
        $result = pg_execute($this->db, 'get_api_token', [$userLogin]);
        $row = pg_fetch_assoc($result);
        return $row ? $row['cal_api_token'] : null;
    }
    
    public function createUser($userLogin, $firstName = 'Test', $lastName = 'User', $email = 'test@example.com') {
        $stmt = pg_prepare($this->db, 'create_user', 
            'INSERT INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_email) 
             VALUES ($1, $2, $3, $4)
             ON CONFLICT (cal_login) DO UPDATE SET 
             cal_firstname = EXCLUDED.cal_firstname, 
             cal_lastname = EXCLUDED.cal_lastname, 
             cal_email = EXCLUDED.cal_email'
        );
        pg_execute($this->db, 'create_user', [$userLogin, $firstName, $lastName, $email]);
        return pg_affected_rows($this->db) > 0;
    }
    
    public function getEventCountForUser($userLogin) {
        $stmt = pg_prepare($this->db, 'get_event_count', 
            'SELECT COUNT(*) as count FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = $1'
        );
        $result = pg_execute($this->db, 'get_event_count', [$userLogin]);
        $row = pg_fetch_assoc($result);
        return $row ? (int)$row['count'] : 0;
    }
    
    public function clearUserEvents($userLogin) {
        $stmt = pg_prepare($this->db, 'clear_user_events', 
            'DELETE FROM webcal_entry_user WHERE cal_login = $1'
        );
        pg_execute($this->db, 'clear_user_events', [$userLogin]);
        return pg_affected_rows($this->db);
    }
    
    public function searchEventsForUser($userLogin, $searchTerm) {
        $stmt = pg_prepare($this->db, 'search_events', 
            'SELECT e.* FROM webcal_entry e 
             JOIN webcal_entry_user u ON e.cal_id = u.cal_id 
             WHERE u.cal_login = $1 AND e.cal_name LIKE $2'
        );
        $result = pg_execute($this->db, 'search_events', [$userLogin, "%$searchTerm%"]);
        $events = [];
        while ($row = pg_fetch_assoc($result)) {
            $events[] = $row;
        }
        return $events;
    }
}

/**
 * Factory function to create database-specific test helpers
 */
function createCrossDatabaseTestHelper($dbType, $config = []) {
    switch ($dbType) {
        case 'sqlite3':
            return new SQLiteTestHelper($dbType, $config);
        case 'mysql':
            return new MySQLTestHelper($dbType, $config);
        case 'postgresql':
            return new PostgreSQLTestHelper($dbType, $config);
        default:
            throw new Exception("Unsupported database type: $dbType");
    }
}

/**
 * Helper function to clean up test database
 */
function cleanupCrossDatabaseTest($helper) {
    $helper->cleanup();
}