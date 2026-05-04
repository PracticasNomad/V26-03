<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
    <title>Crea tu perfil de anfitrión</title>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <link href="../style.css" rel="stylesheet">
</head>
<?php
session_start();

if (isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['input_tel']) && isset($_POST['input_direccion']) && isset($_POST['input_localidad']) && isset($_POST['provincia']) && isset($_POST['comida']) && isset($_POST['parking']) && isset($_POST['input_precio'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $input_tel = $_POST['input_tel'];
    $input_direccion = $_POST['input_direccion'];
    $input_localidad = $_POST['input_localidad'];
    $provincia = $_POST['provincia'];
    $comida = $_POST['comida'];
    $parking = $_POST['parking'];
    $input_precio = $_POST['input_precio'];
    if (isset($_POST['siguiente'])) {
        header('Location: crearPerfil-paso5.php');
    }
}
?>

<body>
    <div class="contenedorAlta">
        <div class="container">
            <div class="row text-center p-3 pb-0 mb-0">
                <div class="col-12 fw-bold p-2">
                    <img src="../img/antena.png" alt="establecimient0" width="150px">
                </div>
                <div class="col-12 h4 fw-bold p-2 pb-0 mb-0">
                    Rellena las especificaciones de tu establecimiento
                </div>
            </div>
        </div>
        <form action="crearPerfil-paso5.php" method="POST" class="container">
            <div class="row p-3 text-center pt-0">
                <input type="hidden" name="nombre" id="nombre" value="<?php echo $_POST['nombre'] ?>" />
                <input type="hidden" name="email" id="email" value="<?php echo $_POST['email'] ?>" />
                <input type="hidden" name="password" id="password" value="<?php echo $_POST['password'] ?>" />
                <input type="hidden" name="input_tel" id="input_tel" value="<?php echo $_POST['input_tel'] ?>" />
                <input type="hidden" name="input_direccion" id="input_direccion" value="<?php echo $_POST['input_direccion'] ?>" />
                <input type="hidden" name="input_localidad" id="input_localidad" value="<?php echo $_POST['input_localidad'] ?>" />
                <input type="hidden" name="provincia" id="provincia" value="<?php echo $_POST['provincia'] ?>" />
                <div class="col-12 mt-3">
                    <div class="color-blue fs-1"><i class="fas fa-hamburger"></i></div>
                    <div>¿Permites la reserva de comida?</div>
                    <div class="form-check form-switch p-0">
                        <input class="form-check-input" style="float: none; top:0px;" type="checkbox" name="comida" id="flexSwitchCheckDefault" value="true">
                        <label class="form-check-label" style="top: 0px;" for="flexSwitchCheckDefault"><span>NO</span></label>
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <div class="color-blue fs-1"><i class="fas fa-parking"></i></div>
                    <div>¿Dispones de parking?</div>
                    <div class="form-check form-switch p-0">
                        <input class="form-check-input" style="float: none; top: 0px;" type="checkbox" name="parking" id="flexSwitchCheckDefault" value="true">
                        <label class="form-check-label" style="top: 0px;" for="flexSwitchCheckDefault"><span>NO</span></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3"></div>
                    <div class="col-6 text-center">
                        <div class="text-start">
                            <label for="input_precio" class="form-label fw-bold">Precio Parking</label>
                        </div>
                        <input type="text" class="form-control text-end" id="input_precio" name="input_precio" value="0">
                    </div>
                    <div class="col-3"></div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-6 text-end">
                        <button class="btn btn-cancel rounded-pill ps-4 pe-4" type="button" onclick="location.href='crearPerfil-paso3.php'">Anterior</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" value="SIGUIENTE" name="siguiente" class="btn btn-success rounded-pill ps-4 pe-4">Siguiente</button>
                    </div>
                </div>
            </div>
        </form>
        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 4 de 5</div>
            </div>
        </div>
    </div>
</body>

</html>
<!--FOOTER-->