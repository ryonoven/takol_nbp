<?php

namespace App\Libraries;

/** PDF satu halaman cover (pratinjau di halaman Self Assessment Tata Kelola). */
class CoverPdf extends \FPDF
{
    use CoverRenderTrait;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
    }
}
