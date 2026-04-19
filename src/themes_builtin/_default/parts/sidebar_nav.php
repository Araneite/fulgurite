<?php
/**
 * Part by default : liste of items of navigation principale.
 * list is already filtered by permissions (see ThemeRenderer::buildNavigation).
 */
$nav = $ctx['nav'] ?? [];
$sections = [];
$groupCounts = [];

foreach ($nav as $item) {
    $groupKey = (string) ($item['group_key'] ?? '');
    if ($groupKey === '') {
        continue;
    }

    $countKey = (string) $item['section'] . '|' . $groupKey;
    $groupCounts[$countKey] = ($groupCounts[$countKey] ?? 0) + 1;
}

foreach ($nav as $item) {
    $section = (string) ($item['section'] ?? '');
    if (!isset($sections[$section])) {
        $sections[$section] = [];
    }

    $groupKey = (string) ($item['group_key'] ?? '');
    $countKey = $section . '|' . $groupKey;
    $hasDropdown = $groupKey !== '' && (($groupCounts[$countKey] ?? 0) > 1);

    if (!$hasDropdown) {
        $sections[$section][] = [
            'type' => 'item',
            'item' => $item,
        ];
        continue;
    }

    $groupIndex = null;
    foreach ($sections[$section] as $index => $entry) {
        if (($entry['type'] ?? '') === 'group' && ($entry['key'] ?? '') === $groupKey) {
            $groupIndex = $index;
            break;
        }
    }

    if ($groupIndex === null) {
        $sections[$section][] = [
            'type' => 'group',
            'key' => $groupKey,
            'label' => (string) ($item['group_label'] ?? ''),
            'icon' => (string) ($item['group_icon'] ?? ''),
            'active' => (bool) ($item['active'] ?? false),
            'items' => [$item],
        ];
        continue;
    }

    $sections[$section][$groupIndex]['items'][] = $item;
    $sections[$section][$groupIndex]['active'] = $sections[$section][$groupIndex]['active'] || !empty($item['active']);
}
?>
<nav class="nav" aria-label="<?= h(t('nav.main_aria')) ?>">
    <?php foreach ($sections as $sectionLabel => $entries): ?>
        <div class="nav-section"><?= h($sectionLabel) ?></div>
        <?php foreach ($entries as $entry): ?>
            <?php if (($entry['type'] ?? '') === 'group'): ?>
                <details class="nav-group <?= !empty($entry['active']) ? 'is-active' : '' ?>" <?= !empty($entry['active']) ? 'open' : '' ?>>
                    <summary class="nav-group-toggle">
                        <span class="nav-group-label">
                            <?= $entry['icon'] /* svg statique */ ?>
                            <span><?= h((string) $entry['label']) ?></span>
                        </span>
                        <svg class="nav-group-caret" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 6.5 8 10l4-3.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </summary>
                    <div class="nav-group-items">
                        <?php foreach (($entry['items'] ?? []) as $item): ?>
                            <a href="<?= h((string) $item['href']) ?>"
                               class="nav-item <?= !empty($item['active']) ? 'active' : '' ?>"
                               <?= !empty($item['active']) ? 'aria-current="page"' : '' ?>>
                                <?= $item['icon'] /* svg statique */ ?>
                                <?= h((string) $item['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php else: ?>
                <?php $item = $entry['item']; ?>
                <a href="<?= h((string) $item['href']) ?>"
                   class="nav-item <?= !empty($item['active']) ? 'active' : '' ?>"
                   <?= !empty($item['active']) ? 'aria-current="page"' : '' ?>>
                    <?= $item['icon'] /* svg statique */ ?>
                    <?= h((string) $item['label']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</nav>
