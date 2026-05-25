<?php

namespace Modules\Report\Reports;

use Database\Database;
use Logging\Log;
use Modules\Report\Libs\ReportGenerator;

class generatePermitReport
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
    protected int $permittype;
    protected int $permitstatus;

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
        $this->permittype = $this->data['permittype'];
        $this->permitstatus = $this->data['permitstatus'];

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generatePermitReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->category ? $this->getDetailedReport() : $this->getSummaryReport();

            Log::sysLog('[generatePermitReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generatePermitReport] No records, returning 100');
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
            Log::sysLog('[generatePermitReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generatePermitReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generatePermitReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generatePermitReport] Rendering table, category=' . $this->category);

                if ($this->category == 1) {
                    $this->renderDetailedReport($report, $arrayData);
                } else {
                    $this->renderSummaryReport($report, $arrayData);
                }

                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generatePermitReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generatePermitReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generatePermitReport] PDF ERROR: ' .
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
            Log::sysLog('[generatePermitReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generatePermitReport] FATAL ERROR: ' .
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
        if ($this->permitstatus) {
            $filter .= " AND mx_permit.opt_mx_permit_status_id = " . $this->permitstatus;
        }
        if ($this->permittype) {
            $filter .= " AND mx_permit.opt_mx_permit_type_id = " . $this->permittype;
        }

        $query = "SELECT
                       mx_permit.txt_permit_number                                                       AS [Permit Number],
                       mx_application.txt_reference                                                      AS [Reference],
                       CONCAT(mx_applicant.txt_first_name, ' ', mx_applicant.txt_middle_name, ' ',
                              mx_applicant.txt_last_name)                                                    AS [Applicant],
                       (SELECT CONVERT(varchar, mx_permit.dat_start_date, 20) AS [DD MM YYYY HH:II:SS])  AS [Start Date],
                       (SELECT CONVERT(varchar, mx_permit.dat_expiry_date, 20) AS [DD MM YYYY HH:II:SS]) AS [Expiry Date],
                       (SELECT CONVERT(varchar, mx_permit.dat_issued_date, 20) AS [DD MM YYYY HH:II:SS]) AS [Issued Date],
                       mx_permit_type.txt_name                                                           AS [Permit Type],
                       mx_permit_status.txt_name                                                         AS [Status]
                FROM mx_permit
                         JOIN mx_permit_type ON mx_permit_type.id = mx_permit.opt_mx_permit_type_id
                         JOIN mx_permit_status ON mx_permit_status.id = mx_permit.opt_mx_permit_status_id
                         JOIN mx_application ON mx_application.id = mx_permit.opt_mx_application_id
                         JOIN mx_applicant ON mx_applicant.id = mx_application.entity_id AND mx_application.opt_mx_entity_type_id = 2
                         JOIN mx_applicant_institution ON mx_applicant.id = mx_applicant_institution.opt_mx_applicant_id
                         JOIN mx_institution ON mx_institution.id = mx_applicant_institution.opt_mx_institution_id
                WHERE mx_permit.dat_issued_date >= CAST(:from_date AS DATETIME)AND mx_permit.dat_issued_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))" . $filter;

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
        if ($this->permitstatus) {
            $filter .= " AND mx_permit.opt_mx_permit_status_id = " . $this->permitstatus;
        }
        if ($this->permittype) {
            $filter .= " AND mx_permit.opt_mx_permit_type_id = " . $this->permittype;
        }

        $query = "SELECT
                    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 1 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Normal],
                    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 2 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Temporary],
                    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 3 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Exemption],
                    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 4 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Foreigner Marriage],
                    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 5 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Student],
                    COUNT(DISTINCT CASE WHEN mx_permit.opt_mx_permit_type_id = 7 THEN mx_permit.txt_permit_number ELSE NULL END) AS [Diaspora],
                    COUNT(DISTINCT mx_permit.txt_permit_number) AS [Total Permits]
                FROM mx_permit
                         JOIN mx_permit_type ON mx_permit_type.id = mx_permit.opt_mx_permit_type_id
                         JOIN mx_application ON mx_application.id = mx_permit.opt_mx_application_id
                         JOIN mx_applicant ON mx_applicant.id = mx_application.entity_id AND mx_application.opt_mx_entity_type_id = 2
                         JOIN mx_applicant_institution ON mx_applicant.id = mx_applicant_institution.opt_mx_applicant_id
                         JOIN mx_institution ON mx_institution.id = mx_applicant_institution.opt_mx_institution_id
                WHERE mx_permit.dat_issued_date >=:from_date AND mx_permit.dat_issued_date <= :to_date" . $filter;
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
        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C' ]);
        $report->setWidths([ 10, 30, 30, 20, 20, 20, 20, 20, 20 ]);

        $headers = [ 'S/N', 'Permit Number', 'Reference', 'Applicant', 'Start Date', 'Expiry Date', 'Issued Date', 'Permit Type', 'Status' ];

        $report->writeTableHeader($headers);

        $sn = 1;

        foreach ($arrayData as $row) {
            $report->writeTableRow([
                $sn,
                $row['Permit Number'],
                $row['Reference'],
                $row['Applicant'],
                $row['Start Date'],
                $row['Expiry Date'],
                $row['Issued Date'],
                $row['Permit Type'],
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

        $report->setAligns([ 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $report->setWidths([ 20, 20, 20, 25, 35, 20, 25, 25]);

        $tableHeader = [
            'S/N', 'Normal', 'Temporary', 'Exemption', 'Foreigner Marriage', 'Student', 'Diaspora', 'Total Permits'
        ];

        $report->writeTableHeader($tableHeader);

        $sn = 1;
        $normal = 0;
        $temporary = 0;
        $exemption = 0;
        $foreignerMarriage = 0;
        $student = 0;
        $diaspora = 0;
        $permit_type = 0;

        foreach ($arrayData as $row) {
            $normal += $row['Normal'];
            $temporary += $row['Temporary'];
            $exemption += $row['Exemption'];
            $foreignerMarriage += $row['Foreigner Marriage'];
            $student += $row['Student'];
            $diaspora += $row['Diaspora'];
            $permit_type += $row['Total Permits'];

            $rowData = [
                $sn,
                number_format((int) $row['Normal'], 2),
                number_format((int) $row['Temporary'], 2),
                number_format((int) $row['Exemption'], 2),
                number_format((int) $row['Foreigner Marriage'], 2),
                number_format((int) $row['Student'], 2),
                number_format((int) $row['Diaspora'], 2),
                number_format((int) $row['Total Permits'], 2)
            ];


            $report->writeTableRow($rowData);
            $sn++;
        }

        $footerRow = [
            'TOTAL',
            number_format($normal, 2),
            number_format($temporary, 2),
            number_format($exemption, 2),
            number_format($foreignerMarriage, 2),
            number_format($student, 2),
            number_format($diaspora, 2),
            number_format($permit_type, 2)
        ];

        $report->writeTableRow($footerRow);
        $report->closeTable();
    }
}
