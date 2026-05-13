<div id="page-content">
    <!-- Controller -->
    <div ng-controller="permissionCtrl">
        <div class='container-fluid'>
            <ul class="nav nav-tabs">
                <li ng-class="{active: isActiveTab(1)}"><a href="#" ng-click="setActiveTab(1);">Group Permissions</a></li>
                <li ng-class="{active: isActiveTab(2)}"><a href="#" ng-click="setActiveTab(2);">User Permissions</a></li>
                <?php
                if ($_SESSION['role'] === '1') {
                    ?>
                    <li ng-class="{active: isActiveTab(3)}"><a href="#" ng-click="setActiveTab(3);">Permissions Management</a></li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <br>

        <!--Group Section Tab-->
        <div ng-show="isActiveTab(1);">
            <!-- Container 1 -->
            <div class="row">
                <!-- New Group Form -->
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="orange">Add New Group</h4>
                        </div>
                        <div class="panel-body">
                            <form class="form-horizontal" id="new_group" ng-submit="saveForm('Permission', 'saveGroup')">
                                <div class="form-group">
                                    <label for="name" class="col-md-3 control-label">Group Name</label>
                                    <div class="col-md-6">
                                        <input type="name" class="form-control" id="name" placeholder="Write Group Name" ng-class="new_group.name.$invalid && !new_group.name.$pristine" ng-model="form.name" required>
                                    </div>

                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-default orange" ng-disabled="new_group.$invalid" >Save Group</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End of New Group Form -->
                <!-- Assign Group Permission Form ng-init="getOptionData('group_id', 'mx_group', 'new_group_permission');"-->
                <div class="col-md-6" ng-init="getOptionData('group_id', 'mx_group', 'new_group_permission');">
                    <div ng-show="groups_data == 1" class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="blue">Grant Group Permission</h4>
                        </div>
                        <div class="panel-body">
                            <!-- Select Group Form -->
                            <form class="form-horizontal" id="new_group_permission">

                                <div class="form-group" >
                                    <label for="group_id" class="col-md-3 control-label">Group Name</label>
                                    <div class="col-md-9">
                                        <select class="form-control" id="group_id" name="group_id" ng-model="new_group_permission.group_id" ng-change="getGroupPermission(new_group_permission.group_id);" required>
                                            <option value=""> Select  Group</option>
                                            <!--user options comes from database-->
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <!-- End of Select Group Form -->
                            <!--Group Permission Table-->
                            <div class="table-responsive scrolled-div" ng-show="group_permission_flag" style="max-height: 65vh">
                                <table class="table table-striped table-hover table-condensed table-bordered" id="group_permission_table">
                                    <thead>
                                        <tr>
                                            <th colspan="2">Permissions</th>
                                            <th>Granted</th>
                                        </tr>
                                    </thead>
                                    <tbody ng-repeat="(key, value) in group_permissions | groupBy: 'section_name'">
                                        <tr style="background-color: #D5D8DC !important;">
                                            <th colspan="2">{{key}}</th>
                                            <th class="hide"><input type="text" ng-model="value.section_id"></th>
                                            <th><input type="checkbox" onclick="selectSectionPermission($(this));"></th>
                                        </tr>
                                        <tr ng-repeat="permission in value">
                                            <td style="width: 50px;">&nbsp;</td>
                                            <td>{{permission.permission_display_name}}</td>
                                            <td class="hide"><input type="text" ng-model="permission.permission_id"></td>
                                            <td><input type="checkbox" ng-model="permission.check"></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="col-md-6"><label><input type="checkbox" id="gp_check" name="gp_check"  ng-model="gp_check" required/> Done</label></div>
                                <div class="col-md-6"><button type="submit" ng-disabled="!gp_check" class="btn btn-primary" ng-click="saveTableData('group_permission_table', 'saveGroupPermission', new_group_permission.group_id);
                                            gp_check = false;">Save</button></div>
                            </div>
                            <!--End of Group Permission Table-->
                        </div>
                    </div>
                </div>
                <!--End of Assign Group Permission-->
            </div>
            <!-- End of Container 1 -->
        </div>
        <!-- End of Group Section Tab -->
        <!-- User Section Tab -->
        <div ng-show="isActiveTab(2);">
            <!-- Container 2 -->
            <div class="row">
                <!-- Assign User Group Form -->
                <div class="col-md-6">
                    <?php
//                        if($_SESSION['institution']!=='0'){
                    ?>
                    <!--<div class="panel panel-default" ng-init="getOptionData('new_user_group', 'mx_user', 'user_id');">-->
                    <!--                        <div class="panel-heading">
                                                <h4 class="green">Assign Group To User</h4>
                                            </div>-->
                    <!--<div class="panel-body">-->
                    <?php
//                                 }   
                    ?>
                    <?php
                    if ($_SESSION['role'] === '1') {
                        ?>
                        <div class="panel panel-default" ng-init="getOptionData('user_id', 'mx_user', 'new_user_group');">
                            <div class="panel-heading">
                                <h4 class="green">Assign Group To User</h4>
                            </div>
                            <div class="panel-body">
    <?php
}
?>
                            <?php
                            if ($_SESSION['role'] === '1') {
                                ?>
                                <form class="form-horizontal"  id="new_user_group">
                                    <div class="form-group">
                                        <label for="user_id" class="col-md-3 control-label">User Name</label>
                                        <div class="col-md-9">
                                            <select class="form-control" id="user_id" name="user_id" ng-model="new_user_group.user_id" required ng-change="getUserGroup(new_user_group.user_id);">
                                                <option value=""> Select  User</option>
                                                <!--user options comes from database-->
                                            </select>
                                        </div>
                                    </div>
                                </form>
    <?php
}
if ($_SESSION['role'] !== '1') {
    ?>
                                <form class="form-horizontal"  id="new_user_group">
                                    <div class="form-group">
                                        <label for="user_id" class="col-md-3 control-label">User Name</label>
                                        <div class="col-md-9">
                                            <select class="form-control" id="user_id" name="user_id" ng-model="new_user_group.user_id" required ng-change="getUserGroup(new_user_group.user_id);">
                                                <option value=""> Select  User</option>
                                                <!--user options comes from database-->
                                            </select>
                                        </div>
                                    </div>
                                </form>

    <?php
}
?>


                            <!-- End of Select User Form -->
                            <!--User Group Table-->
                            <div class="table-responsive" ng-show="user_group_flag">
                                <table class="table table-striped table-hover table-condensed table-bordered" id="user_group_table">
                                    <thead>
                                        <tr>
                                            <th>Groups</th>
                                            <th>Assigned</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr ng-repeat="group in groups">
                                            <td class="hide"></td> 
                                            <td>{{group.group_name}}</td>
                                            <td class="hide"><input type="text" ng-model="group.group_id"></td>
                                            <td><input type="checkbox" ng-model="group.check"></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="col-md-6"><label><input type="checkbox" id="us_check" name="us_check"  ng-model="us_check" required/> Done</label></div>
                                <div class="col-md-6"><button type="submit" ng-disabled="!us_check" class="btn btn-success" ng-click="saveTableData('user_group_table', 'saveUserGroup', new_user_group.user_id, false);
                                            us_check = false;">Save</button></div>
                            </div>
                            <!--End of User Group Table-->
                        </div>
                    </div>
                </div>
                <!-- End of User Group Form -->
<?php
if ($_SESSION['role'] === '1') {
    ?>
                    <!-- Assign User Permission Form -->
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="cuf">Grant User Permission</h4>
                            </div>
                            <div class="panel-body">
                                <!-- Select User Form -->
                                <form class="form-horizontal" id="new_user_permission">
                                    <div class="form-group">
                                        <label for="user_id" class="col-md-3 control-label">User Name</label>
                                        <div class="col-md-9">
                                            <select class="form-control" id="group_id" name="user_id" ng-model="new_user_permission.user_id" ng-change="getUserPermission(new_user_permission.user_id);" required>
                                                <option value=""> Select  User</option>
                                                <!--user options comes from database-->
                                            </select>
                                        </div>
                                    </div>
                                </form>
                                <!-- End of Select User Form -->
                                <!--User Permission Table-->
                                <div class="table-responsive scrolled-div" ng-show="user_permission_flag" style="max-height: 65vh">
                                    <table class="table table-striped table-hover table-condensed table-bordered" id="user_permission_table" style="height: 50% !important;">
                                        <thead>
                                            <tr>
                                                <th colspan="2">Permissions</th>
                                                <th>Granted</th>
                                            </tr>
                                        </thead>
                                        <tbody ng-repeat="(key, value) in user_permissions | groupBy: 'section_name'">
                                            <tr style="background-color: #D5D8DC !important;">
                                                <th colspan="2">{{key}}</th>
                                                <th class="hide"><input type="text" ng-model="value.section_id"></th>
                                                <th><input type="checkbox" onclick="selectSectionPermission($(this));"></th>
                                            </tr>
                                            <tr ng-repeat="permission in value">
                                                <td style="width: 50px;">&nbsp;</td>
                                                <td>{{permission.permission_display_name}}</td>
                                                <td class="hide"><input type="text" ng-model="permission.permission_id"></td>
                                                <td><input type="checkbox" ng-model="permission.check"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="col-md-6"><label><input type="checkbox" id="up_check" name="up_check"  ng-model="up_check" required/> Done</label></div>
                                    <div class="col-md-6"><button type="submit" ng-disabled="!up_check" class="btn btn-info" ng-click="saveTableData('user_permission_table', 'saveUserPermission', new_user_permission.user_id);
                                                    up_check = false;">Save</button></div>
                                </div>
                                <!--End of User Permission Table-->
                            </div>
                        </div>
                    </div>
<?php } ?>
                <!-- End of Assign User Permission Form -->
            </div>
            <!-- End of Container 2 -->
        </div>
        <!-- End of User Section -->
<?php
if ($_SESSION['role'] === '1') {
    ?>
            <!-- Permission Section Tab -->

            <div ng-show="isActiveTab(3);">
                <!-- Container 3 -->
                <div class="row">
                    <!-- Section -->
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="orange">Add New Section</h4>
                            </div>
                            <div class="panel-body">
                                <form class="form-horizontal" id="new_section" name="new_section" ng-submit="saveForm('Permission', 'saveSection')" novalidate>
                                    <div class="form-group">
                                        <label for="name" class="col-md-3 control-label">Section</label>
                                        <div class="col-md-9">
                                            <input type="name" class="form-control" id="name" placeholder="Write section name" ng-class="new_section.name.$invalid && !new_section.name.$pristine" ng-model="form.name" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button type="submit" class="btn btn-default orange" ng-disabled="new_section.$invalid" >Add Section</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- End of Select User Form -->

                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="green">Add New Permission</h4>
                            </div>
                            <div class="panel-body">
                                <!-- Select User Form -->
                                <form class="form-horizontal" id="new_permission" ng-submit="saveForm('Permission', 'savePermission')">
                                    <div class="form-group">
                                        <label for="display_name" class="col-md-3 control-label">Display Name</label>
                                        <div class="col-md-9">
                                            <input type="display_name" class="form-control" id="display_name" placeholder="Write permission display name" ng-class="new_permission.display_name.$invalid && !new_permission.display_name.$pristine" ng-model="form.display_name" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="name" class="col-md-3 control-label">Name</label>
                                        <div class="col-md-9">
                                            <input type="name" class="form-control" id="name" placeholder="Write permission name" ng-class="new_permission.name.$invalid && !new_permission.name.$pristine" ng-model="form.name" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="section_id" class="col-md-3 control-label">Section Name</label>
                                        <div class="col-md-9">
                                            <select class="form-control" id="section_id" name="section_id" ng-model="form.section_id" required>
                                                <option value=""> Select  Section</option>
                                                <!--user options comes from database-->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button type="submit" class="btn btn-default green" ng-disabled="new_permission.$invalid" >Add Permission</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- End of Select User Form -->

                            </div>
                        </div>
                    </div>

                    <!-- End of User Group Form -->

                    <!-- Available  Permission  -->
                    <div class="col-md-4" ng-init="getAllPermissions();">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="blue">Available Permission</h4>
                            </div>
                            <div class="panel-body">

                                <!--Group Permission Table-->
                                <div class="table-responsive scrolled-div" style="max-height: 70vh">
                                    <table class="table table-striped table-hover table-condensed table-bordered" id="permissions_table">                              
                                        <tbody ng-repeat="(key, value) in all_permissions | groupBy: 'section_name'">
                                            <tr style="background-color: #D5D8DC !important;">
                                                <th colspan="2">{{key}}</th>
                                            </tr>
                                            <tr ng-repeat="permission in value">
                                                <td>{{permission.permission_display_name}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!--End of Group Permission Table-->
                            </div>
                        </div>
                    </div>
                    <!--End of Assign Group Permission-->

                </div>

            </div>
    <?php
}
?>
    </div>
    <!-- End of Container 3 -->
</div>
<!-- End of Permission Section -->
</div>
<!-- End of Controller -->
</div>
