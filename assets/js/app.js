(function () {
  var menuBtn = document.getElementById('mobile-menu-toggle');
  var nav = document.getElementById('main-nav');
  if (menuBtn && nav) {
    menuBtn.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  function esc(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  document.querySelectorAll('.thumb').forEach(function (button) {
    button.addEventListener('click', function () {
      var image = document.getElementById('gallery-main');
      var caption = document.getElementById('gallery-caption');
      if (!image) return;
      image.src = button.getAttribute('data-src');
      image.alt = button.getAttribute('data-alt') || '';
      if (caption) caption.textContent = button.getAttribute('data-alt') || '';
      document.querySelectorAll('.thumb').forEach(function (item) { item.classList.remove('is-on'); });
      button.classList.add('is-on');
    });
  });

  var mapEl = document.getElementById('city-map');
  if (!mapEl) return;
  if (!window.L) {
    mapEl.innerHTML = '<p class="map-fallback">تعذر تحميل الخريطة. تبقى بيانات المشاريع في القائمة.</p>';
    return;
  }

  var projects = window.PROJECTS || [];
  var single = mapEl.getAttribute('data-single') === '1';
  var map = L.map(mapEl, { scrollWheelZoom: true, zoomControl: true }).setView([27.511, 41.72], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  var markers = {};
  var bounds = [];
  projects.forEach(function (project) {
    if (!project.lat || !project.lng) return;
    var icon = L.divIcon({
      className: 'pin-wrap',
      html: '<span class="dot status-' + esc(project.status) + (project.alert ? ' is-alert' : '') + '">' + esc(project.progress) + '</span>',
      iconSize: [34, 34],
      iconAnchor: [17, 17]
    });
    var marker = L.marker([project.lat, project.lng], { icon: icon, title: project.title }).addTo(map);
    marker.bindPopup(
      '<div class="hail-pop"><strong>' + esc(project.title) + '</strong>' +
      '<p>' + esc(project.agency) + '</p>' +
      '<p>' + esc(project.statusLabel) + ' · ' + esc(project.progress) + '٪</p>' +
      '<p>' + esc(project.location) + ' · المتوقع ' + esc(project.expected) + '</p>' +
      '<a href="' + esc(project.url) + '">تفاصيل المشروع</a></div>'
    );
    markers[project.id] = marker;
    bounds.push([project.lat, project.lng]);
  });

  if (single && bounds.length === 1) {
    map.setView(bounds[0], 14);
  } else if (bounds.length) {
    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
  }

  var reset = document.getElementById('map-reset');
  if (reset && bounds.length) {
    var frame = bounds.slice();
    reset.addEventListener('click', function () {
      if (frame.length === 1) map.setView(frame[0], 13);
      else map.fitBounds(frame, { padding: [36, 36], maxZoom: 13 });
    });
  }

  document.querySelectorAll('.pin-jump').forEach(function (button) {
    button.addEventListener('click', function () {
      var marker = markers[button.getAttribute('data-focus')];
      if (!marker) return;
      map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14), { animate: true });
      marker.openPopup();
      document.querySelectorAll('.card.is-focused').forEach(function (card) { card.classList.remove('is-focused'); });
      var card = document.getElementById('project-' + button.getAttribute('data-focus'));
      if (card) card.classList.add('is-focused');
      var mapBox = document.getElementById('map');
      if (mapBox) mapBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });

  setTimeout(function () { map.invalidateSize(); }, 200);
})();
