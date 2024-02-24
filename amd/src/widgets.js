define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'], function ($, AJAX, NOTIFICATION, STR, URL, MAIN) {
  return {
    widgets: [],
    currentQRScanner: undefined,
    fixDrawingCanvas: function () {
      $('.drawingcanvas').each(function () {
        $(this).attr('touch-action', 'none').attr('style', $(this).attr('style') + ';touch-action: none;');
      });
    },
    getwidgetid: function () {
      var widgetid = ((+new Date) + Math.random() * 100).toString(32);
      while (typeof this.widgets[widgetid] !== 'undefined') {
        widgetid = ((+new Date) + Math.random() * 100).toString(32);
      }
      return widgetid;
    },
    jqPanel: function (id) {
      var widgetid = this.getwidgetid();
      var panel = $(id);

      this.widgets[widgetid] = {
        panel: panel,
        widgetid: widgetid,
        run: function () {

        }
      }
    },
    switchtab: function (uniqid, id, sender) {
      for (var a = 0; a < $('.tab-container-' + uniqid + '>*').length; a++) {
        $('#' + uniqid + '-tab-' + a).css('display', (id == a) ? 'block' : 'none');
      }
      $('.tab-container-' + uniqid + '>*').removeClass('btn-primary');
      $(sender).addClass('btn-primary');
    },
    panel: function (panel) {
      if (typeof $(panel).attr('data-widgetid') !== 'undefined') {
        this.widgets[$(panel).attr('data-widgetid')].toggle();
      } else {
        var widgetid = this.getwidgetid();
        this.widgets[widgetid] = {
          widgetid: widgetid,
          runnable: undefined,
          panel: panel,
          init: function () {
            console.log(this);
            $(this.panel).attr('data-widgetid', this.widgetid);
            this.layer = $('<div>')
              .attr('onclick', 'require(["local_eduvidual/widgets"], function(WIDGETS) { WIDGETS.widgets[\'' + this.widgetid + '\'].run(); });')
              .addClass('eduvidual-widget-panel-layer widget-' + this.widgetid);
            $('body').append(this.layer);
            var that = this;
            $(this.panel + ' a').each(function (i) {
              // In this block "this" is the jquery element.
              if (!$(this).hasClass('widget-close-on-click')) {
                $(this).addClass('widget-close-on-click');
                var onclick = 'require(["local_eduvidual/widgets"], function(WIDGETS) { WIDGETS.widgets[\'' + that.widgetid + '\'].toggle(-1); %original% });';
                if ($(this).is('[onclick]')) {
                  onclick = onclick.replace('%original%', $(this)[0].getAttribute('onclick'));
                } else {
                  onclick = onclick.replace('%original%', '');
                }
                $(this).attr('onclick', onclick);
              }
            });
            this.toggle();
          },
          toggle: function (force) {
            if (typeof force === 'undefined') {
              force = 0;
            }
            var to = ($(this.panel).hasClass('ui-panel-closed') ? 1 : -1);
            if (to == 1 || force == 1) {
              $(this.panel).removeClass('ui-panel-closed').addClass('ui-panel-open');
              $(this.layer).addClass('show');
            }
            if (to == -1 || force == -1) {
              $(this.panel).addClass('ui-panel-closed').removeClass('ui-panel-open');
              $(this.layer).removeClass('show');
            }
          },
          run: function () {
            console.log('Closing panel', this.panel, ' of ', this);
            this.toggle(-1);
          },
        }
        this.widgets[widgetid].init();
      }
    },
    prompt: function () {
      var widgetid = this.getwidgetid();
      this.widgets[widgetid] = {
        widgetid: widgetid,
        runnable: undefined,
        container: undefined,
        input: undefined,
        /**
         * Creates a nice prompt box
         * @param title Title to show
         * @param text Text to show
         * @param def Default value of input
         * @param runnable Object containing a 'run'-Method with 1 argument, which is called using the input value
         **/
        create: function (title, text, def, runnable) {
          this.runnable = runnable;
          this.container = $('<div>').addClass('local_eduvidual_prompt prompt_' + this.widgetid);
          var div = $('<div>');
          var header = $('<div>');
          var main = $('<div>');
          var controls = $('<div>');

          if (typeof title !== 'undefined' && title != '') {
            header = header.addClass('header').html(title);
          }
          if (typeof text !== 'undefined' && text != '') {
            main = main.addClass('main').html(text);
          }
          controls = controls.addClass('controls').attr('data-role', 'controlgroup');
          this.input = $('<input>').attr('type', 'text').attr('value', def);
          main.append(this.input);
          var ok = $('<a href="#" data-role="button" onclick="require([\'local_eduvidual/widgets\'], function(WIDGETS) { WIDGETS.widgets[\'' + this.widgetid + '\'].run(); });">').html('ok');
          var cancel = $('<a href="#" data-role="button" onclick="require([\'local_eduvidual/widgets\'], function(WIDGETS) { WIDGETS.widgets[\'' + this.widgetid + '\'].destroy(); });">').html('cancel');
          controls.append([ok, cancel]);
          div.append([header, main, controls]);
          this.container.append(div);
          $('body').append(this.container);
          $(this.input).focus();
        },
        run: function () {
          this.runnable.run(this.input.val());
          this.destroy();
          return false;
        },
        destroy: function () {
          $(this.container).remove();
        },
      }
      return this.widgets[widgetid];
    },
    qrscanner: function () {
      var widgetid = this.getwidgetid();
      this.widgets[widgetid] = {
        widgetid: widgetid,
        runnable: undefined,
        /**
         * starts scanning
         * @param runnable Object containing a 'run'-Method with 1 argument, which is called using the input value
         **/
        scan: function (runnable, endless) {
          if (typeof endless === 'undefined') {
            endless = false;
          }
          this.endless = endless;
          this.runnable = runnable;
          var QRScanner = this;

          STR.get_strings([
            {'key': 'qrscan:cameratoobject', component: 'local_eduvidual'},
          ]).done(function (s) {
              cordova.plugins.barcodeScanner.scan(
                function (result) {
                  //alert('Adding ' + result);
                  if (typeof result.text !== 'undefined' && result.text != '') {
                    QRScanner.runnable.run(result.text);
                  }
                  if (QRScanner.endless && typeof result.text !== 'undefined' && result.text !== '') {
                    QRScanner.scan(QRScanner.runnable, true);
                  }
                },
                function (error) {
                  NOTIFICATION.alert('Scanning Error: ' + error);
                },
                {
                  preferFrontCamera: false, // iOS and Android
                  showFlipCameraButton: true, // iOS and Android
                  showTorchButton: true, // iOS and Android
                  torchOn: true, // Android, launch with the torch switched on (if available)
                  prompt: s[0], // Android
                  resultDisplayDuration: 500, // Android, display scanned text for X ms. 0 suppresses it entirely, default 1500
                  formats: "QR_CODE,PDF_417", // default: all but PDF_417 and RSS_EXPANDED
                  //orientation : "landscape", // Android only (portrait|landscape), default unset so it rotates with the device
                  disableAnimations: true, // iOS
                  disableSuccessBeep: false // iOS
                }
              );
            }
          ).fail(NOTIFICATION.exception);
        },
      };

      return this.widgets[widgetid];
    },

  };
});
