<?php
/**
 * MASUMX TRADE - Admin User Management Page
 */
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-admin.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle Status Toggle (Suspend / Activate)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $current_status = sanitize_input($_GET['toggle_status']);
    $new_status = ($current_status === 'active') ? 'suspended' : 'active';
    
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$new_status, $id]);
    $message = "<p class='alert alert-success'>User status updated successfully!</p>";
}

// Handle Reset Password
if (isset($_POST['reset_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token validation failed.");
    }
    $id = intval($_POST['user_id']);
    $new_password = password_hash('12345678', PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_password, $id]);
    $message = "<p class='alert alert-success'>Password reset to default (12345678) successfully!</p>";
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$id]);
    $message = "<p class='alert alert-success'>User deleted successfully!</p>";
}

// Search and Filter Users
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$query = "SELECT * FROM users WHERE role = 'user'";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | MASUMX TRADE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #121212;
            --bg-tertiary: #1e1e1e;
            --accent-gold: #ffbd00;
            --accent-green: #00ff66;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border-color: #2b2b2b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-primary); color: var(--text-main); display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background-color: var(--bg-secondary); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; }
        .sidebar-brand { padding: 30px 20px; font-size: 20px; font-weight: 700; color: var(--accent-gold); letter-spacing: 1px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border-color); }
        .sidebar-menu { list-style: none; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 15px; color: var(--text-muted); text-decoration: none; padding: 12px 15px; border-radius: 8px; transition: all 0.3s ease; font-size: 15px; }
        .sidebar-menu li a:hover, .sidebar-menu li.active a { background-color: var(--bg-tertiary); color: var(--accent-gold); }
        .sidebar-footer { margin-top: auto; padding: 20px; border-top: 1px solid var(--border-color); }
        .btn-logout { display: flex; align-items: center; justify-content: center; gap: 10px; background-color: #ff3333; color: white; text-decoration: none; padding: 12px; border-radius: 8px; font-weight: 600; transition: 0.3s ease; }
        .btn-logout:hover { background-color: #cc0000; }

        .main-content { margin-left: 260px; flex: 1; padding: 40px; }
        .header-section { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-section h1 { font-size: 28px; font-weight: 700; }
        .header-section p { color: var(--text-muted); font-size: 14px; }

        .card-container { background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--accent-gold); }

        .search-bar { display: flex; gap: 15px; margin-bottom: 20px; }
        .form-control { background-color: var(--bg-primary); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 6px; color: var(--text-main); font-size: 14px; transition: 0.3s; flex: 1; }
        .form-control:focus { border-color: var(--accent-gold); outline: none; }
        
        .btn-submit { background-color: var(--accent-gold); color: #000000; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background-color: #e5a700; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: rgba(0, 255, 102, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 102, 0.2); }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); font-weight: 500; }
        
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-active { background-color: rgba(0, 255, 102, 0.2); color: var(--accent-green); }
        .badge-suspended { background-color: rgba(255, 51, 51, 0.2); color: #ff3333; }

        .actions-flex { display: flex; gap: 15px; align-items: center; }
        .btn-action { text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; background: none; border: none; }
        .btn-suspend { color: #ff9900; }
        .btn-activate { color: var(--accent-green); }
        .btn-delete { color: #ff3333; }
        .btn-reset { color: var(--accent-gold); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-chart-line"></i>
            <span>MASUMX ADMIN</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="videos.php"><i class="fa-solid fa-video"></i> Video Manager</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-list"></i> Category Manager</a></li>
            <li class="active"><a href="users.php"><i class="fa-solid fa-users"></i> User Manager</a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gears"></i> Website Controls</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-section">
            <div>
                <h1>User Base Management</h1>
                <p>Manage, suspend, or trigger credentials resets dynamically.</p>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="card-container">
            <h2 class="card-title">Registered Members</h2>
            
            <form method="GET" action="" class="search-bar">
                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-submit">Search</button>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Joined Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Verification</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $usr): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($usr['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                            <td>
                                <?php if ($usr['is_verified']): ?>
                                    <span class="badge badge-active">Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-suspended">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($usr['status'] === 'active'): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-suspended">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-flex">
                                    <a href="users.php?toggle_status=<?php echo $usr['status']; ?>&id=<?php echo $usr['id']; ?>" class="btn-action <?php echo ($usr['status'] === 'active') ? 'btn-suspend' : 'btn-activate'; ?>">
                                        <?php echo ($usr['status'] === 'active') ? 'Suspend' : 'Activate'; ?>
                                    </a>
                                    
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                        <button type="submit" name="reset_password" class="btn-action btn-reset" onclick="return confirm('Reset this user password to 12345678?');">Reset PW</button>
                                    </form>

                                    <a href="users.php?delete=<?php echo $usr['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this user permanently?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
