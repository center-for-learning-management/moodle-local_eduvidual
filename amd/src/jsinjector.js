define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'block_eduvidual/main'], function($, AJAX, NOTIFICATION, STR, URL, MAIN) {
    return {
        debug: false,
        dashboardEnhanceInfo: {},
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
            $("a[href*='/login/logout.php?sesskey']").attr('href', URL.relativeUrl('/blocks/eduvidual/pages/redirects/logout.php'));
            this.fakeBreadCrumb();
        },
        /**
         * Clear session cache.
         * @TODO: Not yet called by any script.
         */
        clearSessionStorage: function() {
            console.log('block_eduvidual/jsinjector:clearSessionStorage()');
            sessionStorage.clear();
        },
        /**
         * We do not want to see the course shortname field.
         */
        courseEditPage: function(userid) {
            $('#fitem_id_shortname').css('display', 'none');
            if ($('#fitem_id_shortname #id_shortname').val() == '') {
                var d = new Date();
                $('#fitem_id_shortname #id_shortname').val(userid + '-' + Date.now());
            }
        },
        dashboardCourseLoaded: function() {
            console.log('block_eduvidual/jsinjector:dashboardCourseLoaded()');
            var JSI = this;
            $('.dashboard-card-deck>.dashboard-card:not(.block-eduvidual-enhanced)').each(function() {
                JSI.dashboardEnhanceCourse($(this));
            });
        },
        dashboardEnhanceCourse: function(card) {
            card.addClass('block-eduvidual-enhanced block-eduvidual-transition-all');
            var JSI = this;
            var courseid = +$(card).attr('data-course-id');
            var footer = $(card).find('.dashboard-card-footer');
            if (courseid > 0) {
                if (typeof this.dashboardEnhanceInfo[courseid] !== 'undefined') {
                    var info = this.dashboardEnhanceInfo[courseid];
                    card.attr('id', 'dashboard-card-' + courseid);
                    footer.empty().append(
                        $('<a>')
                            .html(info.label)
                            .attr('href', '#')
                            .attr('onclick', '$(\'#dashboard-card-' + courseid + '>*\').toggleClass(\'block-eduvidual-height-no\'); return false;')
                    );
                    card.append(
                        $('<div>')
                            .addClass('block-eduvidual-height-no')
                            .append([
                                $('<a>')
                                    .html('hide')
                                    .attr('href', '#')
                                    .attr('onclick', '$(\'#dashboard-card-' + courseid + '>*\').toggleClass(\'block-eduvidual-height-no\'); return false;')
                            ])
                    );
                } else {
                    footer.empty();
                    AJAX.call([{
                        methodname: 'block_eduvidual_user_course_news',
                        args: { courseid: courseid },
                        done: function(reply) {
                            console.log('got reply for courseid ' + courseid, reply);
                            if (typeof reply.label !== 'undefined' && reply.label != '') {
                                JSI.dashboardEnhanceInfo[courseid] = reply;
                                JSI.dashboardEnhanceCourse(card);
                            }
                        },
                        fail: NOTIFICATION.exception
                    }]);
                }
            } else {
                console.error('Courseid is empty', courseid);
            }
        },
        /**
         * If a page does not have a breadcrumb, we inject one.
         */
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
        /**
         * In some cases, we want other redirections.
         */
        modifyRedirectUrl: function(type) {
            if (this.debug) console.log('block_eduvidual/jsinjector:modifyRedirectUrl(type)', type);
            if (type == 'coursedelete') {
                $('#page-content .continuebutton form').attr('action', URL.relativeUrl('/my'));
                $('#page-content .continuebutton form input[name="categoryid"]').remove();
            }
        },
        /**
         * Inject org specific menu.
         */
        orgMenu: function(userid) {
            console.log('block_eduvidual/jsinjector:orgMenu(userid)', userid);
            if (typeof userid == 'undefined' || userid == 0) return;
            var foruserid = sessionStorage.getItem('block_eduvidual_foruserid');
            var menu = sessionStorage.getItem('block_eduvidual_orgmenu');

            if (userid != foruserid) {
                menu = false;
            }

            if (typeof menu === 'undefined' || !menu) {
                AJAX.call([{
                    methodname: 'block_eduvidual_user_orgmenu',
                    args: { userid: userid },
                    done: function(menu) {
                        sessionStorage.setItem('block_eduvidual_foruserid', userid);
                        sessionStorage.setItem('block_eduvidual_orgmenu', menu);
                        $(menu).insertBefore($('#page-wrapper>.navbar div.usermenu').closest('li'));
                    },
                    fail: NOTIFICATION.exception
                }]);
            } else {
                $(menu).insertBefore($('#page-wrapper>.navbar div.usermenu').closest('li'));
            }
        },
        /**
         * We do not want the username field on the signup-page.
         */
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
