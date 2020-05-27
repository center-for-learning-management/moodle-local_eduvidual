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
            $("a[href*='/login/logout.php?sesskey']").attr('href', URL.relativeUrl('/blocks/eduvidual/pages/redirect/logout.php'));
            this.fakeBreadCrumb();
        },
        fakeBreadCrumb: function() {
            if ($('#page #page-header .card-body .d-flex.flex-wrap #page-navbar').length == 0) {
                STR.get_strings([{ key: 'navigation', component: 'core' }, { key: 'myhome', component: 'core' }]).then(function (s) {
                    $('#page #page-header .card-body .d-flex.flex-wrap').prepend(
                        $("<div id=\"page-navbar\">").append(
                            $("<nav role=\"navigation\" aria-label=\"" + s[0] + "\">").append(
                                $('<ol class="breadcrumb">').append(
                                    $('<li class="breadcrumb-item">').append(
                                        $('<a data-ajax="false">').attr('href', URL.relativeUrl('/my')).html(s[1])
                                    )
                                )
                            )
                        )
                    );
                });
            }
        },
        modifyRedirectUrl: function(type) {
            if (this.debug) console.log('block_eduvidual/jsinjector:modifyRedirectUrl(type)', type);
            if (type == 'coursedelete') {
                $('#page-content .continuebutton form').attr('action', URL.relativeUrl('/my'));
                $('#page-content .continuebutton form input[name="categoryid"]').remove();
            }
        },
        signupPage: function() {
            STR.get_strings([{ key: 'email', component: 'core' }]).then(function (s) {
                $('#fitem_id_username label[for="id_username"]').html(s[0]);
                $('#fitem_id_username #id_username').attr('onkeyup', "document.getElementById('id_email').value = document.getElementById('id_username').value;");
                $('#fitem_id_email').css('display', 'none');
                $('form[action*="/login/signup.php"]').attr("onsubmit", "document.getElementById('id_email').value = document.getElementById('id_username').value;");
            });
        },
    };
});
