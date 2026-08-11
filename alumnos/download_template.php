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
// SOLO ADMIN
// =====================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: list.php');
    exit();
}


// =====================================================
// CARGAR COMPOSER
// =====================================================

$autoload = '../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('No se encontró vendor/autoload.php');
}

require_once $autoload;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


// =====================================================
// CREAR ARCHIVO
// =====================================================

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Libros');


// =====================================================
// ENCABEZADOS
// =====================================================

$encabezados = [
    'Código',
    'Dewey',
    'Clasificación',
    'Nombre',
    'Autor(es)',
    'Editorial',
    'Edición',
    'Año',
    'ISBN',
    'Estado',
    'Ubicación',
    'Fecha de ingreso',
    'Idioma',
    'Carrera',
    'Cantidad',
    'Sede ID'
];


// =====================================================
// ESCRIBIR ENCABEZADOS
// SIN setCellValueByColumnAndRow()
// =====================================================

$columnas = [
    'A',
    'B',
    'C',
    'D',
    'E',
    'F',
    'G',
    'H',
    'I',
    'J',
    'K',
    'L',
    'M',
    'N',
    'O',
    'P'
];

foreach ($encabezados as $i => $encabezado) {

    $sheet->setCellValue(
        $columnas[$i] . '1',
        $encabezado
    );

}


// =====================================================
// ESTILO DEL ENCABEZADO
// =====================================================

$sheet
    ->getStyle('A1:P1')
    ->getFont()
    ->setBold(true);

$sheet
    ->getStyle('A1:P1')
    ->getFont()
    ->getColor()
    ->setARGB('FFFFFFFF');

$sheet
    ->getStyle('A1:P1')
    ->getFill()
    ->setFillType(Fill::FILL_SOLID);

$sheet
    ->getStyle('A1:P1')
    ->getFill()
    ->getStartColor()
    ->setARGB('3159D8');

$sheet
    ->getStyle('A1:P1')
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );

$sheet
    ->getStyle('A1:P1')
    ->getAlignment()
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );

$sheet
    ->getRowDimension(1)
    ->setRowHeight(25);


// =====================================================
// EJEMPLO
// =====================================================

$ejemplo = [
    'UPH-04-BLGM-000001',
    '100',
    'Generalidades',
    'dBASE III Plus',
    'Julian Martinez Valero',
    'ANAYA Multimedia',
    'Cuarta Reimpresión',
    '1994',
    '84-7614-444-X',
    'Disponible',
    'Estante A-01',
    date('Y-m-d'),
    'Español',
    'Ingeniería en Sistemas Computacionales',
    '1',
    '4'
];


foreach ($ejemplo as $i => $valor) {

    $sheet->setCellValue(
        $columnas[$i] . '2',
        $valor
    );

}


// =====================================================
// INSTRUCCIONES
// =====================================================

$sheet->setCellValue(
    'A4',
    'INSTRUCCIONES'
);

$sheet->setCellValue(
    'A5',
    'Complete los datos de los libros sin modificar los encabezados.'
);

$sheet->setCellValue(
    'A6',
    'La Sede ID para esta plantilla es 4 = Danlí.'
);

$sheet->setCellValue(
    'A7',
    'Estado: Disponible, Prestado, Deteriorado o Baja.'
);

$sheet->setCellValue(
    'A8',
    'Fecha de ingreso: formato AAAA-MM-DD.'
);

$sheet->setCellValue(
    'A9',
    'Cantidad debe ser un número mayor o igual a 1.'
);


$sheet
    ->getStyle('A4')
    ->getFont()
    ->setBold(true);

$sheet
    ->getStyle('A4')
    ->getFont()
    ->getColor()
    ->setARGB('FF3159D8');


// =====================================================
// ANCHOS
// =====================================================

$anchos = [
    'A' => 25,
    'B' => 12,
    'C' => 20,
    'D' => 35,
    'E' => 30,
    'F' => 25,
    'G' => 25,
    'H' => 10,
    'I' => 20,
    'J' => 18,
    'K' => 20,
    'L' => 18,
    'M' => 15,
    'N' => 40,
    'O' => 12,
    'P' => 12
];


foreach ($anchos as $columna => $ancho) {

    $sheet
        ->getColumnDimension($columna)
        ->setWidth($ancho);

}


// =====================================================
// FILTRO
// =====================================================

$sheet->setAutoFilter('A1:P2');


// =====================================================
// CONGELAR PRIMERA FILA
// =====================================================

$sheet->freezePane('A2');


// =====================================================
// DESCARGA
// =====================================================

$nombreArchivo = 'Plantilla_Libros_Danli.xlsx';


// Limpiar salida anterior
if (ob_get_length()) {
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

header('Cache-Control: max-age=0');


$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit();

?>