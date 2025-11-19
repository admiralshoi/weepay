<?php

use classes\Methods;

?>
<div id="main-wrapper" class="">
    <div class="row">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-body p-0">
                    <div class="row no-gutters">
                        <div class="col-lg-6 overflow-y-auto" style="height: 100vh;">
                            <div class="flex-col-center flex-align-center ">
                                <div class="p-5 auth-form" id="signup-form">
                                    <div class="mb-4">
                                        <h3 class="h4 font-weight-bold text-theme">Sign up to <?=BRAND_NAME?></h3>
                                        <p class="text-muted text-left mb-0  font-20">
                                            Already have an account?
                                            <a href="<?=__url("login")?>" class="color-dark hover-underline ">Login</a>
                                        </p>
                                    </div>

                                    <div class="flex-col-center flex-align-center" id="user_type_container">


                                        <div class="type-selection-card noSelect" id="brand_type_select">
                                            <p>I'm a Brand / Agency</p>
                                            <img src="<?=__image("illustrations/brand.svg")?>" />
                                        </div>
                                        <div class="type-selection-card" id="creator_type_select">
                                            <p>I'm a Content Creator</p>
                                            <img src="<?=__image("illustrations/creator.svg")?>" />
                                        </div>


                                    </div>



                                    <div class="flex-col-start" id="signup_forms" style="display: none; margin-top: 4rem;">
                                        <div class="cursor-pointer mb-2 flex-row-start flex-align-center hover-underline color-primary-cta noSelect"
                                             style="column-gap: .25rem" id="nav-back-choice">
                                            <i class="mdi mdi-arrow-left"></i>
                                            <p class="mb-0 ">Choose another account type</p>
                                        </div>

                                        <form method="post" id="brand_form" style="display: none;">
                                            <input type="hidden" value="<?=Methods::roles()->accessLevel("brand")?>" name="access_level" />
                                            <h6 class="h5 mb-0">Signup as a brand / agency</h6>
                                            <p class="text-muted mt-2 mb-3">
                                                Once your user is registered you'll be able to create an organisation to which you can invite your
                                                coworkers etc, so that you do not need to share a login.
                                            </p>


                                            <div class="form-group">
                                                <label for="email">Email address</label>
                                                <input type="email" id="email" class="form-control" name="email" placeholder="youemail@example.com">
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Your name</label>
                                                <input type="text" id="full_name" placeholder="Your name" class="form-control" name="full_name" >
                                            </div>
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
                                        </form>

                                        <form method="post" id="creator_form" style="display: none">
                                            <input type="hidden" value="<?=Methods::roles()->accessLevel("creator")?>" name="access_level" />
                                            <h6 class="h5 mb-0">Signup as a content creator</h6>
                                            <p class="text-muted mt-2 mb-3">
                                                Once your user is registered you'll be able to integrate your social account. This way you will not have
                                                to manually send any metrics and media to the brands that you are working with.
                                            </p>
                                            <div class="form-group">
                                                <label for="email">Email address</label>
                                                <input type="email" id="email" class="form-control" name="email" placeholder="youemail@example.com">
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Your name</label>
                                                <input type="text" id="full_name" placeholder="Your name" class="form-control" name="full_name" >
                                            </div>
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
                                        </form>

                                        <div class="flex-col-start my-3" style="column-gap: .75rem;">
                                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem;">
                                                <p class="mb-0 font-16">
                                                    I have read and accept the <a href="<?=__url("privacy-policy")?>" target="_blank">Privacy policy</a>.
                                                </p>
                                                <input type="checkbox" class="custom-check" name="policy_accept">
                                            </div>
                                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem;">
                                                <p class="mb-0 font-16">
                                                    I have read and accept the <a href="<?=__url("terms-of-use")?>" target="_blank">Terms of use</a>.
                                                </p>
                                                <input type="checkbox" class="custom-check" name="terms_accept">
                                            </div>
                                        </div>

                                        <p class="my-2 p-3 alert-danger" id="error-field" style="display: none"></p>
                                        <button name="signup_user" class="auth-btn-prim">Register now</button>

                                    </div>
                                </div>


                            </div>
                        </div>
                        <div class="col-lg-6 d-none d-lg-inline-block" style="height: 100vh;">
                            <div class="flex-col-center auth-illustration-container h-100">
                                <div class="flex-col-center flex-align-center" id="brand-usp" style="display: none !important;">
                                    <p class="mb-5 font-40 color-white text-center">Signup Benefits</p>
                                    <div class="usp-card">
                                        <div class="usp-content">
                                            <span class="usp-icon">🎁</span>
                                            Get 10 Free Days of Campaigning
                                        </div>
                                    </div>
                                    <div class="usp-card">
                                        <div class="usp-content">
                                            <span class="usp-icon">🔒</span>
                                            No Credit Card Required
                                        </div>
                                    </div>
                                    <div class="usp-card">
                                        <div class="usp-content">
                                            <span class="usp-icon">✔</span>
                                            Complete content tracking
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-col-center flex-align-center" id="creator-usp" style="display: none !important;">
                                    <p class="mb-5 font-40 color-white text-center">Signup Benefits</p>
                                    <div class="usp-card">
                                        <div class="usp-content">
                                            <span class="usp-icon">✔</span>
                                            Automatically Send Your Campaign Data
                                        </div>
                                    </div>
                                    <div class="usp-card">
                                        <div class="usp-content">
                                            <span class="usp-icon">🆓</span>
                                            Completely Free
                                        </div>
                                    </div>
                                    <div class="usp-card">
                                        <div class="usp-content">
                                            <span class="usp-icon">⚡</span>
                                            No Time Spend, Full Efficiency
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-col-center flex-align-center" id="choice-usp" >
                                    <img src="<?=__image("illustrations/signup.svg")?>" class="auth-image noSelect" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>