jQuery(document).ready(function () {

//    jQuery(select_text).after(
//        "<button type=\"button\" class=\"button copy-button\">Copy</button>"
//    );

    jQuery(".copy-button").click(function () {
        jQuery(this).parent().prev().find('.bobsi-url').select();
    });

    jQuery("#resetaudit").click(function (evt) {
        jQuery("#ctrl-c-message").css({
            top: evt.pageY - 80,
            left: evt.pageX - 280
        }).show();
    });

    jQuery("#resetaudit").focus(function () {
        jQuery(this).select();
    }).click(function () {
        jQuery(this).select();
    });

    jQuery('.logfiles').before(jQuery('#linksblock'))
});
