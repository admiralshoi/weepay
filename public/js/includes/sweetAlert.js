function swalConfirmCancel(args={}) {
    if(!(("callback" in args) && ("visualText" in args))) return false;

    let visual = args.visualText, timeOut = ('refreshTimeout' in args) ? args.refreshTimeout : false;
    if(!("preFireText" in visual && "successText" in visual && "errorText" in visual)) return false;
    let callback = args.callback;

    let preFireText = visual.preFireText,
        successText = visual.successText,
        errorText = visual.errorText,
        isExpectingInput = ("input" in preFireText);
    let inputOptional = (('inputOptional' in preFireText) && preFireText.inputOptional);

    Swal.fire({
        ...{
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        },
        ...preFireText
    })
        .then(result => {
            let swalNewValues={};
            if(("dismiss" in result) && ["cancel", "backdrop", "esc"].includes(result.dismiss)) {
                if("callbackError" in args) args.callbackError(result);
            }
            else if (result.value || ((isExpectingInput && result.value !== "") || inputOptional)) {

                callback(result)
                    .then((response) => {
                        let doRefresh = true;
                        let resultCallback = ('resultCallback' in response) ? response.resultCallback : null;
                        let swalOptionsResponse = {};
                        if(resultCallback === null && ("swalOptions" in response)) swalOptionsResponse = response.swalOptions;
                        else if(resultCallback !== null && typeof resultCallback === "object" && ("swalOptions" in resultCallback)) swalOptionsResponse = resultCallback.swalOptions;


                        console.log([response, resultCallback])

                        switch (response.status) {
                            case "success":
                                swalNewValues.title = ("successTitle" in swalOptionsResponse) ? swalOptionsResponse.successTitle : successText.title;
                                swalNewValues.text = ("successText" in swalOptionsResponse) ? swalOptionsResponse.successText : successText.text;
                                swalNewValues.icon = ("successIcon" in swalOptionsResponse) ? swalOptionsResponse.successIcon : successText.icon;
                                swalNewValues.html = ("successHtml" in swalOptionsResponse) ? swalOptionsResponse.successHtml : successText.html;
                                break;
                            case "error":
                                swalNewValues.title = ("errorTitle" in swalOptionsResponse) ? swalOptionsResponse.errorTitle : errorText.title;
                                swalNewValues.text = ("errorText" in swalOptionsResponse) ? swalOptionsResponse.errorText : (errorText.text.replace("<_ERROR_MSG_>",response.error));
                                swalNewValues.icon = ("errorIcon" in swalOptionsResponse) ? swalOptionsResponse.errorIcon : errorText.icon;
                                swalNewValues.html = ("errorHtml" in swalOptionsResponse) ? swalOptionsResponse.errorHtml : (errorText.html.replace("<_ERROR_MSG_>",response.error));

                                doRefresh = false;
                                break;
                            default:
                                swalNewValues.title = "Unknown error";
                                swalNewValues.text = "Something unexpected happened";
                                swalNewValues.icon = "error";
                                swalNewValues.html = "";

                                doRefresh = false;
                        }
                        Swal.fire(swalNewValues)
                            .then(() => {
                                if(resultCallback !== null) resultCallback(response);
                                if(timeOut !== false && doRefresh) {
                                    setTimeout(function () {
                                        window.location = (window.location.href).replace("#","")
                                    },timeOut)
                                }
                            });
                    })
                    .catch((response) => {
                        if("callbackError" in args) args.callbackError(response);
                        if(timeOut !== false) {
                            setTimeout(function () {
                                window.location = (window.location.href).replace("#","")
                            },timeOut)
                        }
                    });
            }
            else {
                if("callbackError" in args) args.callbackError(result);
                swalNewValues.title = "Error";
                swalNewValues.text = isExpectingInput ? "Input value cannot be empty" : "Unknown error";
                swalNewValues.icon = "error";
                swalNewValues.html = "";
                Swal.fire(swalNewValues)

            }

            isMigrating = false;
        })
        .catch(result => {
            if("callbackError" in args) args.callbackError(result);
            console.log(result);
        })
}