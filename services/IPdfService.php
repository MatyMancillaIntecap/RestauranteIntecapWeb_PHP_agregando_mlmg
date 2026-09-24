<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PdfReport.php';

/** Contrato de generacion de documentos PDF para reportes. */
interface IPdfService
{
    public function generate(PdfReport $report, string $filename): PdfFile;
}
