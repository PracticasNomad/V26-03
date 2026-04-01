<?php $paginaActual = basename($_SERVER['PHP_SELF']); ?>

<style>
    /* GESTOR / ADMIN FOOTER */
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
        margin: 0;
    }

    .footer-container {
        background-color: white;
        box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
        padding-top: 1px !important;
        padding-bottom: 1px !important;
        height: auto;
        margin: 0;
        width: 100%;
    }

    /* Usamos col para que se ajusten solos sin importar cuántos sean */
    .footer-item {
        padding: 8px 0;
        text-decoration: none !important;
        color: #6c757d;
        font-size: 0.8rem;
        display: block;
    }

    .icon-container {
        transition: transform 0.3s ease, color 0.3s ease;
        padding: 5px 0;
        color: #000000;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Hover: Animación al pasar el ratón */
    .footer-item:hover .icon-container {
        transform: translateY(-7px);
        color: #007bff; /* Azul Gestor */
    }

    /* Estado activo unificado */
    .footer-item.active .icon-container {
        color: #007bff !important;
    }
    
    .footer-item.active .footer-text {
        color: #007bff !important;
    }

    .footer-text {
        font-weight: 700;
        font-size: 1rem;
        margin-top: 2px;
        color: #6c757d;
    }

    .icon-container i {
        font-size: 1.5rem !important; /* Tamaño reducido de Admin */
        margin-bottom: 2px;
    }

    /* Adaptación para móviles: ocultar texto para que quepan 6 botones */
    @media (max-width: 400px) {
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

<div class="container-fluid footer p-0">
    <div class="row text-center fixed-bottom bg-white footer-container">
        
        <a href="Anfitriones.php" class="col text-center footer-item <?php if ($paginaActual == 'Anfitriones.php') echo 'active'; ?>">
            <div class="icon-container">
                <i class="fas fa-users"></i>
                <div class="footer-text">Anfitriones</div>
            </div>
        </a>

        <a href="verValidar.php" class="col text-center footer-item <?php if ($paginaActual == 'verValidar.php') echo 'active'; ?>">
            <div class="icon-container">
                <i class="fas fa-check-circle"></i>
                <div class="footer-text">Validar</div>
            </div>
        </a>

        <a href="verReservas.php" class="col text-center footer-item <?php if ($paginaActual == 'verReservas.php') echo 'active'; ?>">
            <div class="icon-container">
                <i class="fas fa-book-open"></i>
                <div class="footer-text">Reservas</div>
            </div>
        </a>

        <a href="verEstablecimientos.php" class="col text-center footer-item <?php if ($paginaActual == 'verEstablecimientos.php') echo 'active'; ?>">
            <div class="icon-container">
                <i class="fas fa-building"></i>
                <div class="footer-text">Establecimientos</div>
            </div>
        </a>

        <a href="verEspacios.php" class="col text-center footer-item <?php if ($paginaActual == 'verEspacios.php') echo 'active'; ?>">
            <div class="icon-container">
                <i class="fas fa-chair"></i>
                <div class="footer-text">Espacios</div>
            </div>
        </a>

        <a href="tuPerfil.php" class="col text-center footer-item <?php if ($paginaActual == 'tuPerfil.php') echo 'active'; ?>">
            <div class="icon-container">
                <i class="fas fa-user-tie"></i>
                <div class="footer-text">Perfil</div>
            </div>
        </a>

    </div>
</div>