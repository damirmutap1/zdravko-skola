<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();

try {
    cms_ensure_settings_table($pdo);
} catch (Throwable $exception) {
    admin_flash('error', 'Podešavanja nisu dostupna. Uvezite migraciju database/migrations/20261107_homepage_display_settings.sql.');
}

$definitions = cms_homepage_display_setting_definitions();
$limitsByArea = cms_get_homepage_display_limits($pdo);
$form = [];

foreach ($definitions as $key => $definition) {
    $form[$key] = $limitsByArea[$definition['area_key']] ?? $definition['default'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_verify_csrf();

        if (($_POST['action'] ?? '') === 'migrate-legacy-homepage') {
            $result = cms_migrate_legacy_homepage_content($pdo, admin_current_user()['id']);
            if (!empty($result['already_migrated'])) {
                admin_flash('success', 'Početna strana već koristi objave iz baze.');
            } else {
                admin_flash(
                    'success',
                    'Preneto je ' . (int) $result['imported'] . ' starih stavki u bazu'
                    . ((int) $result['skipped'] > 0 ? '; preskočeno duplikata: ' . (int) $result['skipped'] . '.' : '.')
                );
            }
        } else {
            $form = array_merge($form, $_POST);
            cms_save_homepage_display_limits($pdo, $_POST, admin_current_user()['id']);
            admin_flash('success', 'Broj objava na početnoj strani je sačuvan.');
        }

        admin_redirect('homepage-settings.php');
    } catch (Throwable $exception) {
        admin_flash('error', $exception->getMessage());
    }
}

$migrationStatus = cms_get_legacy_homepage_migration_status($pdo);

$pageTitle = 'Prikaz početne strane';
$pageDescription = 'Odredite koliko poslednjih objava posetioci vide u glavnim sekcijama.';
$activeNav = 'homepage-settings';

require __DIR__ . '/layout-start.php';
?>
<?php if ($migrationStatus['has_legacy_section']): ?>
    <section class="card" style="margin-bottom: 22px; border-color: #e9d393; background: #fffaf0;">
        <span class="badge">Potrebna jednokratna migracija</span>
        <h2 style="margin: 12px 0 6px;">Postojeće stavke su još uvek hardkodovane</h2>
        <p class="muted">Prenesite ih u bazu da biste mogli da ih menjate, sakrivate i ograničavate kao sve nove objave. Uvoz prepoznaje duplikate, a stari HTML blok uklanja tek kada su sve stavke bezbedno obrađene.</p>
        <form method="post" style="margin-top: 16px;">
            <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
            <input type="hidden" name="action" value="migrate-legacy-homepage">
            <button type="submit">Prenesi postojeće stavke u bazu</button>
        </form>
    </section>
<?php elseif ($migrationStatus['uses_database_feed']): ?>
    <section class="card" style="margin-bottom: 22px;">
        <div class="actions" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="status-pill is-live">Prikaz iz baze je aktivan</span>
                <h2 style="margin: 12px 0 6px;">Sve postojeće stavke su u CMS-u</h2>
                <p class="muted" style="margin: 0;">
                    Važna obaveštenja: <?php echo (int) $migrationStatus['important_count']; ?> ·
                    Aktuelno: <?php echo (int) $migrationStatus['events_count']; ?>
                </p>
            </div>
            <a class="button button-secondary" href="content.php?page_id=<?php echo (int) $migrationStatus['page_id']; ?>">Uredi objave</a>
        </div>
    </section>
<?php endif; ?>

<section class="form-card" aria-labelledby="homepage-limits-title">
    <div style="margin-bottom: 20px;">
        <span class="badge">Početna strana</span>
        <h2 id="homepage-limits-title" style="margin: 10px 0 6px;">Broj poslednjih objava</h2>
        <p class="muted" style="margin: 0;">Prikazuju se najnovije objavljene stavke prema datumu objave. Starije stavke ostaju sačuvane u administraciji.</p>
    </div>

    <form method="post" data-guard-unsaved>
        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">

        <div class="form-grid two">
            <?php foreach ($definitions as $key => $definition): ?>
                <label for="<?php echo site_escape($key); ?>">
                    <?php echo site_escape($definition['label']); ?>
                    <input
                        id="<?php echo site_escape($key); ?>"
                        type="number"
                        name="<?php echo site_escape($key); ?>"
                        value="<?php echo site_escape((string) ($form[$key] ?? $definition['default'])); ?>"
                        min="<?php echo (int) $definition['min']; ?>"
                        max="<?php echo (int) $definition['max']; ?>"
                        step="1"
                        inputmode="numeric"
                        required
                    >
                    <span class="field-help">Dozvoljeno: <?php echo (int) $definition['min']; ?>–<?php echo (int) $definition['max']; ?> objava.</span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="actions" style="margin-top: 20px;">
            <button type="submit">Sačuvaj podešavanja</button>
            <a class="button button-secondary" href="dashboard.php">Odustani</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Kako funkcioniše</h2>
    <p class="muted" style="margin-bottom: 0;">Na primer, vrednost 5 prikazuje pet najnovijih objavljenih stavki. Kada dodate novu, ona se pojavljuje prva, a najstarija od trenutno prikazanih se automatski sklanja sa početne.</p>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>
