<?php
$p = max(0, (int) ($_GET['p'] ?? 1));
$v = max(1, (int) ($_GET['v'] ?? 1));
$palettes = [
    ['#0B1F2A', '#163445', '#C4843C', '#F4EFE6', '#E8C38A'],
    ['#163445', '#1d4e6e', '#E8C38A', '#F4EFE6', '#9fc0d4'],
    ['#0B1F2A', '#5C6B73', '#C4843C', '#FFFcf7', '#E8C38A'],
];
[$ink, $mid, $sand, $paper, $mist] = $palettes[$p % 3];
if ($v === 3) {
    $ink = '#1b2430';
    $mid = '#3d5368';
}
$motif = ($p + $v) % 6;
$shift = ($p % 5) * 10;
header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: public, max-age=604800');
header('X-Content-Type-Options: nosniff');

$motifSvg = '';
if ($motif === 0) {
    $motifSvg = '<g fill="none" stroke="' . $sand . '" stroke-width="2"><circle cx="250" cy="340" r="8"/><circle cx="330" cy="300" r="8"/><circle cx="420" cy="350" r="8"/><circle cx="510" cy="310" r="8"/><path d="M250 340 330 300 420 350 510 310"/></g>';
} elseif ($motif === 1) {
    $motifSvg = '<g fill="' . $sand . '"><rect x="250" y="300" width="70" height="90"/><rect x="330" y="260" width="54" height="130"/><rect x="394" y="320" width="80" height="70"/><rect x="268" y="318" width="16" height="16" fill="' . $ink . '"/><rect x="346" y="280" width="14" height="14" fill="' . $ink . '"/></g>';
} elseif ($motif === 2) {
    $motifSvg = '<g fill="' . $sand . '"><rect x="250" y="330" width="28" height="60"/><rect x="292" y="290" width="28" height="100"/><rect x="334" y="310" width="28" height="80"/><rect x="376" y="250" width="28" height="140"/><rect x="418" y="300" width="28" height="90"/></g>';
} elseif ($motif === 3) {
    $motifSvg = '<path d="M360 250 430 276 430 340c0 40-70 62-70 62s-70-22-70-62v-64Z" fill="none" stroke="' . $sand . '" stroke-width="3"/><path d="M330 330 352 352 398 306" fill="none" stroke="' . $sand . '" stroke-width="3"/>';
} elseif ($motif === 4) {
    $motifSvg = '<g fill="' . $mid . '"><circle cx="280" cy="300" r="26"/><rect x="274" y="300" width="12" height="70"/><circle cx="360" cy="286" r="32"/><rect x="354" y="286" width="12" height="84"/><circle cx="450" cy="304" r="22"/><rect x="444" y="304" width="12" height="66"/></g>';
} else {
    $motifSvg = '<g fill="none" stroke="' . $sand . '" stroke-width="2"><circle cx="370" cy="320" r="28"/><circle cx="370" cy="320" r="52"/><circle cx="370" cy="320" r="76"/><path d="M370 240v160M290 320h160"/></g>';
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" role="img" aria-label="صورة توضيحية">';
echo '<rect width="800" height="500" fill="' . $paper . '"/>';
echo '<rect width="800" height="500" fill="' . $ink . '" opacity="0.92"/>';
echo '<circle cx="' . (560 + $shift) . '" cy="120" r="54" fill="' . $sand . '" opacity="0.9"/>';
echo '<path d="M0 300 C 120 250, 180 340, 280 300 C 380 250, 430 210, 520 250 C 620 290, 700 220, 800 250 L 800 500 L 0 500 Z" fill="' . $mid . '"/>';
echo '<path d="M0 360 C 160 330, 240 400, 380 360 C 520 320, 640 390, 800 350 L 800 500 L 0 500 Z" fill="' . $ink . '"/>';
echo '<path d="M40 250 150 150 230 230 250 250" fill="none" stroke="' . $mist . '" stroke-width="8" opacity="0.7"/>';
echo '<path d="M520 250 640 120 760 240" fill="none" stroke="' . $sand . '" stroke-width="8" opacity="0.85"/>';
echo $motifSvg;
echo '<rect x="36" y="36" width="92" height="28" rx="14" fill="' . $sand . '"/>';
echo '<text x="82" y="55" text-anchor="middle" font-family="Segoe UI, Tahoma, sans-serif" font-size="16" fill="' . $ink . '">حائل</text>';
echo '</svg>';
