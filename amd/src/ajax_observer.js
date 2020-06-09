// Attention, ajax_observer requires two components.
// The global script has to be included in the document.
// This is the according AMD Module and is required by the global script.
define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'], function($, AJAX, NOTIFICATION, STR, URL, MAIN) {
    return {
        debug: true,
        inci: 0, // incrementing id
        /**
        * Add the filter to the questionbank.
        */
        questionBankCategoryAddFilter: function() {
            var OBSERVER = this;
            OBSERVER.inci++;
            if (OBSERVER.debug) console.log('local_eduvidual/ajax_observer:questionBankCategoryAddFilter(), inci is ', OBSERVER.inci);
            STR.get_strings([
                {'key' : 'questioncategoryfilter:label', component: 'local_eduvidual' },
            ]).done(function(s) {
                var keyup = 'var i = $(\'#id_filtercategory_' + OBSERVER.inci + '\'); require([\'local_eduvidual/main\', \'local_eduvidual/ajax_observer\'], function(M, OBSERVER) { M.watchValue({ target: i, run: function() { OBSERVER.questionBankCategoryFilter($(i)); } }); }); return false;';
                var formedit = $('.questionbankformforpopup form[action$="/mod/quiz/edit.php"]:not(".observer-modified")');
                var formrandom = $('form[action$="/mod/quiz/addrandom.php"]:not(".observer-modified")');
                var label = $('<label>').attr('for', 'id_filtercategory_' + OBSERVER.inci).html(s[0] + ':').css('min-width', '150px');
                var input = $('<input>').attr('id', 'id_filtercategory_' + OBSERVER.inci)
                    .attr('onkeyup', keyup)
                    .attr('style', 'width: 100%');
                var clearbtn = $('<a>')
                    .attr('onclick', '\$(\'#id_filtercategory_' + OBSERVER.inci + '\').val(\'\');' + keyup)
                    .attr('style', 'position: absolute; left: calc(100% - 35px);')
                    .append(
                        $('<img>').attr('src', URL.relativeUrl('/pix/t/delete.svg')).addClass('icon')
                    );

                if (formedit.length > 0) {
                    $(formedit).addClass('observer-modified');
                    $(formedit).parent().prepend([
                        $('<div>').attr('id', 'fitem_id_filtercategory').addClass('form-group row fitem filtercategory').append([
                            $('<div>').addClass('col-md-3').append([
                                label
                            ]),
                            $('<div>').addClass('col-md-9 form-inline felement').append([
                                input, clearbtn
                            ]),
                        ])
                    ]);
                    $(formedit).find('select#id_category, select#id_selectacategory').css({'width': '100%'});
                }
                if (formrandom.length > 0) {
                    $(formrandom).addClass('observer-modified');
                    $(formrandom).find('fieldset#id_existingcategoryheader').prepend([
                        $('<div>').attr('id', 'fitem_id_filtercategory').addClass('form-group row fitem filtercategory').append([
                            $('<div>').addClass('col-md-3').append([
                                label
                            ]),
                            $('<div>').addClass('col-md-9 form-inline felement').append([
                                input, clearbtn
                            ]),
                        ])
                    ]);
                    $(formrandom).find('select#id_category, select#id_selectacategory').css({'width': '100%'});
                }
            }
        ).fail(NOTIFICATION.exception);
    },
    /**
    * Toggle visiblity of certain category-items from questionbank.
    */
    questionBankCategoryFilter: function(input) {
        var OBSERVER = this;
        var needle = $(input).val().toLowerCase();

        var container = $(input).closest('.modal-body');
        var select = $(container).find('select#id_category, select#id_selectacategory');
        if (needle == '') {
            $(select).find('option, optgroup').css('display', '');
            this.confirmed($(select), true, 500);
            return;
        }
        if (OBSERVER.debug) console.log('Search for ', needle, ' in ', select);
        $(select).find('optgroup').css('display', 'none');
        $(select).find('option').each(function(i, e) {
            if (($(e).html().toLowerCase().indexOf(needle) === -1)) {
                $(e).css('display', 'none');
            } else {
                $(e).css('display', '');
                $(e).parent().css('display', '');
            }
            if (OBSERVER.debug) console.log($(e), $(e).html(), $(e).css('display'));
        });
        this.confirmed($(select), true, 500);
    },
    /**
    * Sets and removes the confirmed state for html elements
    **/
    confirmed: function(selector, success, timeout) {
        //var className = 'alert alert-' + ((success)?'success':'danger');
        if (typeof timeout === 'undefined' || timeout == 0) timeout = 1000;
        console.log('local_eduvidual/ajax_observer:confirmed(selector, success, timeout)', selector, success, timeout);
        $(selector).css({'background-color': 'rgba(0, 255, 0, 0.1)'});
        setTimeout(function(){
            $(selector).css({'background-color': ''});
        }, timeout);
    },
};
});
