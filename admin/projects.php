<?php
define('APP', true);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash('انتهت صلاحية النموذج.', 'bad');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $project = $id ? fetch_project($id) : null;
        if (!$project) {
            flash('المشروع غير موجود.', 'bad');
        } else {
            delete_project($id);
            flash('حُذف المشروع ' . $project['code'] . '.');
        }
    }
    header('Location: projects.php');
    exit;
}

$filters = filters_from_request();
$projects = fetch_projects($filters);
$agencies = fetch_agencies();

render_admin_header('المشاريع');
?>
<div class="panel-head">
  <p><?= e(count_label(count($projects))) ?></p>
  <a class="btn btn-primary" href="project-form.php">إضافة مشروع</a>
</div>
<form class="filters" method="get">
  <label class="field"><span>بحث</span><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"></label>
  <label class="field"><span>السنة</span>
    <select name="year">
      <option value="">الكل</option>
      <?php foreach ([2024, 2025, 2026] as $year): ?>
        <option value="<?= $year ?>"<?= (int) ($filters['year'] ?? 0) === $year ? ' selected' : '' ?>><?= $year ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="field"><span>الوكالة</span>
    <select name="agency">
      <option value="">الكل</option>
      <?php foreach ($agencies as $agency): ?>
        <option value="<?= (int) $agency['id'] ?>"<?= (int) ($filters['agency'] ?? 0) === (int) $agency['id'] ? ' selected' : '' ?>><?= e($agency['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="field"><span>الحالة</span>
    <select name="status">
      <option value="">الكل</option>
      <?php foreach (statuses() as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= ($filters['status'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn btn-primary" type="submit">تصفية</button>
</form>
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr><th>الرمز</th><th>المشروع</th><th>الوكالة</th><th>السنة</th><th>الإنجاز</th><th>الحالة</th><th>الموعد</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($projects as $project): ?>
        <tr>
          <td><?= e($project['code']) ?></td>
          <td><a href="project-form.php?id=<?= (int) $project['id'] ?>"><?= e($project['title']) ?></a><small><?= e($project['location_name']) ?></small></td>
          <td><?= e($project['agency_name']) ?></td>
          <td><?= (int) $project['announced_year'] ?></td>
          <td><?= (int) $project['progress'] ?>٪</td>
          <td><?= status_pill($project['status']) ?></td>
          <td><?= e(ar_date($project['expected_date'])) ?></td>
          <td class="row-actions">
            <a href="../project.php?id=<?= (int) $project['id'] ?>">عرض</a>
            <a href="project-form.php?id=<?= (int) $project['id'] ?>">تعديل</a>
            <form method="post" data-confirm="حذف هذا المشروع نهائيًا؟">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
              <button class="linkish" type="submit">حذف</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$projects): ?>
        <tr><td colspan="8">لا توجد مشاريع مطابقة.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php render_admin_footer(); ?>
