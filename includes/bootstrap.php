<?php
if (!defined('APP')) {
    http_response_code(403);
    exit;
}

define('APP_ROOT', dirname(__DIR__));

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_name('hail_projects');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function uses_sqlite(): bool
{
    $flag = getenv('USE_SQLITE');
    return $flag === '1' || strtolower((string) $flag) === 'true';
}

function sqlite_path(): string
{
    $path = getenv('SQLITE_PATH');
    return $path !== false && $path !== '' ? $path : APP_ROOT . '/database/demo.sqlite';
}

function app_config(): array
{
    if (uses_sqlite()) {
        return ['driver' => 'sqlite', 'path' => sqlite_path()];
    }
    $file = APP_ROOT . '/config/config.php';
    if (!is_file($file)) {
        return ['driver' => 'mysql', 'host' => '127.0.0.1', 'name' => 'amanah_hail', 'user' => 'root', 'pass' => ''];
    }
    $config = require $file;
    $config = is_array($config) ? $config : [];
    $config['driver'] = 'mysql';
    return $config;
}

function render_db_error(PDOException $e): void
{
    $raw = $e->getMessage();
    $hint = 'تعذر الاتصال بقاعدة البيانات.';
    if (strpos($raw, '2002') !== false || stripos($raw, 'refused') !== false) {
        $hint = 'خدمة MySQL غير شغّالة. افتح لوحة XAMPP وشغّل MySQL ثم أعد المحاولة.';
    } elseif (strpos($raw, '1049') !== false || stripos($raw, 'Unknown database') !== false) {
        $hint = 'قاعدة البيانات غير موجودة بعد. افتح صفحة التثبيت لإنشائها مع المشاريع التجريبية.';
    } elseif (strpos($raw, '1045') !== false) {
        $hint = 'بيانات دخول قاعدة البيانات غير صحيحة. صحّحها من صفحة التثبيت.';
    }
    $install = app_prefix() . 'install.php';
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>اتصال قاعدة البيانات</title><link rel="stylesheet" href="' . e(app_prefix() . 'assets/css/style.css') . '"></head>';
    echo '<body class="is-public"><main class="container install-card"><p class="kicker">أمانة منطقة حائل</p><h1>قاعدة البيانات غير جاهزة</h1><p>' . e($hint) . '</p>';
    echo '<p><a class="btn btn-primary" href="' . e($install) . '">فتح صفحة التثبيت</a></p>';
    echo '<p class="muted">' . e($raw) . '</p></main></body></html>';
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = app_config();
    try {
        if (($c['driver'] ?? 'mysql') === 'sqlite') {
            $path = $c['path'];
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='projects'")->fetchColumn();
            if (!$exists) {
                $sql = file_get_contents(APP_ROOT . '/database/schema.sqlite.sql');
                $pdo->exec($sql);
            }
        } else {
            $dsn = 'mysql:host=' . $c['host'] . ';dbname=' . $c['name'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    } catch (PDOException $e) {
        render_db_error($e);
    }
    return $pdo;
}

function app_prefix(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return preg_match('#/(admin|api)(/|$)#', $script) ? '../' : '';
}

function root_url(string $path = ''): string
{
    return app_prefix() . ltrim($path, '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    $sent = $_POST['csrf'] ?? '';
    $real = $_SESSION['csrf'] ?? '';
    return is_string($sent) && is_string($real) && $real !== '' && hash_equals($real, $sent);
}

function flash(?string $message = null, string $type = 'ok'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $item = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $item;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_admin(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function ar_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '—';
    }
    $months = [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'];
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function ar_days(int $n): string
{
    if ($n === 1) {
        return 'يوم واحد';
    }
    if ($n === 2) {
        return 'يومان';
    }
    if ($n >= 3 && $n <= 10) {
        return $n . ' أيام';
    }
    return $n . ' يومًا';
}

function days_overdue(array $project): ?int
{
    if (($project['status'] ?? '') === 'completed' || empty($project['expected_date'])) {
        return null;
    }
    $diff = (strtotime(date('Y-m-d')) - strtotime($project['expected_date'])) / 86400;
    return $diff > 0 ? (int) floor($diff) : null;
}

function schedule_label(array $project): string
{
    if (($project['status'] ?? '') === 'completed') {
        return 'أُنجز في ' . ar_date($project['expected_date'] ?? null);
    }
    $overdue = days_overdue($project);
    if ($overdue !== null && $overdue > 0) {
        return 'متأخر ' . ar_days($overdue);
    }
    if (empty($project['expected_date'])) {
        return '—';
    }
    $left = (int) floor((strtotime($project['expected_date']) - strtotime(date('Y-m-d'))) / 86400);
    if ($left === 0) {
        return 'الموعد اليوم';
    }
    if ($left > 0) {
        return 'متبقٍ ' . ar_days($left);
    }
    return ar_date($project['expected_date']);
}

function sar($amount): string
{
    if ($amount === null || $amount === '') {
        return 'غير محدد';
    }
    return number_format((float) $amount, 0, '.', ',') . ' ر.س';
}

function statuses(): array
{
    return [
        'planned' => 'مخطط',
        'in_progress' => 'قيد التنفيذ',
        'needs_followup' => 'يحتاج متابعة',
        'delayed' => 'متأخر',
        'on_hold' => 'متوقف',
        'completed' => 'مكتمل',
    ];
}

function status_label(string $status): string
{
    return statuses()[$status] ?? $status;
}

function priorities(): array
{
    return [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'critical' => 'حرجة',
    ];
}

function priority_label(string $priority): string
{
    return priorities()[$priority] ?? $priority;
}

function project_alert_reasons(array $project): array
{
    if (($project['status'] ?? '') === 'completed') {
        return [];
    }
    $reasons = [];
    $today = date('Y-m-d');
    $status = $project['status'] ?? '';
    if ($status === 'needs_followup') {
        $reasons[] = ['level' => 'high', 'text' => 'المشروع مصنّف ضمن ما يحتاج متابعة'];
    }
    if ($status === 'delayed') {
        $reasons[] = ['level' => 'high', 'text' => 'الحالة مسجّلة متأخرة'];
    }
    if ($status === 'on_hold') {
        $reasons[] = ['level' => 'medium', 'text' => 'المشروع متوقف ويحتاج قرار استئناف'];
    }
    if (!empty($project['expected_date']) && $project['expected_date'] < $today) {
        $reasons[] = ['level' => 'high', 'text' => 'تجاوز الموعد المتوقع للإنجاز'];
    }
    $start = !empty($project['start_date']) ? strtotime($project['start_date']) : false;
    $end = !empty($project['expected_date']) ? strtotime($project['expected_date']) : false;
    $now = strtotime($today);
    if ($start && $end && $end > $start && $now > $start && $now < $end) {
        $elapsed = ($now - $start) / ($end - $start);
        if ($elapsed * 100 > ((int) $project['progress']) + 25) {
            $reasons[] = ['level' => 'medium', 'text' => 'نسبة الإنجاز أقل من الزمن المنقضي بفارق ملحوظ'];
        }
    }
    if (($project['priority'] ?? '') === 'critical' && (int) $project['progress'] < 50) {
        $reasons[] = ['level' => 'high', 'text' => 'أولوية حرجة ونسبة الإنجاز دون النصف'];
    }
    $note = trim((string) ($project['follow_note'] ?? ''));
    if ($note !== '') {
        $reasons[] = ['level' => 'medium', 'text' => 'ملاحظة المتابعة: ' . $note];
    }
    return $reasons;
}

function alert_level(array $reasons): string
{
    foreach ($reasons as $reason) {
        if (($reason['level'] ?? '') === 'high') {
            return 'high';
        }
    }
    return 'medium';
}

function filters_from_request(): array
{
    $filters = [];
    $year = (int) ($_GET['year'] ?? 0);
    if (in_array($year, [2024, 2025, 2026], true)) {
        $filters['year'] = $year;
    }
    $agency = (int) ($_GET['agency'] ?? 0);
    if ($agency > 0) {
        $filters['agency'] = $agency;
    }
    $department = (int) ($_GET['dept'] ?? 0);
    if ($department > 0) {
        $filters['department'] = $department;
    }
    $status = (string) ($_GET['status'] ?? '');
    if (isset(statuses()[$status])) {
        $filters['status'] = $status;
    }
    $sort = (string) ($_GET['sort'] ?? '');
    if (in_array($sort, ['due', 'progress', 'alert'], true)) {
        $filters['sort'] = $sort;
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '') {
        $filters['q'] = mb_substr($q, 0, 80);
    }
    return $filters;
}

function page_query(array $overrides = [], array $remove = []): string
{
    $query = $_GET;
    foreach ($remove as $key) {
        unset($query[$key]);
    }
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }
    return $query ? ('?' . http_build_query($query)) : '';
}

function sort_projects(array $rows, string $sort): array
{
    if ($sort === 'due') {
        usort($rows, fn($a, $b) => strcmp($a['expected_date'], $b['expected_date']));
    } elseif ($sort === 'progress') {
        usort($rows, fn($a, $b) => ((int) $a['progress']) <=> ((int) $b['progress']));
    } elseif ($sort === 'alert') {
        usort($rows, function ($a, $b) {
            $alert = (empty($a['alerts']) ? 1 : 0) <=> (empty($b['alerts']) ? 1 : 0);
            return $alert !== 0 ? $alert : strcmp($a['expected_date'], $b['expected_date']);
        });
    }
    return $rows;
}

function fetch_projects(array $filters = []): array
{
    $where = ['p.announced_year IN (2024, 2025, 2026)'];
    $params = [];
    if (!empty($filters['year'])) {
        $where[] = 'p.announced_year = ?';
        $params[] = (int) $filters['year'];
    }
    if (!empty($filters['agency'])) {
        $where[] = 'a.id = ?';
        $params[] = (int) $filters['agency'];
    }
    if (!empty($filters['department'])) {
        $where[] = 'd.id = ?';
        $params[] = (int) $filters['department'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'p.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(p.title LIKE ? OR p.summary LIKE ? OR p.location_name LIKE ? OR d.name LIKE ? OR a.name LIKE ? OR p.code LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $sql = 'SELECT p.*, d.name AS department_name, a.id AS agency_id, a.name AS agency_name,
                   a.color AS agency_color, a.slug AS agency_slug,
                   (SELECT i.file_path FROM project_images i WHERE i.project_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover
            FROM projects p
            INNER JOIN departments d ON d.id = p.department_id
            INNER JOIN agencies a ON a.id = d.agency_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.announced_year DESC, p.expected_date ASC, p.id DESC';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $row['alerts'] = project_alert_reasons($row);
    }
    unset($row);
    return sort_projects($rows, $filters['sort'] ?? 'default');
}

function fetch_project(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT p.*, d.name AS department_name, a.id AS agency_id, a.name AS agency_name,
                a.color AS agency_color, a.slug AS agency_slug, a.description AS agency_description
         FROM projects p
         INNER JOIN departments d ON d.id = p.department_id
         INNER JOIN agencies a ON a.id = d.agency_id
         WHERE p.id = ? AND p.announced_year IN (2024, 2025, 2026)'
    );
    $statement->execute([$id]);
    $project = $statement->fetch();
    if (!$project) {
        return null;
    }
    $project['alerts'] = project_alert_reasons($project);
    return $project;
}

function fetch_agencies(): array
{
    return db()->query(
        'SELECT a.id, a.name, a.slug, a.description, a.color, a.icon,
                COUNT(DISTINCT d.id) AS department_count,
                COUNT(p.id) AS project_count,
                COALESCE(ROUND(AVG(p.progress)), 0) AS avg_progress
         FROM agencies a
         LEFT JOIN departments d ON d.agency_id = a.id
         LEFT JOIN projects p ON p.department_id = d.id AND p.announced_year IN (2024, 2025, 2026)
         GROUP BY a.id, a.name, a.slug, a.description, a.color, a.icon
         ORDER BY a.id'
    )->fetchAll();
}

function fetch_departments(): array
{
    return db()->query(
        'SELECT d.*, a.name AS agency_name, a.color AS agency_color, a.id AS agency_id,
                (SELECT COUNT(*) FROM projects p WHERE p.department_id = d.id AND p.announced_year IN (2024, 2025, 2026)) AS project_count
         FROM departments d
         INNER JOIN agencies a ON a.id = d.agency_id
         ORDER BY a.id, d.id'
    )->fetchAll();
}

function fetch_updates(int $projectId): array
{
    $statement = db()->prepare('SELECT * FROM project_updates WHERE project_id = ? ORDER BY created_at DESC, id DESC');
    $statement->execute([$projectId]);
    return $statement->fetchAll();
}

function fetch_images(int $projectId): array
{
    $statement = db()->prepare('SELECT * FROM project_images WHERE project_id = ? ORDER BY is_cover DESC, id ASC');
    $statement->execute([$projectId]);
    return $statement->fetchAll();
}

function fetch_recent_updates(int $limit = 6): array
{
    $statement = db()->prepare(
        'SELECT u.*, p.title, p.code
         FROM project_updates u
         INNER JOIN projects p ON p.id = u.project_id
         ORDER BY u.created_at DESC, u.id DESC
         LIMIT ' . (int) $limit
    );
    $statement->execute();
    return $statement->fetchAll();
}

function project_gallery(int $id): array
{
    $rows = fetch_images($id);
    if ($rows) {
        return $rows;
    }
    return [
        ['file_path' => 'assets/img/cover.php?p=' . $id . '&v=1', 'caption' => 'مشهد توضيحي لنطاق المشروع', 'is_cover' => 1],
        ['file_path' => 'assets/img/cover.php?p=' . $id . '&v=2', 'caption' => 'مخطط بصري للموقع', 'is_cover' => 0],
        ['file_path' => 'assets/img/cover.php?p=' . $id . '&v=3', 'caption' => 'مؤشر التقدم البصري', 'is_cover' => 0],
    ];
}

function cover_url(array $project): string
{
    if (!empty($project['cover'])) {
        return root_url($project['cover']);
    }
    return root_url('assets/img/cover.php?p=' . (int) $project['id'] . '&v=1');
}

function related_projects(array $project, int $limit = 3): array
{
    $pick = function (array $rows) use ($project, $limit): array {
        $out = [];
        foreach ($rows as $row) {
            if ((int) $row['id'] === (int) $project['id']) {
                continue;
            }
            $out[] = $row;
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    };
    $sameDepartment = $pick(fetch_projects(['department' => (int) $project['department_id']]));
    if ($sameDepartment) {
        return $sameDepartment;
    }
    return $pick(fetch_projects(['agency' => (int) $project['agency_id']]));
}

function year_counts(): array
{
    $rows = db()->query('SELECT announced_year AS y, COUNT(*) AS c FROM projects WHERE announced_year IN (2024, 2025, 2026) GROUP BY announced_year')->fetchAll();
    $counts = [2024 => 0, 2025 => 0, 2026 => 0];
    foreach ($rows as $row) {
        $counts[(int) $row['y']] = (int) $row['c'];
    }
    return $counts;
}

function alert_projects(array $projects): array
{
    $list = [];
    foreach ($projects as $project) {
        if (empty($project['alerts'])) {
            continue;
        }
        $project['level'] = alert_level($project['alerts']);
        $project['overdue_days'] = days_overdue($project);
        $list[] = $project;
    }
    usort($list, function ($a, $b) {
        if ($a['level'] !== $b['level']) {
            return $a['level'] === 'high' ? -1 : 1;
        }
        return ($b['overdue_days'] ?? -1) <=> ($a['overdue_days'] ?? -1);
    });
    return $list;
}

function alert_count(): int
{
    static $count = null;
    if ($count === null) {
        $count = count(alert_projects(fetch_projects()));
    }
    return $count;
}

function map_payload(array $projects): array
{
    $payload = [];
    foreach ($projects as $project) {
        $payload[] = [
            'id' => (int) $project['id'],
            'title' => $project['title'],
            'lat' => (float) $project['latitude'],
            'lng' => (float) $project['longitude'],
            'status' => $project['status'],
            'statusLabel' => status_label($project['status']),
            'progress' => (int) $project['progress'],
            'agency' => $project['agency_name'],
            'location' => $project['location_name'],
            'expected' => ar_date($project['expected_date']),
            'url' => root_url('project.php?id=' . (int) $project['id']),
            'alert' => !empty($project['alerts']),
        ];
    }
    return $payload;
}

function count_label(int $n): string
{
    if ($n === 0) {
        return 'لا مشاريع';
    }
    if ($n === 1) {
        return 'مشروع واحد';
    }
    if ($n === 2) {
        return 'مشروعان';
    }
    if ($n >= 3 && $n <= 10) {
        return $n . ' مشاريع';
    }
    return $n . ' مشروعًا';
}

function ensure_upload_dir(): string
{
    $dir = APP_ROOT . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function delete_upload(string $path): void
{
    if (strpos($path, 'uploads/') !== 0) {
        return;
    }
    $full = APP_ROOT . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($full)) {
        unlink($full);
    }
}

function store_uploaded_images(int $projectId, array $files): array
{
    $errors = [];
    if (empty($files['name']) || !is_array($files['name'])) {
        return $errors;
    }
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $dir = ensure_upload_dir();
    $saved = 0;
    $existing = count(fetch_images($projectId));
    foreach ($files['name'] as $i => $original) {
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'تعذر رفع إحدى الصور.';
            continue;
        }
        if (($files['size'][$i] ?? 0) > 5 * 1024 * 1024) {
            $errors[] = 'حجم الصورة يجب ألا يتجاوز 5 ميغابايت.';
            continue;
        }
        $ext = strtolower(pathinfo((string) $original, PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            $errors[] = 'الصيغ المقبولة: JPG و PNG و WEBP و GIF.';
            continue;
        }
        $mime = $finfo->file($files['tmp_name'][$i]);
        if ($mime !== $allowed[$ext]) {
            $errors[] = 'ملف الصورة غير صالح.';
            continue;
        }
        if ($existing + $saved >= 6) {
            $errors[] = 'الحد الأقصى ست صور لكل مشروع.';
            break;
        }
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($files['tmp_name'][$i], $dir . DIRECTORY_SEPARATOR . $name)) {
            $errors[] = 'تعذر حفظ الصورة على الخادم.';
            continue;
        }
        db()->prepare('INSERT INTO project_images (project_id, file_path, caption, is_cover) VALUES (?, ?, ?, 0)')
            ->execute([$projectId, 'uploads/' . $name, '']);
        $saved++;
    }
    return $errors;
}

function set_cover(int $projectId, int $imageId): void
{
    db()->prepare('UPDATE project_images SET is_cover = 0 WHERE project_id = ?')->execute([$projectId]);
    db()->prepare('UPDATE project_images SET is_cover = 1 WHERE project_id = ? AND id = ?')->execute([$projectId, $imageId]);
}

function ensure_cover(int $projectId): void
{
    $statement = db()->prepare('SELECT id FROM project_images WHERE project_id = ? ORDER BY is_cover DESC, id ASC LIMIT 1');
    $statement->execute([$projectId]);
    $id = $statement->fetchColumn();
    if ($id) {
        set_cover($projectId, (int) $id);
    }
}

function delete_project_image(int $projectId, int $imageId): void
{
    $statement = db()->prepare('SELECT file_path FROM project_images WHERE id = ? AND project_id = ?');
    $statement->execute([$imageId, $projectId]);
    $path = $statement->fetchColumn();
    if (!$path) {
        return;
    }
    delete_upload((string) $path);
    db()->prepare('DELETE FROM project_images WHERE id = ? AND project_id = ?')->execute([$imageId, $projectId]);
}

function delete_project(int $id): void
{
    $statement = db()->prepare('SELECT file_path FROM project_images WHERE project_id = ?');
    $statement->execute([$id]);
    foreach ($statement->fetchAll() as $image) {
        delete_upload($image['file_path']);
    }
    db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
}

function valid_date(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

require_once __DIR__ . '/layout.php';
