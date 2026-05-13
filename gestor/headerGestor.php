<?php
$currentPage    = basename($_SERVER['PHP_SELF']);

// Título, icono, subtítulo, badge y texto de ayuda (tooltip) por defecto
$title = 'Panel de Gestora';
$icon = 'fa-briefcase';
$heroSubtitle = '';
$heroBadge = '';
$heroInfoTooltip = '';

switch ($currentPage) {
    case 'Anfitriones.php':
        $title = 'Tus Anfitriones';
        $icon = 'fa-users';
        $heroSubtitle = 'Gestión de anfitriones asignados a tu código postal';
        $heroInfoTooltip = 'Aquí puedes ver y gestionar todos los anfitriones que operan dentro de tu zona de influencia.';
        break;

    case 'verValidar.php':
        $title = 'Validaciones Pendientes';
        $icon = 'fa-check-circle';
        $heroSubtitle = 'Revisión y aprobación de nuevos espacios';
        $heroInfoTooltip = 'Revisa la documentación y características de los nuevos espacios antes de hacerlos públicos en la plataforma.';
        break;

    case 'verReservas.php':
        $title = 'Gestión de Reservas';
        $icon = 'fa-book-open';
        $heroSubtitle = 'Historial y control de reservas en tu zona';
        $heroInfoTooltip = 'Visualiza todas las reservas pasadas y futuras de los establecimientos bajo tu gestión.';
        break;

    case 'verEstablecimientos.php':
        $title = 'Establecimientos';
        $icon = 'fa-building';
        $heroSubtitle = 'Vista de todos los establecimientos de tu área';
        $heroInfoTooltip = 'Controla imagen, estado, ubicación y datos clave de tus negocios desde un solo panel.';
        break;

    case 'verEspacios.php':
        $title = 'Espacios de Trabajo';
        $icon = 'fa-chair';
        $heroSubtitle = 'Administración de espacios disponibles';
        $heroInfoTooltip = 'Gestiona los detalles específicos, aforos y disponibilidades de cada espacio individual.';
        break;

    case 'tuPerfil.php':
        $title = 'Tu Perfil';
        $icon = 'fa-user-tie';
        $heroBadge = 'Perfil Gestora';
        $heroInfoTooltip = 'Actualiza tu información personal, foto de perfil y contraseña de acceso.';
        break;
        
    case 'tusHistorias.php':
        $title = 'Histórico de Reservas';
        $icon = 'fa-history';
        $heroSubtitle = 'Historial completo de reservas pasadas y canceladas';
        $heroInfoTooltip = 'Aquí puedes consultar las reservas que ya han concluido o han sido canceladas en tu zona.';
        break;
}
?>

<style>
    :root {
        /* Paleta Azul para Gestora (basado en tu #007bff) */
        --gestor-accent:      #007bff;
        --gestor-accent-dark: #0056b3;
        --gestor-accent-mid:  #0069d9;
    }

    /* Contenedor hero genérico */
    .page-hero {
        max-width: 1400px;
        margin: 1.5rem auto;
        padding: 0 15px;
    }

    .page-hero-inner {
        border-radius: 20px;
        /* Degradado azul para el rol Gestora */
        background: linear-gradient(135deg, var(--gestor-accent-dark) 0%, var(--gestor-accent-mid) 52%, #4da3ff 100%);
        color: #ffffff;
        padding: 1.5rem 2rem;
        box-shadow: 0 18px 40px rgba(0, 123, 255, 0.24); /* Sombra azulada */
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
        display: flex;
        align-items: center;
        gap: 12px;
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

    /* Estilos del botón de información (?) */
    .info-hint-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, 0.45);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: rgba(255, 255, 255, 0.12);
        cursor: pointer;
        transition: 0.2s ease;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .info-hint-btn:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-1px);
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
            justify-content: center;
        }
    }
</style>

<header class="page-hero">
    <div class="page-hero-inner">
        <div>
            <h1 class="page-hero-title">
                <span><i class="fas <?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
                
                <?php if ($heroInfoTooltip !== ''): ?>
                    <span class="info-hint-btn" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($heroInfoTooltip, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fas fa-info" style="font-size: 0.85rem;"></i>
                    </span>
                <?php endif; ?>
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