<?php

require __DIR__ . '/../../../vendor/autoload.php';

//load phpspreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Codeigniter\Controller;

$file = "TataKelola BPRK 12312025.xlsx";

$spreadsheet = new Spreadsheet();
$spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', 'Faktor 1');
$spreadsheet->getActiveSheet()->setTitle('B0100');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(1)->setCellValue('A1', 'Faktor 2');
$spreadsheet->getActiveSheet()->setTitle('B0200');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(2)->setCellValue('A1', 'Faktor 3');
$spreadsheet->getActiveSheet()->setTitle('B0300');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(3)->setCellValue('A1', 'Faktor 4');
$spreadsheet->getActiveSheet()->setTitle('B0400');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(4)->setCellValue('A1', 'Faktor 5');
$spreadsheet->getActiveSheet()->setTitle('B0500');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(5)->setCellValue('A1', 'Faktor 6');
$spreadsheet->getActiveSheet()->setTitle('B0600');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(6)->setCellValue('A1', 'Faktor 7');
$spreadsheet->getActiveSheet()->setTitle('B0700');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(7)->setCellValue('A1', 'Faktor 8');
$spreadsheet->getActiveSheet()->setTitle('B0800');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(8)->setCellValue('A1', 'Faktor 9');
$spreadsheet->getActiveSheet()->setTitle('B0900');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(9)->setCellValue('A1', 'Faktor 10');
$spreadsheet->getActiveSheet()->setTitle('B1000');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(10)->setCellValue('A1', 'Faktor 11');
$spreadsheet->getActiveSheet()->setTitle('B01100');

$spreadsheet->createSheet();
$spreadsheet->setActiveSheetIndex(11)->setCellValue('A1', 'Faktor 12');
$spreadsheet->getActiveSheet()->setTitle('B01200');

$spreadsheet->setActiveSheetIndex(0);

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $file . '"');

exit;

// Write to output
//echo "<meta http-equiv='refresh' content='0;url=hello world.xlsx'>";