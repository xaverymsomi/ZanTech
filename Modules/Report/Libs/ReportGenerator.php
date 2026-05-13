<?php

namespace Modules\Report\Libs;

use Loggers\Log;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Output\Destination;

/**
 * mPDF-based ReportGenerator
 *
 * NOTE:
 * - This is NOT a drop-in replacement for your FPDI version.
 * - It keeps a similar public API (setters, table helpers) so you can migrate gradually.
 * - Layout is done via HTML/CSS, which is what mPDF is best at.
 */
class ReportGenerator
{
    /** @var Mpdf */
    public Mpdf $mpdf;

    // --- Your original business properties (simplified but compatible) ---
    protected array $array_footer = [];
    protected array $array_company = [];
    protected array $array_data = [];

    protected ?string $report_heading = null;
    protected ?string $from_date      = null;
    protected ?string $to_date        = null;
    protected $table_title            = null; // can be string/array like before
    protected array $widths           = [];
    protected array $aligns           = [];
    protected ?int $total_rows        = null;

    protected ?int $adults   = null;
    protected ?int $children = null;
    protected ?int $infants  = null;

    protected ?string $from = null;
    protected ?string $to   = null;

    protected ?string $officer_name = null;
    protected ?int $header          = 1;
    protected ?string $channel_name = null;

    // internal table buffer for generic table render
    protected bool   $tableOpen = false;
    protected string $tableBuffer = '';

    public function __construct(array $config = [])
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

        // Make sure temp dir exists & is writable
        $tempDir = __DIR__ . '/tmp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Use mPDF default + ensure utf-8 + Unicode font
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData          = $defaultFontConfig['fontdata'];

        $mpdfConfig = array_merge([
            'tempDir'      => $tempDir,
            'mode'         => 'utf-8',
            'format'       => 'A4-L',
            'fontDir'      => $fontDirs,
            'fontdata'     => $fontData + [
                    'dejavusans' => [
                        'R'  => 'DejaVuSans.ttf',
                        'B'  => 'DejaVuSans-Bold.ttf',
                        'I'  => 'DejaVuSans-Oblique.ttf',
                        'BI' => 'DejaVuSans-BoldOblique.ttf',
                    ],
                ],
            'default_font' => 'dejavusans',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 8,
            'margin_bottom' => 20,
        ], $config);

        $this->mpdf = new Mpdf($mpdfConfig);
        $this->mpdf->setAutoTopMargin = 'stretch';


        // These *must* be set on the instance, not config array
        $this->mpdf->debug = false;
        $this->mpdf->useSubstitutions = false;
        $this->mpdf->showImageErrors = false;

        $this->mpdf->SetHTMLFooter(
            '<div style="font-size:8pt;color:#6a6a6a;text-align:right;">
            Page {PAGENO} of {nbpg}
         </div>'
        );
    }

    // ------------------------------------------------------------------
    // Basic setters / getters (similar to your FPDI class)
    // ------------------------------------------------------------------

    public function setHeader(int $header): void
    {
        $this->header = $header;
        $this->applyHeader();
    }

    public function setChannelName(?string $channel): void
    {
        $this->channel_name = $channel;
        $this->applyHeader();
    }

    public function setOfficerName(?string $officer): void
    {
        $this->officer_name = $officer;
        $this->applyHeader();
    }

    public function getOfficerName(): ?string
    {
        return $this->officer_name;
    }

    public function setAdults(?int $adults): void { $this->adults = $adults; }
    public function setChildren(?int $children): void { $this->children = $children; }
    public function setInfants(?int $infants): void { $this->infants = $infants; }

    public function getAdults(): ?int { return $this->adults; }
    public function getChildren(): ?int { return $this->children; }
    public function getInfants(): ?int { return $this->infants; }

    public function setArrayData(array $array_data): void
    {
        $this->array_data = $array_data;
    }

    public function setArrayFooter(array $array_footer): void
    {
        $this->array_footer = $array_footer;
    }

    public function setArrayCompany(array $array_company): void
    {
        $this->array_company = $array_company;
    }

    public function setReportHeading(?string $report_heading): void
    {
        $this->report_heading = $report_heading;
        $this->applyHeader();
    }

    public function getReportHeading(): ?string
    {
        return $this->report_heading;
    }

    public function setTableTitle($table_title): void
    {
        $this->table_title = $table_title;
    }

    public function getTableTitle()
    {
        return $this->table_title;
    }

    public function setWidths(array $widths): void
    {
        $this->widths = $widths;
    }

    public function getWidths(): array
    {
        return $this->widths;
    }

    public function setAligns(array $aligns): void
    {
        $this->aligns = $aligns;
    }

    public function getAligns(): array
    {
        return $this->aligns;
    }

    public function getFromDate(): ?string
    {
        return $this->from_date;
    }

    public function getToDate(): ?string
    {
        return $this->to_date;
    }

    public function setFromDate(?string $from_date): void
    {
        $this->from_date = $from_date;
        $this->applyHeader();
    }

    public function setToDate(?string $to_date): void
    {
        $this->to_date = $to_date;
        $this->applyHeader();
    }

    public function getTotalRows(): ?int
    {
        return $this->total_rows;
    }

    public function setTotalRows(?int $total_rows): void
    {
        $this->total_rows = $total_rows;
    }

    public function setFrom(?string $from): void { $this->from = $from; }
    public function setTo(?string $to): void { $this->to = $to; }

    public function getFrom(): ?string { return $this->from; }
    public function getTo(): ?string { return $this->to; }

    // ------------------------------------------------------------------
    // HEADER / FOOTER (mPDF style)
    // ------------------------------------------------------------------

    /**
     * Build and register the repeating HTML header for all pages,
     * based on current properties (heading, dates, officer, etc.).
     */

    protected function applyHeader(): void
    {
        if ($this->header !== 1) {
            $this->mpdf->SetHTMLHeader('');
            return;
        }

        $companyName   = 'WORK PERMIT REGISTRATION';

        $fromStr = $this->from_date ? date('d M Y', strtotime($this->from_date)) : '';
        $toStr   = $this->to_date   ? date('d M Y', strtotime($this->to_date))   : '';

        $period = '';
        if ($fromStr && $toStr) {
            $period = "As of <strong>{$fromStr}</strong> to <strong>{$toStr}</strong>";
        } elseif ($fromStr) {
            $period = "As of <strong>{$fromStr}</strong>";
        }

        $heading = strtoupper($this->report_heading ?? '');

        $html = '
    <div style="text-align:center; margin-top:10px;">
        <img src="' . $this->escape($this->getLogoPath()) . '" 
             style="height:60px; margin-bottom:8px;" />

        <div style="font-size:14pt; font-weight:bold; color:#084d93;">
            ' . $this->escape($companyName) . '
        </div>

        <div style="font-size:12pt; font-weight:bold; margin-top:5px;">
            ' . $this->escape($heading) . '
        </div>

        <div style="font-size:10pt; color:#444; margin-top:3px;">
            ' . $period . '
        </div>

        <div style="height:40px;"></div>
    </div>
    ';


        $this->mpdf->SetHTMLHeader($html);
    }

    protected function getLogoPath(): string
    {
        return ZT_PUBLIC_PATH . '/assets/images/smz_logo.png';
    }

    // ------------------------------------------------------------------
    // GENERIC TABLE HELPERS (replacement for WriteTableHeader / WriteTableRow)
    // ------------------------------------------------------------------

    /**
     * Start a simple table and write the header row.
     * You can call this instead of old WriteTableHeader().
     */
    public function writeTableHeader(array $headerCells): void
    {
        $this->tableOpen   = true;
        $this->tableBuffer = '';

        $this->tableBuffer .= '
        <table width="100%" autosize="1" style="border-collapse:collapse; font-size:8pt;">
            <thead>
            <tr>';

        foreach ($headerCells as $i => $label) {
            $width = $this->widths[$i] ?? null;
            $align = $this->mapAlign($this->aligns[$i] ?? 'L');

            $style = 'padding:3px 4px;border:0.2mm solid #c4c4c4;background-color:#e0e0e0;';
            if ($width) {
                $style .= 'width:' . (float)$width . 'mm;';
            }
            $style .= 'text-align:' . $align . '; color:#6a6a6a; font-weight:bold;';

            $this->tableBuffer .= '<th style="' . $style . '">' . $this->escape($label) . '</th>';
        }

        $this->tableBuffer .= '
            </tr>
            </thead>
            <tbody>';
    }

    /**
     * Append a data row to the currently open table.
     * Equivalent high-level role to your WriteTableRow().
     */
    public function writeTableRow(array $cells): void
    {
        if (!$this->tableOpen) {
            // In case dev forgot to call writeTableHeader first
            $this->writeTableHeader(array_keys($cells));
        }

        $this->tableBuffer .= '<tr>';

        foreach ($cells as $i => $value) {
            $width = $this->widths[$i] ?? null;
            $align = $this->mapAlign($this->aligns[$i] ?? 'L');

            $style = 'padding:3px 4px;border:0.2mm solid #c4c4c4;';
            if ($width) {
                $style .= 'width:' . (float)$width . 'mm;';
            }
            $style .= 'text-align:' . $align . '; color:#525252;';

            $this->tableBuffer .= '<td style="' . $style . '">' . $this->escape($value) . '</td>';
        }

        $this->tableBuffer .= '</tr>';
    }

    /**
     * Flush the buffered table HTML into the PDF and close it.
     * Call once after you have written all rows.
     */
    public function closeTable(): void
    {
        if (!$this->tableOpen) {
            return;
        }
        $this->tableBuffer .= '</tbody></table>';
        $this->mpdf->WriteHTML($this->tableBuffer);
        $this->tableOpen   = false;
        $this->tableBuffer = '';
    }

    // ------------------------------------------------------------------
    // Example: Render Audit Trail Report (port of writeAuditTrailReport)
    // ------------------------------------------------------------------

    public function writeAuditTrailReport(): void
    {
        // Start page
        $this->applyHeader();
        $this->mpdf->AddPage('L');

        // Header / title
        if ($this->report_heading) {
            $this->mpdf->WriteHTML(
                '<div style="font-size:10pt;font-weight:bold;color:#6a6a6a;margin-bottom:4mm;">
                    ' . $this->escape($this->report_heading) . '
                 </div>'
            );
        }

        // Table header
        $headers = ['Date', 'Section', 'Action', 'Affected Record', 'Added By', 'Attended By', 'Attended Date', 'Status'];
        $this->setWidths([25, 20, 25, 70, 25, 25, 25, 20]);
        $this->setAligns(['C','C','C','L','C','C','C','C']);

        $this->writeTableHeader($headers);

        // Rows
        foreach ($this->array_data as $row) {
            $this->writeTableRow([
                $row['Date']          ?? '',
                $row['Section']       ?? '',
                $row['Action']        ?? '',
                $row['Affected Record'] ?? '',
                $row['Added By']      ?? '',
                $row['Attended By']   ?? '',
                $row['Attended Date'] ?? '',
                $row['Status']        ?? '',
            ]);
        }

        $this->closeTable();
    }

    // ------------------------------------------------------------------
    // Output helpers
    // ------------------------------------------------------------------

    public function outputInline(string $filename = 'report.pdf'): void
    {
        $this->mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    }

    public function outputToFile(string $filePath): void
    {
        // Catch errors/warnings from mPDF and log them, but ignore the known benign
        // libpng ICC profile warning to avoid noisy logs.
        set_error_handler(function ($severity, $message, $file, $line) {
            // Ignore known harmless libpng ICC warning
            if ($severity === E_WARNING && str_contains($message, 'iCCP: known incorrect sRGB profile')) {
                return true; // handled/ignored
            }

            Log::sysLog("[mPDF PHP Error] $message in $file on line $line");
            error_log("[mPDF PHP Error] $message in $file on line $line");
            return false; // let normal error handling continue if any
        });

        error_log("[ReportGenerator] outputToFile() starting...");

        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            error_log("[ReportGenerator] Directory missing. Creating: $dir");
            mkdir($dir, 0777, true);
        }

        if (!is_writable($dir)) {
            error_log("[ReportGenerator] Directory NOT WRITABLE: $dir");
            throw new \RuntimeException("Directory not writable: " . $dir);
        }

        try {
            error_log("[ReportGenerator] Before mPDF->Output()");

            $this->mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

            error_log("[ReportGenerator] After mPDF->Output() — SUCCESS");
            Log::sysLog("[ReportGenerator] PDF successfully written: $filePath");

        } catch (\Throwable $e) {

            error_log("[ReportGenerator] **** FATAL mPDF EXCEPTION ****");
            error_log("[ReportGenerator] Message: " . $e->getMessage());
            error_log("[ReportGenerator] File: " . $e->getFile());
            error_log("[ReportGenerator] Line: " . $e->getLine());

            Log::sysLog("[ReportGenerator] mPDF ERROR: " . $e->getMessage());

            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Small internal helpers
    // ------------------------------------------------------------------

    protected function mapAlign(string $align): string
    {
        $align = strtoupper($align);
        return match ($align) {
            'C'     => 'center',
            'R'     => 'right',
            default => 'left',
        };
    }

    protected function escape($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
