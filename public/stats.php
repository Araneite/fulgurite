<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();
Auth::requirePermission('stats.view');

$defaultPeriod = (string) AppConfig::statsDefaultPeriodDays();
$period = $_GET['period'] ?? $defaultPeriod;
$period = in_array($period, ['7', '14', '30', '90'], true) ? $period : $defaultPeriod;

$title = t('stats.title');
$active = 'stats';
include 'layout_top.php';
?>

<div class="flex items-center gap-2 mb-4">
    <span style="font-size:13px;color:var(--text2)"><?= t('stats.period_label') ?></span>
    <?php foreach (['7' => t('stats.period_7d'), '14' => t('stats.period_14d'), '30' => t('stats.period_30d'), '90' => t('stats.period_90d')] as $value => $label): ?>
    <a href="?period=<?= $value ?>" class="btn btn-sm <?= $period === $value ? 'btn-primary' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div id="stats-page-root">
    <div class="stats-grid mb-4">
        <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="stat-card"><div class="skeleton-line" style="width:38%;margin-bottom:10px"></div><div class="skeleton-line short" style="width:60%"></div></div>
        <?php endfor; ?>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div class="card"><div class="skeleton-file-list"><?php for ($i = 0; $i < 5; $i++): ?><div class="skeleton-file-row"><div class="skeleton-dot"></div><div class="skeleton-line" style="width:45%"></div><div class="skeleton-line short" style="width:18%;margin-left:auto"></div></div><?php endfor; ?></div></div>
        <div class="card"><div class="skeleton-file-list"><?php for ($i = 0; $i < 5; $i++): ?><div class="skeleton-file-row"><div class="skeleton-dot"></div><div class="skeleton-line" style="width:40%"></div><div class="skeleton-line short" style="width:20%;margin-left:auto"></div></div><?php endfor; ?></div></div>
    </div>
    <div class="card"><div class="skeleton-file-list"><?php for ($i = 0; $i < 8; $i++): ?><div class="skeleton-file-row"><div class="skeleton-dot"></div><div class="skeleton-line" style="width:<?= $i % 2 === 0 ? '35%' : '52%' ?>"></div><div class="skeleton-line short" style="width:14%;margin-left:auto"></div></div><?php endfor; ?></div></div>
</div>

<script<?= cspNonceAttr() ?>>
(function() {
    let snapshotsChart = null;
    let activityChart = null;

    function renderStatsPayload(payload) {
        if (!payload || payload.status === 'error') {
            throw new Error((payload && payload.error) || '<?= h(t('stats.load_error')) ?>');
        }

        document.getElementById('stats-page-root').innerHTML = payload.html;
        hydrateStatsCharts();
    }

    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#e6edf3', font: { size: 11 } } } },
            scales: {
                x: { ticks: { color: '#8b949e', maxTicksLimit: 12, font: { size: 10 } }, grid: { color: '#21262d' } },
                y: { ticks: { color: '#8b949e', precision: 0, font: { size: 10 } }, grid: { color: '#21262d' }, beginAtZero: true },
            }
        };
    }

    function hydrateStatsCharts() {
        const payloadEl = document.getElementById('stats-page-data');
        if (!payloadEl || !window.ensureChartJs) {
            return;
        }

        let payload = null;
        try {
            payload = JSON.parse(payloadEl.textContent || '{}');
        } catch (error) {
            return;
        }

        window.ensureChartJs().then(() => {
            const snapshotsCanvas = document.getElementById('chart-snapshots');
            const activityCanvas = document.getElementById('chart-activity');

            if (snapshotsCanvas && payload.snapshots && payload.snapshots.labels && payload.snapshots.datasets && payload.snapshots.datasets.length) {
                if (snapshotsChart) {
                    snapshotsChart.destroy();
                }
                snapshotsChart = new Chart(snapshotsCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: payload.snapshots.labels,
                        datasets: payload.snapshots.datasets,
                    },
                    options: chartOptions(),
                });
            }

            if (activityCanvas && payload.activity && payload.activity.labels && payload.activity.labels.length) {
                if (activityChart) {
                    activityChart.destroy();
                }
                activityChart = new Chart(activityCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: payload.activity.labels,
                        datasets: [{
                            label: '<?= h(t('stats.chart_actions_label')) ?>',
                            data: payload.activity.values || [],
                            backgroundColor: '#1f6feb88',
                            borderColor: '#58a6ff',
                            borderWidth: 1,
                            borderRadius: 4,
                        }]
                    },
                    options: chartOptions(),
                });
            }
        }).catch(() => {});
    }

    async function loadStatsPage() {
        const fetchPayload = async function(url, cacheKey, maxStaleMs) {
            if (!window.fetchJsonWithCache) {
                return window.fetchJsonSafe(url, { timeoutMs: 12000 });
            }
            return window.fetchJsonWithCache(url, {
                cacheKey: cacheKey,
                maxStaleMs: maxStaleMs,
                timeoutMs: 12000,
                shouldCache: (data) => !!data && data.status === 'ready' && typeof data.html === 'string',
                onStaleData: (cachedPayload) => {
                    try {
                        renderStatsPayload(cachedPayload);
                    } catch (error) {}
                },
            });
        };

        try {
            try {
                const summaryPayload = await fetchPayload('/api/stats_page.php?period=<?= $period ?>&section=summary', 'stats-page:<?= $period ?>:summary:v1', 60000);
                renderStatsPayload(summaryPayload);
            } catch (error) {}

            const heavyPayload = await fetchPayload('/api/stats_page.php?period=<?= $period ?>&section=heavy', 'stats-page:<?= $period ?>:heavy:v1', 180000);
            renderStatsPayload(heavyPayload);
        } catch (error) {
            document.getElementById('stats-page-root').innerHTML =
                '<div class="alert alert-danger"><?= h(t('stats.load_error_prefix')) ?>' + (error.message || '<?= h(t('common.error')) ?>') + '</div>';
        }
    }

    window.addEventListener('load', loadStatsPage);
})();
</script>

<?php include 'layout_bottom.php'; ?>
