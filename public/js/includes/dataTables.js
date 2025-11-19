/**
* Copyright @Admiralshoi 2021
* The code and its structure as a whole  is copyrighted.
* You may use the code as you please, but sharing the code to 3rd parties and or
* selling its components is a violation of the Copyright
*/


function setDataTable(el, sort = [], datePicker = false, entries = [], defaultDisplayLength = 0, columnDefs = []) {
    entries = (!empty(entries) ? entries :
            [
                [10, 30, 50, 100, 500, 1000, 2500, 5000, -1],
                [10, 30, 50, 100, 500, 1000, 2500, 5000, "All"]
            ]
    );
    defaultDisplayLength = defaultDisplayLength === 0 ? entries[0][0] : defaultDisplayLength;

    let dtOpt = {
        aLengthMenu: entries,
        iDisplayLength: defaultDisplayLength,
        deferRender: true,
        scrollCollapse: true,
        language: {
            search: "",
        },
        columnDefs
    };
    if(!empty(sort)) dtOpt.order = sort;
    else dtOpt.ordering = false;
    if($.fn.dataTable.isDataTable(el)) {
        let elDT = el.DataTable();
        elDT.clear();
        elDT.destroy();
    }
    el.DataTable(dtOpt);


    // SEARCH - Add the placeholder for Search and Turn this into in-line form control
    let search_input = el.closest('.dataTables_wrapper').find('div[id$=_filter] input');
    search_input.attr('placeholder', 'Search');
    search_input.removeClass('form-control-sm');
    // LENGTH - Inline-Form control
    let length_sel = el.closest('.dataTables_wrapper').find('div[id$=_length] select');
    length_sel.removeClass('form-control-sm');

    if(!datePicker) return true; //  BELOW: DATE PICKER ------------BELOW: DATE PICKER ------------BELOW: DATE PICKER ------------BELOW: DATE PICKER ------------

    var parentRow = $(".dataTables_length").parents(".row").first(),
        parentCol = parentRow.find("div").first(), newSizeClass = "flex-row-around",
        newCol = parentCol.clone();

    parentCol.siblings().each(function () {
        // $(this).removeClass("col-md-6").addClass(newSizeClass);
    });
    newCol.removeClass("col-md-6").addClass(newSizeClass).addClass("mb-4");

    var htmlDateFrom = $('' +
        '<div class="col-sm-6 col-md-6">' +
            '<div class="input-group date datepicker dashboard-date mr-2 mb-2 mb-md-0 d-md-none d-xl-flex" id="dateFrom">'+
                '<input type="text" class="form-control date-range-filter" placeholder="Date from" autocomplete="off">' +
                '<span class="input-group-addon bg-transparent"><i class="mdi mdi-calendar color-dark"></i></span>' +
            '</div>' +
        '</div>');
    var htmlDateTo = htmlDateFrom.clone(),
        dateWrapper = $("<form class='row' style='height: 43px !important;' autocomplete='off'></form>");
    htmlDateTo.find(".datepicker").first().attr("id","dateTo");
    htmlDateTo.find(".date-range-filter").first().attr("placeholder","Date to");
    dateWrapper.append('<input type="hidden" autocomplete="off" />').append(htmlDateFrom).append(htmlDateTo);
    newCol.html(dateWrapper);

    newCol.find('.dashboard-date').each(function () {
        $(this).datepicker({
            format: "yyyy-mm-dd",
            todayHighlight: true,
            autoclose: true
        });
    });


    newCol.find('.date-range-filter').change( function() {

        $.fn.dataTable.ext.search.push(
            function( settings, data, dataIndex ) {
                var min  = newCol.find('#dateFrom').find(".date-range-filter").val();
                var max  = newCol.find('#dateTo').find(".date-range-filter").val();

                if(min === undefined || max === undefined || (min === "" && max === ""))
                    return true;

                if(min !== "") min = (new Date(min)).getTime();
                if(max !== "") max = (new Date(max)).getTime();

                var col = el.find("thead th[data-datepick-col]"),
                    colNum = col.data("datepick-col"),createdAt = data[colNum] || 0; // Our date column in the table
                let dataTime = (new Date(createdAt)).getTime();

                dataTime = dataTime + 3600 * SUMMERTIME * 1000;

                if(min !== "" && max !== "" && (dataTime >= min && dataTime <= max))  return true;
                else if(min === "" && max !== ""  && (dataTime <= max)) return true;
                else if(min !== "" && max === ""  && (dataTime >= min)) return true;

                return false;
            }
        );

        el.DataTable().draw();
    } );

    parentRow.append(newCol);

}