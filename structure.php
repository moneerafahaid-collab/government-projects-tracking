<?php
define('APP', true);
require __DIR__ . '/includes/bootstrap.php';

$agencies = fetch_agencies();
$departments = fetch_departments();
$byAgency = [];
foreach ($departments as $department) {
    $byAgency[$department['agency_id']][] = $department;
}

render_public_header('الوكالات والإدارات', 'structure', false);
?>
<section class="container page-gap">
  <header class="page-intro">
    <h2>هيكل المتابعة</h2>
    <p>ثلاث وكالات، وتحت كل وكالة إدارات تتولى مشاريعها. اختر إدارة لعرض مشاريعها على الخريطة والسجل.</p>
  </header>
  <div class="org">
    <?php foreach ($agencies as $agency): ?>
      <article class="org-card" style="--agency: <?= e($agency['color']) ?>">
        <header>
          <p class="kicker">وكالة</p>
          <h2><?= e($agency['name']) ?></h2>
          <p><?= e($agency['description']) ?></p>
          <p class="org-stats"><?= (int) $agency['department_count'] ?> إدارات · <?= e(count_label((int) $agency['project_count'])) ?> · متوسط <?= (int) $agency['avg_progress'] ?>٪</p>
        </header>
        <ul class="dept-list">
          <?php foreach ($byAgency[$agency['id']] ?? [] as $department): ?>
            <li>
              <a href="index.php?agency=<?= (int) $agency['id'] ?>&amp;dept=<?= (int) $department['id'] ?>#list"><?= e($department['name']) ?></a>
              <span><?= (int) $department['project_count'] ?></span>
              <small><?= e($department['description']) ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
        <a class="btn btn-ghost" href="index.php?agency=<?= (int) $agency['id'] ?>#list">مشاريع الوكالة</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php render_public_footer(); ?>
