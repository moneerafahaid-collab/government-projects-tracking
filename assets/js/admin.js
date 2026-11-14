(function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
    });
  });

  var range = document.getElementById('progress');
  var output = document.getElementById('progress-out');
  if (range && output) {
    var paint = function () { output.textContent = range.value + '٪'; };
    range.addEventListener('input', paint);
    paint();
  }

  var picker = document.getElementById('picker-map');
  if (!picker || !window.L) return;
  var latInput = document.getElementById('latitude');
  var lngInput = document.getElementById('longitude');
  var lat = parseFloat(latInput.value);
  var lng = parseFloat(lngInput.value);
  if (!isFinite(lat) || !isFinite(lng)) {
    lat = 27.5219;
    lng = 41.6902;
  }
  var map = L.map(picker).setView([lat, lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);
  var marker = L.marker([lat, lng], { draggable: true }).addTo(map);
  var write = function (latlng) {
    latInput.value = latlng.lat.toFixed(6);
    lngInput.value = latlng.lng.toFixed(6);
    marker.setLatLng(latlng);
  };
  marker.on('dragend', function () { write(marker.getLatLng()); });
  map.on('click', function (event) { write(event.latlng); });
  function sync() {
    var nextLat = parseFloat(latInput.value);
    var nextLng = parseFloat(lngInput.value);
    if (!isFinite(nextLat) || !isFinite(nextLng)) return;
    marker.setLatLng([nextLat, nextLng]);
    map.panTo([nextLat, nextLng]);
  }
  latInput.addEventListener('change', sync);
  lngInput.addEventListener('change', sync);
  setTimeout(function () { map.invalidateSize(); }, 250);
})();
