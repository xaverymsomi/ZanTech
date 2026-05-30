<div id="page-content" class="px-4 py-4 zt-animate-fade-in">
    <?php
    use Authentication\Gate;
    use Authentication\Session;
    use View\DataView;

    $returned = Session::get('returned') ?? 0;

    // Modern Header Section
    echo '<div class="d-flex align-items-center justify-content-between mb-4">';
    echo '<div><h3 class="fw-bold text-main mb-0">' . trans($this->title) . '</h3>';
    echo '<p class="text-muted small mb-0">Review and authorize pending system activities</p></div>';

    echo '<div ng-controller="formController" class="d-flex gap-2" ng-init="buttons=' . sizeof($this->buttons) . '; return_value=' . $returned . '" ng-show="buttons > 0">';
    foreach ($this->buttons as $button) {
        if ($perm->verifyPermission(strtolower($button['action']))) {
            $action = "'" . $button['action'] . "'";
            $colorClass = $button['color'] == 'primary' ? 'btn-premium' : 'btn-soft-' . $button['color'];
            echo '<button ng-click="showForm(' . $button['url'] . ', ' . $action . ')" class="btn ' . $colorClass . ' rounded-pill px-4 shadow-sm fw-bold">'
                . trans($button['action']) . '</button>';
        }
    }
    echo '</div></div>';

    $actions = [];
    if (sizeof($this->actions)) {
        foreach ($this->actions as $action) {
            if (Gate::allows(strtolower($action['action']))) {
                $actions[] = $action;
            }
        }
    }
    
    echo '<div ng-controller="profileController" ng-init="return_value=' . $returned . '">';
    
    if ($this->resultData['recordsFiltered'] > 0) {
        // Modern Filter Wrapper
        echo '<div class="zt-card mb-4 p-3">';
        echo '<mabrex-filter mx-selected="' . $this->postData['length'] . '" mx-location="\'' .
        $this->postData['location'] . '\'" mx-title="\'' . $this->title . '\'" mx-current-link="\'' .
        $this->postData['current'] . '\'" mx-page-size="\'' . $this->postData['length'] . '\'" mx-search-term="\'' .
        $this->postData['search'] . '\'" mx-total-records="' . $this->resultData['recordsTotal'] . '" mx-table-columns="' .
        $this->resultData['columns'] . '" mx-sort-column="\'' . $this->postData['order_column'] . '\'" mx-sort-order="\'' .
        $this->postData['order_dir'] . '\'" mx-column-label="\'' . $this->resultData['column_label'] . '\'"></mabrex-filter>';
        echo '</div>';

        $view = new DataView();
        // Standardized rendering with new engine
        echo $view->renderTable($this->headings, $this->allRecords, $actions, $this->hidden);
        
        // Modern Pager
        echo '<div class="mt-4">';
        echo '<mabrex-pager mx-filtered="' . $this->resultData['recordsFiltered'] . '" mx-total="' .
        $this->resultData['recordsTotal'] . '" mx-current-page="' . $this->resultData['currentPage'] . '" mx-pages="' .
        $this->resultData['totalPages'] . '" mx-page-buttons="10" mx-page-location="\'' .
        $this->postData['location'] . '\'" mx-page-title="\'' . $this->title . '\'" mx-page-current-link="\'' .
        $this->postData['current'] . '\'" mx-page-size="\'' . $this->postData['length'] . '\'" mx-page-search-term="\'' .
        $this->postData['search'] . '\'" mx-returned="' . $this->resultData['recordsReturned'] . '" mx-sort-column="\'' .
        $this->postData['order_column'] . '\'" mx-sort-order="\'' . $this->postData['order_dir'] . '\'"></mabrex-pager>';
        echo '</div>';
    } else {
        echo '<div class="zt-card p-5 text-center">';
        echo '<div class="mb-4 text-muted opacity-25"><i class="fa fa-clipboard-check fa-5x"></i></div>';
        echo '<h4 class="fw-bold text-main">No Pending Activities</h4>';
        echo '<p class="text-muted">You are all caught up! There are no records requiring your attention at this time.</p>';
        echo '</div>';
    }
    echo '</div>';
    ?>
</div>

