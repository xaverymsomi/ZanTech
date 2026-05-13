<div id="page-content">
    <?php

    use Library\\Session;

    $returned = Session::get('returned') != null || Session::get('returned') != '' ? Session::get('returned') : 0;
    ?>
    <form ng-controller="formController" name="userpassword" novalidate ng-init="return_value =<?php echo $returned; ?>">
        <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
            <h4 class="modal-title ocean"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i>Change Password</h4>
        </div>
        <div class="modal-body">
            <div class="form-horizontal">
                <div class="modal-body">
                    <?php
                    include 'forms/changepassword.html';
                    ?>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info btn-md" name="submit" ng-click="changePassword()" ng-disabled="userpassword.$invalid"> Change Password </button>
                    </div>

                </div>

            </div>
        </div>

    </form>

</div>
