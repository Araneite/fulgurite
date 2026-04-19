<?php
/**
 * Fulgurite onboarding wizard
 * Included by layout_bottom.php on all authenticated pages.
 * Shown automatically on first login, re-openable from the footer.
 */

// Only for logged-in users
if (!Auth::isLoggedIn()) return;
?>

<div id="onboarding-wizard" class="onboarding-overlay" role="dialog" aria-modal="true" aria-label="<?= h(t('onboarding.aria_label')) ?>">
    <div class="onboarding-shell">

        <!-- Step sidebar -->
        <aside class="onboarding-sidebar">
            <div class="onboarding-brand">
                <div class="onboarding-brand-icon">R</div>
                <div>
                    <div class="onboarding-brand-name"><?= h(AppConfig::appName()) ?></div>
                    <div class="onboarding-brand-sub"><?= t('onboarding.brand_sub') ?></div>
                </div>
            </div>

            <nav class="onboarding-steps-nav" aria-label="<?= h(t('onboarding.steps_aria_label')) ?>">
                <div class="onboarding-step-item" data-step="0">
                    <div class="onboarding-step-indicator">
                        <span class="onboarding-step-check">✓</span>
                        <span class="onboarding-step-num">1</span>
                    </div>
                    <div class="onboarding-step-label"><?= t('onboarding.step.welcome') ?></div>
                </div>
                <div class="onboarding-step-connector"></div>
                <div class="onboarding-step-item" data-step="1">
                    <div class="onboarding-step-indicator">
                        <span class="onboarding-step-check">✓</span>
                        <span class="onboarding-step-num">2</span>
                    </div>
                    <div class="onboarding-step-label"><?= t('onboarding.step.repos') ?></div>
                </div>
                <div class="onboarding-step-connector"></div>
                <div class="onboarding-step-item" data-step="2">
                    <div class="onboarding-step-indicator">
                        <span class="onboarding-step-check">✓</span>
                        <span class="onboarding-step-num">3</span>
                    </div>
                    <div class="onboarding-step-label"><?= t('onboarding.step.backups') ?></div>
                </div>
                <div class="onboarding-step-connector"></div>
                <div class="onboarding-step-item" data-step="3">
                    <div class="onboarding-step-indicator">
                        <span class="onboarding-step-check">✓</span>
                        <span class="onboarding-step-num">4</span>
                    </div>
                    <div class="onboarding-step-label"><?= t('onboarding.step.restore') ?></div>
                </div>
                <div class="onboarding-step-connector"></div>
                <div class="onboarding-step-item" data-step="4">
                    <div class="onboarding-step-indicator">
                        <span class="onboarding-step-check">✓</span>
                        <span class="onboarding-step-num">5</span>
                    </div>
                    <div class="onboarding-step-label"><?= t('onboarding.step.security') ?></div>
                </div>
                <div class="onboarding-step-connector"></div>
                <div class="onboarding-step-item" data-step="5">
                    <div class="onboarding-step-indicator">
                        <span class="onboarding-step-check">✓</span>
                        <span class="onboarding-step-num">6</span>
                    </div>
                    <div class="onboarding-step-label"><?= t('onboarding.step.ready') ?></div>
                </div>
            </nav>

            <button type="button" class="onboarding-skip-btn" onclick="window.closeOnboardingWizard()">
                <?= t('onboarding.skip') ?>
            </button>
        </aside>

            <!-- Main content -->
        <div class="onboarding-content">

            <!-- Progress bar -->
            <div class="onboarding-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                <div class="onboarding-progress-fill" id="onboarding-progress-fill"></div>
            </div>

            <!-- Step counter -->
            <div class="onboarding-step-counter">
                <span id="onboarding-step-current">1</span> / <span id="onboarding-step-total">6</span>
            </div>

            <!-- ── Step 0: Welcome ─────────────────────────────────── -->
            <div class="onboarding-panel" data-panel="0">
                <div class="onboarding-panel-icon">👋</div>
                <h2 class="onboarding-panel-title"><?= t('onboarding.welcome.title', ['app' => h(AppConfig::appName())]) ?></h2>
                <p class="onboarding-panel-lead">
                    <?= t('onboarding.welcome.lead', ['app' => h(AppConfig::appName())]) ?>
                </p>

                <div class="onboarding-feature-grid">
                    <div class="onboarding-feature-card">
                        <div class="onboarding-feature-icon">🗄️</div>
                        <div>
                            <div class="onboarding-feature-title"><?= t('onboarding.welcome.feature.encrypted_repos.title') ?></div>
                            <div class="onboarding-feature-desc"><?= t('onboarding.welcome.feature.encrypted_repos.desc') ?></div>
                        </div>
                    </div>
                    <div class="onboarding-feature-card">
                        <div class="onboarding-feature-icon">⏱️</div>
                        <div>
                            <div class="onboarding-feature-title"><?= t('onboarding.welcome.feature.scheduling.title') ?></div>
                            <div class="onboarding-feature-desc"><?= t('onboarding.welcome.feature.scheduling.desc') ?></div>
                        </div>
                    </div>
                    <div class="onboarding-feature-card">
                        <div class="onboarding-feature-icon">🔍</div>
                        <div>
                            <div class="onboarding-feature-title"><?= t('onboarding.welcome.feature.explore_restore.title') ?></div>
                            <div class="onboarding-feature-desc"><?= t('onboarding.welcome.feature.explore_restore.desc') ?></div>
                        </div>
                    </div>
                    <div class="onboarding-feature-card">
                        <div class="onboarding-feature-icon">🔔</div>
                        <div>
                            <div class="onboarding-feature-title"><?= t('onboarding.welcome.feature.notifications.title') ?></div>
                            <div class="onboarding-feature-desc"><?= t('onboarding.welcome.feature.notifications.desc') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Step 1: Repositories ────────────────────────────── -->
            <div class="onboarding-panel" data-panel="1" hidden>
                <div class="onboarding-panel-icon">🗄️</div>
                <h2 class="onboarding-panel-title"><?= t('onboarding.repos.title') ?></h2>
                <p class="onboarding-panel-lead">
                    <?= t('onboarding.repos.lead') ?>
                </p>

                <div class="onboarding-info-blocks">
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">1</span>
                            <?= t('onboarding.repos.block1.title') ?>
                        </div>
                        <p><?= t('onboarding.repos.block1.body') ?></p>
                    </div>
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">2</span>
                            <?= t('onboarding.repos.block2.title') ?>
                        </div>
                        <p><?= t('onboarding.repos.block2.body') ?></p>
                    </div>
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">3</span>
                            <?= t('onboarding.repos.block3.title') ?>
                        </div>
                        <p><?= t('onboarding.repos.block3.body') ?></p>
                    </div>
                </div>

                <a href="<?= routePath('/repos.php') ?>" class="onboarding-cta-link" onclick="window.closeOnboardingWizard()">
                    <?= t('onboarding.repos.cta') ?>
                </a>
            </div>

            <!-- ── Step 2: Backups ─────────────────────────────────── -->
            <div class="onboarding-panel" data-panel="2" hidden>
                <div class="onboarding-panel-icon">📦</div>
                <h2 class="onboarding-panel-title"><?= t('onboarding.backups.title') ?></h2>
                <p class="onboarding-panel-lead">
                    <?= t('onboarding.backups.lead') ?>
                </p>

                <div class="onboarding-info-blocks">
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">1</span>
                            <?= t('onboarding.backups.block1.title') ?>
                        </div>
                        <p><?= t('onboarding.backups.block1.body') ?></p>
                    </div>
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">2</span>
                            <?= t('onboarding.backups.block2.title') ?>
                        </div>
                        <p><?= t('onboarding.backups.block2.body') ?></p>
                    </div>
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">3</span>
                            <?= t('onboarding.backups.block3.title') ?>
                        </div>
                        <p><?= t('onboarding.backups.block3.body') ?></p>
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a href="<?= routePath('/quick_backup.php') ?>" class="onboarding-cta-link" onclick="window.closeOnboardingWizard()">
                        <?= t('onboarding.backups.cta.quick') ?>
                    </a>
                    <a href="<?= routePath('/backup_jobs.php') ?>" class="onboarding-cta-link" onclick="window.closeOnboardingWizard()" style="opacity:.8">
                        <?= t('onboarding.backups.cta.manual_jobs') ?>
                    </a>
                </div>
            </div>

            <!-- ── Step 3: Restore ─────────────────────────────────── -->
            <div class="onboarding-panel" data-panel="3" hidden>
                <div class="onboarding-panel-icon">🔄</div>
                <h2 class="onboarding-panel-title"><?= t('onboarding.restore.title') ?></h2>
                <p class="onboarding-panel-lead">
                    <?= t('onboarding.restore.lead') ?>
                </p>

                <div class="onboarding-info-blocks">
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">1</span>
                            <?= t('onboarding.restore.block1.title') ?>
                        </div>
                        <p><?= t('onboarding.restore.block1.body') ?></p>
                    </div>
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">2</span>
                            <?= t('onboarding.restore.block2.title') ?>
                        </div>
                        <p><?= t('onboarding.restore.block2.body') ?></p>
                    </div>
                    <div class="onboarding-info-block">
                        <div class="onboarding-info-block-header">
                            <span class="onboarding-info-block-num">3</span>
                            <?= t('onboarding.restore.block3.title') ?>
                        </div>
                        <p><?= t('onboarding.restore.block3.body') ?></p>
                    </div>
                </div>

                <a href="<?= routePath('/explore.php') ?>" class="onboarding-cta-link" onclick="window.closeOnboardingWizard()">
                    <?= t('onboarding.restore.cta') ?>
                </a>
            </div>

            <!-- ── Step 4: Security ────────────────────────────────── -->
            <div class="onboarding-panel" data-panel="4" hidden>
                <div class="onboarding-panel-icon">🔐</div>
                <h2 class="onboarding-panel-title"><?= t('onboarding.security.title') ?></h2>
                <p class="onboarding-panel-lead">
                    <?= t('onboarding.security.lead', ['app' => h(AppConfig::appName())]) ?>
                </p>

                <div class="onboarding-role-grid">
                    <div class="onboarding-role-card">
                        <div class="onboarding-role-badge role-admin">Admin</div>
                        <div class="onboarding-role-desc"><?= t('onboarding.security.role.admin') ?></div>
                    </div>
                    <div class="onboarding-role-card">
                        <div class="onboarding-role-badge role-operator">Operator</div>
                        <div class="onboarding-role-desc"><?= t('onboarding.security.role.operator') ?></div>
                    </div>
                    <div class="onboarding-role-card">
                        <div class="onboarding-role-badge role-viewer">Viewer</div>
                        <div class="onboarding-role-desc"><?= t('onboarding.security.role.viewer') ?></div>
                    </div>
                    <div class="onboarding-role-card">
                        <div class="onboarding-role-badge role-api">API</div>
                        <div class="onboarding-role-desc"><?= t('onboarding.security.role.api') ?></div>
                    </div>
                </div>

                <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap">
                    <a href="<?= routePath('/settings.php') ?>" class="onboarding-cta-link" onclick="window.closeOnboardingWizard()">
                        <?= t('onboarding.security.cta.settings') ?>
                    </a>
                    <a href="<?= routePath('/users.php') ?>" class="onboarding-cta-link onboarding-cta-link--secondary" onclick="window.closeOnboardingWizard()">
                        <?= t('onboarding.security.cta.users') ?>
                    </a>
                </div>
            </div>

            <!-- ── Step 5: Ready ───────────────────────────────────── -->
            <div class="onboarding-panel" data-panel="5" hidden>
                <div class="onboarding-panel-icon">✅</div>
                <h2 class="onboarding-panel-title"><?= t('onboarding.ready.title') ?></h2>
                <p class="onboarding-panel-lead">
                    <?= t('onboarding.ready.lead', ['app' => h(AppConfig::appName())]) ?>
                </p>

                <div class="onboarding-checklist">
                    <a href="<?= routePath('/repos.php') ?>" class="onboarding-checklist-item" onclick="window.closeOnboardingWizard()">
                        <span class="onboarding-checklist-icon">🗄️</span>
                        <div>
                            <div class="onboarding-checklist-title"><?= t('onboarding.ready.checklist.repo.title') ?></div>
                            <div class="onboarding-checklist-desc"><?= t('onboarding.ready.checklist.repo.desc') ?></div>
                        </div>
                        <span class="onboarding-checklist-arrow">→</span>
                    </a>
                    <a href="<?= routePath('/backup_jobs.php') ?>" class="onboarding-checklist-item" onclick="window.closeOnboardingWizard()">
                        <span class="onboarding-checklist-icon">📦</span>
                        <div>
                            <div class="onboarding-checklist-title"><?= t('onboarding.ready.checklist.backup.title') ?></div>
                            <div class="onboarding-checklist-desc"><?= t('onboarding.ready.checklist.backup.desc') ?></div>
                        </div>
                        <span class="onboarding-checklist-arrow">→</span>
                    </a>
                    <a href="<?= routePath('/settings.php') ?>#notifications" class="onboarding-checklist-item" onclick="window.closeOnboardingWizard()">
                        <span class="onboarding-checklist-icon">🔔</span>
                        <div>
                            <div class="onboarding-checklist-title"><?= t('onboarding.ready.checklist.notifications.title') ?></div>
                            <div class="onboarding-checklist-desc"><?= t('onboarding.ready.checklist.notifications.desc') ?></div>
                        </div>
                        <span class="onboarding-checklist-arrow">→</span>
                    </a>
                </div>

                <div class="onboarding-docs-banner">
                    <div class="onboarding-docs-icon">📖</div>
                    <div>
                        <div class="onboarding-docs-title"><?= t('onboarding.ready.docs.title') ?></div>
                        <div class="onboarding-docs-desc"><?= t('onboarding.ready.docs.desc') ?></div>
                    </div>
                    <a href="https://restic.net/docs/" target="_blank" rel="noopener" class="btn btn-primary" style="white-space:nowrap">
                        <?= t('onboarding.ready.docs.cta') ?>
                    </a>
                </div>
            </div>

            <!-- Pied du wizard -->
            <div class="onboarding-footer">
                <button type="button" id="onboarding-btn-prev" class="btn" onclick="window.onboardingPrev()" style="display:none">
                    <?= t('onboarding.nav.prev') ?>
                </button>
                <div style="flex:1"></div>
                <button type="button" id="onboarding-btn-next" class="btn btn-primary" onclick="window.onboardingNext()">
                    <?= t('onboarding.nav.next') ?>
                </button>
                <button type="button" id="onboarding-btn-finish" class="btn btn-primary" onclick="window.closeOnboardingWizard()" style="display:none">
                    <?= t('onboarding.nav.start') ?>
                </button>
            </div>

        </div><!-- .onboarding-content -->
    </div><!-- .onboarding-shell -->
</div><!-- #onboarding-wizard -->
