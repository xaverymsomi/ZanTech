<div id="page-content">
    <div id="data_content" data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>" data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>" data-disabled="<?php // echo htmlspecialchars(json_encode($this->disabled, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')
                                                                                                                                                                                                                                                                                ?>"></div>
    <div id="display_content">
<!--style="background: linear-gradient(to right, #000201, #085A78); color: white;"-->
<!--        style="background: linear-gradient(to right, #000201, #085A78); color: white;"-->
        <form name="user" ng-submit="saveForm()" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i>New User</h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <?php
                    include 'forms/user.html';
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" ng-disabled="user.$invalid" class="btn btn-info" name="submit"> Submit </button>
                <button ng-click="cancel()" type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
            </div>
        </form>


    </div>
</div>
