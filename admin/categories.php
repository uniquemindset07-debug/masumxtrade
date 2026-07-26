<?php
/**
 * MASUMX TRADE - Admin Category Management Panel
 */
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-admin.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle Category Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token validation failed.");
    }
    $name = sanitize_input($_POST['name']);
    $sort_order = intval($_POST['sort_order']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $slug, $sort_order]);
            $message = "<p class='alert alert-success'>Category created successfully!</p>";
        } catch (PDOException $e) {
            $message = "<p class='alert alert-danger'>Error: Category might already exist.</p>";
        }
    }
}

// Handle Category Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<p class='alert alert-success'>Category deleted successfully!</p>";
}

// Fetch all categories
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | MASUMX TRADE</title>
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
        .header-section { margin-bottom: 30px; }
        .header-section h1 { font-size: 28px; font-weight: 700; }
        .header-section p { color: var(--text-muted); font-size: 14px; }

        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .card-container { background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; height: fit-content; }
        .card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--accent-gold); }

        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 13px; color: var(--text-muted); }
        .form-control { background-color: var(--bg-primary); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 6px; color: var(--text-main); font-size: 14px; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent-gold); outline: none; }

        .btn-submit { background-color: var(--accent-gold); color: #000000; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: #e5a700; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: rgba(0, 255, 102, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 102, 0.2); }
        .alert-danger { background-color: rgba(255, 51, 51, 0.1); color: #ff3333; border: 1px solid rgba(255, 51, 51, 0.2); }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); font-weight: 500; }
        .btn-delete { color: #ff3333; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-delete:hover { text-decoration: underline; }
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
            <li class="active"><a href="categories.php"><i class="fa-solid fa-list"></i> Category Manager</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Manager</a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gears"></i> Website Controls</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-section">
            <h1>Category Management</h1>
            <p>Group and structure dynamic platform content easily.</p>
        </div>

        <?php echo $message; ?>

        <div class="grid-layout">
            <div class="card-container">
                <h2 class="card-title">Add Category</h2>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Binary Options" required>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0" required>
                    </div>
                    <button type="submit" name="create_category" class="btn-submit">Add Category</button>
                </form>
            </div>

            <div class="card-container">
                <h2 class="card-title">All Categories</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Sort Order</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                            <td><?php echo $cat['sort_order']; ?></td>
                            <td>
                                <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category? All associated videos will be lost.');">Delete</a>
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
