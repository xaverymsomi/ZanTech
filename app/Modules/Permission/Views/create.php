?>

<div id="page-content">
    <form name="menu" ng-submit="saveForm()" novalidate>
        <div class="modal-header" style="background: linear-gradient(to right, #030000, rgba(3, 3, 3, 0.95), #00AEEF); color: white;">
            <button type="button"  ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;
            </button>
            <h4 class="modal-title ocean"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i>New Menu</h4>
        </div>
        <div class="modal-body">
            <div class="form-horizontal">
                <input type="hidden" id="id" name="id" value="" ng-model="form.id" />

                <div class="form-group">
                    <label for="txt_name" class="col-lg-3 control-label">Name</label>
                    <div class="col-lg-9">
                        <input type="text" id="txt_name" placeholder="Enter name" name="txt_name" class="form-control" ng-class="menu.txt_name.$invalid && !menu.txt_name.$pristine" ng-model="form.txt_name" required />
                    </div>
                </div>

                <div class="form-group">
                    <label for="txt_icon" class="col-lg-3 control-label">Icon</label>
                    <div class="col-lg-9">
                        <input type="text" id="txt_icon" placeholder="Enter icon" name="txt_icon" class="form-control" ng-class="menu.txt_icon.$invalid && !menu.txt_icon.$pristine" ng-model="form.txt_icon" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="int_parent" class="col-lg-3 control-label">Parent</label>
                    <div class="col-lg-9">
                        <input type="number" id="int_parent" placeholder="Enter parent" name="int_parent" class="form-control" ng-class="menu.int_parent.$invalid && !menu.int_parent.$pristine" ng-model="form.int_parent" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="int_position" class="col-lg-3 control-label">Position</label>
                    <div class="col-lg-9">
                        <input type="number" id="int_position" placeholder="Enter position" name="int_position" class="form-control" ng-class="menu.int_position.$invalid && !menu.int_position.$pristine" ng-model="form.int_position" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="txt_link" class="col-lg-3 control-label">Link</label>
                    <div class="col-lg-9">
                        <input type="text" id="txt_link" placeholder="Enter link" name="txt_link" class="form-control" ng-class="menu.txt_link.$invalid && !menu.txt_link.$pristine" ng-model="form.txt_link" required />
                    </div>
                </div>

                <div class="form-group">
                    <label for="txt_title" class="col-lg-3 control-label">Title</label>
                    <div class="col-lg-9">
                        <input type="text" id="txt_title" placeholder="Enter title" name="txt_title" class="form-control" ng-class="menu.txt_title.$invalid && !menu.txt_title.$pristine" ng-model="form.txt_title" required />
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" ng-disabled="menu.$invalid" class="btn btn-info" name="submit">Submit</button>
            <button type="button" ng-click="cancel()" class="btn btn-danger" data-dismiss="modal">Close</button>
        </div>
    </form>


</div>
