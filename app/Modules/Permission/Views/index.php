<style>
    /* Permission Architect - Sidebar Refinements */
    .zt-permission-architect .list-group-item {
        transition: all 0.2s ease;
        border-radius: 12px !important;
        margin-bottom: 4px;
    }
    .zt-permission-architect .list-group-item:hover {
        background: rgba(var(--zt-primary-rgb), 0.05);
        color: var(--zt-primary);
    }
    .zt-permission-architect .list-group-item.active {
        background: linear-gradient(135deg, var(--zt-primary), var(--zt-secondary)) !important;
        color: #fff !important;
        box-shadow: 0 4px 15px rgba(var(--zt-primary-rgb), 0.25);
        border-left: 4px solid var(--zt-accent) !important;
    }
    .zt-permission-architect .list-group-item.active i {
        color: #fff !important;
        opacity: 1 !important;
    }
    .zt-permission-architect .list-group-item.active .zt-menu-admin__panel-icon--create {
        background: rgba(255, 255, 255, 0.2) !important;
        color: #fff !important;
    }
    .zt-permission-architect .zt-search__input {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    .zt-permission-architect .zt-search__input:focus {
        background: #fff;
        box-shadow: 0 0 0 4px rgba(var(--zt-primary-rgb), 0.1);
    }
</style>

<div id="page-content" class="px-4 py-4 zt-animate-fade-in">
    <!-- Controller Data Transfer -->
    <div id="data_content"
         data-permission-detail="<?php use Authentication\Perm_Auth;
         echo htmlspecialchars(json_encode($this->permission_details, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         ></div>

    <div ng-controller="permissionCtrl" ng-init="getData();" class="zt-permission-architect">
        
        <!-- HEADER SECTION -->
        <div class="zt-menu-admin__head mb-5">
            <div>
                <span class="zt-menu-admin__eyebrow">Security & Access Control</span>
                <h2 class="zt-menu-admin__title h1 mt-1 mb-2">Permission Architect</h2>
                <p class="zt-menu-admin__lede text-muted mb-0">Define organizational hierarchy and granular access policies across all system modules.</p>
            </div>
            <div class="d-flex gap-3">
                <button type="button" class="btn btn-premium px-4" ng-click="setActiveTab(1)" ng-class="{'opacity-50': !isActiveTab(1)}">
                    <i class="fa fa-users me-2"></i>Groups
                </button>
                <button type="button" class="btn btn-premium px-4" ng-click="setActiveTab(2)" ng-class="{'opacity-50': !isActiveTab(2)}" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fa fa-user-shield me-2"></i>Users
                </button>
                <button type="button" class="btn btn-premium px-4" ng-click="setActiveTab(3)" ng-class="{'opacity-50': !isActiveTab(3)}" style="background: linear-gradient(135deg, #64748b, #334155);">
                    <i class="fa fa-cog me-2"></i>Manage
                </button>
            </div>
        </div>

        <!-- SPLIT PANEL VIEW (Gropus/Users) -->
        <div class="row g-4" ng-if="isActiveTab(1) || isActiveTab(2)">
            
            <!-- LEFT PANEL: Entity Selector -->
            <div class="col-lg-4">
                <div class="zt-card h-100 p-0 border-0 shadow-lg overflow-hidden">
                    <div class="p-4 border-bottom bg-light bg-opacity-10">
                        <h6 class="zt-card__label mb-3 fw-bold">
                            {{ isActiveTab(1) ? 'Permission Groups' : 'Individual Users' }}
                        </h6>
                        <div class="zt-search w-100 max-width-100">
                            <i class="fa fa-search zt-search__icon"></i>
                            <input type="text" class="zt-search__input bg-white" placeholder="Filter list..." ng-model="searchTerm">
                        </div>
                    </div>
                    
                    <div class="zt-menu-admin__table-wrap" style="max-height: 60vh;">
                        <div class="list-group list-group-flush">
                            <!-- Group List -->
                            <a href="javascript:void(0)" 
                               ng-if="isActiveTab(1)"
                               ng-repeat="g in account_detail.groups | filter:searchTerm track by g.id"
                               class="list-group-item list-group-item-action border-0 px-4 py-3 d-flex align-items-center justify-content-between"
                               ng-class="{'active': new_group_permission.group_id == g.id}"
                               ng-click="new_group_permission.group_id = g.id; getPermissions('getGroupPermissions', g.id)">
                                <div class="d-flex align-items-center">
                                    <div class="zt-menu-admin__panel-icon--create p-2 rounded-3 me-3" style="width: 32px; height: 32px;">
                                        <i class="fa fa-users small"></i>
                                    </div>
                                    <span>{{ g.name }}</span>
                                </div>
                                <i class="fa fa-chevron-right small opacity-50" ng-if="new_group_permission.group_id == g.id"></i>
                            </a>

                            <!-- User List -->
                            <a href="javascript:void(0)" 
                               ng-if="isActiveTab(2)"
                               ng-repeat="u in account_detail.users | filter:searchTerm track by u.id"
                               class="list-group-item list-group-item-action border-0 px-4 py-3 d-flex align-items-center justify-content-between"
                               ng-class="{'active': new_user_permission.user_id == (u.id + ',' + u.domain)}"
                               ng-click="new_user_permission.user_id = (u.id + ',' + u.domain); getPermissions('getUserPermissions', (u.id + ',' + u.domain)); getPermissions('getUserGroups', (u.id + ',' + u.domain))">
                                <div class="d-flex align-items-center">
                                    <img ng-src="https://ui-avatars.com/api/?name={{u.name}}&background=random&color=fff" class="rounded-circle me-3" style="width: 24px; height: 24px;">
                                    <span>{{ u.name }}</span>
                                </div>
                                <i class="fa fa-chevron-right small opacity-50" ng-if="new_user_permission.user_id == (u.id + ',' + u.domain)"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Add Group Form Footer (Tab 1 Only) -->
                    <div class="p-4 bg-light bg-opacity-5 border-top" ng-if="isActiveTab(1)">
                        <form ng-submit="saveForm('Permission', 'saveGroup')">
                            <div class="input-group">
                                <input type="text" class="form-control border-0 bg-white" placeholder="New group name..." ng-model="form.name" required>
                                <button class="btn btn-warning text-white fw-bold px-3" type="submit">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: Matrix -->
            <div class="col-lg-8">
                <div class="zt-card h-100 p-0 border-0 shadow-lg overflow-hidden position-relative">
                    
                    <!-- Empty State -->
                    <div class="p-5 text-center my-5" ng-if="!group_permission_flag && !user_permission_flag">
                        <div class="opacity-10 mb-4">
                            <i class="fa fa-shield-alt fa-5x"></i>
                        </div>
                        <h4 class="text-muted fw-light">Select an entity to configure permissions</h4>
                        <p class="small text-muted">Groups define collective access, while User Overrides handle specific cases.</p>
                    </div>

                    <!-- Permission Matrix (Groups) -->
                    <div ng-if="isActiveTab(1) && group_permission_flag" class="zt-animate-fade-in">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-primary bg-opacity-5">
                            <h5 class="mb-0 fw-bold">Matrix: <span class="text-primary">{{ (account_detail.groups | filter:{id:new_group_permission.group_id}:true)[0].name }}</span></h5>
                            <button type="button" class="btn btn-primary px-4 shadow-sm" ng-click="saveTableData('group_permission_table', 'postGroupPermission', new_group_permission.group_id)">
                                <i class="fa fa-save me-2"></i>Commit Changes
                            </button>
                        </div>
                        
                        <div class="zt-menu-admin__table-wrap" style="max-height: 70vh;">
                            <table class="table table-hover align-middle mb-0 zt-table-responsive">
                                <thead class="sticky-top bg-white shadow-sm" style="z-index: 10;">
                                    <tr>
                                        <th class="ps-4 py-3" style="width: 50px;"></th>
                                        <th class="py-3">Capability</th>
                                        <th class="text-center py-3">Access</th>
                                    </tr>
                                </thead>
                                <tbody ng-repeat="(key, value) in group_permissions | groupBy: 'section_name' track by key">
                                    <tr class="bg-primary bg-opacity-5">
                                        <td colspan="2" class="fw-bold text-primary small ps-4 py-3"><i class="fa fa-folder-open me-2"></i>{{key}}</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" ng-model="sectionCheck1[key]" ng-change="toggleSection(value, sectionCheck1[key])">
                                            </div>
                                        </td>
                                    </tr>
                                    <tr ng-repeat="permission in value track by permission.permission_id" class="zt-menu-admin__row">
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
                    </div>

                    <!-- Permission Matrix (User Overrides) -->
                    <div ng-if="isActiveTab(2) && user_permission_flag" class="zt-animate-fade-in">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-success bg-opacity-5">
                            <h5 class="mb-0 fw-bold">Overrides: <span class="text-success">{{ (account_detail.users | filter:{id:new_user_permission.user_id.split(',')[0]}:true)[0].name }}</span></h5>
                            <button type="button" class="btn btn-success px-4 shadow-sm" ng-click="saveTableData('user_permission_table', 'postUserPermission', new_user_permission.user_id)">
                                <i class="fa fa-save me-2"></i>Apply Overrides
                            </button>
                        </div>
                        
                        <!-- Mini Tabs for User (Groups vs Overrides) -->
                        <div class="px-4 py-2 border-bottom bg-light bg-opacity-10 d-flex gap-4">
                            <a href="javascript:void(0)" class="small text-decoration-none fw-bold" ng-class="{'text-success': !showUserGroups, 'text-muted': showUserGroups}" ng-click="showUserGroups = false">Permissions</a>
                            <a href="javascript:void(0)" class="small text-decoration-none fw-bold" ng-class="{'text-success': showUserGroups, 'text-muted': !showUserGroups}" ng-click="showUserGroups = true">Assigned Groups</a>
                        </div>

                        <div class="zt-menu-admin__table-wrap" style="max-height: 62vh;">
                            <!-- Permissions Grid -->
                            <table class="table table-hover align-middle mb-0 zt-table-responsive" ng-if="!showUserGroups">
                                <tbody ng-repeat="(key, value) in user_permissions | groupBy: 'section_name' track by key">
                                    <tr class="bg-success bg-opacity-5">
                                        <td class="fw-bold text-success small ps-4 py-3"><i class="fa fa-folder-open me-2"></i>{{key}}</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" ng-model="sectionCheck2[key]" ng-change="toggleSection(value, sectionCheck2[key])">
                                            </div>
                                        </td>
                                    </tr>
                                    <tr ng-repeat="p in value track by p.permission_id" class="zt-menu-admin__row">
                                        <td class="ps-5 text-muted small fw-medium">{{p.permission_display_name}}</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" ng-model="p.check">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Assigned Groups Grid -->
                            <table class="table table-hover align-middle mb-0 zt-table-responsive" ng-if="showUserGroups">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th class="ps-4 py-3">Group Identity</th>
                                        <th class="text-center py-3">Membership</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr ng-repeat="group in groups">
                                        <td class="ps-4 text-muted small fw-medium"><i class="fa fa-users me-2 text-success opacity-50"></i>{{group.group_name}}</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" ng-model="group.check">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Footer for User Groups -->
                        <div class="p-4 bg-light bg-opacity-5 border-top text-end" ng-if="showUserGroups">
                            <button type="button" class="btn btn-success px-4" ng-click="saveTableData('user_group_table', 'postUserGroup', new_user_permission.user_id)">
                                <i class="fa fa-save me-2"></i>Update Memberships
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: INFRASTRUCTURE MANAGEMENT -->
        <div class="row g-4" ng-if="isActiveTab(3)">
            <div class="col-lg-4">
                <div class="zt-card border-0 shadow-lg p-4">
                    <h6 class="zt-card__label text-secondary mb-4 fw-bold"><i class="fa fa-folder-plus me-2"></i>New Logical Section</h6>
                    <form ng-submit="saveForm('Permission', 'saveSection')">
                        <div class="zt-form-group mb-4">
                            <label class="form-label-premium">Section Name</label>
                            <input type="text" class="form-control-premium px-3" placeholder="e.g. Audit Logs" ng-model="form.txt_name" required>
                        </div>
                        <button type="submit" class="btn btn-premium w-100 shadow-sm" style="background: #64748b;">
                            Create Section
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="zt-card border-0 shadow-lg p-4">
                    <h6 class="zt-card__label text-success mb-4 fw-bold"><i class="fa fa-key me-2"></i>New Permission Key</h6>
                    <form ng-submit="saveForm('Permission', 'savePermission')">
                        <div class="zt-form-group mb-3">
                            <label class="form-label-premium">Display Label</label>
                            <input type="text" class="form-control-premium px-3" placeholder="e.g. Manage API" ng-model="form.display_name" required>
                        </div>
                        <div class="zt-form-group mb-3">
                            <label class="form-label-premium">System Slug</label>
                            <input type="text" class="form-control-premium px-3" placeholder="e.g. manage_api" ng-model="form.name" required>
                        </div>
                        <div class="zt-form-group mb-4">
                            <label class="form-label-premium">Category</label>
                            <select class="form-select rounded-pill bg-white bg-opacity-50 border-0 shadow-sm text-dark px-4 py-2" 
                                    ng-options="v.id as v.name for (k, v) in account_detail.sections"
                                    ng-model="form.section_id" required>
                                <option value="">-- Select Section --</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-premium w-100 shadow-sm">
                            Generate Permission
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="zt-card border-0 shadow-lg p-0 overflow-hidden">
                    <div class="p-4 border-bottom bg-primary bg-opacity-5">
                        <h6 class="zt-card__label text-primary mb-0 fw-bold"><i class="fa fa-list-check me-2"></i>System Registry</h6>
                    </div>
                    <div class="zt-menu-admin__table-wrap" style="max-height: 60vh;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <tbody ng-repeat="(key, value) in account_detail.allPermissions | groupBy: 'section_name' track by key">
                                <tr class="bg-primary bg-opacity-5">
                                    <th class="ps-4 py-2 text-primary small fw-bold">{{key}}</th>
                                </tr>
                                <tr ng-repeat="p in value track by p.permission_id">
                                    <td class="ps-5 text-muted small border-0 py-2 fw-medium">{{p.permission_display_name}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
