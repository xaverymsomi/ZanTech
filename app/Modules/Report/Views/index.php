<div id="page-content" class="px-4 py-4">
    <?php
    use Authentication\Session;
    $report_types = $this->report_types;
    $login_type = Session::get('logintype') ?? '';
    ?>
    <div ng-controller="reportCtrl">
        <div class="row g-4">
            <div class="col-md-12">
                <div class="notification-area"></div>
                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold text-main mb-0"><?php echo trans('report_title'); ?></h3>
                        <p class="text-muted small mb-0"><?php echo trans('report_sub_title'); ?></p>
                    </div>
                    <div class="d-none d-md-block text-end">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                            <i class="fa fa-chart-line me-1"></i> Reporting Module
                        </span>
                    </div>
                </div>

                <div class="zt-card mb-4">
                    <div class="zt-card__header border-0 bg-transparent py-4">
                        <ul class="nav nav-pills zt-pills-modern px-4" role="tablist"
                            ng-init="getFormFields('General_Report', 1)">
                            <?php
                            foreach ($report_types as $type) {
                                $is_active = '';
                                if ($login_type == 'stakeholder') {
                                    if ($type['report_type'] == 'Transaction_Report') {
                                        $is_active = 'active';
                                    }
                                } else {
                                    if ($type['report_type'] == 'General_Report') {
                                        $is_active = 'active';
                                    }
                                }
                                ?>
                                <li class="nav-item me-2">
                                    <a class="nav-link <?php echo $is_active; ?> px-4 py-2 fw-bold" href="#<?php echo $type['report_type']; ?>" 
                                       ng-click="getFormFields('<?php echo $type['report_type']; ?>', <?php echo $type['report_id'] ?>)" 
                                       data-bs-toggle="tab">
                                        <?php echo $type['report_title']; ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>

                    <div class="zt-card__body pt-0">
                        <div id="myTabContent" class="tab-content mb-4">
                            <?php
                            foreach ($report_types as $type) {
                                $is_active = '';
                                if ($login_type == 'stakeholder') {
                                    if ($type['report_type'] == 'Transaction_Report') {
                                        $is_active = 'show active';
                                    }
                                } else {
                                    if ($type['report_type'] == 'General_Report') {
                                        $is_active = 'show active';
                                    }
                                }
                                ?>
                                <div class="tab-pane fade <?php echo $is_active; ?>" id="<?php echo $type['report_type']; ?>">
                                    <?php include $type['report_header']; ?>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <form id="ReportForm">
                            <!-- Filter Operations -->
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white mb-2">
                                <h6 class="zt-card__label text-primary mb-4 fw-bold"><i class="fa fa-sliders-h me-2"></i>Report Parameters</h6>
                                <div class="row g-4 align-items-end">
                                    <div ng-if="ReportOptions.Type === 5 || ReportOptions.Type === 6" class="col-md-3">
                                        <div class="zt-form-group mb-0">
                                            <label class="form-label-premium">Date</label>
                                            <input type="date" class="form-control-premium" id="FromDate" name="FromDate"
                                                   ng-class="{'is-invalid': ReportForm.FromDate.$invalid && !ReportForm.FromDate.$pristine}"
                                                   ng-model="ReportOptions.StartDate" required>
                                            <i class="fa fa-calendar form-icon-premium"></i>
                                        </div>
                                    </div>
                                    
                                    <div ng-if="ReportOptions.Type !== 5 && ReportOptions.Type !== 6" class="col-md-3">
                                        <div class="zt-form-group mb-0">
                                            <label class="form-label-premium"><?php echo trans('date_from'); ?></label>
                                            <input type="date" class="form-control-premium" id="FromDate" name="FromDate"
                                                   ng-class="{'is-invalid': ReportForm.FromDate.$invalid && !ReportForm.FromDate.$pristine}"
                                                   ng-model="ReportOptions.StartDate" required>
                                            <i class="fa fa-calendar-alt form-icon-premium"></i>
                                        </div>
                                    </div>
                                    
                                    <div ng-if="ReportOptions.Type !== 5 && ReportOptions.Type !== 6" class="col-md-3">
                                        <div class="zt-form-group mb-0">
                                            <label class="form-label-premium"><?php echo trans('date_to'); ?></label>
                                            <input type="date" class="form-control-premium" id="ToDate" name="ToDate"
                                                   ng-class="{'is-invalid': ReportForm.ToDate.$invalid && !ReportForm.ToDate.$pristine}"
                                                   ng-model="ReportOptions.EndDate" required>
                                            <i class="fa fa-calendar-check form-icon-premium"></i>
                                        </div>
                                    </div>

                                    <div class="col-md-3" ng-if="ReportCategories.length > 0">
                                        <div class="zt-form-group mb-0">
                                            <label class="form-label-premium">View Mode</label>
                                            <select name="ReportCategory" id="ReportCategory" class="form-control-premium py-2" ng-model="ReportOptions.Category"
                                                    ng-options="field.Id as field.Name for (key, field) in ReportCategories"
                                                    ng-class="{'is-invalid': ReportForm.ReportCategory.$invalid && !ReportForm.ReportCategory.$pristine}" required>
                                            </select>
                                            <i class="fa fa-layer-group form-icon-premium"></i>
                                        </div>
                                    </div>

                                    <div class="col-md-3" ng-if="ReportFilters.length > 0">
                                        <div class="zt-form-group mb-0">
                                            <label class="form-label-premium">Filter By</label>
                                            <select name="ReportFilter" id="ReportFilter" class="form-control-premium py-2" ng-model="ReportOptions.FilterField"
                                                    ng-options="field.Id as field.Name for (key, field) in ReportFilters"
                                                    ng-change="getFilteringValues()"
                                                    ng-class="{'is-invalid': ReportForm.ReportFilter.$invalid && !ReportForm.ReportFilter.$pristine}" required>
                                            </select>
                                            <i class="fa fa-filter form-icon-premium"></i>
                                        </div>
                                    </div>

                                    <div class="col-md-3" ng-if="ReportFilterValues.length > 0 && (ReportOptions.FilterField !== 6)">
                                        <div class="zt-form-group mb-0">
                                            <label class="form-label-premium">Value</label>
                                            <select name="FilterFieldValue" id="FilterFieldValue" class="form-control-premium py-2" ng-model="ReportOptions.FilterFieldValue"
                                                    ng-options="field as field.Name for field in ReportFilterValues"
                                                    ng-change="getAuditActions()"
                                                    ng-class="{'is-invalid': ReportForm.FilterFieldValue.$invalid && !ReportForm.FilterFieldValue.$pristine}" required>
                                            </select>
                                            <i class="fa fa-tags form-icon-premium"></i>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-4 d-flex justify-content-between align-items-center border-top pt-4">
                                        <div ng-if="ReportOptions.Category == 0 && ReportOptions.GroupingField == 3" class="alert alert-danger py-2 px-3 mb-0 rounded-pill small">
                                            <i class="fa fa-info-circle me-2"></i>Summary reports cannot be grouped by status.
                                        </div>
                                        <div class="ms-auto d-flex gap-2">
                                            <button type="button" ng-if="ReportIsOpen" class="btn btn-soft-danger rounded-pill px-4 fw-bold" ng-click="closeReportViewer();">
                                                <i class="fa fa-times me-2"></i>Close Viewer
                                            </button>
                                            <button type="button" 
                                                    ng-disabled="(ReportOptions.FilterFieldValue.Id == 0 && ReportOptions.Type === 5) || (ReportOptions.Category == 0 && ReportOptions.FilterField == 1 && [5].indexOf(ReportOptions.Type) > 0) || ReportForm.$invalid || (ReportOptions.Category == 0 && ReportOptions.GroupingField == 3)" 
                                                    class="btn btn-premium px-5 shadow-sm" 
                                                    ng-click="generateReport();">
                                                <i class="fa fa-sync-alt me-2"></i>Generate Report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!--Report Preview Panel-->
                <div class="zt-card d-none" id="preview_panel" ng-class="{'d-block': ReportIsOpen, 'd-none': !ReportIsOpen}">
                    <div class="zt-card__header border-0 bg-transparent">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h5 class="zt-card__title fw-bold text-main mb-0">Report Preview</h5>
                                <p class="text-muted small mb-0"><i class="fa fa-info-circle me-1"></i>Optimized for up to 10,000 records</p>
                            </div>
                            <div class="btn-group">
                                <button ng-if=" ReportOptions.Type === 5" class="btn btn-soft-success btn-sm rounded-start-pill px-3" title="Export to Excel"
                                        data-bs-toggle="collapse" data-bs-target="#TransferLog">
                                    <i class="fa fa-file-excel me-1"></i> Export Log
                                </button>

                                <button ng-if="ReportOptions.Type === 1" class="btn btn-soft-success btn-sm px-3" onclick="ExportTableToExcel('Report')" title="Export to Excel">
                                    <i class="fa fa-file-excel me-1"></i> Excel
                                </button>
                                <button ng-if="ReportOptions.Type === 1" class="btn btn-soft-danger btn-sm ms-1 px-3" onclick="ExportTableToPDF()" title="PDF Version">
                                    <i class="fa fa-file-pdf me-1"></i> PDF
                                </button>
                                <button class="btn btn-soft-success btn-sm px-3 rounded-pill" ng-if=" [1].indexOf(ReportOptions.Type) <= -1 " ng-click="ExportToExcel(ReportOptions.ReportTitle, records);" title="Export to Excel">
                                    <i class="fa fa-file-excel me-1"></i> Excel Export
                                </button>
                                <button ng-if="[1].indexOf(ReportOptions.Type) <= -1 " class="btn btn-soft-danger btn-sm ms-1 px-3 rounded-pill" onclick="ExportTableToPDF()" title="PDF Version">
                                    <i class="fa fa-file-pdf me-1"></i> PDF Export
                                </button>
                            </div>
                        </div>

                        <div class="collapse mt-3" id="TransferLog">
                            <div class="zt-card zt-bg-warning bg-opacity-10 border-warning border-opacity-25 p-4 text-center">
                                <h5 class="fw-bold text-warning mb-2">Confirm Data Transfer</h5>
                                <p class="text-muted mb-4">This action will transfer test samples and cannot be undone. Are you sure?</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" ng-click="ExportToExcel('labsample', records); submitToLab(records)" data-bs-toggle="collapse" data-bs-target="#TransferLog">Confirm Transfer</button>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-toggle="collapse" data-bs-target="#TransferLog">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="zt-card__body p-0">
                        <div class="table-responsive" id="reportHtmlSection">
                             <!-- HTML table content will be injected here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Over Limit Modal -->
        <div class="modal fade" id="hasMany" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                    <div class="modal-header bg-danger text-white border-0 py-3">
                        <h5 class="modal-title fw-bold"><i class="fa fa-exclamation-circle me-2"></i>Data Limit Notice</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-5">
                        <div class="mb-4">
                            <span class="display-4 fw-bold text-danger">{{ totalRecords | number}}</span>
                            <div class="text-muted text-uppercase small fw-bold mt-1">Total Records Found</div>
                        </div>
                        <p class="text-muted mb-4">The web previewer is optimized for up to 10,000 records. For larger datasets, please download the full report using the options below.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg rounded-pill fw-bold" ng-click="ExportToExcel(ReportOptions.ReportTitle, records);">
                                <i class="fa fa-file-excel me-2"></i>Download Full Excel
                            </button>
                            <button ng-if="ReportOptions.Type !== 5 && ReportOptions.Type !== 8" class="btn btn-outline-danger btn-lg rounded-pill fw-bold" onclick="ExportTableToPDF()">
                                <i class="fa fa-file-pdf me-2"></i>Download Full PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF Modal -->
        <div id="DemoModal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-main"><i class="fa fa-file-pdf me-2 text-danger"></i>Report Document Viewer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0" id="reportPDFPreview" style="min-height: 650px; background: #f8f9fa;">
                        <!-- PDF Viewer content -->
                    </div>
                </div>
            </div>
        </div>

        <!-- No Record Modal -->
        <div class="modal fade" id="noRecordFound" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                     <div class="modal-body text-center p-5">
                        <div class="mb-4 text-primary opacity-25">
                            <i class="fa fa-search fa-5x"></i>
                        </div>
                        <h3 class="fw-bold text-main mb-2">No Results Found</h3>
                        <p class="text-muted">We couldn't find any data matching your current filter criteria. Try adjusting your dates or parameters.</p>
                        <button type="button" class="btn btn-primary rounded-pill px-5 mt-4 fw-bold" data-bs-dismiss="modal">Got it</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
