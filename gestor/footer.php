<?php $paginaActual = basename($_SERVER['PHP_SELF']); ?>

<style>
    /* Estado activo unificado para el footer compartido */
    .footer-container .footer-item.active,
    .footer-container .footer-item.active a,
    .footer-container .footer-item.active a:visited,
    .footer-container .footer-item.active a:active {
        color: #00B7CF !important;
    }

    .footer-container .footer-item.active .icon-container i {
        color: #007bff !important;
    }
</style>

<div class="container-fluid footer mt-5 p-3">
    <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
        <label for="anf" id="lbl_anf" class="col-2 text-center footer-item <?php if ($paginaActual == 'Anfitriones.php') echo 'active'; ?>">
            <div class="row">
                <a href="Anfitriones.php">
                    <div class="col-12 icon-container">
                        <i class="h2 fas fa-users p-1 m-0"></i>
                        <div>Anfitriones</div>
                    </div>
                </a>
            </div>
        </label>

        <label for="val" id="lbl_val" class="col-2 text-center footer-item <?php if ($paginaActual == 'verValidar.php') echo 'active'; ?>">
            <div class="row">
                <a href="verValidar.php">
                    <div class="col-12 icon-container">
                        <i class="h2 fas fa-check-circle p-1 m-0"></i>
                        <div>Validar</div>
                    </div>
                </a>
            </div>
        </label>

        <label for="res" id="lbl_res" class="col-2 text-center footer-item <?php if ($paginaActual == 'verReservas.php') echo 'active'; ?>">
            <div class="row">
                <a href="verReservas.php">
                    <div class="col-12 icon-container">
                        <i class="h2 fas fa-book-open p-1 m-0"></i>
                        <div>Reservas</div>
                    </div>
                </a>
            </div>
        </label>

        <label for="his" id="lbl_his" class="col-2 text-center footer-item <?php if ($paginaActual == 'verEstablecimientos.php') echo 'active'; ?>">
            <div class="row">
                <a href="verEstablecimientos.php">
                    <div class="col-12 icon-container">
                        <i class="h2 fas fa-building p-1 m-0"></i>
                        <div>Establecimientos</div>
                    </div>
                </a>
            </div>
        </label>

        <label for="esp" id="lbl_esp" class="col-2 text-center footer-item <?php if ($paginaActual == 'verEspacios.php') echo 'active'; ?>">
            <div class="row">
                <a href="verEspacios.php">
                    <div class="col-12 icon-container">
                        <i class="h2 fas fa-chair p-1 m-0"></i>
                        <div>Espacios</div>
                    </div>
                </a>
            </div>
        </label>

        <label for="per" id="lbl_per" class="col-2 text-center footer-item <?php if ($paginaActual == 'tuPerfil.php') echo 'active'; ?>">
            <div class="row">
                <a href="tuPerfil.php">
                    <div class="col-12 icon-container">
                        <i class="h2 fas fa-user-tie p-1 m-0"></i>
                        <div>Perfil</div>
                    </div>
                </a>
            </div>
        </label>
    </div>
</div>