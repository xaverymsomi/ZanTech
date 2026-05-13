<div id="page-content" class="zt-menu-admin p-3 p-lg-4">
    <div ng-controller="menuManageCtrl">

        <header class="zt-menu-admin__head mb-4">
            <div class="zt-menu-admin__head-text">
                <p class="zt-menu-admin__eyebrow mb-1">Configuration</p>
                <h1 class="zt-menu-admin__title h3 mb-2">Menu builder</h1>
                <p class="zt-menu-admin__lede text-muted mb-0">
                    Create top-level sidebar entries and nested items. Order and links apply across the app shell.
                </p>
            </div>
            <div class="zt-menu-admin__head-meta d-none d-md-flex align-items-center gap-2">
                <span class="badge rounded-pill zt-menu-admin__badge-soft">mx_menu</span>
                <span class="text-muted small" ng-if="dropdowns.all_menus.length">
                    {{ dropdowns.all_menus.length }} root<span ng-if="dropdowns.all_menus.length !== 1">s</span>
                </span>
            </div>
        </header>

        <div class="row g-4 align-items-stretch"
             ng-init='getMenuDropdowns(<?php
                echo json_encode(
                    $this->dropdowns,
                    JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_THROW_ON_ERROR
                );
             ?>); getAllMenus();'>

            <!-- Create form -->
            <div class="col-lg-5">
                <div class="zt-card h-100 zt-menu-admin__panel">
                    <div class="zt-menu-admin__panel-head">
                        <div class="zt-menu-admin__panel-icon zt-menu-admin__panel-icon--create">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="h5 mb-0">New entry</h2>
                            <p class="text-muted small mb-0">Add a main menu or a submenu under an existing root.</p>
                        </div>
                    </div>

                    <form id="new_menu" name="new_menu" novalidate class="zt-menu-admin__form">

                        <div class="mb-3">
                            <label class="form-label zt-menu-admin__label" for="menu_new_name">Name</label>
                            <input id="menu_new_name" type="text"
                                   placeholder="e.g. Utility"
                                   name="txt_name"
                                   class="form-control form-control-lg shadow-sm"
                                   ng-model="new_menu_form.txt_name"
                                   ng-class="{'is-invalid': new_menu.txt_name.$invalid && new_menu.$submitted}"
                                   required />
                            <div class="invalid-feedback">Menu name is required.</div>
                        </div>

                        <div class="mb-4">
                            <span class="form-label zt-menu-admin__label d-block mb-2">Type</span>
                            <div class="btn-group w-100 shadow-sm" role="group" aria-label="Menu type">
                                <input type="radio" class="btn-check" name="menu_relation_type" id="menu_rel_main"
                                       autocomplete="off" ng-model="new_menu_form.relation" ng-value="0">
                                <label class="btn btn-outline-secondary zt-menu-admin__type-btn" for="menu_rel_main">
                                    <i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>Main
                                </label>
                                <input type="radio" class="btn-check" name="menu_relation_type" id="menu_rel_sub"
                                       autocomplete="off" ng-model="new_menu_form.relation" ng-value="1">
                                <label class="btn btn-outline-secondary zt-menu-admin__type-btn" for="menu_rel_sub">
                                    <i class="fa-solid fa-indent me-2" aria-hidden="true"></i>Sub
                                </label>
                            </div>
                            <p class="form-text text-muted small mb-0 mt-2">Main items drive the sidebar; subs nest under one main.</p>
                        </div>

                        <div class="mb-3" ng-if="new_menu_form.relation == 0">
                            <label class="form-label zt-menu-admin__label" for="menu_new_icon">Icon</label>
                            <div class="input-group input-group-lg shadow-sm zt-menu-admin__icon-group">
                                <span class="input-group-text zt-menu-admin__icon-addon" id="menu_icon_addon">
                                    <i ng-class="iconPreviewClasses(new_menu_form.txt_icon)" aria-hidden="true"></i>
                                </span>
                                <input id="menu_new_icon" type="text"
                                       placeholder="tools · fa-tools · fa-solid fa-user"
                                       name="txt_icon"
                                       class="form-control"
                                       ng-model="new_menu_form.txt_icon"
                                       aria-describedby="menu_icon_addon" />
                            </div>
                            <p class="form-text text-muted small mb-0">Font Awesome short name, legacy token, or full class list.</p>
                        </div>

                        <div class="mb-3" ng-if="new_menu_form.relation == 0">
                            <label class="form-label zt-menu-admin__label" for="menu_new_group">Sidebar group</label>
                            <input id="menu_new_group" type="text"
                                   class="form-control shadow-sm"
                                   name="txt_sidebar_group"
                                   placeholder="Optional — e.g. main, settings"
                                   ng-model="new_menu_form.txt_sidebar_group" />
                        </div>

                        <div class="mb-3" ng-if="new_menu_form.relation == 1">
                            <label class="form-label zt-menu-admin__label" for="menu_new_parent">Parent main menu</label>
                            <select id="menu_new_parent" class="form-select form-select-lg shadow-sm"
                                    name="int_parent"
                                    ng-model="new_menu_form.int_parent"
                                    ng-options="p.id as p.name for p in dropdowns.int_parent_ids track by p.id"
                                    required>
                                <option value="">Choose parent…</option>
                            </select>
                        </div>

                        <div class="mb-3" ng-if="new_menu_form.relation == 0">
                            <label class="form-label zt-menu-admin__label" for="menu_new_pos">Position</label>
                            <input id="menu_new_pos" type="number"
                                   placeholder="1"
                                   name="int_position"
                                   class="form-control form-control-lg shadow-sm"
                                   ng-model="new_menu_form.int_position"
                                   min="1"
                                   required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label zt-menu-admin__label" for="menu_new_link">Route link</label>
                            <input id="menu_new_link" type="text"
                                   placeholder="/Menu/index or #"
                                   name="txt_link"
                                   class="form-control shadow-sm font-monospace"
                                   ng-model="new_menu_form.txt_link" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label zt-menu-admin__label" for="menu_new_title">Page title</label>
                            <input id="menu_new_title" type="text"
                                   placeholder="Shown in the tab / header"
                                   name="txt_title"
                                   class="form-control shadow-sm"
                                   ng-model="new_menu_form.txt_title"
                                   ng-class="{'is-invalid': new_menu.txt_title.$invalid && new_menu.$submitted}"
                                   required />
                            <div class="invalid-feedback">Title is required.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit"
                                    class="btn btn-lg btn-premium"
                                    ng-click="new_menu.$setSubmitted(); saveMenu();"
                                    ng-disabled="new_menu.$invalid || savingMenu">
                                <span ng-if="!savingMenu"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Save menu</span>
                                <span ng-if="savingMenu"><i class="fa-solid fa-spinner fa-spin me-2" aria-hidden="true"></i>Saving…</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Tree -->
            <div class="col-lg-7">
                <div class="zt-card zt-table-card h-100 zt-menu-admin__panel zt-menu-admin__panel--table">
                    <div class="zt-table-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="zt-menu-admin__panel-icon zt-menu-admin__panel-icon--tree d-none d-sm-flex">
                                <i class="fa-solid fa-diagram-project" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Structure</h2>
                                <p class="text-muted small mb-0">Live hierarchy from the database.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-soft-primary btn-sm rounded-pill px-3"
                                ng-click="getAllMenus()"
                                ng-disabled="loadingMenus">
                            <i class="fa-solid me-1" ng-class="loadingMenus ? 'fa-spinner fa-spin' : 'fa-arrows-rotate'" aria-hidden="true"></i>
                            Refresh
                        </button>
                    </div>

                    <div class="zt-menu-admin__table-wrap">
                        <table class="zt-table mb-0 zt-menu-admin__table">
                            <thead>
                                <tr>
                                    <th scope="col">Menu</th>
                                    <th scope="col" class="d-none d-md-table-cell">Group</th>
                                    <th scope="col" class="d-none d-lg-table-cell">Link</th>
                                    <th scope="col" class="text-center" style="width:5.5rem;">Order</th>
                                    <th scope="col" class="text-center" style="width:5rem;"></th>
                                </tr>
                            </thead>
                            <tbody ng-if="!dropdowns.all_menus || !dropdowns.all_menus.length">
                                <tr class="zt-menu-admin__empty-row">
                                    <td colspan="5" class="text-center py-5">
                                        <div class="zt-menu-admin__empty">
                                            <i class="fa-solid fa-folder-open fa-2x text-muted mb-3 d-block opacity-50" aria-hidden="true"></i>
                                            <p class="text-muted mb-1 fw-semibold">No menus yet</p>
                                            <p class="text-muted small mb-0">Save your first main menu, then refresh if needed.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody ng-repeat="parent in dropdowns.all_menus track by parent.id">
                                <tr class="zt-menu-admin__row zt-menu-admin__row--parent">
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="zt-menu-admin__tree-icon text-primary" aria-hidden="true">
                                                <i ng-class="iconPreviewClasses(parent.txt_icon)"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="fw-semibold d-block text-truncate">{{ parent.txt_name }}</span>
                                                <small class="text-muted text-truncate d-block">{{ parent.txt_title }} · {{ parent.txt_link || '#' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell text-muted small">
                                        {{ parent.txt_sidebar_group || '—' }}
                                    </td>
                                    <td class="d-none d-lg-table-cell small font-monospace text-break text-muted">
                                        {{ parent.txt_link || '#' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="zt-menu-admin__pos">{{ parent.int_position }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-soft-primary rounded-pill px-2"
                                                ng-click="showActionForm(parent.txt_row_value, 'Menu', 'edit')"
                                                title="Edit">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="zt-menu-admin__row zt-menu-admin__row--child"
                                    ng-repeat="child in parent.children track by child.id">
                                    <td>
                                        <div class="d-flex align-items-start gap-2 ps-md-4">
                                            <span class="zt-menu-admin__child-mark text-muted" aria-hidden="true">
                                                <i class="fa-solid fa-turn-down fa-rotate-90 small"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="fw-medium d-block text-truncate">{{ child.txt_name }}</span>
                                                <small class="text-muted text-truncate d-block">{{ child.txt_title }} · {{ child.txt_link || '#' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell text-muted small">—</td>
                                    <td class="d-none d-lg-table-cell small font-monospace text-break text-muted">
                                        {{ child.txt_link || '#' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="zt-menu-admin__pos zt-menu-admin__pos--sub">{{ child.int_position }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary rounded-pill px-2"
                                                ng-click="showActionForm(child.txt_row_value, 'Menu', 'edit')"
                                                title="Edit">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
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
