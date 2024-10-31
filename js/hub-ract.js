/* Hub5050 – Ranking and Competitor Tracking v.2.1.5 */

(function($) {

    //Trend chart generation
    if ($("#hub_ract_chart_container").length > 0) {
        if (varz.key_url && varz.key_url.length>5) {
            //get the data from the key url so that the licence needs never be exposed
            //combine the code into the REST API e.g. http://vhost9/wordpress/wp-json/ctasm/v1/chart/aHR0cHM6Ly9jcmVhdG9yc2VvLmNvbS8=/
            var api_url = 'https://hub5050.com/wp-json/ctasm/v1/chart/' + varz.key_url + '/';
            // console.log('REST Data API', api_url);
            $.ajax({
                url: api_url,
                method: 'GET',
                success: function (rest_info) {
                    //console.log('success', rest_info);
                    var rst_keywords = rest_info.map.keywords;
                    var rst_rivals = rest_info.map.rivals;
                    var rst_traits = rest_info.map.traits;
                    var rst_pages = rest_info.map.pages;
                    var rst_results = rest_info.ranking;
                    var rst_data = {};

                    $("#hub_ract_chart_container").html("");
                    if (rst_results){
                        for (var ix_page in rst_results) {
                            if (rst_results.hasOwnProperty(ix_page)) {
                                var rst_results_1 = rst_results[ix_page];
                                for (var ix_trait in rst_results_1) {
                                    if (rst_results_1.hasOwnProperty(ix_trait)) {
                                        var rst_results_2 = rst_results_1[ix_trait];
                                        $("#hub_ract_chart_container").append("<h3>" + rst_traits[ix_trait].toUpperCase() + "<span class='hub_ract_fade'>: " + rst_pages[ix_page] + "</span></h3>");
                                        for (var ix_keyword in rst_results_2) {
                                            if (rst_results_2.hasOwnProperty(ix_keyword)) {
                                                var rst_results_3 = rst_results_2[ix_keyword];
                                                var chartID = "chart01_" + ix_page + "_" + ix_trait + "_" + ix_keyword;
                                                //$("#hub_ract_chart_container").append("<h4>Keyword: " + rst_keywords[ix_keyword] + " [" + chartID + "]</h4>");
                                                $("#hub_ract_chart_container").append("<canvas class='hub_ract_canvas' id='" + chartID + "'></canvas><hr />");
                                                mydata = {
                                                    chart: chartID,
                                                    keyword: rst_keywords[ix_keyword],
                                                    rivals: [],
                                                    points: {},
                                                    cnt: 0
                                                };
                                                for (var ix_rival in rst_results_3) {
                                                    if (rst_results_3.hasOwnProperty(ix_rival)) {
                                                        //$("#hub_ract_chart_container").append("<li>Rival: " + rst_rivals[ix_rival] + "</li>");
                                                        mydata.rivals.push(ix_rival);
                                                        mydata.points[rst_rivals[ix_rival]] = rst_results_3[ix_rival];
                                                        if (ix_rival === 0){
                                                            mydata.cnt = rst_results_3[ix_rival];
                                                        }
                                                    }
                                                }
                                                //mydata.showchart = function(){ ract_draw_chart( this.data.points )};
                                            }
                                            rst_data[chartID] = mydata;
                                            mydata = null;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $("#hub_ract_chart_container").html("<h2>Trend Charts data not yet available</h2>" +
                            "<p>Please be patient the initial trend data takes a while to accumulate.</p>" +
                            "<p><img src='https://hub5050.com/img/Chart_hold.jpg' alt='Trend Charts' /></p>");
                    }
                    //draw charts in the chart containers
                    var ract_canvases = $("canvas");
                    //console.log('CANVASES', ract_canvases);
                    ract_canvases.each(function(){
                        var ract_chart_id = $(this).attr("id");
                        //console.log('DATA', rst_data[ract_chart_id]);
                        //convert and format the data into chart JSON
                        chartData = ract_create_chart(rst_data[ract_chart_id]);
                        //console.log('SET', chartData);
                        //display the charts
                        var testChart = new Chart($(this), chartData);
                    });

                    //console.log('DATA', rst_data["chart01_6_0_38"]);
                    //console.log('DEBUG', rst_data);
                },
                error: function (xhr) {
                    console.log('ERROR', ('REST call error occurred: ' + xhr.status + ' ' + xhr.statusText));
                }
            });
        } else {
            console.log('ERROR', 'Missing varz (API Seed value)');
        }
    }

    //Pie chart generation
    if ($("#hub_ract_pie_container").length > 0) {
        if (varz.key_url.length>5) {
            //get the data from the key url so that the licence needs never be exposed
            //combine the code into the REST API e.g. http://vhost9/wordpress/wp-json/ctasm/v1/chart/aHR0cHM6Ly9jcmVhdG9yc2VvLmNvbS8=/
            var api_url = 'https://hub5050.com/wp-json/ctasm/v1/pie/' + varz.key_url + '/';
            //var api_url = 'http://vhost9/wordpress/wp-json/ctasm/v1/pie/' + varz.key_url + '/';
            //console.log('REST Data API', api_url);
            $.ajax({
                url: api_url,
                method: 'GET',
                success: function (rest_info) {
                    //console.log('success', rest_info);
                    var rst_url = rest_info.info.url;
                    var rst_atts = rest_info.atts;
                    var rst_results = rest_info.pie;
                    var rst_data = {};

                    $("#hub_ract_pie_container").html("");
                    if (rst_results){
                        for (var ix_page in rst_results) {
                            if (rst_results.hasOwnProperty(ix_page)) {
                                var rst_results_1 = rst_results[ix_page];
                                for (var ix_trait in rst_results_1) {
                                    if (rst_results_1.hasOwnProperty(ix_trait)) {
                                        var rst_results_2 = rst_results_1[ix_trait];
                                        var chartID = "chart02_" + ix_page + "_" + ix_trait + "_pie";
                                        $("#hub_ract_pie_container").append("<h3>" + rst_results_2['title_2'].toUpperCase() + "<span class='hub_ract_fade'>: " + rst_url + "</span></h3>");
                                        $("#hub_ract_pie_container").append("<canvas class='hub_ract_canvas' id='" + chartID + "'></canvas><hr />");
                                        mydata = {
                                            chart: chartID,
                                            engine: rst_results_2['engine'],
                                            labels: rst_results_2['labels'],
                                            points: rst_results_2['values']
                                        };
                                        rst_data[chartID] = mydata;
                                        //console.log('ID', chartID);
                                        //console.log('DATA', mydata);
                                        mydata = null;
                                    }
                                }
                            }
                        }
                    } else {
                        $("#hub_ract_pie_container").html("<h2>Pie Charts data not yet available</h2>" +
                            "<p>Please be patient the competitor space data takes a while to accumulate.</p>" +
                            "<p><img src='https://hub5050.com/img/Chart_hold.jpg' alt='Trend  Charts' /></p>");
                    }
                    //draw charts in the chart containers
                    var ract_canvases = $("canvas");
                    //console.log('CANVASES', ract_canvases);
                    ract_canvases.each(function(){
                        var ract_chart_id = $(this).attr("id");
                        //console.log('ID', ract_chart_id);
                        //console.log('DATA', rst_data[ract_chart_id]);
                        //convert and format the data into chart JSON
                        chartData = ract_create_pie(rst_data[ract_chart_id]);
                        //console.log('SET', chartData);
                        //display the charts
                        var testChart = new Chart($(this), chartData);
                    });
                },
                error: function (xhr) {
                    console.log('ERROR', ('REST call error occurred: ' + xhr.status + ' ' + xhr.statusText));
                }
            });
        }
    }

    //full suite of social charts - attributes in the PHP function control which charts are displayed
    //note: the attrib post variable is defined as show_all_social | show_histogram | show_combined | show_detail
    if ($("#hub_ract_ajax_container").length > 0) {
        var stamp = new Date(Date.now());
        var mytxt = ''; var myhead = '';
        var ract_action = 'ract_social_charts';
        $.ajax({
            url: varz.ajax_url,
            type: 'post',
            data: {action: ract_action, my_hash: 'none', attrib: '0|1|1|0'},
            dataType: 'json',
            success : function(data,status){
                $("#hub_ract_ajax_container").html('');
                // $("#hub_ract_social_container").append('<h3>Status Check</h3><p><li>DATE: ' + stamp + '</li>');
                // $("#hub_ract_social_container").append('<li>TYPE: ' + data['type'] + '</li></p><hr />');
                if (data['type'] == 'chart'){
                    var rst_data = data.sets; //data sets array each element fully specifies a chart
                    if (rst_data) {
                        for (var ix_set in rst_data) {
                            if (rst_data.hasOwnProperty(ix_set)) {
                                var rst_data_set = rst_data[ix_set];
                                var chartID = rst_data_set['id'];
                                var chartTitle = rst_data_set['title'];
                                var chartData = rst_data_set['data'];
                                mytxt = rst_data_set['title_1'].length>0? '<h3>' + rst_data_set['title_1'] + '</h3>': '';
                                mytxt += rst_data_set['title_2'].length>0? '<h4>' + rst_data_set['title_2'] + '</h4>': '';
                                mytxt += rst_data_set['title_3'].length>0? '<h5>' + rst_data_set['title_3'] + '</h5>': '';
                                //mytxt += '<h3>ID: ' + chartID + '</h3>';
                                //mytxt += '<p>' + JSON.stringify(chartData) + '</p>';
                                //Create the chart placeholder with a specified id
                                mytxt += '<canvas class="test" id="' + chartID + '"></canvas><hr />';
                                $("#hub_ract_ajax_container").append('<div>' + mytxt + '</div>');
                                //Draw the chart at the id placeholder using the chartData returned from PHP
                                var testChart = new Chart(chartID, chartData);
                            }
                        }
                    }
                } else if (data['type'] == 'html'){
                    $("#hub_ract_ajax_container").append('<div>' + data['output'] + '</div>');
                } else {
                    $("#hub_ract_ajax_container").append('<div><h2>Error</h2><p>Data type not recognised</p></div>');
                }
            },
            error: function (xhr, status, errorThrown) {
                mytxt = '<h3>ERROR</h3><p>' + status + ' [' + errorThrown + ']<br />' + xhr.responseText + '</p>';
                $("#hub_ract_ajax_container").html('<div><p>' + mytxt + '</p></div>');
            }
        });
        return false; //prevent the browser from following the link
    }

})(jQuery);


function ract_create_chart( chartInfo ){
    //console.log('CHART DATA', chartInfo);
    var colorz = ["#3e95cd", "#8e5ea2","#3cba9f","#c45850","#b4ba1a", "e4701b"];
    var timeFormat = 'YYYY-MM-DD';
    var dataSets = [];
    var dataSet = chartInfo.points;
    var maxPt = 0;
    var ix = 0;
    var checkPoint = 0;
    for (var legend in dataSet){
        var mydata = [];
        var pt = 0;
        for (var xdata in dataSet[legend]){
            pt++;
            var ydata = dataSet[legend][xdata]==0? 50: dataSet[legend][xdata];
            mydata.push({ x: xdata, y: ydata })
        }
        maxPt = pt>maxPt? pt: maxPt;
        //console.log('MYDATA', mydata);
        dataSets.push({
            label: legend,
            borderWidth: 2,
            //backgroundColor: '#cdcdcd',
            backgroundColor: colorz[ix],
            borderColor: colorz[ix],
            fill: false,
            lineTension: 0,
            pointRadius: (pt>50? 0: 1),
            data: mydata
        });
        ix++;
    }
    timeUnit = maxPt > 500? 'year': (maxPt > 250? 'quarter': (maxPt > 50? 'month': 'day'));
    var chartMap = {
        type: 'line',
        data: {
            datasets: dataSets
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: chartInfo.keyword,
                fontSize: 20
            },
            scales: {
                xAxes: [{
                    type: 'time',
                    time: {
                        format: timeFormat,
                        unit: timeUnit,
                        tooltipFormat: 'll'
                    },
                    position: 'bottom',
                    scaleLabel: {
                        display: true,
                        labelString: 'Date'
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        reverse: true
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Ranking Position'
                    }
                }]
            },
            legend: {
                position: 'right',
                labels: {
                    fontSize: 12
                }
            }
        }
    }
    return chartMap ;
}

function ract_create_pie( chartInfo ){
    //console.log('CHART DATA', chartInfo);
    var colorset1 = ['#3e95cd', '#8e5ea2','#3cba9f','#e8c3b9','#c45850'];
    var colorset2 = ['#e6194b', '#3cb44b', '#ffe119', '#0082c8', '#f58231', '#911eb4', '#46f0f0', '#f032e6', '#d2f53c',
        '#fabebe', '#008080', '#e6beff', '#aa6e28', '#fffac8', '#800000', '#aaffc3', '#808000', '#ffd8b1', '#000080'];
    var dataSets = [];
    var title = chartInfo.engine;
    var labels = chartInfo.labels;
    var dataSet = chartInfo.points;
    //console.log('MYDATA', mydata);
    dataSets.push({
        fill: false,
        backgroundColor: colorset2,
        borderColor: '#cdcdcd',
        borderWidth: 1,
        lineTension: 0,
        pointRadius: 2,
        hoverBorderColor: '#777',
        hoverBorderWidth: 3,
        hoverRadius: 2,
        label: 'Contenders',
        data: dataSet
    });
    var chartMap = {
        type: 'pie',
        data: {
            datasets: dataSets,
            labels: labels
        },
        options: {
            responsive: true,
            legend: {
                display: true,
                position: 'right',
                labels: {
                    fontSize: 10
                }
            },
            title: {
                display: false,
                text: title,
                fontSize: 20
            },
            scales: {
                display: false
            },
            cutoutPercentage: 70
        }
    }
    return chartMap ;
}
