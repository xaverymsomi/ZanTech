<div id="page-content" class="px-4 py-4">
    <?php
    use Authentication\Gate;
    use Authentication\Session;
    use View\DataView;

    $returned = Session::get('returned') ?? 0;
    
    // Modern Header Section
    echo '<div class="d-flex align-items-center justify-content-between mb-4">';
    echo '<div><h3 class="fw-bold text-main mb-0">' . htmlspecialchars(trans($this->title), ENT_QUOTES, 'UTF-8') . '</h3>';
    echo '<p class="text-muted small mb-0">Manage system SMS templates and automation</p></div>';

    echo '<div ng-controller="formController" class="d-flex gap-2" ng-init="buttons=' . (int)sizeof($this->buttons) . '; return_value=' . (int)$returned . '" ng-show="buttons > 0">';
    foreach ($this->buttons as $button) {
        if ($perm->verifyPermission(strtolower($button['action']))) {
            $action = "'" . htmlspecialchars($button['action'], ENT_QUOTES, 'UTF-8') . "'";
            echo '<button ng-click="showForm(' . htmlspecialchars($button['url'], ENT_QUOTES, 'UTF-8') . ', ' . $action . ')" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center gap-2">'
                . '<i class="fa fa-plus-circle"></i> ' . htmlspecialchars(trans($button['name']), ENT_QUOTES, 'UTF-8') . '</button>';
        }
    }
    echo '</div></div>';

    $actions = [];
    if (isset($this->actions) && sizeof($this->actions)) {
        foreach ($this->actions as $action) {
            if (Gate::allows(strtolower($action['action']))) {
                $actions[] = $action;
            }
        }
    }

    echo '<div ng-controller="profileController" ng-init="return_value=' . (int)$returned . '">';
    
    if (isset($this->resultData['recordsFiltered']) && $this->resultData['recordsFiltered'] > 0) {
        $view = new DataView();
        
        // Render modernized table
        echo $view->renderTable(
            $this->headings,
            $this->allRecords,
            $actions,
            $this->hidden ?? [],
            [
                'title' => 'SMS Template List',
                'resultData' => $this->resultData,
                'postData' => $this->postData,
                'location' => $this->postData['location']
            ]
        );
    } else {
        echo '<div class="zt-card text-center py-5">';
        echo '<div class="opacity-25 mb-3"><i class="fa fa-sms fa-4x"></i></div>';
        echo '<h4 class="fw-bold text-main">No SMS Templates Available</h4>';
        echo '<p class="text-muted">Create templates to automate your SMS communications.</p>';
        echo '</div>';
    }
    echo '</div>';
    ?>
</div>
