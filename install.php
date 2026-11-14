<?php
$configFile = __DIR__ . '/config/config.php';
$existing = is_file($configFile) ? require $configFile : ['host' => '127.0.0.1', 'name' => 'amanah_hail', 'user' => 'root', 'pass' => ''];
$error = '';
$done = false;

function hail_sql(mysqli $db, string $file): ?string
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        return 'تعذر قراءة ملف القاعدة.';
    }
    if (!$db->multi_query($sql)) {
        return $db->error;
    }
    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());
    return $db->errno ? $db->error : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? '127.0.0.1');
    $name = trim($_POST['name'] ?? 'amanah_hail');
    $user = trim($_POST['user'] ?? 'root');
    $pass = (string) ($_POST['pass'] ?? '');
    $reset = !empty($_POST['reset']);
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        $error = 'اسم القاعدة يقبل حروفًا إنجليزية وأرقامًا وشرطة سفلية فقط.';
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $db = @new mysqli($host, $user, $pass);
        if ($db->connect_errno) {
            $error = $db->connect_errno === 2002
                ? 'تعذر الاتصال. شغّل MySQL من لوحة XAMPP ثم أعد المحاولة.'
                : ('فشل الاتصال: ' . $db->connect_error);
        } else {
            $db->set_charset('utf8mb4');
            $safeName = str_replace('`', '', $name);
            $exists = $db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $db->real_escape_string($safeName) . "'");
            $hasDb = $exists && $exists->num_rows > 0;
            $hasProjects = false;
            if ($hasDb) {
                $db->select_db($safeName);
                $check = $db->query("SHOW TABLES LIKE 'projects'");
                $hasProjects = $check && $check->num_rows > 0;
            }
            if ($hasProjects && !$reset) {
                $error = 'القاعدة موجودة وفيها مشاريع. فعّل خيار إعادة البناء إذا أردت استبدال البيانات التجريبية.';
            } else {
                if ($hasDb && $reset) {
                    $db->query('DROP DATABASE `' . $safeName . '`');
                }
                $written = file_put_contents($configFile, "<?php\nreturn " . var_export([
                    'host' => $host,
                    'name' => $safeName,
                    'user' => $user,
                    'pass' => $pass,
                ], true) . ";\n");
                if ($written === false) {
                    $error = 'تعذر حفظ ملف الإعداد. تأكد أن مجلد config قابل للكتابة.';
                } else {
                    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
                    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS amanah_hail/i', 'CREATE DATABASE IF NOT EXISTS `' . $safeName . '`', $schema);
                    $schema = preg_replace('/USE amanah_hail;/i', 'USE `' . $safeName . '`;', $schema);
                    $temp = tempnam(sys_get_temp_dir(), 'hail');
                    file_put_contents($temp, $schema);
                    $sqlError = hail_sql($db, $temp);
                    unlink($temp);
                    if ($sqlError) {
                        $error = 'تعذر بناء الجداول: ' . $sqlError;
                    } else {
                        $db->select_db($safeName);
                        $hash = password_hash('Admin@2026', PASSWORD_DEFAULT);
                        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
                        $username = 'admin';
                        $stmt->bind_param('ss', $hash, $username);
                        $stmt->execute();
                        $done = true;
                    }
                }
            }
            $db->close();
        }
    }
    $existing = ['host' => $host ?? $existing['host'], 'name' => $name ?? $existing['name'], 'user' => $user ?? $existing['user'], 'pass' => $pass ?? ''];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تثبيت النظام — متابعة مشاريع أمانة حائل</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="assets/img/logo-mark.png" type="image/png">
</head>
<body class="is-public">
  <main class="container install-card">
    <img class="brand-logo" src="assets/img/logo.png" alt="متابعة المشاريع الحكومية">
    <p class="kicker">تثبيت النظام</p>
    <h1>تثبيت نظام المتابعة</h1>
    <p>ينشئ قاعدة MySQL وجداول الوكالات والإدارات والمشاريع، ويملأ أعوام 2024 و2025 و2026 ببيانات تجريبية. شغّل MySQL من XAMPP قبل المتابعة.</p>
    <?php if ($done): ?>
      <p class="flash flash-ok">تم بناء القاعدة. الدخول إلى لوحة التحكم: admin / Admin@2026</p>
      <p><a class="btn btn-primary" href="index.php">فتح المنصة</a> <a class="btn btn-ghost" href="admin/login.php">لوحة التحكم</a></p>
    <?php else: ?>
      <?php if ($error): ?><p class="flash flash-bad"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      <form method="post" class="stack-form">
        <label class="field"><span>المضيف</span><input name="host" value="<?= htmlspecialchars($existing['host'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>" required></label>
        <label class="field"><span>اسم القاعدة</span><input name="name" value="<?= htmlspecialchars($existing['name'] ?? 'amanah_hail', ENT_QUOTES, 'UTF-8') ?>" required></label>
        <label class="field"><span>المستخدم</span><input name="user" value="<?= htmlspecialchars($existing['user'] ?? 'root', ENT_QUOTES, 'UTF-8') ?>" required></label>
        <label class="field"><span>كلمة مرور القاعدة</span><input name="pass" type="password" value="<?= htmlspecialchars($existing['pass'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="off"></label>
        <label class="check"><input type="checkbox" name="reset" value="1"> إعادة البناء وحذف البيانات الحالية إن وُجدت</label>
        <button class="btn btn-primary" type="submit">إنشاء القاعدة والبيانات</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
