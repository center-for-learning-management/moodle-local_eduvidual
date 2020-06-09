define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'], function($, AJAX, NOTIFICATION, STR, URL, MAIN) {
    return {
        setBackground: function(sender) {
            var background = $(sender).css('background-image').replace('url(', '').replace(')', '').replace('"','').replace('"','');
            $(sender).css('filter', 'blur(1px)');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'preferences', act: 'background', background: background }, { sender: sender, signalItem: $(sender) });
            });
        },
        result: function(o) {
            if (o.data.act == 'background') {
                $('#local_eduvidual_preferences_background a').css('filter', 'unset');
                if (o.result.status == 'ok') {
                    $('#local_eduvidual_preferences_background a').parent().removeClass('active');
                    $(o.payload.sender).parent().addClass('active');
                    $('body').css('background-image', 'url(' + o.data.background + ')');
                }
            }
        },
    };
});
