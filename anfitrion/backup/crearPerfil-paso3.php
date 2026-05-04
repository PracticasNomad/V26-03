<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
	<link href="../style.css" rel="stylesheet">
	<script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
	<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
	<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
	<link rel="icon" href="favicon-color.png">

	<link rel="icon" href="favicon-negro.png" media="(prefers-color-scheme: light)">

	<link rel="icon" href="favicon-color.png" media="(prefers-color-scheme: dark)">
	<title>Crea tu perfil de anfitrión</title>
</head>
<?php
session_start();
?>

<body>
	<div class="contenedorAlta">
		<div class="container">
			<div class="row text-center p-3 pb-0 mb-0">
				<div class="col-12 fw-bold p-2">
					<img src="../img/antena.png" alt="establecimient0" width="150px">
				</div>
				<div class="col-12 h4 fw-bold p-2 pb-0 mb-0">
					Rellena la dirección del establecimiento y las formas de contacto
				</div>
			</div>
		</div>

		<form method="post" action="crearPerfil-paso4.php" class="container">
			<div class="row p-3 pt-0">
				<div class="col-12">
					<label for="input_tel" class="form-label fw-bold">Teléfono</label>
					<input type="text" class="form-control" id="input_tel" name="input_tel" required>
				</div>
				<div class="col-12">
					<label for="input_direccion" class="form-label fw-bold">Dirección ej. C/Rambla...</label>
					<input type="text" class="form-control" id="input_direccion" name="input_direccion" required>
				</div>
				<div class="col-12">
					<label for="input_localidad" class="form-label fw-bold">Localidad ej. Madrid</label>
					<input type="text" class="form-control" id="input_localidad" name="input_localidad" required>
				</div>
				<div class="col-12">
					<label for="input_provincia" class="form-label fw-bold">Provincia</label>
					<select required name="provincia" class="form-select form-control" aria-label="input_provincia" required>
						<option value="" disabled="true">Elige Provincia</option>
						<option value="Álava/Araba">Álava/Araba</option>
						<option value="Albacete">Albacete</option>
						<option value="Alicante">Alicante</option>
						<option value="Almería">Almería</option>
						<option value="Asturias">Asturias</option>
						<option value="Ávila">Ávila</option>
						<option value="Badajoz">Badajoz</option>
						<option value="Baleares">Baleares</option>
						<option value="Barcelona">Barcelona</option>
						<option value="Burgos">Burgos</option>
						<option value="Cáceres">Cáceres</option>
						<option value="Cádiz">Cádiz</option>
						<option value="Cantabria">Cantabria</option>
						<option value="Castellón">Castellón</option>
						<option value="Ceuta">Ceuta</option>
						<option value="Ciudad Real">Ciudad Real</option>
						<option value="Córdoba">Córdoba</option>
						<option value="Cuenca">Cuenca</option>
						<option value="Gerona/Girona">Gerona/Girona</option>
						<option value="Granada">Granada</option>
						<option value="Guadalajara">Guadalajara</option>
						<option value="Guipúzcoa/Gipuzkoa">Guipúzcoa/Gipuzkoa</option>
						<option value="Huelva">Huelva</option>
						<option value="Huesca">Huesca</option>
						<option value="Jaén">Jaén</option>
						<option value="La Coruña/A Coruña">La Coruña/A Coruña</option>
						<option value="La Rioja">La Rioja</option>
						<option value="Las Palmas">Las Palmas</option>
						<option value="León">León</option>
						<option value="Lérida/Lleida">Lérida/Lleida</option>
						<option value="Lugo">Lugo</option>
						<option value="Madrid">Madrid</option>
						<option value="Málaga">Málaga</option>
						<option value="Melilla">Melilla</option>
						<option value="Murcia">Murcia</option>
						<option value="Navarra">Navarra</option>
						<option value="Orense/Ourense">Orense/Ourense</option>
						<option value="Palencia">Palencia</option>
						<option value="Pontevedra">Pontevedra</option>
						<option value="Salamanca">Salamanca</option>
						<option value="Segovia">Segovia</option>
						<option value="Sevilla">Sevilla</option>
						<option value="Soria">Soria</option>
						<option value="Tarragona">Tarragona</option>
						<option value="Tenerife">Tenerife</option>
						<option value="Teruel">Teruel</option>
						<option value="Toledo">Toledo</option>
						<option value="Valencia">Valencia</option>
						<option value="Valladolid">Valladolid</option>
						<option value="Vizcaya/Bizkaia">Vizcaya/Bizkaia</option>
						<option value="Zamora">Zamora</option>
						<option value="Zaragoza">Zaragoza</option>
					</select>
				</div>

				<input type="hidden" name="nombre" id="nombre" value="<?php echo $_POST['nombre'] ?>" />
				<input type="hidden" name="email" id="email" value="<?php echo $_POST['email'] ?>" />
				<input type="hidden" name="password" id="password" value="<?php echo $_POST['password'] ?>" />

			</div>
			<div class="container">
				<div class="row">
					<div class="col-6 text-end">
						<button class="btn btn-cancel rounded-pill ps-4 pe-4" type="button" onclick="location.href='crearPerfil-paso2.php'">Anterior</button>
					</div>
					<div class="col-6">
						<button type="submit" value="SIGUIENTE" name="siguiente" class="btn btn-success rounded-pill ps-4 pe-4">Siguiente</button>
					</div>
				</div>
			</div>
		</form>

		<?php

		if (isset($_POST['input_tel']) && isset($_POST['input_direccion']) && isset($_POST['input_localidad']) && isset($_POST['provincia'])) {
			$nombre = $_POST['nombre'];
			$email = $_POST['email'];
			$password = $_POST['password'];
			$input_tel = $_POST['input_tel'];
			$input_direccion = $_POST['input_direccion'];
			$input_localidad = $_POST['input_localidad'];
			$provincia = $_POST['provincia'];
			if (isset($_POST["siguiente"])) {
				header('Location: crearPerfil-paso4.php');
			}
		}

		?>

		<div class="container-fluid p-3">
			<div class="row text-center">
				<div class="col-12">Paso 3 de 5</div>
			</div>
		</div>
	</div>
</body>

</html>