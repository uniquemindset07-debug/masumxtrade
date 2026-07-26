<?php
/**
 * MASUMX TRADE - Admin Video Management Page
 */
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-admin.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle Video Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<p class='alert alert-success'>Video deleted successfully!</p>";
}

// Handle Video Upload / Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token validation failed.");
    }
    $title = sanitize_input($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $description = sanitize_input($_POST['description']);
    $video_type = sanitize_input($_POST['video_type']);
    $video_url = sanitize_input($_POST['video_url']);
    $thumbnail_url = sanitize_input($_POST['thumbnail_url']);
    $duration = sanitize_input($_POST['duration']);
    $instructor = sanitize_input($_POST['instructor']);
    
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;

    $seo_title = sanitize_input($_POST['seo_title']) ?: $title;
    $seo_description = sanitize_input($_POST['seo_description']) ?: $description;

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    if (!empty($title) && !empty($video_url)) {
        $stmt = $db->prepare("INSERT INTO videos (category_id, title, slug, description, video_type, video_url, thumbnail_url, duration, instructor, is_featured, is_trending, is_premium, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $category_id, $title, $slug, $description, $video_type, $video_url, 
                $thumbnail_url, $duration, $instructor, $is_featured, $is_trending, 
                $is_premium, $seo_title, $seo_description
            ]);
            $message = "<p class='alert alert-success'>Video uploaded and indexed successfully!</p>";
        } catch (PDOException $e) {
            $message = "<p class='alert alert-danger'>Error indexing video. Make sure title is unique.</p>";
        }
    }
}

// Fetch categories and videos
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
$videos = $db->query("SELECT v.*, c.name as category_name FROM videos v JOIN categories c ON v.category_id = c.id ORDER BY v.created_at DESC")->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Management | MASUMX TRADE</title>
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

        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .card-container { background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; height: fit-content; }
        .card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--accent-gold); }

        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 13px; color: var(--text-muted); }
        .form-control { background-color: var(--bg-primary); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 6px; color: var(--text-main); font-size: 14px; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent-gold); outline: none; }
        
        .form-row-checkbox { display: flex; gap: 15px; margin: 15px 0; }
        .checkbox-container { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); cursor: pointer; }

        .btn-submit { background-color: var(--accent-gold); color: #000000; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: #e5a700; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: rgba(0, 255, 102, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 102, 0.2); }
        .alert-danger { background-color: rgba(255, 51, 51, 0.1); color: #ff3333; border: 1px solid rgba(255, 51, 51, 0.2); }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); font-weight: 500; }
        
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-premium { background-color: rgba(255, 189, 0, 0.2); color: var(--accent-gold); }
        .badge-free { background-color: rgba(0, 255, 102, 0.2); color: var(--accent-green); }

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
            <li class="active"><a href="videos.php"><i class="fa-solid fa-video"></i> Video Manager</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-list"></i> Category Manager</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Manager</a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gears"></i> Website Controls</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-section">
            <div>
                <h1>Video Management</h1>
                <p>Upload, update, configure SEO, and flag premium courses instantly.</p>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="grid-layout">
            <!-- Add Video Form -->
            <div class="card-container">
                <h2 class="card-title">Upload Video Meta</h2>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Video Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Price Action Strategy">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Explain what is inside the video..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Hosting Source Type</label>
                        <select name="video_type" class="form-control" required>
                            <option value="youtube">YouTube Embed Link</option>
                            <option value="vimeo">Vimeo Embed Link</option>
                            <option value="mp4">Self Hosted MP4 Link</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Video URL / Embed Link</label>
                        <input type="url" name="video_url" class="form-control" required placeholder="https://youtube.com/embed/...">
                    </div>
                    <div class="form-group">
                        <label>Thumbnail Image URL</label>
                        <input type="text" name="thumbnail_url" class="form-control" required placeholder="/assets/images/thumb-default.jpg">
                    </div>
                    <div class="form-group">
                        <label>Video Duration</label>
                        <input type="text" name="duration" class="form-control" value="10:00" placeholder="e.g. 15:30" required>
                    </div>
                    <div class="form-group">
                        <label>Instructor Name</label>
                        <input type="text" name="instructor" class="form-control" value="Masum X" required>
                    </div>

                    <div class="form-row-checkbox">
                        <label class="checkbox-container">
                            <input type="checkbox" name="is_featured" value="1"> Featured
                        </label>
                        <label class="checkbox-container">
                            <input type="checkbox" name="is_trending" value="1"> Trending
                        </label>
                        <label class="checkbox-container">
                            <input type="checkbox" name="is_premium" value="1"> Premium
                        </label>
                    </div>

                    <h3 class="card-title" style="font-size: 14px; margin-top: 20px;">SEO Settings</h3>
                    <div class="form-group">
                        <label>SEO Meta Title</label>
                        <input type="text" name="seo_title" class="form-control" placeholder="Target search keyword title">
                    </div>
                    <div class="form-group">
                        <label>SEO Meta Description</label>
                        <input type="text" name="seo_description" class="form-control" placeholder="Short summary of the trading video">
                    </div>

                    <button type="submit" name="add_video" class="btn-submit" style="width: 100%;">Index Video</button>
                </form>
            </div>

            <!-- Videos List Grid -->
            <div class="card-container">
                <h2 class="card-title">All Dynamic Videos</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Access</th>
                                <th>Views</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videos as $vid): ?>
                            <tr>
                                <td><img src="<?php echo $vid['thumbnail_url']; ?>" alt="" style="width: 60px; height: 35px; border-radius: 4px; object-fit: cover;"></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($vid['title']); ?></strong>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">Instructor: <?php echo htmlspecialchars($vid['instructor']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($vid['category_name']); ?></td>
                                <td>
                                    <?php if ($vid['is_premium']): ?>
                                        <span class="badge badge-premium">Premium</span>
                                    <?php else: ?>
                                        <span class="badge badge-free">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($vid['views']); ?></td>
                                <td>
                                    <a href="videos.php?delete=<?php echo $vid['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this video?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
