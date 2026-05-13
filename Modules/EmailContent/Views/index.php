<div id="page-content" class="px-4 py-4">
    <?php
    use Library\DataView;
    use Authentication\Perm_Auth;
    use Authentication\Session;

    $returned = Session::get('returned') ?? 0;
    $perm = Perm_Auth::getPermissions();

    // Modern Header Section
    echo '<div class="d-flex align-items-center justify-content-between mb-4">';
    echo '<div><h3 class="fw-bold text-main mb-0">' . trans($this->title) . '</h3>';
    echo '<p class="text-muted small mb-0">Manage system email templates and content</p></div>';

    echo '<div ng-controller="formController" class="d-flex gap-2" ng-init="buttons=' . sizeof($this->buttons) . '; return_value=' . $returned . '" ng-show="buttons > 0">';
    foreach ($this->buttons as $button) {
        if ($perm->verifyPermission(strtolower($button['action']))) {
            $action = "'" . $button['action'] . "'";
            echo '<button ng-click="showForm(' . $button['url'] . ', ' . $action . ')" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center gap-2">'
                . '<i class="fa fa-plus-circle"></i> ' . trans($button['name']) . '</button>';
        }
    }
    echo '</div></div>';

    $actions = [];
    if (sizeof($this->actions)) {
        foreach ($this->actions as $action) {
            if ($perm->verifyPermission(strtolower($action['action']))) {
                $actions[] = $action;
            }
        }
    }

    echo '<div ng-controller="profileController" ng-init="return_value=' . $returned . '">';
    
    if ($this->resultData['recordsFiltered'] > 0) {
        $view = new DataView();
        
        // Render modernized table
        echo $view->renderTable(
            $this->headings,
            $this->allRecords,
            $actions,
            $this->hidden,
            [
                'title' => 'Email Content List',
                'resultData' => $this->resultData,
                'postData' => $this->postData,
                'location' => $this->postData['location']
            ]
        );
    } else {
        echo '<div class="zt-card text-center py-5">';
        echo '<div class="opacity-25 mb-3"><i class="fa fa-envelope-open fa-4x"></i></div>';
        echo '<h4 class="fw-bold text-main">No Email Contents Available</h4>';
        echo '<p class="text-muted">Get started by creating your first email template.</p>';
        echo '</div>';
    }
    echo '</div>';
    ?>
</div>
