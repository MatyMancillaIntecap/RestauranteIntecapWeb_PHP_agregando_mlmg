<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PdfReport.php';

/** Contrato de generacion de documentos PDF para reportes. */
interface IPdfService
{
    /**
     * Genera un PDF a partir de un reporte validado.
     *
     * @param PdfReport $report Datos y estructura visual del documento.
     * @param string $filename Nombre seguro para la descarga.
     * @return PdfFile Documento PDF y metadatos HTTP.
     */
    public function generate(PdfReport $report, string $filename): PdfFile;
}
