<div id="page-content" class="zt-menu-admin-modal">

    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode($this->data ?? [], JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8'); ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns ?? [], JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8'); ?>">
    </div>

    <div id="display_content">

        <div class="modal-header border-0 pb-0 zt-menu-admin-modal__header">
            <div class="d-flex align-items-center gap-3">
                <div class="zt-menu-admin__panel-icon zt-menu-admin__panel-icon--create zt-menu-admin-modal__head-icon">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 class="modal-title h5 mb-0">
                        <?php echo htmlspecialchars($this->title ?? 'Edit menu', ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <p class="text-muted small mb-0">Update navigation metadata and ordering.</p>
                </div>
            </div>
            <button type="button" class="btn-close" aria-label="Close" ng-click="cancel()"></button>
        </div>

        <div class="modal-body pt-3">
            <div class="notification-area mb-3 rounded-3"></div>

            <form name="menu" novalidate class="zt-menu-admin__form">

                <input type="hidden" name="id" ng-model="form.id">

                <div class="mb-3">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_name">Name</label>
                    <input id="menu_edit_name" type="text"
                           class="form-control shadow-sm"
                           ng-model="form.txt_name"
                           ng-class="{'is-invalid': menu.txt_name.$invalid && menu.$submitted}"
                           name="txt_name"
                           required>
                    <div class="invalid-feedback">Name is required.</div>
                </div>

                <div class="mb-4">
                    <span class="form-label zt-menu-admin__label d-block mb-2">Type</span>
                    <div class="btn-group w-100 shadow-sm" role="group" aria-label="Menu type">
                        <input type="radio" class="btn-check" name="menu_edit_relation" id="menu_edit_rel_main"
                               autocomplete="off" ng-model="form.relation" ng-value="0">
                        <label class="btn btn-outline-secondary zt-menu-admin__type-btn" for="menu_edit_rel_main">
                            <i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>Main
                        </label>
                        <input type="radio" class="btn-check" name="menu_edit_relation" id="menu_edit_rel_sub"
                               autocomplete="off" ng-model="form.relation" ng-value="1">
                        <label class="btn btn-outline-secondary zt-menu-admin__type-btn" for="menu_edit_rel_sub">
                            <i class="fa-solid fa-indent me-2" aria-hidden="true"></i>Sub
                        </label>
                    </div>
                </div>

                <div class="mb-3" ng-if="form.relation == 0">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_icon">Icon</label>
                    <div class="input-group shadow-sm zt-menu-admin__icon-group">
                        <span class="input-group-text zt-menu-admin__icon-addon" id="menu_edit_icon_addon">
                            <i ng-class="iconPreviewClasses(form.txt_icon)" aria-hidden="true"></i>
                        </span>
                        <input id="menu_edit_icon" type="text" class="form-control" ng-model="form.txt_icon"
                               placeholder="tools, fa-tools, fa-solid fa-user"
                               aria-describedby="menu_edit_icon_addon">
                    </div>
                </div>

                <div class="mb-3" ng-if="form.relation == 0">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_group">Sidebar group</label>
                    <input id="menu_edit_group" type="text" class="form-control shadow-sm" name="txt_sidebar_group"
                           ng-model="form.txt_sidebar_group"
                           placeholder="Optional — e.g. main, settings">
                    <p class="form-text text-muted small mb-0">Main menus only.</p>
                </div>

                <div class="mb-3" ng-if="form.relation == 1">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_parent">Parent</label>
                    <select id="menu_edit_parent" class="form-select shadow-sm"
                            name="int_parent"
                            ng-model="form.int_parent"
                            ng-options="p.id as p.name for p in dropdowns.int_parent_ids | filter:menuParentRowFilter track by p.id"
                            ng-class="{'is-invalid': menu.int_parent.$invalid && menu.$submitted}"
                            required>
                        <option value="">Choose parent…</option>
                    </select>
                    <div class="invalid-feedback">Parent is required for a submenu.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_pos">Position</label>
                    <input id="menu_edit_pos" type="number" min="1" class="form-control shadow-sm" ng-model="form.int_position" name="int_position">
                </div>

                <div class="mb-3">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_link">Link</label>
                    <input id="menu_edit_link" type="text" class="form-control shadow-sm font-monospace" ng-model="form.txt_link" name="txt_link"
                           placeholder="/Menu/index or #">
                </div>

                <div class="mb-0">
                    <label class="form-label zt-menu-admin__label" for="menu_edit_title">Title</label>
                    <input id="menu_edit_title" type="text"
                           class="form-control shadow-sm"
                           ng-model="form.txt_title"
                           ng-class="{'is-invalid': menu.txt_title.$invalid && menu.$submitted}"
                           name="txt_title"
                           required>
                    <div class="invalid-feedback">Title is required.</div>
                </div>

            </form>
        </div>

        <div class="modal-footer border-0 pt-0 bg-transparent zt-menu-admin-modal__footer">
            <span class="text-muted me-auto small" ng-if="ProcessingData === true">
                <i class="fa-solid fa-spinner fa-spin me-1" aria-hidden="true"></i> Saving…
            </span>

            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" ng-click="cancel()" ng-disabled="ProcessingData === true">
                Cancel
            </button>

            <button type="button"
                    class="btn btn-premium rounded-pill px-4"
                    ng-click="menu.$setSubmitted(); saveProfileOperation('Menu', 'post_edit')"
                    ng-disabled="menu.$invalid || ProcessingData === true">
                <i class="fa-solid fa-check me-2" aria-hidden="true"></i>Save changes
            </button>
        </div>

    </div>
</div>
