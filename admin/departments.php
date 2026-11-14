<?php
define('APP', true);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash('انتهت صلاحية النموذج.', 'bad');
        header('Location: departments.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add-agency') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#0f3d34');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#0f3d34';
        }
        if (mb_strlen($name) < 3 || mb_strlen($description) < 3) {
            flash('اسم الوكالة ووصفها مطلوبان.', 'bad');
        } else {
            $slug = 'agency-' . bin2hex(random_bytes(3));
            db()->prepare('INSERT INTO agencies (name, slug, description, color, icon) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $slug, $description, $color, 'mark']);
            flash('أُضيفت الوكالة.');
        }
    } elseif ($action === 'add-department') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $agencyId = (int) ($_POST['agency_id'] ?? 0);
        $agency = db()->prepare('SELECT id FROM agencies WHERE id = ?');
        $agency->execute([$agencyId]);
        if (!$agency->fetch() || mb_strlen($name) < 3) {
            flash('اختر وكالة واكتب اسم الإدارة.', 'bad');
        } else {
            db()->prepare('INSERT INTO departments (agency_id, name, description) VALUES (?, ?, ?)')
                ->execute([$agencyId, $name, $description !== '' ? $description : 'إدارة مضافة من لوحة التحكم.']);
            flash('أُضيفت الإدارة.');
        }
    } elseif ($action === 'delete-department') {
        $deptId = (int) ($_POST['id'] ?? 0);
        $count = db()->prepare('SELECT COUNT(*) FROM projects WHERE department_id = ?');
        $count->execute([$deptId]);
        if ((int) $count->fetchColumn() > 0) {
            flash('لا يمكن حذف إدارة مرتبطة بمشاريع.', 'bad');
        } else {
            db()->prepare('DELETE FROM departments WHERE id = ?')->execute([$deptId]);
            flash('حُذفت الإدارة.');
        }
    } elseif ($action === 'delete-agency') {
        $agencyId = (int) ($_POST['id'] ?? 0);
        $count = db()->prepare('SELECT COUNT(*) FROM departments WHERE agency_id = ?');
        $count->execute([$agencyId]);
        if ((int) $count->fetchColumn() > 0) {
            flash('انقل أو احذف إدارات الوكالة قبل حذفها.', 'bad');
        } else {
            db()->prepare('DELETE FROM agencies WHERE id = ?')->execute([$agencyId]);
            flash('حُذفت الوكالة.');
        }
    }
    header('Location: departments.php');
    exit;
}

$agencies = fetch_agencies();
$departments = fetch_departments();
$byAgency = [];
foreach ($departments as $department) {
    $byAgency[$department['agency_id']][] = $department;
}

render_admin_header('الإدارات');
?>
<div class="admin-grid">
  <section class="panel">
    <h2>الهيكل الحالي</h2>
    <?php foreach ($agencies as $agency): ?>
      <article class="dept-admin" style="--agency: <?= e($agency['color']) ?>">
        <header>
          <h3><?= e($agency['name']) ?></h3>
          <form method="post" data-confirm="حذف هذه الوكالة؟">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete-agency">
            <input type="hidden" name="id" value="<?= (int) $agency['id'] ?>">
            <button class="linkish" type="submit">حذف الوكالة</button>
          </form>
        </header>
        <ul>
          <?php foreach ($byAgency[$agency['id']] ?? [] as $department): ?>
            <li>
              <span><?= e($department['name']) ?> <small><?= (int) $department['project_count'] ?></small></span>
              <form method="post" data-confirm="حذف هذه الإدارة؟">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete-department">
                <input type="hidden" name="id" value="<?= (int) $department['id'] ?>">
                <button class="linkish" type="submit">حذف</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </article>
    <?php endforeach; ?>
  </section>
  <section>
    <form method="post" class="panel stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add-department">
      <h2>إضافة إدارة</h2>
      <label class="field"><span>الوكالة</span>
        <select name="agency_id">
          <?php foreach ($agencies as $agency): ?>
            <option value="<?= (int) $agency['id'] ?>"><?= e($agency['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>اسم الإدارة</span><input name="name" required></label>
      <label class="field"><span>وصف مختصر</span><input name="description"></label>
      <button class="btn btn-primary" type="submit">حفظ الإدارة</button>
    </form>
    <form method="post" class="panel stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add-agency">
      <h2>إضافة وكالة</h2>
      <label class="field"><span>الاسم</span><input name="name" required></label>
      <label class="field"><span>الوصف</span><input name="description" required></label>
      <label class="field"><span>اللون</span><input name="color" type="color" value="#0f3d34"></label>
      <button class="btn btn-ghost" type="submit">حفظ الوكالة</button>
    </form>
  </section>
</div>
<?php render_admin_footer(); ?>
