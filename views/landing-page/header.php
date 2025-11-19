<div class="row">
    <div class="col-12 pt-4 m-0 flex-row-between flex-align-center" id="main-top-nav-dark">
        <div>
            <a href="<?=HOST?>">
                <div class="flex-row-start flex-align-center mt-2">
                    <p class="pb-0 font-25 color-dark"><?=BRAND_NAME?></p>
                    <img src="<?=__asset(LOGO_ICON)?>" class="h-25px ml-2" />
                </div>
            </a>
        </div>

        <div class="flex-row-start flex-align-center" id="main-top-nav-bar">
            <?php if(!isLoggedIn()): ?>
            <a href="<?=__url("login")?>" class="btn-base link-prim font-weight-bold">Login</a>
            <a href="<?=__url("signup")?>" class="btn-prim btn-base font-weight-bold">SIGN UP</a>
            <?php else: ?>
                <a href="<?=__url("")?>" class="btn-base link-prim font-weight-bold">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</div>
