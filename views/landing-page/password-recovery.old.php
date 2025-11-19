<?php
use classes\Methods;




$resetPwd = isset($_GET["token"]) && Methods::passwordHandler()->resetAvailable($_GET["token"]);



?>



<div class="row mt-5 section-xs-bg section-xs">
    <div class="col-12 flex-row-around">

        <div class="row ">
            <div class="col-12 col-lg-7 pt-5 pb-5 pl-3 pr-3 pl-sm-5 pr-sm-5 border-radius-tl-bl-20px border-lg-left border-lg-top border-lg-bottom  border-lg-right-0 border-primary-dark" >
                <div class="flex-col-start">
                    <img class="w-150px noSelect d-none d-lg-block" src="<?=__asset(LOGO_HEADER)?>" />
                    <?php if(!$resetPwd): ?>
                        <p class="font-30 font-weight-bold mt-4">Reset your password</p>
                        <p class="font-16 text-gray">Enter the email address associated with your account, and we'll send you a link to reset your password</p>

                        <form class="flex-col-start flex-align-start mt-5" method="post" action="" id="user_reset_pwd">
                            <p class="font-16 ">Email</p>
                            <input type="email" name="email" placeholder="youemail@example.com" class="form-control mt-1" />

                            <button class="btn-sec btn-base border-transparen mt-4" name="user_reset_pwd">Send link</button>
                        </form>

                    <?php else: ?>

                        <p class="font-30 font-weight-bold mt-4">Create new password</p>
                        <p class="font-16 text-gray">Create and confirm your new password</p>

                        <form class="flex-col-start flex-align-start mt-5" method="post" action="" id="user_create_new_password">
                            <p class="font-16 ">New password</p>
                            <input type="password" name="password" placeholder="mypassword123" class="form-control mt-1" />

                            <p class="font-16 mt-2">Repeat new password</p>
                            <input type="password" name="password_repeat" placeholder="mypassword123" class="form-control mt-1" />

                            <button class="btn btn-orange-white btn-base mt-4" name="user_create_new_password">Update new password</button>
                        </form>

                    <?php endif; ?>
                </div>
            </div>

            <div class="d-none d-lg-block col-lg-5 border border-radius-tr-br-20px border-lg-left-0 color-white bg-primary-dark" >
                <div class="flex-col-around h-100">

                    <div class="flex-col-start flex-align-center">
                        <p class="font-22 font-weight-bold text-center">Already have an account?</p>
                        <a href="<?=__url('login')?>" class="btn-sec-reversed btn-base mt-4 mxw-150px">Sign in</a>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>
