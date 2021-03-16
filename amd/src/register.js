define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'], function($, AJAX, NOTIFICATION, STR, URL, MAIN) {
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
        validate: function() {
            var orgid = $('#eduvidual_registration_orgid').val();
            var token = $('#eduvidual_registration_token').val();
            var name = $('#eduvidual_registration_name').val();
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'register', stage: 2, orgid: orgid, token: token, name: name });
            });
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
                    STR.get_strings([
                        {'key' : 'mailregister:confirmation', component: 'local_eduvidual' },
                        {'key' : 'mailregister:confirmation:mailsent', component: 'local_eduvidual' },
                        {'key' : 'ok' }
                    ]).done(function(s) {
                        NOTIFICATION.alert(s[0], s[1], s[2]);
                    }).fail(NOTIFICATION.exception);
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
                    NOTIFICATION.alert('Error', o.result.error, 'ok');
                } else if(o.result.status != 'silent') {
                    NOTIFICATION.alert('Error', 'Action failed!', 'ok');
                }
            }
        },
    };
});
