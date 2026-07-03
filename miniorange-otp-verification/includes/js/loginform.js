function moLooksLikeEmailLoginIdentifier(str) {
    str = (str || '').trim();
    return /^[^\s@]+@[^\s@]+(\.[^\s@]+)*$/.test(str);
}

(function ($) {
    /**
     * Classic Woo templates use label[for="password"]; some themes/blocks only render the input.
     */
    window.moWcFormHasPasswordField = function () {
        return $('.woocommerce-form-login label[for="password"]').length > 0
            || ($('.woocommerce-form-login input#password').length > 0 && $('.woocommerce-form-login input[name="password"]').length > 0);
    };
    /**
     * WooCommerce uses <button> for submit; .val() only changes the value attribute, not the visible label.
     */
    window.moSetWcLoginButtonLabel = function ($btn, text) {
        if (!$btn || !$btn.length || text === undefined || text === null) {
            return;
        }
        text = String(text);
        $btn.attr('value', text);
        var el = $btn.get(0);
        if (el && el.tagName && el.tagName.toLowerCase() === 'button') {
            $btn.html(text);
        }
    };
})(jQuery);

jQuery(document).ready(function () {
    let $mo = jQuery;
    window.$mo = $mo;

    // After no-phone OTP redirect (?mo_otp_no_phone=1), keep a POST marker so password login must complete phone enrollment.
    // Must run before URL cleanup below; complements PHP hooks on login_form / woocommerce_login_form / UM.
    var moInitialQs = window.location.search || '';
    if (/[?&]mo_otp_no_phone=1(?:&|$)/.test(moInitialQs)) {
        var moForceEnrollInput = '<input type="hidden" name="mo_force_phone_enrollment_after_password" value="1" />';
        $mo('#loginform, .woocommerce-form-login, .um-login form').each(function () {
            var $f = $mo(this);
            if ($f.find('input[name="mo_force_phone_enrollment_after_password"]').length === 0) {
                $f.append(moForceEnrollInput);
            }
        });
    }

    //html button for default form
    let htmlButtonWp = '<p>' +
        '<input type="submit"' +
        'name="wp-submit"' +
        'id="wp-submit"' +
        'class="button button-primary button-large"' +
        'style="width:48%;float:right;' + movarlogin.loginPassButtonCSS + '"' +
        'value="' + movarlogin.loginOTPButtonText + '">' +
        '</p>';
    
    // html button for woocommerce form
    let htmlButtonWc = '<button type="submit" ' +
        'class="woocommerce-Button button" ' +
        'style="width:50%;float:right;padding: 1.4em 2em; margin-top: 1.1em;' + movarlogin.loginPassButtonCSS + '"' +
        'name="login" ' +
        'value="' + movarlogin.loginOTPButtonText + '"> </button>';

    // html button for ultimate member form
    let htmlButtonUm = '<div class="um-right um-half">' +
        '<input type="submit" name="logintype" value="' + movarlogin.loginOTPButtonText + '" class="um-button um-alt" style="' + movarlogin.loginPassButtonCSS + '">' +
        '</div>' +
        '<div class="um-clear"></div>';

    // if userLabel option has been set then change the label of the username field
    if (movarlogin.userLabel) {
        // for ultimate member
        if ($mo('.um-login').length > 0) {
            $mo('.um-login label[for^=username-]').text(movarlogin.userLabel);
        }
        // for default login form
        if ($mo('#loginform label[for="user_login"]').length > 0) {
            let html = $mo('label[for="user_login"]').html().replace("Username or Email Address", movarlogin.userLabel);
            $mo('label[for="user_login"]').html(html);
        }
        // for woocommerce login form
        if ($mo('.woocommerce-form-login').length > 0) {
            $mo('label[for^=username]').text(movarlogin.userLabel);
        }
    }
    
    // if password skip has been set in the setting and the fall back option is not enabled
    // hide the password field and then just show the username field
    if (movarlogin.skipPwdCheck && !movarlogin.skipPwdFallback) {
        let passSelector;
        let btnSelector;
        let userSelector;
        let loginBtnText;

        // for ultimate member login form
        if ($mo('.um-login').length > 0) {
            passSelector = '.um-login .um-field-password';
            btnSelector = '.um-login #um-submit-btn';
            userSelector = 'input[name^="username"]';
            loginBtnText = movarlogin.loginOTPButtonText;
            $mo('.um-login .um-field-password').hide();
            $mo('.um-login #um-submit-btn').val(movarlogin.loginOTPButtonText)
                .append('<input hidden name="logintype" value="' + movarlogin.loginOTPButtonText + '"/>');
        }
        // for default login form
        if ($mo('#loginform label[for="user_pass"]').length > 0) {
            passSelector = '#loginform label[for="user_pass"]';
            btnSelector = "#loginform #wp-submit";
            userSelector = 'input#user_login';
            loginBtnText = $mo(btnSelector).val();
            $mo(passSelector).parent().hide();
            $mo('#user_pass').removeAttr("required");
            $mo(btnSelector).val(movarlogin.loginOTPButtonText);
        }
        // for woocommerce login form
        if (moWcFormHasPasswordField()) {
            passSelector = 'label[for="password"]';
            btnSelector = $mo(".woocommerce-form-login .woocommerce-Button[type='submit']").length ? ".woocommerce-form-login .woocommerce-Button[type='submit']" : ".woocommerce-form-login .woocommerce-button[type='submit'], .woocommerce-button[name=login], button.woocommerce-form-login__submit";
            userSelector = 'input#username';
            loginBtnText = $mo(btnSelector).val();
            $mo('#password').removeAttr("required");
            $mo('input[name=password]').removeAttr("required");
            if ($mo(passSelector).length) {
                $mo(passSelector).parent().hide();
            } else {
                $mo('.woocommerce-form-login input#password').closest('.woocommerce-form-row, p').hide();
            }
            moSetWcLoginButtonLabel($mo(btnSelector), movarlogin.loginOTPButtonText);
        }
        // OTP-only: default intent is OTP; password mode is opened via ?mo_pw_login=1 (admin bypass link in popup).
        if ($mo('.um-login').length > 0 && $mo('.um-login input[name="mo_wp_login_intent"]').length === 0) {
            $mo('<input type="hidden" name="mo_wp_login_intent" value="otp">').insertBefore($mo('.um-login #um-submit-btn'));
            $mo('<input type="hidden" id="hidden_current_tab" value="OTP">').insertBefore($mo('.um-login #um-submit-btn'));
        }
        if ($mo('#loginform label[for="user_pass"]').length > 0 && $mo('#loginform input[name="mo_wp_login_intent"]').length === 0) {
            $mo('<input type="hidden" name="mo_wp_login_intent" id="mo_wp_login_intent" value="otp">').insertBefore("#loginform #wp-submit");
            $mo('<input type="hidden" id="hidden_current_tab" value="OTP">').insertBefore("#loginform #wp-submit");
        }
        if (moWcFormHasPasswordField() && $mo('.woocommerce-form-login input[name="mo_wp_login_intent"]').length === 0) {
            var $wcBtn = $mo(".woocommerce-form-login .woocommerce-Button[type='submit']").length ? $mo(".woocommerce-form-login .woocommerce-Button[type='submit']").first() : $mo(".woocommerce-form-login .woocommerce-button[type='submit'], .woocommerce-button[name=login], button.woocommerce-form-login__submit").first();
            $mo('<input type="hidden" name="mo_wp_login_intent" value="otp">').insertBefore($wcBtn);
            $mo('<input type="hidden" id="hidden_current_tab" value="OTP">').insertBefore($wcBtn);
        }
    }
    // if password skip is set in the settings and fallback option is set as well.
    // need to show both username+password+login with OTP option
    else if (movarlogin.skipPwdCheck) {
        let passSelector;
        let btnSelector;
        let userSelector;
        let loginBtnText;
        
        // for ultimate member login form
        if ($mo('.um-login').length > 0) {
            passSelector = '.um-login .um-field-password';
            btnSelector = '.um-login #um-submit-btn';
            userSelector = 'input[name^="username"]';
            loginBtnText = movarlogin.loginOTPButtonText;
            $mo('<input type="hidden" name="mo_wp_login_intent" value="otp">').insertBefore(btnSelector);
            $mo('<input type="hidden" id="hidden_current_tab" value="OTP">').insertBefore(btnSelector);
            $mo('.um-login .um-field-password').hide();
            $mo('.um-login #um-submit-btn').val(movarlogin.loginOTPButtonText)
                .append('<input hidden name="logintype" value="' + movarlogin.loginOTPButtonText + '"/>');
            $mo("#um-login #um-submit-btn").css({ "width": "50%", "float": "left" });
            $mo(".um-login #um-submit-btn").parent().next().removeClass("um-right").removeClass("um-half").children().css({ "margin-top": "2%" });
            $mo(htmlButtonUm).insertAfter($mo('.um-login #um-submit-btn').parent());
            $mo(".um-login #um-submit-btn").parent().next().children().attr("type", "button").val(movarlogin.loginPassButtonText).attr("onclick", "toSendOtpBttn()");
        }

        // for default login form
        if ($mo('#loginform label[for="user_pass"]').length > 0) {
            passSelector = '#loginform label[for="user_pass"]';
            btnSelector = "#loginform #wp-submit";
            userSelector = 'input#user_login';
            loginBtnText = $mo(btnSelector).val();
            $mo('<input type="hidden" name="mo_wp_login_intent" id="mo_wp_login_intent" value="otp">').insertBefore(btnSelector);
            $mo('<input type="hidden" id="hidden_current_tab" value="OTP">').insertBefore(btnSelector);
            $mo(passSelector).parent().hide(200);
            $mo('#user_pass').removeAttr("required");
            $mo(btnSelector).css({ "width": "48%", "float": "left" });
            $mo("#loginform .forgetmenot").css({ "width": "100%", "margin-bottom": "2%" });
            $mo("#loginform #wp-submit").val(movarlogin.loginOTPButtonText);
            $mo('#loginform').append(htmlButtonWp);
            $mo('input[name="wp-submit"]:eq(1)').val(movarlogin.loginPassButtonText).css({ "padding": "0px 2px" }).attr("type", "button").attr("onclick", "toSendOtpBttn()");
        }

        if (moWcFormHasPasswordField()) {
            passSelector = 'label[for="password"]';
            btnSelector = $mo(".woocommerce-form-login .woocommerce-Button[type='submit']").length ? ".woocommerce-form-login .woocommerce-Button[type='submit']" : ".woocommerce-form-login .woocommerce-button[type='submit'], .woocommerce-button[name=login], button.woocommerce-form-login__submit";
            userSelector = 'input#username';
            loginBtnText = $mo(btnSelector).val();
            $mo('<input type="hidden" name="mo_wp_login_intent" value="otp">').insertBefore(btnSelector);
            $mo('<input type="hidden" id="hidden_current_tab" value="OTP">').insertBefore(btnSelector);
            if ($mo(passSelector).length) {
                $mo(passSelector).parent().hide();
            } else {
                $mo('.woocommerce-form-login input#password').closest('.woocommerce-form-row, p').hide();
            }
            $mo(btnSelector).wrap('<div class="mo-flex-space-between"></div>');
            $mo(htmlButtonWc).insertAfter(btnSelector);
            moSetWcLoginButtonLabel($mo(btnSelector), movarlogin.loginOTPButtonText);
            $mo('#password').removeAttr("required");
            $mo('input[name=password]').removeAttr("required");
            moSetWcLoginButtonLabel($mo('button[name="login"]:eq(0)'), movarlogin.loginOTPButtonText);
            $mo('button[name="login"]:eq(1)').attr("type", "button").attr("onclick", "toSendOtpBttn()");
            moSetWcLoginButtonLabel($mo('button[name="login"]:eq(1)'), movarlogin.loginPassButtonText);
        }
    }

    if (movarlogin.phoneOnlyIdentifiers && movarlogin.phoneOnlyLoginMessage) {
        $mo('#loginform').on('submit', function (e) {
            var pass = ($mo('#user_pass').val() || '').trim();
            var passRowVisible = $mo("#loginform label[for='user_pass']").parent().is(':visible');
            if (passRowVisible && pass.length > 0) {
                return true;
            }
        });
        $mo('.woocommerce-form-login').on('submit', function (e) {
            var $form = $mo(this);
            var pass = ($mo('#password', $form).val() || '').trim();
            var passVisible = $mo('label[for="password"]', $form).parent().is(':visible')
                || ($mo('#password', $form).length && $mo('#password', $form).closest('.woocommerce-form-row, p').is(':visible'));
            if (passVisible && pass.length > 0) {
                return true;
            }
        });
        $mo('.um-login form').on('submit', function (e) {
            var $form = $mo(this);
            var pass = ($mo('input[name^="user_password"]', $form).first().val() || '').trim();
            var passVisible = $mo('.um-field-password', $form).is(':visible');
            if (passVisible && pass.length > 0) {
                return true;
            }
        });
    }

    if (movarlogin.skipPwdCheck) {
        $mo(document).on('submit', '#loginform, .woocommerce-form-login, .um-login form', function () {
            var $form = $mo(this);
            var passVisible = $form.find("#user_pass:visible, #password:visible, input[name='password']:visible, input[name^='user_password']:visible").length > 0;
            var passHasValue = (
                ($form.find('#user_pass').val() || '').trim().length > 0 ||
                ($form.find('#password').val() || '').trim().length > 0 ||
                ($form.find("input[name='password']").val() || '').trim().length > 0 ||
                ($form.find("input[name^='user_password']").val() || '').trim().length > 0
            );
            var intent = $form.find('input[name="mo_wp_login_intent"]').val();
            var currentTab = (($form.find('#hidden_current_tab').val() || '') + '').toUpperCase();

            // If password mode/tab is active, preserve password.
            if (intent !== 'otp' || currentTab === 'LOGIN' || passVisible) {
                $form.find('input[name="mo_wp_login_intent"]').val('password');
                return;
            }

            // If some password value exists while tab is not explicitly OTP, preserve it.
            if (passHasValue && currentTab !== 'OTP') {
                $form.find('input[name="mo_wp_login_intent"]').val('password');
                return;
            }

            // Clear passwords only in explicit OTP mode.
            $form.find('#user_pass').val('');
            $form.find('#password').val('');
            $form.find('input[name="password"]').val('');
            $form.find('input[name^="user_password"]').val('');
        });
    }

    var moPwQ = window.location.search || '';
    if (/[?&]mo_pw_login=1(?:&|$)/.test(moPwQ)) {
        var moPwParams = moPwQ.replace(/^\?/, '').split('&').filter(function (p) {
            return p && p.indexOf('mo_pw_login=') !== 0 && p.indexOf('mo_otp_no_phone=') !== 0;
        });
        var moPwNewQ = moPwParams.length ? '?' + moPwParams.join('&') : '';
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', window.location.pathname + moPwNewQ + window.location.hash);
        }
        toSendOtpBttn();
    }
});

function toSendOtpBttn() {
    $mo('input[name="mo_wp_login_intent"]').val('password');
    // for ultimate member login form
    if ($mo('.um-login').length > 0) {
        $mo('.um-login .um-field-password').show();
        $mo('.um-login input[name^="user_password"]').prop('disabled', false).removeAttr('disabled');
        $mo("#hidden_current_tab").val("Login");
        $mo(".um-login #um-submit-btn").parent().next().children().val(movarlogin.loginOTPButtonText).attr("onclick", "hidePassBttn()");
        $mo(".um-login #um-submit-btn").val('Login with Password').children().remove();
    }

    // for default login form
    if ($mo('#loginform label[for="user_pass"]').length > 0) {
        $mo("#loginform label[for='user_pass']").parent().show(200);
        $mo('#user_pass, input[name="pwd"]').prop('disabled', false).removeAttr('disabled');
        $mo('#user_pass').attr("required", "required");
        if ($mo("#hidden_current_tab").length) {
            $mo("#hidden_current_tab").val("Login");
        }
        var $wpSubmits = $mo('#loginform input[name="wp-submit"]');
        if ($wpSubmits.length >= 2) {
            $mo('input[name="wp-submit"]:eq(0)').css({ "padding": "0px 2px" }).val(movarlogin.loginPassButtonText);
            $mo('input[name="wp-submit"]:eq(1)').val("Back").attr("onclick", "hidePassBttn()");
        } else {
            $mo('#loginform #wp-submit').val(movarlogin.loginPassButtonText);
        }
    }
    
    // for woocommerce login form
    if (typeof moWcFormHasPasswordField === 'function' && moWcFormHasPasswordField()) {
        $mo("#hidden_current_tab").val("Login");
        if ($mo('label[for="password"]').length) {
            $mo('label[for="password"]').parent().show(200);
        } else {
            $mo('.woocommerce-form-login input#password').closest('.woocommerce-form-row, p').show(200);
        }
        $mo('#password, input[name="password"]').prop('disabled', false).removeAttr('disabled');
        moSetWcLoginButtonLabel($mo('button[name="login"]:eq(1)'), movarlogin.loginOTPButtonText);
        $mo('button[name="login"]:eq(1)').attr("onclick", "hidePassBttn()");
        moSetWcLoginButtonLabel($mo('button[name="login"]:eq(0)'), movarlogin.loginPassButtonText);
    }
}

function hidePassBttn() {
    $mo('input[name="mo_wp_login_intent"]').val('otp');
    // for ultimate member login form
    if ($mo('.um-login').length > 0) {
        $mo('.um-login .um-field-password').hide();
        $mo('.um-login input[name^="user_password"]').prop('disabled', true).attr('disabled', 'disabled');
        $mo("#hidden_current_tab").val("OTP");
        $mo(".um-login #um-submit-btn").parent().next().children().val(movarlogin.loginPassButtonText).attr("onclick", "toSendOtpBttn()");
        $mo(".um-login #um-submit-btn").val(movarlogin.loginOTPButtonText).append('<input hidden name="logintype" value="' + movarlogin.loginOTPButtonText + '"/>');
    }

    // for default login form
    if ($mo('#loginform label[for="user_pass"]').length > 0) {
        if ($mo("#hidden_current_tab").length) {
            $mo("#hidden_current_tab").val("OTP");
        }
        $mo("#loginform label[for='user_pass']").parent().hide(200);
        $mo('#user_pass, input[name="pwd"]').prop('disabled', true).attr('disabled', 'disabled');
        $mo('#user_pass').removeAttr("required");
        var $wpSub = $mo('#loginform input[name="wp-submit"]');
        if ($wpSub.length >= 2) {
            $mo('input[name="wp-submit"]:eq(1)').attr("onclick", "toSendOtpBttn()").val(movarlogin.loginPassButtonText);
            $mo('input[name="wp-submit"]:eq(0)').val(movarlogin.loginOTPButtonText);
        } else {
            $mo('#loginform #wp-submit').val(movarlogin.loginOTPButtonText);
        }
    }

    // for woocommerce login form
    if (typeof moWcFormHasPasswordField === 'function' && moWcFormHasPasswordField()) {
        if ($mo('label[for="password"]').length) {
            $mo('label[for="password"]').parent().hide(200);
        } else {
            $mo('.woocommerce-form-login input#password').closest('.woocommerce-form-row, p').hide(200);
        }
        $mo('#password, input[name="password"]').prop('disabled', true).attr('disabled', 'disabled');
        $mo("#hidden_current_tab").val("OTP");
        moSetWcLoginButtonLabel($mo('button[name="login"]:eq(1)'), movarlogin.loginPassButtonText);
        $mo('button[name="login"]:eq(1)').attr("onclick", "toSendOtpBttn()");
        moSetWcLoginButtonLabel($mo('button[name="login"]:eq(0)'), movarlogin.loginOTPButtonText);
    }
}

