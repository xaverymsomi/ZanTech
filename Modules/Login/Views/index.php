<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');

    :root {
        --zt-bg: #003947; /* Deep teal */
        --zt-accent: #ff7e5f; /* Coral from image */
        --zt-card-bg: #ffffff;
        --zt-text-left: #ffffff;
        --zt-text-muted: rgba(255, 255, 255, 0.78);
        --zt-input-bg: #ffffff;
        --zt-input-border: #e2e8f0;
    }

    body,
    body.zt-login-page {
        margin: 0;
        padding: 0;
        font-family: 'Outfit', -apple-system, sans-serif;
        background: var(--zt-bg) !important;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        color: var(--zt-text-left);
        overflow-x: hidden;
        position: relative;
    }

    body.zt-login-page {
        color: var(--zt-text-left) !important;
    }

    /* Bokeh Background Effect */
    .bokeh-container {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 0;
        pointer-events: none;
    }

    .bokeh {
        position: absolute;
        background: rgba(173, 216, 230, 0.13);
        border-radius: 50%;
        filter: blur(12px);
    }

    /* Layout */
    .login-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        z-index: 1;
        min-height: calc(100vh - 90px);
    }

    .login-content {
        display: flex;
        max-width: 1100px;
        width: 100%;
        gap: 5rem;
        align-items: center;
    }

    /* Left Side: System Info */
    .system-info {
        flex: 1;
        text-align: left;
    }

    .system-info h1 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        letter-spacing: -0.4px;
    }

    .system-info .description {
        font-size: 1.05rem;
        line-height: 1.7;
        color: var(--zt-text-muted);
        margin-bottom: 2.5rem;
        max-width: 500px;
        font-weight: 300;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0 0 2.5rem 0;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
        font-weight: 500;
        color: var(--zt-text-muted);
        font-size: 0.95rem;
    }

    .feature-icon {
        width: 34px;
        height: 34px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--zt-accent);
        font-size: 0.9rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .legal-notice {
        font-size: 0.85rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.7);
        max-width: 550px;
        font-weight: 300;
    }

    .legal-notice strong {
        color: var(--zt-accent);
        font-weight: 600;
    }

    /* Right Side: Login Card */
    .login-card-container {
        width: 440px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .login-card {
        background: var(--zt-card-bg);
        border-radius: 14px;
        padding: 2.2rem 2rem;
        box-shadow: 0 25px 45px rgba(0,0,0,0.3);
        color: #334155;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }

    .card-title {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 500;
        margin-bottom: 1.1rem;
    }

    /* Inputs */
    .form-group {
        position: relative;
        margin-bottom: 0.6rem;
    }

    .form-icon {
        position: absolute;
        left: 1.15rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .form-control-mabrex {
        width: 100%;
        padding: 0.65rem 0.95rem 0.65rem 2.8rem;
        border: 1px solid var(--zt-input-border);
        border-radius: 2px;
        font-size: 0.8rem;
        color: #1e293b;
        transition: all 0.3s ease;
        box-sizing: border-box;
        font-family: 'Outfit', sans-serif;
    }

    .form-control-mabrex:focus {
        outline: none;
        border-color: var(--zt-accent);
        box-shadow: 0 0 0 4px rgba(255, 126, 95, 0.1);
    }

    .btn-signin {
        width: 100%;
        background: var(--zt-accent);
        color: #fff;
        border: none;
        border-radius: 2px;
        padding: 0.65rem;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 0.7rem;
        font-family: 'Outfit', sans-serif;
        text-transform: none;
    }

    .btn-signin:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 126, 95, 0.2);
    }

    .forgot-link {
        display: inline-block;
        margin-top: 1rem;
        color: rgba(255,255,255,0.68);
        text-decoration: none;
        font-size: 0.72rem;
        transition: color 0.3s ease;
        font-weight: 400;
    }

    .forgot-link:hover {
        color: #ffffff;
    }

    /* Footer */
    .login-footer {
        padding: 2.5rem 5rem;
        border-top: 1px solid rgba(255,255,255,0.15);
        display: flex;
        justify-content: space-between;
        font-size: 0.72rem;
        color: rgba(255,255,255,0.7);
        z-index: 1;
        font-weight: 300;
    }

    .footer-link {
        color: inherit;
        text-decoration: none;
    }

    .footer-link:hover {
        color: #ffffff;
    }

    @media (max-width: 992px) {
        .login-content {
            flex-direction: column;
            gap: 4rem;
            text-align: center;
        }
        .system-info {
            text-align: center;
        }
        .system-info .description, .legal-notice {
            margin-left: auto;
            margin-right: auto;
        }
        .feature-list {
            display: inline-block;
            text-align: left;
        }
    }

    .text-accent { color: var(--zt-accent); }
    .mb-3 { margin-bottom: 1.25rem; }

    /* Modal Styles */
    .zt-modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .zt-modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .zt-modal {
        background: #fff;
        width: 100%;
        max-width: 750px;
        max-height: 85vh;
        border-radius: 14px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        display: flex;
        flex-direction: column;
        transform: translateY(20px);
        transition: transform 0.3s ease;
        color: #334155;
    }

    .zt-modal-overlay.active .zt-modal {
        transform: translateY(0);
    }

    .zt-modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .zt-modal-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.15rem;
        color: #1e293b;
    }

    .zt-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #94a3b8;
        cursor: pointer;
        transition: color 0.2s;
    }

    .zt-modal-close:hover {
        color: #ef4444;
    }

    .zt-modal-body {
        padding: 2.5rem;
        overflow-y: auto;
        font-size: 0.95rem;
        line-height: 1.8;
        font-weight: 300;
    }

    .terms-intro {
        color: #64748b;
        margin-bottom: 2rem;
    }

    .terms-list {
        padding-left: 1.25rem;
    }

    .terms-list li {
        margin-bottom: 1.25rem;
        color: #334155;
    }

    /* Captcha Popover */
    .captcha-container {
        position: relative;
        margin-top: 0.8rem;
    }

    .captcha-popover {
        position: absolute;
        bottom: calc(100% + 12px);
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background: #fff;
        padding: 6px;
        border-radius: 10px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 10;
        border: 1px solid #e2e8f0;
    }

    .captcha-container:focus-within .captcha-popover {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .captcha-popover::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 8px;
        border-style: solid;
        border-color: #fff transparent transparent transparent;
    }

    /* Outer border for the popover arrow */
    .captcha-popover::before {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 9px;
        border-style: solid;
        border-color: #e2e8f0 transparent transparent transparent;
        margin-top: 1px;
    }

    .captcha-img {
        height: 34px;
        display: block;
        border-radius: 6px;
        cursor: pointer;
    }
</style>

<div class="bokeh-container">
    <div class="bokeh" style="width: 250px; height: 250px; top: 5%; left: 2%;"></div>
    <div class="bokeh" style="width: 180px; height: 180px; top: 65%; left: 10%;"></div>
    <div class="bokeh" style="width: 220px; height: 220px; top: 25%; right: 5%;"></div>
    <div class="bokeh" style="width: 150px; height: 150px; bottom: 10%; right: 15%;"></div>
</div>

<!-- Terms of Use Modal -->
<div class="zt-modal-overlay" ng-class="{'active': $root.showTermsModal}" ng-click="$root.showTermsModal = false" ng-controller="formController">
    <div class="zt-modal" ng-click="$event.stopPropagation()">
        <div class="zt-modal-header">
            <h5>Terms of Use</h5>
            <button class="zt-modal-close" ng-click="$root.showTermsModal = false">&times;</button>
        </div>
        <div class="zt-modal-body">
            <p class="terms-intro">
                Dear user of this system, this online system is owned by Zantech Group (hereinafter to be referred to as the Company). 
                By logging in the Zantech Admin Portal (hereinafter referred to as the System) you are accepting these terms and conditions of use;
            </p>
            <ol class="terms-list">
                <li>That you are guaranteeing the Company that you are capable of reading and understanding these terms and conditions.</li>
                <li>That you shall provide genuine, authentic and accurate information as may be need in this system.</li>
                <li>That you shall verify the information generated by the System or on your demand and report or modify accordingly the information that appears to be erroneous about you.</li>
                <li>That you will keep your username and password and that you will remain responsible for confidentiality of your password even where you decide to change the same. Zantech Group is not liable for loss/stolen passwords and for any issues of security breach occurring as a result of that loss/theft.</li>
                <li>That lost account data can be revived/retrieved. This is an automatic process enabled to provide users with their data. The System allows its users to retrieve lost passwords and forgotten usernames by email. The User can click on "reset password" button and check his/her email box with further detailed instructions. It is the responsibility of the user to keep the username and password of his/her email.</li>
                <li>That the Company reserves the right to prevent any unauthorized access to the System for security reasons and for protection of user accounts.</li>
                <li>That all users must know that it is unauthorized access to use authorized access to perform unauthorized activity in the System.</li>
                <li>That all users must know that it is unauthorized access to use someone else's login credentials to access his/her account in the System without his/her express consent.</li>
                <li>That all users will be responsible for any activity done in their accounts upon any request generated by the user in the System.</li>
                <li>That use of the System may require the browser to accept cookies and that JavaScript is enabled.</li>
                <li>That some of the data for the System is generated in PDF format. The user must have Adobe Reader or a similar PDF reader. Adobe Reader may be downloaded free of charge from the Adobe website.</li>
                <li>That users are responsible for ensuring the legibility (readability) of any attachments they upload to the system.</li>
            </ol>
        </div>
    </div>
</div>

<div class="login-wrapper" ng-controller="formController" ng-init="current_task = 'login'">
    <div class="login-content">
        <!-- Left Side -->
        <div class="system-info">
            <h1>ZANTECH</h1>
            
            <div ng-show="current_task === 'login'">
                <p class="description">
                    A comprehensive enterprise management system designed to streamline system administrative workflows, 
                    manage permissions, and facilitate high-level reporting functionalities which include;
                </p>
                <ul class="feature-list">
                    <li class="feature-item">
                        <div class="feature-icon"><i class="fa fa-user-plus"></i></div>
                        User Registration & Onboarding
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon"><i class="fa fa-shield-alt"></i></div>
                        Advanced Permission Controls
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon"><i class="fa fa-chart-bar"></i></div>
                        Financial & Activity Reporting
                    </li>
                </ul>
                <p class="legal-notice">
                    * By logging into this system you accept its <a href="javascript:void(0)" ng-click="$root.showTermsModal = true" style="color: var(--zt-accent); text-decoration: none; font-weight: 600;">terms and conditions</a> of use. 
                    Zantech Group retains the right to use the data provided in the system for any lawful purpose.
                </p>
            </div>

            <div ng-show="current_task === 'recover'">
                <p class="description">Zantech System Administrative Management Portal</p>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <span class="text-accent fw-bold">*</span> For users; please use your registered email address to reset your password.
                    </li>
                    <li class="mb-3">
                        <span class="text-accent fw-bold">*</span> For administrators; please contact support if you lose access to your primary email.
                    </li>
                    <li class="mb-3">
                        <span class="text-accent fw-bold">*</span> Upon Successful; Go to your email inbox and use the reset link to reset your password.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Side -->
        <div class="login-card-container">
            <!-- LOGIN CARD -->
            <div ng-show="current_task === 'login'" style="width: 100%;">
                <div class="login-card">
                    <div class="text-center">
                        <i class="fa fa-shield-alt fa-4x text-muted mb-4 opacity-10"></i>
                    </div>

                    <?php if (!empty($this->error_message)): ?>
                        <div class="alert alert-warning border-0 shadow-sm small text-start mb-4 py-2 px-3 d-flex align-items-center rounded-3 animate__animated animate__fadeIn">
                            <i class="fa fa-exclamation-circle me-2"></i>
                            <div><?= htmlspecialchars($this->error_message) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="card-title">Sign in with credentials</div>
                    <form method="post" action="<?php echo URL; ?>/login/login">
                        <input type="hidden" name="_token" value="<?= \Authentication\Session::csrfToken() ?>">
                        <div class="form-group">
                            <i class="fa fa-user form-icon"></i>
                            <input type="text" name="email" class="form-control-mabrex" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <i class="fa fa-lock form-icon"></i>
                            <input type="password" name="password" class="form-control-mabrex" placeholder="Password" required>
                        </div>
                        
                        <div class="captcha-container">
                            <div class="captcha-popover">
                                <img src="<?php echo APP_DIR; ?>/Login/get_captcha" alt="CAPTCHA" class="captcha-img" onclick="refreshCaptcha(this)" title="Click to refresh">
                            </div>
                            <i class="fa fa-sync-alt form-icon"></i>
                            <input type="text" name="captcha" class="form-control-mabrex" placeholder="Security Code" required autocomplete="off">
                        </div>

                        <button type="submit" name="SignIn" class="btn-signin">Sign In</button>
                    </form>
                </div>
                <div class="text-center">
                    <a href="javascript:void(0)" class="forgot-link" ng-click="current_task = 'recover'">Forgot password?</a>
                </div>
            </div>

            <!-- RECOVERY CARD -->
            <div ng-show="current_task === 'recover'" style="width: 100%;">
                <div class="login-card">
                    <h4 class="fw-bold mb-4">Reset Password</h4>
                    <div class="card-title">Reset with your Email Address</div>
                    <form method="post" action="<?php echo URL; ?>/Login/recover">
                        <input type="hidden" name="_token" value="<?= \Authentication\Session::csrfToken() ?>">
                        <div class="form-group">
                            <i class="fa fa-envelope form-icon"></i>
                            <input type="email" name="email" class="form-control-mabrex" placeholder="Email Address" required>
                        </div>
                        <button type="submit" name="RecoverPassword" class="btn-signin">Send Link</button>
                    </form>
                </div>
                <div class="text-center">
                    <a href="javascript:void(0)" class="forgot-link" ng-click="current_task = 'login'">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="login-footer">
    <div>&copy; 2026 <span style="color: var(--zt-accent); font-weight: 500;">Zantech Group</span></div>
    <div><a href="javascript:void(0)" class="footer-link" ng-click="$root.showTermsModal = true">Terms of Use</a></div>
</div>

<script>
    document.body.classList.add('zt-login-page');

    function refreshCaptcha(img) {
        img.src = img.src.split('?')[0] + '?' + new Date().getTime();
    }
</script>
