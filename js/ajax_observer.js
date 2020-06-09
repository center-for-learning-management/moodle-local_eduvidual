// Attention, ajax_observer requires two components.
// This script has to be included in the document.
// The according AMD Module is required by this script.


var observer_debug = false;
var observer_preview = false;
var xmlhttprequestopen = window.XMLHttpRequest.prototype.open,
    xmlhttprequestsend = window.XMLHttpRequest.prototype.send;

function XMLHttpRequestOpen(method, url, async, user, password) {
    this._url = url;
    return xmlhttprequestopen.apply(this, arguments);
}

function XMLHttpRequestSend(data) {
    if (observer_debug) console.log(this,data,arguments);
    try {
        var odata = JSON.parse(data);
        var replacement;
        if (typeof odata[0].args !== 'undefined' && typeof odata[0].args.callback !== 'undefined') {
            switch(odata[0].args.callback) {
                case 'quiz_question_bank':
                case 'add_random_question_form':
                    replacement = onReadyStateChangeReplacementQuestionBank;
                break;
            }
        }
        if (typeof replacement !== 'undefined') {
            if (this.onreadystatechange) {
                this._onreadystatechange = this.onreadystatechange;
            }
            this.onreadystatechange = replacement;
        }
        if (typeof replacement !== 'undefined' && observer_debug) console.log('Watch event', odata);
        if (typeof replacement === 'undefined' && observer_debug) console.log('Do not watch event', odata);
    } catch(e) {}

    return xmlhttprequestsend.apply(this, arguments);
};

function onReadyStateChangeReplacementQuestionBank() {
    if (this.readyState == 4) {
        require(['jquery'], function($) {
            setTimeout(function() {
                require(['local_eduvidual/ajax_observer'], function(OBSERVER) {
                    OBSERVER.questionBankCategoryAddFilter();
                });
            }, 500);
        });
    }

    if(this._onreadystatechange) {
        return this._onreadystatechange.apply(this, arguments);
    }
}

window.XMLHttpRequest.prototype.open = XMLHttpRequestOpen;
window.XMLHttpRequest.prototype.send = XMLHttpRequestSend;
