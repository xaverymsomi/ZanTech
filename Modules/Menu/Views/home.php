<div id="page-content" class="p-3 p-lg-4">
    <!-- Data Manifest: High-security injection -->
    <script>
        window.zt_menu_dropdowns = <?php echo json_encode(
            $this->dropdowns, 
            JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ); ?>;
    </script>

    <div ng-controller="profileController" ng-cloak>
        <div ng-controller="menuManageCtrl" ng-cloak>

        <header class="mb-4">
            <h1 class="h3 mb-1">Menu management</h1>
            <p class="text-muted small mb-0">Create and organize system navigation.</p>
        </header>

        <div class="row g-4" ng-init='getMenuDropdowns(window.zt_menu_dropdowns); getAllMenus();'>
            
            <!-- Create form -->
            <div class="col-lg-5">
                <div class="zt-card">
                    <h5 class="mb-4">New menu item</h5>
                    <form id="new_menu" name="new_menu" novalidate>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Name</label>
                            <input type="text" class="form-control" placeholder="Menu name"
                                   name="txt_name" ng-model="new_menu_form.txt_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold d-block">Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="relation" id="rel_main" ng-model="new_menu_form.relation" ng-value="0">
                                <label class="btn btn-outline-secondary" for="rel_main">Main menu</label>
                                
                                <input type="radio" class="btn-check" name="relation" id="rel_sub" ng-model="new_menu_form.relation" ng-value="1">
                                <label class="btn btn-outline-secondary" for="rel_sub">Submenu</label>
                            </div>
                        </div>

                        <div ng-if="new_menu_form.relation == 0">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Icon</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i ng-class="iconPreviewClasses(new_menu_form.txt_icon)"></i>
                                    </span>
                                    <input type="text" class="form-control" placeholder="fa-home" ng-model="new_menu_form.txt_icon">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Sidebar group</label>
                                <input type="text" class="form-control" placeholder="e.g. settings" ng-model="new_menu_form.txt_sidebar_group">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Position</label>
                                <input type="number" class="form-control" ng-model="new_menu_form.int_position" min="1">
                            </div>
                        </div>

                        <div class="mb-3" ng-if="new_menu_form.relation == 1">
                            <label class="form-label small fw-bold">Parent menu</label>
                            <select class="form-select" ng-model="new_menu_form.int_parent"
                                    ng-options="p.id as p.name for p in dropdowns.int_parent_ids track by p.id" required>
                                <option value="">Select parent...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Route link</label>
                            <input type="text" class="form-control font-monospace" placeholder="/Menu/index" ng-model="new_menu_form.txt_link">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Page title</label>
                            <input type="text" class="form-control" placeholder="Header title" ng-model="new_menu_form.txt_title" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2" 
                                ng-click="saveMenu()" ng-disabled="new_menu.$invalid || savingMenu">
                            <i class="fa-solid fa-save me-2" ng-if="!savingMenu"></i>
                            <i class="fa-solid fa-spinner fa-spin me-2" ng-if="savingMenu"></i>
                            {{ savingMenu ? 'Saving...' : 'Save menu' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Tree -->
            <div class="col-lg-7">
                <div class="zt-card p-0 overflow-hidden">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Menu structure</h5>
                        <button class="btn btn-sm btn-outline-primary" ng-click="getAllMenus()">
                            <i class="fa-solid fa-sync me-1"></i> Refresh
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Menu item</th>
                                    <th class="text-center">Order</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody ng-repeat="parent in dropdowns.all_menus track by parent.id">
                                <tr class="table-info-soft fw-bold">
                                    <td>
                                        <i ng-class="iconPreviewClasses(parent.txt_icon)" class="me-2 text-primary"></i>
                                        {{ parent.txt_name }}
                                        <div class="small text-muted fw-normal ps-4">{{ parent.txt_link }}</div>
                                    </td>
                                    <td class="text-center">{{ parent.int_position }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" ng-click="showActionForm(parent.txt_row_value, 'Menu', 'edit')">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr ng-repeat="child in parent.children track by child.id">
                                    <td class="ps-4">
                                        <i class="fa-solid fa-turn-up fa-rotate-90 me-2 text-muted small"></i>
                                        {{ child.txt_name }}
                                        <div class="small text-muted fw-normal ps-4">{{ child.txt_link }}</div>
                                    </td>
                                    <td class="text-center small text-muted">{{ child.int_position }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" ng-click="showActionForm(child.txt_row_value, 'Menu', 'edit')">
                                            <i class="fa-solid fa-edit"></i>
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
