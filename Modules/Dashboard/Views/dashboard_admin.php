<?php
date_default_timezone_set('Africa/Dar_es_Salaam');
?>
<div ng-controller="AdminDashboardCtrl">
    <div class="row splice px-3" id="button-row">
        <span id="progress" style="visibility:hidden;">
            <div class="lds-ripple">
                <div></div>
                <div></div>
            </div>
        </span>
        
        <?php $user = \Authentication\Auth::user(); ?>
        
        <ul class="nav nav-pills border-0 mb-4 bg-light p-1 rounded-pill d-inline-flex" style="width: auto;">
            <li class="nav-item">
                <a class="nav-link active rounded-pill px-4 fw-bold" data-bs-toggle="tab" href="#work_permit">
                    <i class="fa fa-briefcase me-2"></i>
                    <?= htmlspecialchars(($user['int_role_id'] == 3) ? 'WORK PERMIT' : 'LABOUR COMMISSION', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div id="work_permit" class="tab-pane fade show active">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold text-main mb-0">Dashboard Overview</h3>
                        <p class="text-muted small mb-0">Real-time statistics and application analytics</p>
                    </div>
                    <div class="text-end d-none d-md-block">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                            <i class="fa fa-clock me-1"></i> <?= date('d M, Y') ?>
                        </span>
                    </div>
                </div>
                <div class="zt-grid mb-4">
                    <!-- Total Applications: Today -->
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-primary">
                            <i class="fa fa-file-invoice"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Today Applications</div>
                            <h3 class="zt-card__value">{{ (application_summary_by_today[0].today_applications || 0) | number }}</h3>
                            <div class="d-flex align-items-center mt-1">
                                <small class="text-muted"><?= date('d M Y') ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Total Applications: Monthly -->
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-info">
                            <i class="fa fa-chart-bar"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Monthly Applications</div>
                            <h3 class="zt-card__value">{{ (application_summary_by_monthly[0].monthly_applications || 0) | number }}</h3>
                            <div class="d-flex align-items-center mt-1">
                                <small class="text-muted"><?= date('M Y') ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Paid Applications: Today -->
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-success">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Paid (Today)</div>
                            <h3 class="zt-card__value">{{ (get_all_paid_applications_today[0].today_applications || 0) | number }}</h3>
                            <div class="d-flex align-items-center mt-1">
                                <small class="text-success small fw-bold">Active</small>
                            </div>
                        </div>
                    </div>

                    <!-- Paid Applications: Monthly -->
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-warning">
                            <i class="fa fa-wallet"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Paid (Monthly)</div>
                            <h3 class="zt-card__value">{{ (get_all_paid_applications_monthly[0].monthly_applications || 0) | number }}</h3>
                            <div class="d-flex align-items-center mt-1">
                                <small class="text-muted"><?= date('M Y') ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4 px-3">
                    <div class="col-sm-12 col-md-6">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h5 class="zt-card__title fw-bold text-main mb-0">Permit Applications Statistics</h5>
                                <p class="text-muted small">Annual trend analysis</p>
                            </div>
                            <div class="zt-card__body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="permit_graph"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h5 class="zt-card__title fw-bold text-main mb-0">Status Summary (Monthly)</h5>
                                <p class="text-muted small">Distribution of permit types</p>
                            </div>
                            <div class="zt-card__body">
                                <div class="chart-container mb-4" style="position: relative; height: 180px;">
                                    <canvas id="PermitChartSummary"></canvas>
                                </div>
                                <div class="zt-legend-grid">
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-success"></span>
                                        <span class="zt-legend-label">Normal</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-primary"></span>
                                        <span class="zt-legend-label">Temporary</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-danger"></span>
                                        <span class="zt-legend-label">Exemption</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-dark"></span>
                                        <span class="zt-legend-label">Marriage</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-secondary"></span>
                                        <span class="zt-legend-label">Student</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-info"></span>
                                        <span class="zt-legend-label">Diaspora</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="col-sm-12 col-md-12">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h5 class="zt-card__title fw-bold text-main mb-0">Institutions Summary</h5>
                                <p class="text-muted small">Monthly application volume by status</p>
                            </div>
                            <div class="zt-card__body">
                                <div class="chart-container mb-4" style="position: relative; height: 200px;">
                                    <canvas id="InstitutionChartSummary"></canvas>
                                </div>
                                <div class="zt-legend-grid">
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-success"></span>
                                        <span class="zt-legend-label">Approved</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-primary"></span>
                                        <span class="zt-legend-label">Pending Approval</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-danger"></span>
                                        <span class="zt-legend-label">Rejected</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-warning"></span>
                                        <span class="zt-legend-label">Pending Survey</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-info"></span>
                                        <span class="zt-legend-label">Verification</span>
                                    </div>
                                    <div class="zt-legend-item">
                                        <span class="zt-legend-dot bg-secondary"></span>
                                        <span class="zt-legend-label">In Survey</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>
                    </div>
                </div>

                <div class="zt-grid mb-4">
                    <!-- Revenue Summary: TZS -->
                    <div class="zt-card zt-card--currency zt-bg-success text-white">
                        <div class="zt-card__header border-0 pb-0">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <h6 class="text-white text-opacity-75 text-uppercase small fw-bold mb-0">Revenue (TZS)</h6>
                                <i class="fa fa-money-bill-wave text-white text-opacity-25"></i>
                            </div>
                        </div>
                        <div class="zt-card__body py-4">
                            <div class="row align-items-center">
                                <div class="col-6">
                                    <div class="small text-white text-opacity-50">Today</div>
                                    <h4 class="fw-bold mb-0 text-white">{{ (payment_summary_in_tzs[0].total_amount_tzs || 0) | number }}</h4>
                                </div>
                                <div class="col-6 border-start border-white border-opacity-10">
                                    <div class="small text-white text-opacity-50"><?= date('M') ?> Total</div>
                                    <h4 class="fw-bold mb-0 text-white">{{ (payment_summary_in_tzs[1].total_amount_tzs || 0) | number }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Summary: USD -->
                    <div class="zt-card zt-card--currency zt-bg-info text-white">
                        <div class="zt-card__header border-0 pb-0">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <h6 class="text-white text-opacity-75 text-uppercase small fw-bold mb-0">Revenue (USD)</h6>
                                <i class="fa fa-dollar-sign text-white text-opacity-25"></i>
                            </div>
                        </div>
                        <div class="zt-card__body py-4">
                            <div class="row align-items-center">
                                <div class="col-6">
                                    <div class="small text-white text-opacity-50">Today</div>
                                    <h4 class="fw-bold mb-0 text-white">{{ (payment_summary_in_usd[0].total_amount_usd || 0) | number }}</h4>
                                </div>
                                <div class="col-6 border-start border-white border-opacity-10">
                                    <div class="small text-white text-opacity-50"><?= date('M') ?> Total</div>
                                    <h4 class="fw-bold mb-0 text-white">{{ (payment_summary_in_usd[1].total_amount_usd || 0) | number }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="zt-grid mb-4">
                    <!-- Permits Generated: Today -->
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-primary">
                            <i class="fa fa-stamp"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Permits Generated (Today)</div>
                            <h3 class="zt-card__value">{{ (getAllGeneratedPermitToday[0].totalPermits || 0) | number }}</h3>
                            <small class="text-muted"><?= date('d M Y') ?></small>
                        </div>
                    </div>

                    <!-- Permits Generated: Monthly -->
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-info">
                            <i class="fa fa-print"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Permits Generated (Monthly)</div>
                            <h3 class="zt-card__value">{{ (getAllGeneratedPermitMonthly[0].totalPermits || 0) | number }}</h3>
                            <small class="text-muted"><?= date('M Y') ?></small>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4 px-3">
                    <!-- Top 5 Applied Permits -->
                    <div class="col-sm-12 col-md-6">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent">
                                <h5 class="zt-card__title fw-bold text-main mb-0">Top 5 Applied Permits</h5>
                                <p class="text-muted small">Most popular categories</p>
                            </div>
                            <div class="zt-card__body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 border-0 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem">#</th>
                                                <th class="border-0 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem">Permit Type</th>
                                                <th class="border-0 text-uppercase small fw-bold text-muted" style="font-size: 0.7rem">Today</th>
                                                <th class="pe-4 border-0 text-uppercase small fw-bold text-muted text-end" style="font-size: 0.7rem"><?= date('M') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr ng-repeat="(key,value) in getAllPermitType">
                                                <td class="ps-4 text-muted small">{{$index + 1}}</td>
                                                <td>
                                                    <span class="fw-medium text-main" ng-if="value.permit_type">{{value.permit_type}}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2">{{ (value.permits_today || 0) | number }}</span>
                                                </td>
                                                <td class="pe-4 text-end fw-bold text-main">
                                                    {{ (value.permits_this_month || 0) | number }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Annual Income Trend -->
                    <div class="col-sm-12 col-md-6">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent">
                                <h5 class="zt-card__title fw-bold text-main mb-0">Annual Income Trend</h5>
                                <p class="text-muted small">Revenue for <?= date('Y') ?></p>
                            </div>
                            <div class="zt-card__body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="yearly_income_summary_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div id="job_seeker" class="tab-pane fade">
                <!-- Modern Dashboard Header -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold text-main mb-0">Jobseeker Analytics</h3>
                        <p class="text-muted small mb-0">Employment services and activity insights</p>
                    </div>
                    <div class="text-end" ng-if="loading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Core 4 KPI Cards -->
                <div class="zt-grid mb-4">
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-primary">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">New Jobseekers</div>
                            <h3 class="zt-card__value">{{ (kpi.jobseekers_month||0) | number }}</h3>
                            <small class="text-primary small fw-bold">This Month</small>
                        </div>
                    </div>
                    
                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-success">
                            <i class="fa fa-file-alt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Applications</div>
                            <h3 class="zt-card__value">{{ (kpi.applications_month||0) | number }}</h3>
                            <small class="text-success small fw-bold">This Month</small>
                        </div>
                    </div>

                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-info">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Vacancies</div>
                            <h3 class="zt-card__value">{{ (kpi.vacancies_month||0) | number }}</h3>
                            <small class="text-info small fw-bold">This Month</small>
                        </div>
                    </div>

                    <div class="zt-card zt-card--stat">
                        <div class="zt-card__icon zt-bg-warning">
                            <i class="fa fa-money-bill-wave"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="zt-card__label">Revenue (TZS)</div>
                            <h3 class="zt-card__value">{{ (kpi.tzs_month||0) | number }}</h3>
                            <small class="text-warning small fw-bold">This Month</small>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h6 class="zt-card__label text-primary mb-0"><i class="fa fa-certificate me-2"></i>Licence Summary</h6>
                            </div>
                            <div class="zt-card__body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">This Month</span>
                                    <span class="fw-bold text-main">{{ (kpi.licences_month||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">Valid Licences</span>
                                    <span class="fw-bold text-success">{{ (kpi.licences_valid||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Total Active</span>
                                    <span class="fw-bold text-info">{{ (kpi.licences_total||0) | number }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h6 class="zt-card__label text-warning mb-0"><i class="fa fa-award me-2"></i>Attestation Summary</h6>
                            </div>
                            <div class="zt-card__body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">This Month</span>
                                    <span class="fw-bold text-main">{{ (kpi.attestations_month||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">Valid Attestations</span>
                                    <span class="fw-bold text-success">{{ (kpi.attestations_valid||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Total Issued</span>
                                    <span class="fw-bold text-info">{{ (kpi.attestations_total||0) | number }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h6 class="zt-card__label text-success mb-0"><i class="fa fa-building me-2"></i>Agency Summary</h6>
                            </div>
                            <div class="zt-card__body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">This Month</span>
                                    <span class="fw-bold text-main">{{ (kpi.agencies_month||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">Active Agencies</span>
                                    <span class="fw-bold text-success">{{ (kpi.agencies_active||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Total Registered</span>
                                    <span class="fw-bold text-info">{{ (kpi.agencies_total||0) | number }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section - Modern Design -->
                <div class="row" style="margin-bottom:25px;">
                    <div class="col-sm-12 col-md-4">
                        <div style="background:white;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.08);overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#f8f9fc,#e9ecef);padding:20px;border-bottom:1px solid rgba(0,0,0,0.05);">
                                <h6 style="margin:0;font-weight:600;color:#2c3e50;display:flex;align-items:center;">
                                    <i class="fa fa-chart-pie" style="color:#667eea;margin-right:10px;"></i>
                                    Applications by Status
                                </h6>
                            </div>
                            <div style="padding:25px;height:350px;">
                                <canvas id="ch_app_status"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <div style="background:white;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.08);overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#f8f9fc,#e9ecef);padding:20px;border-bottom:1px solid rgba(0,0,0,0.05);">
                                <h6 style="margin:0;font-weight:600;color:#2c3e50;display:flex;align-items:center;">
                                    <i class="fa fa-certificate" style="color:#667eea;margin-right:10px;"></i>
                                    Licences by Status
                                </h6>
                            </div>
                            <div style="padding:25px;height:350px;">
                                <canvas id="ch_lic_status"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <div style="background:white;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.08);overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#f8f9fc,#e9ecef);padding:20px;border-bottom:1px solid rgba(0,0,0,0.05);">
                                <h6 style="margin:0;font-weight:600;color:#2c3e50;display:flex;align-items:center;">
                                    <i class="fa fa-industry" style="color:#667eea;margin-right:10px;"></i>
                                    Most Applied Sectors
                                </h6>
                            </div>
                            <div style="padding:25px;height:350px;">
                                <canvas id="ch_sectors"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trend Charts Row -->
                <div class="row" style="margin-bottom:25px;">
                    <div class="col-sm-12 col-md-6">
                        <div style="background:white;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.08);overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#f8f9fc,#e9ecef);padding:20px;border-bottom:1px solid rgba(0,0,0,0.05);">
                                <h6 style="margin:0;font-weight:600;color:#2c3e50;display:flex;align-items:center;">
                                    <i class="fa fa-chart-line" style="color:#667eea;margin-right:10px;"></i>
                                    Yearly Jobseekers Trend
                                </h6>
                            </div>
                            <div style="padding:25px;height:350px;">
                                <canvas id="ch_yearly_intake"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div style="background:white;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.08);overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#f8f9fc,#e9ecef);padding:20px;border-bottom:1px solid rgba(0,0,0,0.05);">
                                <h6 style="margin:0;font-weight:600;color:#2c3e50;display:flex;align-items:center;">
                                    <i class="fa fa-chart-bar" style="color:#667eea;margin-right:10px;"></i>
                                    Vacancies by Month
                                </h6>
                            </div>
                            <div style="padding:25px;height:350px;">
                                <canvas id="ch_vacancies_year"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Chart and Interview Summary -->
                <div class="row g-4 mb-4">
                    <div class="col-sm-12 col-md-8">
                        <div class="zt-card h-100">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h6 class="zt-card__label text-primary mb-0"><i class="fa fa-tools me-2"></i>Top Skills in Demand</h6>
                            </div>
                            <div class="zt-card__body">
                                <div class="chart-container" style="position: relative; height: 350px;">
                                    <canvas id="js_top_skills_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <div class="zt-card h-100 zt-bg-primary text-white border-0 shadow-lg">
                            <div class="zt-card__header border-0 bg-transparent pb-0">
                                <h6 class="text-white text-opacity-75 text-uppercase small fw-bold mb-0"><i class="fa fa-calendar-check me-2"></i>Interview Summary</h6>
                            </div>
                            <div class="zt-card__body">
                                <div class="d-flex justify-content-between mb-3 align-items-center">
                                    <span class="text-white text-opacity-75 small">This Month</span>
                                    <span class="h4 fw-bold mb-0">{{ (kpi.interviews_month||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 align-items-center">
                                    <span class="text-white text-opacity-75 small">Upcoming</span>
                                    <span class="h4 fw-bold mb-0 text-warning">{{ (kpi.interviews_upcoming||0) | number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-4 align-items-center">
                                    <span class="text-white text-opacity-75 small">Completed</span>
                                    <span class="h4 fw-bold mb-0 text-success">{{ (kpi.interviews_completed||0) | number }}</span>
                                </div>
                                
                                <div class="bg-white bg-opacity-10 rounded-4 p-3 mt-4">
                                    <div class="text-white text-opacity-50 small mb-1">Next Scheduled Interview</div>
                                    <div class="fw-bold h5 mb-0" ng-if="upcoming_interviews.length > 0">
                                        <i class="fa fa-clock me-2 opacity-50"></i>
                                        {{ upcoming_interviews[0].interview_date | date:'MMM dd, yyyy' }}
                                    </div>
                                    <div class="text-white text-opacity-50 fw-medium" ng-if="!upcoming_interviews.length">
                                        No upcoming interviews
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
