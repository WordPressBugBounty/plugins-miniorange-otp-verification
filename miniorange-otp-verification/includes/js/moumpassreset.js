jQuery(document).ready(function () {
    $mo = jQuery;
    var messagebox = "<div id='mo_message' style='display:none; background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;margin-top:10px;'></div>";
    var verify_field =
        messagebox +
        '<div class="um-field um-field-text um-field-type_text" id="verify_div" style="display:none;"><div class="um-field-area"><input autocomplete="off" class="um-form-field" type="text" name="verify_field" id="verify_field" value="" placeholder="Enter Verification code here"></div></div><div class="um-col-alt um-col-alt-b"><div class="um-clear"></div><div class="um-left um-half"><input type="button" style = "background-color: #3ba1da" value="' +
        moumprvar.buttontext +
        '" class="um-button" id="mo_um_send_otp_pass"></div><div class="um-right um-half"><input type="submit" value="Verify & Reset Password" class="um-button um-alt" id="mo_um_verify_pass" disabled></div><div class="um-clear"></div></div>';
    if ($mo("#_um_password_reset").length > 0) {
        $mo(".um-password #um-submit-btn").remove();
        $mo(verify_field).insertAfter(".um-password .um-field-username_b");
        $mo("input#username_b").attr("placeholder", moumprvar.phText);
        $mo(".um-field-type_block").children().children().text(moumprvar.resetLabelText);
    }
    $mo("#mo_um_send_otp_pass").click(function (c) {
        c.preventDefault();
        mo_um_send_otp();
    });

    $mo("#mo_um_verify_pass").click(function(n) {
        n.preventDefault();
        var username = $mo("[id^=" + moumprvar.fieldKey + "]").val();
        var otp = $mo("#verify_field").val();
        let img   = "<div class='moloader'></div>";

        $mo("#mo_message").empty();
        $mo("#mo_message").append(img);
        $mo("#mo_message").show();
        $mo.ajax({
            url: moumprvar.siteURL,
            type: "POST",
            data: { username: username, security: moumprvar.nonce, otp_token:otp, action: moumprvar.vaction },
            crossDomain: !0,
            dataType: "json",
            success: function (d) {
                if (d.result == "success") {
                    $mo("#mo_message").empty(), $mo("#mo_message").append(d.message), $mo("#mo_message").css({"background-color":"#dbfff7","color":"#008f6e"});
                    $mo(".um-form form").submit();                     
                } else {
                    $mo("#mo_message").empty(), $mo("#mo_message").append(d.message), $mo("#mo_message").css({"background-color":"#ffefef","color":"#ff5b5b"});
                }
            },
            error: function (d) {
                $mo("#mo_um_send_otp_pass").removeAttr("disabled");
                $mo("#mo_message").empty(), $mo("#mo_message").append(d.message), $mo("#mo_message").css({"background-color":"#ffefef","color":"#ff5b5b"});
            }, 
    
        });
    });
});

function mo_um_send_otp() {
    var username = $mo("[id^=" + moumprvar.fieldKey + "]").val();
    let img   = "<div class='moloader'></div>";
    $mo("#mo_message").empty();
    $mo("#mo_messag").append(img);
    $mo("#mo_message").show();
    $mo.ajax({
        url: moumprvar.siteURL,
        type: "POST",
        data: { username: username, security: moumprvar.nonce, action: moumprvar.action.send },
        crossDomain: !0,
        dataType: "json",
        success: function (b) {
            $mo("#mo_um_send_otp_pass").removeAttr("disabled");
            if (b.result == "success") {
                $mo("#verify_div").show();
                $mo("#mo_um_verify_pass").removeAttr("disabled");
                $mo("#mo_um_verify_pass").css({"background-color":"#3ba1da","color":"#faf7f7"});
                $mo("#mo_um_send_otp_pass").css({"background-color":"","color":"#030303"});
                $mo("#mo_message").empty(), $mo("#mo_message").append(b.message), $mo("#mo_message").css({"background-color":"#dbfff7","color":"#008f6e"});
            } else {
                $mo("#verify_div").hide();
                $mo("#mo_message").empty(), $mo("#mo_message").append(b.message), $mo("#mo_message").css({"background-color":"#ffefef","color":"#ff5b5b"});
            }
        },
        error: function (b) {
            $mo("#mo_um_send_otp_pass").removeAttr("disabled");
            $mo("#mo_message").empty(), $mo("#mo_message").append(b.message), $mo("#mo_message").css({"background-color":"#ffefef","color":"#ff5b5b"});
        },
    });
}
