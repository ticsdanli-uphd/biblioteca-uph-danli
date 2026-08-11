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

$alertScript = ""; // Variable para almacenar el script de alerta

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0){
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileExtension === 'xlsx' || $fileExtension === 'xls'){
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestDataRow();

                // Preparar sentencias
                // Verificar si el email ya existe (usamos el email como username)
                $existsStmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
                // Insertar en la tabla "alumnos"
                $insertAlumnoStmt = $conn->prepare("INSERT INTO alumnos (nombre, telefono, email) VALUES (?, ?, ?)");
                // Insertar en la tabla "usuarios" con rol 'usuario'
                $insertUserStmt = $conn->prepare("INSERT INTO usuarios (username, password, role, sede_id) VALUES (?, ?, 'usuario', ?)");
                
                $insertedCount = 0;
                $duplicateCount = 0;

                // Recorrer filas (asumiendo que la fila 1 son encabezados)
                for ($row = 2; $row <= $highestRow; $row++){
                    $nombre = $worksheet->getCell('A' . $row)->getValue();
                    $telefono = $worksheet->getCell('B' . $row)->getValue();
                    $email = $worksheet->getCell('C' . $row)->getValue();
                    $passwordManual = $worksheet->getCell('D' . $row)->getValue();
                    $sede_id = $worksheet->getCell('E' . $row)->getValue();
                    
                    // Validar que los campos requeridos no estén vacíos
                    if(empty($nombre) || empty($email) || empty($passwordManual)){
                        continue;
                    }
                    
                    // Verificar si ya existe un usuario con ese email
                    $existsStmt->bind_param("s", $email);
                    $existsStmt->execute();
                    $existsStmt->store_result();
                    
                    if($existsStmt->num_rows > 0){
                        $duplicateCount++;
                    } else {
                        // Insertar alumno
                        $insertAlumnoStmt->bind_param("sss", $nombre, $telefono, $email);
                        $insertAlumnoStmt->execute();
                        $alumno_id = $conn->insert_id;
                        
                        // Hashear la contraseña proporcionada manualmente
                        $hashedPassword = password_hash($passwordManual, PASSWORD_DEFAULT);
                        
                        // Si no se especifica sede, usar la sede en sesión o valor por defecto (1)
                        if(empty($sede_id)){
                            $sede_id = isset($_SESSION['sede_seleccionada']) ? intval($_SESSION['sede_seleccionada']) : 1;
                        }
                        
                        // Insertar usuario
                        $insertUserStmt->bind_param("ssi", $email, $hashedPassword, $sede_id);
                        $insertUserStmt->execute();
                        $user_id = $conn->insert_id;
                        
                        // Actualizar la tabla alumnos para vincularlo con el usuario recién creado
                        $conn->query("UPDATE alumnos SET usuario_id = $user_id WHERE id = $alumno_id");
                        
                        $insertedCount++;
                    }
                }
                
                $alertScript = "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                      icon: 'success',
                      title: 'Importación completada',
                      html: 'Se han importado <b>{$insertedCount}</b> alumnos nuevos.<br>Se omitieron <b>{$duplicateCount}</b> registros duplicados.',
                      showCancelButton: true,
                      confirmButtonText: 'Volver',
                      cancelButtonText: 'Cancelar',
                      customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-secondary'
                      },
                      buttonsStyling: false
                    }).then((result) => {
                      if(result.isConfirmed) {
                        window.location.href = 'add.php';
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
    <h2>Subir Alumnos desde Excel</h2>
    <form method="post" enctype="multipart/form-data">
       <div class="mb-3">
          <label for="excel_file" class="form-label">Seleccione el archivo Excel</label>
          <input type="file" name="excel_file" id="excel_file" class="form-control" required>
       </div>
       <button type="submit" class="btn btn-primary">Subir y Procesar</button>
       <a href="add.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php
if (!empty($alertScript)) {
    echo $alertScript;
}
include '../includes/footer.php';
?>
