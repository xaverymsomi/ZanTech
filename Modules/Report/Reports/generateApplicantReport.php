<?php

namespace Modules\Report\Reports;

use Database\Database;
use Loggers\Log;
use Modules\Report\Libs\ReportGenerator;

class generateApplicantReport
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
    protected int $nationality;

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
        $this->nationality = $this->data['nationality'];

        $this->generateReport();
    }

    private function generateReport(): void
    {
        // Entry log
        Log::sysLog('[generateApplicantReport] generateReport() ENTER');

        try {
            // 1) FETCH RAW DATA
            $arrayData = $this->category ? $this->getDetailedReport() : $this->getSummaryReport();
            Log::sysLog('[generateApplicantReport] DB rows: ' . count($arrayData));

            // No records
            if (empty($arrayData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 100, 'records' => []]);
                Log::sysLog('[generateApplicantReport] No records, returning 100');
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
            Log::sysLog('[generateApplicantReport] PDF path: ' . $savePath);

            // 3) Generate the PDF (isolated try–catch so if mPDF fails we still return JSON)
            try {
                Log::sysLog('[generateApplicantReport] Instantiating ReportGenerator');
                $report = new ReportGenerator();

                Log::sysLog('[generateApplicantReport] Setting header meta');
                $report->setReportHeading($this->title);
                $report->setFromDate($this->from_date);
                $report->setToDate($this->to_date);
                $report->setHeader(1);

                // Add page before writing content
                $report->mpdf->AddPage('L');

                Log::sysLog('[generateApplicantReport] Rendering table, category=' . $this->category);

                if ($this->category == 1) {
                    $this->renderDetailedReport($report, $arrayData);
                } else {
                    $this->renderSummaryReport($report, $arrayData);
                }

                if (method_exists($report, 'closeTable')) {
                    $report->closeTable();
                }

                $report->mpdf->WriteHTML("<div></div>");

                Log::sysLog('[generateApplicantReport] Calling outputToFile()');
                $report->outputToFile($savePath);
                Log::sysLog('[generateApplicantReport] PDF written OK');
            } catch (\Throwable $e) {
                // Log & return JSON error but DO NOT let it silently kill the request
                Log::sysLog(
                    '[generateApplicantReport] PDF ERROR: ' .
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
            Log::sysLog('[generateApplicantReport] Returning JSON 200 with pdf_name=' . $pdfName);
            return;

        } catch (\Throwable $e) {
            // Any *other* fatal error will still come back as JSON so Angular can show the real message
            Log::sysLog(
                '[generateApplicantReport] FATAL ERROR: ' .
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
            $filter .= ' AND mx_applicant.opt_mx_gender_id = ' . $this->filter_value;
        }
        if ($this->nationality) {
            $filter .= " AND mx_applicant.opt_mx_nationality_id = " . $this->nationality;
        }

        $query = "SELECT
			       mx_honorific.txt_name                                                               AS [Honorific],
			       CONCAT(mx_applicant.txt_first_name, ' ', mx_applicant.txt_middle_name, ' ', mx_applicant.txt_last_name) AS [Applicant],
			       ISNULL(mx_applicant.txt_reference_number, 'N/A')                                     AS [E-Number],
			       mx_applicant_log.txt_identification_number                                              AS [ID],
			       mx_applicant_log.txt_place_issued                                                       AS [Place],
			       mx_nationality.txt_nationality                                                      AS [Nationality],
			       (SELECT CONVERT(varchar, mx_applicant.dat_birth_date, 20) AS [DD MM YYYY])          AS [Birth Date],
			       mx_applicant_log.txt_home_address                                                       AS [Home Address],
			       (SELECT CONVERT(varchar, mx_applicant.dat_added_date, 20) AS [DD MM YYYY HH:II:SS]) AS [Added Date]
				FROM mx_applicant
			         JOIN mx_honorific ON mx_applicant.opt_mx_honorific_id = mx_honorific.id
			         JOIN mx_nationality ON mx_applicant.opt_mx_nationality_id = mx_nationality.id
			         JOIN mx_applicant_log ON mx_applicant_log.opt_mx_applicant_id = mx_applicant.id
				WHERE mx_applicant.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_applicant.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME))" . $filter;

        return $this->db->select($query, [':from_date' => $this->from_date, ':to_date' => $this->to_date]);
    }

    /**
     * Summary report query
     */
    private function getSummaryReport(): array
    {
        $filter = '';
        if ($this->filter_criteria && $this->filter_value) {
            $filter .= ' AND mx_applicant.opt_mx_gender_id = ' . $this->filter_value;
        }
        if ($this->nationality) {
            $filter .= " AND mx_applicant.opt_mx_nationality_id = " . $this->nationality;
        }

        $query = "SELECT
                    mx_applicant.dat_added_date AS [DATE],
				    COUNT(DISTINCT CASE WHEN mx_gender.id = 1 THEN mx_applicant.txt_reference_number ELSE NULL END) AS [MALE],
				    COUNT(DISTINCT CASE WHEN mx_gender.id = 2 THEN mx_applicant.txt_reference_number ELSE NULL END) AS [FEMALE],
				    COUNT(DISTINCT CASE WHEN mx_applicant_log.opt_mx_identification_type_id = 1 THEN mx_applicant.txt_reference_number ELSE NULL END) AS [PASSPORT],
				    COUNT(DISTINCT mx_applicant.txt_reference_number) AS [TOTAL APPLICANTS]
				FROM mx_applicant
					JOIN mx_gender ON mx_applicant.opt_mx_gender_id = mx_gender.id
				JOIN mx_applicant_log ON mx_applicant_log.opt_mx_applicant_id = mx_applicant.id
				WHERE mx_applicant.dat_added_date >= CAST(:from_date AS DATETIME)AND mx_applicant.dat_added_date < DATEADD(DAY, 1, CAST(:to_date AS DATETIME)) " . $filter . " GROUP BY mx_applicant.dat_added_date";

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
        $report->setWidths([ 10, 20, 25, 20, 20, 20, 20, 20, 15, 20 ]);

        $headers = [ 'S/N', 'Honorific', 'Applicant', 'E-Number', 'ID', 'Place', 'Nationality', 'Birth Date', 'Home Address', 'Added Date' ];

        $report->writeTableHeader($headers);

        $sn = 1;

        foreach ($arrayData as $row) {
            $report->writeTableRow([
                $sn,
                $row['Honorific'],
                $row['Applicant'],
                $row['E-Number'],
                $row['ID'],
                $row['Place'],
                $row['Nationality'],
                $row['Birth Date'],
                $row['Home Address'],
                $row['Added Date']
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

        $report->setAligns([ 'C', 'C','C', 'C', 'C', 'C', 'C' ]);
        $report->setWidths([ 20, 35, 36, 30, 30, 41 ]);

        $tableHeader = [
            'S/N', 'DATE','MALE', 'FEMALE', 'PASSPORT', 'TOTAL APPLICANTS'
        ];

        $report->writeTableHeader($tableHeader);

        $sn = 1;
        $male = 0;
        $female = 0;
        $passport = 0;
        $total = 0;

        foreach ($arrayData as $row) {
            $male += $row['MALE'];
            $female += $row['FEMALE'];
            $passport += $row['PASSPORT'];
            $total += $row['TOTAL APPLICANTS'];

            $rowData = [
                $sn,
                $row['DATE'],
                number_format((int) $row['MALE'], 2),
                number_format((int) $row['FEMALE'], 2),
                number_format((int) $row['PASSPORT'], 2),
                number_format((int) $row['TOTAL APPLICANTS'], 2),
            ];


            $report->writeTableRow($rowData);
            $sn++;
        }

        $footerRow = [
            '',
            'TOTAL',
            number_format($male, 2),
            number_format($female, 2),
            number_format($passport, 2),
            number_format($total, 2)
        ];

        $report->writeTableRow($footerRow);
        $report->closeTable();
    }
}
