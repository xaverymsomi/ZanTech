<div id="page-content">
    <div id="data_content" 
         data-initial="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-tabs="<?php echo htmlspecialchars(json_encode($this->tabs, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-hidden-columns="<?php echo htmlspecialchars(json_encode($this->hidden_columns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <div ng-controller="profileController">
            <div class="modal-header" style="background-color:<?php echo $this->primary_color; ?>; color: white;">
                <button ng-click="cancel()" type="button" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-phone pe-fw pe-va pe-2x"></i> <?php echo $this->title ?></h4>        
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 15px;">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a data-toggle="tab" href="#EmailContent" ng-click="getProfileRecords('EmailContent', '<?php echo $this->data['row_id'] ?>')">SMS Template</a>
                        </li>
                        <li ng-repeat="tab in tabs">
                            <a data-toggle="tab" href="#{{tab}}" ng-click="getAssociatedRecords(tab, initial_tab_data.id)"> {{tab.replace('_', ' ')}}  </a>
                        </li>          
                    </ul>
                </div>

                <div class="tab-content">
                    <div class="tab-pane fade active in" id="EmailContent">
                        <div class="row profile_section">
                            <div class="col col-md-6">
                                <table class="table table-condensed table-striped table-bordered table-responsive">
                                    <thead>
                                        <tr class="bottom-border-color-green primary">
                                            <th colspan="2">Email Content</th>
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

                    <div ng-repeat="tab in tabs" class="tab-pane fade" id="{{tab}}">
                        <div class="associated_section">
                            <!-- ASSOCIATED RECORDS TO BE LOADED HERE -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" ng-click="cancel()">Cancel</button>
            </div>

        </div>
    </div>
</div>


