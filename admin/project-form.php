<?php
define('APP', true);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$current = $id ? fetch_project($id) : null;
if ($id && !$current) {
    flash('المشروع غير موجود.', 'bad');
    header('Location: projects.php');
    exit;
}

$departments = fetch_departments();
$groups = [];
foreach ($departments as $department) {
    $groups[$department['agency_name']][] = $department;
}

$blank = [
    'title' => '', 'department_id' => $departments[0]['id'] ?? '', 'summary' => '', 'description' => '',
    'status' => 'in_progress', 'progress' => 0, 'announced_year' => 2026,
    'start_date' => date('Y-m-d'), 'expected_date' => date('Y-m-d', strtotime('+6 months')),
    'location_name' => 'حائل', 'latitude' => '27.521900', 'longitude' => '41.690200',
    'budget' => '', 'manager_name' => '', 'priority' => 'medium', 'follow_note' => '', 'update_note' => '',
];
$form = $current ? array_merge($blank, $current) : $blank;
$form['budget'] = $form['budget'] === null ? '' : $form['budget'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($blank as $key => $value) {
        $form[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : $form[$key];
    }
    if (!csrf_ok()) {
        $errors[] = 'انتهت صلاحية النموذج. أعد الإرسال.';
    }
    if (mb_strlen($form['title']) < 4) {
        $errors[] = 'اكتب عنوانًا واضحًا للمشروع.';
    }
    if (mb_strlen($form['summary']) < 10) {
        $errors[] = 'الملخص قصير. اكتب جملة أو جملتين.';
    }
    if (mb_strlen($form['description']) < 20) {
        $errors[] = 'أضف وصفًا أوضح للمشروع.';
    }
    $deptOk = false;
    foreach ($departments as $department) {
        if ((int) $department['id'] === (int) $form['department_id']) {
            $deptOk = true;
        }
    }
    if (!$deptOk) {
        $errors[] = 'اختر إدارة تابعة لإحدى الوكالات.';
    }
    if (!isset(statuses()[$form['status']])) {
        $errors[] = 'الحالة غير معروفة.';
    }
    if (!isset(priorities()[$form['priority']])) {
        $errors[] = 'الأولوية غير معروفة.';
    }
    $year = (int) $form['announced_year'];
    if (!in_array($year, [2024, 2025, 2026], true)) {
        $errors[] = 'سنة الإعلان يجب أن تكون 2024 أو 2025 أو 2026.';
    }
    $progress = max(0, min(100, (int) $form['progress']));
    if ($form['status'] === 'completed') {
        $progress = 100;
    }
    $form['progress'] = $progress;
    if (!valid_date($form['start_date']) || !valid_date($form['expected_date'])) {
        $errors[] = 'تحقق من تاريخ البداية والموعد المتوقع.';
    } elseif ($form['start_date'] > $form['expected_date']) {
        $errors[] = 'تاريخ البداية يجب أن يسبق الموعد المتوقع.';
    }
    if ($form['location_name'] === '' || $form['manager_name'] === '') {
        $errors[] = 'الموقع ومدير المشروع حقلان مطلوبان.';
    }
    if (!is_numeric($form['latitude']) || !is_numeric($form['longitude']) || $form['latitude'] < -90 || $form['latitude'] > 90 || $form['longitude'] < -180 || $form['longitude'] > 180) {
        $errors[] = 'الإحداثيات غير صالحة. حدّد النقطة على الخريطة.';
    }
    $budget = str_replace([',', ' '], '', $form['budget']);
    $budgetValue = $budget === '' ? null : (float) $budget;
    if ($budget !== '' && !is_numeric($budget)) {
        $errors[] = 'الميزانية يجب أن تكون رقمًا.';
    }

    if (!$errors) {
        $params = [
            (int) $form['department_id'], $form['title'], $form['summary'], $form['description'], $form['status'],
            $progress, $year, $form['start_date'], $form['expected_date'], $form['location_name'],
            $form['latitude'], $form['longitude'], $budgetValue, $form['manager_name'], $form['priority'],
            $form['follow_note'] !== '' ? $form['follow_note'] : null,
        ];
        if ($current) {
            $params[] = (int) $current['id'];
            db()->prepare('UPDATE projects SET department_id=?, title=?, summary=?, description=?, status=?, progress=?, announced_year=?, start_date=?, expected_date=?, location_name=?, latitude=?, longitude=?, budget=?, manager_name=?, priority=?, follow_note=? WHERE id=?')
                ->execute($params);
            $savedId = (int) $current['id'];
        } else {
            db()->prepare('INSERT INTO projects (department_id, code, title, summary, description, status, progress, announced_year, start_date, expected_date, location_name, latitude, longitude, budget, manager_name, priority, follow_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute(array_merge([(int) $form['department_id'], 'TMP-' . bin2hex(random_bytes(4))], array_slice($params, 1)));
            $savedId = (int) db()->lastInsertId();
            db()->prepare('UPDATE projects SET code = ? WHERE id = ?')->execute([sprintf('HL-%d-%03d', $year, $savedId), $savedId]);
        }

        $note = trim($form['update_note']);
        $oldProgress = $current ? (int) $current['progress'] : null;
        if ($note === '' && $current && $oldProgress !== $progress) {
            $note = 'تم تعديل نسبة الإنجاز من ' . $oldProgress . '٪ إلى ' . $progress . '٪.';
        }
        if ($note === '' && !$current) {
            $note = 'أُضيف المشروع إلى سجل المتابعة.';
        }
        if ($note !== '') {
            db()->prepare('INSERT INTO project_updates (project_id, progress, note, created_at) VALUES (?, ?, ?, NOW())')
                ->execute([$savedId, $progress, mb_substr($note, 0, 400)]);
        }

        foreach ((array) ($_POST['delete_images'] ?? []) as $imageId) {
            delete_project_image($savedId, (int) $imageId);
        }
        $imageErrors = store_uploaded_images($savedId, $_FILES['images'] ?? []);
        $coverId = (int) ($_POST['cover_image'] ?? 0);
        if ($coverId) {
            set_cover($savedId, $coverId);
        }
        $coverCheck = db()->prepare('SELECT COUNT(*) FROM project_images WHERE project_id = ? AND is_cover = 1');
        $coverCheck->execute([$savedId]);
        if (!(int) $coverCheck->fetchColumn()) {
            ensure_cover($savedId);
        }

        $message = $current ? 'تم تحديث المشروع.' : 'تمت إضافة المشروع.';
        if ($imageErrors) {
            $message .= ' ' . implode(' ', $imageErrors);
        }
        flash($message, $imageErrors ? 'bad' : 'ok');
        header('Location: project-form.php?id=' . $savedId);
        exit;
    }
}

$images = $current ? fetch_images((int) $current['id']) : [];
render_admin_header($current ? 'تعديل مشروع' : 'إضافة مشروع', true);
?>
<?php if ($errors): ?>
  <div class="errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="project-form">
  <?= csrf_field() ?>
  <section class="form-section">
    <h2>التعريف</h2>
    <div class="form-grid">
      <label class="field span-2"><span>عنوان المشروع</span><input name="title" required value="<?= e($form['title']) ?>"></label>
      <label class="field"><span>الإدارة</span>
        <select name="department_id" required>
          <?php foreach ($groups as $agencyName => $items): ?>
            <optgroup label="<?= e($agencyName) ?>">
              <?php foreach ($items as $department): ?>
                <option value="<?= (int) $department['id'] ?>"<?= (int) $form['department_id'] === (int) $department['id'] ? ' selected' : '' ?>><?= e($department['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>مدير المشروع</span><input name="manager_name" required value="<?= e($form['manager_name']) ?>"></label>
      <label class="field span-2"><span>الملخص</span><textarea name="summary" rows="2" required><?= e($form['summary']) ?></textarea></label>
      <label class="field span-2"><span>الوصف</span><textarea name="description" rows="5" required><?= e($form['description']) ?></textarea></label>
    </div>
  </section>
  <section class="form-section">
    <h2>المتابعة</h2>
    <div class="form-grid">
      <label class="field"><span>الحالة</span>
        <select name="status">
          <?php foreach (statuses() as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $form['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>الأولوية</span>
        <select name="priority">
          <?php foreach (priorities() as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $form['priority'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>سنة الإعلان</span>
        <select name="announced_year">
          <?php foreach ([2024, 2025, 2026] as $year): ?>
            <option value="<?= $year ?>"<?= (int) $form['announced_year'] === $year ? ' selected' : '' ?>><?= $year ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>نسبة الإنجاز <output id="progress-out"><?= (int) $form['progress'] ?>٪</output></span><input id="progress" name="progress" type="range" min="0" max="100" value="<?= (int) $form['progress'] ?>"></label>
      <label class="field"><span>تاريخ البداية</span><input type="date" name="start_date" required value="<?= e($form['start_date']) ?>"></label>
      <label class="field"><span>الموعد المتوقع</span><input type="date" name="expected_date" required value="<?= e($form['expected_date']) ?>"></label>
      <label class="field"><span>الميزانية بالريال</span><input name="budget" inputmode="decimal" value="<?= e($form['budget']) ?>" placeholder="اختياري"></label>
      <label class="field span-2"><span>ملاحظة المتابعة</span><input name="follow_note" value="<?= e($form['follow_note']) ?>" placeholder="تظهر في التنبيهات إن كان المشروع غير مكتمل"></label>
      <label class="field span-2"><span>تحديث يُضاف إلى السجل</span><textarea name="update_note" rows="2" placeholder="اختياري. إن تُرك فارغًا عند تغيير النسبة تُسجَّل ملاحظة تلقائية."><?= e($form['update_note'] ?? '') ?></textarea></label>
    </div>
  </section>
  <section class="form-section">
    <h2>الموقع على الخريطة</h2>
    <div class="form-grid">
      <label class="field span-2"><span>اسم الموقع</span><input name="location_name" required value="<?= e($form['location_name']) ?>"></label>
      <label class="field"><span>خط العرض</span><input id="latitude" name="latitude" required value="<?= e($form['latitude']) ?>"></label>
      <label class="field"><span>خط الطول</span><input id="longitude" name="longitude" required value="<?= e($form['longitude']) ?>"></label>
    </div>
    <p class="muted">انقر على الخريطة أو اسحب المؤشر لتحديد موقع المشروع داخل حائل.</p>
    <div class="map-shell" dir="ltr"><div id="picker-map" class="map-canvas map-short"></div></div>
  </section>
  <section class="form-section">
    <h2>الصور</h2>
    <label class="field"><span>رفع صور جديدة</span><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple></label>
    <?php if ($images): ?>
      <div class="image-picks">
        <?php foreach ($images as $image): ?>
          <label class="image-pick">
            <img src="<?= e(root_url($image['file_path'])) ?>" alt="">
            <span><input type="radio" name="cover_image" value="<?= (int) $image['id'] ?>"<?= (int) $image['is_cover'] === 1 ? ' checked' : '' ?>> غلاف</span>
            <span><input type="checkbox" name="delete_images[]" value="<?= (int) $image['id'] ?>"> حذف</span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted">بدون صور مرفوعة تُعرض لوحات توضيحية على المنصة.</p>
    <?php endif; ?>
  </section>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit"><?= $current ? 'حفظ التعديل' : 'إضافة المشروع' ?></button>
    <a class="btn btn-ghost" href="projects.php">رجوع</a>
    <?php if ($current): ?><a class="btn btn-ghost" href="../project.php?id=<?= (int) $current['id'] ?>">عرض في المنصة</a><?php endif; ?>
  </div>
</form>
<?php render_admin_footer(); ?>
