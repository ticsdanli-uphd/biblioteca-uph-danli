<?php
include '../includes/session.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /biblioteca/dashboard.php");
    exit();
}

include '../config/db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// 1. Obtener las carreras desde la base de datos
$sqlCarreras = "SELECT nombre FROM carreras ORDER BY nombre ASC";
$resCarreras = $conn->query($sqlCarreras);
$careerNames = [];
while ($row = $resCarreras->fetch_assoc()) {
    $careerNames[] = $row['nombre'];
}

// 2. Crear el objeto Spreadsheet y la hoja principal para la plantilla de tesis
$spreadsheet = new Spreadsheet();
$sheetMain = $spreadsheet->getActiveSheet();
$sheetMain->setTitle('Tesis');

// Definir encabezados para la plantilla de tesis
$headers = [
    'N° Tesis',          // Columna A
    'N° Cuenta',         // Columna B
    'Alumno',            // Columna C
    'Carrera',           // Columna D (desplegable)
    'Título',            // Columna E
    'Año Egresado',      // Columna F
    'Asesor Metodológico', // Columna G
    'Asesor Temático',     // Columna H
    'Cantidad',            // Columna I
    'Sede ID'              // Columna J
];

$col = 'A';
foreach ($headers as $header) {
    $sheetMain->setCellValue($col . '1', $header);
    $col++;
}

// Ajustar el ancho de la columna D para que se vea mejor el texto del combo
$sheetMain->getColumnDimension('D')->setAutoSize(true);

// 3. Crear una hoja oculta para almacenar la lista de carreras
$sheetCareers = new Worksheet($spreadsheet, 'CARRERAS');
$spreadsheet->addSheet($sheetCareers);
$sheetCareers->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

// Escribir la lista de carreras en la columna A de la hoja "CARRERAS"
$rowIndex = 1;
foreach ($careerNames as $career) {
    $sheetCareers->setCellValue('A' . $rowIndex, $career);
    $rowIndex++;
}
$lastRow = count($careerNames);

// 4. Aplicar validación de datos en la columna "Carrera" (columna D) en la hoja principal
for ($row = 2; $row <= 100; $row++) {
    $cell = 'D' . $row;
    $validation = $sheetMain->getCell($cell)->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST)
               ->setErrorStyle(DataValidation::STYLE_STOP)
               ->setAllowBlank(false)
               ->setShowDropDown(true)
               ->setErrorTitle('Entrada no válida')
               ->setError('El valor ingresado no está en la lista.')
               ->setPromptTitle('Seleccione una carrera')
               ->setPrompt('Seleccione una carrera de la lista.')
               ->setFormula1('=CARRERAS!$A$1:$A$' . $lastRow);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="plantilla_tesis.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
