	<?php
	require_once 'verificar_sesion_guest.php';
	$AnfitrionId = $_GET['id'];
	$DiaHoraInicio = $_GET['horaInicio'];
	$reservaId = $_GET['reservaId'];
	$anfitrionId = $_GET['anfitrionId'];
	$nomadaId2 = $_GET['Id'];

	?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
		<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
		<!-- <script src='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js'></script> -->
		<!-- <link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' /> -->
		<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous"> -->
		<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script> -->
		<link rel="icon" href="favicon-color.png">

		<link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

		<link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">

		<title>Reserva</title>

		<script>
			const url = "getNomadaByEmailAsync.php";

			fetch(url)
				.then(response => response.json())
				.then(data => {
					let id = data.id;
				})
				.catch(err => console.log(err));
		</script>

		<style>
			body {
				min-height: 100vh;
				font-family: 'Nunito', sans-serif;
				background-color: #E3E1E1;
			}

			.header {
				width: 100%;
				max-height: 25rem;
				background: #00B7CF;
				color: white;
			}

			.body {
				margin-top: 4rem;
			}

			.icons {
				color: #4CCBD4;
			}

			.select_day {
				border-radius: 0.5rem;
				background: rgb(243, 209, 181);
				color: #81ba18;
			}

			.select_day div div input {
				background: rgb(243, 209, 181);
				border: none;
				font-weight: bold;
				color: #00B7CF;
			}

			.disponibilidad .col-12 {
				color: #00B7CF;
			}

			.disponibilidad .col-3 div {
				border-radius: 0.5rem;
				color: black;
				background-color: #BDE742;
			}

			.button button {
				border: none;
				border-radius: 1rem;
				height: 2rem;
			}

			.button button a {
				color: white;
				text-decoration: none;
			}

			.button .cancel {
				background-color: chocolate;
			}

			.button .confirm {
				background-color: #00B7CF;
			}

			#map {
				height: 13rem;
				margin-top: 1rem;
			}

			.reserva {
				color: #00B7CF;
			}

			.btn-primary,
			.btn-primary:focus {
				background-color: #00B7CF;
				border: none;
			}

			.btn-primary:hover {
				background-color: #4CCBD4;
			}

			#developed {
				color: gray;
				transition: 0.3s;
			}

			#developed:hover {
				color: #A3FF2E;
			}

			#avisos a {
				color: lightgray;
				display: inline-block;
				padding-left: 20px;
			}
		</style>
	</head>

	<body>
		<div class="container p-0">
			<img class="header" src="https://cdn.pixabay.com/photo/2016/11/18/14/05/brick-wall-1834784_960_720.jpg" alt="">
		</div>
		<div class="container header">
			<div class="row p-1">
				<div class="col-12 h2 text-center fw-bold">
					<?php $nombre = $_GET['nombre'];
					echo $nombre; ?>
				</div>
				<div class="col-12 h4 text-center fw-bold">
					<?php $direccion = $_GET['direccion'];
					echo $direccion; ?>
				</div>
				<div class="col-12 h3 text-center fw-bold">
					<?php $poblacion = $_GET['poblacion'];
					echo $poblacion; ?>
				</div>
			</div>
		</div>

		<form method="post" class="col-12 px-5">

			<div class="container">
				<div class="row">
					<div id="map" class="col-12 text-center m-0">
						mapa
					</div>
					<div class="col-12 h2 text-center fw-bold pt-3">
						horario de reserva
					</div>
					<div class="col-12">

						<div class="row reserva">
							<div class="col-12 text-center fw-bold fst-italic">
								<?php
								echo $fecha_reserva = $_GET['dia'];
								?>
							</div>
							<div class="col-12 text-center fw-bold fst-italic">
								<?php $dispo = $_GET['hora'];
								echo $dispo; ?>
							</div>
							<div class="col-12 text-center fw-bold fst-italic">
								<?php $horaInicio = $_GET['horaInicio'];
								echo $horaInicio; ?>
							</div>
						</div>

					</div>
					<div class="col-12 text-center mb-5 mt-4">
						<div class="row">
							<div class="col-6 px-3 button">
								<button class="cancel" type="submit" id="cancelar" name="cancelar" data-toggle="modal" data-target="#exampleModal"><a class="px-4 fw-bold">Cancelar</a></button>
							</div>
							<div class="col-6 px-3 button" id="as">
								<button class="confirm" type="submit" id="confirmar" name="confirmar"><a class="px-4 fw-bold">Confirmar</a></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>

	</body>

	<?php



	$token = $_SESSION["token"];
	//setup the request, you can also use CURLOPT_URL
	//$ch = curl_init('http://yonomadapp.hopto.org:8089/api/anfitriondatas/GetAnfitrionNomadaByIdAsync');

	$persona3 = [];
	// Los codificamos
	// recomendado: https://parzibyte.me/blog/2018/12/26/codificar-decodificar-json-php/
	$datosCodificados3 = json_encode($persona3);

	//echo $datosCodificados;

	// Comenzar a crear el objeto de curl
	# A dónde se hace la petición...
	$url3 = "http://yonomadapp.hopto.org:8089/api/nomadadatas/GetNomadaByEmailAsync";
	$ch3 = curl_init($url3);

	# Ahora le ponemos todas las opciones
	# Nota: podríamos usar la versión corta de arreglos: https://parzibyte.me/blog/2018/10/11/sintaxis-corta-array-php/


	curl_setopt_array($ch3, array(
		// Indicar que vamos a hacer una petición POST
		CURLOPT_CUSTOMREQUEST => "POST",
		// Justo aquí ponemos los datos dentro del cuerpo
		CURLOPT_POSTFIELDS => $datosCodificados3,
		// Encabezados
		//CURLOPT_HEADER => true,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json;charset=UTF-8',
			'Authorization: Bearer ' . $token
		),
		# indicar que regrese los datos, no que los imprima directamente
		CURLOPT_RETURNTRANSFER => true,
	));

	//setup request to send json via POST
	$data3 = array(
		'email' => $_SESSION['email']
	);

	//$payload = json_encode(array("user" => $data));

	$payload3 = json_encode($data3);

	//attach encoded JSON string to the POST fields
	curl_setopt($ch3, CURLOPT_POSTFIELDS, $payload3);

	//return response instead of outputting
	curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
	# Hora de hacer la petición
	$resultado3 = curl_exec($ch3);
	$nomadaId = json_decode($resultado3, true);

	# Vemos si el código es 200, es decir, HTTP_OK
	$codigoRespuesta3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);

	//echo $codigoRespuesta;
	if ($codigoRespuesta3 === 200) {
		# Decodificar JSON porque esa es la respuesta
		$respuestaDecodificada3 = json_decode($resultado3);
		//echo $respuestaDecodificada;
		# Simplemente los imprimimos
		// echo "<strong>El servidor dice que la hora de petición fue: </strong>" . $respuestaDecodificada->fechaYHora;
		//echo "<br><strong>El servidor dice que el primer lenguaje es: </strong>" . $respuestaDecodificada->primerLenguaje;
		//echo "<br><strong>Los encabezados que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada-				>encabezados, 		true) . "</pre>";
		//echo "<br><strong>Los gustos musicales que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada-		>gustosMusicales, true) . "</pre>";
		//echo "<br><strong>Los libros que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada->libros, true) 		. "		</pre>";
		//echo "<br><strong>Mensaje del servidor: </strong>" . $respuestaDecodificada->mensaje;
	} else {
		# Error
	}
	curl_close($ch3);

	$persona2 = [];
	// Los codificamos
	// recomendado: https://parzibyte.me/blog/2018/12/26/codificar-decodificar-json-php/
	$datosCodificados2 = json_encode($persona2);

	//echo $datosCodificados;

	// Comenzar a crear el objeto de curl
	# A dónde se hace la petición...
	$url2 = "http://yonomadapp.hopto.org:8089/api/anfitriondatas/GetAnfitrionNomadaByIdAsync";
	$ch2 = curl_init($url2);

	# Ahora le ponemos todas las opciones
	# Nota: podríamos usar la versión corta de arreglos: https://parzibyte.me/blog/2018/10/11/sintaxis-corta-array-php/


	curl_setopt_array($ch2, array(
		// Indicar que vamos a hacer una petición POST
		CURLOPT_CUSTOMREQUEST => "GET",
		// Justo aquí ponemos los datos dentro del cuerpo
		CURLOPT_POSTFIELDS => $datosCodificados2,
		// Encabezados
		//CURLOPT_HEADER => true,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json;charset=UTF-8',
			'Authorization: Bearer ' . $token
		),
		# indicar que regrese los datos, no que los imprima directamente
		CURLOPT_RETURNTRANSFER => true,
	));

	//setup request to send json via POST
	$data2 = array(
		'id' => $AnfitrionId
	);

	//$payload = json_encode(array("user" => $data));

	$payload2 = json_encode($data2);

	//attach encoded JSON string to the POST fields
	curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload2);

	//return response instead of outputting
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
	# Hora de hacer la petición
	$resultado2 = curl_exec($ch2);


	# Vemos si el código es 200, es decir, HTTP_OK
	$codigoRespuesta2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

	//echo $codigoRespuesta;
	if ($codigoRespuesta2 === 200) {
		# Decodificar JSON porque esa es la respuesta
		$respuestaDecodificada2 = json_decode($resultado2);
		//echo $respuestaDecodificada;
		# Simplemente los imprimimos
		// echo "<strong>El servidor dice que la hora de petición fue: </strong>" . $respuestaDecodificada->fechaYHora;
		//echo "<br><strong>El servidor dice que el primer lenguaje es: </strong>" . $respuestaDecodificada->primerLenguaje;
		//echo "<br><strong>Los encabezados que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada-				>encabezados, 		true) . "</pre>";
		//echo "<br><strong>Los gustos musicales que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada-		>gustosMusicales, true) . "</pre>";
		//echo "<br><strong>Los libros que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada->libros, true) 		. "		</pre>";
		//echo "<br><strong>Mensaje del servidor: </strong>" . $respuestaDecodificada->mensaje;
	} else {
		# Error
	}
	curl_close($ch2);

	$fechareserva = $fecha_reserva . "T" . $dispo . ":00+02:00";

	if (isset($_POST['confirmar'])) {

		$token = $_SESSION["token"];
		//setup the request, you can also use CURLOPT_URL
		//$ch = curl_init('http://yonomadapp.hopto.org:8089/api/anfitriondatas/GetAnfitrionNomadaByIdAsync');

		$persona = [];
		// Los codificamos
		// recomendado: https://parzibyte.me/blog/2018/12/26/codificar-decodificar-json-php/
		$datosCodificados = json_encode($persona);

		//echo $datosCodificados;

		// Comenzar a crear el objeto de curl
		# A dónde se hace la petición...
		$url = "http://yonomadapp.hopto.org:8089/api/reserva/AddReservaEspacio";
		$ch = curl_init($url);

		# Ahora le ponemos todas las opciones
		# Nota: podríamos usar la versión corta de arreglos: https://parzibyte.me/blog/2018/10/11/sintaxis-corta-array-php/


		curl_setopt_array($ch, array(
			// Indicar que vamos a hacer una petición POST
			CURLOPT_CUSTOMREQUEST => "POST",
			// Justo aquí ponemos los datos dentro del cuerpo
			CURLOPT_POSTFIELDS => $datosCodificados,
			// Encabezados
			//CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json;charset=UTF-8',
				'Authorization: Bearer ' . $token
			),
			# indicar que regrese los datos, no que los imprima directamente
			CURLOPT_RETURNTRANSFER => true,
		));

		//setup request to send json via POST
		$data = array(
			'nomadaId' => $nomadaId["id"],
			'AnfitrionId' => $AnfitrionId,
			'DiaHoraInicio' => $fechareserva
		);

		//$payload = json_encode(array("user" => $data));

		$payload = json_encode($data);

		//attach encoded JSON string to the POST fields
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

		//return response instead of outputting
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		# Hora de hacer la petición
		$resultado = curl_exec($ch);

		# Vemos si el código es 200, es decir, HTTP_OK
		$codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		//echo $codigoRespuesta;
		if ($codigoRespuesta === 200) {
			# Decodificar JSON porque esa es la respuesta
			$respuestaDecodificada = json_decode($resultado);
			//echo $respuestaDecodificada;
			# Simplemente los imprimimos
			// echo "<strong>El servidor dice que la hora de petición fue: </strong>" . $respuestaDecodificada->fechaYHora;
			//echo "<br><strong>El servidor dice que el primer lenguaje es: </strong>" . $respuestaDecodificada->primerLenguaje;
			//echo "<br><strong>Los encabezados que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada-				>encabezados, 		true) . "</pre>";
			//echo "<br><strong>Los gustos musicales que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada-		>gustosMusicales, true) . "</pre>";
			//echo "<br><strong>Los libros que el servidor recibió fueron: </strong><pre>" . var_export($respuestaDecodificada->libros, true) 		. "		</pre>";
			//echo "<br><strong>Mensaje del servidor: </strong>" . $respuestaDecodificada->mensaje;
		} else {
			# Error
			echo "Ya tienes una reserva en este horario";
		}
		curl_close($ch);
		echo "<script type='text/javascript'> document.location = 'https://nomadappme.yonomad.app/nomada_reservas.php'; </script>";
	}

	if (isset($_POST['cancelar'])) {

		$persona4 = [];

		// Los codificamos
		// recomendado: https://parzibyte.me/blog/2018/12/26/codificar-decodificar-json-php/
		$datosCodificados4 = json_encode($persona4);

		//echo $datosCodificados;

		// Comenzar a crear el objeto de curl
		# A dónde se hace la petición...
		$url4 = "http://yonomadapp.hopto.org:8089/api/reserva/ModificarReservaEspacio";
		$ch4 = curl_init($url4);



		curl_setopt_array($ch4, array(
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => $datosCodificados4,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json;charset=UTF-8',
				'Authorization: Bearer ' . $token
			),
			CURLOPT_RETURNTRANSFER => true,
		));

		$data4 = array(
			'Id' => $reservaId,
			'Cancelada' => true,
			'Realizada' => true,
			'Comentario' => "",
			'nomadaId' => $nomadaId2,
			'AnfitrionId' => $anfitrionId,
			'DiaHoraInicio' => $DiaHoraInicio
		);

		$payload4 = json_encode($data4);

		curl_setopt($ch4, CURLOPT_POSTFIELDS, $payload4);

		curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
		$resultado4 = curl_exec($ch4);
		$nomadaId = json_decode($resultado4, true);

		$codigoRespuesta4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);

		//echo $codigoRespuesta;
		if ($codigoRespuesta4 === 200) {
			$respuestaDecodificada4 = json_decode($resultado4);
		} else {
			echo "Error consultando. Código de respuesta: $codigoRespuesta4";
		}

		curl_close($ch4);

		echo "<script type='text/javascript'> document.location = 'https://nomadappme.yonomad.app/nomada_reservas.php'; </script>";
	}

	?>

	<script>
		var ciudaBuscadaY = 36.7196;
		var ciudaBuscadaX = -4.42002;


		L.mapbox.accessToken = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';
		var map = L.mapbox.map('map')
			.setView([ciudaBuscadaY, ciudaBuscadaX], 13)
			.addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));
		var myIcon = L.icon({
			iconUrl: 'img/posicionAnfitrion.png',
			iconSize: [26, 26],
			iconAnchor: [13, 42],
		});

		L.mapbox.featureLayer({
			'features': [{
				type: 'Feature'
			}, ]

		}).addTo(map);
		L.marker([ciudaBuscadaY, ciudaBuscadaX], {
			icon: myIcon
		}).addTo(map);
	</script>

	</html>