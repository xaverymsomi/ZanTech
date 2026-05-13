<div id="page-content">
    <div id="data_content"
         data-associated="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-headings="<?php echo htmlspecialchars(json_encode($this->table_headers, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-labels="<?php echo htmlspecialchars(json_encode($this->labels, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-actions="<?php echo htmlspecialchars(json_encode($this->actions, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <div class="row">
            <div class="col-md-12">
                <div style="max-height: 50vh;" class="scrolled-div">
                    <div ng-if=" '<?php echo $this->caller ?>' == 'Areas' ">
                        <?php include 'user_areas.php' ?>
                    </div>
                </div>
                <div class="panel panel-default" ng-if="associated_records.length === 0">
                    <div class="panel-body">
                        <h5 class="text-info" style="font-size: 1.5em; opacity: 60%">
                            No <?php echo strtolower($this->caller) ?> available
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
