<?php
/**
 * MASUMX TRADE - Admin Panel Core Authentication & Dashboard Layout
 */
require_once __DIR__ . '/../config/config.php';

// Force Admin Session Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-admin.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Fetch System Stats securely
$users_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$videos_count = $db->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$views_count = $db->query("SELECT SUM(views) FROM videos")->fetchColumn() ?? 0;

$latest_users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$latest_videos = $db->query("SELECT v.*, c.name as category_name FROM videos v JOIN categories c ON v.category_id = c.id ORDER BY v.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MASUMX TRADE</title>
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .sidebar-brand {
            padding: 30px 20px;
            font-size: 20px;
            font-weight: 700;
            color: var(--accent-gold);
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-muted);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .sidebar-menu li a:hover, .sidebar-menu li.active a {
            background-color: var(--bg-tertiary);
            color: var(--accent-gold);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background-color: #ff3333;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s ease;
        }

        .btn-logout:hover {
            background-color: #cc0000;
        }

        /* Main Content Styling */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 40px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-title h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Metrics Widget Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-gold);
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 5px;
        }

        .metric-label {
            color: var(--text-muted);
            font-size: 14px;
        }

        .metric-icon {
            font-size: 32px;
            color: var(--accent-gold);
            background: rgba(255, 189, 0, 0.1);
            padding: 15px;
            border-radius: 12px;
        }

        .metric-icon.green {
            color: var(--accent-green);
            background: rgba(0, 255, 102, 0.1);
        }

        /* Charts & Lists Styling */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .card-container {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-main);
        }

        /* Tables & Lists */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        th {
            color: var(--text-muted);
            font-weight: 500;
        }

        td {
            color: var(--text-main);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-premium {
            background-color: rgba(255, 189, 0, 0.2);
            color: var(--accent-gold);
        }

        .badge-free {
            background-color: rgba(0, 255, 102, 0.2);
            color: var(--accent-green);
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-chart-line"></i>
            <span>MASUMX ADMIN</span>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="videos.php"><i class="fa-solid fa-video"></i> Video Manager</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-list"></i> Category Manager</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Manager</a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gears"></i> Website Controls</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header-section">
            <div class="header-title">
                <h1>Welcome Back, Admin</h1>
                <p>Monitor platform growth, edit dynamic page components, and view traffic statistics.</p>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div>
                    <span class="metric-label">Total Users Registered</span>
                    <div class="metric-value"><?php echo $users_count; ?></div>
                </div>
                <div class="metric-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="metric-card">
                <div>
                    <span class="metric-label">Total Video Content</span>
                    <div class="metric-value"><?php echo $videos_count; ?></div>
                </div>
                <div class="metric-icon green"><i class="fa-solid fa-circle-play"></i></div>
            </div>
            <div class="metric-card">
                <div>
                    <span class="metric-label">Total Video Views</span>
                    <div class="metric-value"><?php echo number_format($views_count); ?></div>
                </div>
                <div class="metric-icon"><i class="fa-solid fa-eye"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Latest Uploaded Videos Section -->
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">Latest Uploaded Videos</h2>
                    <a href="videos.php" style="color: var(--accent-gold); font-size: 14px; text-decoration: none;">Manage All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_videos as $vid): ?>
                            <tr>
                                <td><img src="<?php echo $vid['thumbnail_url']; ?>" alt="" style="width: 60px; height: 35px; border-radius: 4px; object-fit: cover;"></td>
                                <td><strong><?php echo htmlspecialchars($vid['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vid['category_name']); ?></td>
                                <td>
                                    <?php if ($vid['is_premium']): ?>
                                        <span class="badge badge-premium">Premium</span>
                                    <?php else: ?>
                                        <span class="badge badge-free">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="fa-solid fa-eye"></i> <?php echo number_format($vid['views']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Users Section -->
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">Recent Signups</h2>
                </div>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($latest_users as $usr): ?>
                    <li style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div>
                            <p style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($usr['name']); ?></p>
                            <span style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($usr['email']); ?></span>
                        </div>
                        <span class="badge" style="background-color: rgba(0, 255, 102, 0.1); color: var(--accent-green);">Active</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

</body>
</html>
