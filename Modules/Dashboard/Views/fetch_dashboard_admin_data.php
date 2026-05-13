<?php
use Library\\Database;

date_default_timezone_set('Africa/Dar_es_Salaam');

/** ---- Config / constants (adjust if your enums differ) ---- */
function cfg(): array {
	return [
		'JOBSEEKER_TYPE_ID' => 1, // 👈 change if jobseekers ≠ 1
		'ENTITY_JOBSEEKER'  => 1, // 👈 mx_application.opt_mx_entity_type_id for jobseeker
	];
}

/** ---- Shared date windows (sargable) ---- */
function win(): array {
	$todayStart = "CAST(GETDATE() AS DATE)";
	$todayEnd   = "DATEADD(DAY, 1, CAST(GETDATE() AS DATE))";
	$monthStart = "DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)";
	$monthEnd   = "DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))";
	$yearStart  = "DATEFROMPARTS(YEAR(GETDATE()), 1, 1)";
	$yearEnd    = "DATEADD(YEAR, 1, DATEFROMPARTS(YEAR(GETDATE()), 1, 1))";
	return compact('todayStart','todayEnd','monthStart','monthEnd','yearStart','yearEnd');
}


/**
 * Centralized date windows (UTC of SQL Server; if you store TZ+3, keep it consistent!)
 */
function dateWindows(): array
{
	$todayStart  = "CAST(GETDATE() AS DATE)";
	$todayEnd    = "DATEADD(DAY, 1, CAST(GETDATE() AS DATE))";
	$monthStart  = "DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)";
	$monthEnd    = "DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))";

	return compact('todayStart','todayEnd','monthStart','monthEnd');
}

/** Lightweight query wrapper with timing + error log */
function runQuery(Database $db, string $sql, array $context = [])
{
	$t0 = microtime(true);
	try {
		$rows = $db->select($sql);
		$ms = (int) ((microtime(true) - $t0) * 1000);
		// ✅ Structured log (adjust to your logger)
		error_log(json_encode([
			'level' => 'info',
			'event' => 'sql.select',
			'elapsed_ms' => $ms,
			'rows' => count($rows),
			'context' => $context
		]));
		return $rows;
	} catch (\Throwable $e) {
		error_log(json_encode([
			'level' => 'error',
			'event' => 'sql.select.failed',
			'error' => $e->getMessage(),
			'context' => $context
		]));
		// return a safe empty set
		return [];
	}
}

function getApplicants(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT COUNT(txt_reference_number) AS totalApplicants
        FROM mx_applicant
        WHERE dat_added_date >= {$w['monthStart']}
          AND dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getAllGeneratedPermitMonthly(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT COUNT(DISTINCT txt_permit_number) AS totalPermits
        FROM mx_permit
        WHERE dat_issued_date >= {$w['monthStart']}
          AND dat_issued_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getAllCompletedInspections(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT COUNT(txt_survey_reference) AS totalSurveys
        FROM mx_survey
        WHERE opt_mx_survey_status_id = 1
          AND dat_added_date >= {$w['monthStart']}
          AND dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getAllPermitType(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT TOP 5
            pt.txt_name AS permit_type,
            SUM(CASE WHEN p.dat_issued_date >= {$w['todayStart']} AND p.dat_issued_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS permits_today,
            SUM(CASE WHEN p.dat_issued_date >= {$w['monthStart']} AND p.dat_issued_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS permits_this_month
        FROM mx_permit p
        INNER JOIN mx_permit_type pt ON p.opt_mx_permit_type_id = pt.id
        GROUP BY pt.txt_name
        ORDER BY permits_this_month DESC
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getAllPaidApplications(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Monthly' AS period,
               COUNT(DISTINCT a.txt_reference) AS monthly_applications
        FROM mx_application a
        WHERE a.opt_mx_application_status_id = 1
          AND a.dat_added_date >= {$w['monthStart']}
          AND a.dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getAllPaidApplicationsToday(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Today' AS period,
               COUNT(DISTINCT a.txt_reference) AS today_applications
        FROM mx_application a
        WHERE a.opt_mx_application_status_id = 1
          AND a.dat_added_date >= {$w['todayStart']}
          AND a.dat_added_date <  {$w['todayEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getAllGeneratedPermitToday(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT COUNT(DISTINCT txt_permit_number) AS totalPermits
        FROM mx_permit
        WHERE dat_issued_date >= {$w['todayStart']}
          AND dat_issued_date <  {$w['todayEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getTotalPaymentSummaryShillings(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Today' AS period,
               SUM(CASE WHEN opt_mx_currency_id = 1 THEN dbl_amount ELSE 0 END) AS total_amount_tzs
        FROM mx_payment
        WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['todayStart']}
          AND dat_added_date <  {$w['todayEnd']}
        UNION ALL
        SELECT 'Monthly' AS period,
               SUM(CASE WHEN opt_mx_currency_id = 1 THEN dbl_amount ELSE 0 END) AS total_amount_tzs
        FROM mx_payment
        WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['monthStart']}
          AND dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getTotalPaymentSummaryUSD(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Today' AS period,
               SUM(CASE WHEN opt_mx_currency_id = 2 THEN dbl_amount ELSE 0 END) AS total_amount_usd
        FROM mx_payment
        WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['todayStart']}
          AND dat_added_date <  {$w['todayEnd']}
        UNION ALL
        SELECT 'Monthly' AS period,
               SUM(CASE WHEN opt_mx_currency_id = 2 THEN dbl_amount ELSE 0 END) AS total_amount_usd
        FROM mx_payment
        WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['monthStart']}
          AND dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getTodayPaymentSummary(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Today' AS period,
               ROUND(COALESCE(SUM(dbl_amount), 0), 2) AS today_amount
        FROM mx_payment
        WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['todayStart']}
          AND dat_added_date <  {$w['todayEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getMonthlyPaymentSummary(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Monthly' AS period,
               ROUND(COALESCE(SUM(dbl_amount), 0), 2) AS monthly_amount
        FROM mx_payment
        WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['monthStart']}
          AND dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getTodayApplicationsSummary(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Today' AS period,
               COUNT(DISTINCT txt_reference) AS today_applications
        FROM mx_application
        WHERE dat_added_date >= {$w['todayStart']}
          AND dat_added_date <  {$w['todayEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getMonthlyApplicationsSummary(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT 'Monthly' AS period,
               COUNT(DISTINCT txt_reference) AS monthly_applications
        FROM mx_application
        WHERE dat_added_date >= {$w['monthStart']}
          AND dat_added_date <  {$w['monthEnd']}
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getApprovedInstitution(Database $db)
{
	$sql = "
        SELECT COUNT(txt_institution_number) AS totalInstitutions
        FROM mx_institution
        WHERE opt_mx_institution_status_id = 1
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getSurveySummary(Database $db)
{
	$sql = "
        SELECT s.txt_name  AS status_name,
               COUNT(DISTINCT v.txt_survey_reference) AS total_survey,
               s.txt_color AS survey_color
        FROM mx_survey v
        INNER JOIN mx_survey_status s ON v.opt_mx_survey_status_id = s.id
        GROUP BY s.txt_name, s.txt_color
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getInstitutionSummary(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT st.txt_name  AS institution_status_name,
               COUNT(DISTINCT i.txt_institution_number) AS total_institutions,
               st.txt_color AS institution_color
        FROM mx_institution i
        INNER JOIN mx_institution_status st ON i.opt_mx_institution_status_id = st.id
        WHERE i.dat_added_date >= {$w['monthStart']}
          AND i.dat_added_date <  {$w['monthEnd']}
        GROUP BY st.txt_name, st.txt_color
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getMonthlyPermitSummary(Database $db)
{
	$w = dateWindows();
	$sql = "
        SELECT t.txt_name AS permit_type_name,
               COUNT(DISTINCT p.txt_permit_number) AS total_permits,
               t.txt_color AS permit_color
        FROM mx_permit p
        INNER JOIN mx_permit_type t ON p.opt_mx_permit_type_id = t.id
        WHERE p.dat_issued_date >= {$w['monthStart']}
          AND p.dat_issued_date <  {$w['monthEnd']}
        GROUP BY t.txt_name, t.txt_color
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getIncomesSummaryByMonth(Database $db)
{
	// ✅ No master..spt_values; use a proper 1..12 generator
	$sql = "
    WITH Months AS (
        SELECT 1 AS m
        UNION ALL
        SELECT m+1 FROM Months WHERE m < 12
    ),
    CombinedData AS (
        SELECT p.dbl_amount, p.dat_added_date, p.opt_mx_currency_id AS currency_id
        FROM mx_payment p
        INNER JOIN mx_invoice i ON p.opt_mx_invoice_id = i.id
        WHERE YEAR(p.dat_added_date) = YEAR(GETDATE())
    )
    SELECT
        m.m AS MonthNumber,
        DATENAME(MONTH, DATEFROMPARTS(1900, m.m, 1)) AS MonthName,
        c.txt_name  AS CurrencyName,
        c.txt_color AS CurrencyColor,
        COALESCE(SUM(CASE WHEN MONTH(cd.dat_added_date) = m.m THEN cd.dbl_amount ELSE 0 END), 0) AS TotalPayments
    FROM Months m
    CROSS JOIN mx_currency c
    LEFT JOIN CombinedData cd
           ON cd.currency_id = c.id AND MONTH(cd.dat_added_date) = m.m
    GROUP BY m.m, c.txt_name, c.txt_color
    ORDER BY m.m, CurrencyName
    OPTION (MAXRECURSION 12)
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

function getPermit_status(Database $db)
{
	// This actually aggregates *permit types* across months.
	$sql = "
    WITH Months AS (
        SELECT 1 AS m
        UNION ALL SELECT m+1 FROM Months WHERE m < 12
    ),
    Types AS (
        SELECT id, txt_name, txt_color FROM mx_permit_type
    ),
    Grid AS (
        SELECT m.m AS MonthNumber,
               DATENAME(MONTH, DATEFROMPARTS(1900, m.m, 1)) AS MonthName,
               t.txt_name AS TypeName,
               t.txt_color AS Color
        FROM Months m CROSS JOIN Types t
    ),
    Counts AS (
        SELECT MONTH(p.dat_issued_date) AS MonthNumber,
               t.txt_name AS TypeName,
               t.txt_color AS Color,
               COUNT(DISTINCT p.id) AS ApplicationCount
        FROM mx_permit p
        INNER JOIN mx_permit_type t ON p.opt_mx_permit_type_id = t.id
        WHERE YEAR(p.dat_issued_date) = YEAR(GETDATE())
        GROUP BY MONTH(p.dat_issued_date), t.txt_name, t.txt_color
    )
    SELECT g.MonthNumber, g.MonthName, g.TypeName AS StatusName, g.Color,
           ISNULL(c.ApplicationCount, 0) AS ApplicationCount
    FROM Grid g
    LEFT JOIN Counts c
      ON c.MonthNumber = g.MonthNumber AND c.TypeName = g.TypeName
    ORDER BY g.MonthNumber, g.TypeName
    OPTION (MAXRECURSION 12);
    ";
	return runQuery($db, $sql, ['fn' => __FUNCTION__]);
}

/** ---- Safe runner with timing logs ---- */
function run(Database $db, string $sql, array $ctx=[]){
	$t0 = microtime(true);
	try {
		$rows = $db->select($sql);
		error_log(json_encode([
			'level'=>'info','event'=>'sql.select','elapsed_ms'=>(int)((microtime(true)-$t0)*1000),
			'rows'=>count($rows),'ctx'=>$ctx
		]));
		return $rows;
	} catch (\Throwable $e) {
		error_log(json_encode(['level'=>'error','event'=>'sql.fail','msg'=>$e->getMessage(),'ctx'=>$ctx]));
		return [];
	}
}

/** ======================= METRICS ======================= */

/** Applicants (Jobseekers) created today / month */
function getJobseekersCounts(Database $db){
	$w = win(); $c = cfg();
	$sql = "
        SELECT
          SUM(CASE WHEN js.dat_added_date >= {$w['todayStart']} AND js.dat_added_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS today_new,
          SUM(CASE WHEN js.dat_added_date >= {$w['monthStart']} AND js.dat_added_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS month_new,
          COUNT(*) AS total
        FROM mx_applicant js
        WHERE js.opt_mx_applicant_type_id = {$c['JOBSEEKER_TYPE_ID']};
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Vacancies posted today / month (all statuses) */
function getVacancyCounts(Database $db){
	$w = win();
	$sql = "
        SELECT
          SUM(CASE WHEN v.dat_added_date >= {$w['todayStart']} AND v.dat_added_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS posted_today,
          SUM(CASE WHEN v.dat_added_date >= {$w['monthStart']} AND v.dat_added_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS posted_month,
          COUNT(*) AS total
        FROM mx_vacancy v;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}
function getVacancyStatusMonthly(Database $db){
	$c = cfg();
	$sql = "
        WITH Months AS (SELECT 1 AS m UNION ALL SELECT m+1 FROM Months WHERE m < 12),
		     Agg AS (
		         SELECT MONTH(v.dat_added_date) AS m, COUNT(*) AS total_new
		         FROM mx_vacancy v
		         WHERE v.opt_mx_entity_type_id = 3
		           AND YEAR(v.dat_added_date) = YEAR(GETDATE())
		         GROUP BY MONTH(v.dat_added_date)
		     )
		SELECT Months.m AS month_number,
		       DATENAME(MONTH, DATEFROMPARTS(YEAR(GETDATE()), Months.m, 1)) AS month_name,
		       COALESCE(Agg.total_new, 0) AS total_new
		FROM Months LEFT JOIN Agg ON Agg.m = Months.m
		ORDER BY Months.m
		OPTION (MAXRECURSION 12);
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Applications via applicant→vacancy mapping (more accurate than mx_application) */
function getApplicationsCounts(Database $db){
	$w = win(); $c = cfg();
	$sql = "
        SELECT
          SUM(CASE WHEN av.dat_added_date >= {$w['todayStart']} AND av.dat_added_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS apps_today,
          SUM(CASE WHEN av.dat_added_date >= {$w['monthStart']} AND av.dat_added_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS apps_month,
          COUNT(*) AS apps_total
        FROM mx_applicant_vacancy av
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Agencies today / month + licensed */
function getAgencyCounts(Database $db){
	$w = win();
	$sql = "
        SELECT
          SUM(CASE WHEN a.dat_added_date >= {$w['todayStart']} AND a.dat_added_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS agencies_today,
          SUM(CASE WHEN a.dat_added_date >= {$w['monthStart']} AND a.dat_added_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS agencies_month,
          SUM(CASE WHEN a.int_has_licence = 1 THEN 1 ELSE 0 END) AS agencies_licensed,
          COUNT(*) AS agencies_total
        FROM mx_agency a;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Licences issued today / month + monthly status breakdown */
function getLicencesCounts(Database $db){
	$w = win();
	$sql = "
        SELECT
          SUM(CASE WHEN l.dat_added_date >= {$w['todayStart']} AND l.dat_added_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS licences_today,
          SUM(CASE WHEN l.dat_added_date >= {$w['monthStart']} AND l.dat_added_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS licences_month
        FROM mx_agency_licence l;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}
function getLicencesStatusMonthly(Database $db){
	$w = win();
	$sql = "
        SELECT s.txt_name AS status_name, s.txt_color AS color, COUNT(*) AS total
        FROM mx_agency_licence l
        JOIN mx_agency_licence_status s ON s.id = l.opt_mx_agency_licence_status_id
        WHERE l.dat_added_date >= {$w['monthStart']} AND l.dat_added_date < {$w['monthEnd']}
        GROUP BY s.txt_name, s.txt_color
        ORDER BY total DESC;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Attestations today / month + monthly status breakdown */
function getAttestationsCounts(Database $db){
	$w = win();
	$sql = "
        SELECT
          SUM(CASE WHEN a.dat_added_date >= {$w['todayStart']} AND a.dat_added_date < {$w['todayEnd']} THEN 1 ELSE 0 END) AS attest_today,
          SUM(CASE WHEN a.dat_added_date >= {$w['monthStart']} AND a.dat_added_date < {$w['monthEnd']} THEN 1 ELSE 0 END) AS attest_month,
          COUNT(*) AS attest_total
        FROM mx_attestation a;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}
function getAttestationStatusMonthly(Database $db){
	$w = win();
	$sql = "
        SELECT s.txt_name AS status_name, s.txt_color AS color, COUNT(*) AS total
        FROM mx_attestation a
        JOIN mx_attestation_status s ON s.id = a.opt_mx_attestation_status_id
        WHERE a.dat_added_date >= {$w['monthStart']} AND a.dat_added_date < {$w['monthEnd']}
        GROUP BY s.txt_name, s.txt_color
        ORDER BY total DESC;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Most-applied sectors (monthly) */
function getMostAppliedSectorsMonthly(Database $db){
	$w = win();
	$sql = "
        SELECT TOP 8
            COALESCE(sec.txt_name,'Unknown') AS sector_name,
            sec.txt_color AS color,
            COUNT(*) AS total
        FROM mx_applicant_vacancy av
        JOIN mx_vacancy v ON v.id = av.opt_mx_vacancy_id
        LEFT JOIN mx_sector sec ON sec.id = v.opt_mx_sector_id
        WHERE av.dat_added_date >= {$w['monthStart']} AND av.dat_added_date < {$w['monthEnd']}
        GROUP BY sec.txt_name, sec.txt_color
        ORDER BY total DESC, sector_name ASC;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Payments (TZS / USD) today & month */
function getPaymentsTZS(Database $db){
	$w = win();
	$sql = "
        SELECT 'Today' AS period, SUM(CASE WHEN opt_mx_currency_id = 1 THEN dbl_amount ELSE 0 END) AS total
        FROM mx_payment WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['todayStart']} AND dat_added_date < {$w['todayEnd']}
        UNION ALL
        SELECT 'Monthly' AS period, SUM(CASE WHEN opt_mx_currency_id = 1 THEN dbl_amount ELSE 0 END) AS total
        FROM mx_payment WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['monthStart']} AND dat_added_date < {$w['monthEnd']};
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}
function getPaymentsUSD(Database $db){
	$w = win();
	$sql = "
        SELECT 'Today' AS period, SUM(CASE WHEN opt_mx_currency_id = 2 THEN dbl_amount ELSE 0 END) AS total
        FROM mx_payment WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['todayStart']} AND dat_added_date < {$w['todayEnd']}
        UNION ALL
        SELECT 'Monthly' AS period, SUM(CASE WHEN opt_mx_currency_id = 2 THEN dbl_amount ELSE 0 END) AS total
        FROM mx_payment WHERE opt_mx_payment_status_id = 1
          AND dat_added_date >= {$w['monthStart']} AND dat_added_date < {$w['monthEnd']};
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Top skills (by applicant records) */
function getTopSkills(Database $db){
	$c = cfg();
	$sql = "
        SELECT TOP 8 s.txt_name AS skill_name, COUNT(*) AS total
        FROM mx_skill s
        JOIN mx_applicant a ON a.id = s.opt_mx_applicant_id
        WHERE a.opt_mx_applicant_type_id = {$c['JOBSEEKER_TYPE_ID']}
        GROUP BY s.txt_name
        ORDER BY total DESC, skill_name ASC;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Interviews scheduled today / month + monthly status breakdown */
function getInterviewCounts(Database $db){
	$w = win();
	$sql = "
        SELECT
          SUM(CASE WHEN s.[dat_interview_date ] >= {$w['todayStart']} AND s.[dat_interview_date ] < {$w['todayEnd']} THEN 1 ELSE 0 END) AS interviews_today,
          SUM(CASE WHEN s.[dat_interview_date ] >= {$w['monthStart']} AND s.[dat_interview_date ] < {$w['monthEnd']} THEN 1 ELSE 0 END) AS interviews_month
        FROM mx_interview_schedule s;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}
function getInterviewStatusMonthly(Database $db){
	$w = win();
	$sql = "
        SELECT st.txt_name AS status_name, st.txt_color AS color, COUNT(*) AS total
        FROM mx_interview_schedule s
        JOIN mx_interview_schedule_status st ON st.id = s.opt_mx_interview_schedule_status_id
        WHERE s.[dat_interview_date ] >= {$w['monthStart']} AND s.[dat_interview_date ] < {$w['monthEnd']}
        GROUP BY st.txt_name, st.txt_color
        ORDER BY total DESC;
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** Yearly new jobseekers by month */
function getYearlyNewJobseekersByMonth(Database $db){
	$c = cfg();
	$sql = "
        WITH Months AS (SELECT 1 AS m UNION ALL SELECT m+1 FROM Months WHERE m < 12),
        Agg AS (
          SELECT MONTH(js.dat_added_date) AS m, COUNT(*) AS total_new
          FROM mx_applicant js
          WHERE js.opt_mx_applicant_type_id = {$c['JOBSEEKER_TYPE_ID']}
            AND YEAR(js.dat_added_date) = YEAR(GETDATE())
          GROUP BY MONTH(js.dat_added_date)
        )
        SELECT Months.m AS month_number,
               DATENAME(MONTH, DATEFROMPARTS(YEAR(GETDATE()), Months.m, 1)) AS month_name,
               COALESCE(Agg.total_new, 0) AS total_new
        FROM Months LEFT JOIN Agg ON Agg.m = Months.m
        ORDER BY Months.m
        OPTION (MAXRECURSION 12);
    ";
	return run($db,$sql,['fn'=>__FUNCTION__]);
}

/** ---------- Assemble payload ---------- */
header('Content-Type: application/json');
$db = new Database();

echo json_encode([

	'getApplicants'                      => getApplicants($db),
	'payment_summary_by_today'           => getTodayPaymentSummary($db),
	'payment_summary_in_usd'             => getTotalPaymentSummaryUSD($db),
	'payment_summary_in_tzs'             => getTotalPaymentSummaryShillings($db),
	'payment_summary_by_monthly'         => getMonthlyPaymentSummary($db),
	'application_summary_by_today'       => getTodayApplicationsSummary($db),
	'application_summary_by_monthly'     => getMonthlyApplicationsSummary($db),
	'getApprovedInstitution'             => getApprovedInstitution($db),
	'survey_summary'                     => getSurveySummary($db),
	'institution_summary'                => getInstitutionSummary($db),
	'permit_summary'                     => getMonthlyPermitSummary($db),
	'income_summary_by_month_yearly'     => getIncomesSummaryByMonth($db),
	'Permit_status'                      => getPermit_status($db),
	'getAllGeneratedPermitToday'         => getAllGeneratedPermitToday($db),
	'getAllGeneratedPermitMonthly'       => getAllGeneratedPermitMonthly($db),
	'getAllCompletedInspections'         => getAllCompletedInspections($db),
	'get_all_paid_applications_today'    => getAllPaidApplicationsToday($db),
	'get_all_paid_applications_monthly'  => getAllPaidApplications($db),
	'getAllPermitType'                   => getAllPermitType($db),


	'jobseekers'             => getJobseekersCounts($db),
	'vacancies'              => getVacancyCounts($db),
	'vacancies_year'         => getVacancyStatusMonthly($db),
	'applications'           => getApplicationsCounts($db),
	'agencies'               => getAgencyCounts($db),
	'licences'               => getLicencesCounts($db),
	'licences_status_month'  => getLicencesStatusMonthly($db),
	'attestations'           => getAttestationsCounts($db),
	'attestation_status_mon' => getAttestationStatusMonthly($db),
	'sectors_applied_month'  => getMostAppliedSectorsMonthly($db),
	'payments_tzs'           => getPaymentsTZS($db),
	'payments_usd'           => getPaymentsUSD($db),
	'top_skills'             => getTopSkills($db),
	'interviews'             => getInterviewCounts($db),
	'interview_status_month' => getInterviewStatusMonthly($db),
	'yearly_intake'          => getYearlyNewJobseekersByMonth($db)
], JSON_NUMERIC_CHECK);
