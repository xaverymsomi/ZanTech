<div id="page-content">
    <style>
        /* Modern Modal Styling */
        .modern-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            border: none;
        }

        .modern-modal-header .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
        }

        .modern-modal-header .close {
            color: white;
            opacity: 0.8;
            font-size: 1.75rem;
            font-weight: 300;
            text-shadow: none;
            transition: all 0.2s ease;
        }

        .modern-modal-header .close:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .modern-modal-body {
            padding: 2rem;
            background: #f8f9fa;
        }

        .modern-modal-footer {
            padding: 1.25rem 2rem;
            background: white;
            border-top: 2px solid #e9ecef;
            border-radius: 0 0 12px 12px;
        }

        .modern-modal-footer .btn {
            border-radius: 8px;
            padding: 0.625rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modern-modal-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .processing-indicator {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #667eea;
            font-weight: 500;
        }

        .processing-indicator i {
            color: #667eea;
        }
    </style>

    <div id="data_content" 
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>" 
         data-dropdowns="<?php echo json_encode($this->dropdowns, JSON_NUMERIC_CHECK) ?>">
    </div>
    
    <div id="display_content">
        <form name="smssetup" ng-submit="saveForm('post_edit_sms_setup')" novalidate>
            <!-- Modern Modal Header -->
            <div class="modern-modal-header">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-cog fa-lg"></i>
                    <?php echo $this->title ?>
                </h4>
            </div>

            <!-- Modern Modal Body -->
            <div class="modern-modal-body">
                <div class="notification-area mb-3"></div>
                <div class="form-horizontal">
                    <?php include 'forms/smssetup.html'; ?>
                </div>
            </div>

            <!-- Modern Modal Footer -->
            <div class="modern-modal-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="processing-indicator" ng-if="ProcessingData === true">
                        <i class="fa fa-spinner fa-pulse fa-lg"></i>
                        <span>Processing your request, please wait...</span>
                    </div>
                    <div ng-if="ProcessingData !== true"></div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" 
                                ng-disabled="smssetup.$invalid || ProcessingData === true" 
                                class="btn btn-primary">
                            <i class="fa fa-save me-2"></i>Save Changes
                        </button>
                        <button ng-disabled="ProcessingData === true" 
                                ng-click="cancel()" 
                                type="button" 
                                class="btn btn-outline-secondary" 
                                data-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
