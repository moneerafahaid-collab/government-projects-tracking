<?php
define('APP', true);
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
$projects = fetch_projects(filters_from_request());
echo json_encode(['projects' => map_payload($projects)], JSON_UNESCAPED_UNICODE);
