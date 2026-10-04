<?php
// Start session
session_start();

// DB configuration (supports environment variables for Render / cloud deployment, with local XAMPP fallbacks)
$dbHost = getenv('DB_HOST') ?: (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost');
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
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            echo json_encode(["status" => "error", "message" => "Connection failed"]);
            exit;
        }
    }

    // User auth
    public function login($username, $password) {
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
    }

    // Fetch logs
    public function getLogs() {
        $userId = $_SESSION['user_id'] ?? 1;
        $stmt = $this->db->prepare("SELECT * FROM fitness_logs WHERE user_id = ? ORDER BY log_date ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add log
    public function addLog($date, $height, $weight, $calories) {
        $userId = $_SESSION['user_id'] ?? 1;
        // Regex check
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            return ["status" => "error", "message" => "Invalid date"];
        }
        $stmt = $this->db->prepare("INSERT INTO fitness_logs (user_id, log_date, height, weight, calories) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $date, $height, $weight, $calories]);
        return ["status" => "success"];
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
} elseif ($action === 'get_logs') {
    echo json_encode($tracker->getLogs());
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
