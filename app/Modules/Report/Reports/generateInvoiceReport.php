<?php

namespace Modules\Report\Reports;

use Database\Database;
use Logging\Log;
use Modules\Report\Libs\ReportGenerator;

class generateInvoiceReport
{
    protected array $data;
    protected Database $db;

    protected $report_type;
    protected string $from_date;
    protected string $to_date;
    protected int $filter_criteria;
    protected int $group_criteria;
    protected int $category;
    protected string $title;
    protected $filter_value;
    protected string $filter_name;
    protected int $invoice;

    public function init(array $data): void
    {
        $this->data               = $data;
        $this->db                 = new Database();
        $this->report_type = $this->data['report_type'];
        $this->from_date = date('Y-m-d', strtotime($this->data['from_date']));
        $this->to_date = date('Y-m-d', strtotime($this->data['to_date']));
        $this->filter_criteria = $this->data['filter_criteria'];
        $this->group_criteria = $this->data['group_criteria'];
        $this->category = $this->data['category'];
        $this->title = $this->data['title'];
        $this->filter_value = $this->data['filter_value'];
        $this->filter_name = $this->data['filter_name'];
        $this->invoice = $this->data['invoice'];

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generateInvoiceReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->category ? $this->getDetailedReport() : $this->getSummaryReport();
            Log::sysLog('[generateInvoiceReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generateInvoiceReport] No records, returning 100');
                return;
            }

            // Ensure UTF-8 safe encoding
            $arrayData = json_decode(
                html_entity_decode(json_encode($arrayData), ENT_QUOTES, 'UTF-8'),
                true
            );

            // 2) Prepare filename
            $pdfName  = strtolower(str_replace(' ', '_', $this->title)) . '_' . time() . '.pdf';
            $folder   = ZT_PUBLIC_PATH . '/uploads/report/';
            $savePath = $folder . $pdfName;

            // Make sure folder exists
            $this->ensureDirectory($folder);
            Log::sysLog('[generateInvoiceReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generateInvoiceReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generateInvoiceReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generateInvoiceReport] Rendering table, category=' . $this->category);

                if ($this->category == 1) {
                    $this->renderDetailedReport($report, $arrayData);
                } else {
                    $this->renderSummaryReport($report, $arrayData);
                }

                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generateInvoiceReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generateInvoiceReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generateInvoiceReport] PDF ERROR: ' .
                    $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
                );

                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'status'  => 500,
                    'message' => 'pdf_generation_failed',
                    'error'   => $e->getMessage(),
                ]);
                return;
            }

            // 4) Clean any accidental output & send JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'status'   => 200,
                'pdf_name' => $pdfName,
                'records'  => $arrayData,
            ]);
            Log::sysLog('[generateInvoiceReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generateInvoiceReport] FATAL ERROR: ' .
                $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
            );

            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json', true, 500);
            echo json_encode([
                'status'  => 500,
                'message' => 'internal_error',
                'error'   => $e->getMessage(),
            ]);
            return;
        }
    }


    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            Log::sysLog("[generateReport] Directory created: $dir");
        }
    }

    /**
     * Detailed report query
     */
    private function getDetailedReport(): array
    {
        $filter = '';
        if ($this->filter_criteria && $this->filter_value) {
            $filter .= ' AND mx_invoice.opt_mx_invoice_status_id = ' . $this->filter_value;
        }
        if ($this->invoice) {
            $filter .= " AND mx_invoice.opt_mx_invoice_type_id =  " . $this->invoice;
        }

        $query = "SELECT
       mx_invoice.txt_invoice_number                                                      AS [Invoice],
       ISNULL(mx_invoice.txt_control_number, '')                                          AS [Control Number],
       mx_application.txt_reference                                                       AS [Reference],
       CONCAT(mx_applicant.txt_first_name, ' ', mx_applicant.txt_middle_name, ' ',
              mx_applicant.txt_last_name)                                                 AS [Applicant],
       CASE
        WHEN mx_invoice.opt_mx_currency_id = 1 THEN mx_invoice.dbl_amount
        ELSE 0
        END AS [Amount(TZS)],
    CASE
        WHEN mx_invoice.opt_mx_currency_id = 2 THEN mx_invoice.dbl_amount
        ELSE 0
        END AS [Amount(USD)],
       mx_currency.txt_abbreviation                                                       AS [Currency],
       (SELECT CONVERT(varchar, mx_invoice.dat_added_date, 20) AS [DD MM YYYY HH:II:SS])  AS [Added Date],
       (SELECT CONVERT(varchar, mx_invoice.dat_expiry_date, 20) AS [DD MM YYYY HH:II:SS]) AS [Expiry Date],
       mx_invoice_type.txt_name                                                           AS [Invoice Type],
       mx_invoice_status.txt_name                                                         AS [Status]
FROM mx_invoice
         JOIN mx_application ON mx_invoice.opt_mx_application_id = mx_application.id
         JOIN mx_application_log ON mx_application_log.opt_mx_application_id = mx_application.id
         JOIN mx_applicant ON mx_application.entity_id = mx_applicant.id AND mx_application.opt_mx_entity_type_id = 2
         JOIN mx_currency ON mx_invoice.opt_mx_currency_id = mx_currency.id
         JOIN mx_application_type ON mx_application_log.opt_mx_application_type_id = mx_application_type.id
         JOIN mx_invoice_status ON mx_invoice.opt_mx_invoice_status_id = mx_invoice_status.id
         JOIN mx_invoice_type ON mx_invoice.opt_mx_invoice_type_id = mx_invoice_type.id
WHERE mx_invoice.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_invoice.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))" . $filter;

        return $this->db->select($query, [':from_date' => $this->from_date, ':to_date' => $this->to_date]);
    }

    /**
     * Summary report query
     */
    private function getSummaryReport(): array
    {
        $filter = '';
        if ($this->filter_criteria && $this->filter_value) {
            $filter .= ' AND mx_invoice.opt_mx_invoice_status_id = ' . $this->filter_value;
        }
        if ($this->invoice) {
            $filter .= " AND mx_invoice.opt_mx_invoice_type_id =  " . $this->invoice;
        }

        $query = "SELECT
    mx_invoice_status.txt_name AS [Invoice Status],
    COUNT(mx_invoice.txt_invoice_number) AS [Total Invoices],
    SUM(CASE WHEN mx_invoice.opt_mx_currency_id = 1 THEN mx_invoice.dbl_amount ELSE 0 END) AS [Amount(TZS)],
    SUM(CASE WHEN mx_invoice.opt_mx_currency_id = 2 THEN mx_invoice.dbl_amount ELSE 0 END) AS [Amount(USD)]

FROM mx_invoice
         JOIN mx_application ON mx_invoice.opt_mx_application_id = mx_application.id
         JOIN mx_application_log ON mx_application_log.opt_mx_application_id = mx_application.id
         JOIN mx_applicant ON mx_application.entity_id = mx_applicant.id AND mx_application.opt_mx_entity_type_id = 2
         JOIN mx_currency ON mx_invoice.opt_mx_currency_id = mx_currency.id
         JOIN mx_application_type ON mx_application_log.opt_mx_application_type_id = mx_application_type.id
         JOIN mx_invoice_status ON mx_invoice.opt_mx_invoice_status_id = mx_invoice_status.id
         JOIN mx_invoice_type ON mx_invoice.opt_mx_invoice_type_id = mx_invoice_type.id
WHERE mx_invoice.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_invoice.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) " . $filter . "
GROUP BY mx_invoice_status.txt_name";
        return $this->db->select($query, [':from_date' => $this->from_date, ':to_date' => $this->to_date]);
    }

    /**
     * Detailed complaint report (category = 1)
     */
    private function renderDetailedReport(ReportGenerator $report, array $arrayData): void
    {
        if (empty($arrayData)) {
            return;
        }

        // 8 columns: SN, DATE, INSTITUTION, CATEGORY, ACCUSED, DESCRIPTION, SUGGESTION, STATUS
        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([ 15, 25, 40, 25, 25, 25, 25, 25, 25, 25, 25]);

        $headers = [
            'S/N',
            'Invoice',
            'Control Number',
            'Reference',
            'Applicant',
            'Amount(TZS)',
            'Amount(TZS)',
            'Currency',
            'Added Date',
            'Invoice Type',
            'Status'
        ];

        $report->writeTableHeader($headers);

        $sn = 1;

        foreach ($arrayData as $row) {
            $report->writeTableRow([
                $sn,
                $row['Invoice'],
                $row['Control Number'],
                $row['Reference'],
                $row['Applicant'],
                $row['Amount(TZS)'],
                $row['Amount(TZS)'],
                $row['Currency'],
                $row['Added Date'],
                $row['Invoice Type'],
                $row['Status']
            ]);

            $sn++;
        }

        $report->closeTable();
    }

    /**
     * Summary report (category != 1)
     */
    private function renderSummaryReport(ReportGenerator $report, array $arrayData): void
    {
        if (empty($arrayData)) {
            return;
        }

        $report->setAligns([ 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([ 15, 50, 40, 40, 40]);

        $tableHeader = [
            'S/N',
            'Invoice Status',
            'Total Invoices',
            'Amount(TZS)',
            'Amount(USD)'
        ];

        $report->writeTableHeader($tableHeader);

        $sn = 1;
        $invoice = 0;
        $amount_tzs = 0;
        $amount_usd = 0;

        foreach ($arrayData as $row) {
            $invoice += $row['Total Invoices'];
            $amount_tzs += $row['Amount(TZS)'];
            $amount_usd += $row['Amount(USD)'];

            $rowData = [
                $sn,
                $row['Invoice Status'],
                number_format((int) $row['Total Invoices'], 2),
                number_format((int) $row['Amount(TZS)'], 2),
                number_format((int) $row['Amount(USD)'], 2)
            ];


            $report->writeTableRow($rowData);
            $sn++;
        }

        $footerRow = [
            '',
            'TOTAL',
            number_format($invoice, 2),
            number_format($amount_tzs, 2),
            number_format($amount_usd, 2)
        ];

        $report->writeTableRow($footerRow);
        $report->closeTable();
    }
}
