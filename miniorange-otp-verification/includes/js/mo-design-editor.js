/**
 * Design Editor Script
 * Handles popup design editor functionality including tab switching and AJAX operations
 */
(function($mo) {
    'use strict';

    /**
     * Initialize design editor functionality
     */
    function initDesignEditor() {
        if (typeof moDesignEditor === 'undefined') {
            return;
        }
        initDesignTabs();
        initPopupEditor();
    }

    /**
     * Initialize design tab item click handlers
     */
    function initDesignTabs() {
        $mo(".design-tab-item").click(function() {
            $mo(".design-tab-item").removeClass("active");
            $mo(this).addClass("active");
            const targetTab = $mo(this).attr("target-tab");
            $mo(".mo-tab-selector").hide();
            $mo("#" + targetTab).show();
        });
    }

    /**
     * Initialize popup editor functionality
     */
    function initPopupEditor() {
        function disablePreviewInteraction(iframeId) {
            var $body = $mo("#" + iframeId).contents().find("body");
            $body.off("click.previewGuard submit.previewGuard").on("click.previewGuard submit.previewGuard", function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        }

        if (moDesignEditor.message) {
            $mo("#advance_box iframe").contents().find("body").append(moDesignEditor.message);
            $mo("#advance_box iframe").each(function() {
                disablePreviewInteraction($mo(this).attr("id"));
            });
        }

        var allowedIframes = ["defaultPreview", "userchoicePreview", "externalPreview", "errorPreview"];

        $mo(".popupbutton").click(function() {
            var iframe = $mo(this).data("iframe");
            if (allowedIframes.indexOf(iframe) === -1) {
                return;
            }

            var $form = $mo(this).closest("form");
            if (!$form.length) {
                $form = $mo("form[name=" + iframe + "]");
            }
            var nonce = $form.find("input[name='popup_display_nonce']").val();
            var popupAction = $mo(this).data("popup");
            var popupType = $form.find("input[name='popuptype']").val();
            var editorName = $form.find("textarea").attr("name") || $form.find("input[type='hidden']").attr("name");
            if (!editorName && iframe === "defaultPreview") {
                editorName = "customEmailMsgEditor";
            }

            if (typeof window.tinyMCE !== "undefined") {
                window.tinyMCE.triggerSave();
            }
            var templatedata = "";
            if (editorName) {
                templatedata = $form.find("textarea[name='" + editorName + "']").val();
                if (!templatedata) {
                    templatedata = $form.find("input[type='hidden'][name='" + editorName + "']").val() || "";
                }
            }

            if (popupAction === "mo_popup_reset" && confirm(moDesignEditor.resetConfirmText) === false) {
                return;
            }

            var $iframeBody = $mo("#" + iframe).contents().find("body");
            $iframeBody.empty();
            if (moDesignEditor.loaderHtml) {
                $iframeBody.append(moDesignEditor.loaderHtml);
            }

            var requestUrl = moDesignEditor.ajaxUrl || "";
            if (!requestUrl || requestUrl.indexOf("admin-ajax.php") === -1) {
                $iframeBody.empty().append("<p>Preview unavailable. Configuration error.</p>");
                return;
            }

            var data = {
                form_name: iframe,
                popactionvalue: popupAction,
                popuptype: popupType,
                _wpnonce: nonce,
                action: popupAction
            };
            data[editorName] = templatedata;

            $mo.ajax({
                url: requestUrl,
                type: "POST",
                data: data,
                dataType: "text",
                success: function(raw) {
                    var text = (typeof raw === "string" ? raw : "").trim();
                    var jsonStart = text.indexOf("{");
                    if (jsonStart === -1) {
                        $iframeBody.empty().append("<p>Preview unavailable. Invalid response.</p>");
                        return;
                    }
                    var jsonStr = text.substring(jsonStart);
                    var depth = 0, end = -1, i, c, inStr = false, escape = false, q;
                    for (i = 0; i < jsonStr.length; i++) {
                        c = jsonStr.charAt(i);
                        if (escape) { escape = false; continue; }
                        if (c === "\\" && inStr) { escape = true; continue; }
                        if (inStr) { if (c === q) inStr = false; continue; }
                        if (c === "\"" || c === "'") { inStr = true; q = c; continue; }
                        if (c === "{") { depth++; continue; }
                        if (c === "}") { depth--; if (depth === 0) { end = i + 1; break; } }
                    }
                    if (end !== -1) { jsonStr = jsonStr.substring(0, end); }

                    try {
                        var response = JSON.parse(jsonStr);
                        if (popupAction === "mo_popup_reset" && response.message && typeof response.message === "object") {
                            var resetMsg = response.message.message;
                            var resetTpl = response.message.template;
                            $iframeBody.empty().append(typeof resetMsg === "string" ? resetMsg : "");
                            disablePreviewInteraction(iframe);
                            if (editorName && typeof resetTpl === "string") {
                                $mo("#" + editorName).val(resetTpl);
                            }
                        } else {
                            var htmlToWrite = (response && response.message && typeof response.message === "string") ? response.message : "";
                            var iframeElem = document.getElementById(iframe);
                            if (iframeElem && iframeElem.contentDocument && htmlToWrite) {
                                iframeElem.contentDocument.open();
                                iframeElem.contentDocument.write(htmlToWrite);
                                iframeElem.contentDocument.close();
                            }
                            disablePreviewInteraction(iframe);
                        }
                    } catch (e) {
                        $iframeBody.empty().append("<p>Preview unavailable. Invalid response.</p>");
                    }
                },
                error: function() {
                    $iframeBody.empty().append("<p>Request failed. Please try again.</p>");
                }
            });
        });
    }

    $mo(document).ready(function() {
        initDesignEditor();
    });

})(jQuery);

