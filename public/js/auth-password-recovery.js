/**
 * Password Recovery Form Handler
 * Handles the password recovery request form with phone/email detection and rate limiting timer
 */

const passwordRecoveryHandler = () => {
    let isLoading = false;
    let cooldownTimer = null;
    let cooldownEndTime = 0;

    /**
     * Handle showing/hiding country code select based on input
     * Shows country code dropdown when user types a phone number (numeric, >3 chars)
     */
    const handleIdentifierInput = (input) => {
        let value = input.val().trim();
        let countryCodeContainer = input.closest('form').find('.recovery-country-code-container');

        // Check if input is numeric and more than 3 characters
        let isPhoneNumber = value.length > 3 && /^\d+$/.test(value);

        if(isPhoneNumber) {
            countryCodeContainer.removeClass('d-none');
            input.css('width', 'calc(100% - 75px - 2px)');
        } else {
            countryCodeContainer.addClass('d-none');
            input.css('width', '100%');
        }
    };

    /**
     * Start the cooldown timer
     * @param {number} seconds - Number of seconds to wait
     */
    const startCooldownTimer = (seconds) => {
        cooldownEndTime = Date.now() + (seconds * 1000);

        // Show timer display, hide resend link
        $('#recovery-timer-display').removeClass('d-none');
        $('#recovery-resend-link').addClass('d-none');

        // Update button to show waiting state
        const btn = $('#recovery-button');
        btn.get(0).disabled = true;

        const updateTimer = () => {
            let remaining = Math.ceil((cooldownEndTime - Date.now()) / 1000);

            if (remaining <= 0) {
                clearInterval(cooldownTimer);
                cooldownTimer = null;
                cooldownEndTime = 0;

                // Reset button state
                btn.find('span').first().text('Send nulstillingslink');
                btn.get(0).disabled = false;
                isLoading = false;

                // Hide timer, show resend link
                $('#recovery-timer-display').addClass('d-none');
                $('#recovery-resend-link').removeClass('d-none');
            } else {
                // Update button text and timer countdown
                btn.find('span').first().text(`Vent ${remaining}s`);
                $('#recovery-timer-countdown').text(remaining);
            }
        };

        updateTimer();
        cooldownTimer = setInterval(updateTimer, 1000);
    };

    /**
     * Show success state and start timer for potential resend
     */
    const showSuccessState = () => {
        const form = $('#password-recovery-form');

        // Hide form fields, show success message
        form.find('.recovery-form-fields').addClass('d-none');
        form.find('#recovery-success-message').removeClass('d-none');
        form.find('#recovery-success-actions').removeClass('d-none');

        // Show resend section with timer
        form.find('#recovery-resend-section').removeClass('d-none');

        // Start cooldown timer (60 seconds)
        startCooldownTimer(60);
    };

    /**
     * Reset to form state (for resending)
     */
    const resetToFormState = () => {
        const form = $('#password-recovery-form');

        // Show form fields, hide success message
        form.find('.recovery-form-fields').removeClass('d-none');
        form.find('#recovery-success-message').addClass('d-none');
        form.find('#recovery-success-actions').addClass('d-none');

        // Focus on input
        form.find('#identifier').focus();
    };

    /**
     * Submit the password recovery request
     */
    const submitRecoveryRequest = async (btn) => {
        if(isLoading) return;

        // Check if we're still on cooldown
        if (cooldownEndTime > Date.now()) {
            let remaining = Math.ceil((cooldownEndTime - Date.now()) / 1000);
            showErrorNotification('Vent venligst', `Du kan sende et nyt link om ${remaining} sekunder`);
            return;
        }

        let form = btn.closest('form');
        let identifierField = form.find('#identifier');
        let identifier = identifierField.val().trim();

        if(!identifier) {
            showErrorNotification('Fejl', 'Indtast venligst din email eller telefonnummer');
            return;
        }

        // Check if it's a phone number
        let isPhoneNumber = identifier.length > 3 && /^\d+$/.test(identifier);
        let phoneCountryCode = null;

        if(isPhoneNumber) {
            phoneCountryCode = form.find('#recovery_phone_country_code').val();
        }

        // Disable button and show loading
        btn.get(0).disabled = true;
        isLoading = true;

        try {
            let data = {
                identifier: identifier
            };

            if(phoneCountryCode) {
                data.phone_country_code = phoneCountryCode;
            }

            // Get reCAPTCHA token
            let recaptchaToken = await captchaGet(form);
            if(recaptchaToken) {
                data.recaptcha_token = recaptchaToken;
            }

            let result = await post(passwordRecoveryApiUrl, data);

            if(result.status === 'error') {
                // Check if it's a cooldown error
                if (result.cooldown && result.wait_seconds) {
                    // Start timer with remaining seconds from server
                    startCooldownTimer(result.wait_seconds);
                    showErrorNotification('Vent venligst', `Du kan sende et nyt link om ${result.wait_seconds} sekunder`);
                    return;
                }

                // error can be object {message: "..."} or string
                let errorMsg = result.error?.message || result.error || result.message || 'Der opstod en fejl';
                showErrorNotification('Fejl', errorMsg);
                btn.get(0).disabled = false;
                isLoading = false;
                return;
            }

            // Success - show success message and start timer
            showSuccessState();

        } catch(error) {
            console.error('Password recovery error:', error);
            showErrorNotification('Fejl', 'Der opstod en fejl. Prøv venligst igen.');
            btn.get(0).disabled = false;
            isLoading = false;
        }
    };

    // Initialize event listeners
    const init = () => {
        // Handle identifier input changes (phone/email detection)
        $(document).on('input', '.recovery-identifier-field', function() {
            handleIdentifierInput($(this));
        });

        // Handle form submission
        $(document).on('click', '#recovery-button', function(e) {
            e.preventDefault();
            submitRecoveryRequest($(this));
        });

        // Handle resend link click
        $(document).on('click', '#recovery-resend-link', function(e) {
            e.preventDefault();
            // Only allow if not on cooldown
            if (cooldownEndTime <= Date.now()) {
                resetToFormState();
            }
        });

        // Handle Enter key on identifier field
        $(document).on('keypress', '#identifier', function(e) {
            if(e.which === 13) {
                e.preventDefault();
                $('#recovery-button').click();
            }
        });

        // Initialize country code select if present
        let countryCodeSelect = $('#recovery_phone_country_code');
        if(countryCodeSelect.length && typeof selectV2 === 'function') {
            selectV2(countryCodeSelect.get(0));
        }

        // Cleanup timer on page unload
        $(window).on('beforeunload', function() {
            if (cooldownTimer) clearInterval(cooldownTimer);
        });
    };

    return { init };
};

// Initialize on DOM ready
$(document).ready(function() {
    const handler = passwordRecoveryHandler();
    handler.init();
});
