<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Lógica inteligente para el botón de Reservas / Histórico
$esHistorico = ($currentPage == 'tusHistorias.php');
$reservaIcon = $esHistorico ? 'fa-history' : 'fa-book-open';
$reservaText = $esHistorico ? 'Histórico' : 'Reservas';
$reservaLink = $esHistorico ? 'tusHistorias.php' : 'tusReservas.php';
$reservaActive = ($currentPage == 'tusReservas.php' || $currentPage == 'tusHistorias.php') ? 'active-icon' : '';
?>
<style>
    /* HOST FOOTER */
    .footer {
        width: 100%;
        left: 0;
        right: 0;
        -webkit-user-select: none;
        -ms-user-select: none;
        user-select: none;
        bottom: 0;
        font-size: 15px;
        text-align: center;
        position: fixed;
        z-index: 1000;
        margin: 0;
    }

    .footer-container {
        background-color: white;
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
        padding-top: 8px !important;
        padding-bottom: 8px !important;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        height: auto;
        margin: 0;
        width: 100%;
    }

    .footer-item {
        text-decoration: none !important;
        color: #6c757d !important;
        padding: 5px 0;
    }

    .icon-container {
        transition: transform 0.3s ease, color 0.3s ease;
        padding: 5px 0;
        color: #6c757d;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .footer-item:hover .icon-container {
        transform: translateY(-7px);
        color: #10bfeb !important; /* Azul/Cyan Anfitrión */
    }

    .active-icon {
        color: #10bfeb !important;
    }

    .footer-text {
        font-weight: 700;
        font-size: 1rem;
        margin-top: 2px;
    }
    
    .footer-icon-size {
        font-size: 1.8rem;
        margin-bottom: 2px;
    }
</style>

<div class="container-fluid footer p-0">
    <div class="row text-center fixed-bottom bg-white footer-container">
        
        <a href="<?php echo $reservaLink; ?>" class="col-3 text-center footer-item">
            <div class="icon-container <?php echo $reservaActive; ?>">
                <i class="fas <?php echo $reservaIcon; ?> footer-icon-size"></i>
                <div class="footer-text"><?php echo $reservaText; ?></div>
            </div>
        </a>

        <a href="verEstablecimientos.php" class="col-3 text-center footer-item">
            <div class="icon-container <?php echo $currentPage == 'verEstablecimientos.php' ? 'active-icon' : ''; ?>">
                <i class="fas fa-building footer-icon-size"></i>
                <div class="footer-text">Establecimientos</div>
            </div>
        </a>
        
        <a href="tusEspacios.php" class="col-3 text-center footer-item">
            <div class="icon-container <?php echo $currentPage == 'tusEspacios.php' ? 'active-icon' : ''; ?>">
                <i class="fas fa-chair footer-icon-size"></i>
                <div class="footer-text">Espacios</div>
            </div>
        </a>
        
        <a href="tuPerfil.php" class="col-3 text-center footer-item">
            <div class="icon-container <?php echo $currentPage == 'tuPerfil.php' ? 'active-icon' : ''; ?>">
                <i class="fas fa-user-tie footer-icon-size"></i>
                <div class="footer-text">Perfil</div>
            </div>
        </a>

    </div>
</div>