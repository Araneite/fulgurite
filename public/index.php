<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();

$title = 'Dashboard';
$active = 'dashboard';
include 'layout_top.php';

// Allows an advanced theme to fully override the page.
// Data is provided to the template via $ctx['data'] (empty here: the
// dashboard loads everything in JS, but we still expose it to themes
// that want to do server-side rendering via the rui_* template tags).
if (ThemeRenderer::renderPageOverride('dashboard', [
    'summary' => rui_dashboard_summary(),
    'statuses' => rui_repo_statuses(),
])) {
    include 'layout_bottom.php';
    return;
}
?>

<div id="dashboard-page-root">
    <div class="card mb-4">
        <div class="card-body">
            <div class="skeleton-toolbar">
                <div class="skeleton-chip"></div>
                <div class="skeleton-chip"></div>
            </div>
            <div class="skeleton-card-grid" style="margin-top:18px">
                <div class="skeleton-block"></div>
                <div class="skeleton-block"></div>
                <div class="skeleton-block"></div>
                <div class="skeleton-block"></div>
            </div>
        </div>
    </div>
    <div class="grid-2" style="gap:16px;margin-bottom:16px">
        <div class="card"><div class="skeleton-file-list"><?php for ($i = 0; $i < 8; $i++): ?><div class="skeleton-file-row"><div class="skeleton-dot"></div><div class="skeleton-line" style="width:42%"></div><div class="skeleton-line short" style="width:18%;margin-left:auto"></div></div><?php endfor; ?></div></div>
        <div class="card"><div class="skeleton-file-list"><?php for ($i = 0; $i < 8; $i++): ?><div class="skeleton-file-row"><div class="skeleton-dot"></div><div class="skeleton-line" style="width:38%"></div><div class="skeleton-line short" style="width:24%;margin-left:auto"></div></div><?php endfor; ?></div></div>
    </div>
    <div class="card"><div class="skeleton-file-list"><?php for ($i = 0; $i < 6; $i++): ?><div class="skeleton-file-row"><div class="skeleton-dot"></div><div class="skeleton-line" style="width:<?= $i % 2 === 0 ? '36%' : '48%' ?>"></div><div class="skeleton-line short" style="width:16%;margin-left:auto"></div></div><?php endfor; ?></div></div>
</div>

<script<?= cspNonceAttr() ?>>
(function() {
    let dashboardChart = null;
    let dashboardDiskChart = null;
    const dashboardRefreshMs = <?= AppConfig::dashboardRefreshSeconds() * 1000 ?>;

    function renderDashboardPayload(payload) {
        if (!payload || payload.status === 'error') {
            throw new Error((payload && payload.error) || '<?= h(t('dashboard.js.load_failed')) ?>');
        }

        document.getElementById('dashboard-page-root').innerHTML = payload.html;
        hydrateDashboardCharts();
    }

    function hydrateDashboardCharts() {
        const payloadEl = document.getElementById('dashboard-page-data');
        const canvas = document.getElementById('snapshots-chart');
        const diskCanvas = document.getElementById('disk-chart');
        if (!payloadEl || !window.ensureChartJs) {
            return;
        }

        let payload = null;
        try {
            payload = JSON.parse(payloadEl.textContent || '{}');
        } catch (error) {
            return;
        }

        const chart = payload.chart || {};
        const diskChart = payload.disk_chart || {};
        if (!Array.isArray(chart.labels) || !chart.labels.length || !Array.isArray(chart.datasets) || !chart.datasets.length) {
            if (!diskCanvas || !Array.isArray(diskChart.labels) || !diskChart.labels.length || !Array.isArray(diskChart.datasets) || !diskChart.datasets.length) {
                return;
            }
        }

        window.ensureChartJs().then(() => {
            if (dashboardChart && canvas) {
                dashboardChart.destroy();
            }
            if (canvas && Array.isArray(chart.labels) && chart.labels.length && Array.isArray(chart.datasets) && chart.datasets.length) {
                dashboardChart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: chart.labels,
                        datasets: chart.datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { labels: { color: '#e6edf3', font: { size: 11 } } } },
                        scales: {
                            x: { ticks: { color: '#8b949e', maxTicksLimit: 10, font: { size: 10 } }, grid: { color: '#21262d' } },
                            y: { ticks: { color: '#8b949e', precision: 0, font: { size: 10 } }, grid: { color: '#21262d' }, beginAtZero: true },
                        }
                    }
                });
            }

            if (dashboardDiskChart && diskCanvas) {
                dashboardDiskChart.destroy();
            }
            if (diskCanvas && Array.isArray(diskChart.labels) && diskChart.labels.length && Array.isArray(diskChart.datasets) && diskChart.datasets.length) {
                dashboardDiskChart = new Chart(diskCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: diskChart.labels,
                        datasets: diskChart.datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { labels: { color: '#e6edf3', font: { size: 10 } } } },
                        scales: {
                            x: { ticks: { color: '#8b949e', maxTicksLimit: 10, font: { size: 10 } }, grid: { color: '#21262d' } },
                            y: { ticks: { color: '#8b949e', callback: (value) => value + '%', font: { size: 10 } }, grid: { color: '#21262d' }, beginAtZero: false, suggestedMin: 0, suggestedMax: 100 },
                        }
                    }
                });
            }
        }).catch(() => {});
    }

    async function loadDashboardPage() {
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
                        renderDashboardPayload(cachedPayload);
                    } catch (error) {}
                },
            });
        };

        try {
            try {
                const summaryPayload = await fetchPayload('/api/dashboard_page.php?section=summary', 'dashboard-page:summary:v1', 60000);
                renderDashboardPayload(summaryPayload);
            } catch (error) {}

            const heavyPayload = await fetchPayload('/api/dashboard_page.php?section=heavy', 'dashboard-page:heavy:v1', 180000);
            renderDashboardPayload(heavyPayload);
        } catch (error) {
            document.getElementById('dashboard-page-root').innerHTML =
                '<div class="alert alert-danger"><?= h(t('dashboard.js.load_failed_prefix')) ?>' + (error.message || '<?= h(t('common.error')) ?>') + '</div>';
        }
    }

    window.refreshDashboard = async function() {
        try {
            const data = await window.fetchJsonSafe('/api/dashboard_statuses.php', { timeoutMs: 10000 });
            let totalSnaps = 0;
            let okCount = 0;
            let alertCount = 0;

            data.statuses.forEach((status) => {
                totalSnaps += status.count;
                if (status.status === 'ok') {
                    okCount++;
                } else if (['warning', 'error', 'no_snap'].includes(status.status)) {
                    alertCount++;
                }
            });

            const snapshotsEl = document.getElementById('stat-snapshots');
            const okEl = document.getElementById('stat-ok');
            const alertEl = document.getElementById('stat-alerts');
            const updatedEl = document.getElementById('last-update');

            if (snapshotsEl) snapshotsEl.textContent = totalSnaps;
            if (okEl) okEl.textContent = okCount;
            if (alertEl) {
                alertEl.textContent = alertCount;
                alertEl.style.color = alertCount > 0 ? 'var(--red)' : 'var(--text2)';
            }
            if (updatedEl) updatedEl.textContent = '<?= h(t('dashboard.js.updated_at_prefix')) ?>' + data.updated;
        } catch (error) {}
    };

    window.probeDiskNow = async function() {
        try {
            const payload = await window.fetchJsonSafe('/api/probe_disk_space.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>'},
                body: JSON.stringify({}),
                timeoutMs: 30000
            });
            if (!payload || !payload.success) {
                throw new Error((payload && payload.error) || '<?= h(t('dashboard.js.disk_probe_failed')) ?>');
            }
            if (typeof toast === 'function') {
                toast(payload.message || '<?= h(t('dashboard.js.disk_probe_done')) ?>', 'success');
            }
            await loadDashboardPage();
        } catch (error) {
            if (typeof toast === 'function') {
                toast(error.message || '<?= h(t('dashboard.js.disk_probe_failed')) ?>', 'error');
            }
        }
    };

    window.addEventListener('load', () => {
        loadDashboardPage().then(() => {
            if (window.registerVisibilityAwareInterval) {
                window.registerVisibilityAwareInterval(window.refreshDashboard, dashboardRefreshMs, { runImmediately: true });
            } else {
                window.setInterval(window.refreshDashboard, dashboardRefreshMs);
                window.refreshDashboard();
            }
        });
    });
})();
</script>

<?php include 'layout_bottom.php'; ?>
