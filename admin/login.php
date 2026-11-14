<?php
define('APP', true);
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['login_fails'] ?? 0) >= 8) {
        $error = 'أُوقف الدخول بعد محاولات متعددة. أغلق الصفحة وافتحها من جديد.';
    } elseif (!csrf_ok()) {
        $error = 'انتهت صلاحية النموذج. أعد المحاولة.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $statement->execute([$username]);
        $user = $statement->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'name' => $user['full_name'],
                'username' => $user['username'],
            ];
            unset($_SESSION['login_fails']);
            header('Location: index.php');
            exit;
        }
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}

render_document_start('دخول الموظفين', false, false);
render_gov_chrome('login');
?>
<main class="login-screen" id="content">
  <section class="login-card">
    <img class="brand-logo" src="<?= e(root_url('assets/img/logo.png')) ?>" alt="متابعة المشاريع الحكومية">
    <p class="kicker">لوحة التحكم</p>
    <h1>تسجيل الدخول</h1>
    <p>لوحة إضافة المشاريع وتحديث نسب الإنجاز والصور والتنبيهات.</p>
    <?php if ($error): ?><p class="flash flash-bad"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <label class="field"><span>اسم المستخدم</span><input name="username" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>"></label>
      <label class="field"><span>كلمة المرور</span><input name="password" type="password" autocomplete="current-password" required></label>
      <button class="btn btn-primary" type="submit">دخول</button>
    </form>
    <p class="muted">للتجربة المحلية: admin / Admin@2026</p>
    <p><a href="<?= e(root_url('index.php')) ?>">العودة إلى المنصة</a></p>
  </section>
</main>
<script src="<?= e(root_url('assets/js/app.js')) ?>"></script>
</body></html>
