<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$role = function_exists('current_user_role') ? current_user_role() : 'admin';

function menu_active($pages, $currentPage) {
    return in_array($currentPage, (array)$pages, true) ? 'active' : '';
}
?>

<aside class="app-menu">
    <div class="menu-brand">Admin Apartamente</div>

    <nav class="menu-nav">
        <a class="<?php echo menu_active('dashboard.php', $currentPage); ?>" href="dashboard.php">Dashboard</a>

        <?php if ($role !== 'chirias') { ?>
            <div class="menu-group">
                <span class="menu-section-title">Administrare</span>
                <a class="<?php echo menu_active(['index.php', 'adauga_apartament.php'], $currentPage); ?>" href="index.php">Apartamente</a>
                <a class="<?php echo menu_active(['chiriasi.php', 'adauga_chirias.php'], $currentPage); ?>" href="chiriasi.php">Chiriași</a>
                <a class="<?php echo menu_active('documente.php', $currentPage); ?>" href="documente.php">Documente</a>
                <a class="<?php echo menu_active(['contracte.php', 'contract_chirias.php'], $currentPage); ?>" href="contracte.php">Contracte</a>
            </div>
        <?php } ?>

        <div class="menu-group">
            <span class="menu-section-title">Financiar</span>
            <a class="<?php echo menu_active(['facturi.php', 'adauga_factura.php'], $currentPage); ?>" href="facturi.php">Facturi</a>
            <a class="<?php echo menu_active('plati.php', $currentPage); ?>" href="plati.php">Plăți</a>
        </div>

        <div class="menu-group">
            <span class="menu-section-title">Service</span>
            <a class="<?php echo menu_active(['mentenanta.php', 'adauga_mentenanta.php'], $currentPage); ?>" href="mentenanta.php">Mentenanță</a>
        </div>

        <div class="menu-group">
            <span class="menu-section-title">Sistem</span>
            <?php if ($role !== 'chirias') { ?>
                <a class="<?php echo menu_active('rapoarte.php', $currentPage); ?>" href="rapoarte.php">Rapoarte</a>
            <?php } ?>
            <?php if ($role === 'admin') { ?>
                <a class="<?php echo menu_active('utilizatori.php', $currentPage); ?>" href="utilizatori.php">Utilizatori</a>
            <?php } ?>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
</aside>
