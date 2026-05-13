<div id="page-content">
    <div id="data_content" data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>" data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>" data-dual-ativity-page=""></div>
    <div id="display_content">
        <form name="dual_activity" ng-submit="saveProfileOperation('DualActivity', 'post_edit')" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #030000, rgba(3, 3, 3, 0.95), #00AEEF); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-edit pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Model</label>
                        <div class="col-lg-9">
                            <label class="form-control"><?php echo $this->model ?></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Action</label>
                        <div class="col-lg-9">
                            <label class="form-control"><?php echo $this->actionname ?></label>
                        </div>
                    </div>

                    <?php
                    include 'forms/dual_activity.html';
                    ?>

                    <div class="form-group">
                        <label for="opt_mx_group_id" class="col-lg-12"> GROUPS CONFIGURATION BY INSTITUTION</label>
                        <div class="col-lg-12">
                            <div class="well" style="max-height: 300px; overflow-y: auto;">
                                <div class="row" id="dual_activity_section">
                                    <?php
                                    foreach ($this->councils_groups as $council) {
                                        if (count($council['groups']) >= 0) {
                                    ?>
                                            <div class="col-md-4">
                                                <table class="table table-condensed">
                                                    <tr>
                                                        <th class="col-md-6"><?php echo $council['name'] ?></th>
                                                        <td class="col-md-6">
                                                            <select name="opt_mx_group_id[]" class="form-control" data-council="<?php echo $council['id'] ?>">
                                                                <option value="">Select Group</option>
                                                                <?php
                                                                foreach ($council['groups'] as $group) {
                                                                    echo '<option value="' . $group['id'] . '"';
                                                                    if ($group['selected'] > 0) {
                                                                        echo ' selected="selected"';
                                                                    }
                                                                    echo '>' . $group['name'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                    <button type="submit" ng-disabled="dual_activity.$invalid || ProcessingData === true" class="btn btn-info" name="submit"> Submit </button>
                    <button ng-disabled="ProcessingData === true" ng-click="cancel()" type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
        </form>
    </div>
</div>
