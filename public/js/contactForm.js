



const contactForm = async (btn) => {
    if('contactForm' in applicationProcessing && applicationProcessing.contactForm) return false;
    applicationProcessing.contactForm = true;
    btn.disabled = true
    let form = $(btn).parents('form').first();
    let captchaToken = await captchaGet(form);

    let formData = new FormData(form.get(0))
    formData.append("recaptcha_token", captchaToken)
    let dest = form.attr("action")

    let result = await post(dest, formData);
    console.log(result)

    if(result.status === 'error') {
        btn.disabled = false
        applicationProcessing.contactForm = false;
        showErrorNotification("Der opstod en fejl", result.error.message)
        return false;
    }

    showSuccessNotification("Formularen er blevet sendt", result.message)
    form.get(0).reset();
    btn.disabled = false
    applicationProcessing.contactForm = false;
}