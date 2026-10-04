<?php
// Start session with secure cookie options if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure clean JSON output by disabling inline HTML display of warnings
error_reporting(E_ALL);
ini_set('display_errors', '0');

// DB configuration (supports environment variables for Render / cloud deployment, with local XAMPP fallbacks)
$rawHost = getenv('DB_HOST') ?: (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1');
// If host is 'localhost', replace with '127.0.0.1' to force TCP/IP socket connection instead of Unix domain socket (/var/run/mysqld/mysqld.sock)
$dbHost = ($rawHost === 'localhost') ? '127.0.0.1' : $rawHost;

$dbPort = getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '3306');
$dbUser = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: (isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'root'));
$dbPass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
$dbName = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: (isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'studentdb'));

if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
if (!defined('DB_PORT')) define('DB_PORT', $dbPort);
if (!defined('DB_USER')) define('DB_USER', $dbUser);
if (!defined('DB_PASS')) define('DB_PASS', $dbPass);
if (!defined('DB_NAME')) define('DB_NAME', $dbName);

// Tracker class
class FitnessTracker {
    private $db;

    // DB connect
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message" => "Database connection failed. Please check environment variables (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)."
            ]);
            exit;
        }
    }

    // User auth (Login)
    public function login($username, $password) {
        try {
            if (empty($username) || empty($password)) {
                return ["status" => "error", "message" => "Fields required"];
            }
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                setcookie("user", $user['username'], time() + 3600, "/");
                return ["status" => "success", "username" => $user['username']];
            }
            return ["status" => "error", "message" => "Invalid credentials"];
        } catch (PDOException $e) {
            error_log("Login DB Error: " . $e->getMessage());
            return ["status" => "error", "message" => "Database error during login"];
        }
    }

    // User registration
    public function register($username, $password) {
        try {
            if (empty($username) || empty($password)) {
                return ["status" => "error", "message" => "Fields required"];
            }
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ["status" => "error", "message" => "Username already taken"];
            }
            $stmt = $this->db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $password]);
            $newId = $this->db->lastInsertId();

            $_SESSION['user_id'] = $newId;
            $_SESSION['username'] = $username;
            setcookie("user", $username, time() + 3600, "/");
            return ["status" => "success", "username" => $username];
        } catch (PDOException $e) {
            error_log("Registration DB Error: " . $e->getMessage());
            return ["status" => "error", "message" => "Database error during registration"];
        }
    }

    // Fetch logs
    public function getLogs() {
        try {
            $userId = $_SESSION['user_id'] ?? 1;
            $stmt = $this->db->prepare("SELECT * FROM fitness_logs WHERE user_id = ? ORDER BY log_date ASC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("GetLogs DB Error: " . $e->getMessage());
            return ["status" => "error", "message" => "Database error loading logs"];
        }
    }

    // Add log
    public function addLog($date, $height, $weight, $calories) {
        try {
            $userId = $_SESSION['user_id'] ?? 1;
            // Regex check
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                return ["status" => "error", "message" => "Invalid date format"];
            }
            $stmt = $this->db->prepare("INSERT INTO fitness_logs (user_id, log_date, height, weight, calories) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $date, $height, $weight, $calories]);
            return ["status" => "success"];
        } catch (PDOException $e) {
            error_log("AddLog DB Error: " . $e->getMessage());
            return ["status" => "error", "message" => "Database error saving log"];
        }
    }
}

// JSON header
header('Content-Type: application/json');
$tracker = new FitnessTracker();
$action = $_GET['action'] ?? '';

// Handle action
if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode($tracker->login($data['username'] ?? '', $data['password'] ?? ''));
} elseif ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode($tracker->register($data['username'] ?? '', $data['password'] ?? ''));
} elseif ($action === 'get_logs') {
    $logs = $tracker->getLogs();
    echo json_encode($logs);
} elseif ($action === 'add_log') {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode($tracker->addLog(
        $data['log_date'] ?? '',
        $data['height'] ?? 170,
        $data['weight'] ?? 0,
        $data['calories'] ?? 0
    ));
} else {
    echo json_encode(["status" => "active"]);
}
