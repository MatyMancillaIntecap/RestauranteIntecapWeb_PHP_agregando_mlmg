<?php
declare(strict_types=1);

/** Columna tipada de una tabla PDF. */
final class PdfColumn
{
    public string $label;
    public float $width;

    public function __construct(string $label, float $width)
    {
        $label = trim($label);
        if ($label === '' || $width <= 0) {
            throw new InvalidArgumentException('Una columna PDF debe tener etiqueta y ancho validos.');
        }

        $this->label = $label;
        $this->width = $width;
    }
}

/** Documento PDF listo para ser enviado como archivo HTTP. */
final class PdfFile
{
    public string $content;
    public string $filename;
    public string $contentType;

    public function __construct(string $content, string $filename, string $contentType = 'application/pdf')
    {
        if ($content === '' || $filename === '') {
            throw new InvalidArgumentException('El documento PDF y su nombre son obligatorios.');
        }

        $this->content = $content;
        $this->filename = basename($filename);
        $this->contentType = $contentType;
    }
}

/** DTO inmutable con los datos visuales necesarios para construir un reporte. */
final class PdfReport
{
    /** @param array<string,string> $metadata @param array<string,string> $summary @param PdfColumn[] $columns @param array<int,array<int,mixed>> $rows */
    public function __construct(
        public string $title,
        public string $module,
        public string $requester,
        public string $issuedAt,
        public string $folio,
        public string $confidentiality,
        public array $metadata,
        public array $summary,
        public array $columns,
        public array $rows,
    ) {
        if (trim($this->title) === '' || trim($this->module) === '' || trim($this->folio) === '') {
            throw new InvalidArgumentException('Titulo, modulo y folio son obligatorios para un reporte PDF.');
        }
        if ($this->columns === []) {
            throw new InvalidArgumentException('El reporte PDF debe definir al menos una columna.');
        }
        foreach ($this->columns as $column) {
            if (!$column instanceof PdfColumn) {
                throw new InvalidArgumentException('Las columnas del reporte deben ser instancias de PdfColumn.');
            }
        }
    }
}
