<?php
header("Content-Type: application/json");

$host = 'localhost';
$db = 'hr';
$user = 'root';
$pass = '';
$port = '3308';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset"; 
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT 
            a.userid, 
            a.username, 
            a.pass, 
            a.email, 
            u.employee_name, 
            u.employee_position, 
            u.contact_no 
        FROM 
            accounts a
        JOIN 
            users u ON a.userid = u.user_id
    ");
    $users = $stmt->fetchAll();
    echo json_encode($users);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Start a transaction
        $pdo->beginTransaction();
        
        // Insert into accounts table
        $sqlAccounts = "INSERT INTO accounts (username, pass, email) VALUES (?, ?, ?)";
        $stmtAccounts = $pdo->prepare($sqlAccounts);
        $stmtAccounts->execute([$input['username'], $input['pass'], $input['email']]);
        
        // Get the last inserted user_id
        $userId = $pdo->lastInsertId();
        
        // Insert into userprofile table
        $sqlProfile = "INSERT INTO users (user_id, employee_name, employee_position, contact_no) VALUES (?, ?, ?, ?)";
        $stmtProfile = $pdo->prepare($sqlProfile);
        $stmtProfile->execute([$userId, $input['employee_name'], $input['employee_position'], $input['contact_no']]);
        
        // Commit the transaction
        $pdo->commit();
        
        echo json_encode(['message' => 'User added successfully']);
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        echo json_encode(['error' => 'Failed to add user: ' . $e->getMessage()]);
    }
}
?>
