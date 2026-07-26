<?php
/**
 * MASUMX TRADE - Admin Settings & Home Page Dynamic Controller Page
 */
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-admin.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle Settings & Home Page Customizer Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token validation failed.");
    }
    
    $settings_to_update = [
        'website_name' => sanitize_input($_POST['website_name']),
        'primary_color' => sanitize_input($_POST['primary_color']),
        'secondary_color' => sanitize_input($_POST['secondary_color']),
        'telegram_url' => sanitize_input($_POST['telegram_url']),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'hero_title' => sanitize_input($_POST['hero_title']),
        'hero_subtitle' => sanitize_input($_POST['hero_subtitle']),
        'seo_title' => sanitize_input($_POST['seo_title']),
        'seo_description' => sanitize_input($_POST['seo_description']),
        'seo_keywords' => sanitize_input($_POST['seo_keywords']),
        'contact_email' => sanitize_input($_POST['contact_email']),
        'contact_phone' => sanitize_input($_POST['contact_phone']),
        'contact_address' => sanitize_input($_POST['contact_address'])
    ];

    $stmt = $db->prepare("INSERT INTO settings (key_name, val_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE val_value = ?");
    foreach ($settings_to_update as $key => $val) {
        $stmt->execute([$key, $val, $val]);
    }
    $message = "<p class='alert alert-success'>Website settings & controls updated successfully!</p>";
}

// Fetch dynamic settings values
$stmt = $db->query("SELECT * FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['key_name']] = $s['val_value'];
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Settings | MASUMX TRADE</title>
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

        .card-container { background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--accent-gold); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 13px; color: var(--text-muted); }
        .form-control { background-color: var(--bg-primary); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 6px; color: var(--text-main); font-size: 14px; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent-gold); outline: none; }
        
        .checkbox-row { display: flex; align-items: center; gap: 10px; margin: 15px 0; }
        
        .btn-submit { background-color: var(--accent-gold); color: #000000; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 15px; margin-top: 15px; }
        .btn-submit:hover { background-color: #e5a700; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: rgba(0, 255, 102, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 102, 0.2); }
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
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Manager</a></li>
            <li class="active"><a href="settings.php"><i class="fa-solid fa-gears"></i> Website Controls</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-section">
            <h1>Global Settings & Page Controls</h1>
            <p>Customize primary UI parameters, HERO information, dynamic values, and SEO tags without writing code.</p>
        </div>

        <?php echo $message; ?>

        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <!-- Global Brand settings -->
            <div class="card-container">
                <h2 class="card-title">Brand & Theme Configuration</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Website Name</label>
                        <input type="text" name="website_name" class="form-control" value="<?php echo htmlspecialchars($settings['website_name'] ?? 'MASUMX TRADE'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Primary UI Color (Accent Gold)</label>
                        <input type="text" name="primary_color" class="form-control" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#ffbd00'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Secondary UI Color (Accent Green)</label>
                        <input type="text" name="secondary_color" class="form-control" value="<?php echo htmlspecialchars($settings['secondary_color'] ?? '#00ff66'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telegram Channel Link</label>
                        <input type="url" name="telegram_url" class="form-control" value="<?php echo htmlspecialchars($settings['telegram_url'] ?? 'https://t.me/masumxtrade'); ?>" required>
                    </div>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?php echo (($settings['maintenance_mode'] ?? '0') === '1') ? 'checked' : ''; ?>>
                    <label for="maintenance_mode" style="font-size: 14px; cursor: pointer;">Enable System Maintenance Mode</label>
                </div>
            </div>

            <!-- Home Hero dynamic options -->
            <div class="card-container">
                <h2 class="card-title">Homepage HERO Section Customizer</h2>
                <div class="form-group">
                    <label>Hero Heading</label>
                    <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Learn Trading Through Professional Video Tutorials'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Hero Subtitle Text</label>
                    <textarea name="hero_subtitle" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['hero_subtitle'] ?? 'Master Trading with Premium Educational Videos.'); ?></textarea>
                </div>
            </div>

            <!-- SEO Controls -->
            <div class="card-container">
                <h2 class="card-title">Dynamic SEO & Meta Control</h2>
                <div class="form-group">
                    <label>Global SEO Title Tag</label>
                    <input type="text" name="seo_title" class="form-control" value="<?php echo htmlspecialchars($settings['seo_title'] ?? 'MASUMX TRADE | Premium Educational Trading Platform'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea name="seo_description" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['seo_description'] ?? 'Learn Binary Options, Forex, Price Action, and Advanced Candlestick patterns.'); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Keywords (Comma separated)</label>
                    <input type="text" name="seo_keywords" class="form-control" value="<?php echo htmlspecialchars($settings['seo_keywords'] ?? 'trading, binary options, forex, courses'); ?>" required>
                </div>
            </div>

            <!-- Contact Controls -->
            <div class="card-container">
                <h2 class="card-title">Contact & Footer Configuration</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Support Email Address</label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'support@masumxtrade.com'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Business Phone Number</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+880123456789'); ?>" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Corporate Office Address</label>
                        <input type="text" name="contact_address" class="form-control" value="<?php echo htmlspecialchars($settings['contact_address'] ?? 'Dhaka, Bangladesh'); ?>" required>
                    </div>
                </div>
            </div>

            <button type="submit" name="save_settings" class="btn-submit">Save Live Changes</button>
        </form>
    </div>

</body>
</html>
