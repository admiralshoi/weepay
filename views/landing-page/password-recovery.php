<?php
use classes\Methods;
$resetPwd = isset($_GET["token"]) && Methods::passwordHandler()->resetAvailable($_GET["token"]);

$pageTitle = "Nulstil adgangskode";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<div id="main-wrapper" class="">
    <div class="row">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-body p-0">
                    <div class="row no-gutters">
                        <div class="col-lg-6 overflow-y-auto" style="height: 100vh;">
                            <div class="flex-col-center flex-align-center ">
                                <div class="p-5 auth-form " id="login-form">
                                    <div class="auth-header">
                                        <h3 class="h4 font-weight-bold text-theme">Password Recovery</h3>
                                        <p class="text-muted text-left mb-0  font-20">
                                            Don't want to reset your password?
                                            <a href="<?=__url("login")?>" class="color-dark hover-underline ">Login</a>
                                        </p>
                                    </div>

                                    <?php if(!$resetPwd): ?>
                                        <h6 class="h5 mb-0">Reset your password</h6>
                                        <p class="text-muted mt-2 mb-3">
                                            If you have forgotten your password, you can reset it here.
                                            Enter the email associated with your account to get started.
                                        </p>

                                        <form method="post" id="user_reset_pwd">
                                            <div class="form-group">
                                                <label for="email">Email address</label>
                                                <input type="email" id="email" class="form-control" name="email" placeholder="youemail@example.com">
                                            </div>
                                            <button name="user_reset_pwd" class="auth-btn-prim">Send Recovery Link</button>
                                        </form>

                                    <?php else: ?>
                                        <h6 class="h5 mb-0">Create a new password</h6>
                                        <p class="text-muted mt-2 mb-3">
                                            Create and confirm your new password
                                        </p>


                                        <form class="flex-col-start flex-align-start mt-5" method="post" action="" id="user_create_new_password">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <div class="position-relative">
                                                    <input type="password" id="password" name="password" placeholder="******"
                                                           class="form-control togglePwdVisibilityField" data-placement-class="absolute-tr-10-5">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="password_repeat">Repeat your password</label>
                                                <div class="position-relative">
                                                    <input type="password" name="password_repeat" id="password_repeat" placeholder="******"
                                                           class="form-control togglePwdVisibilityField" data-placement-class="absolute-tr-10-5">
                                                </div>
                                            </div>
                                            <button class="btn btn-orange-white btn-base mt-4" name="user_create_new_password">Update your password</button>
                                        </form>

                                    <?php endif; ?>


                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 d-none d-lg-inline-block" style="height: 100vh;">
                            <div class="flex-col-center auth-illustration-container h-100">
                                <div class="flex-col-start">
                                    <img src="<?=__image("illustrations/forgot_password.svg")?>" class="noSelect auth-image" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>