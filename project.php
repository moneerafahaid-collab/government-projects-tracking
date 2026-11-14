<?php
define('APP', true);
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$project = $id ? fetch_project($id) : null;
if (!$project) {
    http_response_code(404);
    render_public_header('المشروع غير موجود', 'home', false);
    echo '<section class="container page-gap"><h2>المشروع غير موجود</h2><p>تحقق من الرابط أو ارجع إلى السجل.</p><p><a class="btn btn-primary" href="index.php">سجل المشاريع</a></p></section>';
    render_public_footer();
    exit;
}

$gallery = project_gallery((int) $project['id']);
$updates = fetch_updates((int) $project['id']);
$related = related_projects($project);
$percent = (int) $project['progress'];

render_public_header($project['title'], 'project', true);
render_map_data([$project]);
?>
<article class="container detail">
  <p class="crumb"><a href="index.php">المشاريع</a> / <a href="index.php?agency=<?= (int) $project['agency_id'] ?>"><?= e($project['agency_name']) ?></a> / <span><?= e($project['code']) ?></span></p>
  <header class="detail-title">
    <div>
      <p class="kicker"><?= e($project['agency_name']) ?> · <?= e($project['department_name']) ?></p>
      <h2><?= e($project['title']) ?></h2>
      <p class="lede"><?= e($project['summary']) ?></p>
    </div>
    <?= status_pill($project['status']) ?>
  </header>

  <?php if (!empty($project['alerts'])): ?>
    <div class="alert-banner" role="status">
      <strong>هذا المشروع يحتاج متابعة</strong>
      <ul>
        <?php foreach ($project['alerts'] as $reason): ?>
          <li><?= e($reason['text']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="detail-grid">
    <div class="gallery">
      <figure>
        <img id="gallery-main" src="<?= e(root_url($gallery[0]['file_path'])) ?>" alt="<?= e($gallery[0]['caption'] ?: $project['title']) ?>">
        <figcaption id="gallery-caption"><?= e($gallery[0]['caption'] ?: $project['title']) ?></figcaption>
      </figure>
      <?php if (count($gallery) > 1): ?>
        <div class="thumbs">
          <?php foreach ($gallery as $index => $image): ?>
            <button type="button" class="thumb<?= $index === 0 ? ' is-on' : '' ?>" data-src="<?= e(root_url($image['file_path'])) ?>" data-alt="<?= e($image['caption'] ?: $project['title']) ?>">
              <img src="<?= e(root_url($image['file_path'])) ?>" alt="">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <aside class="facts">
      <div class="ring-wrap" style="color: <?= e($project['agency_color']) ?>">
        <svg class="ring" viewBox="0 0 36 36" aria-hidden="true">
          <circle class="ring-track" cx="18" cy="18" r="15.5" pathLength="100"></circle>
          <circle class="ring-value" cx="18" cy="18" r="15.5" pathLength="100" stroke-dasharray="<?= $percent ?> 100"></circle>
        </svg>
        <div class="ring-label"><strong><?= $percent ?>٪</strong><span>نسبة الإنجاز</span></div>
      </div>
      <dl class="fact-list">
        <div><dt>الرمز</dt><dd><?= e($project['code']) ?></dd></div>
        <div><dt>سنة الإعلان</dt><dd><?= (int) $project['announced_year'] ?></dd></div>
        <div><dt>الحالة</dt><dd><?= e(status_label($project['status'])) ?></dd></div>
        <div><dt>الأولوية</dt><dd><?= e(priority_label($project['priority'])) ?></dd></div>
        <div><dt>البداية</dt><dd><?= e(ar_date($project['start_date'])) ?></dd></div>
        <div><dt>الموعد المتوقع</dt><dd><?= e(ar_date($project['expected_date'])) ?></dd></div>
        <div><dt>الجدول</dt><dd><?= e(schedule_label($project)) ?></dd></div>
        <div><dt>الموقع</dt><dd><?= e($project['location_name']) ?></dd></div>
        <div><dt>مدير المشروع</dt><dd><?= e($project['manager_name']) ?></dd></div>
        <div><dt>الميزانية</dt><dd><?= e(sar($project['budget'])) ?></dd></div>
      </dl>
    </aside>
  </div>

  <section class="prose">
    <h3>وصف المشروع</h3>
    <?php foreach (preg_split("/\n+/", trim($project['description'])) as $paragraph): ?>
      <?php if (trim($paragraph) !== ''): ?><p><?= e(trim($paragraph)) ?></p><?php endif; ?>
    <?php endforeach; ?>
  </section>

  <section class="split-bottom">
    <div>
      <h3>سجل التحديثات</h3>
      <?php if (!$updates): ?>
        <p class="empty">لا توجد تحديثات مسجلة بعد.</p>
      <?php else: ?>
        <ol class="timeline">
          <?php foreach ($updates as $update): ?>
            <li>
              <time datetime="<?= e($update['created_at']) ?>"><?= e(ar_date(substr($update['created_at'], 0, 10))) ?></time>
              <strong><?= (int) $update['progress'] ?>٪</strong>
              <p><?= e($update['note']) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
    <div>
      <h3>الموقع</h3>
      <div class="map-shell" dir="ltr">
        <div id="city-map" class="js-map map-canvas map-short" data-single="1" role="region" aria-label="موقع المشروع"></div>
      </div>
      <p class="muted"><?= e($project['location_name']) ?> · <span dir="ltr"><?= e($project['latitude']) ?> , <?= e($project['longitude']) ?></span></p>
    </div>
  </section>

  <?php if ($related): ?>
    <section>
      <h3>مشاريع قريبة في الإدارة</h3>
      <div class="cards cards-compact">
        <?php foreach ($related as $item): ?>
          <article class="card">
            <a class="card-media" href="project.php?id=<?= (int) $item['id'] ?>"><img src="<?= e(cover_url($item)) ?>" alt="<?= e($item['title']) ?>"><?= status_pill($item['status']) ?></a>
            <div class="card-body">
              <p class="card-meta"><?= (int) $item['announced_year'] ?></p>
              <h3><a href="project.php?id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a></h3>
              <?= progress_bar((int) $item['progress']) ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</article>
<?php render_public_footer(); ?>
