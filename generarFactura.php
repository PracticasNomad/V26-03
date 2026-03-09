<?php

require_once 'verificar_sesion_guest.php';

// Incluir la librería TCPDF
require_once('vendor/autoload.php');

// Crear una nueva instancia de TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('TheNomadapp');
$pdf->SetTitle('Factura de Reserva');
$pdf->SetSubject('Datos de la Reserva');
$pdf->SetKeywords('TCPDF, PDF, factura, reserva');

// Configurar datos del header y footer
$pdf->SetHeaderData('', 0, 'FACTURA DE RESERVA', 'TheNomadapp - Sistema de Reservas');

// Configurar fuentes del header y footer
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Configurar márgenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Configurar escala de imagen
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Configurar fuente
$pdf->SetFont('helvetica', '', 12);

// Añadir una página
$pdf->AddPage();

// Obtener datos por parámetros
$id = isset($_GET['id']) ? $_GET['id'] : 'ID no especificado';
$dia = isset($_GET['dia']) ? $_GET['dia'] : date('Y-m-d');
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : '00:00';
$end_time = isset($_GET['end_time']) ? $_GET['end_time'] : '00:00';
$space_name = isset($_GET['space_name']) ? $_GET['space_name'] : 'Espacio no especificado';
$establecimiento_nombre = isset($_GET['establecimiento_nombre']) ? $_GET['establecimiento_nombre'] : 'Establecimiento no especificado';
$dni_nomada = isset($_GET['dni_nomada']) ? $_GET['dni_nomada'] : 'DNI no especificado';
$direccion = isset($_GET['direccion']) ? $_GET['direccion'] : 'Dirección no especificada';
$price = isset($_GET['price']) ? floatval($_GET['price']) : 0.00;

// Calcular precio sin IVA (restar el 21%)
$precio_sin_iva = $price / 1.21;
$iva_amount = $price - $precio_sin_iva;

// Configurar zona horaria española
date_default_timezone_set('Europe/Madrid');

// Formatear la fecha
$fechaFormateada = date('d/m/Y', strtotime($dia));

// Crear el contenido HTML del PDF
$html = '
<style>
    .titulo1 {
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
        text-align: center;
        background-color: #ecf0f1;
        padding: 10px;
        margin-bottom: 20px;
        border: 2px solid #3498db;
    }
    
    .seccion {
        margin-bottom: 15px;
        padding: 10px;
        border-left: 4px solid #3498db;
        background-color: #f8f9fa;
    }
    
    .seccion-emisor {
        margin-bottom: 25px;
        padding: 15px;
        border-left: 4px solid #e74c3c;
        background-color: #fdf2f2;
        border: 1px solid #fadbd8;
        border-radius: 5px;
    }
    
    .etiqueta {
        font-weight: bold;
        color: #2c3e50;
        display: inline-block;
        width: 180px;
    }
    
    .valor {
        color: #34495e;
    }
    
    .seccion-precios {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 15px;
        margin-top: 20px;
        border-radius: 5px;
    }
    
    .total {
        background-color: #d1ecf1;
        border: 2px solid #bee5eb;
        padding: 15px;
        margin-top: 20px;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }
    
    .fecha-generacion {
        text-align: right;
        font-size: 10px;
        color: #6c757d;
        margin-top: 30px;
    }
    
    .horario {
        background-color: #e8f5e8;
        border-left: 4px solid #28a745;
        padding: 10px;
        margin: 15px 0;
    }
    
    .titulo-emisor {
        font-size: 14px;
        font-weight: bold;
        color: #c0392b;
        margin-bottom: 10px;
    }
</style>

<div class="titulo1">FACTURA DE RESERVA</div>

<div class="seccion-emisor">
    <div class="titulo-emisor">DATOS DEL EMISOR</div>
    <div style="margin-bottom: 8px;">
        <span class="etiqueta">Razón Social:</span>
        <span class="valor">Smartable IoT, S.L.U.</span>
    </div>
    <div>
        <span class="etiqueta">NIF:</span>
        <span class="valor">B54985536</span>
    </div>
</div>

<p>A continuación se detallan los datos de la reserva solicitada. Esta información constituye el comprobante oficial de su reserva en nuestro sistema.</p>

<div class="seccion">
    <span class="etiqueta">ID de Reserva:</span>
    <span class="valor">' . htmlspecialchars($id) . '</span>
</div>

<div class="seccion">
    <span class="etiqueta">Día de la Reserva:</span>
    <span class="valor">' . $fechaFormateada . '</span>
</div>

<div class="horario">
    <div style="margin-bottom: 8px;">
        <span class="etiqueta">Hora de Inicio:</span>
        <span class="valor">' . htmlspecialchars($start_time) . '</span>
    </div>
    <div>
        <span class="etiqueta">Hora de Fin:</span>
        <span class="valor">' . htmlspecialchars($end_time) . '</span>
    </div>
</div>

<div class="seccion">
    <span class="etiqueta">Nombre del Espacio:</span>
    <span class="valor">' . htmlspecialchars($space_name) . '</span>
</div>

<div class="seccion">
    <span class="etiqueta">Establecimiento:</span>
    <span class="valor">' . htmlspecialchars($establecimiento_nombre) . '</span>
</div>

<div class="seccion">
    <span class="etiqueta">DNI:</span>
    <span class="valor">' . htmlspecialchars($dni_nomada) . '</span>
</div>

<div class="seccion">
    <span class="etiqueta">Dirección de Factura:</span>
    <span class="valor">' . htmlspecialchars($direccion) . '</span>
</div>

<div class="seccion-precios">
    <h4 style="margin-top: 0; color: #856404;">Desglose de Precios:</h4>
    <div style="margin-bottom: 8px;">
        <span class="etiqueta">Precio sin IVA:</span>
        <span class="valor">€' . number_format($precio_sin_iva, 2, ',', '.') . '</span>
    </div>
    <div style="margin-bottom: 8px;">
        <span class="etiqueta">IVA (21%):</span>
        <span class="valor">€' . number_format($iva_amount, 2, ',', '.') . '</span>
    </div>
</div>

<div class="total">
    TOTAL A PAGAR (con IVA): €' . number_format($price, 2, ',', '.') . '
</div>

<div class="fecha-generacion">
    Documento generado el ' . date('d/m/Y H:i:s') . ' (CET - Horario Español)
</div>

<hr style="margin-top: 30px;">

<p style="font-size: 10px; color: #6c757d; text-align: center;">
    Este documento ha sido generado automáticamente por el sistema de reservas.<br>
    Para cualquier consulta, contacte con nuestro servicio de atención al cliente.
</p>
';

// Escribir el HTML al PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Configurar la respuesta HTTP para descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="factura_reserva_' . $id . '_' . date('Y-m-d') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Salida del PDF
$pdf->Output('factura_reserva_' . $id . '_' . date('Y-m-d') . '.pdf', 'D');

exit();
