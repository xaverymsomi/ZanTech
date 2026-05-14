<?php

namespace Modules\Report\Reports;

use Database\Database;
use Logging\Log;
use Modules\Report\Libs\ReportGenerator;

class generateGeneralReport
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
        $this->report_type        = $data['report_type'];
        $this->from_date          = date('Y-m-d', strtotime($data['from_date']));
        $this->to_date            = date('Y-m-d', strtotime($data['to_date']));
        $this->filter_criteria    = (int) $data['filter_criteria'];
        $this->group_criteria     = (int) $data['group_criteria'];
        $this->category           = (int) $data['category'];
        $this->title              = $data['title'];
        $this->filter_value       = $data['filter_value'];
        $this->filter_name        = $data['filter_name'];

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generateGeneralReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->getGeneralSummaryReport();

            Log::sysLog('[generateGeneralReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generateGeneralReport] No records, returning 100');
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
            Log::sysLog('[generateGeneralReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generateGeneralReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generateGeneralReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generateGeneralReport] Rendering table, category=' . $this->category);

                $this->renderGeneralReport($report, $arrayData);


                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generateGeneralReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generateGeneralReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generateGeneralReport] PDF ERROR: ' .
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
            Log::sysLog('[generateGeneralReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generateGeneralReport] FATAL ERROR: ' .
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
     * Summary report query
     */
    private function getGeneralSummaryReport(): array
    {
        $applicant = $this->getApplicantReport();
        $application = $this->getApplicationReport();
        $permit = $this->getPermitReport();
        $invoice = $this->getInvoiceReport();
        $finance = $this->getFinanceReport();
        $Receipt = $this->getReceiptReport();
        return [ 'Applicants' => $applicant, 'Permit' => $permit, 'Applications' => $application, 'Invoice' => $invoice, 'Finances' => $finance, 'Receipt'=>$Receipt];
    }

    private function getApplicantReport()
    {
        $sql = "SELECT
				    COUNT(DISTINCT CASE WHEN mx_gender.id = 1 THEN mx_applicant.txt_reference_number ELSE NULL END) AS [MALE],
				    COUNT(DISTINCT CASE WHEN mx_gender.id = 2 THEN mx_applicant.txt_reference_number ELSE NULL END) AS [FEMALE],
				    COUNT(DISTINCT CASE WHEN mx_applicant_log.opt_mx_identification_type_id = 1 THEN mx_applicant.txt_reference_number ELSE NULL END) AS [PASSPORT],
				    COUNT(DISTINCT mx_applicant.txt_reference_number) AS [TOTAL APPLICANTS]
				FROM mx_applicant
					JOIN mx_gender ON mx_applicant.opt_mx_gender_id = mx_gender.id
				JOIN mx_applicant_log ON mx_applicant_log.opt_mx_applicant_id = mx_applicant.id
				WHERE mx_applicant.dat_added_date  >= CAST(:from_date AS DATETIME) AND mx_applicant.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) ";
        return $this->db->select($sql, [ ':from_date' => $this->from_date, ':to_date' => $this->to_date ]);
    }

    private function getApplicationReport()
    {
        $sql = "SELECT
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 1 THEN mx_application.txt_reference ELSE NULL END) AS [Approved],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 2 THEN mx_application.txt_reference ELSE NULL END) AS [Pending Fee],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 3 THEN mx_application.txt_reference ELSE NULL END) AS [Pending Verification],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 8 THEN mx_application.txt_reference ELSE NULL END) AS [Pending Approval],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 9 THEN mx_application.txt_reference ELSE NULL END) AS [Denied],
    COUNT(DISTINCT mx_application.txt_reference) AS [Total Applications]
FROM mx_application
         JOIN mx_application_status ON mx_application.opt_mx_application_status_id = mx_application_status.id
WHERE mx_application.dat_added_date  >= CAST(:from_date AS DATETIME) AND mx_application.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) ";
        return $this->db->select($sql, [ ':from_date' => $this->from_date, ':to_date' => $this->to_date ]);
    }

    private function getPermitReport()
    {
        $sql = "SELECT
    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 1 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Normal],
    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 2 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Temporary],
    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 3 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Exemption],
    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 4 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Foreigner Marriage],
    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 5 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Student],
    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 7 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Diaspora],
    COUNT(DISTINCT mx_permit.txt_permit_number) AS [Total Permits]
FROM mx_permit
         JOIN mx_permit_type ON mx_permit_type.id = mx_permit.opt_mx_permit_type_id
WHERE mx_permit.dat_issued_date  >= CAST(:from_date AS DATETIME) AND mx_permit.dat_issued_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) ";
        return $this->db->select($sql, [ ':from_date' => $this->from_date, ':to_date' => $this->to_date ]);
    }

    private function getInvoiceReport()
    {
        $sql = "SELECT
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
WHERE mx_invoice.dat_added_date >= CAST(:from_date AS DATETIME) AND mx_invoice.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))
GROUP BY mx_invoice_status.txt_name";
        return $this->db->select($sql, [ ':from_date' => $this->from_date, ':to_date' => $this->to_date ]);
    }

    private function getFinanceReport()
    {
        $sql = "SELECT
    mx_payment_status.txt_name AS [Payment Status],
    COUNT(mx_payment.opt_mx_invoice_id) AS [Total Invoices],
    COUNT(DISTINCT mx_payment.txt_payment_reference_number) AS [Total Payments],
    COALESCE(SUM(CASE WHEN mx_payment.opt_mx_currency_id = 1 THEN mx_payment.dbl_amount ELSE 0 END), 0) AS [Amount(TZS)],
    COALESCE(SUM(CASE WHEN mx_payment.opt_mx_currency_id = 2 THEN mx_payment.dbl_amount ELSE 0 END), 0) AS [Amount(USD)]
    
FROM mx_payment
         JOIN mx_payment_status ON mx_payment.opt_mx_payment_status_id = mx_payment_status.id
         JOIN mx_payment_setup ON mx_payment.opt_mx_payment_setup_id = mx_payment_setup.id
         JOIN mx_payment_provider ON mx_payment_setup.opt_mx_payment_provider_id = mx_payment_provider.id
         
WHERE mx_payment.dat_added_date >= CAST(:from_date AS DATETIME) 
      AND mx_payment.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) group by mx_payment_status.txt_name
 ";
        return $this->db->select($sql, [ ':from_date' => $this->from_date, ':to_date' => $this->to_date ]);
    }

    private function getReceiptReport()
    {
        $sql = "SELECT
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
				WHERE mx_receipt.dat_added_date  >= CAST(:from_date AS DATETIME) AND mx_receipt.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) group by mx_payment_provider.txt_name";
        return $this->db->select($sql, [ ':from_date' => $this->from_date, ':to_date' => $this->to_date ]);
    }

    private function renderGeneralReport(ReportGenerator $report, array $arrayData): void
    {
        $firstSection = true;

        foreach ($arrayData as $sectionKey => $rows) {

            if (empty($rows)) continue;

            // Add a pagebreak BEFORE each section (except first)
            if (!$firstSection) {
                $report->mpdf->AddPage('L');
            }
            $firstSection = false;

            //----------------------------------------------
            // 1. SECTION HEADING (styled)
            //----------------------------------------------

            $heading = match ($sectionKey) {
                'Applicants'    => 'APPLICANT SUMMARY REPORT',
                'Permit' => 'PERMIT SUMMARY REPORT',
                'Applications'   => 'APPLICATION SUMMARY REPORT',
                'Invoice'   => 'INVOICE SUMMARY REPORT',
                'Finances'   => 'FINANCE SUMMARY REPORT',
                'Receipt'   => 'RECEIPT SUMMARY REPORT',
                default         => strtoupper($sectionKey),
            };

            $report->mpdf->WriteHTML('
            <div style="
                font-size:16pt;
                font-weight:bold;
                color:#084d93;
                text-align:center;
                border-bottom:1px solid #ccc;
                padding-bottom:8px;
                margin-bottom:15px;
                margin-top:5px;
            ">
                '.$heading.'
            </div>
        ');

            //----------------------------------------------
            // 2. AUTO-SET WIDTHS & ALIGNMENTS
            //----------------------------------------------

            $columns = array_keys($rows[0]);
            $colCount = count($columns);

            // Auto width (equal distribution)
            $autoWidth = round(280 / $colCount, 2); // A4 landscape width ≈ 280mm

            $report->setWidths(array_fill(0, $colCount, $autoWidth));

            // Auto align: numeric → right, others → left
            $aligns = [];
            foreach ($rows[0] as $v) {
                $aligns[] = is_numeric($v) ? 'R' : 'L';
            }
            $report->setAligns($aligns);

            //----------------------------------------------
            // 3. WRITE TABLE HEADER
            //----------------------------------------------

            $report->writeTableHeader($columns);

            //----------------------------------------------
            // 4. WRITE ROWS + optional totals calculation
            //----------------------------------------------

            $totals = array_fill(0, $colCount, 0);
            $hasNumericData = false;

            foreach ($rows as $idx => $row) {

                $formattedRow = [];
                $colIndex = 0;

                foreach ($row as $value) {
                    if (is_numeric($value)) {
                        $formattedRow[] = number_format($value, 2);
                        $totals[$colIndex] += $value;
                        $hasNumericData = true;
                    } else {
                        $formattedRow[] = $value;
                    }
                    $colIndex++;
                }

                $report->writeTableRow($formattedRow);
            }

            //----------------------------------------------
            // 5. TOTAl ROW (just like the old version)
            //----------------------------------------------

            if ($hasNumericData) {

                $footer = [];
                foreach ($totals as $t) {
                    $footer[] = $t > 0 ? number_format($t, 2) : '';
                }

                // Add TOTAL label on 2nd column
                if ($colCount > 1) {
                    $footer[1] = 'TOTAL';
                }

                $report->writeTableRow($footer);
            }

            //----------------------------------------------
            // 6. Close table before next section
            //----------------------------------------------
            $report->closeTable();

            //----------------------------------------------
            // 7. Add spacing
            //----------------------------------------------
            $report->mpdf->WriteHTML('<div style="height:20px;"></div>');
        }
    }

}
