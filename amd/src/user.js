define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'block_eduvidual/main', 'block_eduvidual/manager', 'block_eduvidual/teacher','core/modal_factory', 'core/modal_events'],
    function($, AJAX, NOTIFICATION, STR, URL, MAIN, MANAGER, TEACHER, ModalFactory, ModalEvents) {
    return {
        /**
         * Allows a user to enrol to an organization using a specific access code.
        **/
        accesscode: function(){
            var orgid = +$('#block_eduvidual_user_accesscode_orgid').val();
            var code = $('#block_eduvidual_user_accesscode_code').val();
            require(['block_eduvidual/main'], function(MAIN) {
			    MAIN.connect({ module: 'user', act: 'accesscode', orgid: orgid, code: code }, { signalItem: $('#block_eduvidual_user_accesscode_btn') });
            });
        },
        actionCategories: function(sender){
            var categoryid = +$('.ul-eduvidual-courses').attr('data-categoryid');
            var orgid = +$('.ul-eduvidual-courses').attr('data-orgid');
            switch ($(sender).val()) {
                case 'createcourse':
                    TEACHER.loadCourseForm();
                break;
                case 'createcategory':
                    MANAGER.categoryAdd(undefined, orgid, categoryid);
                break;
                case 'editcategory':
                    var currentname = $('.block_eduvidual_courses_title').html();
                    MANAGER.categoryEdit(undefined, orgid, categoryid, currentname);
                break;
                case 'removecategory':
                    MANAGER.categoryRemove(undefined, undefined, orgid, categoryid);
                break;
            }
            $(sender).val('');
        },
        /**
		 * Sets the default org for the current user
		 * @param orgid orgid to set
		**/
		defaultorg: function(orgid) {
            require(['block_eduvidual/main'], function(MAIN) {
			    MAIN.connect({ module: 'user', act: 'defaultorg', orgid: +orgid }, { signalItem: $('#block_eduvidual_user_defaultorg') });
            });
		},
        loadCategory: function(categoryid, orgid) {
            if (typeof orgid === 'undefined') {
                orgid = +$('.ul-eduvidual-courses').attr('data-orgid');
            }
            require(['block_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'user', act: 'categories', orgid: orgid, categoryid: categoryid }, {  });
            });
        },
        triggerShowHidden: function(setto) {
            console.log('USER.triggerShowHidden(setto)', setto);
            if (typeof setto === 'undefined') {
                $('#block_eduvidual_user_courselist').toggleClass('showhidden');
            } else if(setto == 1) {
                $('#block_eduvidual_user_courselist').addClass('showhidden');
            } else {
                $('#block_eduvidual_user_courselist').removeClass('showhidden');
            }

        },
        placeSubmenu: function(){
            var h = 0;
            $('.block_eduvidual_submenu_wrapper').each(function(){ if ($(this).height() > 0) { h = $(this).height(); } });
            console.log('USER.placeSubmenu(), h is ', h);
            $('.block_eduvidual_submenu_wrapper').css('margin-top', (h / -2) + 'px');
        },
        setEditor: function(sel) {
            require(['block_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'user', act: 'seteditor', editor: $(sel).val() }, { signalItem: $(sel) });
            });
        },
        setLandingPage: function(url){
            if (typeof url === 'undefined') {
                STR.get_strings([
                        {'key' : 'user:landingpage:title', component: 'block_eduvidual' },
                        {'key' : 'user:landingpage:description', component: 'block_eduvidual' },
                        {'key' : 'ok', component: 'core' },
                        {'key' : 'cancel', component: 'core' },
                    ]).done(function(s) {
                        NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function(){ require(['block_eduvidual/user'], function(USER) { USER.setLandingPage(top.location.href); }); });
                    }
                ).fail(NOTIFICATION.exception);
            } else {
                require(['block_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'user', act: 'landingpage_set', url: url }, { signalItem: $('#block_eduvidual_setlandingpage') });
                });
            }
        },
        showModuleInfo: function(a) {
            var title = $(a).children('h3').html();
            var description = $(a).children('p').html();
            var url = $(a).attr('data-url');
            console.log(url);
            if (typeof url !== 'undefined' && url != '') {
                STR.get_strings([
                        {'key' : 'open', component: 'block_eduvidual' },
                        {'key' : 'close', component: 'block_eduvidual' },
                    ]).done(function(s) {
                        NOTIFICATION.confirm(title, description, s[0], s[1], function(){ require(['block_eduvidual/main'], function(MAIN) { MAIN.navigate(url); }); });
                    }
                ).fail(NOTIFICATION.exception);
            } else {
                NOTIFICATION.alert(title, description);
            }
        },
        toggleSubmenu: function(setto){
            if (typeof setto !== 'undefined') {
                if (setto) {
                    $('.block_eduvidual_submenu_wrapper').addClass('opened');
                } else {
                    $('.block_eduvidual_submenu_wrapper').removeClass('opened');
                }
            } else {
                $('.block_eduvidual_submenu_wrapper').toggleClass('opened');
            }
        },
        setHidden: function(sender) {
            var li = $(sender).parent();
            var courseid = +li.attr('data-courseid');
            var state = li.hasClass('inactive');
            require(['block_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'user', act: 'courselist_sethidden', courseid: courseid, setto: (state)?0:1 }, { li: li });
            });
        },
        result: function(o) {
            if (o.data.act == 'accesscode') {
                if (o.result.status == 'ok') {
                    top.location.href = URL.fileUrl("/blocks/eduvidual/pages/categories.php", "") + '?orgid=' + o.result.orgid;
                }
            }
            if (o.data.act == 'autologin') {
                var url = o.result.autologinurl + '/?userid=' + o.result.userid + '&key=' + o.result.key + '&urltogo=' + encodeURI(o.payload.href);
                console.log('Navigating to ', url);
                window.open(url, '_blank');
            }
            if (o.data.act == 'categories') {
                if (o.result.status == 'ok') {
                    var container = $('.ul-eduvidual-courses').empty();
                    $('.block_eduvidual_courses_title').html(o.result.category.name);
                    $('.ul-eduvidual-courses').attr('data-categoryid', o.data.categoryid);
                    // Controlgroup with back and create button
                    var controlgroup = $('<div>')
                        //.addClass('form-inline felement')
                        .addClass('grid-eq-2')
                        .css('text-align', 'center');
                        //.attr('data-fieldtype', 'group');
                    container.append(controlgroup);

                    STR.get_strings([
                            {'key' : 'back', component: 'block_eduvidual' },
                            {'key' : 'action', component: 'block_eduvidual' },
                            {'key' : 'createcourse:here', component: 'block_eduvidual' },
                            {'key' : 'createcategory:here', component: 'block_eduvidual' },
                            {'key' : 'createcategory:rename', component: 'block_eduvidual' },
                            {'key' : 'createcategory:remove', component: 'block_eduvidual' },
                        ]).done(function(s) {
                            // Always add the back-button
                            var divback = $('<div>');//.addClass('form-group fitem');
                            var aback = $('<a>').addClass('ui-btn btn btn-_secondary').attr('onclick', 'history.go(-1);').html(s[0]);
                            if (typeof o.result.parent !== 'undefined' && o.result.parent.id > 0) {
                                aback.attr('onclick', 'require(["block_eduvidual/user"], function(USER) { USER.loadCategory(' + o.result.parent.id + '); });').html(o.result.parent.name);  // BLOCK_EDUVIDUAL_LANG['js:back']
                            }
                            aback.html('<img src="/pix/t/left.svg" alt=""> ' + aback.html());
                            divback.append(aback);
                            controlgroup.append(divback);

                            var actionselect = $('<select>')
                                .attr('data-role', 'selectmenu')
                                .attr('onchange', 'var sel = this; require(["block_eduvidual/user"], function(USER) { USER.actionCategories(sel); });');
                            actionselect.append($('<option value="">' + s[1] + '</option>'));

                            // Add course-add buttons for teachers
                            if (['Administrator', 'Manager', 'Teacher'].indexOf(o.result.orgrole) > -1 || o.result.role == 'Administrator') {
                                actionselect.append($('<option value="createcourse">' + s[2] + '</option>'));
                            }
                            // Add category-add/-remove buttons for managers
                            if (['Administrator', 'Manager'].indexOf(o.result.orgrole) > -1 || o.result.role == 'Administrator') {
                                actionselect.append($('<option value="createcategory">' + s[3] + '</option>'));
                                if (o.result.orgcategoryid != o.data.categoryid) {
                                    actionselect.append($('<option value="editcategory">' + s[4] + '</option>'));
                                    actionselect.append($('<option value="removecategory">' + s[5 ]+ '</option>'));
                                }
                            }
                            if (actionselect.children().length > 1) {
                                controlgroup.append(actionselect);
                            }
                        }
                    ).fail(NOTIFICATION.exception);

                    if (o.result.categories.length > 0) {
                        var ul = $('<ul>')
                            .attr('data-role', 'listview')
                            .attr('data-inset', 'true')
                            .attr('data-split-icon', 'plus')
                            .addClass('ui-listview ui-listview-inset ui-corner-all ui-shadow');
                        for (var a = 0; a < o.result.categories.length; a++) {
                            var category = o.result.categories[a];
                            //console.log(category)
                            var li = $('<li>');
                            if (a == 0) {
                                li.addClass('ui-first-child');
                            }
                            var a_ = $('<a>').attr('href', '#').attr('onclick', 'require(["block_eduvidual/user"], function(USER) { USER.loadCategory(' + category.id + '); });').addClass('ui-btn btn');
                            if (category.visible == 0) {
                                a_.addClass('disabled').css('color', 'darkgray');
                            }
                            var img = $('<img>').attr('src', '/pix/i/withsubcat.svg').attr('alt', 'Course category').css('display', 'inline-block');
                            var h3 = $('<h3>').html(category.name)
                                        .css('line-height', '2.5em')
                                        .css('display', 'inline')
                                        .css('margin-left', '5px');
                            li.append(a_.append([img, h3]));
                            /*
                            if (['Administrator', 'Manager', 'Teacher'].indexOf(o.result.orgrole) > -1 || o.result.role == 'Administrator') {
                                var url = URL.fileUrl("/blocks/eduvidual/pages/teacher.php", "") + '?act=createcourse&categoryid=' + category.id;
                                var aplus = $('<a>').attr('href', url).html('<img src="/pix/t/add.svg" alt="add course" />');
                                li.append(aplus);
                            }
                            */
                            ul.append(li);
                        }
                        container.append(ul);
                    }
                    if (o.result.courses.length > 0) {
                        var isembedded = localStorage.getItem('block_eduvidual_isembedded') !== null && localStorage.getItem('block_eduvidual_isembedded') == 1;
                        var ul = $('<ul>').
                            attr('data-role', 'listview').
                            attr('data-inset', 'true').
                            addClass('ui-listview ui-listview-inset ui-corner-all ui-shadow');
                        for (var a = 0; a < o.result.courses.length; a++) {
                            var course = o.result.courses[a];
                            //console.log(course)
                            var li = $('<li>');
                            if (course.visible == 0) {
                                li.addClass('block_eduvidual_inactive');
                            }
                            if (a == 0) {
                                li.addClass('ui-first-child');
                            }
                            var url = URL.fileUrl("/course/view.php", "") + '?id=' + course.id;
                            var onclick = '';

                            if (isembedded) {
                                url = '#';
                                onclick = 'require(["block_eduvidual/jquery-ba-postmessage"], function(p) { p.post("open_course|' + course.id + '"); });';

                            }
                            var img = $('<img>').attr('src', (course.image != '') ? course.image : '/pix/i/course.svg').attr('alt', 'Course');
                            var a_ = $('<a>').attr('href', url).attr('onclick', onclick).attr('data-ajax', 'false').addClass('ui-btn btn');
                            var h3 = $('<h3>').html(course.fullname)
                                        .css('line-height', '2.5em')
                                        .css('display', 'inline')
                                        .css('margin-left', '5px');
                            ul.append(li.append(a_.append([img, h3])));
                        }
                        container.append(ul);
                    }
                } else {
                    container.append($('<li>').html('ERROR'));
                }
            }
            if (o.data.act == 'courselist_sethidden') {
                if (o.data.setto) {
                    $(o.payload.li).addClass('inactive ishidden');
                } else {
                    $(o.payload.li).removeClass('inactive ishidden');
                }
            }
            if (o.data.act == 'whoami' || o.data.act == 'login') {
                if (o.result.status == 'ok') {
                    if (o.payload.urltogo.indexOf('/pages/login_app.php') > 0) {
                        console.log('This is the login page in app-mode - going to my courses');
                        o.payload.urltogo = URL.fileUrl("/blocks/eduvidual/pages/courses.php", "");
                    }
                    require(["block_eduvidual/main"], function(MAIN) {
                        MAIN.resume(o.payload.urltogo, o.result.userid);
                    });
                } else {
                    $('#block_eduvidual_overlay').remove();
                }
            }
        },
        showShibboleth: function(){
            var url = URL.fileUrl("/auth/shibboleth_link", "login.php?embed=1");
            console.log('popPage ', url);
            //MAIN.spinnerGrid(true);
            $.get(url)
             .done(function(body) {
                    console.log('Got body ', body);
                    ModalFactory.create({
                        title: 'edu.IDAM',
                        //type: ModalFactory.types.OK,
                        body: body,
                        //footer: 'footer',
                    }).done(function(modal) {
                        //MAIN.spinnerGrid(false);
                        console.log('Created modal');
                        modal.show();
                    });
             })
             .fail(function(err) {
                console.err('Error', err);
            });
        },
    };
});
