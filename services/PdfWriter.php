<?php
declare(strict_types=1);

/**
 * Generador PDF ligero para reportes internos, sin dependencias externas.
 *
 * Dibuja una barra de titulo, lineas de contexto, un resumen clave/valor y
 * una tabla real (encabezado + filas con bandas alternas) que se pagina
 * automaticamente cuando el contenido no cabe en una sola hoja.
 */
class PdfWriter
{
    /** @var string[] Lineas de texto libres (institucion, periodo, etc.). */
    private array $lines = [];
    /** @var array{headers?: array, rows?: array, widths?: array} */
    private array $table = [];
    /** @var array<string,string> Pares indicador => valor mostrados como resumen. */
    private array $summary = [];
    /** @var string[] Alineaciones opcionales de las columnas de la tabla. */
    private array $columnAlignments = [];
    private string $footerText = 'Restaurante Escuela INTECAP';

    private array $pages = [];
    private string $buf = '';
    private float $y = 806;

    public function __construct(private string $title = 'Reporte')
    {
    }

    /** Agrega una linea de texto libre (antes de la tabla). */
    public function addLine(string $line): void
    {
        $this->lines[] = $line;
    }

    public function addLines(array $lines): void
    {
        foreach ($lines as $line) {
            $this->addLine((string) $line);
        }
    }

    /** Define la tabla principal del reporte. */
    public function setTable(array $headers, array $rows, array $widths = []): void
    {
        $this->table = ['headers' => $headers, 'rows' => $rows, 'widths' => $widths];
    }

    /**
     * Define la alineación de cada columna: left, center o right.
     *
     * @param string[] $alignments Alineaciones en el mismo orden que los encabezados.
     */
    public function setColumnAlignments(array $alignments): void
    {
        $this->columnAlignments = $alignments;
    }

    /** Define un resumen clave/valor mostrado antes de la tabla. */
    public function setSummary(array $summary): void
    {
        $this->summary = $summary;
    }

    /** Define el texto institucional que acompana la paginacion. */
    public function setFooterText(string $footerText): void
    {
        $footerText = trim($footerText);
        if ($footerText !== '') {
            $this->footerText = $footerText;
        }
    }

    /** @return string Bytes binarios de un documento PDF valido, con paginacion automatica. */
    public function generate(): string
    {
        $this->buildAllPages();

        $pageCount = count($this->pages);
        $pagesObjId = 2;
        $firstPageObjId = 3;
        $firstContentObjId = 3 + $pageCount;
        $fontRegularId = 3 + $pageCount * 2;
        $fontBoldId = $fontRegularId + 1;

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages ' . $pagesObjId . ' 0 R >>';

        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = ($firstPageObjId + $i) . ' 0 R';
        }
        $objects[$pagesObjId] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';

        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = $firstPageObjId + $i;
            $contentId = $firstContentObjId + $i;
            $objects[$pageId] = '<< /Type /Page /Parent ' . $pagesObjId . ' 0 R /MediaBox [0 0 595 842]'
                . ' /Contents ' . $contentId . ' 0 R'
                . ' /Resources << /Font << /F1 ' . $fontRegularId . ' 0 R /F2 ' . $fontBoldId . ' 0 R >> >> >>';
            $stream = $this->pages[$i];
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        ksort($objects);
        $maxId = max(array_keys($objects));

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        for ($id = 1; $id <= $maxId; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $objects[$id] . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    /** Dibuja titulo, lineas, resumen y tabla completa, generando tantas paginas como haga falta. */
    private function buildAllPages(): void
    {
        $this->pages = [];
        $this->buf = '';
        $this->y = 806;

        $this->drawPageHeader();

        foreach ($this->lines as $line) {
            $this->text(36, $this->y, 9, $line);
            $this->y -= 15;
        }

        if ($this->summary !== []) {
            $this->y -= 6;
            foreach ($this->summary as $label => $value) {
                $this->text(36, $this->y, 10, strtoupper((string) $label) . ': ' . $value, true);
                $this->y -= 15;
            }
        }
        $this->y -= 10;

        $headers = $this->table['headers'] ?? [];
        $rows = $this->table['rows'] ?? [];
        $widths = $this->table['widths'] ?? [];
        if ($headers !== [] && $widths === []) {
            $widths = array_fill(0, count($headers), 523 / count($headers));
        }

        if ($headers !== []) {
            $this->drawTableHeader($headers, $widths);

            foreach ($rows as $i => $row) {
                if ($this->y < 70) {
                    $this->flushPage();
                    $this->drawPageHeader();
                    $this->drawTableHeader($headers, $widths);
                }
                $this->drawTableRow($row, $headers, $widths, $i % 2 === 0);
            }

        }

        $this->flushPage();
        $this->stampPageNumbers();
    }

    /** Cierra la pagina actual y reinicia el cursor vertical para la siguiente. */
    private function flushPage(): void
    {
        $this->pages[] = $this->buf;
        $this->buf = '';
        $this->y = 806;
    }

    /** Agrega el pie "Pagina X de Y" a cada pagina, una vez conocido el total. */
    private function stampPageNumbers(): void
    {
        $total = count($this->pages);
        foreach ($this->pages as $i => &$page) {
            $footer = $this->footerText . ' - Pagina ' . ($i + 1) . ' de ' . $total;
            $page .= "BT\n0.45 0.45 0.45 rg\n/F1 8 Tf\n36 30 Td\n(" . $this->escapePdf($footer) . ") Tj\nET\n";
        }
        unset($page);
    }

    private function drawHeaderBar(): void
    {
        $this->rect(0, 792, 595, 50, '0.12 0.24 0.42');
        $this->text(36, 815, 18, $this->title, true, '1 1 1');
    }

    /** Dibuja el encabezado institucional y el titulo en cada pagina. */
    private function drawPageHeader(): void
    {
        $this->drawHeaderBar();
        $this->text(36, 780, 9, 'RESTAURANTE ESCUELA INTECAP', true, '0.12 0.24 0.42');
        $this->y = 762;
    }

    private function drawTableHeader(array $headers, array $widths): void
    {
        $x = 36;
        $rowHeight = 24;
        $this->rect($x, $this->y - $rowHeight, array_sum($widths), $rowHeight, '0.12 0.24 0.42');
        foreach ($headers as $i => $header) {
            $this->text($x + 4, $this->y - 16, 8, (string) $header, true, '1 1 1');
            $x += $widths[$i];
        }
        $this->y -= $rowHeight;
    }

    private function drawTableRow(array $row, array $headers, array $widths, bool $band): void
    {
        $rowHeight = 20;
        $x = 36;
        if ($band) {
            $this->rect($x, $this->y - $rowHeight, array_sum($widths), $rowHeight, '0.95 0.96 0.97');
        }
        foreach ($headers as $i => $_header) {
            $value = (string) ($row[$i] ?? '');
            $alignment = $this->columnAlignments[$i] ?? 'left';
            $this->textAligned($x, $this->y - 14, 8, $value, $widths[$i], $alignment);
            $x += $widths[$i];
        }
        $this->y -= $rowHeight;
    }

    /** Dibuja una celda de tabla respetando el ancho y la alineación configurada. */
    private function textAligned(float $x, float $y, int $size, string $value, float $width, string $alignment): void
    {
        $value = $this->truncate($value, max(6, (int) ($width / 4.3)));
        $estimatedWidth = strlen($value) * $size * 0.48;
        $textX = match ($alignment) {
            'right' => $x + $width - $estimatedWidth - 4,
            'center' => $x + (($width - $estimatedWidth) / 2),
            default => $x + 4,
        };
        $this->text(max($x + 2, $textX), $y, $size, $value);
    }

    private function text(float $x, float $y, int $size, string $value, bool $bold = false, string $color = '0.12 0.16 0.19'): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->buf .= "BT\n{$color} rg\n/{$font} {$size} Tf\n{$x} {$y} Td\n(" . $this->escapePdf($value) . ") Tj\nET\n";
    }

    private function rect(float $x, float $y, float $width, float $height, string $color): void
    {
        $this->buf .= "q\n{$color} rg\n{$x} {$y} {$width} {$height} re\nf\nQ\n";
    }

    private function truncate(string $value, int $length): string
    {
        return strlen($value) > $length ? substr($value, 0, max(1, $length - 3)) . '...' : $value;
    }

    /** Escapa caracteres PDF y convierte UTF-8 a la codificacion WinAnsi de Helvetica. */
    private function escapePdf(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    }
}
