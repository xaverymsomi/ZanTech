<table class="table table-striped table-condensed table-hover table-bordered">
    <thead>
    <tr class="bottom-border-color-orange primary">
        <th colspan="{{table_headers.length}}" style="font-size: 12pt; color:#f26e09; text-transform: uppercase;">User Areas</th>
    </tr>
    </thead>
    <thead class="thead-red" ng-if="associated_records.length > 0">
    <tr>
        <th ng-repeat="header in table_headers track by $index" ng-if="['id', 'Id', 'row_id', 'status_id'].indexOf(header) < 0">{{header}}</th>
        <th ng-if="associated_actions.length > 0">Actions</th>
    </tr>
    </thead>
    <tbody>
    <tr ng-repeat="record in associated_records">
        <td ng-repeat="(key, value) in record" ng-if="['id', 'Id', 'row_id', 'status_id'].indexOf(key) < 0">
                                    <span ng-class="{
                                        'label label-success' : labels.success.indexOf(value) > -1,
                                        'label label-danger' : labels.danger.indexOf(value) > -1,
                                        'label label-warning' : labels.warning.indexOf(value) > -1,
                                        'label label-default' : labels.default.indexOf(value) > -1
                                       }">{{value}}</span>
        </td>
        <td ng-if="associated_actions.length > 0" class="text-center">
            <a ng-repeat="action in associated_actions" href="#" ng-click="showProfileActionForm(action.caller,
            action.action, [initial_tab_data.row_id, record.row_id])" class="{{action.cssclass}} associated_records_action"
               ng-class="{disabledLink : {{action.disabled}} }"><i class="{{action.icon}}" title="{{action.title}}"></i></a>
            {{action.disabled}}
        </td>
    </tr>
    </tbody>
</table>

