<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$role = function_exists('current_user_role') ? current_user_role() : 'admin';

function menu_active($pages, $currentPage) {
    return in_array($currentPage, (array)$pages, true) ? 'active' : '';
}
?>

<aside class="app-menu">
    <div class="menu-brand">
        <span class="menu-brand-icon">&#127962;</span>
        <span class="menu-brand-text">Admin Apartamente</span>
    </div>

    <nav class="menu-nav">
        <a class="<?php echo menu_active('dashboard.php', $currentPage); ?>" href="dashboard.php" title="Dashboard">
            <span class="menu-icon">&#127968;</span>
            <span class="menu-label">Dashboard</span>
        </a>

        <?php if ($role !== 'chirias') { ?>
            <div class="menu-group">
                <span class="menu-section-title">Administrare</span>
                <a class="<?php echo menu_active(['index.php', 'adauga_apartament.php'], $currentPage); ?>" href="index.php" title="Apartamente">
                    <span class="menu-icon">&#127962;</span>
                    <span class="menu-label">Apartamente</span>
                </a>
                <a class="<?php echo menu_active(['chiriasi.php', 'adauga_chirias.php'], $currentPage); ?>" href="chiriasi.php" title="Chiria&#537;i">
                    <span class="menu-icon">&#128101;</span>
                    <span class="menu-label">Chiria&#537;i</span>
                </a>
                <a class="<?php echo menu_active('documente.php', $currentPage); ?>" href="documente.php" title="Documente">
                    <span class="menu-icon">&#128196;</span>
                    <span class="menu-label">Documente</span>
                </a>
                <a class="<?php echo menu_active(['contracte.php', 'contract_chirias.php'], $currentPage); ?>" href="contracte.php" title="Contracte">
                    <span class="menu-icon">&#128203;</span>
                    <span class="menu-label">Contracte</span>
                </a>
            </div>
        <?php } ?>

        <div class="menu-group">
            <span class="menu-section-title">Financiar</span>
            <a class="<?php echo menu_active(['facturi.php', 'adauga_factura.php'], $currentPage); ?>" href="facturi.php" title="Facturi">
                <span class="menu-icon">&#128176;</span>
                <span class="menu-label">Facturi</span>
            </a>
            <a class="<?php echo menu_active('plati.php', $currentPage); ?>" href="plati.php" title="Pl&#259;&#539;i">
                <span class="menu-icon">&#128179;</span>
                <span class="menu-label">Pl&#259;&#539;i</span>
            </a>
        </div>

        <div class="menu-group">
            <span class="menu-section-title">Service</span>
            <a class="<?php echo menu_active(['mentenanta.php', 'adauga_mentenanta.php'], $currentPage); ?>" href="mentenanta.php" title="Mentenan&#539;&#259;">
                <span class="menu-icon">&#128295;</span>
                <span class="menu-label">Mentenan&#539;&#259;</span>
            </a>
        </div>

        <div class="menu-group">
            <span class="menu-section-title">Sistem</span>
            <?php if ($role !== 'chirias') { ?>
                <a class="<?php echo menu_active('rapoarte.php', $currentPage); ?>" href="rapoarte.php" title="Rapoarte">
                    <span class="menu-icon">&#128202;</span>
                    <span class="menu-label">Rapoarte</span>
                </a>
            <?php } ?>
            <?php if ($role === 'admin') { ?>
                <a class="<?php echo menu_active('utilizatori.php', $currentPage); ?>" href="utilizatori.php" title="Utilizatori">
                    <span class="menu-icon">&#9881;&#65039;</span>
                    <span class="menu-label">Utilizatori</span>
                </a>
            <?php } ?>
            <a href="logout.php" title="Logout">
                <span class="menu-icon">&#128682;</span>
                <span class="menu-label">Logout</span>
            </a>
        </div>
    </nav>
</aside>
