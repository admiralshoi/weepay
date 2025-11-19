class SweetPrompt {
    /**
     * Displays a SweetAlert2 confirmation prompt with optional input,
     * and handles async processing with success/error flows.
     *
     * @function
     * @param {string} title - Title of the initial confirmation prompt.
     * @param {string} message - Message text or HTML (controlled by `useHtml`).
     * @param {Object} [options] - Configuration object.
     * @param {function} options.onConfirm - Async callback fired on confirmation. Must return a Promise resolving to a response object.
     * @param {string|null} [options.input=null] - Type of input ('text', 'email', 'number', etc.) or null for no input.
     * @param {boolean} [options.inputOptional=false] - If true, allows input to be empty.
     * @param {Object} [options.success={}] - Options for the success alert (title, text, icon, html).
     * @param {Object} [options.error={}] - Options for the error alert (title, text, icon, html). Use `<_ERROR_MSG_>` to inject error string.
     * @param {number|null} [options.refreshTimeout=null] - If set, triggers page reload after success, delayed by milliseconds.
     * @param {boolean} [options.useHtml=false] - If true, treats the main message as HTML instead of plain text.
     * @param {function|null} [options.callbackError=null] - Callback triggered on cancellation or promise rejection.
     * @param {boolean} [options.refireAfter=true] - If true, skips showing success/error SweetAlert after the async callback.
     * @param {string} [options.confirmButtonText='Confirm'] - Custom text for the confirm button.
     *
     * @example
     * SweetPrompt.confirm("Delete item?", "This cannot be undone.", {
     *   onConfirm: async () => {
     *     const res = await api.deleteItem(42);
     *     return {
     *       status: res.ok ? "success" : "error",
     *       error: res.error,
     *       resultCallback: () => console.log("Done")
     *     };
     *   },
     *   success: { title: "Deleted!", text: "The item was removed." },
     *   error: { title: "Failed", text: "Could not delete: <_ERROR_MSG_>" }
     * });
     */

    static confirm(title, message, {
        onConfirm,
        input = null,
        inputOptional = false,
        success = {},
        error = {},
        refreshTimeout = null,
        useHtml = false,
        callbackEnd = null,
        refireAfter = true,
        confirmButtonText = 'Confirm'
    } = {}) {
        const preOptions = {
            title,
            icon: 'warning',
            confirmButtonText,
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonColor: '#3085d6'
        };

        if (input) {
            preOptions.input = input;
            if (inputOptional) {
                preOptions.inputValidator = () => null;
            }
        }

        if (useHtml) {
            preOptions.html = message;
        } else {
            preOptions.text = message;
        }

        Swal.fire(preOptions).then(result => {
            if (result.dismiss) {
                if (typeof callbackEnd === 'function') callbackEnd(result);
                return;
            }

            const inputValid = !input || inputOptional || (result.value && result.value !== '');
            if (!inputValid) {

                if (typeof callbackEnd === 'function') callbackEnd(result);
                return Swal.fire({
                    icon: 'error',
                    title: 'Missing input',
                    text: 'You must provide a value.'
                });
            }

            if (typeof onConfirm !== 'function') {
                if (typeof callbackEnd === 'function') callbackEnd(result);
                return;
            }

            // Swal.fire({
            //     title: 'Processing...',
            //     allowOutsideClick: false,
            //     didOpen: () => Swal.showLoading()
            // });

            Promise.resolve(onConfirm(result))
                .then(response => {
                    Swal.close();
                    if(refireAfter) {
                        if (typeof callbackEnd === 'function') callbackEnd(result);
                        return;
                    }

                    if (!refireAfter) {
                        if (typeof response.resultCallback === 'function') {
                            response.resultCallback(response);
                        }
                        if (refreshTimeout && response.status === 'success') {
                            setTimeout(() => {
                                window.location.href = window.location.href.replace("#", "");
                            }, refreshTimeout);
                        } else if (response.redirect && response.status === 'success') {
                            window.location.href = response.redirect;
                        }

                        if (typeof callbackEnd === 'function') callbackEnd(result);
                        return;
                    }

                    const status = response.status || 'error';
                    const defaultSuccess = {
                        title: 'Success',
                        text: '',
                        icon: 'success'
                    };
                    const defaultError = {
                        title: 'Error',
                        text: `Something went wrong.\n${response.error || ''}`,
                        icon: 'error'
                    };

                    const swalOpts = status === 'success'
                        ? { ...defaultSuccess, ...success }
                        : { ...defaultError, ...error };

                    if (swalOpts.text?.includes("<_ERROR_MSG_>") && response.error) {
                        swalOpts.text = swalOpts.text.replace("<_ERROR_MSG_>", response.error);
                    }

                    Swal.fire(swalOpts).then(() => {
                        if (typeof response.resultCallback === 'function') {
                            response.resultCallback(response);
                        }

                        if (refreshTimeout && status === 'success') {
                            setTimeout(() => {
                                window.location.href = window.location.href.replace("#", "");
                            }, refreshTimeout);
                        } else if (response.redirect && status === 'success') {
                            window.location.href = response.redirect;
                        }
                    });
                })
                .catch(err => {
                    Swal.close();
                    if (typeof callbackEnd === 'function') callbackEnd(err);
                    if(refireAfter) {
                        return;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: error.title || 'Error',
                        text: (error.text || 'Something went wrong.').replace("<_ERROR_MSG_>", err.message || "")
                    });

                    if (refreshTimeout) {
                        setTimeout(() => {
                            window.location.href = window.location.href.replace("#", "");
                        }, refreshTimeout);
                    }
                });
        });
    }
}
