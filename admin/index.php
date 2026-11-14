<?php
define('APP', true);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$projects = fetch_projects();
$agencies = fetch_agencies();
$alerts = alert_projects($projects);
$updates = fetch_recent_updates(6);
$total = count($projects);
$average = $total ? (int) round(array_sum(array_map(fn($p) => (int) $p['progress'], $projects)) / $total) : 0;
$done = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));

render_admin_header('نظرة عامة');
?>
<section class="stat-grid">
  <article class="kpi"><span>المشاريع</span><strong><?= $total ?></strong><small>2024 – 2026</small></article>
  <article class="kpi"><span>متوسط الإنجاز</span><strong><?= $average ?>٪</strong><small>كل السجلات</small></article>
  <article class="kpi kpi-alert"><span>تنبيهات</span><strong><?= count($alerts) ?></strong><small>تحتاج متابعة</small></article>
  <article class="kpi"><span>مكتملة</span><strong><?= $done ?></strong><small>أُغلق سجلها</small></article>
</section>

<section class="admin-grid">
  <article class="panel">
    <h2>الوكالات</h2>
    <ul class="bars">
      <?php foreach ($agencies as $agency): ?>
        <li>
          <div><strong><?= e($agency['name']) ?></strong><span><?= (int) $agency['project_count'] ?> مشروع · <?= (int) $agency['avg_progress'] ?>٪</span></div>
          <div class="bar"><span style="width: <?= (int) $agency['avg_progress'] ?>%; background: <?= e($agency['color']) ?>"></span></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </article>
  <article class="panel">
    <h2>آخر التحديثات</h2>
    <?php if (!$updates): ?>
      <p class="empty">لا تحديثات بعد.</p>
    <?php else: ?>
      <ul class="plain-list">
        <?php foreach ($updates as $update): ?>
          <li>
            <a href="project-form.php?id=<?= (int) $update['project_id'] ?>"><?= e($update['title']) ?></a>
            <span><?= (int) $update['progress'] ?>٪ · <?= e(ar_date(substr($update['created_at'], 0, 10))) ?></span>
            <p><?= e($update['note']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </article>
</section>

<section class="panel">
  <div class="panel-head"><h2>أبرز التنبيهات</h2><a href="../alerts.php">عرض المنصة</a></div>
  <?php if (!$alerts): ?>
    <p class="empty">لا تنبيهات حالية.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>المشروع</th><th>الوكالة</th><th>الإنجاز</th><th>السبب</th><th></th></tr></thead>
        <tbody>
          <?php foreach (array_slice($alerts, 0, 8) as $project): ?>
            <tr>
              <td><?= e($project['title']) ?></td>
              <td><?= e($project['agency_name']) ?></td>
              <td><?= (int) $project['progress'] ?>٪</td>
              <td><?= e($project['alerts'][0]['text']) ?></td>
              <td><a href="project-form.php?id=<?= (int) $project['id'] ?>">تعديل</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php render_admin_footer(); ?>
