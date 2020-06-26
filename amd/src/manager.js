define(['jquery', 'core/ajax', 'core/modal_events', 'core/modal_factory', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main', 'local_eduvidual/user', 'local_eduvidual/widgets'], function($, AJAX, ModalEvents, ModalFactory, NOTIFICATION, STR, URL, MAIN, USER, WIDGETS) {
    return {
        addParentFilterRequest: 0,
        debug: 0,
		customcsscache: '',
        addParentFilter: function(type, inp) {
            console.log('local_eduvidual/main:addParentFilter(type, inp)', type, inp);
            this.addParentFilterRequest++
            var orgid = $('#local_eduvidual_manage_addparent_studentfilter').attr('data-orgid');
            var studentid = 0;

            var select = $('#local_eduvidual_manage_addparent_' + type);
            $(select).empty();

            if ($(inp).val().length == 0) {
                $(inp).val('*');
            }
            studentid = $('#local_eduvidual_manage_addparent_student').val();

            var addParentFilterRequest = this.addParentFilterRequest;

            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'addparent_filter', orgid: orgid, filter: $(inp).val(), studentid: studentid }, { signalItem: inp, appendItem: select, type: type, request: addParentFilterRequest });
            });
        },
        addParentSelectStudent: function() {
            this.addParentFilter('parent', $('#local_eduvidual_manage_addparent_parentfilter'));
        },
        addParent: function(inp){
            var orgid = $('#local_eduvidual_manage_addparent_studentfilter').attr('data-orgid');
            var studentid = $('#local_eduvidual_manage_addparent_student').val();
            var parentid = $('#local_eduvidual_manage_addparent_parent').val();
            if (studentid > 0 && parentid > 0) {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'manage', act: 'addparent', orgid: orgid, studentid: studentid, parentid: parentid }, { signalItem: inp });
                });
            }
        },
		addUser: function(secret){
            if (typeof secret === 'undefined') {
                secret = $('#local_eduvidual_manage_adduser').val();
            }
            var role = $('#local_eduvidual_manage_adduser_role').val();
			var orgid = $('#local_eduvidual_manage_adduser').attr('data-orgid');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'adduser', orgid: orgid, role: role, secret: secret }, { signalItem: $('#local_eduvidual_manage_adduser') });
            });
		},
        addUserAnonymous: function(){
            var orgid = +$('#local_eduvidual_manage_createuseranonymous_orgid').val();
            var role = $('#local_eduvidual_manage_createuseranonymous_role').val();
            var amount = +$('#local_eduvidual_manage_createuseranonymous_amount').val();
            var cohorts = $('#local_eduvidual_manage_createuseranonymous_cohorts').val();
            var maximum = 50;
            if (amount > maximum) {
                STR.get_strings([
                        {'key' : 'manage:createuseranonymous:exceededmax:title', component: 'local_eduvidual' },
                        {'key' : 'manage:createuseranonymous:exceededmax:text', component: 'local_eduvidual', param: { 'maximum': maximum } },
                    ]).done(function(s) {
                        NOTIFICATION.alert(s[0], s[1]);
                    }
                ).fail(NOTIFICATION.exception);
            } else {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'manage', act: 'adduser_anonymous', orgid: orgid, role: role, amount: amount, cohorts: cohorts }, { signalItem: $('#local_eduvidual_manage_adduseranonymous_btn') });
                });
            }
        },
        categoryAdd: function(src, orgid, parentid) {
            if (typeof src !== 'undefined') {
                var li = $(src);
                while(!$(li).is('li')) {
                    li = $(li).parent();
                }
            }
            if (typeof orgid === 'undefined') {
                orgid = +$('.ul-eduvidual-courses').attr('data-orgid');
            }
            if (typeof parentid === 'undefined') {
                parentid = +li.attr('data-categoryid');
            }
            var runnable = {
                orgid: orgid,
                li: li,
                parentid: parentid,
                run: function(name){
                    var o = this;
                    if (name !== null && name.length > 2) {
                        require(['local_eduvidual/main'], function(MAIN) {
                            MAIN.connect({ module: 'manage', act: 'addcategory', orgid: o.orgid, parentid: o.parentid, name: name }, { parent: o.li });
                        });
                    } else if(name !== null) {
                        STR.get_strings([
                                {'key' : 'categoryadd:title:length:title', component: 'local_eduvidual' },
                                {'key' : 'categoryadd:title:length:text', component: 'local_eduvidual' },
                            ]).done(function(s) {
                                NOTIFICATION.alert(s[0], s[1]);
                            }
                        ).fail(NOTIFICATION.exception);
                    }
                },
            }
            console.log(runnable);
            var prompt = WIDGETS.prompt();
            STR.get_strings([
                    {'key' : 'categoryadd:title', component: 'local_eduvidual' },
                    {'key' : 'categoryadd:text', component: 'local_eduvidual' },
                ]).done(function(s) {
                    prompt.create(s[0], s[1], '', runnable);
                }
            ).fail(NOTIFICATION.exception);
        },
        categoryEdit: function(src, orgid, parentid, currentname) {
            if (typeof src !== 'undefined') {
                var li = $(src);
                while(!$(li).is('li')) {
                    li = $(li).parent();
                }
            }
            if (typeof orgid === 'undefined') {
                orgid = +$('.ul-eduvidual-courses').attr('data-orgid');
            }
            if (typeof parentid === 'undefined') {
                parentid = +li.attr('data-categoryid');
            }
            if (typeof currentname === 'undefined') {
                currentname = '';
            }
            var runnable = {
                orgid: orgid,
                li: li,
                parentid: parentid,
                run: function(name){
                    var o = this;
                    if (name !== null && name.length > 2) {
                        require(['local_eduvidual/main'], function(MAIN) {
                            MAIN.connect({ module: 'manage', act: 'editcategory', orgid: o.orgid, parentid: o.parentid, name: name }, { parent: o.li });
                        });
                    } else if(name !== null) {
                        STR.get_strings([
                                {'key' : 'categoryedit:title:length:title', component: 'local_eduvidual' },
                                {'key' : 'categoryedit:title:length:text', component: 'local_eduvidual' },
                            ]).done(function(s) {
                                NOTIFICATION.alert(s[0], s[1]);
                            }
                        ).fail(NOTIFICATION.exception);
                    }
                },
            }
            console.log(runnable);
            var prompt = WIDGETS.prompt();
            STR.get_strings([
                    {'key' : 'categoryadd:title', component: 'local_eduvidual' },
                    {'key' : 'categoryadd:text', component: 'local_eduvidual' },
                ]).done(function(s) {
                    prompt.create(s[0], s[1], currentname, runnable);
                }
            ).fail(NOTIFICATION.exception);
        },
        categoryRemove: function(src, confirm, orgid, parentid) {
            if (typeof confirm === 'undefined' || !confirm) {
                var MANAGER = this;
                STR.get_strings([
                        {'key' : 'categoryremove:title', component: 'local_eduvidual'},
                        {'key' : 'categoryremove:text', component: 'local_eduvidual'},
                        {'key' : 'yes' },
                        {'key' : 'no' }
                    ]).done(function(s) {
                        NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function() { MANAGER.categoryRemove(src, true, orgid, parentid)});
                    }
                ).fail(NOTIFICATION.exception);
            } else {
                if (typeof orgid === 'undefined') {
                    orgid = +$('.ul-eduvidual-courses').attr('data-orgid');
                }
                if (typeof parentid === 'undefined') {
                    var li = $(src);
                    while(!$(li).is('li')) {
                        li = $(li).parent();
                    }
                    parentid = +li.attr('data-categoryid');
                }
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'manage', act: 'removecategory', orgid: orgid, parentid: parentid }, { parent: li });
                });
            }
        },
        createAccesscode: function() {
            var code = $('#local_eduvidual_manage_accesscode_code').val();
            var orgid = +$('#local_eduvidual_manage_adduser').attr('data-orgid');
            var maturity = $('#local_eduvidual_manage_accesscode_maturity').val();
            var role = $('#local_eduvidual_manage_accesscode_role').val();
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'accesscode_create', orgid: orgid, code: code, role: role, maturity: maturity }, { signalItem: $('#local_eduvidual_manage_accesscode_btn') });
            });
        },
        customcss: function() {
            var orgid = $('#local_eduvidual_manage_customcss').attr('data-orgid');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.watchValue({
                    target: '#local_eduvidual_manage_customcss',
                    orgid: orgid,
                    run: function() {
                        MAIN.connect({ module: 'manage', act: 'customcss', orgid: this.orgid, customcss: $(this.target).val() }, { signalItem: $(this.target) });
                    }
                });
            });
        },
        editProfile: function(orgid, userid, callback) {
            STR.get_strings([
                    {'key' : 'profile', component: 'core'},
                ]).done(function(s) {
                    var method = 'local_eduvidual_manager_user_form';
                    var data = { orgid: orgid, userid: userid };
                    //console.log('Sending to ', method, data);
                    //MAIN.spinnerGrid(true);
                    AJAX.call([{
                        methodname: method,
                        args: data,
                        done: function(formhtml) {
                            //console.log(formhtml);
                            ModalFactory.create({
                                type: ModalFactory.types.SAVE_CANCEL,
                                title: s[0],
                                body: formhtml,
                            }).then(function(modal) {
                                var root = modal.getRoot();
                                //console.log(root, ModalEvents);
                                root.on(ModalEvents.save, function(e) {
                                    e.preventDefault();
                                    var method = 'local_eduvidual_manager_user_update';
                                    var data = {
                                        'orgid': orgid,
                                        'userid': userid,
                                        'firstname': $(root).find('[name="firstname"]').val(),
                                        'lastname': $(root).find('[name="lastname"]').val(),
                                        'email': $(root).find('[name="email"]').val(),
                                    };
                                    //console.log('SAVING', data);
                                    AJAX.call([{
                                        methodname: method,
                                        args: data,
                                        done: function(result) {
                                            console.log('saved', result);
                                            if (typeof result.success !== 'undefined' && result.success == 1) {
                                                modal.hide();
                                                if (typeof callback !== 'undefined' && typeof callback.run !== 'undefined') {
                                                    callback.run();
                                                }
                                            }
                                            if (typeof result.message !== 'undefined') {
                                                NOTIFICATION.alert(result.subject, result.message);
                                            }
                                        },
                                        fail: NOTIFICATION.exception
                                    }]);
                                });
                                //MAIN.spinnerGrid(false);
                                modal.show();
                            });
                        },
                        fail: NOTIFICATION.exception
                    }]);
                }
            ).fail(NOTIFICATION.exception);
        },
        exportUserPopup: function(orgid, userids) {
            if (this.debug > 0) console.log('local_eduvidual/manager:exportUserPopup(orgid, userids)', orgid, userids);
            AJAX.call([{
                methodname: 'local_eduvidual_manager_user_exportform',
                args: { orgid: orgid, userids: userids },
                done: function(formhtml) {
                    ModalFactory.create({
                        title: STR.get_string('export', 'local_eduvidual'),
                        type: ModalFactory.types.SAVE_CANCEL,
                        body: formhtml,
                    }).done(function(modal) {
                        var root = modal.getRoot();
                        //console.log(root, ModalEvents);
                        root.on(ModalEvents.save, function(e) {
                            e.preventDefault();
                            modal.hide();
                            $(root).find('form').submit();
                            $(root).remove();
                        });
                        STR.get_strings([
                                {'key' : 'export', component: 'local_eduvidual'},
                            ]).done(function(s) {
                                $(root).find('.modal-footer button[data-action="save"]').html(s[0]);
                            });
                        $(root).find('button[type="submit"]').remove();

                        modal.show();
                    });
                },
                fail: NOTIFICATION.exception
            }]);
        },
        forceEnrol: function(courseid) {
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'force_enrol', courseid: courseid }, { });
            });
        },
        /**
        * Sets the maildomain to auto assign users to organizations
        **/
        maildomain: function(inp, orgid, type) {
            if (this.debug > 0) console.log('MANAGER.maildomain(inp, orgid, type)', inp, orgid, type);

            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.watchValue({
                    orgid: orgid,
                    target: $(inp),
                    type: type,
                    run: function() {
                        var o = this;
                        require(['local_eduvidual/main'], function(MAIN) {
                            MAIN.connect({ module: 'manage', act: 'maildomain', orgid: o.orgid, type: o.type, maildomain: $(o.target).val() }, { signalItem: $(o.target) });
                        });
                    }
                });
            });
        },
        /**
        * Searches for users matching the maildomain(s) and assigns them to the organization.
        **/
        maildomain_apply: function(orgid, btn) {
            if (this.debug > 0) console.log('MANAGER.maildomain_apply(orgid, btn)', orgid, btn);
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'maildomain_apply', orgid: orgid }, { signalItem: $(btn) });
            });
        },
        /**
         * Reset the password of users.
         */
        setpwreset: function(){
            var orgid = $('#local_eduvidual_manage_adduser').attr('data-orgid');
            var role = $('#local_eduvidual_manage_setuserrole_role').val();
            var secrets = [];
            $('#local_eduvidual_manage_setuserrole_user option:selected:not([value=""])').each(function(){
                secrets.push($(this).val());
            });
            if (secrets.length == 0) return;
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'setpwreset', orgid: orgid, secrets: secrets }, { signalItem: $('#local_eduvidual_manage_setuserrole') });
            });
        },
        setuserrole: function(){
            var orgid = $('#local_eduvidual_manage_adduser').attr('data-orgid');
            var role = $('#local_eduvidual_manage_setuserrole_role').val();
            var secrets = [];
            $('#local_eduvidual_manage_setuserrole_user option:selected:not([value=""])').each(function(){
                secrets.push($(this).val());
            });
            //var secret = $('#local_eduvidual_manage_setuserrole').val();
            if (secrets.length == 0) return;
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'setuserrole', orgid: orgid, role: role, secrets: secrets }, { signalItem: $('#local_eduvidual_manage_setuserrole') });
            });
        },
        setuserrole_search: function(marksuccess,markfailed){
            var orgid = $('#local_eduvidual_manage_adduser').attr('data-orgid');
            var search = $('#local_eduvidual_manage_setuserrole_search').val();
            if (search.length < 2) {
                return;
            } else {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'manage', act: 'setuserrole_search', orgid: orgid, search: search }, { marksuccess: marksuccess, markfailed: markfailed });
                });
            }
        },
        result: function(o) {
            if (o.data.act == 'accesscode_create') {
                if (o.result.status == 'ok') {
                    top.location.href = URL.relativeUrl('/local/eduvidual/pages/manage.php', { orgid: o.data.orgid, act: 'users', tab: 'accesscodes' });
                }
            }
            if (o.data.act == 'accesscode_revoke') {
                if (o.result.status == 'ok') {
                    top.location.href = URL.relativeUrl('/local/eduvidual/pages/manage.php', { orgid: o.data.orgid, act: 'users', tab: 'accesscodes' });
                }
            }
            if (o.data.act == 'addcategory') {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.confirmed('.ul-eduvidual-courses li[data-categoryid="' + o.data.parentid + '"]', (o.result.status=='ok'));
                });
				if (o.result.status=='ok') {
                    if (typeof o.payload.parent === 'undefined') {
                        // Called by myorgs.php and update AJAX
                        require(['local_eduvidual/user'], function(USER) {
                            USER.loadCategory(o.data.parentid, o.data.orgid);
                        });
                    } else {
                        // Called by manage.php and updates tree
                        top.location.href = top.location.href;
                    }
                }
            }
            if (o.data.act == 'addparent') {
                this.addParentSelectStudent();
            }
            if (o.data.act == 'addparent_filter') {
                if (o.payload.request != this.addParentFilterRequest) {
                    // There has been another request in the meanwhile, drop the results!
                    return;
                }
                $(o.payload.appendItem).empty();
                var keys = Object.keys(o.result.users);
                if (keys.length > 0) {
                    for (var a = 0; a < keys.length; a++) {
                        var u = o.result.users[keys[a]];
                        var state = '';
                        if (o.payload.type == 'parent') {
                            if (typeof u.isparent !== 'undefined' && u.isparent) {
                                state = '+ ';
                            } else {
                                state = '- ';
                            }
                        }
                        $(o.payload.appendItem).append($('<option>').html(state + u.userfullname + ' (' + u.email + ')').attr('value', u.id));
                    }
                }
            }
            if (o.data.act == 'adduser' || o.data.act == 'setuserrole') {
                if (o.result.status=='ok') {
                    $('#local_eduvidual_manage_' + o.data.act).val('ok');
                    setTimeout(function(){
                        $('#local_eduvidual_manage_' + o.data.act).val('');
                    }, 500);
                }
                this.setuserrole_search();
            }
            if (o.data.act == 'adduser_anonymous') {
                $('#local_eduvidual_manage_createuseranonymous_success span').html(o.result.success);
                $('#local_eduvidual_manage_createuseranonymous_failed span').html(o.result.failed);
                $('#local_eduvidual_manage_createuseranonymous_success').css('display', (o.result.success > 0) ? 'block' : 'none');
                $('#local_eduvidual_manage_createuseranonymous_failed').css('display', (o.result.failed > 0) ? 'block' : 'none');
                $('#local_eduvidual_manage_createuseranonymous_success input').attr('data-bunch', o.data.bunch);
            }
            if (o.data.act == 'customcss') {
                $('#local_eduvidual_style_org').html(o.data.customcss);
            }
            if (o.data.act == 'force_enrol') {
                if (o.result.status == 'ok') {
                    top.location.href = URL.fileUrl("/course/view.php", "") + '?id=' + o.data.courseid;
                }
            }
            if (o.data.act == 'maildomain_apply') {
                if (typeof o.result.updated !== 'undefined') {
                    alert('Updated ' + o.result.updated.Teacher + ' Teachers and ' + o.result.updated.Student + ' Students');
                }
            }
            if (o.data.act == 'removecategory' || o.data.act == 'editcategory') {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.confirmed('.ul-eduvidual-courses li[data-categoryid="' + o.data.parentid + '"]', (o.result.status=='ok'));
                });
                if (o.result.status=='ok') {
                    if (typeof o.payload.parent === 'undefined') {
                        // Called by myorgs.php and update AJAX
                        require(['local_eduvidual/user'], function(USER) {
                            USER.loadCategory((typeof o.result.removedcat !== 'undefined')?o.result.removedcat.parent:o.result.editedcat.id, o.data.orgid);
                        });
                    } else if(o.data.act == 'removecategory') {
                        // Called by manage.php and updates tree
                        setTimeout(function(){ $('.ul-eduvidual-courses li[data-categoryid="' + o.data.parentid + '"]').remove();  },500);
                    } else if(o.data.act == 'editcategory') {
                        $('.ul-eduvidual-courses li[data-categoryid="' + o.data.parentid + '"]>label>span').html(o.data.name);
                    }
                }
            }
            console.log(o);
            if (o.data.act == 'setpwreset') {
                STR.get_strings([
                        {'key' : 'manage:users:setpwreset', component: 'local_eduvidual' },
                    ]).done(function(s) {
                        require(['core/modal_factory', 'core/templates'], function(ModalFactory, Templates) {
                            ModalFactory.create({
                                title: s[0],
                                body: Templates.render('local_eduvidual/manage_setpwreset_modal', { failed: o.result.failed.join(', '), hasfailed: o.result.failed.length, hasupdated: o.result.updated.length, updated: o.result.updated.join(', ') }),
                                footer: '',
                            }).done(function(modal) {
                                // Do what you want with your modal.
                                modal.show();
                            });
                        });
                    }
                ).fail(NOTIFICATION.exception);
            }
            if (o.data.act == 'setuserrole_search') {
                if (typeof o.result.users !== 'undefined') {
                    $('#local_eduvidual_manage_setuserrole_user option:not([value=""])').remove();
                    for (var a = 0; a < o.result.users.length; a++) {
                        var s = $('<option>').attr('value', o.result.users[a].secret).html(o.result.users[a].userfullname + ((o.result.users[a].email != '') ? ' (' + o.result.users[a].email +')' : ''));
                        $('#local_eduvidual_manage_setuserrole_user').append(s);
                    }
                    var updated = o.payload.marksuccess;
                    if (typeof updated !== 'undefined' && updated.length > 0) {
                        require(['local_eduvidual/main'], function(MAIN) {
                            for (var a = 0; a < updated.length; a++) {
                                MAIN.signal({signalItem: $('#local_eduvidual_manage_setuserrole_user option[value="' + updated[a] + '"]') }, undefined, 'success');
                            }
                        });
                    }
                    var failed = o.payload.markfailed;
                    if (typeof failed !== 'undefined' && failed.length > 0) {
                        for (var a = 0; a < failed.length; a++) {
                            require(['local_eduvidual/main'], function(MAIN) {
                                MAIN.signal({signalItem: $('#local_eduvidual_manage_setuserrole_user option[value="' + failed[a] + '"]') }, undefined, 'success');
                            });
                        }
                    }
				}
			}
        },
        revokeAccesscode: function(id) {
            var orgid = +$('#local_eduvidual_manage_adduser').attr('data-orgid');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'manage', act: 'accesscode_revoke', orgid: orgid, id: id }, { });
            });
        },
    };
});
