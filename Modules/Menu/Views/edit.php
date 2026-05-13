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

                <!-- row_value for post_edit -->
                <input type="hidden" name="id" ng-model="form.id">

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
                            <i ng-class="iconPreviewClasses(form.txt_icon)"></i>
                        </span>
                        <input type="text" class="form-control" ng-model="form.txt_icon"
                               placeholder="e.g. tools, fa-tools, fa-solid fa-user">
                    </div>
                </div>

                <div class="mb-3" ng-if="form.relation == 0">
                    <label class="form-label fw-semibold">Sidebar group</label>
                    <input type="text" class="form-control" name="txt_sidebar_group"
                           ng-model="form.txt_sidebar_group"
                           placeholder="Optional — e.g. main, settings">
                    <div class="form-text text-muted">Optional grouping label for the sidebar (main menus only).</div>
                </div>

                <div class="mb-3" ng-if="form.relation == 1">
                    <label class="form-label fw-semibold">Parent</label>
                    <select class="form-select"
                            name="int_parent"
                            ng-model="form.int_parent"
                            ng-options="p.id as p.name for p in dropdowns.int_parent_ids | filter:menuParentRowFilter track by p.id"
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
