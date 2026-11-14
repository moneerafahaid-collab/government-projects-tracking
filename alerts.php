<?php
define('APP', true);
require __DIR__ . '/includes/bootstrap.php';

$filters = filters_from_request();
$projects = alert_projects(fetch_projects($filters));
$agencies = fetch_agencies();

render_public_header('التنبيهات', 'alerts', false);
?>
<section class="container page-gap">
  <header class="page-intro">
    <h2>مشاريع تحتاج متابعة</h2>
    <p>تظهر هنا المشاريع المتجاوزة للموعد، أو المتوقفة، أو المتأخرة، أو التي تسير نسبة إنجازها أبطأ من الزمن المنقضي، إضافة إلى ما سُجّل عليه ملاحظة متابعة.</p>
  </header>
  <form class="filters" method="get">
    <label class="field"><span>السنة</span>
      <select name="year">
        <option value="">2024–2026</option>
        <?php foreach ([2024, 2025, 2026] as $year): ?>
          <option value="<?= $year ?>"<?= (int) ($filters['year'] ?? 0) === $year ? ' selected' : '' ?>><?= $year ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>الوكالة</span>
      <select name="agency">
        <option value="">كل الوكالات</option>
        <?php foreach ($agencies as $agency): ?>
          <option value="<?= (int) $agency['id'] ?>"<?= (int) ($filters['agency'] ?? 0) === (int) $agency['id'] ? ' selected' : '' ?>><?= e($agency['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>بحث</span><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"></label>
    <button class="btn btn-primary" type="submit">تصفية</button>
    <a class="btn btn-ghost" href="alerts.php">مسح</a>
  </form>

  <p class="result-line"><?= count($projects) ? e(count_label(count($projects))) . ' ضمن التنبيه' : 'لا تنبيهات مطابقة' ?></p>

  <?php if (!$projects): ?>
    <p class="empty">لا توجد مشاريع بحاجة إلى متابعة وفق هذه التصفية.</p>
  <?php else: ?>
    <ol class="alert-list">
      <?php foreach ($projects as $project): ?>
        <li class="alert-item level-<?= e($project['level']) ?>">
          <a class="alert-thumb" href="project.php?id=<?= (int) $project['id'] ?>">
            <img src="<?= e(cover_url($project)) ?>" alt="<?= e($project['title']) ?>">
          </a>
          <div>
            <p class="card-meta"><?= e($project['code']) ?> · <?= e($project['agency_name']) ?> · <?= e($project['department_name']) ?></p>
            <h3><a href="project.php?id=<?= (int) $project['id'] ?>"><?= e($project['title']) ?></a></h3>
            <ul>
              <?php foreach ($project['alerts'] as $reason): ?>
                <li><?= e($reason['text']) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="alert-side">
            <?= status_pill($project['status']) ?>
            <strong><?= (int) $project['progress'] ?>٪</strong>
            <?= progress_bar((int) $project['progress']) ?>
            <span><?= e($project['location_name']) ?></span>
            <span><?= e(schedule_label($project)) ?></span>
            <span>المتوقع <?= e(ar_date($project['expected_date'])) ?></span>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</section>
<?php render_public_footer(); ?>
