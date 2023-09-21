
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

import typeahead from "jquery-typeahead";


$.typeahead({
    input: '.js-typeahead-po-batches',
    minLength: 1,
    hint: true,
    highlight: true,
    maxItem: 0,
    searchOnFocus: true,
    order: "asc",
    dynamic: true,
    asyncResult:true,
    delay: 500,
    display: ["name","sku"],
    template: function (query, item) {
        var temp_str = '<span class="row">'
            + '<span class="username" style="padding-left: 10px">{{name}} ';
        temp_str += '</span></span>';
        temp_str += '<span class="row">';
        // temp_str += '<span class="sku" style="padding-left: 10px"><small><strong>SKU:</strong> {{sku}}</small></span>';
        temp_str += '<span class="quantity" style="padding-left: 10px"><small><strong>Unit Cost:</strong> {{cost_display}}</small></span>';
        if(item.suggested_unit_sale_price) {
            temp_str += '<span class="unit_price" style="padding-left: 20px"><small><strong>Sale Price:</strong> {{suggested_unit_sale_price_display}}</small></span>';
        }
        if(item.min_flex_display) {
            temp_str += '<span class="unit_price" style="padding-left: 20px"><small><strong>Min. Flex:</strong> {{min_flex_display}}</small></span>';
        }
        temp_str += "</span>";
        return temp_str;
    },
    emptyTemplate: "no result for {{query}}",
    source: {
        batches: {
            ajax: function (query) {
                return {
                    type: "GET",
                    url: "/batches/search-all",
                    path: "data.batches",
                    data: {
                        q: "{{query}}",
                        cost: "1",
                    },
                    callback: {
                        done: function (data) {
                            return data;
                        }
                    },
                    statusCode: {
                        401: function () {
                            location.reload();
                        }
                    }
                }
            }
        }
    },
    callback: {
        onClick: function (node, a, item, event) {

            node.parents('form#add-batch-item').find('input[name="_batches[name]"]').val(item.orig_name);
            node.parents('form#add-batch-item').find('input[name="_batches[unit_cost]"]').val(item.cost);
            node.parents('form#add-batch-item').find('input[name="_batches[suggested_unit_sale_price]"]').val(item.suggested_unit_sale_price);
            node.parents('form#add-batch-item').find('input[name="_batches[min_flex]"]').val(item.min_flex);
            node.parents('form#add-batch-item').find('select[name="_batches[uom]"]').val(item.uom);

            node.parents('form#add-batch-item').find('select[name="_batches[category_id]"]').val(item.category_id);
            if(item.brand_id) {
                node.parents('form#add-batch-item').find('select[name="_batches[brand_id]"]').val(item.brand_id);
            }

            if(item.type) {
                node.parents('form#add-batch-item').find('select[name="_batches[type]"]').val(item.type);
            }
        },
        onClickAfter: function (node, a, item, event) {
            node.parents('form#add-new-item').find('input#search_query').val(item.name);
        },
        onSendRequest: function (node, query) {
            // console.log('request is sent')
        },
        onReceiveRequest: function (node, query) {
            // console.log('request is received')
        },
        onCancel: function (node, item, event) {
            node.parents('form#add-batch-item').find('input[name="_batches[name]"]').val("");
            node.parents('form#add-batch-item').find('input[name="_batches[unit_cost]"]').val("");
            node.parents('form#add-batch-item').find('input[name="_batches[total_cost]"]').val("");
            node.parents('form#add-batch-item').find('input[name="_batches[suggested_unit_sale_price]"]').val("");
            node.parents('form#add-batch-item').find('input[name="_batches[min_flex]"]').val("");
            node.parents('form#add-batch-item').find('select[name="_batches[uom]"]').val("lb");
            node.parents('form#add-batch-item').find('select[name="_batches[category_id]"]').val("");
            node.parents('form#add-batch-item').find('select[name="_batches[brand_id]"]').val("");
            node.parents('form#add-batch-item').find('select[name="_batches[type]"]').val("");
        }
    },
    debug: true
});

$(function () {

    $('.prevent_double_click').submit(function(e) {

        $(this).find(':submit').prop('disabled', true);

    });

    $('.conf_action').click(function(e) {
        e.preventDefault();

        if( ! confirm('Are you sure?')) {
            return false;
        } else {
            $(this).parent().submit();
            return true;
        }
    });

    $('#sell_return_action').change(function() {
        if($('option:selected', this).text() == 'Return') {
            $('#sell_container').hide();
        } else {
            $('#sell_container').show();
        }
    });


    //PO & SO update
    $('a.qb_update').click(function (e) {
        e.preventDefault();

        let $this = this;
        let data = {"in_qb" : ($(this).data('in_qb')?0:1)};

        $.ajax({
            url: this.href,
            type: 'PUT',
            accept: 'application/json',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify(data), // access in body
            error: function() {
                alert('Error: Unable to update!');
            },
            dataType: 'json',
            success: function(so_obj) {
                $($this).data('in_qb', so_obj.in_qb);
                if(so_obj.in_qb) {
                    $($this).find('i.mdi').removeClass('text-danger').addClass('text-success');
                } else {
                    $($this).find('i.mdi').removeClass('text-success').addClass('text-danger');
                }
            },

        });

    });

    $('#date_preset').change(function(e) {
        $('#from_delivered_at').val($('#date_preset option:selected').data('date-from'));
        $('#to_delivered_at').val($('#date_preset option:selected').data('date-to'));
    });

    $('.quantity').change(function() {

        var form_grp = $(this).parents('.add_batch_row');

        if($(form_grp).find('.unit_cost').val()) {
            var qty = $(this).val();
            var unit_cost = $(form_grp).find('.unit_cost').val();
            $(form_grp).find('.total_cost').val((qty * unit_cost).toFixed(2));
        }
    });

    $('#non_inventory_item').change(function(e) {
        if($(this).is(':checked')) {
            $('.qty_row').hide();
            $('input.quantity').removeClass('required').attr('required', false);
            $('input.total_cost').removeClass('required').attr('required', false);
            $('.total_cost_group').hide();
        } else {
            $('.qty_row').show();
            $('input.quantity').addClass('required').attr('required', true);
            $('input.total_cost').addClass('required').attr('required', true);
            $('.total_cost_group').show();
        }
    });

    $('.unit_cost').change(function() {
        var form_grp = $(this).parents('.add_batch_row');
        var qty = $(form_grp).find('input.quantity').val();
        var unit_cost = $(this).val();

        $(form_grp).find('.total_cost').val((qty * unit_cost).toFixed(2));
    });

    $('.total_cost').change(function() {

        var form_grp = $(this).parents('.add_batch_row');
        var qty = $(form_grp).find('.quantity').val();
        var total_cost = $(this).val();

        if(qty) $(form_grp).find('.unit_cost').val((total_cost / qty).toFixed(2));
    });

    $('.add_batch_submit').click(function (e) {


        if($('.tab-pane:is(.active)').length) {

            $('form#add-batch-item').find('.required').each(function (idx, elem) {
                $(elem).attr('required', false);
            });

            $('.tab-pane:is(.active)').find('.required').each(function (idx, elem) {
                $(elem).attr('required', true);
                console.log(elem);
            });
        } else {
            $('form#add-batch-item').find('.required').each(function (idx, elem) {
                $(elem).attr('required', true);
            });
        }

        return true;
    });

});
