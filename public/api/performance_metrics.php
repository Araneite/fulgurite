<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
rateLimitApi('performance_metrics', 30, 60);

jsonResponseCached([
    'success' => true,
    'metrics' => PerformanceMetrics::collectLightweight(),
], 200, AppConfig::performanceMetricsCacheTtlSeconds());
