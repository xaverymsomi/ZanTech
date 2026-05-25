<div id="page-content">
    <div id="data_content" 
        data-initial="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-tabs="<?php echo htmlspecialchars(json_encode($this->tabs,JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-hidden-columns="<?php echo htmlspecialchars(json_encode($this->hidden_columns,JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"></div>
    <div id="display_content">
        <div ng-controller="profileController">
            <div class="modal-header" style="background-color:#480190; color: white;">
                <button ng-click="cancel()" type="button" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean text-capitalize"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i> <?php echo $this->title ?></h4>        
            </div>
            <div class="modal-body" style="padding:25px;">
                <div style="margin-bottom: 15px;">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a data-toggle="tab" href="#User" ng-click="getProfileRecords('User', '<?php echo $this->data['row_id'] ?>')">User</a>
                        </li>
                        <li ng-repeat="tab in tabs">
                            <a data-toggle="tab" href="#{{tab}}" ng-click="getAssociatedRecords(tab, initial_tab_data.row_id)">{{tab.replace('_', ' ')}}</a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="row" style="margin-bottom: 15px;">
                        <?php if (count($this->buttons) > 0) { ?>
                            <div class="text-center">
                                <div class="profile-buttons-group">
                                    <?php
                                    foreach ($this->buttons as $button) {
                                        $params = '';
                                        echo '<button ng-disabled="' . $button['disabled'] . '" class="' . $button['cssclass'] . '" ng-click="showProfileActionForm(\'' . $button['controller'] . '\',\'' . $button['action'] . '\',[' . $button['params'] . '])" ng-show="' . $button['show'] . '">' . $button['label'] . '</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="tab-pane fade active in" id="User">
                        <div class="row profile_section">
                            <div class="col col-md-6">
                                <table class="table table-condensed table-striped table-bordered table-responsive">
                                    <thead>
                                        <tr class="bottom-border-color-green primary">
                                            <th colspan="2">USER DETAILS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr ng-repeat="(key, value) in initial_tab_data" ng-if="hidden_columns.indexOf(key) < 0 && key !== 'row_id'">
                                            <th class="blue">{{key}}</th>
                                            <td class="text-right">{{value}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div  ng-repeat="tab in tabs" class="tab-pane fade" id="{{tab}}">
                        <div class="associated_section">
                            <!-- ASSOCIATED RECORDS TO BE LOADED HERE -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn rah-red" ng-click="cancel()">Cancel</button>
            </div>
        </div>
    </div>
</div>

