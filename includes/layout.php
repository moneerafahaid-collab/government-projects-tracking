<?php

function render_document_start(string $title, bool $withMap = false, bool $admin = false): void
{
    $full = $title . ' — متابعة مشاريع أمانة حائل';
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="description" content="نظام متابعة مشاريع وكالة التحول الرقمي ووكالة الاستراتيجية ووكالة التميز المؤسسي في أمانة منطقة حائل.">';
    echo '<title>' . e($full) . '</title>';
    echo '<link rel="icon" href="' . e(root_url('assets/img/logo-mark.png')) . '" type="image/png">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="' . e(root_url('assets/css/style.css')) . '">';
    if ($admin) {
        echo '<link rel="stylesheet" href="' . e(root_url('assets/css/admin.css')) . '">';
    }
    if ($withMap) {
        echo '<link rel="stylesheet" href="' . e(root_url('assets/vendor/leaflet/leaflet.css')) . '">';
    }
    echo '</head><body class="' . ($admin ? 'is-admin' : 'is-public') . '">';
}

function render_flash(): void
{
    $item = flash();
    if (!$item) {
        return;
    }
    $type = ($item['type'] ?? '') === 'bad' ? 'bad' : 'ok';
    echo '<div class="container"><p class="flash flash-' . e($type) . '" role="status">' . e($item['message']) . '</p></div>';
}

function render_gov_chrome(string $active = 'home'): void
{
    $count = alert_count();
    if ($active === 'project' || $active === 'map') {
        $active = 'home';
    }
    echo '<a class="skip" href="#content">تجاوز إلى المحتوى</a>';
    echo '<header class="app-header">';
    echo '<a class="brand" href="' . e(root_url('index.php')) . '" aria-label="متابعة المشاريع الحكومية"><img src="' . e(root_url('assets/img/logo.png')) . '" alt="متابعة المشاريع الحكومية"></a>';
    echo '<button type="button" class="mobile-menu-btn" id="mobile-menu-toggle" aria-controls="main-nav" aria-expanded="false" aria-label="القائمة">☰</button>';
    echo '<nav class="main-nav" id="main-nav" aria-label="التنقل الرئيسي">';
    $links = [
        'home' => [root_url('index.php'), 'المشاريع'],
        'map' => [root_url('index.php#map'), 'الخريطة'],
        'structure' => [root_url('structure.php'), 'الوكالات'],
        'alerts' => [root_url('alerts.php'), 'التنبيهات'],
    ];
    foreach ($links as $key => $link) {
        $class = $active === $key ? ' class="is-active"' : '';
        echo '<a' . $class . ' href="' . e($link[0]) . '">' . e($link[1]);
        if ($key === 'alerts' && $count > 0) {
            echo ' <em class="badge">' . (int) $count . '</em>';
        }
        echo '</a>';
    }
    echo '</nav>';
    echo '<a class="header-login" href="' . e(root_url('admin/login.php')) . '">دخول</a>';
    echo '</header>';
}

function render_public_header(string $title, string $active = 'home', bool $withMap = false): void
{
    $GLOBALS['hail_map'] = $withMap;
    render_document_start($title, $withMap, false);
    render_gov_chrome($active);
    $heads = [
        'home' => ['تابع تقدّم المشاريع على الخريطة', 'سجل موحّد لنسب الإنجاز والحالة والمواعيد عبر الوكالات الثلاث.'],
        'structure' => ['الوكالات والإدارات', 'الهيكل الذي تُدار من خلاله المشاريع.'],
        'alerts' => ['تنبيهات المتابعة', 'المشاريع التي تحتاج قرارًا أو تجاوزت موعدها.'],
        'project' => [$title, 'نسبة الإنجاز والحالة والموعد المتوقع والموقع.'],
    ];
    $head = $heads[$active] ?? [$title, 'متابعة مشاريع الوكالات.'];
    if ($active !== 'home') {
        echo '<section class="page-intro-bar"><div class="container"><h1>' . e($head[0]) . '</h1><p>' . e($head[1]) . '</p></div></section>';
    }
    echo '<main id="content">';
    render_flash();
}

function render_public_footer(): void
{
    $withMap = !empty($GLOBALS['hail_map']);
    echo '</main><footer class="app-footer"><div class="container footer-row">';
    echo '<p>متابعة المشاريع · التحول الرقمي · الاستراتيجية · التميز المؤسسي</p>';
    echo '<p>السجل يشمل المشاريع المعلنة في 2024 و2025 و2026</p>';
    echo '</div></footer>';
    if ($withMap) {
        echo '<script src="' . e(root_url('assets/vendor/leaflet/leaflet.js')) . '"></script>';
    }
    echo '<script src="' . e(root_url('assets/js/app.js')) . '"></script></body></html>';
}

function render_map_data(array $projects): void
{
    $json = json_encode(map_payload($projects), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo '<script>window.PROJECTS = ' . $json . ';</script>';
}

function render_admin_header(string $title, bool $withMap = false): void
{
    require_admin();
    $GLOBALS['hail_map'] = $withMap;
    $user = current_user();
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    render_document_start($title, $withMap, true);
    echo '<div class="shell"><aside class="side"><a class="side-brand" href="index.php"><img src="' . e(root_url('assets/img/logo.png')) . '" alt="متابعة المشاريع الحكومية"><small>لوحة التحكم</small></a><nav>';
    $items = [
        'index.php' => 'نظرة عامة',
        'projects.php' => 'المشاريع',
        'project-form.php' => 'إضافة مشروع',
        'departments.php' => 'الإدارات',
    ];
    foreach ($items as $file => $label) {
        $class = $current === $file ? ' class="is-current"' : '';
        echo '<a' . $class . ' href="' . e($file) . '">' . e($label) . '</a>';
    }
    echo '</nav><div class="side-foot"><a href="' . e(root_url('index.php')) . '">عرض المنصة</a><a href="logout.php">خروج</a></div></aside>';
    echo '<div class="admin-main"><header class="admin-top"><div><p class="kicker">لوحة التحكم</p><h1>' . e($title) . '</h1></div><p class="who">' . e($user['name'] ?? '') . '</p></header>';
    render_flash();
}

function render_admin_footer(): void
{
    $withMap = !empty($GLOBALS['hail_map']);
    echo '</div></div>';
    if ($withMap) {
        echo '<script src="' . e(root_url('assets/vendor/leaflet/leaflet.js')) . '"></script>';
    }
    echo '<script src="' . e(root_url('assets/js/admin.js')) . '"></script></body></html>';
}

function progress_bar(int $percent): string
{
    $percent = max(0, min(100, $percent));
    return '<div class="bar" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percent . '"><span style="width:' . $percent . '%"></span></div>';
}

function status_pill(string $status): string
{
    return '<span class="status-pill status-' . e($status) . '">' . e(status_label($status)) . '</span>';
}
