<?php
$currentPage    = basename($_SERVER['PHP_SELF']);
// Número de códigos postales cubiertos por las gestoras, si no llega lo ponemos a 0.
$postalCoverage = $postalCoverage ?? 0;

/**
 * CASO 1: Header exclusivo dashboard
 */
if ($currentPage === 'dashboard.php') {
    ?>
    <style>
        .header-admin {
            background: linear-gradient(135deg, #1f2933 0%, #364152 100%);
            color: #ffffff;
            padding: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>

    <header class="header-admin mb-4">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <img src="../img/logo.jpg"
                         alt="Nomadapp"
                         style="height: 45px; border-radius: 10px;">
                    <div>
                        <h4 class="mb-0 fw-bold">Dashboard TheNomadapp</h4>
                        <small class="text-white-50">Estadísticas</small>
                    </div>
                </div>
                <a href="cerrarSesion.php"
                   class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-sign-out-alt me-1"></i> Salir
                </a>
            </div>
        </div>
    </header>
    <?php
    return;
}

/**
 * CASO 2: Header exclusivo gestoras
 */
if ($currentPage === 'verGestoras.php') {
    ?>
    <style>
        .hero {
            background: linear-gradient(135deg, #962d22 0%, #c44536 52%, #df786c 100%);
            color: #ffffff;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24);
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 1.85rem;
            font-weight: 800;
            margin: 0 0 6px;
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .info-hint-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.48);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            cursor: pointer;
            transition: 0.2s ease;
            font-size: 0.92rem;
            font-weight: 800;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .info-hint-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: translateY(-1px);
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .hero-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-weight: 800;
            padding: 12px 18px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
            text-decoration: none;
            transition: 0.2s ease;
        }

        .btn-hero-primary:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.24);
            transform: translateY(-1px);
        }
    </style>

    <section class="hero mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
            <div>
                <div class="title-row">
                    <h1 class="hero-title">
                        <i class="fas fa-user-tie me-2"></i>
                        Vista Global de Gestoras
                    </h1>
                    <span class="info-hint-btn"
                          data-bs-toggle="tooltip"
                          data-bs-placement="right"
                          title="Lista, filtra y administra las gestiones activas. Cada tarjeta resume la zona asignada por código postal y permite editar datos, reasignar cobertura, ver estadísticas y eliminar el perfil.">
                        ?
                    </span>
                </div>

                <div class="hero-pill">
                    <i class="fas fa-map-marked-alt"></i>
                    Cobertura activa en <?php echo (int) $postalCoverage; ?> códigos postales
                </div>
            </div>

            <div class="hero-actions">
                <button type="button" class="btn btn-hero-primary" id="openInviteGestora">
                    <i class="fas fa-paper-plane"></i>
                    Invitar gestora
                </button>
            </div>
        </div>
    </section>
    <?php
    return;
}

/**
 * CASO 3: resto de páginas admin
 * Usan el hero rojo genérico
 * (Gestión de Anfitriones, Establecimientos, Validaciones, Suscripciones, Tu perfil…)
*/

// Título, icono, subtitulo y un badge 
$title = 'Panel de Control';
$icon = 'fa-shield-alt';
$heroSubtitle = '';
$heroBadge = '';

switch ($currentPage) {
    case 'verAnfitriones.php':
        $title = 'Todos los Anfitriones';
        $icon = 'fa-users';
        $heroSubtitle = 'Gestión de anfitriones';
        break;

    case 'verEstablecimientos.php':
        $title = 'Gestión Global de Establecimientos';
        $icon = 'fa-building';
        $heroSubtitle = 'Vista global de todos los establecimientos';
        break;

    case 'verValidar.php':
    case 'validar.php':
        $title = 'Gestión Global de Validaciones';
        $icon = 'fa-check-circle';
        $heroBadge = '';
        break;

    case 'editarSuscripciones.php':
        $title = 'Gestión de Precios y Suscripciones';
        $icon = 'fa-tags';
        $heroSubtitle = '';
        break;

    case 'tuPerfil.php':
        $title = 'Tu Perfil';
        $icon = 'fa-user-shield';
        $heroBadge = 'Acceso Administrador';
        break;

    default:
        // Panel genérico
        $title = 'Panel de Control';
        $icon = 'fa-shield-alt';
        $heroSubtitle = '';
        $heroBadge = '';
        break;
}
?>

<style>
    :root {
        --admin-accent:      #dc3545;
        --admin-accent-dark: #8c1c13;
        --admin-accent-mid:  #c44536;
    }

    /* Contenedor hero genérico */
    .page-hero {
        max-width: 1400px;
        margin: 1.5rem auto;
        padding: 0 15px;
    }

    .page-hero-inner {
        border-radius: 20px;
        background: linear-gradient(135deg, var(--admin-accent-dark) 0%, var(--admin-accent-mid) 52%, #df786c 100%);
        color: #ffffff;
        padding: 1.5rem 2rem;
        box-shadow: 0 18px 40px rgba(140, 28, 19, 0.24);
        border: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 100px;
    }

    .page-hero-title {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 0;
    }

    .page-hero-subtitle {
        margin-top: 4px;
        font-size: 0.96rem;
        opacity: 0.9;
    }

    .page-hero-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #111827;
        color: #ffffff;
        padding: 4px 12px;
        font-size: 0.8rem;
        font-weight: 700;
        margin-top: 8px;
    }

    @media (max-width: 768px) {
        .page-hero-inner {
            flex-direction: column;
            text-align: center;
            gap: 15px;
            padding: 1.2rem;
        }

        .page-hero-title {
            font-size: 1.4rem;
        }
    }
</style>

<header class="page-hero">
    <div class="page-hero-inner">
        <div>
            <h1 class="page-hero-title">
                <i class="fas <?php echo $icon; ?> me-2"></i>
                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
            </h1>

            <?php if ($heroSubtitle !== ''): ?>
                <div class="page-hero-subtitle">
                    <?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($heroBadge !== ''): ?>
                <div class="page-hero-badge mt-2">
                    <?php echo htmlspecialchars($heroBadge, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>