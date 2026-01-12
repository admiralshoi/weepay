


const signupHandler = () => {
    let wrapper = $(document).find("#main-wrapper").first();
    let outerContainer = wrapper.find("#signup-form").first();
    if(!outerContainer.length) return;

    let choiceContainer = outerContainer.find("#user_type_container").first();
    let signupForms = outerContainer.find("#signup_forms").first();
    let errorField = signupForms.find("#error-field").first();
    let signupButton = signupForms.find("button[name=signup_user]").first();
    let brandForm = signupForms.find("#brand_form").first();
    let creatorForm = outerContainer.find("#creator_form").first();
    let navBackBtn = signupForms.find("#nav-back-choice").first();
    let brandTypeBtn = choiceContainer.find("#brand_type_select").first();
    let creatorTypeBtn = choiceContainer.find("#creator_type_select").first();
    let brandUsp = wrapper.find("#brand-usp").first();
    let creatorUsp = wrapper.find("#creator-usp").first();
    let choiceUsp = wrapper.find("#choice-usp").first();
    let policyBox = signupForms.find("[name=policy_accept]").first();
    let termsBox = signupForms.find("[name=terms_accept]").first();
    let formState = null;
    let isLoading = false;

    const handleFormState = (toggleType) => {
        if(!['brand', 'creator', 'main'].includes(toggleType)) return;
        let fadeElements = [choiceContainer, signupForms, brandUsp, creatorUsp, choiceUsp];

        if (toggleType === "brand") {
            brandForm.show();
            creatorForm.hide();
        } else if (toggleType === "creator") {
            creatorForm.show();
            brandForm.hide();
        }

        $.when(...fadeElements.map(el => el.fadeOut(300))).done(() => {
            if (toggleType === "brand") {
                signupForms.fadeIn(300);
                brandUsp.fadeIn(300);
            } else if (toggleType === "creator") {
                signupForms.fadeIn(300);
                creatorUsp.fadeIn(300);
            } else {
                choiceContainer.fadeIn(300);
                choiceUsp.fadeIn(300);
                brandForm.hide();
                creatorForm.hide();
            }
        });


        formState = toggleType;
    }

    if(choiceContainer.length) {
        brandTypeBtn.on("click",function (){ handleFormState('brand') });
        creatorTypeBtn.on("click",function (){ handleFormState('creator') });
        navBackBtn.on("click",function (){ handleFormState('main') });
        formState = "main";
    }

    const clearLoading = () => {
        screenLoader.hide()
        isLoading = false;
    }
    const setError = (msg) => {
        errorField.text(msg)
        errorField.show();
        clearLoading();
    }
    const clearError = () => {
        errorField.text("")
        errorField.hide();
    }
    const setLoading = () => {
        screenLoader.show("Registering your user. Hold on a moment...")
        clearError();
        isLoading = true;
    }


    const register = async (e) => {
        e.preventDefault();
        if(isLoading) return;
        setLoading()
        if(!['brand', 'creator'].includes(formState)) {
            setError("Ukendt  bruger-type.")
            return;
        }
        let form = formState === "brand" ? brandForm : creatorForm;

        if(!policyBox.is(":checked")) {
            setError("Please accept the Privacy Policy");
            return;
        }
        if(!termsBox.is(":checked")) {
            setError("Please accept the Terms of Use");
            return;
        }

        let formData = new FormData(form.get(0));
        let result = await post("api/create-user/normal", formData);
        console.log(result);

        if(result.status === "error") {
            setError(result.error.message)
            return false;
        }

        notifyTopCorner("Account created. Redirecting...")
        screenLoader.update("Account created. Redirecting...");
        window.setTimeout(function (){
            window.location = serverHost;
        }, 2000)
    }
    signupButton.on("click", register)



















}

signupHandler();




const loginHandler = () => {
    let isLoading = false;
    let twoFaTimer = null;
    let twoFaCooldownEnd = 0;

    // Handle showing/hiding country code select based on input
    const handleUsernameInput = (input) => {
        let value = input.val().trim();
        let countryCodeContainer = input.closest('form').find('.login-country-code-container');

        // Check if input is numeric and more than 3 characters
        let isPhoneNumber = value.length > 3 && /^\d+$/.test(value);

        if(isPhoneNumber) {
            countryCodeContainer.removeClass('d-none');
            input.css('width', 'calc(100% - 75px - 2px)');
        } else {
            countryCodeContainer.addClass('d-none');
            input.css('width', '100%');
        }
    }

    // Attach input event listeners to login username fields
    $(".login-username-field").on("input keyup", function() {
        handleUsernameInput($(this));
    });

    const startTwoFaTimer = (seconds = 60) => {
        twoFaCooldownEnd = Date.now() + (seconds * 1000);

        // Hide resend link and show timer
        $("#login-2fa-resend-link").addClass("d-none");
        $("#login-2fa-timer-display").removeClass("d-none");

        const updateTimer = () => {
            let remaining = Math.ceil((twoFaCooldownEnd - Date.now()) / 1000);
            if (remaining <= 0) {
                clearInterval(twoFaTimer);
                twoFaTimer = null;
                twoFaCooldownEnd = 0;

                // Hide timer and show resend link
                $("#login-2fa-timer-display").addClass("d-none");
                $("#login-2fa-resend-link").removeClass("d-none");
            } else {
                $("#login-2fa-timer-countdown").text(remaining);
            }
        };

        updateTimer();
        twoFaTimer = setInterval(updateTimer, 1000);
    }

    const show2faSection = (phoneHint) => {
        // Update phone hint
        $("#login-2fa-phone-hint").text(phoneHint);

        // Clear any existing code
        $("#login_2fa_code").val('');

        // Hide credentials section elements, show 2FA section elements
        $(".login-credentials-section").addClass("d-none");
        $(".login-2fa-section").removeClass("d-none");

        // Focus on code input
        $("#login_2fa_code").focus();

        // Start timer
        startTwoFaTimer(60);
    }

    const hide2faSection = () => {
        // Clear timer
        if(twoFaTimer) {
            clearInterval(twoFaTimer);
            twoFaTimer = null;
        }

        // Hide 2FA section elements, show credentials section elements
        $(".login-2fa-section").addClass("d-none");
        $(".login-credentials-section").removeClass("d-none");

        // Clear code input
        $("#login_2fa_code").val('');

        // Re-enable login button
        $("button[name=login-button]").get(0).disabled = false;
    }

    const loginUser = async (btn) => {
        if(isLoading) return;
        btn.get(0).disabled = true
        isLoading = true;
        let form = btn.parents('form').first();
        let formData = new FormData(form.get(0))
        let dest = form.attr("action")

        // Check if country code container is visible and add phone_country_code
        let countryCodeContainer = form.find('.login-country-code-container');
        if(!countryCodeContainer.hasClass('d-none')) {
            formData.set('phone_country_code', form.find('#login_phone_country_code').val());
        }

        let result = await post(dest, formData);
        console.log(result)

        isLoading = false;

        if(result.status === 'error') {
            btn.get(0).disabled = false
            showErrorNotification("Unable to login", result.error.message)
            return false;
        }

        // Check if 2FA is required
        if(result.data && result.data.requires_2fa) {
            showSuccessNotification("Verifikationskode sendt", result.message);
            show2faSection(result.data.phone_hint);
            return;
        }

        queueNotificationOnLoad("Log ind succesfuldt", result.message, 'success', 2000)

        // Check if form requests page reload instead of redirect (e.g., in checkout flow)
        if(form.data('reload-on-success') === true) {
            window.location.reload();
            return;
        }

        handleStandardApiRedirect(result, 1)
    }

    const verify2fa = async (btn) => {
        if(isLoading) return;

        let code = $("#login_2fa_code").val();
        if(!code || code.trim() === '' || code.length !== 6) {
            showErrorNotification("Fejl", "Indtast 6-cifret kode");
            return false;
        }

        let form = btn.parents('form').first();
        btn.get(0).disabled = true;
        isLoading = true;

        let result = await post(platformLinks.api.auth.verify2faLogin, { code: code });
        console.log(result);

        isLoading = false;

        if(result.status === 'error') {
            btn.get(0).disabled = false;
            showErrorNotification("Verificering fejlede", result.error.message);
            return false;
        }

        queueNotificationOnLoad("Log ind succesfuldt", result.message, 'success', 2000)

        // Check if form requests page reload instead of redirect (e.g., in checkout flow)
        if(form.data('reload-on-success') === true) {
            window.location.reload();
            return;
        }

        handleStandardApiRedirect(result, 1)
    }

    const resend2faCode = async (e) => {
        e.preventDefault();
        if(isLoading) return;

        isLoading = true;

        let result = await post(platformLinks.api.auth.resend2faLoginCode, {});
        console.log(result);

        isLoading = false;

        if(result.status === 'error') {
            showErrorNotification("Kunne ikke sende kode", result.error.message);
            // If session expired, go back to login
            if(result.code === 401) {
                hide2faSection();
            }
            return false;
        }

        showSuccessNotification("Kode sendt", result.message);
        // Update phone hint in case it changed
        if(result.data && result.data.phone_hint) {
            $("#login-2fa-phone-hint").text(result.data.phone_hint);
        }
        startTwoFaTimer(60);
    }

    const backToLogin = (e) => {
        e.preventDefault();
        hide2faSection();
    }

    $("button[name=login-button]").on("click", function (e) {
        e.preventDefault();
        loginUser($(this));
    })

    $("button[name=verify-2fa-button]").on("click", function (e) {
        e.preventDefault();
        verify2fa($(this));
    })

    $("#login-2fa-resend-link").on("click", resend2faCode);
    $("#login-2fa-back-link").on("click", backToLogin);

    // Cleanup timer on page unload
    $(window).on('beforeunload', function() {
        if (twoFaTimer) clearInterval(twoFaTimer);
    })
}
loginHandler();


const merchantSignupHandler = () => {
    let isLoading = false;

    const signupMerchant = async (btn) => {
        if(isLoading) return;

        let form = btn.parents('form').first();
        let formData = new FormData(form.get(0));
        let dest = form.attr("action");

        // Get password fields
        let password = formData.get('password');
        let passwordConfirm = formData.get('password_confirm');

        // Validate password match
        if(password !== passwordConfirm) {
            showErrorNotification("Fejl", "Adgangskoderne matcher ikke");
            return false;
        }

        // Validate terms acceptance
        let acceptTerms = form.find("input[name=accept_terms]").is(":checked");
        if(!acceptTerms) {
            showErrorNotification("Fejl", "Du skal acceptere vilkår og betingelser");
            return false;
        }

        btn.get(0).disabled = true;
        isLoading = true;

        let result = await post(dest, formData);
        console.log(result);

        if(result.status === 'error') {
            btn.get(0).disabled = false;
            isLoading = false;
            showErrorNotification("Kunne ikke oprette konto", result.error.message);
            return false;
        }

        queueNotificationOnLoad("Konto oprettet", result.message, 'success', 2000);
        handleStandardApiRedirect(result, 1);
    }

    $("button[name=signup-button]").on("click", function (e) {
        e.preventDefault();
        signupMerchant($(this));
    })
}
merchantSignupHandler();


const consumerCompleteProfileHandler = () => {
    let isLoading = false;
    let isVerified = false;
    let lastVerifiedPhone = '';
    let lastVerifiedCountryCode = '';
    let sendCodeTimer = null;
    let sendCodeCooldownEnd = 0;
    let verifyCodeTimer = null;
    let verifyCodeCooldownEnd = 0;

    const startSendCodeTimer = (seconds = 60) => {
        sendCodeCooldownEnd = Date.now() + (seconds * 1000);

        // Hide "try new number" link and show timer
        $("#try-new-number-link").addClass("d-none");
        $("#send-code-timer-display").removeClass("d-none");

        const updateTimer = () => {
            let remaining = Math.ceil((sendCodeCooldownEnd - Date.now()) / 1000);
            if (remaining <= 0) {
                clearInterval(sendCodeTimer);
                sendCodeTimer = null;
                sendCodeCooldownEnd = 0;
                $("#send-code-button span").first().text("Send kode");
                $("#send-code-button").get(0).disabled = false;

                // Hide timer and show "try new number" link
                $("#send-code-timer-display").addClass("d-none");
                $("#try-new-number-link").removeClass("d-none");
            } else {
                $("#send-code-button span").first().text(`Vent ${remaining}s`);
                $("#send-code-button").get(0).disabled = true;
                $("#send-timer-countdown").text(remaining);
            }
        };

        updateTimer();
        sendCodeTimer = setInterval(updateTimer, 1000);
    }

    const startVerifyCodeTimer = (seconds = 30) => {
        verifyCodeCooldownEnd = Date.now() + (seconds * 1000);

        const updateTimer = () => {
            let remaining = Math.ceil((verifyCodeCooldownEnd - Date.now()) / 1000);
            if (remaining <= 0) {
                clearInterval(verifyCodeTimer);
                verifyCodeTimer = null;
                verifyCodeCooldownEnd = 0;
                $("#verify-code-button span").first().text("Verificer");
                $("#verify-code-button").get(0).disabled = false;
            } else {
                $("#verify-code-button span").first().text(`Vent ${remaining}s`);
                $("#verify-code-button").get(0).disabled = true;
            }
        };

        updateTimer();
        verifyCodeTimer = setInterval(updateTimer, 1000);
    }

    const resetVerificationUI = () => {
        isVerified = false;
        $("#verification-success").addClass("d-none");
        $("#verification-code-section").addClass("d-none");
        $("#verification_code").val('');
        $("#phone-input-section").show();
    }

    const tryNewNumber = (e) => {
        e.preventDefault();

        // Fade out verification section, fade in phone input section
        $("#verification-code-section").fadeOut(300, function() {
            $("#verification-code-section").addClass("d-none");
            $("#phone-input-section").hide().fadeIn(300);
            $("#verification_code").val('');
            $("#phone").focus();

            // Hide timer display and try new number link when going back
            $("#send-code-timer-display").addClass("d-none");
            $("#try-new-number-link").addClass("d-none");
        });
    }

    const showVerifiedUI = () => {
        isVerified = true;
        $("#verification-success").removeClass("d-none");
        $("#verification-code-section").addClass("d-none");
        $("#phone-input-section").hide();
    }

    const checkPhoneVerification = async () => {
        let phone = $("#phone").val();
        let phoneCountryCode = $("#phone_country_code").val();

        // If empty, reset
        if(!phone || phone.trim() === '') {
            isVerified = false;
            $("#verification-success").addClass("d-none");
            $("#verification-code-section").addClass("d-none");
            $("#phone-input-section").show();
            return;
        }

        // If same as last verified, keep verified state
        if(phone === lastVerifiedPhone && phoneCountryCode === lastVerifiedCountryCode && isVerified) {
            showVerifiedUI();
            return;
        }

        // Check if this number is already verified
        let result = await post(platformLinks.api.auth.consumerCheckPhoneVerification, {
            phone: phone,
            phone_country_code: phoneCountryCode
        });

        if(result.status === 'success' && result.data?.is_verified) {
            // This number is already verified
            lastVerifiedPhone = phone;
            lastVerifiedCountryCode = phoneCountryCode;
            showVerifiedUI();
        } else {
            // This number is not verified or API call failed
            isVerified = false;
            $("#verification-success").addClass("d-none");
            $("#verification-code-section").addClass("d-none");
            $("#phone-input-section").show();
        }
    }

    const sendVerificationCode = async (btn) => {
        if(isLoading) return;

        let phone = $("#phone").val();
        let phoneCountryCode = $("#phone_country_code").val();
        if(!phone || phone.trim() === '') {
            showErrorNotification("Fejl", "Indtast telefonnummer");
            return false;
        }

        btn.get(0).disabled = true;
        isLoading = true;

        let result = await post(platformLinks.api.auth.consumerSendVerificationCode, {phone: phone, phone_country_code: phoneCountryCode});
        console.log(result);

        isLoading = false;

        if(result.status === 'error') {
            btn.get(0).disabled = false;
            showErrorNotification("Kunne ikke sende kode", result.error.message);
            return false;
        }

        showSuccessNotification("Kode sendt", result.message);

        // Fade out phone input section, fade in verification section
        $("#phone-input-section").fadeOut(300, function() {
            $("#verification-code-section").removeClass("d-none").hide().fadeIn(300);
            $("#verification_code").focus();
        });

        // Start global cooldown timer
        startSendCodeTimer(60);
    }

    const verifyCode = async (btn) => {
        if(isLoading) return;

        let phone = $("#phone").val();
        let phoneCountryCode = $("#phone_country_code").val();
        let code = $("#verification_code").val();

        if(!phone || phone.trim() === '') {
            showErrorNotification("Fejl", "Telefonnummer mangler");
            return false;
        }

        if(!code || code.trim() === '' || code.length !== 6) {
            showErrorNotification("Fejl", "Indtast 6-cifret kode");
            return false;
        }

        btn.get(0).disabled = true;
        isLoading = true;

        let result = await post(platformLinks.api.auth.consumerVerifyCode, {phone: phone, phone_country_code: phoneCountryCode, code: code});
        console.log(result);

        isLoading = false;

        if(result.status === 'error') {
            showErrorNotification("Verificering fejlede", result.error.message);
            // Start global cooldown timer on failed verification attempt
            startVerifyCodeTimer(30);
            return false;
        }

        btn.get(0).disabled = false;
        showSuccessNotification("Verificeret", result.message);
        lastVerifiedPhone = phone;
        lastVerifiedCountryCode = phoneCountryCode;
        showVerifiedUI();
    }

    const completeProfile = async (btn) => {
        if(isLoading) return;

        let phone = $("#phone").val();

        if(!phone || phone.trim() === '') {
            showErrorNotification("Fejl", "Telefonnummer er påkrævet");
            return false;
        }

        if(!isVerified) {
            showErrorNotification("Fejl", "Telefonnummer skal verificeres først");
            return false;
        }

        let form = btn.parents('form').first();
        let formData = new FormData(form.get(0));
        let dest = form.attr("action");

        btn.get(0).disabled = true;
        isLoading = true;

        let result = await post(dest, formData);
        console.log(result);

        if(result.status === 'error') {
            btn.get(0).disabled = false;
            isLoading = false;
            showErrorNotification("Kunne ikke opdatere profil", result.error.message);
            return false;
        }

        queueNotificationOnLoad("Profil opdateret", result.message, 'success', 2000);
        handleStandardApiRedirect(result, 1);
    }

    $("button[name=send-code-button]").on("click", function (e) {
        e.preventDefault();
        sendVerificationCode($(this));
    })

    $("button[name=verify-code-button]").on("click", function (e) {
        e.preventDefault();
        verifyCode($(this));
    })

    $("button[name=complete-profile-button]").on("click", function (e) {
        e.preventDefault();
        completeProfile($(this));
    })

    $("#try-new-number-link").on("click", tryNewNumber)

    // Watch for phone number changes
    $("#phone, #phone_country_code").on("input change", function() {
        checkPhoneVerification();
    })

    // Check initial verification status on page load
    if($("#phone").val() && $("#phone").val().trim() !== '') {
        checkPhoneVerification();
    }

    // Cleanup timers on page unload
    $(window).on('beforeunload', function() {
        if (sendCodeTimer) clearInterval(sendCodeTimer);
        if (verifyCodeTimer) clearInterval(verifyCodeTimer);
    })
}
consumerCompleteProfileHandler();


const changePasswordHandler = () => {
    let isLoading = false;

    const changePassword = async (btn) => {
        if(isLoading) return;

        let form = btn.parents('form').first();
        let newPassword = form.find("#new_password").val();
        let confirmPassword = form.find("#confirm_password").val();

        if(!newPassword || newPassword.trim() === '') {
            showErrorNotification("Fejl", "Indtast ny adgangskode");
            return false;
        }

        if(newPassword.length < 8) {
            showErrorNotification("Fejl", "Adgangskoden skal være mindst 8 tegn");
            return false;
        }

        if(newPassword !== confirmPassword) {
            showErrorNotification("Fejl", "Adgangskoderne matcher ikke");
            return false;
        }

        let formData = new FormData(form.get(0));
        let dest = form.attr("action");

        btn.get(0).disabled = true;
        isLoading = true;

        let result = await post(dest, formData);
        console.log(result);

        if(result.status === 'error') {
            btn.get(0).disabled = false;
            isLoading = false;
            showErrorNotification("Kunne ikke ændre adgangskode", result.error.message);
            return false;
        }

        queueNotificationOnLoad("Adgangskode ændret", result.message, 'success', 2000);
        handleStandardApiRedirect(result, 1);
    }

    $("button[name=change-password-button]").on("click", function (e) {
        e.preventDefault();
        changePassword($(this));
    })
}
changePasswordHandler();