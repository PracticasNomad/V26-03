<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="Nomadapp.ico" type="image/png">
    <title>Mis Espacios</title>
    <style>
        body {
			font-family: 'Nunito', sans-serif;
			background-color: #f8f9fa;
		}

		.contenedorLista {
			max-width: 900px;
			margin: 2rem auto;
			background-color: white;
			border-radius: 15px;
			box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
			padding: 1rem;
            position: relative;
		}

		.form-control {
			border-radius: 10px;
			padding: .75rem;
			border: 1px solid #ced4da;
			transition: border-color .3s;
		}

		.form-control:focus {
			border-color: #80bdff;
			box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
		}

		.btn-success {
			background-color: #28a745;
			border: none;
			font-weight: 600;
			padding: .75rem 2rem;
		}

		.btn-primary {
			background-color: #007bff;
			border: none;
			font-weight: 600;
			padding: .5rem 1rem;
		}

		.btn-danger {
			background-color: #dc3545;
			border: none;
			font-weight: 600;
			padding: .5rem 1rem;
		}

		.btn-info {
			background-color: #17a2b8;
			border: none;
			font-weight: 600;
			padding: .5rem 1rem;
			color: white;
		}

		.logo-container {
			background-color: #f8f9fa;
			border-radius: 50%;
			width: 120px;
			height: 120px;
			display: flex;
			justify-content: center;
			align-items: center;
			margin: 0 auto;
		}

		.establecimiento-header {
			background-color: #f8f9fa;
			padding: 10px;
			border-radius: 10px;
			margin-bottom: 15px;
			font-weight: bold;
		}

		.espacio-card {
			border: 1px solid #ced4da;
			border-radius: 10px;
			margin-bottom: 15px;
			box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
		}

		.espacio-header {
			padding: 15px;
			background-color: #f8f9fa;
			border-bottom: 1px solid #ced4da;
			border-radius: 10px 10px 0 0;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.horarios-container {
			padding: 15px;
			display: none;
		}

		.day-badge {
			display: inline-block;
			width: 30px;
			height: 30px;
			line-height: 30px;
			text-align: center;
			border-radius: 50%;
			margin-right: 5px;
			font-weight: bold;
		}

		.day-active {
			background-color: #28a745;
			color: white;
		}

		.day-inactive {
			background-color: #dc3545;
			color: white;
		}

		.horario-item {
			border: 1px solid #ced4da;
			border-radius: 8px;
			padding: 10px;
			margin-bottom: 10px;
		}

		.servicio-item {
			background-color: #f8f9fa;
			border-radius: 6px;
			padding: 8px;
			margin-top: 5px;
		}

		.no-espacios {
			text-align: center;
			padding: 20px;
			color: #6c757d;
		}

		.add-btn {
			position: absolute;
			top: 15px;
			right: 15px;
			width: 45px;
			height: 45px;
			border-radius: 50%;
			background-color: #28a745;
			color: white;
			display: flex;
			justify-content: center;
			align-items: center;
			font-size: 20px;
			box-shadow: 0 .3rem .5rem rgba(0, 0, 0, .15);
			text-decoration: none;
			z-index: 10;
		}

		.add-btn:hover {
			background-color: #218838;
			color: white;
		}

		.establecimiento-vacio {
			text-align: center;
			padding: 15px;
			background-color: #f8f9fa;
			border-radius: 10px;
			margin-bottom: 15px;
			color: #6c757d;
		}

		.toast-container {
			position: fixed;
			bottom: 20px;
			right: 20px;
			z-index: 1050;
		}

		.custom-toast {
			min-width: 250px;
		}

		@media (max-width: 768px) {
			.espacio-header {
				flex-direction: column;
				align-items: flex-start;
			}
			
			.btn-group {
				margin-top: 10px;
				width: 100%;
			}
			
			.btn-group .btn {
				flex: 1;
			}
		}

		.modal-confirm {
			font-family: 'Nunito', sans-serif;
		}

		.modal-confirm .modal-content {
			padding: 20px;
			border-radius: 15px;
		}

		.modal-confirm .modal-header {
			border-bottom: none;
			position: relative;
		}

		.modal-confirm .modal-title {
			text-align: center;
			font-size: 24px;
			font-weight: bold;
		}

		.modal-confirm .modal-body {
			color: #636363;
		}

		.modal-confirm .modal-footer {
			border-top: none;
			text-align: center;
			justify-content: center;
		}

		.modal-confirm .icon-box {
			width: 80px;
			height: 80px;
			margin: 0 auto;
			border-radius: 50%;
			z-index: 9;
			text-align: center;
			border: 3px solid #f15e5e;
		}

		.modal-confirm .icon-box i {
			color: #f15e5e;
			font-size: 46px;
			display: inline-block;
			margin-top: 13px;
		}

		.modal-confirm .btn-danger,
		.modal-confirm .btn-secondary {
			min-width: 100px;
		}


		.footer-container {
            background-color: white;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            padding-top: 1px !important;
            padding-bottom: 1px !important;
            height: auto;
        }

        .footer-item {
            padding: 8px 0;
        }

        .icon-container {
            transition: transform 0.3s ease;
            padding: 5px 0;
        }

        .footer-item:hover .icon-container {
            transform: translateY(-7px);
        }

		body {
            padding-bottom: 15%;
        }

        .footer {
            color: black;
            background-color: white;
            width: 100%;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
            bottom: 0;
            font-size: 15px;
            background: #E3E1E1;
            text-align: center;
            position: fixed;
        }

        .footer input[type="radio"] {
            display: none;
        }

        label,
        .form-check input[type=checkbox] {
            position: static;
        }

        a,
        a:visited,
        a:active {
            color: black;
            text-decoration: none;
        }

        #res:checked~#lbl_res,
        #his:checked~#lbl_his,
        #esp:checked~#lbl_esp,
        #per:checked~#lbl_per {
            color: #00B7CF !important;
        }

		#per:checked ~ #lbl_per .icon-container,
        #res:checked ~ #lbl_res .icon-container,
        #his:checked ~ #lbl_his .icon-container,
        #esp:checked ~ #lbl_esp .icon-container {
            color: #007bff;
        }

        /* New hover styles for "Establecimientos" and "Perfil" */
        #lbl_his:hover,
        #lbl_per:hover,
        #lbl_anf:hover,
        #lbl_val:hover,
        #lbl_res:hover,
        #lbl_esp:hover {
            color: #00B7CF !important; /* For the text */
        }

        #lbl_his:hover .icon-container,
        #lbl_per:hover .icon-container,
        #lbl_anf:hover .icon-container,
        #lbl_val:hover .icon-container,
        #lbl_res:hover .icon-container,
        #lbl_esp:hover .icon-container {
            color: #007bff; /* For the icon */
        }
        
        .header-container {
            position: relative;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="contenedorLista">
        <div class="header-container">
            <div class="col-12 text-center py-3 fw-bold h4">
                <p>Mis Espacios</p>
            </div>
            
            <a href="crearEspacio.php" class="add-btn">
                <i class="fas fa-plus"></i>
            </a>
        </div>
        
        <div class="col-12 text-center mb-3">
            <div class="logo-container">
                <img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento">
            </div>
        </div>

        <?php if($tieneError): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                Ha ocurrido un error al cargar los datos: <?php echo $establecimientos['error']; ?>
            </div>
        <?php elseif(empty($establecimientos)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                No tienes establecimientos registrados.
            </div>
        <?php else: ?>
            <div id="espacios-container">
                <?php 
                foreach($establecimientos as $establecimiento): 
                ?>
                    <div class="establecimiento-header">
                        <i class="fas fa-building me-2"></i> <?php echo htmlspecialchars($establecimiento['nombre']); ?>
                    </div>
                    
                    <?php if(empty($establecimiento['space'])): ?>
                        <div class="establecimiento-vacio">
                            <i class="fas fa-exclamation-circle mb-2"></i>
                            <p class="mb-0">Este establecimiento no tiene ningún espacio registrado.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($establecimiento['space'] as $espacio): ?>
                            <div class="espacio-card">
                                <div class="espacio-header">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($espacio['name']); ?></h5>
                                        <p class="mb-0"><?php echo htmlspecialchars($espacio['description']); ?></p>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-info btn-sm toggle-horarios" data-espacio-id="<?php echo $espacio['id']; ?>">
                                            <i class="fas fa-clock me-1"></i> Horarios
                                        </button>
                                        <a href="editarSpace.php?id=<?php echo $espacio['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i> Editar
                                        </a>
                                        <button class="btn btn-danger btn-sm btn-eliminar" data-espacio-id="<?php echo $espacio['id']; ?>" data-espacio-nombre="<?php echo htmlspecialchars($espacio['name']); ?>">
                                            <i class="fas fa-trash-alt me-1"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="horarios-container" id="horarios-<?php echo $espacio['id']; ?>">
                                    <?php if(empty($espacio['schedule'])): ?>
                                        <div class="alert alert-secondary">Este espacio no tiene horarios configurados.</div>
                                    <?php else: ?>
                                        <?php foreach($espacio['schedule'] as $horario): ?>
                                            <div class="horario-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <span class="day-badge <?php echo $horario['has_monday'] ? 'day-active' : 'day-inactive'; ?>">L</span>
                                                        <span class="day-badge <?php echo $horario['has_tuesday'] ? 'day-active' : 'day-inactive'; ?>">M</span>
                                                        <span class="day-badge <?php echo $horario['has_wednesday'] ? 'day-active' : 'day-inactive'; ?>">X</span>
                                                        <span class="day-badge <?php echo $horario['has_thursday'] ? 'day-active' : 'day-inactive'; ?>">J</span>
                                                        <span class="day-badge <?php echo $horario['has_friday'] ? 'day-active' : 'day-inactive'; ?>">V</span>
                                                        <span class="day-badge <?php echo $horario['has_saturday'] ? 'day-active' : 'day-inactive'; ?>">S</span>
                                                        <span class="day-badge <?php echo $horario['has_sunday'] ? 'day-active' : 'day-inactive'; ?>">D</span>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo substr($horario['start_time'], 0, 5); ?> - <?php echo substr($horario['end_time'], 0, 5); ?></strong>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between mb-2">
                                                    <div>
                                                        <strong>Precio:</strong> <?php echo number_format($horario['price'], 2); ?>€/hora
                                                    </div>
                                                </div>
                                                
                                                <?php if(!empty($horario['services'])): ?>
                                                    <div>
                                                        <strong>Servicios:</strong>
                                                        <?php foreach($horario['services'] as $servicio): ?>
                                                            <div class="servicio-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <strong><?php echo htmlspecialchars($servicio['name']); ?></strong>
                                                                    <span><?php echo number_format($servicio['price'], 2); ?>€</span>
                                                                </div>
                                                                <div><?php echo htmlspecialchars($servicio['description']); ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-muted">No hay servicios adicionales</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <div class="modal fade modal-confirm" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="icon-box">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <p class="mt-4">¿Estás seguro de que deseas eliminar el espacio <strong id="espacioNombre"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div class="toast custom-toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" id="toastExito">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i> Espacio eliminado correctamente.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <div class="toast custom-toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" id="toastError">
            <div class="d-flex">
                <div class="toast-body" id="mensajeError">
                    <i class="fas fa-exclamation-circle me-2"></i> Error al eliminar el espacio.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

	<div class="container-fluid footer mt-5 p-3">
        <div class="row text-center fixed-bottom bg-blanco pt-1 px-2 footer-container">
            <label for="anf" id="lbl_anf" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="Anfitriones.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-users p-1 m-0"></i>
                            <div>Anfitriones</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="val" id="lbl_val" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="validar.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-check-circle p-1 m-0"></i>
                            <div>Validar</div>
                        </div>
                    </a>
                </div>
            </label>

            <label for="res" id="lbl_res" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="tusReservas.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-book-open p-1 m-0"></i>
                            <div>Reservas</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="his" id="lbl_his" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEstablecimientos.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-building p-1 m-0"></i>
                            <div>Establecimientos</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="esp" id="lbl_esp" class="col-2 text-center footer-item">
                <div class="row">
                    <a href="verEspacios.php">
                        <div class="col-12 icon-container">
                            <i class="h2 fas fa-chair p-1 m-0"></i>
                            <div>Espacios</div>
                        </div>
                    </a>
                </div>
            </label>
            <label for="per" id="lbl_per" class="col-2 text-center footer-item">
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