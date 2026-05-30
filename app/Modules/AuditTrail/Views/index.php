<div id="page-content" class="px-4 py-4">
    <span id="progress" style="visibility:hidden;">
        <div class="lds-ripple"><div></div><div></div></div>
    </span>

    <?php
    use Authentication\Perm_Auth;
    use Authentication\Session;
    use View\DataView;

    $perm = Perm_Auth::getPermissions();
    $returned = Session::get('returned') ?? 0;

    echo '<div class="d-flex align-items-center justify-content-between mb-4">';
    echo '<div><h3 class="fw-bold text-main mb-0">' . htmlspecialchars(trans($this->title), ENT_QUOTES, 'UTF-8') . '</h3>';
    echo '<p class="text-muted small mb-0">View system activity logs and audit trails</p></div>';
    echo '</div>';

    $actions = [];
    if (sizeof($this->actions)) {
        foreach ($this->actions as $action) {
            if ($perm->verifyPermission(strtolower($action['action']))) {
                $actions[] = $action;
            }
        }
    }

    echo '<div ng-controller="profileController" ng-init="return_value=' . (int)$returned . '">';
    
    if ($this->resultData['recordsFiltered'] > 0) {
        echo '<div class="zt-card mb-4 p-3">';
        echo '<mabrex-filter mx-selected="' . htmlspecialchars($this->postData['length'], ENT_QUOTES, 'UTF-8') . '" mx-location="\'' .
        htmlspecialchars($this->postData['location'], ENT_QUOTES, 'UTF-8') . '\'" mx-title="\'' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '\'" mx-current-link="\'' .
        htmlspecialchars($this->postData['current'], ENT_QUOTES, 'UTF-8') . '\'" mx-page-size="\'' . htmlspecialchars($this->postData['length'], ENT_QUOTES, 'UTF-8') . '\'" mx-search-term="\'' .
        htmlspecialchars($this->postData['search'], ENT_QUOTES, 'UTF-8') . '\'" mx-total-records="' . htmlspecialchars($this->resultData['recordsTotal'], ENT_QUOTES, 'UTF-8') . '" mx-table-columns="' .
        htmlspecialchars($this->resultData['columns'], ENT_QUOTES, 'UTF-8') . '" mx-sort-column="\'' . htmlspecialchars($this->postData['order_column'], ENT_QUOTES, 'UTF-8') . '\'" mx-sort-order="\'' .
        htmlspecialchars($this->postData['order_dir'], ENT_QUOTES, 'UTF-8') . '\'" mx-column-label="\'' . htmlspecialchars($this->resultData['column_label'], ENT_QUOTES, 'UTF-8') . '\'"></mabrex-filter>';
        echo '</div>';

        $view = new DataView();
        echo $view->renderTable($this->headings, $this->allRecords, $actions, $this->hidden);

        echo '<div class="mt-4">';
        echo '<mabrex-pager mx-filtered="' . htmlspecialchars($this->resultData['recordsFiltered'], ENT_QUOTES, 'UTF-8') . '" mx-total="' .
        htmlspecialchars($this->resultData['recordsTotal'], ENT_QUOTES, 'UTF-8') . '" mx-current-page="' . htmlspecialchars($this->resultData['currentPage'], ENT_QUOTES, 'UTF-8') . '" mx-pages="' .
        htmlspecialchars($this->resultData['totalPages'], ENT_QUOTES, 'UTF-8') . '" mx-page-buttons="10" mx-page-location="\'' .
        htmlspecialchars($this->postData['location'], ENT_QUOTES, 'UTF-8') . '\'" mx-page-title="\'' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '\'" mx-page-current-link="\'' .
        htmlspecialchars($this->postData['current'], ENT_QUOTES, 'UTF-8') . '\'" mx-page-size="\'' . htmlspecialchars($this->postData['length'], ENT_QUOTES, 'UTF-8') . '\'" mx-page-search-term="\'' .
        htmlspecialchars($this->postData['search'], ENT_QUOTES, 'UTF-8') . '\'" mx-returned="' . htmlspecialchars($this->resultData['recordsReturned'], ENT_QUOTES, 'UTF-8') . '" mx-sort-column="\'' .
        htmlspecialchars($this->postData['order_column'], ENT_QUOTES, 'UTF-8') . '\'" mx-sort-order="\'' . htmlspecialchars($this->postData['order_dir'], ENT_QUOTES, 'UTF-8') . '\'"></mabrex-pager>';
        echo '</div>';
    } else {
        echo '<div class="zt-card p-5 text-center">';
        echo '<div class="mb-4 text-muted opacity-25"><i class="fa fa-folder-open fa-5x"></i></div>';
        echo '<h4 class="fw-bold text-main">No records found</h4>';
        echo '<p class="text-muted">There are currently no audit trails matching your criteria.</p>';
        echo '</div>';
    }
    echo '</div>';
    ?>
</div>
