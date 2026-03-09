<?php
require_once 'verificar_sesion_host.php';
$_SESSION['reservaId'] = $_GET['id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
	<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;400;600;700&display=swap" rel="stylesheet">
	<script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script>
	<link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
	<link rel="icon" href="favicon-color.png">

	<link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

	<link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">

	<title>Detalles de Reserva</title>

	<style>
		body {
			min-height: 100vh;
			font-family: 'Nunito', sans-serif;
			background-color: #f8f9fa;
			color: #333;
		}

		.header-container {
			position: relative;
			width: 100%;
			height: 250px;
			overflow: hidden;
			margin-bottom: 0;
		}

		.header-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			filter: brightness(0.7);
		}

		.header-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(rgba(0, 183, 207, 0.7), rgba(0, 183, 207, 0.9));
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			color: white;
			padding: 1rem;
		}

		.card {
			border-radius: 10px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			margin-bottom: 1.5rem;
			border: none;
			overflow: hidden;
		}

		.card-header {
			background-color: #00B7CF;
			color: white;
			font-weight: 600;
			padding: 1rem;
			border-bottom: none;
		}

		.section-title {
			color: #00B7CF;
			font-weight: 700;
			margin-bottom: 1rem;
			padding-bottom: 0.5rem;
			border-bottom: 2px solid #BDE742;
		}

		.info-item {
			display: flex;
			align-items: center;
			margin-bottom: 0.8rem;
		}

		.info-icon {
			color: #00B7CF;
			width: 25px;
			margin-right: 10px;
			text-align: center;
		}

		#map {
			height: 300px;
			width: 100%;
			border-radius: 10px;
		}

		.btn-nomad {
			background-color: #00B7CF;
			color: white;
			border: none;
			border-radius: 50px;
			padding: 0.5rem 1.5rem;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.btn-nomad:hover {
			background-color: #4CCBD4;
			color: white;
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		}

		.btn-danger-soft {
			background-color: #ff7066;
			color: white;
			border: none;
			border-radius: 50px;
			padding: 0.5rem 1.5rem;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.btn-danger-soft:hover {
			background-color: #ff4136;
			color: white;
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		}

		.service-badge {
			background-color: #BDE742;
			color: #333;
			padding: 0.5rem 1rem;
			border-radius: 50px;
			margin-right: 0.5rem;
			margin-bottom: 0.5rem;
			display: inline-flex;
			align-items: center;
			font-weight: 600;
		}

		.service-badge i {
			margin-right: 5px;
		}

		.reservation-details {
			background-color: #f8f9fa;
			border-radius: 10px;
			padding: 1.5rem;
			margin-bottom: 1.5rem;
			border: 1px solid #e9ecef;
		}

		.reservation-date {
			font-size: 1.2rem;
			font-weight: 700;
			color: #00B7CF;
			margin-bottom: 1rem;
		}

		.reservation-time {
			font-size: 1.1rem;
			color: #495057;
		}

		.modal-content {
			border-radius: 15px;
			border: none;
		}

		.modal-header {
			background-color: #ff7066;
			color: white;
			border-bottom: none;
			border-radius: 15px 15px 0 0;
		}

		.modal-footer {
			border-top: none;
		}

		.client-avatar {
			width: 100px;
			height: 100px;
			border-radius: 50%;
			object-fit: cover;
			border: 3px solid #BDE742;
			margin-bottom: 1rem;
		}

		@media (max-width: 768px) {
			.header-container {
				height: 200px;
			}

			.header-overlay h1 {
				font-size: 1.5rem;
			}

			.header-overlay h3 {
				font-size: 1rem;
			}

			#map {
				height: 200px;
			}

			.client-avatar {
				width: 80px;
				height: 80px;
			}
		}

		.modal-header.cancel-reason {
			background-color: #ffc107;
			color: #212529;
		}

		.establishment-details,
		.host-details {
			background-color: #f8f9fa;
			border-radius: 10px;
			padding: 1.5rem;
			margin-bottom: 1.5rem;
			border: 1px solid #e9ecef;
		}

		#otroMotivoInput {
			display: none;
		}

		.form-control:focus {
			border-color: #00B7CF;
			box-shadow: 0 0 0 0.2rem rgba(0, 183, 207, 0.25);
		}
	</style>
</head>

<body>
	<div class="container p-0">
		<div class="header-container">
			<img class="header-img" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg" alt="Space Image">
			<div id="headerContent" class="header-overlay">
				<div class="text-center">
					<h1 id="spaceName" class="fw-bold mb-2">Cargando detalles...</h1>
					<h3 id="hostName" class="fw-bold">Por favor espere</h3>
				</div>
			</div>
		</div>
	</div>

	<div class="container py-4">
		<div class="row">
			<div class="col-lg-7 mb-4">
				<div class="card">
					<div class="card-header">
						<h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Detalles de la Reserva</h4>
					</div>
					<div class="card-body">
						<div id="reservationDetails" class="reservation-details">
						</div>

						<h5 class="section-title"><i class="fas fa-user me-2"></i>Datos del Cliente</h5>
						<div id="clientInfo" class="mb-4 text-center">
						</div>

						<h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Información del Espacio</h5>
						<div id="spaceInfo" class="mb-4">
						</div>

						<div class="text-center mt-4">
							<button type="button" class="btn btn-danger-soft me-3" id="botonCancelar" data-bs-toggle="modal" data-bs-target="#cancelReasonModal">
								<i class="fas fa-times-circle me-2"></i>Cancelar Reserva
							</button>
							<a href="tusReservas.php" class="btn btn-nomad">
								<i class="fas fa-arrow-left me-2"></i>Volver Atrás
							</a>
						</div>
					</div>
				</div>
			</div>

			<div class="col-lg-5 mb-4">
				<div class="card">
					<div class="card-header">
						<h4 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h4>
					</div>
					<div class="card-body p-0">
						<div id="map">
						</div>
					</div>
					<div class="card-footer bg-white">
						<div id="address" class="pt-2">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header cancel-reason">
					<h5 class="modal-title" id="cancelReasonModalLabel"><i class="fas fa-question-circle me-2"></i>Motivo de Cancelación</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="cancelReasonForm">
						<div class="mb-3">
							<label for="motivoCancelacion" class="form-label fw-bold">¿Cuál es el motivo de tu cancelación? *</label>
							<select class="form-select" id="motivoCancelacion" required>
								<option value="">Selecciona un motivo</option>
								<option value="El espacio ya no está disponible">El espacio ya no está disponible</option>
								<option value="Problemas técnicos o de mantenimiento">Problemas técnicos o de mantenimiento</option>
								<option value="Error en la reserva o información publicada">Error en la reserva o información publicada</option>
								<option value="Otro motivo">Otro motivo</option>
							</select>
						</div>

						<div class="mb-3" id="otroMotivoInput">
							<label for="otroMotivoTexto" class="form-label fw-bold">Especifica el motivo:</label>
							<input type="text" class="form-control" id="otroMotivoTexto" placeholder="Describe brevemente el motivo">
						</div>

						<div class="mb-3">
							<label for="informacionAdicional" class="form-label fw-bold">Información adicional (opcional)</label>
							<textarea class="form-control" id="informacionAdicional" rows="3" placeholder="Comparte cualquier información adicional que consideres relevante..."></textarea>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
					<button type="button" id="proceedCancel" class="btn btn-danger" disabled>Continuar con la cancelación</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="cancelModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Cancelación</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<p class="fs-5">¿Estás seguro que deseas cancelar esta reserva?</p>
					<p class="text-muted">Esta acción no se puede deshacer.</p>
					<div id="cancelSummary" class="mt-3 p-3 bg-light rounded">
						<!-- Aquí se mostrará el resumen del motivo de cancelación -->
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener reserva</button>
					<button type="button" id="confirmCancel" class="btn btn-danger">Sí, cancelar reserva</button>
				</div>
			</div>
		</div>
	</div>

	<script>
		let cancelationData = {};
		if ('<?php isset($_SESSION['reservaId']) ?>') {
			console.log("esta si que lo esta");
		}
		document.addEventListener('DOMContentLoaded', function() {


			document.getElementById('motivoCancelacion').addEventListener('change', function() {
				const otroMotivoDiv = document.getElementById('otroMotivoInput');
				const proceedBtn = document.getElementById('proceedCancel');
				const otroMotivoTexto = document.getElementById('otroMotivoTexto');

				if (this.value === 'Otro motivo') {
					otroMotivoDiv.style.display = 'block';
					otroMotivoTexto.required = true;

					proceedBtn.disabled = otroMotivoTexto.value.trim() === '';
				} else {
					otroMotivoDiv.style.display = 'none';
					otroMotivoTexto.required = false;
					otroMotivoTexto.value = '';

					proceedBtn.disabled = this.value === '';
				}
			});

			document.getElementById('otroMotivoTexto').addEventListener('input', function() {
				const proceedBtn = document.getElementById('proceedCancel');
				const motivoSelect = document.getElementById('motivoCancelacion');

				if (motivoSelect.value === 'Otro motivo') {
					proceedBtn.disabled = this.value.trim() === '';
				}
			});

			document.getElementById('proceedCancel').addEventListener('click', function() {
				const motivo = document.getElementById('motivoCancelacion').value;
				const otroMotivo = document.getElementById('otroMotivoTexto').value;
				const infoAdicional = document.getElementById('informacionAdicional').value;

				cancelationData = {
					motivo: motivo === 'Otro motivo' ? otroMotivo : motivo,
					informacionAdicional: infoAdicional
				};

				let summaryHTML = `<strong><i class="fas fa-info-circle me-2"></i>Motivo:</strong> ${cancelationData.motivo}`;
				if (infoAdicional.trim() !== '') {
					summaryHTML += `<br><strong><i class="fas fa-comment me-2"></i>Información adicional:</strong> ${infoAdicional}`;
				}
				document.getElementById('cancelSummary').innerHTML = summaryHTML;

				const cancelReasonModal = bootstrap.Modal.getInstance(document.getElementById('cancelReasonModal'));
				cancelReasonModal.hide();

				setTimeout(() => {
					const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
					cancelModal.show();
				}, 300);
			});
			const reservaId = '<?php echo $_SESSION["reservaId"]; ?>';

			fetch('../getReservaById.php')
				.then(response => response.json())
				.then(data => {
					console.log(data);
					renderReservationData(data);
				})
				.catch(err => {
					console.log('Error fetching reservation data:', err);
					document.getElementById('headerContent').innerHTML = '<div class="text-center"><h2>Error al cargar los datos</h2></div>';
				});

			document.getElementById('confirmCancel').addEventListener('click', function() {
				cancelReservation(reservaId);
			});
		});

		function renderReservationData(data) {
			document.getElementById('spaceName').textContent = data[0].space.name;
			document.getElementById('hostName').textContent = data[0].space.establecimiento.nombre;

			const reservationDate = new Date(data[0].day);
			const formattedDate = reservationDate.toLocaleDateString('es-ES', {
				weekday: 'long',
				year: 'numeric',
				month: 'long',
				day: 'numeric'
			});

			document.getElementById('reservationDetails').innerHTML = `
					<div class="reservation-date">${formattedDate}</div>
					<div class="reservation-time mb-3">
						<i class="far fa-clock me-2"></i>Hora de entrada: <strong>${data[0].start_time.substring(0, 5)}</strong>
						<br>
						<i class="far fa-clock me-2"></i>Hora de salida: <strong>${data[0].end_time.substring(0, 5)}</strong>
					</div>
					${data[0].message ? `<div class="mt-3 p-3 bg-light rounded"><i class="fas fa-comment-alt me-2 text-muted"></i>Comentario Adicional: "${data[0].message}"</div>` : ''}
				`;

			document.getElementById('spaceInfo').innerHTML = `
					<div class="mb-3">
						<p class="mb-1 fw-bold">${data[0].space.name + " - " + data[0].space.establecimiento.nombre}</p>
						<p class="text-muted">${data[0].space.description}</p>
					</div>
				`;

			let avatarUrl = data[0].user.avatar_url;
			if (!avatarUrl || avatarUrl === 'img/perfil.png') {
				avatarUrl = '../img/perfil.png';
			}

			document.getElementById('clientInfo').innerHTML = `
					<img src="${avatarUrl}" alt="Avatar del Cliente" class="client-avatar">
					<div class="info-item justify-content-center">
						<div class="info-icon"><i class="fas fa-user"></i></div>
						<div>${data[0].user.name}</div>
					</div>
					<div class="info-item justify-content-center">
						<div class="info-icon"><i class="fas fa-phone"></i></div>
						<div>${data[0].user.telefono}</div>
					</div>
					<div class="info-item justify-content-center">
						<div class="info-icon"><i class="fas fa-envelope"></i></div>
						<div>${data[0].user.email}</div>
					</div>
					<div class="info-item justify-content-center">
						<div class="info-icon"><i class="fas fa-id-card"></i></div>
						<div>${data[0].dni_nomada}</div>
					</div>
					<div class="info-item justify-content-center">
						<div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
						<div>${data[0].direccion}</div>
					</div>
				`;

			const today = new Date().toISOString().split('T')[0];
			if (today > data[0].day || data[0].cancelada == true) {
				let botonCancelar = document.getElementById("botonCancelar");
				botonCancelar.style.display = 'none';
			}
			document.getElementById('address').innerHTML = `
					<div class="info-item">
						<div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
						<div>
							${data[0].space.establecimiento.direccion}<br>
							${data[0].space.establecimiento.localidad}, ${data[0].space.establecimiento.provincia}
						</div>
					</div>
				`;

			initMap(data[0].space.establecimiento.latitude, data[0].space.establecimiento.longitude, data[0].space.establecimiento.nombre, data[0].space.establecimiento.direccion + ", " + data[0].space.establecimiento.localidad);
		}

		function initMap(lat, lng, placeName, direccion) {
			if (lat == null || lat == undefined) {
				lat = 0;
			}
			if (lng == null || lng == undefined) {
				lng = 0;
			}
			L.mapbox.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';
			const map = L.mapbox.map('map')
				.setView([lat, lng], 15)
				.addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));

			const myIcon = L.icon({
				iconUrl: '../img/posicionAnfitrion.png',
				iconSize: [36, 36],
				iconAnchor: [18, 36],
			});

			const marker = L.marker([lat, lng], {
				icon: myIcon
			}).addTo(map);

			marker.bindPopup(`<p><b>${placeName}</b></p><p>${direccion}</p>`).openPopup();
		}

		function cancelReservation(reservaId) {
			console.log('Datos de cancelación:', cancelationData);

			let cancelUrl = '../eliminarReserva.php?actor=host&id=' + reservaId;
			if (cancelationData.motivo) {
				cancelUrl += '&motivo=' + encodeURIComponent(cancelationData.motivo);
			}
			if (cancelationData.informacionAdicional) {
				cancelUrl += '&info_adicional=' + encodeURIComponent(cancelationData.informacionAdicional);
			}

			fetch(cancelUrl)
				.then(data => {
					alert('Se ha eliminado correctamente la reserva.');
					location.href = './tusReservas.php';
				})
				.catch(err => {
					console.log('Error al cancelar la reserva:', err);
					alert('Error al cancelar la reserva. Por favor, inténtalo de nuevo.');
				});
		}
	</script>
</body>

</html>