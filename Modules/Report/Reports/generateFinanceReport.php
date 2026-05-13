<?php

namespace Modules\Report\Reports;

use Database\Database;
use Loggers\Log;
use Modules\Report\Libs\ReportGenerator;

class generateFinanceReport
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
    protected int $provider;

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
        $this->provider = $this->data['provider'];

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generateFinanceReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->category ? $this->getDetailedReport() : $this->getSummaryReport();
            Log::sysLog('[generateFinanceReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generateFinanceReport] No records, returning 100');
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
            Log::sysLog('[generateFinanceReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generateFinanceReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generateFinanceReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generateFinanceReport] Rendering table, category=' . $this->category);

                if ($this->category == 1) {
                    $this->renderDetailedReport($report, $arrayData);
                } else {
                    $this->renderSummaryReport($report, $arrayData);
                }

                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generateFinanceReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generateFinanceReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generateFinanceReport] PDF ERROR: ' .
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
            Log::sysLog('[generateFinanceReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generateFinanceReport] FATAL ERROR: ' .
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
            $filter .= ' AND mx_payment.opt_mx_payment_status_id = ' . $this->filter_value;
        }
        if ($this->provider) {
            $filter .= " AND mx_payment_setup.opt_mx_payment_provider_id = " . $this->provider;
        }

        $query = "SELECT
                    mx_payment.txt_transaction_number as [Reference],
                    mx_invoice.txt_invoice_number as [Invoice Number],
                    ISNULL(CASE WHEN mx_payment.opt_mx_currency_id = 1 THEN mx_payment.dbl_amount ELSE 0 END, 0) AS [Amount(TZS)],
                    ISNULL(CASE WHEN mx_payment.opt_mx_currency_id = 2 THEN mx_payment.dbl_amount ELSE 0 END, 0) AS [Amount(USD)],
                    mx_payment.dat_paid_date as [Payment Date],
                    mx_payment.txt_payment_reference_number as [Payment Reference],
                    mx_payment_provider.txt_name as [Payment Provider],
                    mx_payment_status.txt_name as [Status]
                FROM mx_payment
                         JOIN mx_payment_status ON mx_payment.opt_mx_payment_status_id = mx_payment_status.id
                         JOIN mx_currency ON mx_payment.opt_mx_currency_id = mx_currency.id
                         JOIN mx_invoice ON mx_payment.opt_mx_invoice_id = mx_invoice.id
                         JOIN mx_payment_setup ON mx_payment.opt_mx_payment_setup_id = mx_payment_setup.id
                         JOIN mx_payment_provider ON mx_payment_setup.opt_mx_payment_provider_id = mx_payment_provider.id
                WHERE mx_payment.dat_added_date >= CAST(:from_date AS DATETIME) 
                AND mx_payment.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))". $filter;
        return $this->db->select($query, [':from_date' => $this->from_date, ':to_date' => $this->to_date]);
    }

    /**
     * Summary report query
     */
    private function getSummaryReport(): array
    {
        $filter = '';
        if ($this->filter_criteria && $this->filter_value) {
            $filter .= ' AND mx_payment.opt_mx_payment_status_id = ' . $this->filter_value;
        }
        if ($this->provider) {
            $filter .= " AND mx_payment_setup.opt_mx_payment_provider_id = " . $this->provider;
        }

        $query = "SELECT
    COUNT(mx_payment.opt_mx_invoice_id) AS [Total Invoices],
    COUNT(DISTINCT mx_payment.txt_payment_reference_number) AS [Total Payments],
    COALESCE(SUM(CASE WHEN mx_payment.opt_mx_currency_id = 1 THEN mx_payment.dbl_amount ELSE 0 END), 0) AS [Amount(TZS)],
    COALESCE(SUM(CASE WHEN mx_payment.opt_mx_currency_id = 2 THEN mx_payment.dbl_amount ELSE 0 END), 0) AS [Amount(USD)],
    mx_payment_status.txt_name AS [Payment Status]
FROM mx_payment
         JOIN mx_payment_status ON mx_payment.opt_mx_payment_status_id = mx_payment_status.id
         JOIN mx_payment_setup ON mx_payment.opt_mx_payment_setup_id = mx_payment_setup.id
         JOIN mx_payment_provider ON mx_payment_setup.opt_mx_payment_provider_id = mx_payment_provider.id
         
WHERE mx_payment.dat_added_date >= CAST(:from_date AS DATETIME) 
      AND mx_payment.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))
 ". $filter ."
GROUP BY 
    mx_payment_status.txt_name";
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
        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([ 10, 25, 25, 25, 25, 20, 25, 25, 20]);

        $headers = [
            'S/N',
            'Reference',
            'Invoice Number',
            'Amount(TZS)',
            'Amount(USD)',
            'Payment Date',
            'Payment Reference',
            'Payment Provider',
            'Status'
        ];

        $report->writeTableHeader($headers);

        $sn = 1;

        foreach ($arrayData as $row) {
            $report->writeTableRow([
                $sn,
                $row['Reference'],
                $row['Invoice Number'],
                $row['Amount(TZS)'],
                $row['Amount(USD)'],
                $row['Payment Date'],
                $row['Payment Reference'],
                $row['Payment Provider'],
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

        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C' ]);
        $report->setWidths([ 15, 35, 35, 35, 35, 40]);

        $tableHeader = [
            'S/N', 'Total Invoices','Total Payments', 'Amount(TZS)','Amount(USD)', 'Payment Status'
        ];

        $report->writeTableHeader($tableHeader);

        $sn = 1;
        $invoices = 0;
        $payments = 0;
        $amount_tzs = 0;
        $amount_usd = 0;

        foreach ($arrayData as $row) {
            $invoices += $row['Total Invoices'];
            $payments += $row['Total Payments'];
            $amount_tzs += $row['Amount(TZS)'];
            $amount_usd += $row['Amount(USD)'];

            $rowData = [
                $sn,
                number_format((int) $row['Total Invoices'], 2),
                number_format((int) $row['Total Payments'], 2),
                number_format((int) $row['Amount(TZS)'], 2),
                number_format((int) $row['Amount(USD)'], 2),
                $row['Payment Status']
            ];


            $report->writeTableRow($rowData);
            $sn++;
        }

        $footerRow = [
            'TOTAL',
            number_format($invoices, 2),
            number_format($payments, 2),
            number_format($amount_tzs, 2),
            number_format($amount_usd, 2),
            ''
        ];

        $report->writeTableRow($footerRow);
        $report->closeTable();
    }
}
