<?php
/**
 * Minimal FPDF-compatible PDF generator.
 *
 * Implements the subset of the classic FPDF public API (AddPage, SetFont, Cell,
 * Ln, Line, Rect, SetFillColor/SetDrawColor/SetTextColor, SetLineWidth, Output)
 * needed to render a GST invoice. Only the standard, non-embedded "Courier" and
 * "Courier-Bold" core PDF fonts are used, which are monospaced at exactly
 * 600/1000 em for every character - this keeps text-width/alignment math exact
 * without needing a transcribed AFM kerning table for a proportional font.
 *
 * Coordinates/sizes are in millimetres (A4 by default), matching FPDF's
 * default unit, and are converted to PDF points (1mm = 72/25.4 pt) only when
 * content stream operators are emitted.
 */
class FPDF
{
    protected float $k;
    protected float $w;
    protected float $h;
    protected float $lMargin = 10;
    protected float $tMargin = 10;
    protected float $rMargin = 10;
    protected float $bMargin = 10;
    protected float $cMargin = 1;
    protected float $x = 0;
    protected float $y = 0;
    protected int $page = 0;
    protected array $pages = [];
    protected string $fontFamily = 'Courier';
    protected string $fontStyle = '';
    protected float $fontSizePt = 12;
    protected float $fontSize;
    protected array $drawColor = [0, 0, 0];
    protected array $fillColor = [0, 0, 0];
    protected array $textColor = [0, 0, 0];
    protected float $lineWidth = 0.2;
    protected string $title = '';

    protected const FONT_MAP = [
        ''  => 'Courier',
        'B' => 'Courier-Bold',
    ];

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        $this->k = 72 / 25.4;

        [$wMm, $hMm] = $size === 'A4' ? [210.0, 297.0] : [215.9, 279.4];
        if (strtoupper($orientation[0]) === 'L') {
            [$wMm, $hMm] = [$hMm, $wMm];
        }
        $this->w = $wMm;
        $this->h = $hMm;
        $this->fontSize = $this->fontSizePt / $this->k;
    }

    public function SetTitle(string $title): void
    {
        $this->title = $title;
    }

    public function SetMargins(float $left, float $top, ?float $right = null): void
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right ?? $left;
    }

    public function SetLineWidth(float $width): void
    {
        $this->lineWidth = $width;
        $this->_out(sprintf('%.2F w', $width * $this->k));
    }

    public function SetDrawColor(int $r, int $g, int $b): void
    {
        $this->drawColor = [$r, $g, $b];
        $this->_out(sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255));
    }

    public function SetFillColor(int $r, int $g, int $b): void
    {
        $this->fillColor = [$r, $g, $b];
        $this->_out(sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255));
    }

    public function SetTextColor(int $r, int $g, int $b): void
    {
        $this->textColor = [$r, $g, $b];
    }

    public function SetFont(string $family, string $style = '', float $size = 0): void
    {
        $style = strtoupper($style) === 'B' ? 'B' : '';
        $this->fontFamily = $family;
        $this->fontStyle = $style;
        if ($size > 0) {
            $this->fontSizePt = $size;
            $this->fontSize = $size / $this->k;
        }
        if ($this->page > 0) {
            $this->_out(sprintf('/%s %.2F Tf', $this->_fontKey(), $this->fontSizePt));
        }
    }

    public function SetXY(float $x, float $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function SetX(float $x): void
    {
        $this->x = $x;
    }

    public function SetY(float $y): void
    {
        $this->y = $y;
    }

    public function GetX(): float
    {
        return $this->x;
    }

    public function GetY(): float
    {
        return $this->y;
    }

    public function PageWidth(): float
    {
        return $this->w;
    }

    public function PageHeight(): float
    {
        return $this->h;
    }

    /** Exact string width in mm for the current (monospaced) font/size. */
    public function GetStringWidth(string $s): float
    {
        return strlen($s) * 0.6 * $this->fontSize;
    }

    public function AddPage(): void
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        // Re-apply current graphics state so each new page's content stream is self-contained.
        $this->_out(sprintf('%.2F w', $this->lineWidth * $this->k));
        $this->_out(sprintf('%.3F %.3F %.3F RG', ...array_map(fn ($c) => $c / 255, $this->drawColor)));
        $this->_out(sprintf('%.3F %.3F %.3F rg', ...array_map(fn ($c) => $c / 255, $this->fillColor)));
        $this->_out(sprintf('/%s %.2F Tf', $this->_fontKey(), $this->fontSizePt));
    }

    public function Ln(?float $h = null): void
    {
        $this->x = $this->lMargin;
        $this->y += $h ?? $this->fontSize * 1.4;
    }

    public function Line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->_out(sprintf(
            '%.2F %.2F m %.2F %.2F l S',
            $x1 * $this->k,
            ($this->h - $y1) * $this->k,
            $x2 * $this->k,
            ($this->h - $y2) * $this->k
        ));
    }

    public function Rect(float $x, float $y, float $w, float $h, string $style = 'D'): void
    {
        $op = match ($style) {
            'F'  => 'f',
            'FD', 'DF' => 'B',
            default => 'S',
        };
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F re %s',
            $x * $this->k,
            ($this->h - $y - $h) * $this->k,
            $w * $this->k,
            -$h * $this->k,
            $op
        ));
    }

    /**
     * @param int|string $border 0/1 for none/all sides, or any combination of 'L','T','R','B'
     * @param int        $ln     0 = stay on line, 1 = move to next line at left margin, 2 = move below, same x
     */
    public function Cell(float $w, float $h = 0, string $txt = '', $border = 0, int $ln = 0, string $align = '', bool $fill = false): void
    {
        $x = $this->x;
        $y = $this->y;

        if ($fill) {
            $this->Rect($x, $y, $w, $h, 'F');
        }

        if ($border === 1) {
            $this->Rect($x, $y, $w, $h, 'D');
        } elseif (is_string($border)) {
            if (str_contains($border, 'L')) $this->Line($x, $y, $x, $y + $h);
            if (str_contains($border, 'T')) $this->Line($x, $y, $x + $w, $y);
            if (str_contains($border, 'R')) $this->Line($x + $w, $y, $x + $w, $y + $h);
            if (str_contains($border, 'B')) $this->Line($x, $y + $h, $x + $w, $y + $h);
        }

        if ($txt !== '') {
            $strWidth = $this->GetStringWidth($txt);
            $dx = match ($align) {
                'R' => $w - $this->cMargin - $strWidth,
                'C' => ($w - $strWidth) / 2,
                default => $this->cMargin,
            };
            $baselineY = $y + $h / 2 + $this->fontSize * 0.32;
            $px = ($x + $dx) * $this->k;
            $py = ($this->h - $baselineY) * $this->k;

            $this->_out(sprintf('%.3F %.3F %.3F rg', ...array_map(fn ($c) => $c / 255, $this->textColor)));
            $this->_out('BT');
            $this->_out(sprintf('1 0 0 1 %.2F %.2F Tm', $px, $py));
            $this->_out('(' . $this->_escape($txt) . ') Tj');
            $this->_out('ET');
            $this->_out(sprintf('%.3F %.3F %.3F rg', ...array_map(fn ($c) => $c / 255, $this->fillColor)));
        }

        if ($ln === 1) {
            $this->x = $this->lMargin;
            $this->y += $h;
        } elseif ($ln === 2) {
            $this->y += $h;
        } else {
            $this->x += $w;
        }
    }

    public function Output(string $dest = 'I', string $name = 'doc.pdf'): string
    {
        $pdf = $this->_assemble();

        switch (strtoupper($dest)) {
            case 'F':
                file_put_contents($name, $pdf);
                return '';
            case 'S':
                return $pdf;
            case 'D':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $name . '"');
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                return '';
            case 'I':
            default:
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $name . '"');
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                return '';
        }
    }

    protected function _fontKey(): string
    {
        return $this->fontStyle === 'B' ? 'FB' : 'F1';
    }

    protected function _escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    protected function _out(string $s): void
    {
        $this->pages[$this->page] .= $s . "\n";
    }

    protected function _assemble(): string
    {
        $buffer = "%PDF-1.4\n";
        $offsets = [];
        $n = 0;

        $append = function (string $s) use (&$buffer) {
            $buffer .= $s . "\n";
        };
        $newobj = function () use (&$n, &$offsets, &$buffer) {
            $n++;
            $offsets[$n] = strlen($buffer);
            $buffer .= "$n 0 obj\n";
            return $n;
        };

        $pageCount = count($this->pages);
        $fontObjCount = 2; // Courier, Courier-Bold

        // Object numbering plan (all computed up front so every reference is a known integer):
        // 1..P            content streams
        // P+1..P+2        fonts (F1 = Courier, FB = Courier-Bold)
        // P+3..P+2+P      page objects
        // P+3+P           pages (Kids) dictionary
        // P+4+P           info dictionary
        // P+5+P           catalog
        $contentObjNums = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $contentObjNums[$i] = $i;
        }
        $fontF1 = $pageCount + 1;
        $fontFB = $pageCount + 2;
        $pageObjNums = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $pageObjNums[$i] = $pageCount + 2 + $i;
        }
        $pagesObjNum = $pageCount + 2 + $pageCount + 1;
        $infoObjNum = $pagesObjNum + 1;
        $catalogObjNum = $infoObjNum + 1;

        // 1. Content streams
        foreach ($this->pages as $i => $content) {
            $newobj();
            $append('<</Length ' . strlen($content) . '>>');
            $append('stream');
            $append($content);
            $append('endstream');
            $append('endobj');
        }

        // 2. Fonts
        $newobj();
        $append('<</Type/Font/Subtype/Type1/BaseFont/Courier/Encoding/WinAnsiEncoding>>');
        $append('endobj');

        $newobj();
        $append('<</Type/Font/Subtype/Type1/BaseFont/Courier-Bold/Encoding/WinAnsiEncoding>>');
        $append('endobj');

        // 3. Page objects
        $wPt = $this->w * $this->k;
        $hPt = $this->h * $this->k;
        foreach ($this->pages as $i => $content) {
            $newobj();
            $append(sprintf(
                '<</Type/Page/Parent %d 0 R/MediaBox[0 0 %.2F %.2F]/Contents %d 0 R' .
                '/Resources<</Font<</F1 %d 0 R/FB %d 0 R>>>>>>',
                $pagesObjNum,
                $wPt,
                $hPt,
                $contentObjNums[$i],
                $fontF1,
                $fontFB
            ));
            $append('endobj');
        }

        // 4. Pages dictionary
        $newobj();
        $kids = implode(' ', array_map(fn ($num) => "$num 0 R", $pageObjNums));
        $append("<</Type/Pages/Kids[$kids]/Count $pageCount>>");
        $append('endobj');

        // 5. Info
        $newobj();
        $append('<</Producer(GST Billing System)/Title(' . $this->_escape($this->title) . ')>>');
        $append('endobj');

        // 6. Catalog
        $newobj();
        $append("<</Type/Catalog/Pages $pagesObjNum 0 R>>");
        $append('endobj');

        // xref
        $xrefOffset = strlen($buffer);
        $append('xref');
        $append('0 ' . ($n + 1));
        $append('0000000000 65535 f ');
        for ($i = 1; $i <= $n; $i++) {
            $append(sprintf('%010d 00000 n ', $offsets[$i]));
        }

        $append('trailer');
        $append("<</Size " . ($n + 1) . "/Root $catalogObjNum 0 R/Info $infoObjNum 0 R>>");
        $append('startxref');
        $append((string) $xrefOffset);
        $buffer .= '%%EOF';

        return $buffer;
    }
}
