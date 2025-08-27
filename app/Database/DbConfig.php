<?php

/**
 *
 * DbConfig is the main PDO class. It is extended by all the *Model.php classes to interact with DB tables.
 *
 */

namespace App\Database;

use Dotenv\Dotenv;
use PDO;
use PDOException;


class DbConfig
{
    /**
     *  This is the PDO Error code for a duplicate insertion error
     */
    const ERROR_DUPLICATE = '23000';
    /**
     * @var string Username to login to database, could probably be fetched from a config file instead but w/e
     */
    private static $_username = '';
    /**
     * @var string
     */
    private static $_password = '';
    /**
     * @var string
     */
    private static $_database = '';
    /**
     * @var string
     */
    private static $_hostname = 'localhost';
    /**
     * @var string
     */
    private static $_port = '3306';
    /**
     * @var string
     */
    private static $_dsnPrefix = 'mysql';
    /**
     * @var
     */
    private static $_unixSocket;
    /**
     * @var boolean used by installer for testing config values
     */
    private static $_loadConfig = true;
    /**
     * @var PDO
     */
    public $_db;

    public $errorInfo;

    /**
     * Checks the application environment variable and sets the username, password, database and hostname.
     * Creates a connection to the database
     *
     * @throws PDOException Throws a PDOException when it fails to create a database connection.
     */
    public function __construct()
    {
        if (self::$_loadConfig)
            $this->getConf();

        // Build DSN based on configuration
        if (self::$_dsnPrefix === 'sqlite') {
            // SQLite connection
            if (self::$_database === ':memory:') {
                $dsn = 'sqlite::memory:';
            } else {
                $dsn = 'sqlite:' . self::$_database;
            }
        } else {
            // MySQL connection
            $dsn = self::$_dsnPrefix . ':host=' . self::$_hostname . ';port=' . self::$_port . ';dbname=' . self::$_database;
        }

        try {
            if (!$this->_db = new PDO($dsn, self::$_username, self::$_password)) {
                throw new \Exception('Failed to connect to database');
            }

            // Set timezone for MySQL only
            if (self::$_dsnPrefix !== 'sqlite') {
                $this->_db->query("SET time_zone = '+00:00'"); //Set timezone to GMT, previous statement didn't work (Africa/Lagos), and GMT preserved daylight savings.
            }
            
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Enable foreign keys for SQLite
            if (self::$_dsnPrefix === 'sqlite') {
                $this->_db->exec('PRAGMA foreign_keys = ON;');
            }
        } catch (PDOException $e) {
            error_log('Failed to connect to database: ' . $e->getMessage());
            throw new \Exception('Failed to connect to database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Returns the appropriate database configuration
     * @return array
     */
    static function getConf()
    {
        // Try to load .env file for local development
        $envPath = base_path();
        $envFile = '.env';
        
        // Check for testing environment
        if (defined('PHPUNIT_RUNNING') || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing')) {
            $envFile = '.env.testing';
        }
        
        if (!file_exists($envPath . '/' . $envFile)) {
            throw new \Exception('Missing ' . $envFile . ' file');
        }

        $dotenv = Dotenv::createImmutable($envPath, $envFile);
        $dotenv->load();

        // Check for custom DSN first (for SQLite support)
        if (!empty($_ENV['DATABASE_DSN'])) {
            $dsn = $_ENV['DATABASE_DSN'];
            if (strpos($dsn, 'sqlite:') === 0) {
                self::$_dsnPrefix = 'sqlite';
                self::$_database = str_replace('sqlite:', '', $dsn);
                self::$_username = '';
                self::$_password = '';
                self::$_hostname = '';
                self::$_port = '';
            } else {
                // Parse other DSN formats if needed
                throw new \Exception('Unsupported DSN format: ' . $dsn);
            }
        } else if (($url = getenv("DATABASE_URL")) !== false) {
            $url = parse_url($url);
            self::$_username = $url['user'];
            self::$_password = $url['pass'];
            self::$_database = substr($url["path"], 1);
            self::$_hostname = $url['host'];
            self::$_port = $url["port"];
        } else if (!empty($_ENV['DATABASE_HOST']) && !empty($_ENV['DATABASE_NAME'])) {
            self::$_username = $_ENV['DATABASE_USER'] ?? '';
            self::$_password = $_ENV['DATABASE_PASSWORD'] ?? '';
            self::$_database = $_ENV['DATABASE_NAME'];
            self::$_hostname = $_ENV['DATABASE_HOST'];
            self::$_port = $_ENV['DATABASE_PORT'] ?? '3306';
        }

        $conf = ["host" => self::$_hostname, "port" => self::$_port, "user" => self::$_username, "pass" => self::$_password, "db" => self::$_database,];
        return $conf;
    }

    public static function testConf($host, $port, $database, $user, $pass)
    {
        self::$_username = $user;
        self::$_password = $pass;
        self::$_database = $database;
        self::$_hostname = $host;
        self::$_port = $port;
        self::$_loadConfig = false; // prevent config from being loaded, used for testing database connection
        try {
            $db = new DbConfig();
        } catch (\Exception $ex) {
            self::$_loadConfig = true;
            return $ex->getMessage();
        }
        self::$_loadConfig = true;
        return true;
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function insert($sql, $placeholders = null)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)) {
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
                    }
                }
            }
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }
            return $this->_db->lastInsertId();
        } catch (PDOException $e) {
            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     *
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the delete operation
     */
    public function delete($sql, $placeholders = null)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)) {
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
                    }
                }
            }

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            return $stmt->rowCount();
        } catch (PDOException $e) {

            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     *
     * @return bool|int Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function update($sql, $placeholders = null)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)) {
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
                    }
                }
            }
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            return $stmt->rowCount();
        } catch (PDOException $e) {

            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string     $sql
     * @param array|null $placeholders
     * @param int        $fetchStyle
     *
     * @return bool|array Returns false on an unexpected failure or the rows found by the statement. Returns an empty array when nothing is found
     */
    public function select($sql, $placeholders = null, $fetchStyle = PDO::FETCH_ASSOC)
    {
        try {
            if (!$stmt = $this->_db->prepare($sql)) {
                $errorInfo = $this->_db->errorInfo();
                throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            if (is_array($placeholders)) {
                foreach ($placeholders as $key => $placeholder) {
                    if (is_int($key)) {
                        $key++;
                    }
                    if (!$stmt->bindParam($key, $placeholders[$key])) {
                        $errorInfo = $stmt->errorInfo();
                        throw new PDOException("Bind Error: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
                    }
                }
            }

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Execute Failed: " . $errorInfo[0] . " (" . $errorInfo[0] . ")", 0);
            }

            return $stmt->fetchAll($fetchStyle);
        } catch (PDOException $e) {

            $this->errorInfo = $e->getMessage();
            return false;
        }
    }
}
