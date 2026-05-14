<?php

namespace Modules\Report\Reports;

use Database\Database;
use Logging\Log;
use Modules\Report\Libs\ReportGenerator;

class generateApplicationReport
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
    protected int $application;

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
        $this->application = $this->data['application'];

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generateApplicationReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->category ? $this->getDetailedReport() : $this->getSummaryReport();
            Log::sysLog('[generateApplicationReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generateApplicationReport] No records, returning 100');
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
            Log::sysLog('[generateApplicationReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generateApplicationReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generateApplicationReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generateApplicationReport] Rendering table, category=' . $this->category);

                if ($this->category == 1) {
                    $this->renderDetailedReport($report, $arrayData);
                } else {
                    $this->renderSummaryReport($report, $arrayData);
                }

                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generateApplicationReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generateApplicationReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generateApplicationReport] PDF ERROR: ' .
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
            Log::sysLog('[generateApplicationReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generateApplicationReport] FATAL ERROR: ' .
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
            $filter .= ' AND mx_institution.id = ' . $this->filter_value;
        }
        if ($this->application) {
            $filter .= " AND mx_application.opt_mx_application_status_id = " . $this->application;
        }

        $query = "SELECT
       mx_application.txt_reference                                                          AS [Reference Number],
       CONCAT(mx_applicant.txt_first_name, ' ', mx_applicant.txt_middle_name, ' ',
              mx_applicant.txt_last_name)                                                    AS [Applicant],
       mx_service_type.txt_name                                                              AS [Service Type],
       mx_application_type.txt_name                                                          AS [Application Type],
       ISNULL(mx_application_log.txt_previous_permit_number, 'NONE')                         AS [Previous Permit],
       mx_application_status.txt_name                                                        AS [Application Status]
FROM mx_application
         JOIN mx_application_log ON mx_application_log.opt_mx_application_id = mx_application.id
         JOIN mx_application_type ON mx_application_log.opt_mx_application_type_id = mx_application_type.id
         JOIN mx_service_type ON mx_application_log.opt_mx_service_type_id = mx_service_type.id
         JOIN mx_application_status ON mx_application.opt_mx_application_status_id = mx_application_status.id
         LEFT JOIN mx_applicant ON mx_applicant.id = mx_application.entity_id AND mx_application.opt_mx_entity_type_id = 2
         LEFT JOIN mx_applicant_institution ON mx_applicant.id = mx_applicant_institution.opt_mx_applicant_id
         LEFT JOIN mx_institution ON mx_institution.id = mx_applicant_institution.opt_mx_institution_id
WHERE mx_application.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_application.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))" . $filter;

        return $this->db->select($query, [':from_date' => $this->from_date, ':to_date' => $this->to_date]);
    }

    /**
     * Summary report query
     */
    private function getSummaryReport(): array
    {
        $filter = '';
        if ($this->filter_criteria && $this->filter_value) {
            $filter .= ' AND mx_institution.id = ' . $this->filter_value;
        }
        if ($this->application) {
            $filter .= " AND mx_application.opt_mx_application_status_id = " . $this->application;
        }

        $query = "SELECT
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 1 THEN mx_application.txt_reference ELSE NULL END) AS [Approved],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 2 THEN mx_application.txt_reference ELSE NULL END) AS [Pending Fee],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 3 THEN mx_application.txt_reference ELSE NULL END) AS [Pending Verification],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 8 THEN mx_application.txt_reference ELSE NULL END) AS [Pending Approval],
    COUNT(DISTINCT CASE WHEN mx_application.opt_mx_application_status_id = 9 THEN mx_application.txt_reference ELSE NULL END) AS [Denied],
    COUNT(DISTINCT mx_application.txt_reference) AS [Total Applications]
FROM mx_application
         JOIN mx_application_status ON mx_application.opt_mx_application_status_id = mx_application_status.id
         LEFT JOIN mx_applicant ON mx_applicant.id = mx_application.entity_id AND mx_application.opt_mx_entity_type_id = 2
         LEFT JOIN mx_applicant_institution ON mx_applicant.id = mx_applicant_institution.opt_mx_applicant_id
         LEFT JOIN mx_institution ON mx_institution.id = mx_applicant_institution.opt_mx_institution_id
WHERE mx_application.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_application.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))" . $filter;
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
        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([ 15, 30, 35, 25, 30, 30, 30]);

        $headers = [
            'S/N',
            'Reference Number',
            'Applicant',
            'Service Type',
            'Application Type',
            'Previous Permit',
            'Application Status'
        ];

        $report->writeTableHeader($headers);

        $sn = 1;

        foreach ($arrayData as $row) {
            $report->writeTableRow([
                $sn,
                $row['Reference Number'],
                $row['Applicant'],
                $row['Service Type'],
                $row['Application Type'],
                $row['Previous Permit'],
                $row['Application Status']
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

        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([ 20, 25, 30, 30, 30, 25, 30 ]);

        $tableHeader = [
            'S/N', 'Approved', 'Pending Fee', 'Pending Verification', 'Pending Approval', 'Denied', 'Total Applications'
        ];

        $report->writeTableHeader($tableHeader);

        $sn = 1;
        $approved = 0;
        $pending_fee = 0;
        $pending_verification = 0;
        $pending_approval = 0;
        $denied = 0;
        $application = 0;

        foreach ($arrayData as $row) {
            $approved += $row['Approved'];
            $pending_fee += $row['Pending Fee'];
            $pending_verification += $row['Pending Verification'];
            $pending_approval += $row['Pending Approval'];
            $denied += $row['Denied'];
            $application += $row['Total Applications'];

            $rowData = [
                $sn,
                number_format((int) $row['Approved'], 2),
                number_format((int) $row['Pending Fee'], 2),
                number_format((int) $row['Pending Verification'], 2),
                number_format((int) $row['Pending Approval'], 2),
                number_format((int) $row['Denied'], 2),
                number_format((int) $row['Total Applications'], 2)
            ];


            $report->writeTableRow($rowData);
            $sn++;
        }

        $footerRow = [
            'TOTAL',
            number_format($approved, 2),
            number_format($pending_fee, 2),
            number_format($pending_verification, 2),
            number_format($pending_approval, 2),
            number_format($denied, 2),
            number_format($application, 2)
        ];

        $report->writeTableRow($footerRow);
        $report->closeTable();
    }
}
