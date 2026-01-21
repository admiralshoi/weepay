

$(document).ready(function () {


});


function appendTableRows(table, data, columnKeys, maxRows = 10, rowClickAction = undefined) {
    if(empty(data) || empty(columnKeys)) {
        table.html("");
        return;
    }

    let html = "", rowCounter = 0;

    for(let dataRow of data) {
        rowCounter++;

        if(rowClickAction !== undefined && Object.keys(window).includes(rowClickAction))
            html += "<tr onClick='" + rowClickAction + "(this)' class='cursor-pointer'>";
        else html += "<tr>";

        for(let key of columnKeys) {
            let value = (key in dataRow) ? dataRow[key] : "";
            html += (key === "action" ? "<td>" : "<td data-key-"+key+"='" + value + "'>") + (key === "action" ? value : prepareProperNameString(value)) + "</td>";
        }

        html += "</tr>";
        if(rowCounter >= maxRows) break;
    }

    table.html(html);
}


function setDataTableNoData(dataTable) {
    dataTable.find("tbody").first().replaceWith($("<tbody></tbody>"));
    setDataTable(dataTable);
}

function setLineChartNoData(chartElement) {
    let data = {
        series: {
            name: "",
            data: []
        },
        labels: [],
        title: "No data available"
    };

    renderCharts(chartElement.get(0), data, "line");
}

function numberWithCommas(x, separator = ",", max = null) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator);
}

function latestVal(arr) {
    if(typeof arr !== "object") return arr;
    let keys = Object.keys(arr), l = (keys.length)-1;
    return arr[keys[l]];
}

function popBoxes(element, propValue = null) {
    if(propValue === null) propValue = !element.is(":checked");

    element.prop("checked",propValue);
}

/**
 * JavaScript version of PHP number_format()
 *
 * @param {number|string} number          The number to format
 * @param {number}        [decimals=0]    Number of decimal places (default 0)
 * @param {string}        [dec_point='.'] Decimal point character (default '.')
 * @param {string}        [thousands_sep=','] Thousands separator (default ',')
 * @returns {string}                      Formatted number string
 *
 * Examples:
 *   phpNumberFormat(1234.5678, 2, '.', ',');   // "1,234.57"
 *   phpNumberFormat(1234.5, 1, ',', ' ');      // "1 234,5"
 *   phpNumberFormat(1234);                     // "1,234"
 */
function phpNumberFormat(number, decimals, dec_point, thousands_sep) {
    if (typeof decimals === 'undefined') decimals = 0;
    if (typeof dec_point === 'undefined') dec_point = '.';
    if (typeof thousands_sep === 'undefined') thousands_sep = ',';

    number = parseFloat(number);
    if (isNaN(number)) return '0';

    const factor = Math.pow(10, decimals);
    number = Math.round(number * factor) / factor;

    const parts = number.toString().split('.');
    let intPart = parts[0];
    let fracPart = parts.length > 1 ? parts[1] : '';

    if (decimals > 0) {
        while (fracPart.length < decimals) fracPart += '0';
    } else {
        fracPart = '';
    }

    const intWithSep = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);

    return intWithSep + (decimals > 0 ? dec_point + fracPart : '');
}


function serializeForm(formElement) {
    return formElement.serializeArray().reduce(function(obj, item) {
        obj[item.name] = item.value;
        return obj;
    }, {});
}

function multiCheckBoxes(masterBox) {
    let parent = masterBox.parents(".dataParentContainer").first(),
        children = parent.find("input[type=checkbox]"), propValue = masterBox.is(":checked");
    if(parent.length === 0 || children.length === 0) return false;

    children.each(function (){
        // if($(this).hasClass("masterBox")) return;
        popBoxes($(this),propValue);
    });
}


function pluralS(count, str) {
    if(isNormalInteger(count)) count = parseInt(count);
    if(typeof count !== "number") return str;

    return (count !== 1 && count !== -1) ? str + "s" : str;
}

function setCaretPosition(el, pos) {
    // Modern browsers
    if (el.setSelectionRange) {
        el.focus();
        el.setSelectionRange(pos, pos);

        // IE8 and below
    } else if (el.createTextRange) {
        var range = el.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}

function scrollToElement(element,time = 1000, yOffset = 0) {
    if(element.length === 1) scrollToY(((element.offset().top) - yOffset),time);
}
function scrollToY(y,time = 1000) {
    $("html, body").animate({
        scrollTop: y
    },time);
}


function trimObjectMaxXValues(object,max) {
    let counter = 1, response = {};
    for(let k in object) {
        if(counter > max)
            break;
        response[k] = object[k];
        counter++;
    }
    return response;
}

function timeAgo(timestamp, phpTime = false) {
    if(isNormalInteger(timestamp)) timestamp = parseInt(timestamp);
    if(phpTime) timestamp *= 1000;

    let timeNow = (new Date()).valueOf(), difference = Math.round((timeNow-timestamp) / 1000),
    hoursFloor = Math.floor(difference / 3600), response, count;

    if((24 * 365) < hoursFloor) { //Year in hours
        count = Math.floor(hoursFloor / (24 * 365));
        response = count + " " + pluralS(count,"year") + " ago";
    }
    else if((24 * 30 * 3) < hoursFloor) { // 3 months in hours (we display days if not greater than 3 months)
        count = Math.floor(hoursFloor / (24 * 30)); // Display in unit of 1 month (not 3 months)
        response = count + " " + pluralS(count,"month") + " ago";
    }
    else if(24 < hoursFloor) { // day in hours
        count = Math.floor(hoursFloor / 24);
        response = count + " " + pluralS(count,"day") + " ago";
    }
    else  {
        if(hoursFloor === 0) {
            count = Math.round(difference / 60);
            response = count + " " + pluralS(count,"minute") + " ago";
        }
        else response = hoursFloor + " " + pluralS(hoursFloor,"hour") + " ago"; // Hours
    }

    return response;
}

function numberFormatting(number) {
    if(isNormalInteger(number)) number = parseFloat(number);
    return new Intl.NumberFormat('us-US').format(number);
}

function dateToHourFormat(time, deliminator = ":") {
    if(isNormalInteger(time)) time = parseInt(time);

    let dateObj = new Date((time * 1000)), hour = (dateObj.getHours()).toString(), min = (dateObj.getMinutes()).toString();
    return (hour.length === 1 ? 0+hour:hour) + deliminator + (min.length === 1 ? 0+min:min);

}
function convertDate(time, getHours = false,monthName = false, dayAndMonth = false, includeSeconds = false) {
    if(isNormalInteger(time)) time = parseInt(time);

    let dateObj = (new Date(time*1000));
    let month = dateObj.getMonth() + 1; //months from 1-12
    let day = dateObj.getDate();
    let year = dateObj.getUTCFullYear();
    if(!monthName) month = month < 10 ? "0"+month : month;
    day = day < 10 ? "0"+day : day;
    let hours = "";

    if(getHours) {
        let hour = (dateObj.getHours()).toString(), min = (dateObj.getMinutes()).toString(), sec = (dateObj.getSeconds()).toString();
        hours += " " + (hour.length === 1 ? 0+hour:hour) + ":"
            + (min.length === 1 ? 0+min:min) + (!includeSeconds ? "" : ":" + (sec.length === 1 ? 0+sec:sec));
    }

    if(dayAndMonth) {
        return !monthName ? day+"-"+month + (getHours ? ", " + hours : "") :
            monthNames[(month - 1)] + "-" + day + (getHours ? ", " + hours : "");
    }

    return !monthName ? day+"-"+month+"-"+year + hours :
        monthNames[(month - 1)] + "-" + day + "-" + year + (getHours ? ", " + hours : "");
}
const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun","Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];


function compareDateStamps(stamp_1,stamp_2,php=false, od = false) {
    if(php) {
        stamp_1 *= 1000;
        stamp_2 *= 1000;
    }
    let greater, lesser, days;
    if(stamp_1 >= stamp_2) {
        greater = new Date(stamp_1); lesser = new Date(stamp_2);
    } else {
        greater = new Date(stamp_2); lesser = new Date(stamp_1);
    }
    greater.setHours(0,0,0,0);
    lesser.setHours(0,0,0,0);

    return getDateNumbers(greater-lesser, od);
}


function getDateNumbers(timestamp,od= true,dd= false) {
    let date = new Date(timestamp);
    let year = date.getFullYear();
    let month = date.getMonth();
    let day = date.getDate();
    if(dd) { //Double digit
        month = (month + 1) < 10 ? "0"+(month+1) : (month+1);
        day = day < 10 ? "0"+day : day;
    }
    if(od){ //Only days
        year -= 1970;
        if(month > 0)
            day += month*30;
        if(year > 0)
            day += year*30;
    }

    return {y:year.toString(),m:month.toString(),d:day.toString()};
}

function shortNumbByT(number,shortM = true, shortK = false, includeCharSeparate = false) {
    number = typeof number !== "number" ? parseInt(number) : number;
    let mil = 1000000, kilo = 1000,m="M",k="K", response = "";
    if((number >= mil || number <= -mil)  && shortM) {
        let x = (number / mil).toFixed(1);
        response = includeCharSeparate ? {number: x,char:m} : x+m;
    } else if((number >= kilo || number <= -kilo) && shortK) {
        let x = (number / kilo).toFixed(1);
        response = includeCharSeparate ? {number: x,char:k} : x+k;
    } else
        response = number;
    return response;
}

function pairArraysToObject(object1,object2,k=0) {
    let keyObject, valObject,response={};
    if(k === 0) {
        keyObject = object1;
        valObject = object2;
    }
    else {
        keyObject = object2;
        valObject = object1;
    }

    for (let i in keyObject) {
        let item = keyObject[i];
        response[item] = valObject[i];
    }
    return response;
}


function ucFirst(string) {
    return empty(string) ? string : string[0].toUpperCase() +  string.slice(1);
}

function prepareProperNameString(string,UCA = true, UCF = true, ucExceptions = false) {
    if(empty(string)) return string;
    if(typeof string !== "string") string = string.toString();
    string = (string.trim()).replaceAll("_"," ");
    let response = [];
    if(UCA) {
        let words = string.split(" ");
        for(let word of words) {
            let wordV = word.toLowerCase();
            if(ucExceptions && settings.ucExceptions.includes(wordV)) {
                wordV = wordV.toUpperCase();
            } else
                wordV = ucFirst(word);
            response.push(wordV);
        }
    }
    return UCA ? response.join(" ") : (UCF ? ucFirst(string) : string);
}


/**
 * Remove incorrect class active on left-menu links
 */

function setActiveNavItemLabels() {
    let link_href, current_uri, host = serverHost, full_path = window.location.href, activeItem = false //Full window name

    if(!("activePage" in window)) return;
    $("#sidebar a.sidebar-nav-link, #sidebar-admin-panel a.sidebar-nav-link").each(function () {
        link_href = $(this).attr("href");
        current_uri = full_path.replace(host,"");
        let page = $(this).attr("data-page");


        if(activePage === page) {
            $(this).addClass("active");
        }
        else $(this).removeClass("active");
    });
}


function isFloat(n){
    return Number(n) === n && n % 1 !== 0;
}

function isNormalInteger(str) {
    let n = Math.floor(Number(str));
    return n !== Infinity && String(n) === str && n >= 0;
}

function findGetParameter(parameterName) {
    let result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
            tmp = item.split("=");
            if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}


function getKeyByValue(obj, value, searchByValue = true) {
    if(searchByValue)
        return Object.keys(obj).filter(function(key) {return (obj[key].toLowerCase()).includes(value)});
    else
        return Object.keys(obj).filter(function(key) {return (key.toLowerCase()).includes(value)});
}

const unique = (arr) => [...new Set(arr)];

function isAsync(fn) {
    return fn instanceof (async function() {}).constructor;
}

function empty(...args) {
    return args.some(variable =>
        variable === null ||
        variable === "" ||
        typeof variable === "undefined" ||
        variable === undefined ||
        // jQuery object with no elements
        (typeof variable === "object" && variable instanceof jQuery && !variable.length) ||
        // Plain object with no keys
        (typeof variable === "object" && !Array.isArray(variable) && !(variable instanceof Element) && Object.keys(variable).length === 0) ||
        //Normal [] objects
        (typeof variable === "object" && Array.isArray(variable) && !variable.length) ||
        // DOM element that doesn’t exist
        (typeof Element !== "undefined" && variable instanceof Element && !document.body.contains(variable))
    );
}


function ensureString(variable) {
    return typeof variable === "string" ? variable : JSON.stringify(variable);
}
function ensureInteger(n) {
    if(!isNumeric(n)) return 0;
    return typeof n === "number" ? n : parseInt(n);
}

function ensureObject(variable) {
    if(typeof variable === "string") {
        try {
            variable = JSON.parse(variable);
        }
        catch (e) {
            console.error("EnsureObject failed to parse variable (%s). ErrorMsg: %s",variable,e);
            variable = e;
        }
    }
    return variable;
}

const httpBuildQuery = (queryParams) => {
    let esc = encodeURIComponent;
    return Object.keys(queryParams).map(k => esc(k) + '=' + esc(queryParams[k])).join('&');
}


function renderTextWithDots(str,maxLength = 15, dotCount = 3) {
    let dots = "";
    for(let i = 1; i <= dotCount; i++) dots += ".";

    return str.length <= maxLength ? str :
        str.substr(0, (maxLength - dotCount)) + dots;
}

const arrayValues = {
    getLatest: (obj) => {
        if(typeof obj !== "object") return obj;
        return obj [ (Object.keys(obj)[0]) ];
    },
    getNth: (obj,n) => {
        if(typeof obj !== "object" || Object.keys(obj).length < (n-1)) return obj;
        return obj [ (Object.keys(obj)[n]) ];
    },
    getDiff: (obj,n1, n2) => {
        if(typeof obj !== "object" || Object.keys(obj).length < (n1-1) || Object.keys(obj).length < (n2-1)) return 0;
        let values = [obj [ (Object.keys(obj)[n1]) ], obj [ (Object.keys(obj)[n2]) ]];

        if(typeof values[0] !== "number") values[0] = parseInt(values[0]);
        if(typeof values[1] !== "number") values[1] = parseInt(values[1]);

        let diff =  Math.round(values[0] - values[1]);

        return { value:  diff, operator: diff < 0 ? "" : "+" };
    },
    getLast: (obj) => {
        if(typeof obj !== "object") return obj;
        return obj [ (Object.keys(obj)[ (Object.keys(obj).length - 1) ]) ];
    }

};

const intOperator = (num,onlyPositive = true) => {
    num = (typeof num !== "number" && isNormalInteger(num)) ? parseInt(num) : num;
    return num >= 0 ? "+" : (onlyPositive ? "" : "-");
}
function isNumeric(value) {
    return !isNaN(value) && typeof value === 'number' || typeof value === 'string' && value.trim() !== '' && !isNaN(Number(value));
}


function validateEmail(mail) {
    return (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(mail));
}

function firstWord(str) {
    return (typeof str !== "string" || !str.includes(" ")) ? str : ((str.split(" "))[0]);
}


function ePopup(title, msg, hide = 0, type = "error", icon = "error_triangle") {
    let body = $(document).find("body");
    body.find("#top-popup-notify").first().fadeOut().remove();
    if(hide) return;

    let image = "";
    if(icon === "error_triangle") image = errorIcon
    else if(icon === "approve") image = approveIcon
    else if(icon === "email_approve") image = emailApproveIcon

    let html = '<div id="top-popup-notify" style="display: none;">';
        html += '<div class="popup-notify-element ' + type + '">';
            html += '<div class="flex-row-start flex-align-start">';
                html += '<img src="' + image + '" class="square-60 hideOnMobileBlock" />';
                html += '<div class="flex-col-start ml-3">';
                    html += '<p class="font-20 font-weight-bold">' + title + '</p>';
                    html += '<p class="font-14">' + msg + '</p>';
                html += '</div>';
            html += '</div>';
            html += '<div class="flex-row-start flex-align-start square-60 ml-1">';
                html += '<img src="' + closeWhiteIcon + '" class="square-20 cursor-pointer hover-opacity-half close-popup"/>';
            html += '</div>';
        html += '</div>';
    html += '</div>';

    body.append(html);
    body.find("#top-popup-notify").first().fadeIn();
}

function closePopup(btn) {
    btn.parents("#top-popup-notify").first().fadeOut();
    btn.parents("#top-popup-notify").first().remove();
}
function ePopupTimeout(title, message, type = "error", icon = "error_triangle", timeout = 2500) {
    ePopup(title, message, 0, type, icon);
    setTimeout(() => { ePopup("", "",1) }, timeout);
}



function eNotice(msg,parent = null,hide = 0,type = "danger"){
    let parentSelector,
        hideElement = (element) => {
            element.html("");
            element.addClass("hidden")
        },
        showElement = (element,msg) => {
            element.removeClass("hidden")
            element.html(msg);
        };

    if(parent === "HIDE") {
        $(document).find(".eNotice").each(function () {
            if($(this).hasClass("hidden") === false) {
                hideElement($(this));
            }
        });
        return 1;
    }
    if(parent === null) parentSelector = document;
    else parentSelector = parent;
    let E_element = $(parentSelector).find(".eNotice").first();
    let types = {warning:"alert-warning",danger:"alert-danger",success:"alert-success"};
    if(hide === 1) {
        hideElement(E_element);
    } else {
        let errorType = Object.keys(types).includes(type) ? types[type] : type.danger;
        if(E_element.hasClass(errorType) === false){
            for(let a in types){
                if(errorType !== types[a]) {
                    E_element.removeClass(types[a]);
                }
            }
            E_element.addClass(errorType);
        }
        showElement(E_element,msg);
    }
}


function eNoticeTimeout(message, parent, type = "danger", timeout = 5000) {
    eNotice(message, parent,0, type);
    setTimeout(() => { eNotice("",parent,1) }, timeout);
}


function sortByKey(arr,key = "id", ascending = false, key2 = "") {
    arr.sort(function (a, b) {
        if(!(key in a && key in b)) return 0;

        let val1, val2;
        val1 = a[key];
        val2 = b[key];

        if(!empty(key2)) {
            val1 = val1[key2];
            val2 = val2[key2];
        }

        val1 = typeof val1 !== "number" ? parseFloat(val1) : val1;
        val2 = typeof val2 !== "number" ? parseFloat(val2) : val2;
        if (val1 === val2) return 0;
        return (val1 > val2) ? (ascending ? 1 : -1) : (ascending ? -1 : 1) ;
    });

    return arr;
}




/**
 * Pagination generator.
 * Currently supports a grid version only in SET, but simple adjustments to fit others can be made
 * Max pagination: 7
 * @type {{set: paginationGenerator.set, htmlRow: (function(*, *, *): string), gridPaginationClick: paginationGenerator.gridPaginationClick, list: (function(*, *): [])}}
 */
const paginationGenerator = {
    set: (gridContainer,area = 16) => {
        let gridItemClass = "gridItem",
            gridItems = gridContainer.find(`.${gridItemClass}`);
        if(gridItems.length === 0 || gridItems.length <= area) return;

        let pages = Math.ceil(gridItems.length / area), counter = 1, page = 1;

        gridItems.each(function () {
            if(counter > area) {counter = 1; page += 1;}

            $(this).attr("data-page",page);
            counter += 1;
        });

        let paginationRow = $(
            '<div class="w-100 flex-row-end pt-2 pb-2 pr-4 pl-4">'+
            '<div class="flex-row-around" id="paginationRow"><span data-paginator="1"></span></div>'+
            '</div>'
        );

        paginationRow.insertAfter(gridContainer);

        $(paginationRow).off("click").on("click","[data-paginator]",function (){
            let next = $(this).data("paginator");
            if(next === undefined || $(this).hasClass("active")) return;
            paginationGenerator.gridPaginationClick(next,gridItems,paginationRow,pages);
        });
        paginationGenerator.gridPaginationClick(1,gridItems,paginationRow,pages);
    },
    list: (currentPage,lastPage) => {
        let pageList = [], max = 7;
        if(lastPage > max) {
            let equilibrium = (((currentPage - 1) > 3) && ((lastPage - currentPage) > 3)),
                pagStart = ((currentPage - 1) <= 3), pagEnd = ((lastPage - currentPage) <= 3);
            pageList = [
                1,
                (!pagStart) ? "..." : 2,
                (equilibrium) ? (currentPage - 1) : (pagStart ? 3 : (lastPage - 4)),
                (equilibrium) ? currentPage : (pagStart ? 4 : (lastPage - 3)),
                (equilibrium) ? (currentPage + 1) : (pagEnd ? (lastPage - 2) : (1 + 4)),
                (!pagEnd) ? "..." : (lastPage - 1),
                lastPage
            ];
        } else {
            for(let i = 1; i <= max; i++) pageList.push(i);
        }

        return pageList;
    },
    htmlRow: (list,currentPage, lastPage) => {
        let pagination = '<span class="x30-btn cursor-pointer" id="pag_prev" data-paginator="' + (currentPage === 1 ? 1 : (currentPage - 1)) +'">Previous</span>';
        for(let i in list) {
            let p = list[i];

            if(typeof p !== "number") pagination += '<span class="x30-btn">...</span>';
            else pagination += '<span class="x30-btn cursor-pointer ' + (p === currentPage ? "active" : "") + '" data-paginator="' + p +'">' + p +'</span>';
        }
        pagination += '<span class="x30-btn cursor-pointer" id="pag_next" data-paginator="' + (currentPage === lastPage ? lastPage : (currentPage + 1)) +'">Next</span>';

        return pagination
    },
    gridPaginationClick: (next,gridItems,paginationRow,lastPage = 1) => {
        if(paginationRow.find("[data-paginator]").length === 0) return;
        if(paginationRow.find("[data-paginator="+next+"]").hasClass("active")) return;

        let list = paginationGenerator.list(next,lastPage), pagination = paginationGenerator.htmlRow(list,next,lastPage);
        paginationRow.find("#paginationRow").first().html(pagination);

        gridItems.each(function (){
            if($(this).data("page") !== next) $(this).hide();
            else  $(this).show();
        });
    }
};


const copyToClipboard = str => {
    console.log('str to copy:',str)
    let copyString = str.replaceAll("&amp;","&");
    copyString = copyString.replaceAll("&lt;","<");
    copyString = copyString.replaceAll("&gt;",">");
    copyString = copyString.replaceAll("&quot;",'"');
    copyString = copyString.replaceAll("&apos;","'");
    copyString = copyString.toString();
    const el = document.createElement('textarea');
    el.value = copyString;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);


    notifyTopCorner("Copied!");
};


const notifyTopCorner = (text = "", timeout = 3000, colorClass = "bg-green") => {
    const confirmCopyElement = $(`<div class="pt-2 pb-2 pl-3 pr-3 ${colorClass} color-white border-radius-5px position-fixed tr-5rem zindex99">${text}</div>`);
    confirmCopyElement.hide().prependTo("body").fadeIn(200);
    setTimeout(function (){
        confirmCopyElement.fadeOut(1000).remove();
    }, timeout);
}

function docReady(fn) {
    if (document.readyState === "complete" || document.readyState === "interactive") {
        setTimeout(fn, 1);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}



/**
 * Max length
 */
function setMaxLengthItems(elements) {
    elements.each(function (){
        let placement = $(this).data("maxlength-placement"),
            options = {
                alwaysShow: true,
                warningClass: "design-box",
                limitReachedClass: "alert-danger-box",
                placement: "centered-right"
            };

        if(!empty(placement)) options.placement = placement;
        $(this).maxlength(options)
    })
}
if($(document).find("[maxlength]").length) { setMaxLengthItems($(document).find("[maxlength]")); }


/**
 *
 * @param elementList[0 => ["elements to show"], 1 => ["elements to hide"], 2 => jQueryParentElement#Optional]
 *
 */
function toggleHiddenItems(elementList) {
    if(elementList.length < 2 || elementList.length > 3) return;
    let parent = elementList.length === 3 ? elementList[2] : $(document);

    for(let i = 0; i < elementList.length; i++) {
        let toggleList = elementList[i];
        if(empty(toggleList)) continue;

        for(let identifier of toggleList) {
            let element = parent.find(identifier).first();
            if(!element.length) continue;

            if(i === 0) element.show();
            else element.hide();
        }
    }
}


function generateTableDropdown(innerContent, id, linkText) {
    let expandedColumn = "";

    expandedColumn += "<div class='dropdown' style='max-width: inherit; white-space: inherit'>";
        expandedColumn += "<span class='no-after underline-it cursor-pointer hover-color-red dropdown-toggle' id='" + id + "' data-toggle='dropdown'";
            expandedColumn += " aria-haspopup='true' aria-expanded='false' ";
            expandedColumn += " data-is-loaded='false' style='max-width: inherit; white-space: inherit'>";
                expandedColumn += linkText;
            expandedColumn += "</span>";
            expandedColumn += "<div class='dropdown-menu mnw-400px' aria-labelledby='" + id + "' >";
                expandedColumn += innerContent;
            expandedColumn +=  "</div>";
        expandedColumn +=  "</div>";
    expandedColumn +=  "</div>";

    return expandedColumn;
}



function newImgDimension(targetRatio,currentRatio,imgW=null,imgH=null) {
    // Only a single dimension can be null.
    // If dimension is null, we assume that the other dimensions is already correctly calculated
    if(currentRatio < 1)
        return imgW === null ? {width:imgH*targetRatio,height:imgH} : {width:imgW,height:(imgW/targetRatio)};
    else
        return imgH === null ? {width:imgW,height:imgW/targetRatio} : {width:(imgH*targetRatio),height:imgH};
}

function fileExt (str) {
    return str.substring(str.lastIndexOf('.')+1);
}



/*
    Must by default contain status = success | error
    if error, error  =>  "some err message" is required
 */
function basicResponseHandling(response, objType = "object", requiredFields = ["status"]) {
    if(typeof response !== objType) return {status: false, error: "#930 Something went wrong. Please try again later"};
    if("status" in response && (response.status === "error" && !("error" in response))) return {status: false, error: "#932 Something went wrong. Please try again later"};

    if(response.status === "error") return {status: false, error: response.error};
    for(let key of requiredFields) if(!(key in response)) return {status: false, error: "#931 Something went wrong. Please try again later"};

    return {status: true, response}
}

function generateRandomNumber() {
    return parseInt((Math.random(Math.random(7,9999), (new Date()).getTime())) * (100000000));
}

function formDataToObject(formData) {
    const data = {};
    formData.forEach((value, key) => {
        if (!data[key]) {
            data[key] = value;
        } else if (Array.isArray(data[key])) {
            data[key].push(value);
        } else {
            data[key] = [data[key], value];
        }
    });
    return data;
}

function select2SingleInit(elements = []) {
    if(empty(elements)) elements = $(document).find(".select2-single");
    elements.each(function () {
        let attributeSettings = $(this).data("select2-attr");
        if(empty(attributeSettings)) attributeSettings = {};

        if(typeof attributeSettings !== "object") attributeSettings = JSON.parse(attributeSettings);
        $(this).select2(attributeSettings);
    });
}

function select2MultiInit(elements = []) {
    if(empty(elements)) elements = $(document).find(".select2Multi");
    elements.each(function () {
        let attributeSettings = $(this).data("select2-attr");
        if(empty(attributeSettings)) attributeSettings = {};

        if(typeof attributeSettings !== "object") attributeSettings = JSON.parse(attributeSettings);
        $(this).select2(attributeSettings);
    });
}

function select2MultiRemove(elements = []) {
    if(empty(elements)) elements = $(document).find(".select2Multi");
    elements.each(function () {
        $(this).select2("remove");
    });
}

function select2MultiUnselectItem(selectElement, idToRemove, triggerChange = true) {
    var values = selectElement.val();
    if (values) {
        var i = values.indexOf(idToRemove);
        if (i >= 0) {
            values.splice(i, 1);
            selectElement.val(values)
            if(triggerChange) selectElement.change();
        }
    }
}


function togglePasswordVisibility() {
    let passwordFields = $(document).find(".togglePwdVisibilityField");
    if(!passwordFields.length) return false;


    passwordFields.each(function () {
        let el = $(this);
        let placementClass = el.attr("data-placement-class")
        if(empty(placementClass)) placementClass = "absolute-tr-10-10";

        let clickIcon = $(`<i class="mdi mdi-eye ${placementClass} font-16 cursor-pointer hover-opacity-half togglePwdVisibility" data-current-show="password"></i>`);
        clickIcon.insertAfter(el);

        clickIcon.on("click", function () {
            let currentType = el.attr("type");
            if(currentType === "password") el.attr("type", "text");
            else el.attr("type", "password");
            $(this).attr("data-current-show", (currentType === "password" ? "text" : "password"))
        })
    })
}






function resolveAssetPath(path) {
    if (empty(path) || typeof path !== "string") return path;
    if(!path.includes("https://") && !path.includes("http://")) return serverHost + path;
    return path;
}




function smallProfileIcon(url, checkmark = false, unknown = false) {
    let html = '<div class="position-relative">';
    html += '<img src="' + url + '" class="noSelect square-50 border-radius-50 mr-2 mt-1" />';
    if(checkmark) {
        html += '<div style="position:absolute; top: -10px; right: -5px;">';
        html += '<i class="mdi mdi-check-decagram font-25 " style="color: #1c96df"></i>';
        html += '</div>';
    }
    else if(unknown) {
        html += '<div style="position:absolute; top: -10px; right: -5px;">';
        html += '<i class="mdi mdi-account-question font-25 color-acoustic-yellow" data-toggle="tooltip" data-placement="top" title="This is an unknown creator."></i>';
        html += '</div>';
    }

    html += '</div>';
    return html;
}

function microProfileIcon(url, checkmark = false, unknown = false) {
    let html = '<div class="position-relative">';
    html += '<img src="' + url + '" class="noSelect square-30 border-radius-50" />';
    if(checkmark) {
        html += '<div style="position:absolute; bottom: -10px; left: -5px;">';
        html += '<i class="mdi mdi-check-decagram font-25 " style="color: #1c96df"></i>';
        html += '</div>';
    }
    else if(unknown) {
        html += '<div style="position:absolute; top: -13px; left: -10px;">';
        html += '<i class="mdi mdi-account-question font-25 color-acoustic-yellow" data-toggle="tooltip" data-placement="top" title="This is an unknown creator."></i>';
        html += '</div>';
    }

    html += '</div>';
    return html;
}



function IsImageOk(img) {
    if(img.getAttribute("loading") === "lazy") return true;
    if (!img.complete) {
        return false;
    }
    if (typeof img.naturalWidth != "undefined" && img.naturalWidth === 0) {
        return false;
    }
    return true;
}
function replaceBadImage(img) {
    img.setAttribute('src', serverHost + 'public/media/images/placeholder-image.svg');
}


const buttonIdle = (button, previousTextContent = "", previousHtmlContent = "", enable = true) => {
    buttonLoading(button, previousTextContent, previousHtmlContent)
    if(enable) button.removeAttr("disabled")
    return true;
}

const buttonInProcess = (button, disable = true, grabText = true) => {
    let buttonCurrentContent = grabText ? button.text() : button.html();
    buttonLoading(button);
    if(disable) button.attr("disabled", "disabled")
    return buttonCurrentContent;
};

const buttonLoading = (button, textContent = "", htmlContent = "") => {
    let spinner = $(
        '<div class="spinner-border spinner-grow-sm spinner-border-tiny" role="status">' +
        '<span class="sr-only">Loading...</span>' +
        '</div>'
    )

    let setLoading = empty(htmlContent) && empty(textContent);
    if(setLoading) htmlContent = spinner;

    if(!empty(htmlContent)) button.html(htmlContent)
    else if(!empty(textContent)) button.text(textContent)
}


function fadeContainers(container1, container2) {
    container1.stop(true, true).fadeOut(400, function () {
        container2.stop(true, true).fadeIn(400);
    });
}


const screenLoader = {
    show: (message = "Loading...") => {
        if ($("#screen-loader").length >= 1) return true;
        const loader = $(`
            <div id="screen-loader" class="color-white">
                <div class="screen-loader-content color-white">
                    <div class="spinner-border text-light" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="loader-text color-white">${message}</div>
                </div>
            </div>
        `);
        $("body").append(loader);
    },
    hide: () => {
        if($("#screen-loader").length >= 1) $("#screen-loader").remove();
        return true;
    },
    update: (message) => {
        if($("#screen-loader").length >= 1) $("#screen-loader").find(".loader-text").first().text(message);
        return true;
    }
}


const contentLoader = {
    show: (container, message = "Loading...") => {
        if (container.find(".content-loader").length >= 1) return true;
        const loader = $(`
            <div class="content-loader color-primary-dark">
                <div class="screen-loader-content color-primary-dark">
                    <div class="spinner-border color-primary-dark" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="loader-text color-primary-dark">${message}</div>
                </div>
            </div>
        `);
        container.html(loader);
    },
    hide: (container) => {
        let loaderContainer = container.find(".content-loader").first();
        if(loaderContainer.length >= 1) loaderContainer.remove();
        return true;
    }
}


function setTooltips() {
    let elements = $("[data-toggle=tooltip]");
    if(elements.length) elements.tooltip()
}

$.fn.slideUpAndRemove = function(duration = 300, callback) {
    return this.each(function () {
        $(this).slideUp(duration, function () {
            $(this).remove();
            if (typeof callback === 'function') callback();
        });
    });
};

$(function() {
    // Check if Handlebars is defined in the global scope
    if (typeof Handlebars !== 'undefined') {
        // Register the `eq` helper in Handlebars
        Handlebars.registerHelper('eq', function(a, b, options) {
            if (a === b) {
                return options.fn(this);
            } else {
                return options.inverse(this);
            }
        });

        Handlebars.registerHelper('and', function (v1, v2, options) {
            if (!!v1 && !!v2) {
                return options.fn(this);
            } else {
                return options.inverse(this);
            }
        });

        Handlebars.registerHelper('inArray', function (v1, v2, options) {
            if (typeof v2 === 'object' && Object.values(v2).includes(v1)) {
                return options.fn(this);
            } else {
                return options.inverse(this);
            }
        });

        Handlebars.registerHelper('foreach', function(list, options) {
            if (!list) return '';
            if (Array.isArray(list)) {
                return list
                    .map((value, index) => {
                        return options.fn({ value, index });
                    })
                    .join('');
            }

            return '';
        });

        Handlebars.registerHelper('foreachKeyValue', function(obj, options) {
            if (!obj || typeof obj !== 'object') return '';
            return Object.keys(obj)
                .map((key, index) => {
                    return options.fn({ key, value: obj[key], index });
                })
                .join('');
        });

        Handlebars.registerHelper('isEmpty', function(value, options) {
            if (
                empty(value)
            ) {
                return options.fn(this);     // Empty case
            } else {
                return options.inverse(this); // Not empty
            }
        });
    }
});



function formatCurrencyInput(inputElement) {
    let inputValue = inputElement.value.replace(/\D/g, '');  // Remove any non-digit character
    if (inputValue === "") {
        inputElement.value = "";  // Default value when input is empty
        return;
    }
    inputValue = (parseInt(inputValue) / 100).toFixed(2); // Divide by 100 to get two decimal places
    inputElement.value = inputValue;
}
function setCaretToEnd(element) {
    element.setSelectionRange(element.value.length, element.value.length);
}


function cleanTitle(str) {
    return str.replace(/_/g, ' ');
}

function cleanUcAll(str) {
    str = cleanTitle(str);
    let split = []
    for(let word of (str.split(" "))) split.push(ucFirst(word))
    return split.join(" ")
}

function isAssoc(arr) {
    return typeof arr === 'object' && !Array.isArray(arr);
}


const objectActionContentToggle = () => {
    const handle = (btn) => {
        let parent = btn.parents(".object-action-container").first(), contentContainer = parent.find(".object-action-content").first();
        if(empty(contentContainer)) return;
        let isShown = !contentContainer.hasClass("hide-content"),
            isRunning = false,
            mouseOutTimer = null;

        const toggleContent = () => {
            if(isRunning) return;
            isRunning = true;
            clearTimeout(mouseOutTimer)

            if(!isShown) {
                contentContainer.css('display', 'flex')
                btn.hide();
                setTimeout(function() {
                    contentContainer.addClass('show-content').removeClass('hide-content');
                }, 10);

                isShown = true;
            }
            else {
                contentContainer.addClass('hide-content').removeClass('show-content');
                setTimeout(function() {
                    btn.show();
                    contentContainer.hide();
                }, 200);
                isShown = false;
            }
            isRunning = false;
        }
        btn.on("click", toggleContent)

        $(document).on('click', function(event) {
            let select2OpenContainerClass = ".select2-container--open";
            let hasSelect2 = parent.find(".select2").length
            if (isShown && !parent.is(event.target) && !parent.has(event.target).length && !(hasSelect2 && $(document).find(select2OpenContainerClass).length))
                toggleContent();
        });
        parent.on("mouseleave", function() {
            let select2OpenContainerClass = ".select2-container--open";
            let hasSelect2 = parent.find(".select2").length
            if (isShown && !(hasSelect2 && $(document).find(select2OpenContainerClass).length)) {
                mouseOutTimer = setTimeout(function() {
                    toggleContent();
                }, 2000);
            }
        });

        parent.on("mouseenter", function() { clearTimeout(mouseOutTimer); });
    }

    $(document).find(".object-action-expand-btn").each(function () { handle($(this)) })
}


function displayJson(content) {
    try {
        let json;
        if(typeof content === "object") json = content;
        else json = JSON.parse(content);

        const prettyJson = JSON.stringify(json, null, 4); // Pretty print JSON
        return `<pre class="js-pre-display">${escapeHtml(prettyJson)}</pre>`;
    } catch (err) {
        return `<p style='color: red;'>Invalid JSON file.</p>`;
    }
}

function displayLogOrTxt(content) {
    const escapedContent = escapeHtml(content); // Escape HTML entities
    const withLineBreaks = escapedContent.replace(/\n/g, "<br>"); // Replace newlines with <br>
    return `<pre class="log js-pre-display">${withLineBreaks}</pre>`;
}

function displayHtml(content) {
    const escapedHtml = escapeHtml(content); // Escape HTML
    const withLineBreaks = escapedHtml.replace(/\n/g, "<br>"); // Replace newlines with <br>
    return `<div class="js-pre-display">${withLineBreaks}</div>`;
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


function min(...args) {
    if (args.length === 1 && Array.isArray(args[0])) args = args[0];
    return Math.min(...args);
}

function max(...args) {
    if (args.length === 1 && Array.isArray(args[0])) args = args[0];
    return Math.max(...args);
}


function rebuildSelectV2UI(select) {
    if (select instanceof jQuery) select = select.get(0);
    if (!select) return;

    const wrapper = select.nextElementSibling;
    if (wrapper && wrapper.classList.contains("form-select-v2-wrapper")) {
        wrapper.remove(); // 💣 Remove old UI
    }
    selectV2(select);
}
/**
 * Refreshes the custom UI for a select element
 */
function refreshSelectV2UI(select) {
    if (!select) return;
    const wrapper = select.nextElementSibling;
    if (!wrapper || !wrapper.classList.contains("form-select-v2-wrapper")) return;

    const selectedDisplay = wrapper.querySelector(".form-select-v2-selected");
    const valuesContainer = wrapper.querySelector(".form-select-v2-values");
    const optionsContainer = wrapper.querySelector(".form-select-v2-options");

    // Update option classes
    optionsContainer.querySelectorAll(".form-select-v2-option").forEach(optEl => {
        const match = Array.from(select.options).find(o => o.value === optEl.dataset.value);
        if (match && match.selected && match.value !== "") {
            optEl.classList.add("selected");
        } else {
            optEl.classList.remove("selected");
        }
    });

    // Update display text
    if (select.multiple) {
        const selectedOpts = Array.from(select.options).filter(o => o.selected && o.value !== "");
        selectedDisplay.textContent = selectedOpts.length > 0
            ? `Valgt (${selectedOpts.length})`
            : "Vælg";
    } else {
        const selectedOpt = select.options[select.selectedIndex];
        selectedDisplay.innerHTML = (!selectedOpt || selectedOpt.value === "")
            ? "Vælg..."
            : selectedOpt.innerHTML;
    }

    // Render chips (for multi only)
    if (select.multiple) {
        valuesContainer.innerHTML = "";
        const selectedOpts = Array.from(select.options).filter(o => o.selected && o.value !== "");
        if (selectedOpts.length === 0) {
            valuesContainer.style.display = "none";
        } else {
            valuesContainer.style.display = "flex";
            selectedOpts.forEach(opt => {
                const chip = document.createElement("div");
                chip.classList.add("form-select-v2-chip");
                chip.textContent = opt.text;

                const removeBtn = document.createElement("span");
                removeBtn.classList.add("remove-chip");
                removeBtn.innerHTML = "&times;";
                removeBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    opt.selected = false;
                    refreshSelectV2UI(select);
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                });

                chip.appendChild(removeBtn);
                valuesContainer.appendChild(chip);
            });
        }
    }``
}

/**
 * Initializes custom select (single or multi)
 */
function selectV2(select = null) {
    const runFunc = (select) => {
        const selectIsDisabled = select.disabled;
        const existingWrapper = select.nextElementSibling;
        if (existingWrapper && existingWrapper.classList.contains("form-select-v2-wrapper")) return;

        const wrapper = document.createElement("div");
        wrapper.classList.add("form-select-v2-wrapper");

        const sizeClasses = [
            'w-100', 'w-75', 'w-50', 'w-33', 'w-25', 'w-300px', 'w-250px', 'h-45px', 'w-60px',
            'w-200px', 'w-150px', 'w-100px', 'w-30px', 'w-40px', 'flex-1-current', 'w-100px',
            'border-radius-tl-bl-0-5px', 'border-radius-tr-br-0-5px', 'w-65px', 'w-70px',
            'border-radius-tr-br-0-5rem', 'border-radius-tl-bl-0-5rem', 'dropdown-no-arrow'
        ];
        const selectedSizeClasses = [
            'border-radius-tl-bl-0-5px', 'border-radius-tr-br-0-5px', 'border-radius-tr-br-0-5rem',
            'border-radius-tl-bl-0-5rem'
        ];
        sizeClasses.forEach(cls => {
            if (select.classList.contains(cls)) wrapper.classList.add(cls);
        });

        const selectedDisplay = document.createElement("div");
        selectedDisplay.classList.add("form-select-v2-selected");
        selectedSizeClasses.forEach(cls => {
            if (select.classList.contains(cls)) selectedDisplay.classList.add(cls);
        });

        const valuesContainer = document.createElement("div");
        valuesContainer.classList.add("form-select-v2-values");

        const optionsContainer = document.createElement("div");
        optionsContainer.classList.add("form-select-v2-options");

        // ✅ If searchable, add search box
        let searchInput = null;
        if (select.dataset.search === "true") {
            searchInput = document.createElement("input");
            searchInput.type = "text";
            searchInput.classList.add("form-select-v2-search");
            searchInput.classList.add("mb-0");
            searchInput.classList.add("form-field-v2");
            searchInput.placeholder = "Search...";
            optionsContainer.appendChild(searchInput);

            // Basic styling (you can move to CSS!)
            searchInput.style.width = "100%";
            searchInput.style.boxSizing = "border-box";
            searchInput.style.border = "none";
            searchInput.style.outline = "none";
        }

        // ✅ Build option elements
        Array.from(select.options).forEach(option => {
            const optionElement = document.createElement("div");
            optionElement.classList.add("form-select-v2-option");
            optionElement.innerHTML = option.innerHTML;

            for (const [key, value] of Object.entries(option.dataset)) {
                optionElement.dataset[key] = value;
            }
            optionElement.dataset.value = option.value;

            if (selectIsDisabled || option.disabled) {
                optionElement.classList.add("disabled");
                optionElement.setAttribute("aria-disabled", "true");
            } else {
                optionElement.addEventListener("click", () => {
                    if (select.multiple) {
                        option.selected = !option.selected;
                    } else {
                        Array.from(select.options).forEach(o => o.selected = false);
                        option.selected = true;
                        optionsContainer.classList.remove("active");
                        selectedDisplay.classList.remove("active");
                    }
                    refreshSelectV2UI(select);
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                });
            }
            optionsContainer.appendChild(optionElement);
        });

        wrapper.appendChild(selectedDisplay);
        wrapper.appendChild(valuesContainer);
        wrapper.appendChild(optionsContainer);

        // Toggle dropdown
        selectedDisplay.addEventListener("click", () => {
            const isActive = optionsContainer.classList.toggle("active");
            selectedDisplay.classList.toggle("active", isActive);

            // ✅ Focus search when opening
            if (isActive && searchInput) {
                searchInput.value = "";
                filterOptions("");
                searchInput.focus();
            }
        });

        document.addEventListener("click", (e) => {
            if (!wrapper.contains(e.target)) {
                optionsContainer.classList.remove("active");
                selectedDisplay.classList.remove("active");
            }
        });

        select.parentNode.insertBefore(wrapper, select.nextSibling);

        // ✅ Search filter logic
        if (searchInput) {
            searchInput.addEventListener("input", (e) => {
                filterOptions(e.target.value.toLowerCase());
            });
        }

        function filterOptions(term) {
            const opts = optionsContainer.querySelectorAll(".form-select-v2-option");
            opts.forEach(optEl => {
                const sortKey = optEl.dataset.sort?.toLowerCase();
                const textKey = optEl.textContent.toLowerCase();
                const key = sortKey || textKey;
                optEl.style.display = key.includes(term) ? "block" : "none";
            });
        }

        refreshSelectV2UI(select);
    };

    if (select !== null) {
        if (select instanceof jQuery) select = select.get(0);
        runFunc(select);
    } else {
        document.querySelectorAll(".form-select-v2").forEach(runFunc);
    }
}


/**
 * Programmatically update value(s) and refresh UI
 */
function updateSelectV2Value(select, values) {
    if (select instanceof jQuery) select = select.get(0);
    if (!select) return;

    Array.from(select.options).forEach(opt => opt.selected = false);

    if (select.multiple) {
        if (!Array.isArray(values)) values = [values];
        values.forEach(val => {
            const opt = Array.from(select.options).find(o => o.value === val);
            if (opt && opt.value !== "") opt.selected = true;
        });
    } else {
        const opt = Array.from(select.options).find(o => o.value === values);
        if (opt && opt.value !== "") {
            opt.selected = true;
            select.value = values;
        } else {
            select.value = "";
        }
    }

    refreshSelectV2UI(select);
    select.dispatchEvent(new Event("change", { bubbles: true }));
}



function queueMethodOnLoad(method, args) {
    localStorage.setItem("queued_method", JSON.stringify({ method, args }));
}
function queueNotificationOnLoad(title, description = '', type = 'neutral', timeout = 5000) {
    localStorage.setItem("queued_notification", JSON.stringify({ title, description, type, timeout }));
}
function removeQueuedNotification() {
    localStorage.removeItem("queued_notification");
}


function initialiseGoogleCharts(callable = null, packages = ['corechart', 'bar']) {
    if(callable !== null) {

        google.charts.load('current', {packages: packages});
        google.charts.setOnLoadCallback(callable);
    }
    else {
        google.charts.load('current', {packages: packages});
    }
}



function drawUniversalChart(containerId, rawData, columnTitles = [], secondSeriesType = 'line', primarySeriesType = 'bars') {
    let dataArray;

    if (columnTitles.length === 0) {
        const seriesCount = rawData[0].length - 1;
        columnTitles = ['Category'];
        for (let i = 1; i <= seriesCount; i++) {
            columnTitles.push('Series ' + i);
        }
    }
    dataArray = [columnTitles, ...rawData];
    const data = google.visualization.arrayToDataTable(dataArray);

    // figure out how many series (excluding x-axis label)
    var seriesCount = columnTitles.length - 1;

    // base options
    var options = {
        legend: {},
        chartArea: { width: '85%', height: '70%' },
        hAxis: {
            textStyle: { fontSize: 12 },
            slantedText: false,
            showTextEvery: 2
        },
        vAxis: {
            gridlines: { color: '#f0f0f0' },
            textStyle: { fontSize: 12 }
        },
        tooltip: { isHtml: true },
        focusTarget: 'category',
        bar: { groupWidth: '40%' },
        colors: [],
        seriesType: primarySeriesType,
        series: {},
        animation: {
            startup: true,
            duration: 600,
            easing: 'out'
        }
    };

    if (seriesCount > 1 && secondSeriesType === 'line') {
        options.series[1] = { type: secondSeriesType, curveType: 'function', pointSize: 6 };
    }
    options.legend.position = seriesCount === 1 ? 'none' : 'bottom';
    options.colors = seriesCount > 1 ? ['#A78BFA', '#61C2EC'] : ['#61C2EC']

    const chartType = seriesCount > 1 ? google.visualization.ComboChart :
        (primarySeriesType === 'bars' ? google.visualization.ColumnChart : google.visualization.LineChart);
    const chart = new chartType(document.getElementById(containerId));
    chart.draw(data, options);
}



window.addEventListener('DOMContentLoaded', () => {
    const modalQueued = localStorage.getItem("queued_modal");
    if (modalQueued) {
        try {
            const { template, options } = JSON.parse(modalQueued);
            const handler = new ModalHandler(template);
            handler.construct();
            handler.build().then(() => {
                handler.setOptions(options || {});
                handler.open();
            });
        } catch (e) {
            console.error("Failed to restore queued modal:", e);
        } finally {
            localStorage.removeItem("queued_modal");
        }
    }

    const methodQueued = localStorage.getItem("queued_method");
    if (methodQueued) {
        try {
            const { method, args } = JSON.parse(methodQueued);
            if(method in window) window[method](args)
        } catch (e) {
            console.error("Failed to restore queued modal:", e);
        } finally {
            localStorage.removeItem("queued_method");
        }
    }

    const notificationQueued = localStorage.getItem("queued_notification");
    if (notificationQueued) {
        try {
            const { title, description, type, timeout } = JSON.parse(notificationQueued);
            createNotification(title, description, type, timeout)
        } catch (e) {
            console.error("Failed to run queued notification:", e);
        } finally {
            localStorage.removeItem("queued_notification");
        }
    }
});