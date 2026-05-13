<div id="page-content" class="p-3">
    <?= Library\DataView::getStyles() ?>
    
    <style>
        /* Miscellaneous Module Specific Styles */
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .filter-buttons .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .filter-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: white;
        }

        .add-setting-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .add-setting-header {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #dee2e6;
        }

        .inline-form-row {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .settings-table {
            margin-bottom: 0;
        }

        .settings-table thead th {
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem 0.75rem;
        }

        .settings-table tbody tr {
            transition: all 0.2s ease;
            animation: fadeInRow 0.4s ease-out backwards;
        }

        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .settings-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.001);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .settings-table tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
        }

        .rule-name {
            font-weight: 600;
            color: #495057;
        }

        .rule-description {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .current-value {
            font-family: 'Courier New', monospace;
            background: #e7f3ff;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: #0066cc;
            font-weight: 500;
        }

        .action-buttons-group .btn {
            border-radius: 6px;
            padding: 0.375rem 0.875rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .action-buttons-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #dee2e6;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        .date-badge {
            background: #f8f9fa;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .date-badge i {
            color: #667eea;
            margin-right: 0.25rem;
        }
    </style>

    <div id="data_content" 
        data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
        data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>">
        
        <div id="display_content">
            <div class="notification-area mb-3"></div>

            <!-- Header Section -->
            <div class="settings-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold">
                            <i class="fa fa-cog me-2"></i>Miscellaneous Settings
                        </h4>
                        <p class="mb-0 opacity-75 small">Configure system-wide settings and rules</p>
                    </div>
                    <div class="filter-buttons d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm" ng-click="viewMiscellaneous('all')">
                            <i class="fa fa-list me-1"></i>All Settings
                        </button>
                        <button type="button" class="btn btn-light btn-sm" ng-click="viewMiscellaneous('active')">
                            <i class="fa fa-check-circle me-1"></i>Active
                        </button>
                        <button type="button" class="btn btn-light btn-sm" ng-click="viewMiscellaneous('pending')">
                            <i class="fa fa-clock me-1"></i>Pending
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Add Form (Hidden on Desktop) -->
            <div class="add-setting-card d-md-none mb-4">
                <div class="add-setting-header">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa fa-plus-circle me-2 text-primary"></i>Add New Setting
                    </h5>
                </div>
                <div class="p-3">
                    <form name="rule" novalidate>
                        <div class="mb-3">
                            <label for="mobile_int_mx_rule_id" class="form-label fw-medium">Rule</label>
                            <select name="int_mx_rule_id" id="mobile_int_mx_rule_id" class="form-select"
                                    ng-model="form.int_mx_rule_id" required
                                    ng-class="{'is-invalid': rule.int_mx_rule_id.$invalid && !rule.int_mx_rule_id.$pristine}"
                                    ng-change="checkType()"
                                    ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_rule_ids">
                                <option value="">Select Rule</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mobile_txt_value" class="form-label fw-medium">Value</label>
                            <input id="mobile_txt_value" type="{{form.type}}" 
                                   ng-class="{'form-control': form.type !== 'checkbox'}" 
                                   ng-model="form.txt_value" />
                        </div>
                        
                        <div class="mb-3">
                            <label for="mobile_dat_effective_start_date" class="form-label fw-medium">Effective Start Date</label>
                            <input type="date" id="mobile_dat_effective_start_date" 
                                   min="<?php echo date('Y-m-d');?>" 
                                   class="form-control"  
                                   ng-class="{'is-invalid': rule.dat_effective_start_date.$invalid && !rule.dat_effective_start_date.$pristine}" 
                                   ng-model="form.dat_effective_start_date" />
                        </div>
                        
                        <div class="mb-3">
                            <label for="mobile_dat_effective_end_date" class="form-label fw-medium">Effective End Date</label>
                            <input type="date" id="mobile_dat_effective_end_date" 
                                   min="<?php echo date('Y-m-d');?>" 
                                   class="form-control"  
                                   ng-class="{'is-invalid': rule.dat_effective_end_date.$invalid && !rule.dat_effective_end_date.$pristine}" 
                                   ng-model="form.dat_effective_end_date" />
                        </div>
                        
                        <button type="button" class="btn btn-primary w-100" ng-click="saveMiscellaneous($event, rule)">
                            <i class="fa fa-save me-2"></i>Save Setting
                        </button>
                    </form>
                </div>
            </div>

            <!-- Main Settings Table -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table settings-table table-hover align-middle mb-0">
                            <thead>
                                <!-- Desktop Inline Add Form -->
                                <tr class="d-none d-md-table-row inline-form-row">
                                    <td colspan="2">
                                        <select name="int_mx_rule_id" id="int_mx_rule_id" class="form-select"
                                                ng-model="form.int_mx_rule_id" required
                                                ng-class="{'is-invalid': rule.int_mx_rule_id.$invalid && !rule.int_mx_rule_id.$pristine}"
                                                ng-change="checkType()"
                                                ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_rule_ids">
                                            <option value="">Select Rule</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input id="txt_value" type="{{form.type}}" 
                                               ng-class="{'form-control': form.type !== 'checkbox'}" 
                                               ng-model="form.txt_value" 
                                               placeholder="Enter value" />
                                    </td>
                                    <td>
                                        <input type="date" id="dat_effective_start_date" 
                                               min="<?php echo date('Y-m-d');?>" 
                                               class="form-control"  
                                               ng-class="{'is-invalid': rule.dat_effective_start_date.$invalid && !rule.dat_effective_start_date.$pristine}" 
                                               ng-model="form.dat_effective_start_date" />
                                    </td>
                                    <td>
                                        <input type="date" id="dat_effective_end_date" 
                                               min="<?php echo date('Y-m-d');?>" 
                                               class="form-control"  
                                               ng-class="{'is-invalid': rule.dat_effective_end_date.$invalid && !rule.dat_effective_end_date.$pristine}" 
                                               ng-model="form.dat_effective_end_date" />
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm" ng-click="saveMiscellaneous($event, rule)">
                                            <i class="fa fa-save me-1"></i>Save
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Column Headers -->
                                <tr>
                                    <th>Rule</th>
                                    <th>Description</th>
                                    <th>Current Setting</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="rule in misc_data" style="animation-delay: {{$index * 0.05}}s;">
                                    <td>
                                        <span class="rule-name">{{rule.txt_name}}</span>
                                    </td>
                                    <td>
                                        <span class="rule-description">{{rule.txt_description}}</span>
                                    </td>
                                    <td>
                                        <span class="rule_value{{rule.config_id}}">
                                            <span class="current-value">{{rule.txt_value}}</span>
                                        </span>
                                        <span class="rule_editor{{rule.config_id}} d-none">
                                            <input type="{{rule.txt_type}}" 
                                                   ng-class="{'form-control': rule.txt_type !== 'checkbox'}" 
                                                   ng-model="rule.txt_value" />
                                        </span>
                                    </td>
                                    <td>
                                        <span class="rule_value{{rule.config_id}}">
                                            <span class="date-badge">
                                                <i class="fa fa-calendar-alt fa-xs"></i>
                                                {{rule.dat_effective_start_date | date:"dd MMM yyyy"}}
                                            </span>
                                        </span>
                                        <span class="rule_editor{{rule.config_id}} d-none">
                                            <input type="date" 
                                                   min="<?php echo date('Y-m-d');?>" 
                                                   class="form-control"  
                                                   ng-model="rule.dat_effective_start_date" />
                                        </span>
                                    </td>
                                    <td>
                                        <span class="rule_value{{rule.config_id}}">
                                            <span class="date-badge">
                                                <i class="fa fa-calendar-alt fa-xs"></i>
                                                {{rule.dat_effective_end_date | date:"dd MMM yyyy"}}
                                            </span>
                                        </span>
                                        <span class="rule_editor{{rule.config_id}} d-none">
                                            <input type="date" 
                                                   min="<?php echo date('Y-m-d');?>" 
                                                   class="form-control"  
                                                   ng-model="rule.dat_effective_end_date" />
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons-group d-flex gap-1 justify-content-center">
                                            <button type="button" 
                                                    ng-show="dateDiff(rule.dat_effective_start_date, rule.dat_effective_end_date).value > 0" 
                                                    class="btn btn-outline-primary btn-sm edit" 
                                                    ng-click="editMiscellaneous($event, rule)"
                                                    title="Edit Setting">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm d-none save" 
                                                    ng-click="updateMiscellaneous($event, rule)"
                                                    title="Save Changes">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm d-none cancel" 
                                                    ng-click="cancelMiscellaneous($event, rule)"
                                                    title="Cancel">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
