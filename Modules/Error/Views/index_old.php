<div id="page-content">
    <div id="data_content" 
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <div class="row">
            <div class="modal-header" style="background:linear-gradient(to right,#000000,#626262);color: white;">
                <button ng-click="cancel()" type="button" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean text-capitalize"><i class="pe pe-7s-car pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>        
            </div>
            <div class="modal-body">

                <div class="col col-sm-8 text-center">
                    <br><br><br>
                    <?php
                    echo '<h1><i class="fa ' . $this->icon . ' fa-3x error" aria-hidden="true"></i><h1><h3 class="cuf">' . $this->msg . ' </h3>';
                    echo '<h4 class="error">' . $this->sub . '</h4>';
                    echo '<h5>' . date('d M Y @ H:m:s') . '</h5>';
                    ?>
                </div>

                <!-- <div class="col col-sm-8 text-center" style='position: fixed; top: 22%; background-color:#fff; left:25%; border:solid 10px #e8e8e8; height: 350px;'>
                    <br><br><br>
                <?php
                /**
                  echo '<h1><i class="fa ' . $this->icon . ' fa-3x error" aria-hidden="true"></i><h1><h3 class="cuf">' . $this->msg . ' </h3>';
                  echo '<h4 class="error">' . $this->sub . '</h4>';
                  echo '<h5>' . date('d M Y @ H:m:s') . '</h5>';

                 */
                ?>
                </div> -->
            </div>
        </div>

    </div>
</div>
