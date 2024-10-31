/* Hub5050 – Ranking and Competitor Tracking v.1.0.0 */

(function($) {

    if (varz.key_url && varz.key_url.length>5) {
        if ($("#hub_ract_get_api_btn").length > 0) {
            var license_check = $("#hub-ract-api-key-value").html();
            if (license_check.length == 5) {
                $("#hub_ract_get_api_btn").text('Refresh license');
                $("#hub-ract-api-key-value").show();
                $("#hub-ract-main-api-form").show();
            } else {
                $("#hub_ract_get_api_btn").removeClass("button-secondary");
                $("#hub_ract_get_api_btn").addClass("button-primary");
            }
            $("#hub_ract_get_api_btn").bind('click', function () {
                //fetch address for the api data on hub5050
                var api_url = 'https://hub5050.com/wp-json/ctasm/v1/adm/' + varz.key_url + '/';
                //var api_url = 'https://hub5050.com/wp-json/ctasm/v1/code/' + varz.key_url + '/0/';
                console.log('API button clicked!!', api_url);
                $.ajax({
                    url: api_url,
                    method: 'GET',
                    success: function (rst_data) {
                        console.log('success', rst_data);
                        var api_license = rst_data[1];
                        var api_level = rst_data[2];
                        console.log('license', api_license.replace(/<[^>]+>/g, ''));
                        $.ajax({
                            url: varz.ajax_url,
                            method: 'POST',
                            data: {action: 'ract_set_license', api_license: api_license},
                            success: function () {
                                $("#hub_ract_get_api_btn").text('Refresh Again');
                                $("#hub_ract_get_api_btn").removeClass('button-primary');
                                $("#hub_ract_get_api_btn").addClass('button-secondary');
                                //$("#hub_ract_get_api_btn").hide();
                                $("#hub-ract-api-key-value").text(api_license);
                                $("#hub-ract-api-key-value").show();
                                $("#hub-ract-main-api-form").show();
                                $("#hub_ract_form_api_value").attr('value', api_license.replace(/<[^>]+>/g, ''));
                                $("#hub_ract_form_level").attr('value', parseInt(api_level));
                                $("#hub_ract_btn_confirm").html('Success').fadeIn(500).fadeOut(5000);
                                console.log('PHP Call', 'Option Updated');
                            },
                            error: function (xhr) {
                                $('#hub_ract_btn_confirm').html('Error').fadeIn(500).fadeOut(5000);
                                console.log('ERROR', ('PHP call error occurred: ' + xhr.status + ' ' + xhr.statusText));
                            }
                        });
                        console.log('End', 'All done');
                    },
                    error: function (xhr) {
                        console.log('ERROR', ('REST call error occurred: ' + xhr.status + ' ' + xhr.statusText));
                    }
                });
            });
        }
    } else {
        console.log('ERROR', 'Missing API Seed value');
    }

})(jQuery);