<?php

require_once __DIR__ . '/app.php';

$currentRoute = site_request_path();
$pdo = site_db();

if ($pdo instanceof PDO && site_table_exists($pdo, 'cms_navigation_items')) {
    $navigationTree = cms_navigation_tree($pdo);
} else {
    $navigationTree = cms_navigation_blueprint_tree($pdo instanceof PDO ? $pdo : null);
}

$itemHasActiveChild = static function (array $item) use (&$itemHasActiveChild, $currentRoute) {
    foreach ($item['children'] ?? [] as $child) {
        if (cms_navigation_item_is_active($child, $currentRoute) || $itemHasActiveChild($child)) {
            return true;
        }
    }

    return false;
};

$renderNavigation = static function (array $items, $depth = 0) use (&$renderNavigation, $itemHasActiveChild, $currentRoute) {
    if (!$items) {
        return;
    }
    ?>
    <ul>
        <?php foreach ($items as $item): ?>
            <?php
            $isActive = cms_navigation_item_is_active($item, $currentRoute) || $itemHasActiveChild($item);
            $url = cms_navigation_item_url($item);
            $target = !empty($item['open_in_new_tab']) ? '_blank' : '_self';
            $anchorClass = $depth === 0 ? 'main_nav' . ($isActive ? ' activ' : '') : '';
            ?>
            <li>
                <a href="<?php echo site_escape($url); ?>"<?php echo $anchorClass !== '' ? ' class="' . site_escape($anchorClass) . '"' : ''; ?><?php echo $target === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo site_escape($item['label']); ?>
                    <?php if ($depth === 0): ?>
                        <span class="<?php echo $isActive ? 'activ' : 'in-active'; ?>"></span>
                        <span class="<?php echo $isActive ? 'activ' : 'in-active'; ?>"></span>
                        <span class="<?php echo $isActive ? 'activ' : 'in-active'; ?>"></span>
                        <span class="<?php echo $isActive ? 'activ' : 'in-active'; ?>"></span>
                    <?php endif; ?>
                </a>
                <?php if (!empty($item['children'])): ?>
                    <?php $renderNavigation($item['children'], $depth + 1); ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
};
?>
<header>
    <div class="wrap">
        <div class="logo">
            <a href="<?php echo site_escape(site_url('index')); ?>">ОСНОВНА ШКОЛА
                <span>Здравко Јовановић</span>
            </a>
        </div>
        <nav>
            <?php $renderNavigation($navigationTree); ?>
        </nav>
    </div>
</header>
