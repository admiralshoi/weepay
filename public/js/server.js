async function get(path, params = {}) {
    try {
        let liveDbToken = findGetParameter("live_db");
        if(!empty(liveDbToken)) {
            params["live_db"] = liveDbToken;
        }

        return await $.ajax({
            url: serverHost + path,
            type: "GET",
            data: params,
            beforeSend: function(xhr) {
                if (('testingCredAuth' in window) && testingCredAuth !== null)
                    xhr.setRequestHeader('Authorization', 'Basic ' + btoa(testingCredAuth));
                xhr.setRequestHeader('Request-Type', 'api');
            },
            error: function(xhr, textStatus, errorThrown) {
                const httpStatus = xhr.status;
                console.log(`XHR request error code: ${xhr.status}`)
                console.error(`GET request to ${path} failed:`, textStatus, errorThrown);

                let errorData;
                if (httpStatus === 0 || (textStatus === "error" && errorThrown === "Network Error")) {
                    showOfflineNotification();
                    return { error: "No internet connection", status: 0 };
                } else if (httpStatus === 401) {
                    modalAuthentication()
                    errorData = { status: "error", error: {message: "Requires authentication", code: httpStatus }}; // Fallback
                } else if (xhr.responseJSON && Object.keys(xhr.responseJSON).length > 0) {
                    errorData = xhr.responseJSON; // Return JSON data if available
                } else if (xhr.responseText) {
                    errorData = xhr.responseText; // Return raw text if no JSON
                } else {
                    errorData = { status: "error", error: {message: "Unknown error", code: httpStatus }}; // Fallback
                }
                return errorData;
            }
        });
    } catch (error) {
        console.error(`Error in GET request to ${path}:`, error);
        if(("responseJSON" in error) && !empty(error.responseJSON)) return error.responseJSON;
        return error.responseText
    }
}


async function post(path, data = {}) {
    try {
        let liveDbToken = findGetParameter("live_db");
        if(!empty(liveDbToken)) {
            if(path.includes("?")) path += "&live_db=" + liveDbToken;
            else path += "?live_db=" + liveDbToken;
        }

        let url = path;
        if(!url.includes("https://")) url = serverHost + url;
        return await $.ajax({
            url,
            type: "POST",
            data: data instanceof FormData ? data : JSON.stringify(data),
            contentType: data instanceof FormData ? false : "application/json", // Use false for FormData, JSON for plain objects
            processData: !(data instanceof FormData),
            beforeSend: function(xhr) {
                if (('testingCredAuth' in window) && testingCredAuth !== null)
                    xhr.setRequestHeader('Authorization', 'Basic ' + btoa(testingCredAuth));

                xhr.setRequestHeader('Request-Type', 'api');
            },
            error: function(xhr, textStatus, errorThrown) {
                const httpStatus = xhr.status;
                console.log(`XHR request error code: ${xhr.status}`)
                console.error(`POST request to ${path} failed:`, textStatus, errorThrown);

                let errorData;
                if (httpStatus === 0 || (textStatus === "error" && errorThrown === "Network Error")) {
                    showOfflineNotification();
                    return { error: "No internet connection", status: 0 };
                } else if (xhr.responseJSON && Object.keys(xhr.responseJSON).length > 0) {
                    errorData = xhr.responseJSON; // Return JSON data if available
                } else if (xhr.responseText) {
                    errorData = xhr.responseText; // Return raw text if no JSON
                } else {
                    errorData = { status: "error", error: {message: "Unknown error", code: httpStatus }}; // Fallback
                }
                return errorData;
            }
        });
    } catch (error) {
        console.error(`Error in POST request to ${path}:`, error);
        if(("responseJSON" in error) && !empty(error.responseJSON)) return error.responseJSON;
        return error.responseText
    }
}
async function del(path, params = {}) {
    try {
        // Append live_db token if present
        let liveDbToken = findGetParameter("live_db");
        if (!empty(liveDbToken)) params["live_db"] = liveDbToken;

        // Build query string if params exist
        const queryString = Object.keys(params).length > 0
            ? "?" + new URLSearchParams(params).toString()
            : "";

        let url = path + queryString;
        if (!url.includes("https://")) url = serverHost + url;

        return await $.ajax({
            url,
            type: "DELETE",
            processData: true,          // we’re not sending FormData or JSON
            contentType: false,         // no Content-Type header, since there’s no body
            beforeSend: function(xhr) {
                // Optional Basic Auth for testing
                if (('testingCredAuth' in window) && testingCredAuth !== null)
                    xhr.setRequestHeader('Authorization', 'Basic ' + btoa(testingCredAuth));

                xhr.setRequestHeader('Request-Type', 'api');
            },
            error: function(xhr, textStatus, errorThrown) {
                const httpStatus = xhr.status;
                console.log(`XHR request error code: ${xhr.status}`);
                console.error(`DELETE request to ${path} failed:`, textStatus, errorThrown);

                let errorData;
                if (httpStatus === 0 || (textStatus === "error" && errorThrown === "Network Error")) {
                    showOfflineNotification();
                    return { error: "No internet connection", status: 0 };
                } else if (xhr.responseJSON && Object.keys(xhr.responseJSON).length > 0) {
                    errorData = xhr.responseJSON;
                } else if (xhr.responseText) {
                    errorData = xhr.responseText;
                } else {
                    errorData = { status: "error", error: { message: "Unknown error", code: httpStatus }};
                }
                return errorData;
            }
        });

    } catch (error) {
        console.error(`Error in DELETE request to ${path}:`, error);
        if (("responseJSON" in error) && !empty(error.responseJSON)) return error.responseJSON;
        return error.responseText;
    }
}




function showOfflineNotification() {
    if (!$('#offlineModal').length) {
        $('body').append(`
            <div id="offlineModal" class="offline-overlay">
                <div class="offline-container">
                    <span class="offline-icon">📶</span>
                    <h2 class="offline-heading">No Internet Connection</h2>
                    <p class="offline-message">It looks like you’ve lost your internet connection. Please check your network and try again.</p>
                    <button class="offline-btn" onclick="retryConnection()">Retry</button>
                </div>
            </div>
        `);
    }
    $('#offlineModal').show();
}

function retryConnection() {
    $('#offlineModal').hide();
    screenLoader.show("Retrying connection...")
    setTimeout(function () {
        if (navigator.onLine) {
            get("api/connection/test")
                .then(() => screenLoader.hide())
                .catch(() => screenLoader.hide())
        } else {
            showOfflineNotification()
            screenLoader.hide();
        }
    }, 750)
}


async function modalAuthentication() {
    const loadedScripts = {
        'auth.js': false,
        'x': false,
        'y': false
    };
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (loadedScripts[src]) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Ensure scripts load in order if needed
            script.onload = () => {
                loadedScripts[src] = true;
                resolve();
            };
            script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
            document.head.appendChild(script);
        });
    }

    async function ensureScriptsLoaded() {
        try {
            const scripts = [
                serverHost + '/public/js/auth.js',
                serverHost + '/public/js/modalHandler.js',
                serverHost + '/public/js/includes/handleBars.js',
            ];

            // Load scripts in parallel (or sequentially if order matters)
            await Promise.all(scripts.map(script => loadScript(script)));
            console.log('All scripts loaded successfully');
        } catch (error) {
            console.error('Error loading scripts:', error);
            throw error;
        }
    }


    await ensureScriptsLoaded();

    let template = "authenticate";
    const modalOnClose = (modalHandler) => {
        modalHandler.redirectPage()
        modalHandler.dispose();
    }
    let modal = new ModalHandler(template)
    modal.construct({redirect_uri: window.location.href})
    await modal.build()
    modal.bindEvents({onclose: modalOnClose})
    modal.open()







}
