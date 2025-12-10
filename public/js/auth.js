


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



    const loginUser = async (btn) => {
        if(isLoading) return;
        btn.get(0).disabled = true
        let form = btn.parents('form').first();
        let formData = new FormData(form.get(0))
        let dest = form.attr("action")

        let result = await post(dest, formData);
        console.log(result)

        if(result.status === 'error') {
            btn.get(0).disabled = false
            isLoading = false;
            showErrorNotification("Unable to login", result.error.message)
            return false;
        }

        queueNotificationOnLoad("Log ind succesfuldt", result.message, 'success', 2000)
        handleStandardApiRedirect(result, 1)
    }

    $("button[name=login-button]").on("click", function (e) {
        e.preventDefault();
        loginUser($(this));
    })
}
loginHandler();