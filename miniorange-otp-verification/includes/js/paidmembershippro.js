jQuery(document).ready(function () {
    var $mo = jQuery;

    
    var phoneField = '<div class="pmpro_form_field pmpro_form_field-required">' +
        '<label for="phone_paidmembership" class="pmpro_form_label">Phone <span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>' +
        '<input id="phone_paidmembership" size="30" class="pmpro_form_input pmpro_form_input-required" name="phone_paidmembership" required type="text">' +
        '</div>';

    var $target = $mo('.pmpro_form_field-bconfirmemail');
    if ( ! $target.length ) {
        $target = $mo('.pmpro_form_field-bemail');
    }
    if ( ! $target.length ) {
        $target = $mo('.pmpro_form_field-bphone');
    }
    if ( $target.length ) {
        $mo(phoneField).insertAfter($target);
    }
});