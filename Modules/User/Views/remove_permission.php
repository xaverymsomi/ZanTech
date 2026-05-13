<?php

?>
<div id="page-content">
    <div class="modal-header" style="background-color: #f1948a; color: white;">
        <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: #fff; font-size: 35px;">&times;</button>
        <h4 class="modal-title ocean"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i>Remove Permission from User</h4>        
    </div>
    <div class="modal-body">
        <div class="row">
            <di class="col-md-2" style="color: #f1948a">
                <i class="fa fa-warning fa-5x pull-right"></i>
            </di>
            <div class="col-md-10">
                <h4>You are about to remove this permission from the current user.</h4>
                <h4>Click <strong>Confirm</strong> to proceed or <strong>Cancel</strong> to ignore.</h4>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" ng-click="removeUserPermission();cancel()" data-dismiss="modal" class="btn" style="background-color: #f1948a; color: #fff;">Confirm</button>
        <button type="button" ng-click="cancel()" class="btn btn-default" data-dismiss="modal">Cancel</button>
    </div>
</div>
