<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* ADMIN FOOTER */
    .footer {
        color: black;
        background-color: white;
        width: 100%;
        -webkit-user-select: none;
        user-select: none;
        bottom: 0;
        font-size: 15px;
        background: #E3E1E1;
        text-align: center;
        position: fixed;
        z-index: 1000;
    }

    .footer-container {
        background-color: white;
        box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
        padding-top: 1px !important;
        padding-bottom: 1px !important;
        height: auto;
    }

    /* Usamos col para que se ajusten solos sin importar cuántos sean */
    .footer-item {
        padding: 8px 0;
        text-decoration: none;
        color: black;
        font-size: 0.8rem;
    }

    .icon-container {
        transition: transform 0.3s ease, color 0.3s ease;
        padding: 5px 0;
        color: #000000;
    }

    .footer-item:hover .icon-container {
        transform: translateY(-7px);
        color: var(--primary-color, #dc3545);
    }

    /* Clase para iluminar el icono de la página activa */
    .active-icon {
        color: var(--primary-color, #dc3545) !important;
    }

    /* Adaptación para móviles: ocultar texto para que quepan 7 botones */
    @media (max-width: 768px) {
        .footer-text {
            display: none;
        }
        .icon-container i {
            font-size: 1.5rem !important;
            margin-bottom: 0 !important;
        }
        .footer-item {
            padding: 12px 0;
        }
    }
</style>

<div class="container-fluid footer mt-5 p-0">
    <div class="row text-center fixed-bottom bg-white pt-1 px-2 footer-container m-0">
        <a href="dashboard.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'dashboard.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-chart-line p-1 m-0"></i>
                    <div class="footer-text">Panel</div>
                </div>
            </div>
        </a>
        <a href="verGestores.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'verGestores.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-user-tie p-1 m-0"></i>
                    <div class="footer-text">Gestores</div>
                </div>
            </div>
        </a>
        <a href="verAnfitriones.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'verAnfitriones.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-users p-1 m-0"></i>
                    <div class="footer-text">Anfitriones</div>
                </div>
            </div>
        </a>
        <a href="verEstablecimientos.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'verEstablecimientos.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-building p-1 m-0"></i>
                    <div class="footer-text">Establecimientos</div>
                </div>
            </div>
        </a>
        <a href="verValidar.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'verValidar.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-check-circle p-1 m-0"></i>
                    <div class="footer-text">Validar</div>
                </div>
            </div>
        </a>
        <a href="editarSuscripciones.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'editarSuscripciones.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-tags p-1 m-0"></i>
                    <div class="footer-text">Planes</div>
                </div>
            </div>
        </a>
        <a href="tuPerfil.php" class="col footer-item">
            <div class="row">
                <div class="col-12 icon-container <?php echo $currentPage == 'tuPerfil.php' ? 'active-icon' : ''; ?>">
                    <i class="h3 fas fa-user-cog p-1 m-0"></i>
                    <div class="footer-text">Perfil</div>
                </div>
            </div>
        </a>
    </div>
</div>