<div id="page-content">

    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode($this->data ?? [], JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8'); ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns ?? [], JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8'); ?>">
    </div>

    <div id="display_content">

        <!-- ✅ YOUR MODAL HTML STARTS HERE -->
        <div class="modal-header bg-white">
            <h5 class="modal-title d-flex align-items-center gap-2">
                <i class="fa fa-list-alt text-primary"></i>
                <?php echo htmlspecialchars($this->title ?? 'Edit Menu', ENT_QUOTES, 'UTF-8'); ?>
            </h5>

            <button type="button" class="btn-close" aria-label="Close" ng-click="cancel()"></button>
        </div>

        <div class="modal-body">
            <div class="notification-area mb-3"></div>

            <form name="menu" novalidate>

                <!-- ✅ IMPORTANT: send row_value for post_edit -->
                <input type="hidden" ng-model="form.id">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text"
                           class="form-control"
                           ng-model="form.txt_name"
                           ng-class="{'is-invalid': menu.txt_name.$invalid && menu.$submitted}"
                           name="txt_name"
                           required>
                    <div class="invalid-feedback">Name is required.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Type</label>
                    <div class="d-flex gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" ng-model="form.relation" ng-value="0">
                            <span class="form-check-label">Main Menu</span>
                        </label>

                        <label class="form-check">
                            <input class="form-check-input" type="radio" ng-model="form.relation" ng-value="1">
                            <span class="form-check-label">Sub Menu</span>
                        </label>
                    </div>
                    <div class="form-text text-muted">Sub menu requires a parent.</div>
                </div>

                <div class="mb-3" ng-if="form.relation == 0">
                    <label class="form-label fw-semibold">Icon</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-fw" ng-class="form.txt_icon ? ('fa-' + form.txt_icon) : 'fa-circle'"></i>
                        </span>
                        <input type="text" class="form-control" ng-model="form.txt_icon"
                               placeholder="Example: cog, users, tools">
                    </div>
                </div>

                <div class="mb-3" ng-if="form.relation == 1">
                    <label class="form-label fw-semibold">Parent</label>
                    <select class="form-select"
                            name="int_parent"
                            ng-model="form.int_parent"
                            ng-options="p.id as p.name for p in dropdowns.int_parent_ids track by p.id"
                            ng-class="{'is-invalid': menu.int_parent.$invalid && menu.$submitted}"
                            required>
                        <option value="">-- Select Main Menu --</option>
                    </select>
                    <div class="invalid-feedback">Parent is required for Sub Menu.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Position</label>
                    <input type="number" min="1" class="form-control" ng-model="form.int_position" name="int_position">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link</label>
                    <input type="text" class="form-control" ng-model="form.txt_link" name="txt_link"
                           placeholder="/Menu/index or #">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Title</label>
                    <input type="text"
                           class="form-control"
                           ng-model="form.txt_title"
                           ng-class="{'is-invalid': menu.txt_title.$invalid && menu.$submitted}"
                           name="txt_title"
                           required>
                    <div class="invalid-feedback">Title is required.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Permission</label>
                    <select class="form-select"
                            name="txt_name_permission"
                            ng-model="form.permission_slug"
                            ng-options="p.name as p.name for p in dropdowns.opt_mx_permission_ids">
                        <option value="">-- No Permission (Public) --</option>
                    </select>
                    <div class="form-text text-muted">Select the permission required to view this menu.</div>
                </div>

            </form>
        </div>

        <div class="modal-footer bg-light">
            <span class="text-muted me-auto" ng-if="ProcessingData === true">
                <i class="fa fa-spinner fa-spin me-1"></i> Processing...
            </span>

            <button type="button" class="btn btn-outline-secondary" ng-click="cancel()" ng-disabled="ProcessingData === true">
                Close
            </button>

            <button type="button"
                    class="btn btn-primary"
                    ng-click="menu.$setSubmitted(); saveProfileOperation('Menu', 'post_edit')"
                    ng-disabled="menu.$invalid || ProcessingData === true">
                <i class="fa fa-save me-2"></i>Save
            </button>
        </div>
        <!-- ✅ YOUR MODAL HTML ENDS HERE -->

    </div>
</div>
