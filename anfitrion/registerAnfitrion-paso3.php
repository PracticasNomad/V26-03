<?php
session_start();
$formError = '';
if (isset($_POST['siguiente'])) {
    $nombre_establecimiento = trim($_POST['nombre_establecimiento']);
    $descripcion = trim($_POST['descripcion']);
    $has_parking = isset($_POST['has_parking']) ? 1 : 0;
    $precio_parking = ($has_parking && isset($_POST['precio_parking'])) ? trim($_POST['precio_parking']) : '';
    $has_wifi = isset($_POST['has_wifi']) ? 1 : 0;
    $precio_wifi = ($has_wifi && isset($_POST['precio_wifi'])) ? trim($_POST['precio_wifi']) : '';
    $calle = trim($_POST['calle']);
    $numero = trim($_POST['numero']);
    $piso = trim($_POST['piso']);
    $codigo_postal = trim($_POST['codigo_postal']);
    $localidad = trim($_POST['localidad']);
    $provincia = trim($_POST['provincia']);
    $domicilio_facturacion_mismo = isset($_POST['domicilio_facturacion_mismo']) ? 1 : 0;
    $fact_calle = trim($_POST['fact_calle'] ?? '');
    $fact_numero = trim($_POST['fact_numero'] ?? '');
    $fact_piso = trim($_POST['fact_piso'] ?? '');
    $fact_codigo_postal = trim($_POST['fact_codigo_postal'] ?? '');
    $fact_localidad = trim($_POST['fact_localidad'] ?? '');
    $fact_provincia = trim($_POST['fact_provincia'] ?? '');

    $errors = [];

    // Validación Nombre Establecimiento
    if (empty($nombre_establecimiento)) {
        $errors[] = 'El nombre del establecimiento es obligatorio.';
    } elseif (!preg_match('/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,-]{5,60}$/u', $nombre_establecimiento)) {
        $errors[] = 'El nombre del establecimiento debe tener entre 5 y 60 caracteres.';
    }

    if (empty($descripcion))
        $errors[] = 'La descripción es obligatoria.';

    // Validación Calle
    if (empty($calle)) {
        $errors[] = 'La calle es obligatoria.';
    } elseif (!preg_match('/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,\/-]{5,60}$/u', $calle)) {
        $errors[] = 'La calle debe tener entre 5 y 60 caracteres.';
    }

    // Validación Número de portal
    if (empty($numero)) {
        $errors[] = 'El número es obligatorio.';
    } elseif (!preg_match('/^[0-9]{1,3}[a-zA-Z]?$/', $numero)) {
        $errors[] = 'El número debe contener como máximo 3 números y una posible letra (ej. 12A, 123, 5B).';
    }

    // Código Postal
    if (empty($codigo_postal)) {
        $errors[] = 'El código postal es obligatorio.';
    } elseif (!preg_match('/^[0-9]{5}$/', $codigo_postal)) {
        $errors[] = 'El código postal debe contener 5 dígitos.';
    }

    if (empty($localidad))
        $errors[] = 'La localidad es obligatoria.';
    if (empty($provincia))
        $errors[] = 'La provincia es obligatoria.';

    if (!$domicilio_facturacion_mismo) {
        if (empty($fact_calle)) {
            $errors[] = 'La calle del domicilio de facturación es obligatoria.';
        } elseif (!preg_match('/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,\/-]{5,60}$/u', $fact_calle)) {
            $errors[] = 'La calle del domicilio de facturación debe tener entre 5 y 60 caracteres.';
        }

        if (empty($fact_numero)) {
            $errors[] = 'El número del domicilio de facturación es obligatorio.';
        } elseif (!preg_match('/^[0-9]{1,3}[a-zA-Z]?$/', $fact_numero)) {
            $errors[] = 'El número del domicilio de facturación no es válido.';
        }

        if (empty($fact_codigo_postal)) {
            $errors[] = 'El código postal del domicilio de facturación es obligatorio.';
        } elseif (!preg_match('/^[0-9]{5}$/', $fact_codigo_postal)) {
            $errors[] = 'El código postal del domicilio de facturación debe contener 5 dígitos.';
        }

        if (empty($fact_localidad)) {
            $errors[] = 'La localidad del domicilio de facturación es obligatoria.';
        }

        if (empty($fact_provincia)) {
            $errors[] = 'La provincia del domicilio de facturación es obligatoria.';
        }
    }

    if ($has_parking && ($precio_parking === '' || !is_numeric($precio_parking) || floatval($precio_parking) < 0))
        $errors[] = 'Si tiene parking, debe indicar un precio válido.';
    if ($has_wifi && ($precio_wifi === '' || !is_numeric($precio_wifi) || floatval($precio_wifi) < 0))
        $errors[] = 'Si ofrece WiFi, debe indicar un precio válido.';

    if (!empty($errors)) {
        $formError = implode('<br>', $errors);
    } else {
        $_SESSION['establecimiento'] = compact(
            'nombre_establecimiento',
            'descripcion',
            'has_parking',
            'precio_parking',
            'has_wifi',
            'precio_wifi',
            'calle',
            'numero',
            'piso',
            'codigo_postal',
            'localidad',
            'provincia'
        );
        $_SESSION['establecimiento']['nombre'] = $nombre_establecimiento;
        $_SESSION['establecimiento']['descripcion'] = $descripcion;
        $_SESSION['establecimiento']['has_parking'] = $has_parking;
        $_SESSION['establecimiento']['precio_parking'] = $precio_parking;
        $_SESSION['establecimiento']['has_wifi'] = $has_wifi;
        $_SESSION['establecimiento']['precio_wifi'] = $precio_wifi;
        $_SESSION['establecimiento']['calle'] = $calle;
        $_SESSION['establecimiento']['numero'] = $numero;
        $_SESSION['establecimiento']['piso'] = $piso;
        $_SESSION['establecimiento']['codigo_postal'] = $codigo_postal;
        $_SESSION['establecimiento']['localidad'] = $localidad;
        $_SESSION['establecimiento']['provincia'] = $provincia;

        $_SESSION['host']['domicilio_facturacion_mismo'] = $domicilio_facturacion_mismo;
        if ($domicilio_facturacion_mismo) {
            $_SESSION['host']['fact_calle'] = $calle;
            $_SESSION['host']['fact_numero'] = $numero;
            $_SESSION['host']['fact_piso'] = $piso;
            $_SESSION['host']['fact_codigo_postal'] = $codigo_postal;
            $_SESSION['host']['fact_localidad'] = $localidad;
            $_SESSION['host']['fact_provincia'] = $provincia;
        } else {
            $_SESSION['host']['fact_calle'] = $fact_calle;
            $_SESSION['host']['fact_numero'] = $fact_numero;
            $_SESSION['host']['fact_piso'] = $fact_piso;
            $_SESSION['host']['fact_codigo_postal'] = $fact_codigo_postal;
            $_SESSION['host']['fact_localidad'] = $fact_localidad;
            $_SESSION['host']['fact_provincia'] = $fact_provincia;
        }

        // --- ACTUALIZAR BORRADOR EN BASE DE DATOS ---
        if (isset($_SESSION['host']['email'])) {
            require_once '../vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->safeLoad();

            $emailUpd = $_SESSION['host']['email'];
            $urlUpd = 'http://' . $_ENV['SERVER_IP'] . ':' . $_ENV['DATABASE_PORT'] . '/rest/v1/registros_abandonados?email=eq.' . urlencode($emailUpd);
            $chUpd = curl_init($urlUpd);
            $dataUpd = [
                'paso' => 4,
                'datos_sesion' => json_encode($_SESSION)
            ];
            curl_setopt($chUpd, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($chUpd, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chUpd, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $_ENV['DATABASE_APIKEY']
            ]);
            curl_setopt($chUpd, CURLOPT_POSTFIELDS, json_encode($dataUpd));
            curl_exec($chUpd);
            curl_close($chUpd);
        }
        // --- FIN ACTUALIZAR BORRADOR ---

        header("Location: registerAnfitrion-paso4.php");
        exit();
    }
}

$mismoDomicilioFacturacion = true;
if (isset($_SESSION['host']['domicilio_facturacion_mismo'])) {
    $mismoDomicilioFacturacion = (bool)$_SESSION['host']['domicilio_facturacion_mismo'];
}
?>
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
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet'>
    <link rel="icon" href="../favicon-color.png">
    <link rel="icon" href="../favicon-negro.png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="../favicon-color.png" media="(prefers-color-scheme: dark)">
    <title>Datos de tu establecimiento</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .contenedorAlta {
            max-width: 700px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }

        .progress-container {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            width: 40%;
            background-color: #28a745;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            display: none;
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

        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            width: 500px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 12px 16px;
            position: absolute;
            z-index: 1000;
            top: 150%;
            left: 50%;
            transform: translateX(-50%);
            transition: opacity 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            line-height: 1.5;
            font-weight: normal;
        }

        .tooltip-text::after {
            content: "";
            position: absolute;
            bottom: 100%;
            left: 50%;
            margin-left: -10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent transparent #333 transparent;
        }

        .tooltip-text.visible {
            visibility: visible;
            opacity: 1;
        }

        #imgInfo {
            cursor: pointer;
            transition: transform 0.2s;
            margin-left: 5px;
        }

        #imgInfo:hover {
            transform: scale(1.1);
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .loading-spinner {
            display: none;
            margin-left: 10px;
            vertical-align: middle;
        }

        .success-indicator {
            display: none;
            color: #28a745;
            margin-left: 10px;
            vertical-align: middle;
        }

        .error-indicator {
            display: none;
            color: #dc3545;
            margin-left: 10px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .tooltip-text {
                width: 350px;
                font-size: 13px;
            }

            .register-title {
                display: block;
                margin-bottom: 8px;
            }

            .info-icon-mobile {
                display: block;
                margin: 8px auto 0;
                text-align: center;
            }

            .tooltip-container.mobile {
                display: block;
                text-align: center;
            }

            .tooltip-text::after {
                left: 50%;
            }
        }
    </style>
</head>

<body>
    <div class="contenedorAlta">
        <div class="col-12 text-center py-3 fw-bold h4">
            <div class="d-none d-md-block">
                <p>Registra tu establecimiento <span class="tooltip-container"><img src="../img/informacion.png"
                            alt="Información" id="imgInfo" width="24px" height="24px"><span id="masInfo"
                            class="tooltip-text">Un <b>establecimiento</b> es el negocio o lugar físico donde se
                            encuentran uno o varios espacios de trabajo y donde se ofrece servicios a nómadas
                            digitales.</span></span></p>
            </div>
            <div class="d-block d-md-none">
                <p class="register-title">Registra tu establecimiento</p>
                <span class="tooltip-container mobile"><img src="../img/informacion.png" alt="Información"
                        id="imgInfoMobile" width="24px" height="24px"><span id="masInfoMobile" class="tooltip-text">Un
                        <b>establecimiento</b> es el negocio o lugar físico donde se encuentran uno o varios espacios de
                        trabajo y donde se ofrece servicios a nómadas digitales.</span></span>
            </div>
        </div>
        <div class="col-12 text-center mb-3">
            <div class="logo-container"><img src="../img/establecimiento.png" width="80" alt="Logo Establecimiento">
            </div>
        </div>
        <div class="col-12 text-center h4 mb-4 fw-bold">Datos del establecimiento</div>

        <div class="alert alert-danger" id="error-message" <?php echo !empty($formError) ? 'style="display:block"' : ''; ?>><i class="fas fa-exclamation-circle me-2"></i> <span id="error-text"><?php echo $formError; ?></span>
        </div>
        <form method="post" action="" class="container" id="establecimientoForm">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="nombre_establecimiento" class="form-label fw-bold">Nombre del establecimiento *</label>
                    <input type="text" class="form-control" id="nombre_establecimiento" name="nombre_establecimiento"
                        required minlength="5" maxlength="60" pattern="[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,-]{5,60}"
                        placeholder="Nombre de tu establecimiento"
                        value="<?php echo isset($_SESSION['establecimiento']['nombre']) ? htmlspecialchars($_SESSION['establecimiento']['nombre']) : ''; ?>">
                </div>
                <div class="col-md-12">
                    <label for="descripcion" class="form-label fw-bold">Descripción *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required
                        placeholder="Describe tu establecimiento"><?php echo isset($_SESSION['establecimiento']['descripcion']) ? htmlspecialchars($_SESSION['establecimiento']['descripcion']) : ''; ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">¿Dispone de parking?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_parking" name="has_parking" <?php echo (isset($_SESSION['establecimiento']['has_parking']) && $_SESSION['establecimiento']['has_parking'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="has_parking">Sí</label>
                    </div>
                </div>
                <div class="col-md-6" id="parking_price_container"
                    style="<?php echo (isset($_SESSION['establecimiento']['has_parking']) && $_SESSION['establecimiento']['has_parking'] == 1) ? '' : 'display:none'; ?>">
                    <label for="precio_parking" class="form-label fw-bold">Precio del parking (€/día) *</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="precio_parking"
                        name="precio_parking" placeholder="0.00"
                        value="<?php echo isset($_SESSION['establecimiento']['precio_parking']) ? htmlspecialchars($_SESSION['establecimiento']['precio_parking']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">¿Ofrece WiFi?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_wifi" name="has_wifi" <?php echo (isset($_SESSION['establecimiento']['has_wifi']) && $_SESSION['establecimiento']['has_wifi'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="has_wifi">Sí</label>
                    </div>
                </div>
                <div class="col-md-6" id="wifi_price_container"
                    style="<?php echo (isset($_SESSION['establecimiento']['has_wifi']) && $_SESSION['establecimiento']['has_wifi'] == 1) ? '' : 'display:none'; ?>">
                    <label for="precio_wifi" class="form-label fw-bold">Precio del WiFi (€/hora) *</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="precio_wifi" name="precio_wifi"
                        placeholder="0.00"
                        value="<?php echo isset($_SESSION['establecimiento']['precio_wifi']) ? htmlspecialchars($_SESSION['establecimiento']['precio_wifi']) : ''; ?>">
                </div>
                <div class="col-md-8">
                    <label for="calle" class="form-label fw-bold">Calle *</label>
                    <input type="text" class="form-control" id="calle" name="calle" required minlength="5"
                        maxlength="60" pattern="[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,\/-]{5,60}" placeholder="Nombre de la calle"
                        value="<?php echo isset($_SESSION['establecimiento']['calle']) ? htmlspecialchars($_SESSION['establecimiento']['calle']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="numero" class="form-label fw-bold">Número *</label>
                    <input type="text" class="form-control" id="numero" name="numero" required
                        pattern="[0-9]{1,3}[a-zA-Z]?" maxlength="4" placeholder="Ej: 12A"
                        value="<?php echo isset($_SESSION['establecimiento']['numero']) ? htmlspecialchars($_SESSION['establecimiento']['numero']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="piso" class="form-label fw-bold">Piso (opcional)</label>
                    <input type="text" class="form-control" id="piso" name="piso"
                        placeholder="Piso, puerta, escalera..."
                        value="<?php echo isset($_SESSION['establecimiento']['piso']) ? htmlspecialchars($_SESSION['establecimiento']['piso']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="codigo_postal" class="form-label fw-bold">Código Postal *</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" required
                            placeholder="Código postal" maxlength="5" pattern="[0-9]{5}"
                            value="<?php echo isset($_SESSION['establecimiento']['codigo_postal']) ? htmlspecialchars($_SESSION['establecimiento']['codigo_postal']) : ''; ?>">
                        <span class="loading-spinner" id="cp-loading"><i class="fas fa-spinner fa-spin"></i></span>
                        <span class="success-indicator" id="cp-success"><i class="fas fa-check-circle"></i></span>
                        <span class="error-indicator" id="cp-error"><i class="fas fa-exclamation-circle"></i></span>
                    </div>
                    <small id="cp-feedback" class="form-text text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label for="localidad" class="form-label fw-bold">Localidad *</label>
                    <input type="text" class="form-control" id="localidad" name="localidad" required
                        placeholder="Tu localidad"
                        value="<?php echo isset($_SESSION['establecimiento']['localidad']) ? htmlspecialchars($_SESSION['establecimiento']['localidad']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="provincia" class="form-label fw-bold">Provincia *</label>
                    <select class="form-select" id="provincia" name="provincia" required>
                        <option value="" disabled selected>Selecciona una provincia</option>
                        <?php
                        $provincias = ['Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca', 'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares', 'Jaén', 'La Coruña', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Madrid', 'Málaga', 'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'];
                        foreach ($provincias as $prov) {
                            $selected = (isset($_SESSION['establecimiento']['provincia']) && $_SESSION['establecimiento']['provincia'] == $prov) ? 'selected' : '';
                            echo "<option value=\"$prov\"$selected>$prov</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="domicilio_facturacion_mismo" name="domicilio_facturacion_mismo" <?php echo $mismoDomicilioFacturacion ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="domicilio_facturacion_mismo">Este es tu domicilio de facturación</label>
                    </div>
                </div>

                <div class="col-12" id="billingAddressFields" style="<?php echo $mismoDomicilioFacturacion ? 'display:none;' : ''; ?>">
                    <div class="row g-3">
                        <div class="col-12 mt-2">
                            <div class="alert alert-info" style="display:block; margin-bottom:0;">
                                <i class="fas fa-info-circle me-2"></i>Indica tu domicilio de facturación porque no coincide con el del establecimiento.
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label for="fact_calle" class="form-label fw-bold">Calle de facturación *</label>
                            <input type="text" class="form-control" id="fact_calle" name="fact_calle" minlength="5" maxlength="60" pattern="[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,\/-]{5,60}" placeholder="Nombre de la calle" value="<?php echo isset($_SESSION['host']['fact_calle']) ? htmlspecialchars($_SESSION['host']['fact_calle']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="fact_numero" class="form-label fw-bold">Número de facturación *</label>
                            <input type="text" class="form-control" id="fact_numero" name="fact_numero" pattern="[0-9]{1,3}[a-zA-Z]?" maxlength="4" placeholder="Ej: 12A" value="<?php echo isset($_SESSION['host']['fact_numero']) ? htmlspecialchars($_SESSION['host']['fact_numero']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fact_piso" class="form-label fw-bold">Piso de facturación (opcional)</label>
                            <input type="text" class="form-control" id="fact_piso" name="fact_piso" placeholder="Piso, puerta, escalera..." value="<?php echo isset($_SESSION['host']['fact_piso']) ? htmlspecialchars($_SESSION['host']['fact_piso']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fact_codigo_postal" class="form-label fw-bold">Código Postal de facturación *</label>
                            <input type="text" class="form-control" id="fact_codigo_postal" name="fact_codigo_postal" maxlength="5" pattern="[0-9]{5}" placeholder="Código postal" value="<?php echo isset($_SESSION['host']['fact_codigo_postal']) ? htmlspecialchars($_SESSION['host']['fact_codigo_postal']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fact_localidad" class="form-label fw-bold">Localidad de facturación *</label>
                            <input type="text" class="form-control" id="fact_localidad" name="fact_localidad" placeholder="Tu localidad" value="<?php echo isset($_SESSION['host']['fact_localidad']) ? htmlspecialchars($_SESSION['host']['fact_localidad']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fact_provincia" class="form-label fw-bold">Provincia de facturación *</label>
                            <select class="form-select" id="fact_provincia" name="fact_provincia">
                                <option value="" disabled <?php echo empty($_SESSION['host']['fact_provincia']) ? 'selected' : ''; ?>>Selecciona una provincia</option>
                                <?php
                                foreach ($provincias as $prov) {
                                    $selected = (isset($_SESSION['host']['fact_provincia']) && $_SESSION['host']['fact_provincia'] == $prov) ? 'selected' : '';
                                    echo "<option value=\"$prov\"$selected>$prov</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="progress-container mt-4">
                <div class="progress-bar"></div>
            </div>
            <div class="container mt-4">
                <div class="row">
                    <div class="col-6 text-end"><button class="btn btn-cancel rounded-pill" type="button"
                            onclick="location.href='registerAnfitrion-paso2.php'">Anterior</button></div>
                    <div class="col-6"><button type="submit" name="siguiente" id="btnSiguiente"
                            class="btn btn-success rounded-pill">Siguiente</button></div>
                </div>
            </div>
        </form>
        <div class="container-fluid p-3">
            <div class="row text-center">
                <div class="col-12">Paso 3 de 6</div>
            </div>
        </div>
        <script>
            const MAPBOX_ACCESS_TOKEN = 'pk.eyJ1IjoiYW5kcnplamJhbmFzIiwiYSI6ImNrcHdrZXIyYTAyZWkyb3AwNGtpbmtrbXYifQ.PN_iZ4Mh08-V5EXHAHpCSg';

            $(document).ready(function() {
                // Función para mostrar/ocultar el precio del parking
                function toggleParkingPrice() {
                    const hasParkingChecked = $("#has_parking").is(":checked");

                    if (hasParkingChecked) {
                        $("#parking_price_container").show();
                        $("#precio_parking").prop("required", true);
                    } else {
                        $("#parking_price_container").hide();
                        $("#precio_parking").prop("required", false);
                        $("#precio_parking").val("");
                    }
                }

                // Función para mostrar/ocultar el precio del WiFi
                function toggleWifiPrice() {
                    const hasWifiChecked = $("#has_wifi").is(":checked");

                    if (hasWifiChecked) {
                        $("#wifi_price_container").show();
                        $("#precio_wifi").prop("required", true);
                    } else {
                        $("#wifi_price_container").hide();
                        $("#precio_wifi").prop("required", false);
                        $("#precio_wifi").val("");
                    }
                }

                function toggleBillingAddressFields() {
                    const esMismo = $("#domicilio_facturacion_mismo").is(":checked");
                    const billingFields = [
                        "#fact_calle",
                        "#fact_numero",
                        "#fact_codigo_postal",
                        "#fact_localidad",
                        "#fact_provincia"
                    ];

                    if (esMismo) {
                        $("#billingAddressFields").hide();
                        billingFields.forEach(function(selector) {
                            $(selector).prop("required", false);
                        });
                    } else {
                        $("#billingAddressFields").show();
                        billingFields.forEach(function(selector) {
                            $(selector).prop("required", true);
                        });
                    }
                }

                // Función para configurar la búsqueda automática de código postal
                function setupPostalCodeLookup() {
                    let timeout;

                    $("#codigo_postal").on("input", function() {
                        clearTimeout(timeout);
                        $("#cp-loading, #cp-success, #cp-error").hide();
                        $("#cp-feedback").text("");

                        const postalCode = $(this).val().trim();

                        if (postalCode.length === 5 && /^\d{5}$/.test(postalCode)) {
                            $("#cp-loading").show();
                            timeout = setTimeout(function() {
                                lookupPostalCode(postalCode);
                            }, 1000);
                        }
                    });

                    // Verificar si ya hay un código postal al cargar la página
                    const existingPostalCode = $("#codigo_postal").val().trim();
                    if (existingPostalCode.length === 5 && /^\d{5}$/.test(existingPostalCode)) {
                        const existingLocalidad = $("#localidad").val().trim();
                        if (!existingLocalidad) {
                            $("#cp-loading").show();
                            lookupPostalCode(existingPostalCode);
                        }
                    }
                }

                // Función para buscar información del código postal usando Mapbox API
                function lookupPostalCode(postalCode) {
                    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${postalCode}.json?country=es&types=postcode&access_token=${MAPBOX_ACCESS_TOKEN}`;

                    $.ajax({
                        url: url,
                        method: "GET",
                        dataType: "json",
                        success: function(data) {
                            $("#cp-loading").hide();

                            if (data.features && data.features.length > 0) {
                                const placeName = data.features[0].place_name.split(",");

                                if (placeName.length >= 2) {
                                    const localidad = placeName[1].trim();
                                    $("#localidad").val(localidad);
                                    $("#cp-success").show();
                                    $("#cp-feedback")
                                        .text("Localidad encontrada: " + localidad)
                                        .addClass("text-success")
                                        .removeClass("text-danger");

                                    // Intentar autoseleccionar la provincia si está disponible
                                    if (placeName.length >= 3) {
                                        $("#provincia option").each(function() {
                                            if ($(this).text().toLowerCase() === placeName[2].trim().replace("España", "").trim().toLowerCase()) {
                                                $(this).prop("selected", true);
                                                return false;
                                            }
                                        });
                                    }
                                } else {
                                    showPostalCodeError("No se pudo extraer la localidad de los resultados.");
                                }
                            } else {
                                showPostalCodeError("No se encontró ninguna localidad para este código postal.");
                            }
                        },
                        error: function(xhr, status, error) {
                            $("#cp-loading").hide();
                            showPostalCodeError("Error al buscar la localidad: " + error);
                        }
                    });
                }

                // Función para mostrar errores de código postal
                function showPostalCodeError(message) {
                    $("#cp-error").show();
                    $("#cp-feedback")
                        .text(message)
                        .addClass("text-danger")
                        .removeClass("text-success");
                }

                // Inicializar funciones al cargar la página
                toggleParkingPrice();
                toggleWifiPrice();
                toggleBillingAddressFields();

                // Event listeners
                $("#has_parking").change(function() {
                    toggleParkingPrice();
                });

                $("#has_wifi").change(function() {
                    toggleWifiPrice();
                });

                $("#domicilio_facturacion_mismo").change(function() {
                    toggleBillingAddressFields();
                });

                setupPostalCodeLookup();
            });

            // Validación del formulario
            $(document).ready(function() {
                $("#establecimientoForm").submit(function(e) {
                    // Obtener valores del formulario
                    const nombreEstablecimiento = $("#nombre_establecimiento").val().trim();
                    const descripcion = $("#descripcion").val().trim();
                    const hasParking = $("#has_parking").is(":checked");
                    const precioParking = $("#precio_parking").val().trim();
                    const hasWifi = $("#has_wifi").is(":checked");
                    const precioWifi = $("#precio_wifi").val().trim();
                    const calle = $("#calle").val().trim();
                    const numero = $("#numero").val().trim();
                    const codigoPostal = $("#codigo_postal").val().trim();
                    const localidad = $("#localidad").val().trim();
                    const provincia = $("#provincia").val();
                    const esMismoDomicilioFacturacion = $("#domicilio_facturacion_mismo").is(":checked");
                    const factCalle = $("#fact_calle").val().trim();
                    const factNumero = $("#fact_numero").val().trim();
                    const factCodigoPostal = $("#fact_codigo_postal").val().trim();
                    const factLocalidad = $("#fact_localidad").val().trim();
                    const factProvincia = $("#fact_provincia").val();

                    let hasErrors = false;
                    let errorMessages = [];

                    // Ocultar mensajes de error previos
                    $("#error-message").hide();
                    $("#error-text").text("");

                    // Expresiones regulares
                    const regexTexto = /^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,-]{5,60}$/;
                    const regexTextoDireccion = /^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ.,\/-]{5,60}$/;
                    const regexNumero = /^[0-9]{1,3}[a-zA-Z]?$/;

                    // Validaciones
                    if (!nombreEstablecimiento) {
                        hasErrors = true;
                        errorMessages.push("El nombre del establecimiento es obligatorio.");
                    } else if (!regexTexto.test(nombreEstablecimiento)) {
                        hasErrors = true;
                        errorMessages.push("El nombre del establecimiento debe tener entre 5 y 60 caracteres válidos.");
                    }

                    if (!descripcion) {
                        hasErrors = true;
                        errorMessages.push("La descripción es obligatoria.");
                    }

                    // Validaciones Calle
                    if (!calle) {
                        hasErrors = true;
                        errorMessages.push("La calle es obligatoria.");
                    } else if (!regexTextoDireccion.test(calle)) {
                        hasErrors = true;
                        errorMessages.push("La calle debe tener entre 5 y 60 caracteres válidos.");
                    }

                    // Validaciones Número
                    if (!numero) {
                        hasErrors = true;
                        errorMessages.push("El número es obligatorio.");
                    } else if (!regexNumero.test(numero)) {
                        hasErrors = true;
                        errorMessages.push("El número debe contener como máximo 3 números y una posible letra (ej. 12A, 123).");
                    }

                    if (!codigoPostal) {
                        hasErrors = true;
                        errorMessages.push("El código postal es obligatorio.");
                    } else if (!/^[0-9]{5}$/.test(codigoPostal)) {
                        hasErrors = true;
                        errorMessages.push("El código postal debe contener 5 dígitos.");
                    }

                    if (!localidad) {
                        hasErrors = true;
                        errorMessages.push("La localidad es obligatoria.");
                    }

                    if (!provincia) {
                        hasErrors = true;
                        errorMessages.push("La provincia es obligatoria.");
                    }

                    if (!esMismoDomicilioFacturacion) {
                        if (!factCalle) {
                            hasErrors = true;
                            errorMessages.push("La calle del domicilio de facturación es obligatoria.");
                        } else if (!regexTextoDireccion.test(factCalle)) {
                            hasErrors = true;
                            errorMessages.push("La calle del domicilio de facturación debe tener entre 5 y 60 caracteres válidos.");
                        }

                        if (!factNumero) {
                            hasErrors = true;
                            errorMessages.push("El número del domicilio de facturación es obligatorio.");
                        } else if (!regexNumero.test(factNumero)) {
                            hasErrors = true;
                            errorMessages.push("El número del domicilio de facturación no es válido.");
                        }

                        if (!factCodigoPostal) {
                            hasErrors = true;
                            errorMessages.push("El código postal del domicilio de facturación es obligatorio.");
                        } else if (!/^[0-9]{5}$/.test(factCodigoPostal)) {
                            hasErrors = true;
                            errorMessages.push("El código postal del domicilio de facturación debe contener 5 dígitos.");
                        }

                        if (!factLocalidad) {
                            hasErrors = true;
                            errorMessages.push("La localidad del domicilio de facturación es obligatoria.");
                        }

                        if (!factProvincia) {
                            hasErrors = true;
                            errorMessages.push("La provincia del domicilio de facturación es obligatoria.");
                        }
                    }

                    // Validar precio de parking si está marcado
                    if (hasParking && (precioParking === "" || isNaN(precioParking) || parseFloat(precioParking) < 0)) {
                        hasErrors = true;
                        errorMessages.push("Si tiene parking, debe indicar un precio válido.");
                    }

                    // Validar precio de WiFi si está marcado
                    if (hasWifi && (precioWifi === "" || isNaN(precioWifi) || parseFloat(precioWifi) < 0)) {
                        hasErrors = true;
                        errorMessages.push("Si ofrece WiFi, debe indicar un precio válido.");
                    }

                    // Mostrar errores si los hay
                    if (hasErrors) {
                        e.preventDefault(); // Evitamos que el formulario se envíe
                        $("#error-message").show();
                        // Unimos los errores con saltos de línea y viñetas para que se vea más limpio
                        $("#error-text").html("<ul class='mb-0'><li>" + errorMessages.join("</li><li>") + "</li></ul>");
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        }); // Subimos para que el usuario vea el error
                        return false;
                    }

                    return true;
                });
            });

            function updateSessionCoordinates(lat, lng) {
                $.ajax({
                    url: 'update_coordinates.php',
                    method: 'POST',
                    data: {
                        lat: lat,
                        lng: lng
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            console.log('Coordenadas actualizadas en la sesión');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al actualizar coordenadas:', error);
                    }
                });
            }

            function updateCoordinateInputs(lat, lng) {
                document.getElementById('latitud').value = lat.toFixed(6);
                document.getElementById('longitud').value = lng.toFixed(6);
                updateSessionCoordinates(lat, lng);
            }
        </script>
</body>

</html>