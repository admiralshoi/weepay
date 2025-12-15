


function handleStandardApiRedirect(result, timeout = 1) {
    let resultData = ('data' in result) ? result.data : result;
    if(typeof resultData !== 'object') return;
    if(('redirect' in resultData) && resultData.redirect) {
        let redirectUrl = ('redirect_uri' in resultData) ? resultData.redirect_uri : window.location.href;
        setTimeout(function () { window.location = redirectUrl; }, timeout)
    }
}

function isMobileDevice() {
    return window.innerWidth < 630 ||
           /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}


document.addEventListener('click', function (e) {
    const overlay = e.target.closest('.video-overlay');
    if (!overlay) return; // not our button

    const wrapper = overlay.closest('.video-wrapper');
    const video = wrapper.querySelector('video');
    const icon = overlay.querySelector('i');

    if (video.paused) {
        video.play();
        icon.classList.remove('mdi-play');
        icon.classList.add('mdi-pause');
    } else {
        video.pause();
        icon.classList.remove('mdi-pause');
        icon.classList.add('mdi-play');
    }
});

function radioSwitchV2(btn) {
    let parent = btn.parents('.radio-switch-v2').first(),
        targetSelector = btn.attr('data-target'),
        customId = parent.attr('data-radio-v2-switch-id');
    if(btn.parents('.radio-switch-v2-item').first().hasClass('show')) return;

    parent.find(`.radio-switch-v2-item[data-radio-v2-switch-id=${customId}]`).each(function () {
        let parentItem = $(this);
        let container = parentItem.find(`.radio-switch-v2-content${targetSelector}`);
        let radio = parentItem.find(`.radio-switch-v2-title input[type=radio]`).first();
        if(container.length) {
            radio.prop('checked', true);
            parentItem.addClass('show')
        }
        else {
            container = parentItem.find(`.radio-switch-v2-content`);
            if(container.length) {
                radio.prop('checked', false);
                parentItem.removeClass('show')
            }
        }
    });
}
$(document).on('click', '.radio-switch-v2-title', function () { radioSwitchV2($(this)) })




function utilQuantityIncrement(btn) {
    let action = btn.data('util-action');
    let targetSelector = btn.data('target');
    let target = $(document).find(targetSelector).first();
    let increase = action === 'increase_quantity';
    if(!target.length) return;

    let minValue = target.data('min')
    let maxValue = target.data('max')
    let increment = target.data('increment')
    let currentValue = parseInt(target.text())

    let nextValue;
    if(increase) {
        nextValue = currentValue + increment;
        if(isNumeric(maxValue) && maxValue < nextValue) return;
    }
    else {
        nextValue = currentValue - increment;
        if(isNumeric(minValue) && minValue > nextValue) return;
    }
    target.text(nextValue)

    let name = targetSelector.replaceAll('.', '')
    name = name.replaceAll('#', '')
    let hiddenElement = $(document).find(`input[name=${name}]`).first()
    if(hiddenElement.length) {
        hiddenElement.val(nextValue)
        hiddenElement.trigger('change')
    }
}
$(document).on('click', '[data-util-action=decrease_quantity], [data-util-action=increase_quantity]', function () { utilQuantityIncrement($(this)) })







async function switchView(btn, select = false) {
    let switchParent = btn.parents("[data-switchParent]").first()
    let switchId = switchParent.attr("data-switch-id")
    let switchObjects = switchParent.find(".switchViewObject[data-switch-id=" + switchId + "]")
    let currentTitleElement = switchParent.find("#switchCurrentTitle").first()

    let activeBtnClass = switchParent.attr("data-active-btn-class")
    let inactiveBtnClass = switchParent.attr("data-inactive-btn-class");
    let switchTarget;
    if(select) switchTarget = btn.val();
    else switchTarget = btn.data("toggle-switch-object");

    if(empty(switchParent, switchObjects)) return false;
    let targetObject = switchParent.find(".switchViewObject[data-switch-object-name="+switchTarget+"]"),
        currentVisibleObject = switchParent.find(".switchViewObject[data-is-shown=true][data-switch-id=" + switchId + "]");

    if(!targetObject.length) return false;
    if(targetObject.attr("data-is-shown") === "true") return false;

    let newTitle = targetObject.data("switch-object-title");
    let btnActiveClass = !empty(activeBtnClass) ? activeBtnClass : "", btnInactiveClass = !empty(inactiveBtnClass) ? inactiveBtnClass : "";

    if(currentTitleElement.length > 0) currentTitleElement.text(newTitle);
    currentVisibleObject.attr("data-is-shown", "false");

    // currentVisibleObject.fadeOut(function (){
    //     targetObject.fadeIn({duration: 350});
    // });
    currentVisibleObject.hide();
    targetObject.show();
    targetObject.attr("data-is-shown", "true");

    if((!empty(activeBtnClass) || !empty(inactiveBtnClass))) {
        switchParent.find(".switchViewBtn[data-switch-id=" + switchId + "]").each(function (){
            if(!empty(btnActiveClass)) $(this).removeClass(btnActiveClass)
            if(!empty(btnInactiveClass)) $(this).addClass(btnInactiveClass);
        });
        if(!empty(btnInactiveClass)) btn.removeClass(btnInactiveClass);
        if(!empty(btnActiveClass)) btn.addClass(btnActiveClass)
    }


    let switchCallback = btn.data('callback')
    if(!empty(switchCallback)) {
        window[switchCallback]();
    }
}


















function createNotification(title, description = '', type = 'neutral', timeout = 5000) {
    const id = 'custom_notif_' + Date.now();
    let notificationClass;
    if (type === 'error') {
        notificationClass = 'custom-pop-notification-error';
    } else if (type === 'success') {
        notificationClass = 'custom-pop-notification-success';
    } else {
        notificationClass = 'custom-pop-notification-neutral';
    }

    const notification = $(`
        <div id="${id}" class="custom-pop-notification ${notificationClass}">
            <div class="custom-pop-close-btn">&times;</div>
            <strong>${title}</strong>
            <div>${description}</div>
        </div>
    `);

    $('#notification-pop-container').append(notification);

    const $notif = $('#' + id);

    // Slide in from bottom (using translateY)
    $notif.css({
        transform: 'translateY(500px)',
        right: 0
    });

    // Animate upward
    setTimeout(() => {
        $notif.animate(
            { dummy: 1 }, // dummy property to use step
            {
                duration: 300,
                step: function (now, fx) {
                    // Animate translateY from 30px to 0px
                    const progress = fx.pos;
                    const translateY = 500 - (500 * progress);
                    $notif.css('transform', `translateY(${translateY}px)`);
                },
                complete: function () {
                    $notif.css('transform', 'translateY(0)');
                }
            }
        );
    }, 10);

    // Slide out to the right
    const removeNotification = () => {
        $notif.animate(
            { right: '-1000px' },
            300,
            function () {
                $notif.remove();
            }
        );
    };

    const timer = setTimeout(removeNotification, timeout);

    $notif.hover(
        function () {
            $(this).find('.custom-pop-close-btn').css('display', 'flex');
        },
        function () {
            $(this).find('.custom-pop-close-btn').hide();
        }
    );

    $notif.find('.custom-pop-close-btn').on('click', function () {
        clearTimeout(timer);
        removeNotification();
    });
}

function showNeutralNotification(title, description = '', timeout = 5000) {
    createNotification(title, description, 'neutral', timeout);
}
function showErrorNotification(title, description = '', timeout = 5000) {
    createNotification(title, description, 'error', timeout);
}
function showSuccessNotification(title, description = '', timeout = 5000) {
    createNotification(title, description, 'success', timeout);
}




function customTableSearch() {
    const searchInputs = document.querySelectorAll('input[data-target].custom-table-search');

    if (!searchInputs.length) return; // No search inputs found → do nothing

    searchInputs.forEach(searchInput => {
        const targetSelector = searchInput.getAttribute('data-target');
        const tables = document.querySelectorAll(targetSelector);
        if (!tables) return; // No table found for this input → skip



        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();


            tables.forEach(table => {
                const tbody = table.querySelector('tbody');
                if (!tbody) return;

                tbody.querySelectorAll('tr').forEach(row => {
                    let match = false;

                    row.querySelectorAll('td').forEach(cell => {
                        // Prefer data-sort value over text
                        const value = cell.getAttribute('data-sort') || cell.textContent;
                        if (value && value.toLowerCase().includes(query)) {
                            match = true;
                        }
                    });

                    row.style.display = match ? '' : 'none';
                });
            });
        });
    });
}



async function captchaGet(form) {
    if(!('grecaptcha' in window && typeof grecaptcha === 'object')) return null;
    if(form.hasClass('recaptcha')) return await captchaValidate('submit')
    return null;
}
async function captchaValidate(action = 'submit') {
    return new Promise(resolve => {
        grecaptcha.ready(function() {
            grecaptcha.execute(RECAPTCHA_PK, { action })
                .then(function(token) {
                    resolve(token);
                })
                .catch(() => resolve(null))
        });
    });
}










var isSwitching = false;
$(document).on("click", ".switchViewBtn", function (){

    if(isSwitching) return false;
    isSwitching = true;
    switchView($(this))
        .then(() => { isSwitching = false; })
        .catch(() => { isSwitching = false; })
})
$(document).on("change", ".switchViewSelect", function (){
    if(isSwitching) return false;
    isSwitching = true;
    switchView($(this), true)
        .then(() => { isSwitching = false; })
        .catch(() => { isSwitching = false; })
})