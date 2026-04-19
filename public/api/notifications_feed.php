<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
rateLimitApi('notifications_feed', 120, 60);

$user = Auth::currentUser();
$afterId = max(0, (int) ($_GET['after_id'] ?? 0));
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 10)));

$payload = AppNotificationManager::getFeedForUser((int) ($user['id'] ?? 0), $afterId, $limit);
jsonResponseCached($payload, 200, 5);
