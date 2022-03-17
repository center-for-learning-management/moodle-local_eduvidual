define(
    ['jquery', 'core/ajax', 'core/modal_factory', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'],
    function($, Ajax, ModalFactory, Notification, Str, Url, MAIN) {
    return {
        check: function() {
            var orgid = $('#eduvidual_registration_orgid').val();
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'register', stage: 0, orgid: orgid });
            });
        },
        request: function() {
            var orgid = $('#eduvidual_registration_orgid').val();
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'register', stage: 1, orgid: orgid });
            });
        },
        validate: function(maxlength) {
            var orgid = $('#eduvidual_registration_orgid').val();
            var token = $('#eduvidual_registration_token').val();
            var name = $('#eduvidual_registration_name').val();

            if (name.length > maxlength) {
                Str.get_strings([
                    {'key': 'registration:name:lengthexceeded:title', 'component': 'local_eduvidual'},
                    {'key': 'registration:name:lengthexceeded:text', 'component': 'local_eduvidual', param: { 'chars' : maxlength } },
                    {'key': 'ok', 'component': 'core' }
                ]).done(function(s) {
                    Notification.alert(s[0], s[1], s[2]);
                }).fail(Notification.exception);
            } else {
                require(['local_eduvidual/main'], function(MAIN) {
                    MAIN.connect({ module: 'register', stage: 2, orgid: orgid, token: token, name: name });
                });
            }
        },
        result: function(o) {
            if (o.result && o.result.status && o.result.status == 'ok') {
                console.log(o.result);
                if (o.data.stage == 0) {
                    $('#local_eduvidual_registration_stage-1, #local_eduvidual_registration_stage-0-error').css({ display: 'none'});
                    if (o.result.authenticated > 0) {
                        $('#local_eduvidual_registration_stage-0-error').css({ display: 'block'});
                    } else {
                        $('#local_eduvidual_registration_stage-1').css({ display: 'block'});
                    }
                    $('#eduvidual_registration_name').val(o.result.name.substring(0,30));
                    $('#eduvidual_registration_mail').html(o.result.mail);
                }
                if (o.data.stage == 1) {
                    Str.get_strings([
                        {'key' : 'mailregister:confirmation', component: 'local_eduvidual' },
                        {'key' : 'mailregister:confirmation:mailsent', component: 'local_eduvidual' },
                        {'key' : 'ok', component: 'core' }
                    ]).done(function(s) {
                        Notification.alert(s[0], s[1], s[2]);
                    }).fail(Notification.exception);
                }
                if (o.data.stage == 2) {
                    // Show success message
                    $('#local_eduvidual_registration_stage-0').css({ display: 'none'});
                    $('#local_eduvidual_registration_stage-1').css({ display: 'none'});
                    $('#local_eduvidual_registration_stage-2').css({ display: 'block'});
                    $('#local_eduvidual_registration_homecategory').attr('href', '/course/index.php?categoryid=' + o.result.categoryid).html(o.result.name);
                }
            } else {
                // @todo localization
                if (o.result.error && o.result.error != '') {
                    Notification.alert('Error', o.result.error, 'ok');
                } else if(o.result.status != 'silent') {
                    Notification.alert('Error', 'Action failed!', 'ok');
                }
            }
        },
    };
});
