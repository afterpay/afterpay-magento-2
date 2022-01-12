define([
    'jquery'
], function ($) {
    return function (options, selector) {
        $(options.spinnerId).hide();
        $(selector).click(function (e) {
            e.preventDefault();
            $(selector).addClass('disabled');
            $(options.spinnerId).show();
            $.post(
                options.actionUrl,
                {
                    websiteId: $('#website_switcher').val()
                }
            ).done(() => location.reload())
            .always(function () {
                $(selector).removeClass('disabled');
                $(options.spinnerId).hide();
            })
            e.stopPropagation();
            return false;
        });
    }
});
