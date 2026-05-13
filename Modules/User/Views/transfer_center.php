<div id="page-content">
    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <form name="new_pin" ng-submit="saveProfileOperation('<?php echo $this->controller ?>', '<?php echo $this->action ?>')" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
                <button type="button" ng-click="close()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-refresh-2 pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="form-group">
                        <div class="col-md-12" ng-readonly="ProcessingData === true">
                            <div class="form-group">
                                <label for="checkme" class="col-lg-2 control-label"></label>
                                <div class="col-lg-8">
                                    <h2 class="text-success"><i class="pe pe-7s-refresh-2 pe-fw pe-va pe-2x"></i><?php echo $this->subtitle ?></h2>
                                    <span style="padding-left: 90px;">In order to Transfer <?php echo htmlspecialchars($this->name, ENT_NOQUOTES, 'UTF-8') ?> Center, you must click the</span>
                                    <br><span style="padding-left: 90px;">“I have read and agreed to <?php echo APP_NAME ?> transfer policy” and then click the “Submit” button.</span><br><br>
                                    <label for = "opt_mx_center_id" class = "col-lg-3 control-label">User Center</label>
                                        <div class = "col-lg-2">
                                            <select name = "opt_mx_center_id" id = "opt_mx_center_id" class = "form-control" ng-model = "form.opt_mx_center_id" 
                                                    ng-options = "value.id as value.name for (key, value) in dropdowns.opt_mx_centers_ids">
                                                <option value = "">Select Branch</option>
                                            </select>
                                        </div>
                            <hr>
                            <span style="padding-left: 90px;"><input type="checkbox" id="checkme" name="checkme"  ng-model="checkme" required></span>
                                    <strong>I have read and agreed to the <span class="text-success"><?php echo APP_NAME ?> Transfer Policy</span></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="new_pin.$invalid || ProcessingData === true" class="btn btn-info" name="submit">Submit</button>
                <button type="button" ng-disabled="ProcessingData === true" ng-click="cancel()" class="btn btn-danger" data-dismiss="modal" ng-disabled="ProcessingRequest === true">Close</button>
            </div>
        </form>
    </div>
</div>
