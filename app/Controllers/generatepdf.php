<?php

require __DIR__ . "/vendor/autoload.php";

use Dompdf\Dompdf;

$dompdf = new Dompdf;
$dompdf->loadHtml("Hello world");
$dompdf->render();
$dompdf->stream();












;?>