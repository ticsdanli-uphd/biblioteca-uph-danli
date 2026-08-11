<?php
include '../includes/session.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /biblioteca/dashboard.php");
    exit();
}

include '../config/db.php';
include '../includes/header.php';

require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$alertScript = ""; // Variable para guardar el script de alerta

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mensaje de depuración en consola
    echo "<script>console.log('POST recibido en upload_excel.php (tesis)');</script>";

    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestDataRow();

                // Preparar sentencia para verificar duplicados (por N° Tesis)
                $existsStmt = $conn->prepare("SELECT id FROM tesis WHERE numero = ?");
                // Preparar sentencia para insertar nuevos registros
                $insertStmt = $conn->prepare("INSERT INTO tesis (numero, cuenta, alumno, carrera, titulo, anio_egresado, asesor_metodologico, asesor_tematico, cantidad, sede_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $insertedCount = 0;
                $duplicateCount = 0;

                // Se asume que la primera fila contiene los encabezados
                for ($row = 2; $row <= $highestRow; $row++) {
                    $numero               = $worksheet->getCell('A' . $row)->getValue();
                    $cuenta               = $worksheet->getCell('B' . $row)->getValue();
                    $alumno               = $worksheet->getCell('C' . $row)->getValue();
                    $carrera              = $worksheet->getCell('D' . $row)->getValue();
                    $titulo               = $worksheet->getCell('E' . $row)->getValue();
                    $anio_egresado        = $worksheet->getCell('F' . $row)->getValue();
                    $asesor_metodologico  = $worksheet->getCell('G' . $row)->getValue();
                    $asesor_tematico      = $worksheet->getCell('H' . $row)->getValue();
                    $cantidad             = $worksheet->getCell('I' . $row)->getValue();
                    $sede_id              = $worksheet->getCell('J' . $row)->getValue();

                    // Verificar si ya existe una tesis con ese número
                    $existsStmt->bind_param("s", $numero);
                    $existsStmt->execute();
                    $existsStmt->store_result();

                    if ($existsStmt->num_rows == 0) {
                        // Insertar el registro
                        $insertStmt->bind_param("sssssissii", $numero, $cuenta, $alumno, $carrera, $titulo, $anio_egresado, $asesor_metodologico, $asesor_tematico, $cantidad, $sede_id);
                        $insertStmt->execute();
                        $insertedCount++;
                    } else {
                        $duplicateCount++;
                    }
                }

                echo "<script>console.log('Insertados: {$insertedCount}, Duplicados: {$duplicateCount}');</script>";

                // Guardar el script de alerta para ejecutarlo cuando el DOM esté listo
                $alertScript = "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                      icon: 'success',
                      title: 'Subida de tesis completada',
                      html: 'Se han subido <b>{$insertedCount}</b> tesis nuevas.<br>Se omitieron <b>{$duplicateCount}</b> tesis porque ya existían.',
                      showCancelButton: true,
                      confirmButtonText: 'Volver a la lista',
                      cancelButtonText: 'Cancelar',
                      customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-secondary'
                      },
                      buttonsStyling: false
                    }).then((result) => {
                      if(result.isConfirmed) {
                        window.location.href = 'list.php';
                      }
                    });
                });
                </script>";

            } catch (Exception $e) {
                $alertScript = "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                      icon: 'error',
                      title: 'Error en la importación',
                      text: 'Error: " . addslashes($e->getMessage()) . "'
                    });
                });
                </script>";
            }
        } else {
            $alertScript = "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                      icon: 'error',
                      title: 'Formato inválido',
                      text: 'Solo se permiten archivos Excel (.xlsx, .xls).'
                    });
                });
            </script>";
        }
    } else {
        $alertScript = "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                  icon: 'error',
                  title: 'Error de carga',
                  text: 'Hubo un error al cargar el archivo.'
                });
            });
        </script>";
    }
}
?>
<div class="container mt-4">
    <h2>Subir Tesis desde Excel</h2>
    <form method="post" enctype="multipart/form-data">
       <div class="mb-3">
          <label for="excel_file" class="form-label">Seleccione el archivo Excel</label>
          <input type="file" name="excel_file" id="excel_file" class="form-control" required>
       </div>
       <button type="submit" class="btn btn-primary">Subir y Procesar</button>
       <a href="list.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php
if (!empty($alertScript)) {
    echo $alertScript;
}
include '../includes/footer.php';
?>
