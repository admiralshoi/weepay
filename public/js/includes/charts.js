function renderCharts(el, data, chartType, formatterTitle = "") {
    let colorListA = {red: "#fe7773", purple: "#3A3866", green: "#569C75"},
        colorListB = ["#855cfe","#fe7773","#031927","#87ceeb","#ffd8d8","#569C75","#4500F0","#E57BFF"];
    data.color_list = colorListB;
    switch (chartType) {
        default: return false;
        case "line":
            lineChart( el, data );
            break;
        case "bar":
            barChart( el, data, formatterTitle);
            break;
        case "donut":
            pieChart( el, data, chartType, formatterTitle);
            break;
        case "pie":
            pieChart( el, data, chartType, formatterTitle);
            break;
        case "stackedBar":
            stackedBar( el, data);
            break;
        case "bubble":
            bubbleChart( el, data);
            break;

    }
}



function bubbleChart (element, opt) {
    if(!('series' in opt)) return;
    let options = {
        chart: {
            type: 'bubble',
            height: 350,
        },
        series: opt.series,
        xaxis: {
            labels: {
                show: false,
            },
            min: 0
        },
        yaxis: {
            min: 0,
            labels: { formatter: (val) => `${shortNumbByT(val, true, true)}`, }
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: (val) => `${shortNumbByT(val, true, true)}`,
            },
            z: {
                formatter: (val) => `${shortNumbByT(val, true, true)}`,
            }
        },
        dataLabels: { enabled: false },
        fill: {
            opacity: 0.8,
        },
        plotOptions: {
            bubble: {
                maxBubbleRadius: 40,
                minBubbleRadius: 5
            }
        },
        legend: {
            show: (opt.series.length < 10),
        }
    };

    if('y_tooltip' in opt) options.tooltip.y = opt.y_tooltip;
    if('z_tooltip' in opt) options.tooltip.z = opt.z_tooltip;
    if('x_tooltip' in opt) options.tooltip.x = opt.x_tooltip;
    if('x_title' in opt) options.xaxis.title = { text: opt.x_title };
    if('y_title' in opt) options.yaxis.title = {text: opt.y_title};
    if('maxX' in opt) options.xaxis.max = opt.maxX;
    if('maxY' in opt) options.yaxis.max = opt.maxY;
    // if('labels' in opt) options.xaxis.labels = opt.labels;
    // if('tick_amount' in opt) options.xaxis.tickAmount = opt.tick_amount;
    if('title' in opt) {
        options.title = {
            text: opt.title,
            align: "center",
            style: {
                fontWeight: "400",
                fontSize: "16px",
            }
        };
    }
    if('color_list' in opt) {
        options.colors = opt.color_list;
    }

    if($(element).find(".apexcharts-canvas").length) {
        let el = $(element), resizeEl = el.siblings(".resize-triggers").first();
        if(resizeEl.length) resizeEl.remove();
        el.empty();
    }
    (new ApexCharts(element, options)).render();
}



function lineChart(element, opt) {
    if(!Object.keys(opt).includes("series") || !Object.keys(opt).includes("labels") || !Object.keys(opt).includes("color_list")
        || !Object.keys(opt).includes("title") ) return false;

    if(Object.keys(opt.series).includes("data")) opt.series = [opt.series];

    let options = {
        chart: {
            type: 'line',
            width: '100%',
            height: '300px',
            background: "transparent",
            toolbar: {
                show: false
            }
        },
        colors: opt.color_list,
        series: opt.series,
        xaxis: {
            type: "category",
            categories: opt.labels,
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            },
            labels: {
                rotate: -10, // no need to rotate since hiding labels gives plenty of room
                hideOverlappingLabels: true,  // all labels must be rendered
            }
        },
        yaxis: {
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            }
        },
        plotOptions: {
            bar: {
                horizontal: true
            }
        },
        stroke: {
            curve: "straight"
        },
        tooltip: {
            y: {
                formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                    return shortNumbByT(value,true,true);
                }
            }
        }
    };

    if('title' in opt) {
        options.title = {
            text: opt.title,
            align: "center",
            style: {
                fontWeight: "400",
                fontSize: "16px",
            }
        };
    }

    if($(element).find(".apexcharts-canvas").length) {
        let el = $(element), resizeEl = el.siblings(".resize-triggers").first();
        if(resizeEl.length) resizeEl.remove();
        el.empty();
    }

    (new ApexCharts(element, options)).render();
}


function pieChart(element, opt, type, formatterTitle = "") {
    if(!Object.keys(opt).includes("series") || !Object.keys(opt).includes("labels") || !Object.keys(opt).includes("color_list")) return false;

    let legendPosition = "bottom", height = "200", width = false, title = ""
    if(Object.keys(opt).includes("legend_position")) legendPosition = opt.legend_position;
    if(Object.keys(opt).includes("height")) height = opt.height;
    if(Object.keys(opt).includes("width")) width = opt.width;
    if(Object.keys(opt).includes("title")) title = opt.title;

    let chart = {type};
    if(height !== false) chart.height = height;
    if(width !== false) chart.width = width;


    let options = {
        chart,
        series: Object.values(opt.series),
        labels: Object.values(opt.labels),
        colors: opt.color_list,
        legend:{
            show:true,
            position: legendPosition
        },
        tooltip: {
            y: {
                formatter: (value) => {
                    return (formatterTitle === "number_format" ? numberFormatting(value) : value + formatterTitle)
                }
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '55%'
                }
            }
        },
        title: {
            text: title,
            align: "left",
            style: {
                fontWeight: "400",
                fontSize: "16px",
            }
        }
    };


    if('y_tooltip' in opt) options.tooltip.y = opt.y_tooltip;
    if('x_tooltip' in opt) options.tooltip.x = opt.x_tooltip;


    if($(element).find(".apexcharts-canvas").length) {
        let el = $(element), resizeEl = el.siblings(".resize-triggers").first();
        if(resizeEl.length) resizeEl.remove();
        el.empty();
    }
    (new ApexCharts(element, options)).render();
}


function barChart(element, opt, formatterTitle = "") {
    if(!Object.keys(opt).includes("series") || !Object.keys(opt).includes("labels") || !Object.keys(opt).includes("color_list")) return false;

    if(Object.keys(opt.series).includes("data")) {
        if(Object.keys(opt.series.data).length > 0 && typeof opt.series.data[(Object.keys(opt.series.data)[0])] !== "object")
            opt.series = [{data: opt.series.data, name: opt.series.name}];
    }
    else {
        if(Object.keys(opt.series).length > 0 && typeof opt.series[(Object.keys(opt.series)[0])] !== "object") {
            opt.series =  [{data: opt.series, name: opt.title}];
        }
    }

    let chart = {
        type: "bar",
        toolbar: {
            show: false
        }
    },
    plotOptions = {
        bar: {
            borderRadius: 5,
            horizontal: true,
            // barHeight: "80%",
            // columnWidth: '70%',
            // distributed: false,
            // rangeBarOverlap: true,
            // rangeBarGroupRows: false,
            // dataLabels: {
            //     position: 'top',
            //     maxItems: 12,
            //     hideOverflowingLabels: true,
            //     orientation: "horizontal"
            // }
        }
    },
    yaxis = {};

    let orientation = "vertical", yaxisDirection = "normal", height = "400", width = "100%", title = "";
    if(Object.keys(opt).includes("orientation")) orientation = opt.orientation;
    if(Object.keys(opt).includes("yaxis_direction")) yaxisDirection = opt.yaxis_direction;
    if(Object.keys(opt).includes("height")) height = opt.height;
    if(Object.keys(opt).includes("width")) width = opt.width;
    if(Object.keys(opt).includes("title")) title = opt.title;

    if(height !== false) chart.height = height;
    if(width !== false) chart.width = width;

    if(orientation === "horizontal") plotOptions.bar.horizontal = true;
    if(yaxisDirection === "reversed") yaxis.reversed = true;

    let options = {
        chart,
        colors: opt.color_list,
        grid: {
            show: true
        },
        title: {
            text: title,
            align: "left",
            style: {
                fontWeight: "600",
                fontSize: "16px",
            }
        },
        series: Object.values(opt.series),
        xaxis: {
            categories: Object.values(opt.labels),
            axisBorder: {
                show: true
            },
            axisTicks: {
                show: true,
            },
            labels: {
                show: true,
            }
        },
        yaxis,
        plotOptions,
        // legend: {
        //     show: false
        // },
        // options: {
        //     scales: {
        //         y: {
        //             beginAtZero: true
        //         }
        //     }
        // },
        dataLabels: {
            enabled: false,
            // position: 'bottom',
            // textAnchor: 'start',
            // style: {
            //     colors: ['#000']
            // },
            // offsetX: 0,
            // dropShadow: {
            //     enabled: false
            // },
            // formatter: function (value, formOpt) {
            //     return (formatterTitle === "number_format" ? numberFormatting(value) : value + formatterTitle)
            // },
        },
        tooltip: {
            y: {
                formatter: function(value, formOpt) {
                    return (formatterTitle === "number_format" ? numberFormatting(value) : value + formatterTitle)
                }
            },
            shared: true,
            enabled: true,
            intersect: false
        }
    };

    if($(element).find(".apexcharts-canvas").length) {
        let el = $(element), resizeEl = el.siblings(".resize-triggers").first();
        if(resizeEl.length) resizeEl.remove();
        el.empty();
    }

    (new ApexCharts(element, options)).render();
}



function areaChart(element, chartColor) {
    let dataList = [[30, 55, 45, 50],[90, 58, 60, 50],[3, 5, 8, 2]],
        random = Math.floor((Math.random() * 3)),
        chosenData = dataList[random];

    var options = {
        chart: {
            height: "100%",
            type: "area",
            toolbar: {
                show: false
            }
        },
        stroke: {
            width: 1
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false
        },
        tooltip: {
            enabled: false
        },
        grid: {
            show: false
        },
        colors: [chartColor],
        series: [
            {
                data: chosenData
            }
        ],
        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.9,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            tooltip: { enabled: false },
            categories: [
                0,
                1,
                2,
                3
            ],
            labels: {
                show: false
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            }
        },
        yaxis: {
            labels: {
                show: false
            }
        }
    };

    if($(element).find(".apexcharts-canvas").length) {
        let el = $(element), resizeEl = el.siblings(".resize-triggers").first();
        if(resizeEl.length) resizeEl.remove();
        el.empty();
    }
    (new ApexCharts(element, options)).render();
}



function stackedBar(element, opt) {
    if(!Object.keys(opt).includes("series") || !Object.keys(opt).includes("color_list")) return false;

    // {title: "some title", data: [["item 1", 25], ["item 2", 75]]}


    let stackedBarContainer = $('<div class="stacked-bar-chart w-100"></div>');

    for(let item of opt.series) {
        if(!Object.keys(item).includes("title") || !Object.keys(item).includes("total") || !Object.keys(item).includes("data") || empty(item.data)) continue;

        let barElement = $('<div class="row mb-2 flex-align-center"></div>'),
            dataColumn = $('<div class="col"></div>'), dataRow = $('<div class="row"></div>');

        if(!("include_title" in opt) || opt.include_title !== false)
            barElement.append('<div class="col-1 align-self-center">' + prepareProperNameString(item.title) + '</div>');

        let barColorKey = 0;
        for(let i in item.data) {
            let dataItem = item.data[i];
            // let percentage = Math.round(dataItem[1] / item.total * 100);
            let percentage = parseFloat((dataItem[1] / item.total * 100).toFixed(2));
            if(percentage < 3) continue;

            if(!(percentage > 0)) continue;
            if(barColorKey > (opt.color_list.length - 1)) barColorKey = 0;

            let itemCol = $('<div class="col-auto text-center stacked-data-bar"></div>'),
                style = 'width: ' + percentage + '%; background: ' + opt.color_list[barColorKey] + '; padding: 5px;';

            itemCol.text(prepareProperNameString(dataItem[0]) + " (" + percentage + "%)");
            itemCol.attr("style", style);

            if(!("tooltip" in opt) || opt.tooltip !== false) {
                itemCol.attr("data-toggle", "tooltip");
                itemCol.attr("data-placement", "top");
                itemCol.attr("title", prepareProperNameString(dataItem[0]) + ": " +percentage + "%");
                itemCol.tooltip();
            }

            dataRow.append(itemCol);
            barColorKey++;
        }

        dataColumn.append(dataRow);
        barElement.append(dataColumn);
        stackedBarContainer.append(barElement);
    }

    $(element).html(stackedBarContainer);
}




function setGoogleGeoChart(element, dataList) {
    if(!element.length || empty(dataList)) return;
    let parent = element.parents(".row").find("div").first();
    element = element.first().get(0);

    let collector = [['Country', 'Popularity']];
    for(let i = 0; i < Object.keys(dataList.labels).length; i++) collector.push([dataList.labels[i], dataList.series[i]]);

    let data = google.visualization.arrayToDataTable(collector);
    let options = {
        height: 600,
        // width: "100%",
        // chartArea: {width: "100%", left: 50, height: 500, top: 10},
        colorAxis: {colors: ['#A3A3A3', '#000']},
    };

    let chart = new google.visualization.GeoChart(element);
    chart.draw(data, options);

    parent.resize(function(){
        options.width = parent.width();
        chart.draw(data, options);
    });
}
