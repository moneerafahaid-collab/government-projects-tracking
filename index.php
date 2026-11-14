<?php
define('APP', true);
require __DIR__ . '/includes/bootstrap.php';

$filters = filters_from_request();
$projects = fetch_projects($filters);
$agencies = fetch_agencies();
$counts = year_counts();
$byAgency = [];
foreach ($projects as $project) {
    $byAgency[$project['agency_id']][] = $project;
}
$total = count($projects);
$average = $total ? (int) round(array_sum(array_map(fn($p) => (int) $p['progress'], $projects)) / $total) : 0;
$done = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
$attention = alert_projects($projects);
$preview = array_slice($attention, 0, 4);

render_public_header('المشاريع', 'home', true);
render_map_data($projects);
?>
<section class="container map-layout" id="map">
  <div class="map-panel">
    <div class="panel-head">
      <div>
        <h2>تابع تقدّم المشاريع على الخريطة</h2>
        <p class="muted">نسب الإنجاز والحالة والمواعيد عبر الوكالات الثلاث.</p>
      </div>
      <button type="button" class="btn btn-ghost" id="map-reset">إعادة ضبط الإطار</button>
    </div>
    <div class="map-shell" dir="ltr">
      <div id="city-map" class="js-map map-canvas" role="region" aria-label="خريطة مشاريع حائل"></div>
    </div>
    <ul class="legend">
      <li><i class="status-completed"></i> مكتمل</li>
      <li><i class="status-in_progress"></i> قيد التنفيذ</li>
      <li><i class="status-needs_followup"></i> يحتاج متابعة</li>
      <li><i class="status-delayed"></i> متأخر</li>
      <li><i class="status-on_hold"></i> متوقف</li>
      <li><i class="status-planned"></i> مخطط</li>
    </ul>
    <noscript><p>القائمة أدناه تعرض المشاريع ومواقعها عند تعطيل JavaScript.</p></noscript>
  </div>
  <aside class="alert-panel">
    <div class="panel-head">
      <h2>تنبيهات هذا العرض</h2>
      <a href="alerts.php<?= e(page_query()) ?>">كل التنبيهات</a>
    </div>
    <?php if (!$preview): ?>
      <p class="empty">لا توجد مشاريع بحاجة إلى متابعة ضمن هذا العرض.</p>
    <?php else: ?>
      <ul class="alert-mini">
        <?php foreach ($preview as $item): ?>
          <li>
            <a href="project.php?id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a>
            <span><?= e($item['agency_name']) ?> · <?= e($item['alerts'][0]['text']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </aside>
</section>

<section class="container hail-numbers" aria-label="ملخص">
  <div class="kpis">
  <article class="kpi"><span>المشاريع المعروضة</span><strong><?= (int) $total ?></strong><small><?= e(count_label($total)) ?></small></article>
  <article class="kpi"><span>متوسط الإنجاز</span><strong><?= (int) $average ?>٪</strong><small>على السجلات الظاهرة</small></article>
  <article class="kpi kpi-alert"><span>تحتاج متابعة</span><strong><?= count($attention) ?></strong><small>تنبيه موعد أو حالة أو وتيرة</small></article>
  <article class="kpi"><span>مكتملة</span><strong><?= (int) $done ?></strong><small>أُغلق سجلها</small></article>
  </div>
</section>

<section class="container list-block" id="list">
  <form class="filters" method="get" action="index.php">
    <div class="years" role="tablist" aria-label="سنة الإعلان">
      <a class="<?= empty($filters['year']) ? 'is-on' : '' ?>" href="index.php<?= e(page_query([], ['year'])) ?>">الكل <small><?= array_sum($counts) ?></small></a>
      <?php foreach ([2024, 2025, 2026] as $year): ?>
        <a class="<?= (int) ($filters['year'] ?? 0) === $year ? 'is-on' : '' ?>" href="index.php<?= e(page_query(['year' => $year])) ?>"><?= $year ?> <small><?= (int) $counts[$year] ?></small></a>
      <?php endforeach; ?>
    </div>
    <label class="field"><span>بحث</span><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="اسم المشروع أو الحي أو الإدارة"></label>
    <label class="field"><span>الوكالة</span>
      <select name="agency">
        <option value="">كل الوكالات</option>
        <?php foreach ($agencies as $agency): ?>
          <option value="<?= (int) $agency['id'] ?>"<?= (int) ($filters['agency'] ?? 0) === (int) $agency['id'] ? ' selected' : '' ?>><?= e($agency['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>الحالة</span>
      <select name="status">
        <option value="">كل الحالات</option>
        <?php foreach (statuses() as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= ($filters['status'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>الترتيب</span>
      <select name="sort">
        <option value="">الأحدث إعلانًا</option>
        <option value="due"<?= ($filters['sort'] ?? '') === 'due' ? ' selected' : '' ?>>الأقرب استحقاقًا</option>
        <option value="progress"<?= ($filters['sort'] ?? '') === 'progress' ? ' selected' : '' ?>>الأقل إنجازًا</option>
        <option value="alert"<?= ($filters['sort'] ?? '') === 'alert' ? ' selected' : '' ?>>التنبيهات أولًا</option>
      </select>
    </label>
    <?php if (!empty($filters['department'])): ?>
      <input type="hidden" name="dept" value="<?= (int) $filters['department'] ?>">
    <?php endif; ?>
    <?php if (!empty($filters['year'])): ?>
      <input type="hidden" name="year" value="<?= (int) $filters['year'] ?>">
    <?php endif; ?>
    <button class="btn btn-primary" type="submit">تطبيق</button>
    <a class="btn btn-ghost" href="index.php">مسح</a>
  </form>

  <?php if (!$projects): ?>
    <p class="empty">لا توجد مشاريع مطابقة. جرّب سنة أخرى أو امسح التصفية.</p>
  <?php endif; ?>

  <?php foreach ($agencies as $agency): ?>
    <?php if (empty($byAgency[$agency['id']])) { continue; } ?>
    <?php
      $items = $byAgency[$agency['id']];
      $agencyAvg = (int) round(array_sum(array_map(fn($p) => (int) $p['progress'], $items)) / count($items));
      $depts = [];
      foreach ($items as $item) {
          $depts[$item['department_id']] = $item['department_name'];
      }
    ?>
    <section class="agency-block" id="agency-<?= (int) $agency['id'] ?>" style="--agency: <?= e($agency['color']) ?>">
      <header class="agency-head">
        <div>
          <p class="kicker">وكالة</p>
          <h2><?= e($agency['name']) ?></h2>
          <p><?= e($agency['description']) ?></p>
        </div>
        <p class="agency-stat"><strong><?= $agencyAvg ?>٪</strong><span>متوسط هذا العرض · <?= e(count_label(count($items))) ?></span></p>
      </header>
      <div class="chips">
        <?php foreach ($depts as $deptId => $deptName): ?>
          <a class="chip<?= (int) ($filters['department'] ?? 0) === (int) $deptId ? ' is-on' : '' ?>" href="index.php<?= e(page_query(['dept' => $deptId, 'agency' => $agency['id']])) ?>"><?= e($deptName) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="cards">
        <?php foreach ($items as $project): ?>
          <article class="card<?= !empty($project['alerts']) ? ' has-alert' : '' ?>" id="project-<?= (int) $project['id'] ?>" style="--agency: <?= e($project['agency_color']) ?>">
            <a class="card-media" href="project.php?id=<?= (int) $project['id'] ?>">
              <img src="<?= e(cover_url($project)) ?>" alt="<?= e($project['title']) ?>">
              <?= status_pill($project['status']) ?>
              <?php if (!empty($project['alerts'])): ?><em class="flag">تنبيه</em><?php endif; ?>
            </a>
            <div class="card-body">
              <p class="card-meta"><?= e($project['department_name']) ?> · <?= (int) $project['announced_year'] ?></p>
              <h3><a href="project.php?id=<?= (int) $project['id'] ?>"><?= e($project['title']) ?></a></h3>
              <p class="summary"><?= e($project['summary']) ?></p>
              <?= progress_bar((int) $project['progress']) ?>
              <div class="card-foot">
                <span><?= (int) $project['progress'] ?>٪</span>
                <span><?= e($project['location_name']) ?></span>
                <span><?= e(schedule_label($project)) ?></span>
              </div>
              <button class="pin-jump" type="button" data-focus="<?= (int) $project['id'] ?>">أظهر على الخريطة</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
</section>
<?php render_public_footer(); ?>
