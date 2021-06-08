jQuery(function($){
    jQuery(document).ready(function() {
    
        var get_url = window.location;
        var base_url = get_url.protocol + "//" + get_url.host + get_url.pathname.split('/wp-admin')[0];
        var img_loading = "<img src='" + base_url + "/wp-admin/images/loading.gif'>";

        $("#filter_id_customer").autocomplete({        
            source: function (requete, response) {
                $("#filter_id_customer_key").val('');
                $.ajax({
                    method: "POST",
                    url: base_url + "/wp-admin/admin-ajax.php",
                    data: {
                        action: "get_list_customer_autocomp", 
                        term: $("#filter_id_customer").val()
                    },
                    dataType: 'json',
                    success: function (data) {
                        console.log(data);
                        response($.map(data, function(object){
                            return {
                                value : object.id,
                                label : object.firstname + " " + object.lastname
                            };
                        }));
                    }
                });
            },        
            select: function(event, ui ) {
                $("#filter_id_customer").val(ui.item.label);
                $("#filter_id_customer_key").val(ui.item.value);
                $("#filter_id_customer").trigger('blur');
                return false;
            }
        });

        $("#filter_date_add, #filter_date_add_end").datepicker();

        $("#recurring-payment-list tr td.actions-lign span.dashicons").tipTip({
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 200
        });

        $("#reset-filter").on('click', function (e) {
            e.preventDefault();
            $("#filter-lign .filter-recurring-payment[type=text]").val('');
            $("#filter-lign select.filter-recurring-payment").val('-1');
            $($("#filter-lign .filter-recurring-payment[type=text]")[0]).trigger('change');
        });

        $("#filter-lign .filter-recurring-payment").on('change', function () {

            filters = {};
            $("#filter-lign .filter-recurring-payment").each(function () {
                if ($(this).val() !== "" && $(this).val() !== "-1") {
                    switch($(this).attr('id')) {
                        case "filter_id_customer" :
                            filters[$(this).attr('id')] = $("#filter_id_customer_key").val();
                            break;
                        default :
                            filters[$(this).attr('id')] = $(this).val();                        
                            break;
                    }
                }
            });

            var data = {
                "action": "get_content_recurring_payment_list",
                "filters": filters
            };

            $("#recurring-payment-list-content").html("<tr><td class='loading' colspan=11>" + img_loading + "</td></tr>");
            $.ajax({
                method: "POST",
                url: base_url + "/wp-admin/admin-ajax.php",
                data: data,
                dataType: 'json',
                success: function (data) {
                    $("#recurring-payment-list-content").html(data.html);
                    bin_event_status();
                }
            });
        });

        var bin_event_status = function () {
            $("#recurring-payment-list tr td.actions-lign span.dashicons").unbind();
            $("#recurring-payment-list tr td.actions-lign span.dashicons").on('click', function () {
                var lign = $(this).closest('tr');
                var action = $(this).attr('name');
                var recurring_payment_id = lign.attr('name');

                var data = {
                    "action": "update_recurring_payment_status",
                    "recurring_payment_id": recurring_payment_id,
                    "action_libelle": action
                };

                lign.find('.status-lign').html(img_loading);
                $.ajax({
                    method: "POST",
                    url: base_url + "/wp-admin/admin-ajax.php",
                    data: data,
                    dataType: 'json',
                    success: function (data) {
                        if (data.html === "") {
                            lign.remove();
                        } else {
                            lign.find('.status-lign').html(data.html);
                        }
                    }
                });
            });
        };
        bin_event_status();
    
    });
});