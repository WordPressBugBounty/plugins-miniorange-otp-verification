if (typeof $mo === 'undefined') {
    let $mo = jQuery;
}

function mo_wpforms_get_field_value(formId, otpType) {
    let sel = '#wpforms-' + formId + '-field_' + mowpforms.formDetails[formId][otpType + 'key'];
    let $el = $mo(sel);
    if (!$el.length) {
        return '';
    }
    if ($el.is('input, select, textarea')) {
        return $el.val() || '';
    }
    let $input = $el.find('input[type="tel"], input[type="email"], input[type="text"]').first();
    if ($input.length) {
        return $input.val() || '';
    }
    return '';
}

$mo(document).ready(function () {
    $mo("div.wpforms-container").each(function () {
        //fetch the form id for the form
        let formId = $mo(this).attr('id').replace('wpforms-', '');
        if (formId in mowpforms.formDetails) {
            mowpforms.otpType.forEach(function (otpType) {
                addButtonAndFieldsWpForms(formId, otpType);
                bindSendOTPButtonWpForms(formId, otpType);
                bindVerifyButtonWpForms(formId, otpType);
            });
        }
    });
});

function addButtonAndFieldsWpForms(formid, otpType) {
    // Space above/below the Send OTP block to match WPForms field gaps (avoid flush to phone input).
    let containerCSS = 'style="margin-top:24px;margin-bottom:24px;margin-left:0;margin-right:0;"';
    let buttonCSS = 'style="margin:0px;"';

    // messagebox template
    let messageBox = '<div ' +
        'id="mo_message' + otpType + formid + '" ' +
        'style="width:auto; display: none; font-size: 16px; padding: 10px 20px;border-radius: 10px; margin-top: 16px;">' +
        '</div>';

    //Verification field
    let verifyField = '<div id="mo_verify-container' + otpType + formid + '" ' +
        'class="wpforms-field wpforms-field-text" style="display:none;" >' +
        '<label class="wpforms-field-label" for="mo_verify_otp">' +
        mowpforms.fieldText +
        '</label>' +
        '<input type="text" ' +
        'id="mo_verify_otp_' + otpType + formid + '" ' +
        'class="wpforms-field-medium wpforms-field-required"' +
        'name="mo_verify_otp" />' +
        '</div>';

    //Verify Button
    let verifyOTPButton = '<div id="wpforms-submit-container' + otpType + formid + '" ' +
        'class="wpforms-submit-container" style="margin:0px; display:none;">' +
        '<input type="button" ' +
        'name="mo_verify_button_' + otpType + formid + '" ' +
        'class="wpforms-submit wpforms-page-button" ' +
        'id="mo_verify_button_' + otpType + formid + '" ' +
        buttonCSS +
        ' value="' + mowpforms.verifyButtonText + '" />' +
        '<input type="hidden" ' +
        'id="mo_wpforms_nonce_' + otpType + formid + '" ' +
        'name="mo_wpforms_nonce" ' +
        'value="' + mowpforms.formNonce + '" />' +
        '</div>';

    //Send OTP button
    let sendOTPButton = '<div class="wpforms-submit-container" ' + containerCSS + '>' +
        '<input type="button" ' +
        'name="mo_send_otp_' + otpType + formid + '" ' +
        'class="wpforms-submit wpforms-page-button" ' +
        'id="mo_send_otp_' + otpType + formid + '"' +
        buttonCSS +
        ' value="' + mowpforms.buttontext + '"/>' +
        '</div>';

    let html = sendOTPButton + messageBox + verifyField + verifyOTPButton;
    let fieldSelector = '#wpforms-' + formid + '-field_' + mowpforms.formDetails[formid][otpType + 'key'];

    $mo(html).insertAfter(fieldSelector);
    let $field = $mo(fieldSelector);
    $field.find('.iti').css({ width: '100%', maxWidth: '100%' });
}

function bindSendOTPButtonWpForms(formId, otpType) {
    let img = "<div class='moloader'></div>"; // image HTML templates

    $mo('#mo_send_otp_' + otpType + formId).click(function () {
        let userInput = mo_wpforms_get_field_value(formId, otpType);
        
        $mo("#mo_message" + otpType + formId).empty();
        $mo("#mo_message" + otpType + formId).append(img);
        $mo("#mo_message" + otpType + formId).show();
        
        $mo.ajax({
            url: mowpforms.siteURL,
            type: "POST",
            data: {
                user_email: userInput,
                user_phone: userInput,
                otptype: otpType,
                security: mowpforms.gnonce,
                action: mowpforms.gaction,
            },
            crossDomain: true,
            dataType: "json",
            success: function (response) {
                if (response.result === "success") {
                    //if otp was sent successfully
                    $mo("#mo_message" + otpType + formId).empty();
                    $mo("#mo_message" + otpType + formId).append(response.message);
                    $mo("#mo_message" + otpType + formId).css({
                        "background-color": "#dbfff7",
                        "color": "#008f6e"
                    });
                    $mo("#mo_verify-container" + otpType + formId + ",#wpforms-submit-container" + otpType + formId).show();
                } else {
                    // if otp wasn't sent successfully
                    $mo("#mo_message" + otpType + formId).empty();
                    $mo("#mo_message" + otpType + formId).append(response.message);
                    $mo("#mo_message" + otpType + formId).css({
                        "background-color": "#ffefef",
                        "color": "#ff5b5b"
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    });
}

function bindVerifyButtonWpForms(formId, otpType) {
    // image HTML templates
    let img = "<div class='moloader'></div>";

    $mo('#mo_verify_button_' + otpType + formId).click(function () {
        // Safely set window.verifyOTPmessage
        if (typeof window !== 'undefined') {
            window.verifyOTPmessage = img;
        }
        
        let otpToken = $mo('#mo_verify_otp_' + otpType + formId).val();
        let userInput = mo_wpforms_get_field_value(formId, otpType);
        
        $mo("#mo_message" + otpType + formId).empty();
        $mo("#mo_message" + otpType + formId).append(img);
        $mo("#mo_message" + otpType + formId).show();
        
        $mo.ajax({
            url: mowpforms.siteURL,
            type: "POST",
            data: {
                user_email: userInput,
                user_phone: userInput,
                otp_token: otpToken,
                otptype: otpType,
                security: mowpforms.vnonce,
                action: mowpforms.vaction,
            },
            crossDomain: true,
            dataType: "json",
            success: function (response) {
                $mo("#mo_message" + otpType + formId).empty();
                if (response.result === "success") {
                    if (typeof window !== 'undefined') {
                        delete window.verifyOTPmessage;
                    }
                    $mo("#mo_verify-container" + otpType + formId + ",#wpforms-submit-container" + otpType + formId).hide();
                    $mo("#mo_send_otp_" + otpType + formId).closest(".wpforms-submit-container").hide();
                    let successText = (typeof mowpforms.otpVerifiedMessage !== "undefined" && mowpforms.otpVerifiedMessage)
                        ? mowpforms.otpVerifiedMessage
                        : "OTP Verification successful.";
                    $mo("#mo_message" + otpType + formId).empty().text(successText).show().css({
                        "background-color": "#dbfff7",
                        "color": "#008f6e",
                        "font-size": "16px",
                        "padding": "12px 16px",
                        "border-radius": "10px",
                        "margin-top": "16px",
                        "width": "100%",
                        "box-sizing": "border-box"
                    });
                } else {
                    // if otp wasn't sent successfully
                    $mo("#mo_message" + otpType + formId).empty();
                    $mo("#mo_message" + otpType + formId).append(response.message);
                    $mo("#mo_message" + otpType + formId).css({
                        "background-color": "#ffefef",
                        "color": "#ff5b5b"
                    });
                    if (typeof window !== 'undefined') {
                        window.verifyOTPmessage = response.message;
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    });
}


