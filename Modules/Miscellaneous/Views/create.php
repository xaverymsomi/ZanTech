<div id="page-content">
    <div id="data_content" 
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <div class="modal-header" style="background: rgb(54,181,59);background: linear-gradient(87deg, rgba(54,181,59,1) 19%, rgba(0,0,0,1) 61%, rgba(17,125,202,1) 94%, rgba(255,220,0,1) 100%); color: white;">
            <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
            <h4 class="modal-title ocean"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
        </div>

        <form name="rule" ng-submit="saveConfiguration()" novalidate >
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="col-lg-9" style="margin: 0 auto; float: none">
                        <select name="int_mx_rule_id" id="int_mx_rule_id" class="form-control"
                                ng-model="form.int_mx_rule_id" required
                                ng-class="rule.int_mx_rule_id.$invalid && !rule.int_mx_rule_id.$pristine"
                                ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_rule_ids">
                            <option value="">Select Rule</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
