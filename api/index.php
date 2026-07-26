<?php
/**
 * MASUMX TRADE - API Gateway Router & REST Controller Endpoint
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';

$database = new Database();
$db = $database->getConnection();

$request_method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Simplified REST API routing structure
// Expects URL structure like: /api/videos, /api/categories, /api/settings, /api/bookmarks
$resource = isset($uri[2]) ? $uri[2] : null;
$id = isset($uri[3]) ? intval($uri[3]) : null;

// Security check helper
function checkAdminAPI() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized access. Admin privileges required."]);
        exit;
    }
}

switch($resource) {
    case 'videos':
        handleVideos($db, $request_method, $id);
        break;
    case 'categories':
        handleCategories($db, $request_method, $id);
        break;
    case 'settings':
        handleSettings($db, $request_method, $id);
        break;
    case 'bookmarks':
        handleBookmarks($db, $request_method, $id);
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "API Resource not found."]);
        break;
}

function handleVideos($db, $method, $id) {
    switch($method) {
        case 'GET':
            if ($id) {
                $stmt = $db->prepare("SELECT v.*, c.name as category_name FROM videos v JOIN categories c ON v.category_id = c.id WHERE v.id = ?");
                $stmt->execute([$id]);
                $video = $stmt->fetch();
                if ($video) {
                    // Update views counter securely
                    $db->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$id]);
                    echo json_encode($video);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Video not found"]);
                }
            } else {
                $category = isset($_GET['category']) ? $_GET['category'] : null;
                $search = isset($_GET['search']) ? $_GET['search'] : null;
                $type = isset($_GET['type']) ? $_GET['type'] : null; // featured, trending, premium
                
                $query = "SELECT v.*, c.name as category_name FROM videos v JOIN categories c ON v.category_id = c.id WHERE 1=1";
                $params = [];
                
                if ($category) {
                    $query .= " AND c.slug = ?";
                    $params[] = $category;
                }
                if ($search) {
                    $query .= " AND (v.title LIKE ? OR v.description LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                if ($type === 'featured') {
                    $query .= " AND v.is_featured = 1";
                } elseif ($type === 'trending') {
                    $query .= " AND v.is_trending = 1";
                } elseif ($type === 'premium') {
                    $query .= " AND v.is_premium = 1";
                }
                
                $query .= " ORDER BY v.created_at DESC";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                echo json_encode($stmt->fetchAll());
            }
            break;
            
        case 'POST':
            checkAdminAPI();
            $data = json_decode(file_get_contents("php://input"), true);
            if (!empty($data['title']) && !empty($data['video_url'])) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title'])));
                $stmt = $db->prepare("INSERT INTO videos (category_id, title, slug, description, video_type, video_url, thumbnail_url, duration, instructor, is_featured, is_trending, is_premium, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['category_id'], $data['title'], $slug, $data['description'] ?? '',
                    $data['video_type'] ?? 'youtube', $data['video_url'], $data['thumbnail_url'] ?? '/assets/images/default.jpg',
                    $data['duration'] ?? '00:00', $data['instructor'] ?? 'Admin',
                    $data['is_featured'] ?? 0, $data['is_trending'] ?? 0, $data['is_premium'] ?? 0,
                    $data['seo_title'] ?? $data['title'], $data['seo_description'] ?? ''
                ]);
                echo json_encode(["message" => "Video uploaded successfully", "id" => $db->lastInsertId()]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Incomplete parameters"]);
            }
            break;
            
        case 'PUT':
            checkAdminAPI();
            if (!$id) {
                http_response_code(400);
                echo json_encode(["message" => "ID is required for update"]);
                break;
            }
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $db->prepare("UPDATE videos SET category_id = ?, title = ?, description = ?, video_type = ?, video_url = ?, thumbnail_url = ?, duration = ?, instructor = ?, is_featured = ?, is_trending = ?, is_premium = ?, seo_title = ?, seo_description = ? WHERE id = ?");
            $stmt->execute([
                $data['category_id'], $data['title'], $data['description'],
                $data['video_type'], $data['video_url'], $data['thumbnail_url'],
                $data['duration'], $data['instructor'], $data['is_featured'],
                $data['is_trending'], $data['is_premium'], $data['seo_title'],
                $data['seo_description'], $id
            ]);
            echo json_encode(["message" => "Video updated successfully"]);
            break;
            
        case 'DELETE':
            checkAdminAPI();
            if (!$id) {
                http_response_code(400);
                echo json_encode(["message" => "ID is required for deletion"]);
                break;
            }
            $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Video deleted successfully"]);
            break;
    }
}

function handleCategories($db, $method, $id) {
    switch($method) {
        case 'GET':
            $stmt = $db->prepare("SELECT * FROM categories ORDER BY sort_order ASC");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;
            
        case 'POST':
            checkAdminAPI();
            $data = json_decode(file_get_contents("php://input"), true);
            if (!empty($data['name'])) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
                $stmt = $db->prepare("INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$data['name'], $slug, $data['sort_order'] ?? 0]);
                echo json_encode(["message" => "Category created successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Category name is empty"]);
            }
            break;
            
        case 'DELETE':
            checkAdminAPI();
            if (!$id) {
                http_response_code(400);
                echo json_encode(["message" => "ID is required"]);
                break;
            }
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Category deleted successfully"]);
            break;
    }
}

function handleSettings($db, $method, $id) {
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT key_name, val_value FROM settings");
        $stmt->execute();
        $settings = [];
        while($row = $stmt->fetch()) {
            $settings[$row['key_name']] = $row['val_value'];
        }
        echo json_encode($settings);
    } elseif ($method === 'POST') {
        checkAdminAPI();
        $data = json_decode(file_get_contents("php://input"), true);
        if (is_array($data)) {
            $stmt = $db->prepare("INSERT INTO settings (key_name, val_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE val_value = ?");
            foreach($data as $key => $val) {
                $stmt->execute([$key, $val, $val]);
            }
            echo json_encode(["message" => "Settings updated successfully"]);
        }
    }
}

function handleBookmarks($db, $method, $id) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["message" => "User login required"]);
        return;
    }
    $user_id = $_SESSION['user_id'];
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT v.* FROM bookmarks b JOIN videos v ON b.video_id = v.id WHERE b.user_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['video_id'])) {
            $stmt = $db->prepare("INSERT INTO bookmarks (user_id, video_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id");
            $stmt->execute([$user_id, $data['video_id']]);
            echo json_encode(["message" => "Bookmarked successfully"]);
        }
    } elseif ($method === 'DELETE') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(["message" => "Video ID is required"]);
            return;
        }
        $stmt = $db->prepare("DELETE FROM bookmarks WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $id]);
        echo json_encode(["message" => "Bookmark removed"]);
    }
}
