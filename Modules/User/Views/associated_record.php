<div id="page-content">
    <div id="data_content" 
        data-associated="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-headings="<?php echo htmlspecialchars(json_encode($this->table_headers,JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-labels="<?php echo htmlspecialchars(json_encode($this->labels,JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-actions="<?php echo htmlspecialchars(json_encode($this->actions, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"></div>
    <div id="display_content">
        <div class="row">
            <div class="col-md-12">
                <?php if ($this->show_cards) { ?>
                <div ng-if="associated_records.length > 0"> 
                    <div class="col-md-4" ng-repeat="record in associated_records">
                        <div class="account-{{record['channel_id']}} col-md-12" style="margin-bottom: 10px;">
                            <span class="text-centered"><h3 style="text-align: center; color: #fff;">{{record['Account Name']}}</h3></span>
                            <span><h4 style="text-align: center; color:#fff; font-weight: 800;">Current Balance </h4></span>
                            <span><h2 style="text-align: center; font-size: 40px; font-weight: 300; color:#fff; margin-top: -15px;">{{record['Current Balance']| number:2}}</h2></span>
                            <span><h5 style="text-align: center; font-weight: 900; color:#000"> Last Updated: {{record['Last Updated']}} </h5></span>
                        </div>
                    </div>
                </div>
                <?php } else {?>
                <div  style="max-height: 50vh;" class="scrolled-div">
                    <table class="table table-striped table-condensed table-hover table-bordered">
                        <thead>
                            <tr class="bottom-border-color-orange primary">
                                <th colspan="{{table_headers.length}}" style="font-size: 12pt; color:#f26e09; text-transform: uppercase;"><?php echo $this->caller ?></th>
                            </tr>
                        </thead>
                        <thead class="thead-red" ng-if="associated_records.length > 0">
                            <tr>
                                <th ng-repeat="header in table_headers track by $index" ng-if="header != 'id' && header != 'row_id'">{{header}}</th>
                                <th ng-if="associated_actions.length > 0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="record in associated_records">
                                <td ng-repeat="(key, value) in record" ng-if="key != 'id' && key != 'row_id'">
                                    <span ng-class="{
                                        'label label-success' : labels.success.indexOf(value) > -1, 
                                        'label label-danger' : labels.danger.indexOf(value) > -1,
                                        'label label-warning' : labels.warning.indexOf(value) > -1,
                                        'label label-default' : labels.default.indexOf(value) > -1
                                    }">{{value}}</span>
                                </td>
                                <td ng-if="associated_actions.length > 0" class="text-center">
                                    <a ng-repeat="action in associated_actions" href="#" ng-click="showProfileActionForm(action.caller, action.action, [initial_tab_data.row_id, record.row_id])" class="{{action.cssclass}} associated_records_action" ng-class="{disabledLink : {{action.disabled}}}"><i class="{{action.icon}}" title="{{action.title}}"></i></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
                <p ng-if="associated_records.length === 0" class="text-info">No <?php echo strtolower($this->caller) ?> available</p>
            </div>
        </div>
    </div>
</div>
