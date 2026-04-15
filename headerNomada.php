<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Configuraciones por defecto
$title = "Panel Nómada";
$icon = "fa-user";
$showTabs = false;

// Detectar página actual para setear el título e icono dinámicamente
switch ($currentPage) {
    case 'nomada_reservas.php':
        $title = "Tus Reservas";
        $icon = "fa-book-open";
        $showTabs = true;
        break;
    case 'nomada_historico.php':
        $title = "Tu Historial";
        $icon = "fa-history";
        $showTabs = true;
        break;
    case 'nomada_explorar.php':
        $title = "Explorar Espacios";
        $icon = "fa-search-location";
        break;
    case 'nomada_perfil.php':
    case 'tuPerfil.php': // Por si acaso usas este nombre
        $title = "Tu Perfil";
        $icon = "fa-user-tie";
        break;
}
?>
<style>
    :root {
        /* Paleta Verde Nómada */
        --nomada-green: #07ba16;
        --nomada-green-dark: #108303;
        --nomada-green-light: #BDE742;
        --nomada-green-soft: #f4fae8;
    }

    .page-hero {
        max-width: 100%;
        margin: 1.5rem 0 1.5rem;
        padding: 0;
        box-sizing: border-box;
    }

    .page-hero-inner {
        border-radius: 15px;
        background: linear-gradient(135deg, var(--nomada-green-dark) 0%, var(--nomada-green) 62%, var(--nomada-green-light) 100%);
        color: #ffffff;
        padding: 1.2rem 1.8rem;
        box-shadow: 0 10px 25px rgba(129, 186, 24, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        justify-content: space-between; 
        align-items: center;
        min-height: 85px; 
    }

    .page-hero-title {
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: 0.2px;
        margin: 0;
        color: white;
    }

    .hero-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-main { overflow-x: hidden; }
    .header-tabs { overflow: hidden; border-radius: 12px; background-color: white; margin-bottom: 1rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
    
    .header-tab { font-weight: bold; transition: all 0.3s ease; height: 100%; cursor: pointer; color: var(--nomada-green-dark); background-color: white; border-bottom: 3px solid transparent; }
    .header-tab-active { color: white; background-color: var(--nomada-green); border-color: var(--nomada-green-dark); }
    .header-tab-link { text-decoration: none; display: block; height: 100%; }
    .header-tab:hover:not(.header-tab-active) { background-color: var(--nomada-green-soft); color: var(--nomada-green-dark); border-bottom: 3px solid var(--nomada-green); }

    @media (max-width: 576px) {
        .page-hero-inner {
            flex-direction: column;
            text-align: center;
            gap: 15px;
            min-height: auto; 
        }
    }
</style>

<header class="page-hero">
    <div class="page-hero-inner">
        <div class="hero-title-row">
            <h3 class="page-hero-title"><i class="fas <?php echo $icon; ?> me-2"></i><?php echo $title; ?></h3>
        </div>
    </div>
</header>

<?php if ($showTabs): ?>
<div class="row py-2 mb-3 header-main">
    <div class="col-12">
        <div class="header-tabs">
            <div class="row g-0">
                <div class="col-6">
                    <?php if ($currentPage == 'nomada_reservas.php'): ?>
                        <div class="header-tab header-tab-active py-3 text-center rounded-start">
                            <i class="fas fa-calendar-check me-2"></i>RESERVAS
                        </div>
                    <?php else: ?>
                        <a href="nomada_reservas.php" class="header-tab-link">
                            <div class="header-tab py-3 text-center rounded-start">
                                <i class="fas fa-calendar-check me-2"></i>RESERVAS
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <?php if ($currentPage == 'nomada_historico.php'): ?>
                        <div class="header-tab header-tab-active py-3 text-center rounded-end">
                            <i class="fas fa-history me-2"></i>HISTÓRICO
                        </div>
                    <?php else: ?>
                        <a href="nomada_historico.php" class="header-tab-link">
                            <div class="header-tab py-3 text-center rounded-end">
                                <i class="fas fa-history me-2"></i>HISTÓRICO
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>