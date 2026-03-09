<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/b8814a2854.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Inicio sesión Anfitrion</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <form method="post">
        <div class="contenedorLogin">
            <div class="row fw-bold centrar">
                <div class="col-12 text-center py-4 titulo">
                    ¿Ofreces un espacio cómodo para trabajar o estudiar?
                </div>
                <div class="col-12 text-center">
                    <img src="../img/antena.png" width="90px" alt="">
                </div>
                <div class="col-12 text-center h3 pt-4 pb-4 fw-bold">
                    Inicia sesión
                </div>
                <form action="" class="col-12 ml-4">
                    <div class="row">
                        <label for="" class="form-label"><span>Introduce tu e-mail</span>
                            <input type="text" class="form-control" name="email" id="email" required></label>
                    </div>
                    <div class="row">
                        <label for="" class="form-label"><span>Introduce tu contraseña</span>
                            <input type="password" class="form-control" name="password" id="password" required></label>
                    </div>
                    <br>
                </form>
                <div class=center>
                    <button class="btn btn-cancel btn-login" type="button" onclick="location.href='../index.php'">Volver al mapa</button>
                    <button class="btn btn-success btn-login" type="submit">Entrar</button>
                </div>
                <div class="col-12 text-center">
                    <br><br>
                    ¿No tienes una cuenta? <a href="crearPerfil-paso1.php">Regístrate</a>
                </div>
                <div class="col-12 text-center py-5 power">
                    <span class="logoSmart">Powered by </span> <img height="30px" src="../img/smartable.png" alt="">
                </div>
            </div>
        </div>
    </form>

</body>

</html>

<?php

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    //API URL
    $url = 'http://yonomadapp.hopto.org:8089/api/cuentas/login';

    //create a new cURL resource
    $ch = curl_init($url);

    //setup request to send json via POST
    $data = array(
        'email' => $email,
        'password' => $password
    );

    //$payload = json_encode(array("user" => $data));

    $payload = json_encode($data);

    //attach encoded JSON string to the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    //set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    //return response instead of outputting
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //execute the POST request

    $result = curl_exec($ch);
    $result = json_decode($result, true); // decode to associative array
    $result = $result['token'];
    $_SESSION["token"] = $result;
    $_SESSION["email"] = $_POST['email'];

    if (isset($_SESSION["token"])) {
        header('Location: tusEspacios.php');
    } else {
        echo '<script language="javascript">alert("Usuario Incorrecto");</script>';
    }

    //echo "<script>console.log('Debug Objects: " . $result . "' );</script>";

    //close cURL resource
    curl_close($ch);
}


?>