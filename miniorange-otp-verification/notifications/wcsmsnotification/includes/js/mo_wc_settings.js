
jQuery(document).ready(function () {
    var $mo = jQuery;
    if (typeof window.intlTelInput === "function" && typeof mocustommsg !== "undefined" && mocustommsg.telUtilsUrl) {
        var phoneInput = document.querySelector("#billing_phone");
        if (phoneInput && !$mo(phoneInput).data("intlTelInputInitialized")) {
            window.intlTelInput(phoneInput, {
                separateDialCode: true,
                preferredCountries: ["us", "gb", "in"],
                utilsScript: mocustommsg.telUtilsUrl
            });
            $mo(phoneInput).data("intlTelInputInitialized", true);
        }
    }
    $mo('button').on('click', function () {
        const buttonid = $mo(this).attr('id');
        if(!buttonid){
            return;
        }
        let textareaid;
        const suffix = buttonid.includes('wcfm') ? '_sms_body' : '_smsbody';
        textareaid = buttonid.replace('btn-', '') + suffix;
        if (!textareaid) {
            return;
        }
        $mo('.mo-tag').off('click').on('click', function () {
            const $tag = $mo(this);
            const tagText = $tag.text().trim();
            const $textarea = $mo('#' + textareaid);
            const textarea = $textarea[0];
            if (!$textarea.length || !textarea) {
                return;
            }
            const currentVal = $textarea.val() || '';
            const $parentContainer = $tag.closest('.w-full.flex');
            const isPremium = $parentContainer.length > 0 && $parentContainer.find('.mo-title').text().includes("Premium Tags");
            if (isPremium && mocustommsg.planName !== 'wp_email_verification_intranet_woocommerce_plan') {
                return;
            }
            if (currentVal.includes(tagText)) {
                return;
            }
            let cursorPosition = textarea ? textarea.selectionStart : currentVal.length;
            if (cursorPosition < 0) {
                cursorPosition = currentVal.length;
            }
            let newText = currentVal.slice(0, cursorPosition) + tagText + currentVal.slice(cursorPosition);
            $textarea.val(newText);
            if (textarea) {
                const newCursorPosition = cursorPosition + tagText.length;
                textarea.setSelectionRange(newCursorPosition, newCursorPosition);
                textarea.focus();
            }
        });
    });

    $mo("#mo_custom_order_send_message").on("click", function () {
        $mo("#custom_order_sms_meta_box").block({
            message: null,
            overlayCSS: { background: "#fff", opacity: 0.6 }
        });
        if (typeof mocustommsg === "undefined" || !mocustommsg.siteURL || !mocustommsg.nonce) {
            $mo("#custom_order_sms_meta_box").unblock();
            return;
        }

        $mo.ajax({
            url: mocustommsg.siteURL + "?mo_send_custome_msg_option=mo_send_order_custom_msg",
            type: "POST",
            data: {
                mo_send_custome_msg_option: "mo_send_order_custom_msg",
                security: mocustommsg.nonce,
                numbers: $mo("#custom_order_sms_meta_box #billing_phone").val(),
                msg: $mo("#custom_order_sms_meta_box #mo_wc_custom_order_msg").val()
            },
            crossDomain: !0,
            dataType: "json",
            success: function (a) {
                $mo("#custom_order_sms_meta_box").unblock();
                $mo("#jsonMsg").empty();
                if (a.result == "success") {
                    $mo("#jsonMsg").removeClass("red").addClass("green");
                } else {
                    $mo("#jsonMsg").removeClass("green").addClass("red");
                }
                $mo("#jsonMsg").prepend(a.message).show();
            },
            error: function () {
                $mo("#custom_order_sms_meta_box").unblock();
            }
        });
    });
});