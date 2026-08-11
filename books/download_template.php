<?php

session_start();

require_once '../includes/session.php';
require_once '../config/db.php';


// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}


// =====================================================
// VERIFICAR ADMINISTRADOR
// =====================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: list.php');
    exit();
}


// =====================================================
// PHPSPREADSHEET
// =====================================================

require_once '../vendor/autoload.php';


// =====================================================
// IMPORTACIONES
// =====================================================

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// =====================================================
// CREAR EXCEL
// =====================================================

$excel = new Spreadsheet();

$hoja = $excel->getActiveSheet();

$hoja->setTitle('Libros');


// =====================================================
// ENCABEZADOS
// =====================================================

$hoja->setCellValue('A1', 'Código');
$hoja->setCellValue('B1', 'Dewey');
$hoja->setCellValue('C1', 'Clasificación');
$hoja->setCellValue('D1', 'Nombre');
$hoja->setCellValue('E1', 'Autor(es)');
$hoja->setCellValue('F1', 'Editorial');
$hoja->setCellValue('G1', 'Edición');
$hoja->setCellValue('H1', 'Año');
$hoja->setCellValue('I1', 'ISBN');
$hoja->setCellValue('J1', 'Estado');
$hoja->setCellValue('K1', 'Ubicación');
$hoja->setCellValue('L1', 'Fecha de ingreso');
$hoja->setCellValue('M1', 'Idioma');
$hoja->setCellValue('N1', 'Carrera');
$hoja->setCellValue('O1', 'Cantidad');
$hoja->setCellValue('P1', 'Sede ID');


// =====================================================
// EJEMPLO
// =====================================================

$hoja->setCellValue(
    'A2',
    'UPH-04-BLGM-000001'
);

$hoja->setCellValue(
    'B2',
    '100'
);

$hoja->setCellValue(
    'C2',
    'Generalidades'
);

$hoja->setCellValue(
    'D2',
    'dBASE III Plus'
);

$hoja->setCellValue(
    'E2',
    'Julian Martinez Valero'
);

$hoja->setCellValue(
    'F2',
    'ANAYA Multimedia'
);

$hoja->setCellValue(
    'G2',
    'Cuarta Reimpresión'
);

$hoja->setCellValue(
    'H2',
    '1994'
);

$hoja->setCellValue(
    'I2',
    '84-7614-444-X'
);

$hoja->setCellValue(
    'J2',
    'Disponible'
);

$hoja->setCellValue(
    'K2',
    'Estante A-01'
);

$hoja->setCellValue(
    'L2',
    date('Y-m-d')
);

$hoja->setCellValue(
    'M2',
    'Español'
);

$hoja->setCellValue(
    'N2',
    'Ingeniería en Sistemas Computacionales'
);

$hoja->setCellValue(
    'O2',
    '1'
);

$hoja->setCellValue(
    'P2',
    '4'
);


// =====================================================
// ANCHO DE COLUMNAS
// =====================================================

$hoja->getColumnDimension('A')->setWidth(25);
$hoja->getColumnDimension('B')->setWidth(12);
$hoja->getColumnDimension('C')->setWidth(20);
$hoja->getColumnDimension('D')->setWidth(35);
$hoja->getColumnDimension('E')->setWidth(30);
$hoja->getColumnDimension('F')->setWidth(25);
$hoja->getColumnDimension('G')->setWidth(25);
$hoja->getColumnDimension('H')->setWidth(10);
$hoja->getColumnDimension('I')->setWidth(20);
$hoja->getColumnDimension('J')->setWidth(18);
$hoja->getColumnDimension('K')->setWidth(20);
$hoja->getColumnDimension('L')->setWidth(18);
$hoja->getColumnDimension('M')->setWidth(15);
$hoja->getColumnDimension('N')->setWidth(40);
$hoja->getColumnDimension('O')->setWidth(12);
$hoja->getColumnDimension('P')->setWidth(12);


// =====================================================
// NEGRITA EN ENCABEZADOS
// =====================================================

$hoja
    ->getStyle('A1:P1')
    ->getFont()
    ->setBold(true);


// =====================================================
// FILTRO
// =====================================================

$hoja->setAutoFilter('A1:P2');


// =====================================================
// CONGELAR ENCABEZADO
// =====================================================

$hoja->freezePane('A2');


// =====================================================
// DESCARGAR
// =====================================================

$nombreArchivo = 'Plantilla_Libros_Danli.xlsx';


// Limpiar cualquier salida anterior
while (ob_get_level()) {
    ob_end_clean();
}


header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

header(
    'Cache-Control: max-age=0'
);


$writer = new Xlsx($excel);

$writer->save('php://output');

exit;

?>