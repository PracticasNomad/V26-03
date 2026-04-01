<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Configuraciones por defecto
$title = "Panel Anfitrión";
$icon = "fa-user";
$showTabs = false;

// Detectar página actual para setear el título e icono dinámicamente
switch ($currentPage) {
    case 'tusReservas.php':
        $title = "Tus Reservas";
        $icon = "fa-book-open";
        $showTabs = true;
        break;
    case 'tusHistorias.php':
        $title = "Tu Historial";
        $icon = "fa-history";
        $showTabs = true;
        break;
    case 'tusEspacios.php':
        $title = "Tus Espacios";
        $icon = "fa-chair";
        break;
    case 'verEstablecimientos.php':
        $title = "Tus Establecimientos";
        $icon = "fa-building";
        break;
    case 'tuPerfil.php':
        $title = "Tu Perfil";
        $icon = "fa-user-tie";
        break;
}
?>
<style>
    :root {
        --host-accent: #10bfeb;
        --host-accent-dark: #0a95b7;
        --host-accent-soft: #e7f8fd;
        --header-active-green: #81ba18;
        --header-active-green-dark: #6d9e14;
    }

    .page-hero {
        max-width: 100%;
        margin: 1.5rem 0 1.5rem;
        padding: 0;
        box-sizing: border-box;
    }

    .page-hero-inner {
        border-radius: 15px;
        background: linear-gradient(135deg, var(--host-accent-dark) 0%, var(--host-accent) 62%, #51cfee 100%);
        color: #ffffff;
        padding: 1.2rem 1.8rem;
        box-shadow: 0 10px 25px rgba(16, 191, 235, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        justify-content: space-between; 
        align-items: center;
        min-height: 85px; /* AQUÍ ESTÁ LA MAGIA PARA IGUALAR ALTURAS */
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

    .btn-hero-action {
        background-color: white;
        color: var(--host-accent-dark);
        border: none;
        font-weight: 800;
        padding: 10px 20px;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-hero-action:hover:not(.disabled-style) {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        color: var(--host-accent-dark);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }
    
    .disabled-style {
        background-color: rgba(255,255,255,0.6) !important;
        color: #6c757d !important;
        cursor: not-allowed;
        box-shadow: none !important;
    }

    .header-main { overflow-x: hidden; }
    .header-tabs { overflow: hidden; border-radius: 12px; background-color: white; margin-bottom: 1rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
    .header-tab { font-weight: bold; transition: all 0.3s ease; height: 100%; cursor: pointer; color: var(--host-accent); background-color: white; border-bottom: 3px solid transparent; }
    .header-tab-active { color: white; background-color: var(--header-active-green); border-color: var(--header-active-green-dark); }
    .header-tab-link { text-decoration: none; display: block; height: 100%; }
    .header-tab:hover:not(.header-tab-active) { background-color: var(--host-accent-soft); color: var(--host-accent-dark); border-bottom: 3px solid var(--host-accent); }

    @media (max-width: 576px) {
        .page-hero-inner {
            flex-direction: column;
            text-align: center;
            gap: 15px;
            min-height: auto; /* En móvil dejamos que crezca si se apilan */
        }
    }
</style>

<header class="page-hero">
    <div class="page-hero-inner">
        <div class="hero-title-row">
            <h3 class="page-hero-title"><i class="fas <?php echo $icon; ?> me-2"></i><?php echo $title; ?></h3>
        </div>
        
        <?php if (isset($heroActionButton)): ?>
            <div>
                <?php echo $heroActionButton; ?>
            </div>
        <?php endif; ?>
    </div>
</header>

<?php if ($showTabs): ?>
<div class="row py-2 mb-3 header-main">
    <div class="col-12">
        <div class="header-tabs">
            <div class="row g-0">
                <div class="col-6">
                    <?php if ($currentPage == 'tusReservas.php'): ?>
                        <div class="header-tab header-tab-active py-3 text-center rounded-start">
                            <i class="fas fa-calendar-check me-2"></i>RESERVAS
                        </div>
                    <?php else: ?>
                        <a href="tusReservas.php" class="header-tab-link">
                            <div class="header-tab py-3 text-center rounded-start">
                                <i class="fas fa-calendar-check me-2"></i>RESERVAS
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <?php if ($currentPage == 'tusHistorias.php'): ?>
                        <div class="header-tab header-tab-active py-3 text-center rounded-end">
                            <i class="fas fa-history me-2"></i>HISTÓRICO
                        </div>
                    <?php else: ?>
                        <a href="tusHistorias.php" class="header-tab-link">
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