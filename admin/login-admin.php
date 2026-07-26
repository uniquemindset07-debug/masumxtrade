<?php
/**
 * MASUMX TRADE - Admin Panel Login & Authentication Page
 */
require_once __DIR__ . '/../config/config.php';

$error = '';

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token verification failed.");
    }
    
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'suspended') {
            $error = "This administrator account is suspended.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Log Admin Activity
            $log_stmt = $db->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, 'Admin Logged In Successfully', ?)");
            $log_stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? 'Unknown']);

            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error = "Invalid administrator credentials provided.";
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | MASUMX TRADE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #121212;
            --accent-gold: #ffbd00;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border-color: #2b2b2b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-primary); color: var(--text-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        
        .login-card { background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .brand { text-align: center; font-size: 24px; font-weight: 700; color: var(--accent-gold); margin-bottom: 30px; letter-spacing: 1px; }
        
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 13px; color: var(--text-muted); }
        .form-control { background-color: var(--bg-primary); border: 1px solid var(--border-color); padding: 12px 15px; border-radius: 6px; color: var(--text-main); font-size: 14px; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent-gold); outline: none; }
        
        .btn-submit { background-color: var(--accent-gold); color: #000000; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; width: 100%; font-size: 15px; margin-top: 10px; }
        .btn-submit:hover { background-color: #e5a700; }
        
        .error-msg { background-color: rgba(255, 51, 51, 0.1); border: 1px solid rgba(255, 51, 51, 0.2); color: #ff3333; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand">MASUMX TRADE</div>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@masumxtrade.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-submit">Secure Login</button>
        </form>
    </div>

</body>
</html>
