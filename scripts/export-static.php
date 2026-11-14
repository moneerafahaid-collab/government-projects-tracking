<?php
$root = dirname(__DIR__);
putenv('USE_SQLITE=1');
putenv('SQLITE_PATH=' . $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'demo.sqlite');
$_SERVER['HTTPS'] = 'off';

$docs = $root . DIRECTORY_SEPARATOR . 'docs';
if (is_dir($docs)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docs, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
}
mkdir($docs, 0775, true);

function capture_page(string $root, string $script, array $query = []): string
{
    $php = 'C:\\xampp\\php\\php.exe';
    $code = 'putenv("USE_SQLITE=1"); putenv("SQLITE_PATH=" . ' . var_export($root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'demo.sqlite', true) . ');'
        . 'parse_str(' . var_export(http_build_query($query), true) . ', $_GET);'
        . '$_SERVER["REQUEST_METHOD"]="GET";'
        . '$_SERVER["SCRIPT_NAME"]="/' . basename($script) . '";'
        . '$_SERVER["PHP_SELF"]=$_SERVER["SCRIPT_NAME"];'
        . 'chdir(' . var_export($root, true) . ');'
        . 'include ' . var_export($root . DIRECTORY_SEPARATOR . $script, true) . ';';
    $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open([$php, '-d', 'display_errors=0', '-r', $code], $spec, $pipes, $root);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($proc);
    if ($status !== 0) {
        fwrite(STDERR, $err . "\n");
        throw new RuntimeException('failed ' . $script);
    }
    return $out;
}

function rewrite_static(string $html): string
{
    $html = preg_replace('#project\.php\?id=(\d+)#', 'project-$1.html', $html);
    $html = preg_replace('#index\.php\?year=(2024|2025|2026)[^"\']*#', 'year-$1.html', $html);
    $html = str_replace(['alerts.php', 'structure.php'], ['alerts.html', 'structure.html'], $html);
    $html = preg_replace('#href="index\.php[^"]*"#', 'href="index.html"', $html);
    $html = preg_replace('#action="index\.php[^"]*"#', 'action="index.html"', $html);
    $html = str_replace('admin/login.php', 'login.html', $html);
    $html = str_replace('install.php', 'index.html', $html);
    return $html;
}

function save_page(string $docs, string $name, string $html): void
{
    file_put_contents($docs . DIRECTORY_SEPARATOR . $name, rewrite_static($html));
}

save_page($docs, 'index.html', capture_page($root, 'index.php'));
foreach ([2024, 2025, 2026] as $year) {
    save_page($docs, 'year-' . $year . '.html', capture_page($root, 'index.php', ['year' => $year]));
}
save_page($docs, 'alerts.html', capture_page($root, 'alerts.php'));
save_page($docs, 'structure.html', capture_page($root, 'structure.php'));
for ($id = 1; $id <= 26; $id++) {
    save_page($docs, 'project-' . $id . '.html', capture_page($root, 'project.php', ['id' => $id]));
}

$login = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>لوحة التحكم</title><link rel="stylesheet" href="assets/css/style.css"><link rel="icon" href="assets/img/logo-mark.png" type="image/png"></head><body class="is-public"><main class="login-screen"><section class="login-card"><img class="brand-logo" src="assets/img/logo.png" alt="متابعة المشاريع الحكومية"><h1>لوحة التحكم محلية</h1><p>هذه المعاينة على GitHub تعرض الواجهة العامة. إضافة المشاريع وتعديلها تعمل عند تشغيل النظام محليًا بـ PHP وMySQL.</p><p><a class="btn btn-primary" href="index.html">العودة إلى المنصة</a></p></section></main></body></html>';
file_put_contents($docs . DIRECTORY_SEPARATOR . 'login.html', $login);
file_put_contents($docs . DIRECTORY_SEPARATOR . '.nojekyll', '');

$copy = [
    'assets/css',
    'assets/js',
    'assets/img',
    'assets/vendor/leaflet',
];
foreach ($copy as $dir) {
    $from = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    $to = $docs . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $rel = substr($file->getPathname(), strlen($from));
        $dest = $to . $rel;
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        copy($file->getPathname(), $dest);
    }
}

echo "exported to docs\n";
