/**
 * Shared, form-agnostic email fallback for phone OTP failures. Watches every AJAX
 * response for `needsEmailField`, grabs the configured email field's value from the
 * page, and retries the OTP send as an email via the existing "Resend OTP" mechanism.
 */

(function () {
    const $mo = window.$mo || window.jQuery;
    if (!$mo || typeof moEmailFallback === 'undefined') {
        return;
    }

    /**
     * @param {jQuery} context  jQuery-wrapped element to search within.
     * @param {string[]} fieldIds  Field names/IDs configured by the admin as possible email fields.
     * @return {{value: string, fieldExists: boolean}}
     */
    function collectFallbackEmailField(context, fieldIds) {
        let result = { value: '', fieldExists: false };
        if (!fieldIds || !fieldIds.length || !context || !context.length) {
            return result;
        }

        for (let i = 0; i < fieldIds.length; i++) {
            let fieldId = fieldIds[i];
            if (!fieldId) {
                continue;
            }
            let field = context.find('[name="' + fieldId + '"], [id="' + fieldId + '"]');
            if (field.length) {
                result.fieldExists = true;
                let value = field.val();
                if (value) {
                    result.value = value;
                    return result;
                }
            }
        }

        return result;
    }

    function findMessageBox(context) {
        let msgBox = $mo(context || document).find('[id^="mo_message"]').first();
        if (!msgBox.length) {
            msgBox = $mo('[id^="mo_message"]').first();
        }
        return msgBox;
    }

    $mo(document).ajaxSuccess(function (event, jqXHR, ajaxOptions, data) {
        if (!data || !data.needsEmailField) {
            return;
        }

        let msgBox = findMessageBox(ajaxOptions && ajaxOptions.context);
        let found = collectFallbackEmailField($mo(document), moEmailFallback.fallbackEmailFields);

        if (!found.fieldExists) {
            if (msgBox.length && data.defaultMessage) {
                msgBox.empty().append(data.defaultMessage).show();
            }
            return;
        }

        if (!found.value) {
            if (msgBox.length) {
                msgBox.empty().append(moEmailFallback.pleaseFillEmailMessage).show();
            }
            return;
        }

        $mo.post(window.location.href, {
            option: 'verification_resend_otp',
            otp_type: 'email',
            from_both: 0,
            mopopup_wpnonce: moEmailFallback.nonce,
            mo_fallback_email: found.value
        }, function (response) {
            if (!response || !response.message) {
                return;
            }
            let framedMessage = response.result === 'success'
                ? moEmailFallback.phoneFailedPrefix + ' ' + response.message
                : response.message;

            if (msgBox.length) {
                msgBox.empty().append(framedMessage).show();

                if (response.result === 'success') {
                    msgBox.siblings(':hidden').show();
                }
            }
        }, 'json');
    });
})();
