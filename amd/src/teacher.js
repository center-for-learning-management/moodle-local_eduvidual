define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'local_eduvidual/main', 'local_eduvidual/user'], function($, AJAX, NOTIFICATION, STR, TEMPLATES, URL, MAIN, USER) {
    return {
        /**
         * Perform an action on a course
        **/
        courseAction: function(courseid, selectmenu) {
            console.log('TEACHER.courseAction(courseid, selectmenu)', courseid, selectmenu);
            var choice = $(selectmenu).val();
            $(selectmenu).val('');
            require(['local_eduvidual/main'], function(MAIN) {
                switch(choice) {
                    case 'enrol':
                        MAIN.navigate(URL.fileUrl('/local/eduvidual/pages/courses.php', '') + '?act=enrol&id=' + courseid);
                    break;
                    case 'gradings':
                        MAIN.navigate(URL.fileUrl('/grade/report/grader/index.php', '') + '?id=' + courseid);
                    break;
                    case 'hideshow':
                        MAIN.connect({ module: 'teacher', act: 'course_hideshow', courseid: courseid }, { });
                    break;
                    case 'remove':
                        this.courseRemove(courseid);
                    break;
                }
            });
        },
        courseRemove: function(courseid, confirm) {
            if (typeof confirm === 'undefined' || !confirm) {
                var TEACHER = this;
                STR.get_strings([
                        {'key' : 'courseremove:title', component: 'local_eduvidual' },
                        {'key' : 'courseremove:text', component: 'local_eduvidual' },
                        {'key' : 'yes' },
                        {'key': 'no'}
                    ]).done(function(s) {
                        NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function() { TEACHER.courseRemove(courseid, true); });
                    }
                ).fail(NOTIFICATION.exception);
            } else {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'teacher', act: 'course_remove', courseid: courseid }, { });
                });
            }
        },
        /**
         * Load possible selection-options to create a course.
        **/
        createCourseSelections: function(uniqid) {
            var TEACHER = this;
            var orgid = +$('#' + uniqid + '-orgid').val();
            var subcat1 = $('#' + uniqid + '-subcat1').val();
            var subcat2 = $('#' + uniqid + '-subcat2').val();
            var subcat3 = $('#' + uniqid + '-subcat3').val();

            var method = 'local_eduvidual_teacher_createcourse_selections';
            var data = { orgid: orgid, subcat1: subcat1, subcat2: subcat2, subcat3: subcat3 };
            console.log('Sending to ', method, data);
            require(['local_eduvidual/main'], function(MAIN) { MAIN.spinnerGrid(true); });
            AJAX.call([{
                methodname: method,
                args: data,
                done: function(result) {
                    require(['local_eduvidual/main'], function(MAIN) { MAIN.spinnerGrid(false); });
                    try { result = JSON.parse(result); } catch(e) {}
                    console.log('local_eduvidual_teacher_createcourse_selections', result);

                    for (a = 1; a <= 4; a++) {
                        $('.' + uniqid + '-subcats' + a + 'lbl').html(result['subcats' + a + 'lbl']);
                    }
                    var trs = [1, 2, 3, 4];
                    var requiredtrs = {1: true, 2: false, 3: false, 4: false};
                    var hiddentrs = {1: false, 2: false, 3: false, 4: false};

                    Object.keys(trs).forEach(function(i) {
                        var key = trs[i];
                        var keys = 'subcat' + key;
                        var keym = 'subcats' + key;
                        var keyl = 'subcats' + key + 'lbl';
                        if (typeof result[keyl] !== 'undefined' && result[keyl] == '') {
                            // We ignore this layer.
                            $('#' + uniqid + '-form .' + uniqid + '-' + keyl).html('').closest('tr').css('display', 'none');
                            hiddentrs[key] = true;
                        } else {
                            if (key <= 2) requiredtrs[key] = true;
                            if (key == 3 && hiddentrs[2]) requiredtrs[key] = true;
                            if (key == 4 && hiddentrs[2] && hiddentrs[3]) requiredtrs[key] = true;
                            $('#' + uniqid + '-form .' + uniqid + '-' + keyl).html(result[keyl]).closest('tr').css('display', '');
                            if (typeof result[keym] !== 'undefined') {
                                // Show select or input.
                                var o = $('#' + uniqid + '-' + keys);
                                var parent = o.parent();

                                if (result[keym] !== null && result[keym].length > 0) {
                                    if (!o.is('select')) {
                                        o.remove();
                                        var o = $('<select>')
                                            .attr('id', uniqid + '-' + keys)
                                            .attr('name', keys)
                                            .attr('onchange', "require(['local_eduvidual/teacher'], function(T) { T.createCourseSelections('" + uniqid + "'); });")
                                            .attr('style', 'width: 100%');
                                        parent.append(o);
                                    } else {
                                        o.empty();
                                    }
                                    o.append($('<option>')
                                        .attr('value', '')
                                        .html('---')
                                    );
                                    Object.keys(result[keym]).forEach(function(index) {
                                        var opt = $('<option>')
                                            .attr('value', result[keym][index])
                                            .html(result[keym][index]);
                                        if (result[keys] == result[keym][index]) {
                                            opt.attr('selected', 'selected');
                                        }
                                        o.append(opt);
                                    });
                                } else {
                                    if (!o.is('input')) {
                                        o.remove();
                                        var o = $('<input>')
                                            .attr('id', uniqid + '-' + keys)
                                            .attr('name', keys)
                                            .attr('onkeyup', "require(['local_eduvidual/teacher'], function(T) { T.createCourseSelections('" + uniqid + "'); });")
                                            .attr('style', 'width: 100%')
                                            .val(result[keys]);
                                        parent.append(o);
                                    }
                                }
                                console.log(o);
                            }
                        }
                    });
                    // Do the coloring of our table.
                    var trigger = false;
                    $('#' + uniqid + '-form table.generaltable tr').each(function(i,e) {
                        if ($(e).css('display') != 'none') {
                            trigger = !trigger;
                            $(e).css('background-color', (trigger) ? 'rgba(0,0,0,0.05)' : '#ffffff');
                        }
                    });

                    var requirementsfulfilled = true;
                    // Decide if button is enabled and flag required fields.
                    Object.keys(requiredtrs).forEach(function(key) {
                        $('#' + uniqid + '-form .' + uniqid + '-subcat' + key + ' td.requirement').html('');
                        if (requiredtrs[key]) {
                            console.log('Check ', key, $('#' + uniqid + '-form #' + uniqid + '-subcat' + key).val());
                            if ($('#' + uniqid + '-form #' + uniqid + '-subcat' + key).val() == '') {
                                requirementsfulfilled = false;
                            }
                            TEACHER.createCourseSelectionsRequired($('#' + uniqid + '-form .' + uniqid + '-subcat' + key + ' td.requirement'));
                        }
                    });
                    if (!requirementsfulfilled) {
                        $('#' + uniqid + '-submit').addClass('disabled');
                    } else {
                        $('#' + uniqid + '-submit').removeClass('disabled');
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
        /**
         * Append the "required"-icon to a html element.
         * @param e the html element to append to.
         */
        createCourseSelectionsRequired: function(e) {
            TEMPLATES
                .render('local_eduvidual/teacher_createcourse_required', {})
                .then(function(html, js) {
                    $(e).html('');
                    TEMPLATES.appendNodeContents($(e), html, js);
                }).fail(function(ex) {

                });
        },
        createModule: function(){
            var orgid = +$('#local_eduvidual_teacher_createmodule').attr('data-orgid');
            var courseid = +$('#local_eduvidual_teacher_createmodule').attr('data-courseid');
            var sectionid = +$('#local_eduvidual_teacher_createmodule').attr('data-sectionid');
            var moduleid = this.module.id;
            var form = {};
            var customize = this.module.payload.customize;
            if (typeof customize !== 'undefined') {
        		var keys = Object.keys(customize);
        		for (var a = 0; a < keys.length; a++) {
        			var param = keys[a];
        			var field = customize[param];
        			var id = 'edublock_custom_' + param;
                    form[param] = $('#' + id).val();
                }
            }
            form['course'] = courseid;
            form['section'] = sectionid;
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'teacher', act: 'createmodule_create', orgid: orgid, moduleid: moduleid, formdata: JSON.stringify(form) }, { section: sectionid });
            });
        },
        /**
         * Searches for modules provided by edupublisher
        **/
        createModuleSearch: function(uniqid){
            var data = {};
            $('#' + uniqid + '-createmoduleform').serializeArray().map(function(x){data[x.name] = x.value;});
            data.module = 'teacher';
            data.act = 'createmodule_search';
            console.log(data);
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect(data, { uniqid: uniqid, sectionid: data.sectionid, courseid: data.courseid });
            });
        },
        loadCategory: function(categoryid) {
            if (this.debug > 0) console.log('TEACHER.loadCategory(categoryid)', categoryid);
            var orgid = $('#local_eduvidual_teacher_createmodule').attr('data-orgid');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'teacher', act: 'createmodule_category', orgid: orgid, categoryid: categoryid });
            });
        },
        loadCourseCategory: function(categoryid, orgid) {
            if (typeof orgid === 'undefined') {
                orgid = +$('#local_eduvidual_teacher_createcourse').attr('data-orgid');
            }
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'teacher', act: 'createcourse_category', orgid: orgid, categoryid: categoryid });
            });
        },
        loadCourseForm: function(step){
            console.log('TEACHER.loadCourseForm(step)', step);
            if (typeof step === 'undefined') step = 0;
            var categoryid = +$('.ul-eduvidual-courses').attr('data-categoryid');
            var orgid = +$('.ul-eduvidual-courses').attr('data-orgid');
            // Maybe we can skip step 0
            if (orgid > 0 && categoryid > 0 && step == 0) step = 1;

            switch (step) {
                case 0:
                    // select org and category
                break;
                case 1:
                    var container = $('.ul-eduvidual-courses').empty();
                    // Controlgroup with back and create button
                    var controlgroup = $('<div>')
                        //.addClass('form-inline felement')
                        .addClass('grid-eq-2')
                        .css('text-align', 'center');
                        //.attr('data-fieldtype', 'group');
                    container.append(controlgroup);

                    STR.get_strings([
                            {'key' : 'back', component: 'local_eduvidual' },
                            {'key' : 'create', component: 'local_eduvidual' },
                            {'key' : 'createcourse:basement', component: 'local_eduvidual' },
                            {'key' : 'createcourse:name', component: 'local_eduvidual' },
                            {'key' : 'createcourse:nameinfo', component: 'local_eduvidual' },
                            {'key' : 'createcourse:setteacher', component: 'local_eduvidual' },
                            {'key' : 'filter', component: 'core' },
                        ]).done(function(s) {
                            // Always add the back-button
                            var divback = $('<div>');//.addClass('form-group fitem');
                            var aback = $('<a>')
                                .addClass('ui-btn btn btn-_secondary')
                                .attr('onclick', 'require(["local_eduvidual/user"], function(USER) { USER.loadCategory(' + (+$('.ul-eduvidual-courses').attr('data-categoryid')) + '); });')
                                .html(s[0]);
                            aback.html('<img src="/pix/t/left.svg" alt=""> ' + aback.html());
                            divback.append(aback);

                            // Create-btn
                            var btnstore = $('<a>')
                                .addClass('btn ui-btn').attr('href', '#')
                                .attr('onclick', 'require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCourseForm(2); });')
                                .html(s[1]);

                            controlgroup.append([divback, btnstore]);

                            var formgroup = $('<div>')
                                //.addClass('form-inline felement')
                                .addClass('grid-eq-2');
                                //.attr('data-fieldtype', 'group');
                            container.append(formgroup);

                            var divleft = $('<div>');
                            var divright = $('<div class="div-right">');
                            formgroup.append([divleft, divright]);

                            // Select base course
                            var baseselectlbl = $('<label>').html(s[2]);
                            var baseselect = $('<select style="max-width: 100%;">')
                                .attr('onchange', 'var sel = this; require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCourseFormBasementInfo(sel.value); });')
                                .addClass('local_eduvidual_teacher_createcourse_basement');
                            baseselect.append('<option>').html('loading');

                            // Input name
                            var basenamelbl = $('<label>').html(s[3]);
                            var basenameinfo = $('<p>').html(s[4]);
                            var basename = $('<input>').addClass('local_eduvidual_teacher_createcourse_name');

                            divleft.append([baseselectlbl, baseselect, basenamelbl, basename, basenameinfo]);

                            var asteacher = $('<div>').addClass('local_eduvidual_teacher_createcourse_setteacher').css('display', 'none');
                            var label = $('<p>').html(s[5]);
                            var filter = $('<input>').attr('placeholder', s[6])
                                .attr('onkeyup', 'var inp = this; require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCourseTeacher(' + orgid + ', inp); });');
                            var select = $('<select>');
                            asteacher.append([label, filter, select]);
                            container.append(asteacher);

                            require(['local_eduvidual/main'], function(MAIN) {
                                MAIN.connect({ module: 'teacher', act: 'createcourse_basements', orgid: orgid }, { signalItem: baseselect });
                            });
                        }
                    ).fail(NOTIFICATION.exception);
                break;
                case 2:
                    // Validate and create
                    var base = +$('.local_eduvidual_teacher_createcourse_basement').val();
                    var name = $('.local_eduvidual_teacher_createcourse_name').val();
                    var setteacher = +$('.local_eduvidual_teacher_createcourse_setteacher select').val();
                    require(['local_eduvidual/main'], function(MAIN) {
                        MAIN.connect({ module: 'teacher', act: 'createcourse_now', orgid: orgid, categoryid: categoryid, basement: base, name: name, setteacher: setteacher }, { signalItem: {}});
                    });
                break;
            }
        },
        loadCourseTeacher: function(orgid, inp) {
            var search = $(inp).val();
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'teacher', act: 'createcourse_loadteacher', orgid: orgid, search: search }, { signalItem: {}});
            });
        },
        /**
         * Show meta information for a specific basement
        **/
        loadCourseFormBasementInfo: function(basement) {
            var cats = Object.keys(this.basements);
            var container = $('.ul-eduvidual-courses .div-right').empty();
            for (var a = 0; a < cats.length; a++) {
                for (var b = 0; b < this.basements[cats[a]].length; b++) {
                    var base = this.basements[cats[a]][b];
                    if (base.id == basement && $(container).find('img').length == 0) {
                        var p = $('<p>').html(base.summary)
                            .css('text-align', 'justify');
                        if (base.imageurl !== '') {
                            var img = $('<img>').attr('src', base.imageurl)
                                .css('max-width', '200px').css('width', '25%')
                                .css('margin', '0px 0px 5px 5px').css('float', 'right');
                            p.prepend(img);
                        }
                        container.append(p);
                    }
                }
            }
        },
        questioncategories: function(sender) {
			var questioncategories = [];
			$.each($("input[name='questioncategories[]']:checked"), function() {
                questioncategories.push(+$(this).val());
			});
            require(['local_eduvidual/main'], function(MAIN) {
			    MAIN.connect({ module: 'user', act: 'questioncategories', questioncategories: questioncategories }, { signalItem: $(sender).parent() });
            });
		},
        result: function(o) {
            var TEACHER = this;
            if (o.data.act == 'course_hideshow') {
                if (o.result.status == 'ok') {
                    history.go(0);
                } else {
                    // @todo localization
                    NOTIFICATION.alert(o.result.error);
                }
            }
            if (o.data.act == 'course_remove') {
                if (o.result.status == 'ok') {
                    history.go(-1);
                } else {
                    // @todo localization
                    NOTIFICATION.alert(o.result.error);
                }
            }
            if (o.data.act == 'createcourse_basements') {
                this.basements = o.result.basements;
                var cats = Object.keys(o.result.basements);
                var targ = $('.local_eduvidual_teacher_createcourse_basement').empty();
                var firstloaded = false;
                for (var a = 0; a < cats.length; a++) {
                    var optgroup = $('<optgroup>').attr('label', cats[a]);
                    for (var b = 0; b < o.result.basements[cats[a]].length; b++) {
                        var base = o.result.basements[cats[a]][b];
                        var option = $('<option>').attr('value', base.id).html(base.fullname);
                        optgroup.append(option);
                        if (!firstloaded) {
                            firstloaded = true;
                            this.loadCourseFormBasementInfo(base.id);
                        }
                    }
                    targ.append(optgroup);
                }
                if (o.result.canmanage) {
                    $('.local_eduvidual_teacher_createcourse_setteacher').css('display', 'block');
                } else {
                    $('.local_eduvidual_teacher_createcourse_setteacher').css('display', 'block');
                }
            }
            if (o.data.act == 'createcourse_category') {
                if (o.result.status == 'ok') {
                    var parentid = (typeof o.result.parent !== 'undefined' && o.result.parent.id > -1)?o.result.parent.id:-1;
                    var div = $('#local_eduvidual_teacher_createcourse').empty();
                    $('#local_eduvidual_teacher_createcourse_title').html(o.result.category.name);

                    STR.get_strings([
                            {'key' : 'back', component: 'local_eduvidual' },
                            {'key' : 'createcourse:here', component: 'local_eduvidual' },
                        ]).done(function(s) {
                            // Controlgroup with back and create button
                            var controlgroup = $('<div>')
                                .addClass('form-inline felement')
                                .css('text-align', 'center')
                                .attr('data-fieldtype', 'group');
                            var div1 = $('<div>').addClass('form-group fitem');
                            var div2 = $('<div>').addClass('form-group fitem');
                            var aback = $('<a>').addClass('ui-btn btn btn-secondary').attr('onclick', 'history.go(-1);').html(s[0]);

                            if (typeof parentid !== 'undefined' && parentid > 0) {
                                aback.attr('onclick', 'require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCourseCategory(' + o.result.parent.id + '); });').html(o.result.parent.name);
                            }
                            aback.html('<img src="/pix/t/left.svg" alt=""> ' + aback.html());
                            var aplus = $('<a>').addClass('ui-btn btn btn-primary').attr('href', '#')
                                .attr('onclick', 'require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCourseForm(' + o.result.category.id + '); });')
                                .html('<img src="/pix/t/add.svg" alt=""> ' + s[1]);
                            div1.append(aback);
                            div2.append(aplus);
                            controlgroup.append([div1, div2]);
                            div.append(controlgroup);
                        }
                    ).fail(NOTIFICATION.exception);

                    var ul = $('<ul>')
                        .attr('data-role', 'listview').attr('data-inset', 'true').attr('data-filter', 'true')
                        .addClass('ui-listview ui-listview-inset ui-corner-all ui-shadow');

                    /*
                    var li = $('<li>').addClass('ui-li-has-count ui-first-child');
                    var an = $('<a>').attr('href', '#').attr('onclick', 'local_eduvidual_TEACHER.loadCourseForm(' + o.result.category.id + ');').addClass('ui-btn btn');
                    var h3 = $('<h3>').html(local_eduvidual_LANG['js:createcourse:here']);
                    an.append([h3, p]);
                    li.append(an);
                    ul.append(li);
                    */

                    if (typeof o.result.children !== 'undefined') {
                        var k = Object.keys(o.result.children);
                        if (typeof o.result.children !== 'undefined' && k.length > 0) {
                            for (var a = 0; a < k.length; a++) {
                                var child = o.result.children[k[a]];
                                var li = $('<li>').addClass('ui-li-has-count' + ((a == (k.length - 1))?' ui-last-child':''));
                                var an = $('<a>').attr('href', '#').attr('onclick', 'require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCourseCategory(' + child.id + '); });');
                                var h3 = $('<h3>').html(child.name);
                                var p = $('<p>').html(child.description);
                                an.append([h3, p]);
                                li.append(an);
                                ul.append(li);
                            }
                        }
                    }
                    div.append(ul);
                    try { $(ul).trigger('create'); } catch(e) {}
                }
            }
            if (o.data.act == 'createcourse_now' && o.result.status == 'ok') {
                // Reload category list
                // Surely this is local_eduvidual_USER, not local_eduvidual_TEACHER !!
                require(['local_eduvidual/user'], function(USER) {
                    USER.loadCategory(o.data.categoryid, o.data.orgid);
                });
            }
            if (o.data.act == 'createcourse_loadteacher' && o.result.status == 'ok') {
                var sel = $('.local_eduvidual_teacher_createcourse_setteacher select').empty();
                for (var i = 0; i < o.result.users.length; i++) {
                    var u = o.result.users[i];
                    var option = $('<option>').attr('value', u.id).html(u.userfullname + '(' + u.email + ')');
                    sel.append(option);
                }
            }
            if (o.data.act == 'user_search') {
                var selectmenu;
                if ($(o.payload.sender).attr('id') == 'local_eduvidual_courses_courseusers_search') {
                    selectmenu = '#local_eduvidual_courses_courseusers';
                }
                if ($(o.payload.sender).attr('id') == 'local_eduvidual_courses_orgusers_search') {
                    selectmenu = '#local_eduvidual_courses_orgusers';
                }
                if (typeof selectmenu === 'undefined') {
                    console.log(o.payload);
                    NOTIFICATION.alert('Unknown type of search');
                } else {
                    if (o.result.users.length == 0 && !o.payload.initialsearch) {
                        $(selectmenu).empty();
                        $(selectmenu).append($('<option>').html('no results'));
                    } else if(o.result.users.length > 0) {
                        $(selectmenu).empty();
                        for(var a = 0; a < o.result.users.length; a++) {
                            $(selectmenu).append($('<option>').attr('value', o.result.users[a].userid).html(o.result.users[a].name));
                        }
                    }
                }
            }
            if (o.data.act == 'user_set') {
                this.user_search("#local_eduvidual_courses_courseusers_search", "courseusers", 0);
            }
        },
        user_search: function(sender, type, initialsearch){
            var orgid = +$('#local_eduvidual_courses').attr('data-orgid');
            var courseid = +$('#local_eduvidual_courses').attr('data-courseid');
            var searchfor = $(sender).val();
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'teacher', act: 'user_search', orgid: orgid, courseid: courseid, type: type, searchfor: searchfor }, { sender: sender, initialsearch: initialsearch });
            });
        },
        user_set: function(sender, type) {
            var selectmenu, role;
            var userids = [];
            if (type == 'enrol') {
                selectmenu = $('#local_eduvidual_courses_orgusers');
                role = $('#local_eduvidual_courses_setrole').val();
            } else if (type == 'unenrol'){
                selectmenu = $('#local_eduvidual_courses_courseusers');
                role = 'remove';
            }
            if (typeof selectmenu === 'undefined') return;

            var orgid = +$('#local_eduvidual_courses').attr('data-orgid');
            var courseid = +$('#local_eduvidual_courses').attr('data-courseid');
            var userids = [];
            $(selectmenu).find('option:selected:not([value=""])').each(function(){
                userids.push(+$(this).val());
            });
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'teacher', act: 'user_set', orgid: orgid, courseid: courseid, role: role, userids: userids }, { signalItem: $(sender) });
            });
        },
    };
});
