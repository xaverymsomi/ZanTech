<div class="container-fluid" id="page-content">
    <?php
    //Getting all user roles

    use Authentication\Perm_Auth;

    $perm = Perm_Auth::getPermissions();
    //verifying user roles
    include "dashboard.php";
    ?>
</div>
