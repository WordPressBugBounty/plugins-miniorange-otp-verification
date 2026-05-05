jQuery(document).ready(function () {
    let $mo = jQuery;

    // Safely parse JSON with error handling
    let selectors;
    try {
        selectors = JSON.parse(modropdownvars.selector);
        if (!Array.isArray(selectors)) {
            console.error('Invalid selector format: expected array');
            return;
        }
    } catch (error) {
        console.error('Error parsing selector JSON:', error);
        return;
    }

    function moDestroyIntlTelForCf7() {
        if (typeof window.intlTelInput !== 'function') {
            return;
        }
        for (let i = 0; i < selectors.length; i++) {
            $mo(selectors[i]).each(function () {
                try {
                    if (typeof window.intlTelInput.getInstance === 'function') {
                        const iti = window.intlTelInput.getInstance(this);
                        if (iti && typeof iti.destroy === 'function') {
                            iti.destroy();
                        }
                    }
                } catch (e) {
                    /* silently ignore */
                }
                const $tel = $mo(this);
                const $next = $tel.next('input[name="country_code"]');
                if ($next.length) {
                    $next.remove();
                }
            });
        }
    }

    let moCf7DropdownTimer = null;

    function moRunIntlTelInit() {
        for (let i = 0; i < selectors.length; i++) {
            let selector = selectors[i];
            var allowed = Array.isArray(modropdownvars.onlyCountries)
                                                        ? modropdownvars.onlyCountries.map(function(c){ return (c.alphacode || "").toLowerCase(); })
                                                        : [];

            if ($mo(selector).length) {
                // For each field in the page
                $mo(selector).each(function () {
                    const $input = $mo(this);
                    const $cc = $mo("<input type='hidden' name='country_code'>").insertAfter($input);
                    const isCheckoutBillingPhone = selector === "#billing-phone";

                    // Track last user-entered value specifically for the checkout
                    // billing phone field so that if the Blocks re-render and reset
                    // the value, we can restore the most recent user value.
                    let lastUserValue = $input.val() || "";

                    let mocountrycode = window.intlTelInput(this, {
                        initialCountry: modropdownvars.defaultCountry,
                        nationalMode: false,
                        onlyCountries: allowed.filter(Boolean),
                        dropdownContainer: document.body,
                        // intl-tel-input v25+ expects a function returning names for hidden inputs.
                        // Preserve previous behavior by creating a hidden input named "full".
                        hiddenInput: function (originalName) {
                            return { phone: "full" };
                        },
                    });

                    let selected_country_data = mocountrycode.getSelectedCountryData();
                    $cc.val(selected_country_data.dialCode);

                    let mo_code_length = selected_country_data.dialCode.length + 1;
                    let restrictedPositions = Array.from({ length: mo_code_length }, (_, index) => index + 1);

                    var $telForIti = $input.find(".iti__tel-input").first();
                    if (!$telForIti.length && $input.is("input")) {
                        $telForIti = $input;
                    }

                    var moOtpCountryChangeHandler = function () {
                        let selected_country_data = mocountrycode.getSelectedCountryData();

                        if (typeof selected_country_data.dialCode !== "undefined") {
                            $cc.val(selected_country_data.dialCode);
                            let mo_code_length = selected_country_data.dialCode.length + 1;
                            restrictedPositions = Array.from({ length: mo_code_length }, (_, index) => index + 1);
                        }
                    };

                    if ($telForIti.length) {
                        $telForIti.on("countrychange", moOtpCountryChangeHandler);
                    } else {
                        $mo(selector).on("countrychange", moOtpCountryChangeHandler);
                    }

                    // Track all direct input changes on the field.
                    $input.on('input', function () {
                        if (isCheckoutBillingPhone) {
                            lastUserValue = $input.val() || "";
                        }
                    });

                    $mo(selector).keydown(function (event) {
                        const key = event.key || event.keyCode;
                        if (restrictedPositions.includes(event.target.selectionStart)) {
                            if (key === "Backspace" || key === 8 || key === 37 || key === "ArrowLeft" || key === "a") {
                                event.preventDefault();
                            }
                        }
                    });

                    if (isCheckoutBillingPhone) {
                        setInterval(function () {
                            const current = $input.val() || "";
                            if (lastUserValue && current !== lastUserValue) {
                                $input.val(lastUserValue);
                            }
                        }, 300);
                    }

                    // This is to handle WC Block Checkout forms css
                    if (selector !== "#shipping-phone" && selector !== "#billing-phone") {
                        let $telInput = $mo(this).find(".iti__tel-input").first();
                        if (!$telInput.length && $mo(this).is("input")) {
                            $telInput = $mo(this);
                        }
                        if ($telInput.length) {
                            $telInput.css("cssText", "padding-left: 48px !important;font-size:14px;");
                        }
                    } else {
                        // Hide the label for the checkout phone field (billing phone).
                        // Classic checkout:
                        $mo('label[for="billing-phone"]').hide();
                        // WooCommerce Blocks checkout:
                        $mo('.wc-block-components-address-form__phone label').hide();
                    }
                });
            }
        }

        // This is to handle formcraft forms css
        $mo(".formcraft-css .fcb_form .form-element").css({
            "transform": "none",
            "-webkit-transform": "none",
            "transition": "none",
            "-webkit-transition": "none"
        });

        $mo(".intl-tel-input, .iti").css({ "width": "100%" });
        $mo(".intl-tel-input input[type='tel'], .iti .iti__tel-input").css({ "width": "100%" });
    }

    setTimeout(moRunIntlTelInit, 200);

    $mo(document).on(
        'wpcf7mailsent wpcf7invalid wpcf7spam wpcf7mailfailed wpcf7reset',
        function () {
            moDestroyIntlTelForCf7();
            if (moCf7DropdownTimer) {
                clearTimeout(moCf7DropdownTimer);
            }
            moCf7DropdownTimer = setTimeout(function () {
                moCf7DropdownTimer = null;
                moRunIntlTelInit();
            }, 200);
        }
    );
});
