define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'block_eduvidual/main'], function($, AJAX, NOTIFICATION, STR, URL, MAIN) {
    return {
        debug: false,
        run: function(data){
            console.log('block_eduvidual/jsinjector:run(data)', data);
            STR.get_strings([{ key: 'Accesscard', component: 'block_eduvidual' }]).then(function (s) {
                $('.usermenu .dropdown a[href$="/user/preferences.php"]').after(
                    $('<a>').attr('href', URL.relativeUrl('blocks/eduvidual/pages/accesscard.php'))
                            .addClass('dropdown-item menu-action').attr('role', 'menuitem')
                            .attr('data-title', 'moodle,accesscard').attr('aria-labelledby', 'actionmenuaction-accesscard')
                            .attr('data-ajax', 'false').append([
                                $('<i>').addClass('icon fa fa-id-card fa-fw').attr('aria-hidden', 'true'),
                                $('<span>').addClass('menu-action-text').attr('id', 'actionmenuaction-accesscard').html(s[0]),
                            ])
                );
            });
            $("a[href*='/login/logout.php?sesskey']").attr('href', URL.relativeUrl('/blocks/eduvidual/pages/logout.php'));
        },
        modifyRedirectUrl: function(type) {
            if (this.debug) console.log('block_eduvidual/jsinjector:modifyRedirectUrl(type)', type);
            if (type == 'coursedelete') {
                $('#page-content .continuebutton form').attr('action', URL.relativeUrl('/my'));
                $('#page-content .continuebutton form input[name="categoryid"]').remove();
            }
        },
    };
});
