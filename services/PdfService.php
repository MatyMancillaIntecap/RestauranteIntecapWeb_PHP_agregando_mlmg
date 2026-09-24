<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PdfReport.php';
require_once __DIR__ . '/IPdfService.php';
require_once __DIR__ . '/PdfWriter.php';

/** Error de infraestructura controlado durante la exportacion PDF. */
final class PdfGenerationException extends RuntimeException
{
}

/**
 * Servicio unico de exportacion PDF.
 *
 * Recibe un DTO validado y encapsula por completo el renderer, la grilla, la
 * paginacion y la conversion a un resultado de archivo HTTP.
 */
final class PdfService implements IPdfService
{
    private const CONTENT_WIDTH = 515.0;

    public function generate(PdfReport $report, string $filename): PdfFile
    {
        try {
            $writer = new PdfWriter($report->title);
            $writer->setFooterText($report->confidentiality);
            $writer->addLines($this->headerLines($report));
            $writer->setSummary($report->summary);
            $writer->setTable(
                array_map(static fn (PdfColumn $column): string => $column->label, $report->columns),
                $report->rows,
                array_map(static fn (PdfColumn $column): float => $column->width, $report->columns),
            );

            $this->assertGrid($report->columns);
            $content = $writer->generate();
            return new PdfFile($content, $filename);
        } catch (Throwable $exception) {
            throw new PdfGenerationException('No fue posible generar el reporte PDF.', 0, $exception);
        }
    }

    /** @return string[] */
    private function headerLines(PdfReport $report): array
    {
        $lines = [
            'Modulo: ' . $report->module,
            'Solicitado por: ' . $report->requester,
            'Fecha de emision: ' . $report->issuedAt,
            'Folio: ' . $report->folio,
        ];

        foreach ($report->metadata as $label => $value) {
            $lines[] = trim($label) . ': ' . trim($value);
        }

        return $lines;
    }

    /** @param PdfColumn[] $columns */
    private function assertGrid(array $columns): void
    {
        $width = array_sum(array_map(static fn (PdfColumn $column): float => $column->width, $columns));
        if (abs($width - self::CONTENT_WIDTH) > 0.01) {
            throw new InvalidArgumentException('La grilla PDF debe sumar exactamente 515 unidades.');
        }
    }
}
