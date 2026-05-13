<?php

namespace Modules\Report\Reports;

use Database\Database;
use Loggers\Log;
use Modules\Report\Libs\ReportGenerator;

class generateReceiptReport
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

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generateReceiptReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->category ? $this->getDetailedReport() : $this->getSummaryReport();
            Log::sysLog('[generateReceiptReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generateReceiptReport] No records, returning 100');
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
            Log::sysLog('[generateReceiptReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generateReceiptReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generateReceiptReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generateReceiptReport] Rendering table, category=' . $this->category);

                if ($this->category == 1) {
                    $this->renderDetailedReport($report, $arrayData);
                } else {
                    $this->renderSummaryReport($report, $arrayData);
                }

                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generateReceiptReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generateReceiptReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generateReceiptReport] PDF ERROR: ' .
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
            Log::sysLog('[generateReceiptReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generateReceiptReport] FATAL ERROR: ' .
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
            $filter .= ' AND mx_payment_setup.opt_mx_payment_provider_id = ' . $this->filter_value;
        }

        $query = "SELECT
    mx_receipt.dat_paid_date AS [Date Paid],
    mx_invoice.txt_control_number AS [Control Number],
    mx_invoice.txt_invoice_number AS [Invoice Number],
    mx_receipt.txt_receipt_number AS [Receipt Number],
    mx_payment_provider.txt_name AS [Payment Provider],
    -- Aggregate the amounts for each currency using SUM, defaulting to 0 if NULL
    COALESCE(SUM(CASE WHEN mx_receipt.opt_mx_currency_id = 1 THEN mx_receipt.dbl_amount END), 0) AS [Amount(TZS)],
    COALESCE(SUM(CASE WHEN mx_receipt.opt_mx_currency_id = 2 THEN mx_receipt.dbl_amount END), 0) AS [Amount(USD)],
    mx_payment.txt_transaction_number AS [Transaction Number],
    mx_payment_status.txt_name AS [Payment Status]
FROM 
    mx_receipt
JOIN 
    mx_payment ON mx_receipt.opt_mx_payment_id = mx_payment.id
JOIN 
    mx_payment_status ON mx_payment.opt_mx_payment_status_id = mx_payment_status.id
JOIN 
    mx_payment_setup ON mx_payment.opt_mx_payment_setup_id = mx_payment_setup.id
JOIN 
    mx_payment_provider ON mx_payment_setup.opt_mx_payment_provider_id = mx_payment_provider.id
JOIN 
    mx_invoice ON mx_payment.opt_mx_invoice_id = mx_invoice.id
JOIN 
    mx_currency ON mx_receipt.opt_mx_currency_id = mx_currency.id
WHERE mx_receipt.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_receipt.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))" . $filter . "
GROUP BY
    mx_receipt.dat_paid_date,
    mx_invoice.txt_control_number,
    mx_invoice.txt_invoice_number,
    mx_receipt.txt_receipt_number,
    mx_payment_provider.txt_name,
    mx_payment.txt_transaction_number,
    mx_payment_status.txt_name";

        return $this->db->select($query, [':from_date' => $this->from_date, ':to_date' => $this->to_date]);
    }

    /**
     * Summary report query
     */
    private function getSummaryReport(): array
    {
        $filter = '';
        if ($this->filter_criteria && $this->filter_value) {
            $filter .= ' AND mx_payment_setup.opt_mx_payment_provider_id = ' . $this->filter_value;
        }

        $query = "SELECT
    mx_payment_provider.txt_name AS [Provider],
    -- Using COALESCE to handle null values, ensuring 0 if currency matches 1 (TZS)
    COALESCE(SUM(CASE WHEN mx_receipt.opt_mx_currency_id = 1 THEN mx_receipt.dbl_amount ELSE 0 END), 0) AS [Amount(TZS)],
    -- Using COALESCE to handle null values, ensuring 0 if currency matches 2 (USD)
    COALESCE(SUM(CASE WHEN mx_receipt.opt_mx_currency_id = 2 THEN mx_receipt.dbl_amount ELSE 0 END), 0) AS [Amount(USD)],
    -- Counting the number of receipts
    COUNT(mx_receipt.txt_receipt_number) AS [Receipt Count]
FROM
    mx_receipt
        JOIN mx_payment ON mx_receipt.opt_mx_payment_id = mx_payment.id
        JOIN mx_payment_status ON mx_payment.opt_mx_payment_status_id = mx_payment_status.id
        JOIN mx_payment_setup ON mx_payment.opt_mx_payment_setup_id = mx_payment_setup.id
        JOIN mx_payment_provider ON mx_payment_setup.opt_mx_payment_provider_id = mx_payment_provider.id
        JOIN mx_invoice ON mx_payment.opt_mx_invoice_id = mx_invoice.id
				WHERE mx_receipt.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_receipt.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) " . $filter . "
GROUP BY mx_payment_provider.txt_name";
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
        $report->setAligns(['C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([10, 20, 20, 20, 20, 25, 15, 15, 30, 20]);

        $headers = [
            'S/N',
            'Date Paid',
            'Control Number',
            'Invoice Number',
            'Receipt Number',
            'Payment Provider',
            'Amount(TZS)',
            'Amount(USD)',
            'Transaction Number',
            'Payment Status'
        ];

        $report->writeTableHeader($headers);

        $sn = 1;

        foreach ($arrayData as $row) {
            $report->writeTableRow([
                $sn,
                $row['Date Paid'],
                $row['Control Number'],
                $row['Invoice Number'],
                $row['Receipt Number'],
                $row['Payment Provider'],
                $row['Amount(TZS)'],
                $row['Amount(USD)'],
                $row['Transaction Number'],
                $row['Payment Status']
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

        $report->setAligns(['C', 'C', 'C', 'C', 'C']);
        $report->setWidths([20, 42, 42, 42, 44]);

        $tableHeader = [
            'S/N', 'Provider','Amount(TZS)','Amount(USD)', 'Receipt Count'
        ];

        $report->writeTableHeader($tableHeader);

        $sn = 1; $total_tzs = 0;$total_usd = 0; $totalAmount = 0;

        foreach ($arrayData as $row) {
            $total_tzs += $row['Amount(TZS)'];
            $total_usd += $row['Amount(USD)'];
            $totalAmount += $row['Receipt Count'];

            $rowData = [
                $sn,
                $row['Provider'],
                number_format((int) $row['Amount(TZS)'], 2),
                number_format((int) $row['Amount(USD)'], 2),
                number_format((int) $row['Receipt Count'], 2)
            ];


            $report->writeTableRow($rowData);
            $sn++;
        }

        $footerRow = [
            '',
            'TOTAL',
            number_format($total_tzs, 2),
            number_format($total_usd, 2),
            number_format($totalAmount, 2)
        ];

        $report->writeTableRow($footerRow);
        $report->closeTable();
    }
}
