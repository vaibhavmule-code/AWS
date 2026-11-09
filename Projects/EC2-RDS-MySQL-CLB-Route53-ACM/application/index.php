
<?php
declare(strict_types=1);

/*
 * AWS EC2 to RDS MySQL POC
 * Secure PHP application
 */

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true, // Enable when accessing through HTTPS
    'cookie_samesite' => 'Strict'
]);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database configuration from environment variables
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME') ?: 'pocdb';
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = 3306;

$status = '';
$status_type = '';
$records = [];

function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    // Verify required environment variables
    if (!$db_host || !$db_user || !$db_pass) {
        throw new RuntimeException('Database configuration is incomplete.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(
        $db_host,
        $db_user,
        $db_pass,
        $db_name,
        $db_port
    );

    $conn->set_charset('utf8mb4');

    // Create table if it does not exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email_id VARCHAR(150) NOT NULL,
            phone_no VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // CSRF validation
        $submitted_token = $_POST['csrf_token'] ?? '';

        if (
            !is_string($submitted_token) ||
            !hash_equals($_SESSION['csrf_token'], $submitted_token)
        ) {
            throw new RuntimeException('Invalid form submission. Please refresh the page.');
        }

        $name = trim($_POST['name'] ?? '');
        $email_id = trim($_POST['email_id'] ?? '');
        $phone_no = trim($_POST['phone_no'] ?? '');

        // Input validation
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('Name is required and must be within 100 characters.');
        }

        if (!filter_var($email_id, FILTER_VALIDATE_EMAIL) || strlen($email_id) > 150) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone_no)) {
            throw new InvalidArgumentException('Please enter a valid phone number.');
        }

        // Insert using prepared statement
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email_id, phone_no) VALUES (?, ?, ?)"
        );

        $stmt->bind_param('sss', $name, $email_id, $phone_no);
        $stmt->execute();
        $stmt->close();

        $status = 'Data inserted into RDS MySQL successfully.';
        $status_type = 'success';

        // Rotate token after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Retrieve latest 10 records
    $result = $conn->query(
        "SELECT id, name, email_id, phone_no, created_at
         FROM users
         ORDER BY id DESC
         LIMIT 10"
    );

    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    $conn->close();

} catch (InvalidArgumentException $e) {
    $status = $e->getMessage();
    $status_type = 'error';

} catch (Throwable $e) {
    // Log technical details on the server, not in the browser
    error_log('Application error: ' . $e->getMessage());

    $status = 'Unable to process your request. Please try again later.';
    $status_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AWS EC2 to RDS MySQL POC</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 40px 20px;
        }

        .box {
            background: white;
            padding: 25px;
            max-width: 900px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }

        h2 {
            color: #232f3e;
        }

        label {
            display: block;
            margin-top: 12px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: 1px solid #999;
            border-radius: 4px;
        }

        button {
            margin-top: 12px;
            padding: 12px 22px;
            background: #ff9900;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #e68a00;
        }

        .status {
            margin-top: 15px;
            font-weight: bold;
        }

        .success {
            color: green;
        }

        .error {
            color: #b00020;
        }

        table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #999;
            padding: 10px;
            text-align: left;
            overflow-wrap: anywhere;
        }

        th {
            background: #232f3e;
            color: white;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px 8px;
            }

            .box {
                padding: 15px;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

<div class="box">

    <h2>AWS EC2 to RDS MySQL POC</h2>

    <form method="POST" action="">
        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo escape($_SESSION['csrf_token']); ?>"
        >

        <label for="name">Name</label>
        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            required
        >

        <label for="email_id">Email ID</label>
        <input
            type="email"
            id="email_id"
            name="email_id"
            maxlength="150"
            required
        >

        <label for="phone_no">Phone Number</label>
        <input
            type="tel"
            id="phone_no"
            name="phone_no"
            maxlength="20"
            required
        >

        <button type="submit">Submit Data to RDS</button>
    </form>

    <?php if ($status !== ''): ?>
        <p class="status <?php echo escape($status_type); ?>">
            <?php echo escape($status); ?>
        </p>
    <?php endif; ?>

    <h3>Latest Data from RDS MySQL</h3>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email ID</th>
                <th>Phone No</th>
                <th>Created At</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($records) > 0): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo escape($row['name']); ?></td>
                        <td><?php echo escape($row['email_id']); ?></td>
                        <td><?php echo escape($row['phone_no']); ?></td>
                        <td><?php echo escape($row['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
