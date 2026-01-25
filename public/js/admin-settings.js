/**
 * Admin Settings Page JavaScript
 */

// Phone verification state
let phoneVerified = false;
let sendCodeTimer = null;
let sendCodeCountdown = 0;

// Get elements
const phoneInput = document.getElementById('settings_phone');
const phoneCountrySelect = document.getElementById('settings_phone_country_code');
const sendCodeBtn = document.getElementById('settings-send-code-button');
const verificationSection = document.getElementById('settings-verification-code-section');
const verificationCodeInput = document.getElementById('settings_verification_code');
const verifyCodeBtn = document.getElementById('settings-verify-code-button');
const verificationSuccess = document.getElementById('settings-verification-success');
const phoneInputSection = document.getElementById('phone-input-section');
const timerDisplay = document.getElementById('settings-send-code-timer-display');
const timerCountdown = document.getElementById('settings-timer-countdown');
const tryNewNumberLink = document.getElementById('settings-try-new-number-link');
const phoneVerifiedInput = document.getElementById('settings_phone_verified');

// Check if phone has changed
function hasPhoneChanged() {
    const currentPhone = phoneInput.value.trim().replace(/[^0-9]/g, '');
    const currentCountryCode = phoneCountrySelect.value;
    const origPhone = originalPhone.replace(/[^0-9]/g, '');

    // If phone is empty, no verification needed (removal is allowed)
    if (!currentPhone) return false;

    // Check if either phone or country code changed
    return currentPhone !== origPhone || currentCountryCode !== originalPhoneCountryCode;
}

// Update UI based on phone change state
function updatePhoneUI() {
    const changed = hasPhoneChanged();
    const currentPhone = phoneInput.value.trim();

    if (changed && currentPhone && !phoneVerified) {
        sendCodeBtn.classList.remove('d-none');
    } else {
        sendCodeBtn.classList.add('d-none');
    }

    // If phone changed back to original or verified, hide verification section
    if (!changed || phoneVerified) {
        verificationSection.classList.add('d-none');
    }
}

// Listen for phone input changes
phoneInput.addEventListener('input', function() {
    phoneVerified = false;
    phoneVerifiedInput.value = '0';
    verificationSuccess.classList.add('d-none');
    updatePhoneUI();
});

// Listen for country code changes (select-v2 uses change event)
if (phoneCountrySelect) {
    phoneCountrySelect.addEventListener('change', function() {
        phoneVerified = false;
        phoneVerifiedInput.value = '0';
        verificationSuccess.classList.add('d-none');
        updatePhoneUI();
    });
}

// Send verification code
sendCodeBtn.addEventListener('click', async function() {
    const phone = phoneInput.value.trim().replace(/[^0-9]/g, '');
    const countryCode = phoneCountrySelect.value;

    if (!phone || phone.length < 8) {
        showErrorNotification('Fejl', 'Indtast et gyldigt telefonnummer (mindst 8 cifre)');
        return;
    }

    sendCodeBtn.disabled = true;
    sendCodeBtn.classList.add('loading');

    const result = await post(platformLinks.api.auth.consumerSendVerificationCode, {
        phone: phone,
        phone_country_code: countryCode,
        purpose: 'phone_verification'
    });

    sendCodeBtn.disabled = false;
    sendCodeBtn.classList.remove('loading');

    if (result.status === 'success') {
        showSuccessNotification('Kode sendt', 'En bekræftelseskode er sendt til dit telefonnummer');
        sendCodeBtn.classList.add('d-none');
        verificationSection.classList.remove('d-none');
        verificationCodeInput.value = '';
        verificationCodeInput.focus();

        // Start countdown timer
        startSendCodeTimer(60);
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Kunne ikke sende bekræftelseskode');
    }
});

// Verify code
verifyCodeBtn.addEventListener('click', async function() {
    const code = verificationCodeInput.value.trim();
    const phone = phoneInput.value.trim().replace(/[^0-9]/g, '');
    const countryCode = phoneCountrySelect.value;

    if (!code || code.length !== 6) {
        showErrorNotification('Fejl', 'Indtast den 6-cifrede bekræftelseskode');
        return;
    }

    verifyCodeBtn.disabled = true;
    verifyCodeBtn.classList.add('loading');

    const result = await post(platformLinks.api.auth.consumerVerifyCode, {
        phone: phone,
        phone_country_code: countryCode,
        code: code,
        purpose: 'phone_verification'
    });

    verifyCodeBtn.disabled = false;
    verifyCodeBtn.classList.remove('loading');

    if (result.status === 'success') {
        phoneVerified = true;
        phoneVerifiedInput.value = '1';
        verificationSection.classList.add('d-none');
        verificationSuccess.classList.remove('d-none');
        showSuccessNotification('Bekræftet', 'Dit telefonnummer er nu bekræftet');
        stopSendCodeTimer();
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Ugyldig eller udløbet kode');
    }
});

// Try new number link
tryNewNumberLink.addEventListener('click', function(e) {
    e.preventDefault();
    verificationSection.classList.add('d-none');
    sendCodeBtn.classList.remove('d-none');
    phoneInput.focus();
    stopSendCodeTimer();
});

// Timer functions
function startSendCodeTimer(seconds) {
    sendCodeCountdown = seconds;
    timerCountdown.textContent = sendCodeCountdown;
    timerDisplay.classList.remove('d-none');
    tryNewNumberLink.classList.add('d-none');

    sendCodeTimer = setInterval(function() {
        sendCodeCountdown--;
        timerCountdown.textContent = sendCodeCountdown;

        if (sendCodeCountdown <= 0) {
            stopSendCodeTimer();
            timerDisplay.classList.add('d-none');
            tryNewNumberLink.classList.remove('d-none');
        }
    }, 1000);
}

function stopSendCodeTimer() {
    if (sendCodeTimer) {
        clearInterval(sendCodeTimer);
        sendCodeTimer = null;
    }
}

// Profile Form Handler
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const currentPhone = phoneInput.value.trim().replace(/[^0-9]/g, '');

    // Check if phone changed and needs verification
    if (hasPhoneChanged() && currentPhone && !phoneVerified) {
        showErrorNotification('Bekræftelse påkrævet', 'Du skal bekræfte dit nye telefonnummer før du kan gemme');
        return;
    }

    const btn = document.getElementById('saveProfileBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    const result = await post(platformLinks.api.user.updateProfile, data);

    btn.disabled = false;
    btn.innerHTML = originalText;

    if(result.status === 'success') {
        showSuccessNotification('Udført', result.message);
        setTimeout(() => window.location.reload(), 1500);
    } else {
        showErrorNotification('Der opstod en fejl', result.error.message);
    }
});

// Address Form Handler
document.getElementById('addressForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveAddressBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    const result = await post(platformLinks.api.user.updateAddress, data);

    btn.disabled = false;
    btn.innerHTML = originalText;

    if(result.status === 'success') {
        showSuccessNotification('Udført', result.message);
    } else {
        showErrorNotification('Der opstod en fejl', result.error.message);
    }
});

// Password Form Handler
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('savePasswordBtn');
    const originalText = btn.innerHTML;

    const password = this.querySelector('[name="password"]').value;
    const passwordConfirm = this.querySelector('[name="password_confirm"]').value;

    if(password !== passwordConfirm) {
        showErrorNotification('Fejl', 'Adgangskoder matcher ikke');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    const result = await post(platformLinks.api.user.updatePassword, data);

    btn.disabled = false;
    btn.innerHTML = originalText;

    if(result.status === 'success') {
        showSuccessNotification('Udført', result.message);
        setTimeout(() => window.location.reload(), 1500);
    } else {
        showErrorNotification('Der opstod en fejl', result.error.message);
    }
});

// Username Form Handler
document.getElementById('usernameForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveUsernameBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    const result = await post(platformLinks.api.user.updateUsername, data);

    btn.disabled = false;
    btn.innerHTML = originalText;

    if(result.status === 'success') {
        showSuccessNotification('Udført', result.message);
    } else {
        showErrorNotification('Der opstod en fejl', result.error.message);
    }
});

// Two-Factor Form Handler
document.getElementById('twoFactorForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveTwoFactorBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

    const formData = new FormData(this);
    const data = {
        two_factor_enabled: document.getElementById('twoFactorEnabled').checked ? 1 : 0,
        two_factor_method: formData.get('two_factor_method')
    };

    const result = await post(platformLinks.api.user.updateTwoFactor, data);

    btn.disabled = false;
    btn.innerHTML = originalText;

    if(result.status === 'success') {
        showSuccessNotification('Udført', result.message);
    } else {
        showErrorNotification('Der opstod en fejl', result.error.message);
    }
});
