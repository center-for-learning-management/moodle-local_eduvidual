define(['jquery', 'core/ajax', 'core/config', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'], function ($, AJAX, CFG, NOTIFICATION, STR, URL, MAIN) {
  return {
    setBackground: function (sender) {
      if ($(sender).attr('data-image') == 'none') {
        var background = '';
      } else if ($(sender).is('input')) {
        var hex = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec($(sender).val());
        var color = [
          parseInt(hex[1], 16),
          parseInt(hex[2], 16),
          parseInt(hex[3], 16),
        ].join('x');
        // The relative URL is sufficient here!
        var background = CFG.wwwroot + '/local/eduvidual/pix/bg_pixel.php?color=' + color;
      } else {
        var background = $(sender).css('background-image').replace('url(', '').replace(')', '').replace('"', '').replace('"', '');
      }

      $(sender).css('filter', 'blur(1px)');
      require(['local_eduvidual/main'], function (MAIN) {
        MAIN.connect({module: 'preferences', act: 'background', background: background}, {sender: sender, signalItem: $(sender)});
      });
    },
    result: function (o) {
      if (o.data.act == 'background') {
        $('#local_eduvidual_preferences_background').find('a, input').css('filter', 'unset');
        if (o.result.status == 'ok') {
          $('#local_eduvidual_preferences_background').find('a, input').parent().removeClass('active');
          $(o.payload.sender).parent().addClass('active');
          $('body').css('background-image', 'url(' + o.data.background + ')');
        }
      }
    },
  };
});
