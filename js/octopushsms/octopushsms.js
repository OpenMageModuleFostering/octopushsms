//(function ($j) {
var ajaxurl;
//var campaignForm;

function initTab(url, campaignFormulaire) {
    ajaxurl = url;
    //campaignForm = campaignFormulaire;
    //$j(function () {
    // init the table in list or detail view
    //initTableSorter();
    //initTable();

    // if in detail view
    if ($j('#sendsms_form').length > 0) {
        //initButtons();

        $j('input[id^=sendsms]').keyup(function (i) {
            $j(this).val($j(this).val().ucfirst());
        });

        $j('#add_recipient').click(function (e) {
            if (campaignForm.validator.validate()) {
                addFreeRecipient();
            }
        });

        $j('#add_csv').click(function (e) {
            if (campaignForm.validator.validate()) {
                addCSV();
            }
        });

        $j('#sendsms_cancel').click(function (e) {
            e.stopPropagation();
            return confirm(sendsms_confirm_cancel);
        });

        $j('#sendsms_delete').click(function (e) {
            e.stopPropagation();
            return confirm(sendsms_confirm_delete);
        });

        $j('#sendsms_duplicate').click(function (e) {
            $j('#action').val('SendsmsSendTab');
        });

        $j('#sendsms_query_add').click(function (e) {
            if (campaignForm.validator.validate()) {
                addRecipientsFromQuery();
            }
        });

        $j('#sendsms_query_orders_from, #sendsms_query_orders_to').keyup(function (e) {
            $j('#sendsms_query_orders_none').attr('checked', false);
            if ($j(this).val() != '') {
                if (isNaN($j(this).val()))
                    $j(this).val('');
                else if ($j(this).val() < 1) {
                    $j(this).val('');
                    alert(sendsms_error_orders);
                }
            }
        });

        $j('#sendsms_query_orders_none').click(function (e) {
            if ($j(this).attr('checked')) {
                $j('#sendsms_query_orders_from').val('');
                $j('#sendsms_query_orders_to').val('');
            }
        });

        $j('#sendsms_query select, #sendsms_query input').change(function (e) {
            countRecipientsFromQuery();
        });

        $j('.octopushsms-autocomplete li').click(function (e) {
            $j('#sendsms_customer_filter').val($(this).val());
        });
    }
    //});
}

function initButtons(show) {
    status = $j('#current_status').val();
    if (status == 0) {
        $j('#sendsms_save').show();
    } else {
        $j('#sendsms_save').hide();
    }
    if (status <= 1 && $j('#nb_recipients').html() > 0) //	$jthis->_campaign->status <= 1 && !Tools::isSubmit('sendsms_transmit')
        $j('#sendsms_transmit').show();
    else
        $j('#sendsms_transmit').hide();
    if (status == 2 && $j('#nb_recipients').html() > 0) {
        $j('#sendsms_validate').show();
    } else {
        $j('#sendsms_validate').hide();
    }
    if (status > 0 && status < 3) {
        $j('#sendsms_cancel').show();
    } else {
        $j('#sendsms_cancel').hide();
    }
    if ($j('#id_sendsms_campaign').val() > 0 && (status == 0 || status >= 3)) {
        $j('#sendsms_delete').show();
    } else {
        $j('#sendsms_delete').hide();
    }
    if ($j('#id_sendsms_campaign').val() > 0)
        $j('#sendsms_duplicate').show();
    if (show) {
        $j('#buttons').show();
    } else {
        $j('#buttons').hide();
    }

    countRecipientsFromQuery();
}

String.prototype.ucfirst = function () {
    return this.charAt(0).toUpperCase() + this.substr(1).toLowerCase();
};

function checkPhone(phone, international) {
    var reg = new RegExp("^[+]" + (international ? "" : "?") + "[0-9]{8,15}$");
    if (reg.test(phone) == 0) {
        alert(sendsms_error_phone_invalid);
        return false;
    }
    return true;
}

function addFreeRecipient() {
    var customer = {'id_customer': '', 'phone': $j.trim($j('#sendsms_phone').val()), 'firstname': $j.trim($j('#sendsms_firstname').val()), 'lastname': $j.trim($j('#sendsms_lastname').val()), 'iso_country': '', 'country': ''};
    return addRecipient(customer, true, true);
}

function addRecipient(customer, reset, international) {
    if ( customer.phone && checkPhone(customer.phone, international)) {
        $j.ajax({
            type: "POST",
            async: false,
            cache: false,
            url: ajaxurl + "ajaxAddRecipient",
            dataType: "json",
            data: $j('#sendsms_form').serialize() + "&ajax=1&action=addRecipient&phone=" + encodeURIComponent(customer.phone) + "&firstname=" + customer.firstname + "&lastname=" + customer.lastname + "&id_customer=" + customer.id_customer + "&iso_country=" + customer.iso_country + "&form_key=" + $j('#form_key').val(),
            success: function (data) {
                if (data) {
                    if (data.error)
                        alert(data.error);
                    else {
                        $j('#id_sendsms_campaign').val(data.campaign.id_sendsms_campaign);
                        $j('#id_campaign').html(data.campaign.id_sendsms_campaign);
                        $j('#ticket').html(data.campaign.ticket);
                        $j('#sendsms_title').val(data.campaign.title);
                        $j('#nb_recipients').html(data.campaign.nb_recipients);
                        initButtons();
                        Octopushsms.refreshGrid();                        
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus + " " + errorThrown);
            }
        });
    }
    if (reset) {
        $j('#sendsms_firstname').val('');
        $j('#sendsms_lastname').val('');
        $j('#sendsms_phone').val('').focus();
    }
    return false;
}

function delRecipient(obj) {
    Octopushsms.showWaiting(sendsms_msg_delRecipient);
    $j.ajax({
        type: "POST",
        async: false,
        cache: false,
        url: ajaxurl + "ajaxDeleteRecipient",
        dataType: "json",
        data: "action=delRecipient&" + "id_sendsms_campaign=" + $j('#id_sendsms_campaign').val() + "&id_sendsms_recipient=" + $j(obj).attr('id')+ "&form_key=" + $j('#form_key').val(),
        success: function (data) {
            if (data.valid) {
                $j(".displaying-num").text(data.campaign.nb_recipients + " elements");
                initButtons();
                Octopushsms.hideWaiting();
                Octopushsms.refreshGrid();
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            Octopushsms.hideWaiting();
            alert(errorThrown);
        }
    });
    return false;
}

function transmitToOWS() {
    $j('#sendsms_transmit').attr('disabled', true);
    $j.ajax({
        type: "POST",
        async: true,
        cache: false,
        url: ajaxurl + "ajaxTransmitOws",
        dataType: "json",
        data: "action=transmitOWS&id_sendsms_campaign=" + $j('#id_sendsms_campaign').val() + "&form_key=" + $j('#form_key').val(),
        success: function (data) {
            if (data) {
                if (data.error) {
                    $j('#sendsms_transmit').attr('disabled', false);
                    initButtons(true);
                    Octopushsms.hideWaiting();
                    //refresh recipient list
                    Octopushsms.refreshGrid();
                    alert(data.error);
                } else {
                    $j('#nb_recipients').html(data.campaign.nb_recipients);
                    //$j('#nb_sms').html(data.campaign.nb_sms);// .toFixed(3)
                    $j('#price').html(data.campaign.price + ' €');
                    $j('#waiting_transfert').html(data.total_rows);

                    if (data.message) {
                        $j("#loading_mask_loader").contents().last()[0].textContent = data.message;
                    }
                    if (data.finished) {
                        //refresh recipient list
                        Octopushsms.refreshGrid();                    
                        Octopushsms.hideWaiting();
                        $j('#status').html(data.campaign.status_label);
                        $j('#current_status').val(data.campaign.status);
                        initButtons(true);
                    } else {
                        Octopushsms.showWaiting(null);
                        transmitToOWS();
                    }
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $j('#sendsms_transmit').attr('disabled', false);
            initButtons(true);
            alert(errorThrown);
        }
    });
}

function addCSV() {
    if (!$j('#sendsms_csv').val())
        alert(sendsms_error_csv);
    else
        $j('#sendsms_form').submit();
}

function countRecipientsFromQuery() {
    if ($j('#sendsms_query_result').length > 0) {
        var query = '';
        $j('#sendsms_query select, #sendsms_query input[type=text], #sendsms_query input:checked').each(function (e) {
            query += '&' + $j(this).attr('name') + '=' + $j(this).val();
        });

        $j.ajax({
            type: "POST",
            async: true,
            cache: false,
            url: ajaxurl + "ajaxCountRecipientFromQuery",
            dataType: "json",
            data: "action=countRecipientFromQuery&id_sendsms_campaign=" + $j('#id_sendsms_campaign').val() + query + "&form_key=" + $j('#form_key').val(),
            success: function (data) {
                if (data) {
                    $j('#sendsms_query_result').html(data.total_rows);
                    if (data.total_rows > 0)
                        $j('#sendsms_query_add').show();
                    else
                        $j('#sendsms_query_add').hide();
                }
            }
        });
    }
}

function addRecipientsFromQuery() {
    if ($j('#sendsms_query_result').html() != 0) {
        var query = '';
        $j('#sendsms_query select, #sendsms_query input[type=text], #sendsms_query input:checked').each(function (e) {
            query += '&' + $j(this).attr('name') + '=' + $j(this).val();
        });
        Octopushsms.showWaiting('');
        $j.ajax({
            type: "POST",
            async: false,
            cache: false,
            url: ajaxurl + "ajaxAddRecipientsFromQuery",
            dataType: "json",
            data: "action=addRecipientsFromQuery&id_sendsms_campaign=" + $j('#id_sendsms_campaign').val() + query + "&form_key=" + $j('#form_key').val(),
            success: function (data) {
                Octopushsms.hideWaiting('');
                if (data) {
                    $j('#id_sendsms_campaign').val(data.campaign.id_sendsms_campaign);
                    $j('#id_campaign').html(data.campaign.id_sendsms_campaign);
                    $j('#ticket').html(data.campaign.ticket);
                    $j('#sendsms_title').val(data.campaign.title);
                    initButtons();
                    $j('#sendsms_query_result').html(0);

                    //reload recipient grid
                    Octopushsms.refreshGrid();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                Octopushsms.hideWaiting('');
                alert(errorThrown);
            }
        });
    }
}

function submitForm(elt) {
    $j("#button_id").attr('value', ($j(elt).attr("id")));
    campaignForm.submit();
}

var Octopushsms = {};

Octopushsms.refreshGrid = function () {
    $j(".content-header")[2].hide();
    recipientGridJsObject.doFilter();    
}

Octopushsms.showWaiting = function (msg) {
    //if (! ($j("#loading-mask").is(":visible")) ) {
        $j("#loading-mask").show();
    //}
    if (msg!=null) {
        $j("#loading_mask_loader").contents().last()[0].textContent = msg;
    }
}
Octopushsms.hideWaiting = function () {
    $j("#loading-mask").hide();
}
/**
 * Quick Search form client model
 */
Octopushsms.searchForm = Class.create();
Octopushsms.searchForm.prototype = {
    initialize: function (form, field, emptyText) {
        this.form = $(form);
        this.field = $(field);
        this.emptyText = emptyText;

        Event.observe(this.form, 'submit', this.submit.bind(this));
        Event.observe(this.field, 'focus', this.focus.bind(this));
        Event.observe(this.field, 'blur', this.blur.bind(this));
        this.blur();
    },
    submit: function (event) {
        if (this.field.value == this.emptyText || this.field.value == '') {
            Event.stop(event);
            return false;
        }
        return true;
    },
    focus: function (event) {
        if (this.field.value == this.emptyText) {
            this.field.value = '';
        }

    },
    blur: function (event) {
        if (this.field.value == '') {
            this.field.value = this.emptyText;
        }
    },
    initAutocomplete: function (url, destinationElement) {
        new Ajax.Autocompleter(
                this.field,
                destinationElement,
                url,
                {
                    paramName: this.field.name,
                    method: 'get',
                    minChars: 2,
                    updateElement: this._selectAutocompleteItem.bind(this),
                    onShow: function (element, update) {
                        if (!update.style.position || update.style.position == 'absolute') {
                            update.style.position = 'absolute';
                            Position.clone(element, update, {
                                setHeight: false,
                                offsetTop: element.offsetHeight
                            });
                        }
                        Effect.Appear(update, {duration: 0});
                    }

                }
        );
    },
    _selectAutocompleteItem: function (element) {
        if (element.title) {
            //this.field.value = element.textContent;
            var infoCustomer = JSON.parse(element.title);            
            var customer = {'id_customer': infoCustomer.id_customer, 'phone': infoCustomer.telephone, 'firstname': infoCustomer.firstname, 'lastname': infoCustomer.lastname, 'iso_country': '', 'country': ''};
            addRecipient(customer, true, true);
        }
        //this.form.submit();
    }
}

