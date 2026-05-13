<div id="page-content" class="p-3">
    <div ng-controller="menuManageCtrl">

        <div class="row g-4"
             ng-init='getMenuDropdowns(<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, "UTF-8") ?>)'>

            <!-- Left: Add / Update Menu -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-0 text-primary">
                                <i class="fa fa-plus-circle me-2"></i>Menu Management
                            </h5>
                            <div class="text-muted small">Create main menus and submenus.</div>
                        </div>
                        <span class="badge bg-light text-dark">mx_menu</span>
                    </div>

                    <div class="card-body">
                        <form id="new_menu" name="new_menu" novalidate>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Name</label>
                                <input type="text"
                                       placeholder="E.g. Utility"
                                       name="txt_name"
                                       class="form-control"
                                       ng-model="new_menu_form.txt_name"
                                       ng-class="{'is-invalid': new_menu.txt_name.$invalid && new_menu.$submitted}"
                                       required />
                                <div class="invalid-feedback">Menu name is required.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Type</label>

                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="relation"
                                               ng-model="new_menu_form.relation"
                                               ng-value="0">
                                        <span class="form-check-label">Main Menu</span>
                                    </label>

                                    <label class="form-check">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="relation"
                                               ng-model="new_menu_form.relation"
                                               ng-value="1">
                                        <span class="form-check-label">Sub Menu</span>
                                    </label>
                                </div>

                                <div class="form-text text-muted">
                                    Main Menu shows in sidebar with icon. Sub Menu belongs to a Main Menu.
                                </div>
                            </div>

                            <!-- Icon for main menu -->
                            <div class="mb-3" ng-if="new_menu_form.relation == 0">
                                <label class="form-label fw-bold">Icon</label>

                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <!-- supports tools OR fa-tools -->
                                        <i class="fa fa-fw"
                                           ng-class="(new_menu_form.txt_icon || '').startsWith('fa-')
                                                ? (new_menu_form.txt_icon)
                                                : ('fa-' + (new_menu_form.txt_icon || 'circle'))"></i>
                                    </span>

                                    <input type="text"
                                           placeholder="E.g. tools / user-shield / fa-tools"
                                           name="txt_icon"
                                           class="form-control"
                                           ng-model="new_menu_form.txt_icon" />
                                </div>

                                <div class="form-text text-muted">
                                    Use FontAwesome name like <code>tools</code>, <code>list-alt</code> (or <code>fa-tools</code>).
                                </div>
                            </div>

                            <!-- Parent dropdown for submenu -->
                            <div class="mb-3" ng-if="new_menu_form.relation == 1">
                                <label class="form-label fw-bold">Parent Main Menu</label>

                                <select class="form-select"
                                        name="int_parent"
                                        ng-model="new_menu_form.int_parent"
                                        ng-options="p.id as p.name for p in dropdowns.int_parent_ids track by p.id"
                                        required>
                                    <option value="">-- Select Main Menu --</option>
                                </select>

                                <div class="form-text text-muted">
                                    This submenu will appear under the selected main menu.
                                </div>
                            </div>

                            <!-- Position for main menu only -->
                            <div class="mb-3" ng-if="new_menu_form.relation == 0">
                                <label class="form-label fw-bold">Position Order</label>
                                <input type="number"
                                       placeholder="E.g. 1"
                                       name="int_position"
                                       class="form-control"
                                       ng-model="new_menu_form.int_position"
                                       min="1"
                                       required />
                                <div class="form-text text-muted">
                                    The sidebar ordering for main menus.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Route Link</label>
                                <input type="text"
                                       placeholder="E.g. /Menu/index or #"
                                       name="txt_link"
                                       class="form-control"
                                       ng-model="new_menu_form.txt_link" />
                                <div class="form-text text-muted">
                                    Use <code>#</code> for parent-only menu (no navigation).
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Page Title</label>
                                <input type="text"
                                       placeholder="E.g. LRS: Menu Management"
                                       name="txt_title"
                                       class="form-control"
                                       ng-model="new_menu_form.txt_title"
                                       ng-class="{'is-invalid': new_menu.txt_title.$invalid && new_menu.$submitted}"
                                       required />
                                <div class="invalid-feedback">Title is required.</div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit"
                                        class="btn btn-primary"
                                        ng-click="new_menu.$setSubmitted(); saveMenu();"
                                        ng-disabled="new_menu.$invalid">
                                    <i class="fa fa-save me-2"></i>Save Menu
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Menu Structure -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0 text-success">
                                <i class="fa fa-sitemap me-2"></i>Menu Structure
                            </h5>
                            <div class="text-muted small">Preview current hierarchy.</div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary" ng-click="getAllMenus()">
                            <i class="fa fa-refresh me-1"></i>Refresh
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 75vh; overflow-y:auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                <tr>
                                    <th>Menu</th>
                                    <th class="text-center" style="width:110px;">Action</th>
                                </tr>
                                </thead>

                                <tbody ng-repeat="parent in dropdowns.all_menus track by parent.id">

                                <!-- Parent -->
                                <tr class="table-secondary">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-dark me-2">{{parent.int_position}}</span>

                                            <i class="fa fa-fw me-2 text-primary"
                                               ng-class="parent.txt_icon ? ('fa-' + parent.txt_icon) : 'fa-circle'"></i>

                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{parent.txt_name}}</span>
                                                <small class="text-muted">{{parent.txt_title}} • {{parent.txt_link}}</small>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-light border"
                                                ng-click="showActionForm(parent.txt_row_value, 'Menu', 'edit')"
                                                title="Edit">
                                            <i class="fa fa-pencil text-primary"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Children -->
                                <tr ng-repeat="child in parent.children track by child.id">
                                    <td class="ps-5">
                                        <div class="d-flex align-items-center text-muted">
                                            <i class="fa fa-level-up fa-rotate-90 me-2 opacity-50"></i>

                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold text-dark">{{child.txt_name}}</span>
                                                <small class="text-muted">{{child.txt_title}} • {{child.txt_link}}</small>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-light border"
                                                ng-click="showActionForm(child.txt_row_value, 'Menu', 'edit')"
                                                title="Edit">
                                            <i class="fa fa-pencil text-muted"></i>
                                        </button>
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
</div>

<style>
    .table-secondary td { background: #f3f4f6 !important; }
</style>
