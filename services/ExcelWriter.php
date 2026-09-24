<?php
declare(strict_types=1);

/**
 * ExcelWriter — genera archivos .xlsx reales sin dependencias externas.
 * Usa ZipArchive (incluida en PHP) y el formato Open XML de Microsoft.
 *
 * Soporta titulo/subtitulo, ancho de columnas, encabezado congelado, filtro
 * automatico, bandas alternas de lectura y una fila de totales resaltada.
 *
 * Uso:
 *   $xls = new ExcelWriter();
 *   $xls->setTitle('REPORTE');
 *   $xls->setSubtitle('Generado: 01/01/2026');
 *   $xls->setHeaders(['Col1', 'Col2']);
 *   $xls->setColumnWidths([20, 15]);
 *   $xls->addRow(['val1', 10]);
 *   $xls->setTotalRow(['TOTAL', 10]);
 *   $bytes = $xls->generate();
 */
class ExcelWriter
{
    private string $sheetName = 'Hoja1';
    private array $headers = [];
    private array $rows = [];
    private ?array $totalRow = null;
    private array $widths = [];
    private string $title = '';
    private string $subtitle = '';
    /** Color de fondo del encabezado (hex sin #) */
    private string $headerColor = '0D6EFD';
    private ?string $pageOrientation = null;
    private int $fitToWidth = 1;
    private int $fitToHeight = 1;
    private array $pageMargins = [];

    public function setSheetName(string $name): void
    {
        $this->sheetName = $name;
    }

    public function setHeaderColor(string $hexColor): void
    {
        $this->headerColor = strtoupper(ltrim($hexColor, '#'));
    }

    /**
     * Configura la impresión del libro para una página con márgenes controlados.
     *
     * @param string $orientation Orientación `portrait` o `landscape`.
     * @param int $fitToWidth Número máximo de páginas horizontales.
     * @param int $fitToHeight Número máximo de páginas verticales.
     * @param float $left Margen izquierdo en pulgadas.
     * @param float $right Margen derecho en pulgadas.
     * @param float $top Margen superior en pulgadas.
     * @param float $bottom Margen inferior en pulgadas.
     */
    public function setPageLayout(
        string $orientation,
        int $fitToWidth = 1,
        int $fitToHeight = 1,
        float $left = 0.25,
        float $right = 0.25,
        float $top = 0.5,
        float $bottom = 0.5,
    ): void {
        if (!in_array($orientation, ['portrait', 'landscape'], true)) {
            throw new InvalidArgumentException('La orientación de página no es válida.');
        }
        if ($fitToWidth < 1 || $fitToHeight < 1 || min($left, $right, $top, $bottom) < 0) {
            throw new InvalidArgumentException('La configuración de página no es válida.');
        }

        $this->pageOrientation = $orientation;
        $this->fitToWidth = $fitToWidth;
        $this->fitToHeight = $fitToHeight;
        $this->pageMargins = compact('left', 'right', 'top', 'bottom');
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /** @param int[] $widths Ancho de cada columna en unidades de caracter Excel. */
    public function setColumnWidths(array $widths): void
    {
        $this->widths = array_values($widths);
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = array_values($headers);
    }

    public function addRow(array $row): void
    {
        $this->rows[] = array_values($row);
    }

    public function addRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }

    /** Define una fila final resaltada (sumatorias, conteos, etc.). */
    public function setTotalRow(array $row): void
    {
        $this->totalRow = array_values($row);
    }

    /** Devuelve los bytes del archivo .xlsx */
    public function generate(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStrings());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->addFromString('docProps/app.xml', $this->appProps());

        $zip->close();

        $bytes = (string) file_get_contents($tmpFile);
        unlink($tmpFile);
        return $bytes;
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function xe(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function colLetter(int $col): string
    {
        $letters = '';
        $col++;
        while ($col > 0) {
            $col--;
            $letters = chr(65 + ($col % 26)) . $letters;
            $col = intdiv($col, 26);
        }
        return $letters;
    }

    /** Fila (1-based) donde inicia el encabezado, segun exista titulo/subtitulo. */
    private function headerRowNumber(): int
    {
        $row = 1;
        if ($this->title !== '') {
            $row++;
        }
        if ($this->subtitle !== '') {
            $row++;
        }
        return $row;
    }

    // ─── Partes del OOXML ───────────────────────────────────────────────────

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml"
    ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml"
    ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml"
    ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml"
    ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/docProps/app.xml"
    ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="xl/workbook.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties"
    Target="docProps/app.xml"/>
</Relationships>';
    }

    private function workbook(): string
    {
        $name = $this->xe($this->sheetName);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . $name . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
    Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
    Target="styles.xml"/>
  <Relationship Id="rId3"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings"
    Target="sharedStrings.xml"/>
</Relationships>';
    }

    /**
     * cellXfs (por indice, usado como atributo s=""):
     *  0 normal | 1 encabezado | 2 titulo | 3 subtitulo
     *  4 banda A texto | 5 banda B texto | 6 banda A numero | 7 banda B numero
     *  8 total texto | 9 total numero
     */
    private function styles(): string
    {
        $hc = $this->headerColor;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="7">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
    <font><b/><sz val="16"/><name val="Calibri"/><color rgb="FF1F2937"/></font>
    <font><i/><sz val="10"/><name val="Calibri"/><color rgb="FF64748B"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FF1F2937"/></font>
        <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FF166534"/></font>
        <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FF1D4ED8"/></font>
  </fonts>
    <fills count="8">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF' . $hc . '"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF0FDFA"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFF3CD"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE8F5E9"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE8F0FE"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFE2E8F0"/></left>
      <right style="thin"><color rgb="FFE2E8F0"/></right>
      <top style="thin"><color rgb="FFE2E8F0"/></top>
      <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
    <cellXfs count="12">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0"><alignment vertical="center"/></xf>
    <xf numFmtId="4" fontId="0" fillId="3" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="4" fontId="0" fillId="4" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0"><alignment vertical="center"/></xf>
    <xf numFmtId="4" fontId="4" fillId="5" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>
        <xf numFmtId="0" fontId="5" fillId="6" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="6" fillId="7" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
</styleSheet>';
    }

    private function collectStrings(): array
    {
        $strings = [];
        foreach ($this->headers as $h) {
            $strings[] = (string) $h;
        }
        foreach ($this->rows as $row) {
            foreach ($row as $cell) {
                if (!is_numeric($cell) || $cell === '') {
                    $strings[] = (string) $cell;
                }
            }
        }
        if ($this->totalRow !== null) {
            foreach ($this->totalRow as $cell) {
                if (!is_numeric($cell) || $cell === '') {
                    $strings[] = (string) $cell;
                }
            }
        }
        return $strings;
    }

    private function sharedStrings(): string
    {
        $strings = $this->collectStrings();
        $total = count($strings);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
              . ' count="' . $total . '" uniqueCount="' . $total . '">' . "\n";
        foreach ($strings as $s) {
            $xml .= '  <si><t xml:space="preserve">' . $this->xe($s) . '</t></si>' . "\n";
        }
        $xml .= '</sst>';
        return $xml;
    }

    private function buildStringIndex(): array
    {
        $strIndex = [];
        $counter = 0;
        foreach ($this->headers as $h) {
            $key = '__HDR__' . $h;
            if (!array_key_exists($key, $strIndex)) {
                $strIndex[$key] = $counter++;
            }
        }
        foreach ($this->rows as $row) {
            foreach ($row as $cell) {
                if (!is_numeric($cell) || $cell === '') {
                    $key = (string) $cell;
                    if (!array_key_exists($key, $strIndex)) {
                        $strIndex[$key] = $counter++;
                    }
                }
            }
        }
        if ($this->totalRow !== null) {
            foreach ($this->totalRow as $cell) {
                if (!is_numeric($cell) || $cell === '') {
                    $key = (string) $cell;
                    if (!array_key_exists($key, $strIndex)) {
                        $strIndex[$key] = $counter++;
                    }
                }
            }
        }
        return $strIndex;
    }

    private function writeDataCell(int $ci, int $rowNum, $cell, string $textStyle, string $numStyle, array $strIndex): string
    {
        $cellRef = $this->colLetter($ci) . $rowNum;
        if (is_numeric($cell) && $cell !== '') {
            return '  <c r="' . $cellRef . '" s="' . $numStyle . '"><v>' . $this->xe((string) $cell) . '</v></c>' . "\n";
        }
        $siIdx = $strIndex[(string) $cell] ?? 0;
        $style = $cell === '✔' ? ($textStyle === '4' ? '10' : '11') : $textStyle;
        return '  <c r="' . $cellRef . '" t="s" s="' . $style . '"><v>' . $siIdx . '</v></c>' . "\n";
    }

    private function sheet(): string
    {
        $strIndex = $this->buildStringIndex();
        $headerRow = $this->headerRowNumber();
        $lastCol = $this->colLetter(max(0, count($this->headers) - 1));

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        if ($this->pageOrientation !== null) {
            $xml .= '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>' . "\n";
        }
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow
              . '" topLeftCell="A' . ($headerRow + 1) . '" state="frozen"/></sheetView></sheetViews>' . "\n";

        if (!empty($this->widths)) {
            $xml .= '<cols>';
            foreach ($this->widths as $i => $width) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $width . '" customWidth="1"/>';
            }
            $xml .= '</cols>' . "\n";
        }

        $xml .= '<sheetData>' . "\n";

        $rowNum = 1;
        if ($this->title !== '') {
            $xml .= '<row r="' . $rowNum . '" ht="24" customHeight="1"><c r="A' . $rowNum . '" t="inlineStr" s="2"><is><t xml:space="preserve">'
                  . $this->xe($this->title) . '</t></is></c></row>' . "\n";
            $rowNum++;
        }
        if ($this->subtitle !== '') {
            $xml .= '<row r="' . $rowNum . '" ht="18" customHeight="1"><c r="A' . $rowNum . '" t="inlineStr" s="3"><is><t xml:space="preserve">'
                  . $this->xe($this->subtitle) . '</t></is></c></row>' . "\n";
            $rowNum++;
        }

        if (!empty($this->headers)) {
            $xml .= '<row r="' . $rowNum . '" ht="26" customHeight="1">' . "\n";
            foreach ($this->headers as $ci => $h) {
                $cellRef = $this->colLetter($ci) . $rowNum;
                $siIdx = $strIndex['__HDR__' . $h];
                $xml .= '  <c r="' . $cellRef . '" t="s" s="1"><v>' . $siIdx . '</v></c>' . "\n";
            }
            $xml .= '</row>' . "\n";
            $rowNum++;
        }

        $bandIndex = 0;
        foreach ($this->rows as $row) {
            $textStyle = $bandIndex % 2 === 0 ? '4' : '5';
            $numStyle = $bandIndex % 2 === 0 ? '6' : '7';

            $xml .= '<row r="' . $rowNum . '" ht="20" customHeight="1">' . "\n";
            foreach ($row as $ci => $cell) {
                $xml .= $this->writeDataCell($ci, $rowNum, $cell, $textStyle, $numStyle, $strIndex);
            }
            $xml .= '</row>' . "\n";
            $rowNum++;
            $bandIndex++;
        }

        $lastDataRow = $rowNum - 1;

        if ($this->totalRow !== null) {
            $xml .= '<row r="' . $rowNum . '" ht="22" customHeight="1">' . "\n";
            foreach ($this->totalRow as $ci => $cell) {
                $xml .= $this->writeDataCell($ci, $rowNum, $cell, '8', '9', $strIndex);
            }
            $xml .= '</row>' . "\n";
            $rowNum++;
        }

        $xml .= '</sheetData>' . "\n";

        if (!empty($this->headers) && $lastDataRow >= $headerRow) {
            $xml .= '<autoFilter ref="A' . $headerRow . ':' . $lastCol . $lastDataRow . '"/>' . "\n";
        }

        if ($this->pageOrientation !== null) {
            $xml .= '<pageMargins left="' . $this->pageMargins['left'] . '" right="' . $this->pageMargins['right']
                . '" top="' . $this->pageMargins['top'] . '" bottom="' . $this->pageMargins['bottom']
                . '" header="0.15" footer="0.15"/>' . "\n";
            $xml .= '<pageSetup orientation="' . $this->pageOrientation . '" paperSize="9" fitToWidth="'
                . $this->fitToWidth . '" fitToHeight="' . $this->fitToHeight . '"/>' . "\n";
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    private function appProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <Application>Restaurante Escuela INTECAP</Application>
</Properties>';
    }
}
