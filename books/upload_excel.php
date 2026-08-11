<?php
include '../includes/session.php';
include '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$error = '';
$inserted = 0;
$updated = 0;
$duplicados = 0;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Seleccione un archivo Excel válido.';
    } else {
        $extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['xlsx','xls'], true)) {
            $error = 'Solo se permiten archivos .xlsx o .xls.';
        } else {
            try {
                $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestDataRow();

                $headersEsperados = [
                    'Código','Dewey','Clasificación','Nombre','Autor(es)','Editorial',
                    'Edición','Año','ISBN','Estado','Ubicación','Fecha de ingreso',
                    'Idioma','Carrera','Cantidad','Sede ID'
                ];

                $headersExcel = [];
                for ($col=1; $col<=16; $col++) {
                    $headersExcel[] = trim((string)$sheet->getCellByColumnAndRow($col,1)->getValue());
                }

                $normalizar = function($text) {
                    $text = trim((string)$text);
                    $text = mb_strtolower($text, 'UTF-8');
                    $text = str_replace(
                        ['á','é','í','ó','ú','ü','ñ'],
                        ['a','e','i','o','u','u','n'],
                        $text
                    );
                    return preg_replace('/\s+/', ' ', $text);
                };

                $encabezadosValidos = true;
                foreach ($headersEsperados as $i => $header) {
                    if ($normalizar($headersExcel[$i]) !== $normalizar($header)) {
                        $encabezadosValidos = false;
                        break;
                    }
                }

                if (!$encabezadosValidos) {
                    throw new Exception(
                        'La plantilla no corresponde a la nueva estructura. Descargue "Plantilla Excel" y utilícela.'
                    );
                }

                $findCode = $conn->prepare("SELECT id FROM bibliografia WHERE codigo=? AND sede_id=4");
                $findCareer = $conn->prepare("SELECT id FROM carreras WHERE nombre=? LIMIT 1");

                $insert = $conn->prepare("INSERT INTO bibliografia
                    (codigo,dewey,clasificacion,nombre,autores,editorial,edicion,anio,isbn,estado,ubicacion,fecha_ingreso,idioma,carrera_id,cantidad,sede_id,ingresado_por,modificado_por)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

                $usuario_id = (int)$_SESSION['user_id'];

                for ($row=2; $row<=$highestRow; $row++) {
                    $codigo = trim((string)$sheet->getCellByColumnAndRow(1,$row)->getValue());
                    if ($codigo === '') {
                        continue;
                    }

                    $dewey = trim((string)$sheet->getCellByColumnAndRow(2,$row)->getValue());
                    $clasificacion = trim((string)$sheet->getCellByColumnAndRow(3,$row)->getValue());
                    $nombre = trim((string)$sheet->getCellByColumnAndRow(4,$row)->getValue());
                    $autores = trim((string)$sheet->getCellByColumnAndRow(5,$row)->getValue());
                    $editorial = trim((string)$sheet->getCellByColumnAndRow(6,$row)->getValue());
                    $edicion = trim((string)$sheet->getCellByColumnAndRow(7,$row)->getValue());
                    $anioRaw = $sheet->getCellByColumnAndRow(8,$row)->getValue();
                    $anio = $anioRaw !== '' && $anioRaw !== null ? (int)$anioRaw : null;
                    $isbn = trim((string)$sheet->getCellByColumnAndRow(9,$row)->getValue());
                    $estado = trim((string)$sheet->getCellByColumnAndRow(10,$row)->getValue());
                    $ubicacion = trim((string)$sheet->getCellByColumnAndRow(11,$row)->getValue());
                    $fechaRaw = $sheet->getCellByColumnAndRow(12,$row)->getValue();
                    $idioma = trim((string)$sheet->getCellByColumnAndRow(13,$row)->getValue());
                    $carreraNombre = trim((string)$sheet->getCellByColumnAndRow(14,$row)->getValue());
                    $cantidad = (int)$sheet->getCellByColumnAndRow(15,$row)->getValue();
                    // La sede recibida del Excel se ignora: este módulo es solo Danlí.
                    $sede_id = 4;

                    if ($nombre === '') {
                        $errores[] = "Fila $row: falta el nombre.";
                        continue;
                    }

                    if ($cantidad < 1) {
                        $cantidad = 1;
                    }

                    if ($estado === '') {
                        $estado = 'Disponible';
                    }

                    if (!in_array($estado, ['Disponible','Prestado','Deteriorado','Baja'], true)) {
                        $errores[] = "Fila $row: estado '$estado' no válido.";
                        continue;
                    }

                    $fecha_ingreso = null;
                    if ($fechaRaw !== '' && $fechaRaw !== null) {
                        if (is_numeric($fechaRaw)) {
                            try {
                                $fecha_ingreso = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaRaw)->format('Y-m-d');
                            } catch (Throwable $e) {
                                $fecha_ingreso = null;
                            }
                        } else {
                            $timestamp = strtotime((string)$fechaRaw);
                            if ($timestamp !== false) {
                                $fecha_ingreso = date('Y-m-d', $timestamp);
                            }
                        }
                    }

                    $carrera_id = null;
                    if ($carreraNombre !== '') {
                        $findCareer->bind_param('s', $carreraNombre);
                        $findCareer->execute();
                        $careerResult = $findCareer->get_result();

                        if ($careerResult->num_rows > 0) {
                            $carrera_id = (int)$careerResult->fetch_assoc()['id'];
                        } else {
                            $errores[] = "Fila $row: la carrera '$carreraNombre' no existe. Se dejará sin carrera.";
                        }
                    }

                    $findCode->bind_param('s', $codigo);
                    $findCode->execute();
                    $existing = $findCode->get_result();

                    if ($existing->num_rows > 0) {
                        $duplicados++;
                        continue;
                    }

                    $insert->bind_param(
                        'sssssssisssssiiiii',
                        $codigo,$dewey,$clasificacion,$nombre,$autores,$editorial,$edicion,
                        $anio,$isbn,$estado,$ubicacion,$fecha_ingreso,$idioma,$carrera_id,
                        $cantidad,$sede_id,$usuario_id,$usuario_id
                    );

                    if ($insert->execute()) {
                        $inserted++;
                    } else {
                        $errores[] = "Fila $row: " . $insert->error;
                    }
                }

                $findCode->close();
                $findCareer->close();
                $insert->close();

                $mensaje = "Importación terminada. Nuevos: $inserted. Duplicados: $duplicados.";
                if ($errores) {
                    $mensaje .= " Errores/avisos: " . count($errores) . ".";
                }
                $_SESSION['import_msg'] = $mensaje;
                $_SESSION['import_errors'] = $errores;

                header('Location: upload_excel.php');
                exit();

            } catch (Throwable $e) {
                $error = 'Error al procesar el Excel: ' . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';

$importMsg = $_SESSION['import_msg'] ?? '';
$importErrors = $_SESSION['import_errors'] ?? [];
unset($_SESSION['import_msg'], $_SESSION['import_errors']);
?>

<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-file-excel me-2"></i>Importar Libros desde Excel</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Importante:</strong> este importador registra los libros únicamente en
                <strong>Danlí (Sede ID 4)</strong>. La columna Sede ID del Excel se ignora.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($importMsg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($importMsg) ?></div>
            <?php endif; ?>

            <?php if ($importErrors): ?>
                <div class="alert alert-warning">
                    <strong>Observaciones:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach (array_slice($importErrors,0,20) as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($importErrors) > 20): ?>
                        <small>Se muestran los primeros 20 mensajes.</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Archivo Excel</label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i>Subir y Procesar
                    </button>
                    <a href="download_template.php" class="btn btn-success">
                        <i class="fas fa-download me-1"></i>Descargar Plantilla
                    </a>
                    <a href="list.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
