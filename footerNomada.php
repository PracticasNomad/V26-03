<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Lógica inteligente para el botón central
$esHistorico = ($currentPage == 'nomada_historico.php');
$reservaIcon = $esHistorico ? 'fa-history' : 'fa-book';
$reservaText = $esHistorico ? 'Histórico' : 'Reservas';
$reservaLink = $esHistorico ? 'nomada_historico.php' : 'nomada_reservas.php';
$reservaActive = ($currentPage == 'nomada_reservas.php' || $currentPage == 'nomada_historico.php') ? 'active-icon' : '';
?>
<style>
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
        color: #4bba18 !important; /* Tu verde Nomadapp */
    }

    .active-icon {
        color: #4bba18 !important; 
    }

    .footer-text {
        font-weight: 700;
        font-size: 1rem;
        margin-top: 2px;
    }
    
    .footer-icon-size {
        font-size: 1.5rem;
        margin-bottom: 2px;
    }

     /* Adaptación para móviles: ocultar texto */
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
        
        <a href="nomada_explorar.php" class="col text-center footer-item">
            <div class="icon-container <?php echo $currentPage == 'nomada_explorar.php' ? 'active-icon' : ''; ?>">
                <i class="fas fa-search-location footer-icon-size"></i>
                <div class="footer-text">Explorar</div>
            </div>
        </a>
        
        <a href="<?php echo $reservaLink; ?>" class="col text-center footer-item">
            <div class="icon-container <?php echo $reservaActive; ?>">
                <i class="fas <?php echo $reservaIcon; ?> footer-icon-size"></i>
                <div class="footer-text"><?php echo $reservaText; ?></div>
            </div>
        </a>
        
        <a href="nomada_perfil.php" class="col text-center footer-item">
            <div class="icon-container <?php echo $currentPage == 'nomada_perfil.php' ? 'active-icon' : ''; ?>">
                <i class="fas fa-user-tie footer-icon-size"></i>
                <div class="footer-text">Perfil</div>
            </div>
        </a>

    </div>
</div>