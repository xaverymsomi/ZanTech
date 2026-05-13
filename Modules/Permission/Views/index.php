<div id="page-content" class="px-4 py-4 zt-animate-fade-in">
    <!-- Controller Data Transfer -->
    <div id="data_content"
         data-permission-detail="<?php use Authentication\Perm_Auth;
         echo htmlspecialchars(json_encode($this->permission_details, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         ></div>

    <div ng-controller="permissionCtrl" ng-init="getData();">
        <div class="zt-card zt-table-card border-0">
            <div class="zt-card__header border-bottom bg-transparent p-0">
                <!-- Tabs - Premium Styling -->
                <ul class="nav nav-pills zt-pills-modern px-4 py-3" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link px-4 py-2 fw-bold"
                                ng-class="{active: isActiveTab(1)}"
                                ng-click="setActiveTab(1)">
                            <i class="fa fa-users me-2"></i>Groups
                        </button>
                    </li>
                    <li class="nav-item mx-2">
                        <button type="button" class="nav-link px-4 py-2 fw-bold"
                                ng-class="{active: isActiveTab(2)}"
                                ng-click="setActiveTab(2)">
                            <i class="fa fa-user-shield me-2"></i>Users
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link px-4 py-2 fw-bold"
                                ng-class="{active: isActiveTab(3)}"
                                ng-click="setActiveTab(3)">
                            <i class="fa fa-cog me-2"></i>Management
                        </button>
                    </li>
                </ul>
            </div>

            <div class="zt-card__body p-4">
                
                <!-- TAB 1: GROUP PERMISSIONS -->
                <div ng-show="isActiveTab(1);" class="animate slideIn">
                    <div class="row g-4">
                        <!-- Add New Group -->
                        <div class="col-lg-4">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white">
                                <h6 class="zt-card__label mb-4 text-warning fw-bold"><i class="fa fa-plus-circle me-2"></i>New Group</h6>
                                <form id="new_group" ng-submit="saveForm('Permission', 'saveGroup')">
                                    <div class="mb-4">
                                        <label class="form-label-premium">Group Name</label>
                                        <input type="text" class="form-control-premium" placeholder="e.g. Sales Team" 
                                               ng-model="form.name" required>
                                    </div>
                                    <button type="submit" class="btn btn-premium w-100 shadow-sm" ng-disabled="new_group.$invalid" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                        <i class="fa fa-save me-2"></i>Save Group
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Grant Group Permission -->
                        <div class="col-lg-8">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h6 class="zt-card__label text-primary mb-0 fw-bold"><i class="fa fa-lock me-2"></i>Grant Permissions</h6>
                                    
                                    <div class="w-50">
                                        <select class="form-select rounded-pill bg-white bg-opacity-50 border-0 shadow-sm text-dark px-4" 
                                                ng-options="value.id as value.name for (key, value) in account_detail.groups" 
                                                ng-model="new_group_permission.group_id" 
                                                ng-change="getPermissions('getGroupPermissions', new_group_permission.group_id);"
                                                required>
                                            <option value="">-- Choose Group --</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="table-responsive rounded-4 shadow-sm bg-white bg-opacity-50" ng-show="group_permission_flag" style="max-height: 50vh;">
                                    <table class="table table-hover align-middle mb-0 zt-table-responsive">
                                        <thead class="bg-light bg-opacity-50">
                                            <tr>
                                                <th class="ps-4 py-3" style="width: 50px;"></th>
                                                <th class="py-3">Permission Name</th>
                                                <th class="text-center py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody ng-repeat="(key, value) in group_permissions | groupBy: 'section_name'">
                                            <tr class="bg-primary bg-opacity-5">
                                                <td colspan="2" class="fw-bold text-primary small ps-4">{{key}}</td>
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox" onclick="selectSectionPermission($(this));">
                                                </td>
                                            </tr>
                                            <tr ng-repeat="permission in value">
                                                <td></td>
                                                <td class="ps-4 text-muted small fw-medium">{{permission.permission_display_name}}</td>
                                                <td class="text-center">
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox" ng-model="permission.check">
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-4 d-flex justify-content-between align-items-center" ng-show="group_permission_flag">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" ng-model="gp_check" id="gp_conf">
                                        <label class="form-check-label text-muted small fw-medium" for="gp_conf">I confirm these settings</label>
                                    </div>
                                    <button type="button" class="btn btn-premium px-4" ng-disabled="!gp_check"
                                            ng-click="saveTableData('group_permission_table', 'postGroupPermission', new_group_permission.group_id); gp_check = false;">
                                        <i class="fa fa-check-circle me-2"></i>Save Permissions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: USER PERMISSIONS -->
                <div ng-show="isActiveTab(2);" class="animate slideIn">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white h-100">
                                <h6 class="zt-card__label text-success mb-4 fw-bold"><i class="fa fa-user-tag me-2"></i>Assign Groups to User</h6>
                                <select class="form-select form-select-lg rounded-4 bg-white bg-opacity-50 border-0 shadow-sm text-dark px-4 mb-4" 
                                        ng-model="new_user_group.user_id" required 
                                        ng-change="getPermissions('getUserGroups', new_user_group.user_id);">
                                    <option value="">-- Select User --</option>
                                    <option ng-repeat="u in account_detail.users" value="{{u.id + ',' + u.domain}}">{{u.name}}</option>
                                </select>

                                <div class="table-responsive rounded-4 shadow-sm bg-white bg-opacity-50" ng-show="user_group_flag">
                                    <table class="table table-hover align-middle mb-0 zt-table-responsive">
                                        <thead class="bg-light bg-opacity-50">
                                            <tr>
                                                <th class="ps-4 py-3">Group Name</th>
                                                <th class="text-center py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr ng-repeat="group in groups">
                                                <td class="ps-4 text-muted small fw-medium"><i class="fa fa-users me-2 text-primary opacity-50"></i>{{group.group_name}}</td>
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox" ng-model="group.check">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-4 text-end" ng-show="user_group_flag">
                                    <button type="button" class="btn btn-premium px-4" style="background: linear-gradient(135deg, #10b981, #059669);"
                                            ng-click="saveTableData('user_group_table', 'postUserGroup', new_user_group.user_id, false);">
                                        <i class="fa fa-save me-2"></i>Save Groups
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Special User Permissions -->
                        <div class="col-lg-6">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white h-100">
                                <h6 class="zt-card__label text-info mb-4 fw-bold"><i class="fa fa-user-lock me-2"></i>Individual Overrides</h6>
                                <select class="form-select form-select-lg rounded-4 bg-white bg-opacity-50 border-0 shadow-sm text-dark px-4 mb-4" 
                                        ng-model="new_user_permission.user_id" 
                                        ng-change="getPermissions('getUserPermissions', new_user_permission.user_id);" required>
                                    <option value="">-- Select User --</option>
                                    <option ng-repeat="u in account_detail.users" value="{{u.id + ',' + u.domain}}">{{u.name}}</option>
                                </select>

                                <div class="table-responsive rounded-4 shadow-sm bg-white bg-opacity-50" ng-show="user_permission_flag" style="max-height: 40vh;">
                                    <table class="table table-hover align-middle mb-0 zt-table-responsive">
                                        <tbody ng-repeat="(key, value) in user_permissions | groupBy: 'section_name'">
                                            <tr class="bg-info bg-opacity-5">
                                                <td class="fw-bold text-info small ps-4 py-2">{{key}}</td>
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox" onclick="selectSectionPermission($(this));">
                                                </td>
                                            </tr>
                                            <tr ng-repeat="p in value">
                                                <td class="ps-5 text-muted small fw-medium">{{p.permission_display_name}}</td>
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox" ng-model="p.check">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 text-end" ng-show="user_permission_flag">
                                    <button type="button" class="btn btn-premium px-4" style="background: linear-gradient(135deg, #0ea5e9, #0284c7);"
                                            ng-click="saveTableData('user_permission_table', 'postUserPermission', new_user_permission.user_id);">
                                        <i class="fa fa-save me-2"></i>Apply Overrides
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: MANAGEMENT -->
                <div ng-show="isActiveTab(3);" class="animate slideIn">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white">
                                <h6 class="zt-card__label text-secondary mb-4 fw-bold">Add Section</h6>
                                <form ng-submit="saveForm('Permission', 'saveSection')">
                                    <div class="mb-4">
                                        <label class="form-label-premium">Section Title</label>
                                        <input type="text" class="form-control-premium" placeholder="e.g. Finance" ng-model="form.txt_name" required>
                                    </div>
                                    <button type="submit" class="btn btn-premium w-100 shadow-sm" style="background: #64748b;">
                                        <i class="fa fa-folder-plus me-2"></i>Create Section
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white">
                                <h6 class="zt-card__label text-success mb-4 fw-bold">Add Permission</h6>
                                <form ng-submit="saveForm('Permission', 'savePermission')">
                                    <div class="mb-3">
                                        <label class="form-label-premium">Display Name</label>
                                        <input type="text" class="form-control-premium" placeholder="e.g. View Invoices" ng-model="form.display_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label-premium">System Name (slug)</label>
                                        <input type="text" class="form-control-premium" placeholder="e.g. view_invoices" ng-model="form.name" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label-premium">Parent Section</label>
                                        <select class="form-select rounded-pill bg-white bg-opacity-50 border-0 shadow-sm text-dark px-4" 
                                                ng-options="v.id as v.name for (k, v) in account_detail.sections"
                                                ng-model="form.section_id" required>
                                            <option value="">-- Choose Section --</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-premium w-100 shadow-sm">
                                        <i class="fa fa-key me-2"></i>Create Permission
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="bg-light bg-opacity-25 p-4 rounded-4 border border-white h-100">
                                <h6 class="zt-card__label text-primary mb-4 fw-bold">Defined Permissions</h6>
                                <div class="table-responsive rounded-4 shadow-sm bg-white bg-opacity-50" style="max-height: 50vh;">
                                    <table class="table table-sm table-hover align-middle mb-0 zt-table-responsive">
                                        <tbody ng-repeat="(key, value) in account_detail.allPermissions | groupBy: 'section_name'">
                                            <tr class="bg-primary bg-opacity-5">
                                                <th class="ps-3 py-2 text-primary small fw-bold">{{key}}</th>
                                            </tr>
                                            <tr ng-repeat="p in value">
                                                <td class="ps-4 text-muted small border-0 py-1 fw-medium">{{p.permission_display_name}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
