window.addEventListener('load', function(){
require(['jquery'], function($) {
    console.log('checking if dock exits');
    if ($('#dock').length > 0) {
        $('body>div[data-role="page"]').css('padding-left', '50px');
    }
})});;
