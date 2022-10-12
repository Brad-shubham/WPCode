(function ($) {
  'use strict';

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */
  var loopCount = 0;
  var mainAction = '';
  var currentElem = null;
  $(function () {
    console.log('initiated...')
    $(document).on('click', '.sync-btn', function (e) {
      e.preventDefault()
      const elm = $(this);
      currentElem = elm
      loopCount = 0
      $('#response-error').hide();
      $(`.pb-run`).css('width', '1%');
      $('.pb-run').html('1%')
      elm.prop("disabled", true);
      $('.sync-opt-msg').remove()
      const action = elm.data('action')
      const nonce = elm.data('nonce')
      const opt = $('input[name="sync-opt"]:checked').val()
      var data = {}
      console.log(action, nonce, opt)
      if (opt === 'range') {
        var rangeFrom = $('#fromClm').val()
        var rangeTo = $('#toClm').val()
        data = {'rangeFrom': rangeFrom, 'rangeTo': rangeTo}
      } else {
        var specific = $('#syncSpec').val()
        data = {'specific': specific}
      }

      console.log(data)
      $.post(WCGoogleSync.ajaxurl, {
        'action': 'syncAction',
        'subAction': action,
        'nonce': nonce,
        'opt': opt,
        'param': data
      }, function (response) {
        $("#wcgs_progressbar").show();
        const {action, column, status, chunks, size, error} = response;
        if (error === undefined) {
          if (action === 'success') {
            $(`.pb-run`).css('width', '100%');
            $("#wcgs_progressbar").hide()
            currentElem.prop("disabled", false);
            console.log('SYNC OPERATION COMPLETED SUCCESSFULLY !!');
            $("<p class='sync-opt-msg'>SYNC OPERATION COMPLETED SUCCESSFULLY !!</p>").insertAfter("#wcgs_progressbar");
          } else {
            $(`.pb-run`).css('width', '1%');
            mainAction = action
            syncDataIntoChunks(size, chunks[loopCount], chunks, column, action, nonce);
          }
        } else {
          $("#wcgs_progressbar").hide()
          console.log(error)
          $('#response-error').show().html('<pre>' + error + '</pre>')
          elm.prop("disabled", false);
        }

      })
    })


    $(document).on('click', 'input[name="sync-opt"]', function () {
      const opt = $(this).data('opt')
      $('.sync-elem').hide()
      $(opt).show()
    })

    // custom tooltip script
    $('[data-toggle="tooltip"]').hover(function () {
      $(this).find('span').remove()
      const topheight = $(this).offset().top;
      const windowHeight = $(window).scrollTop();
      const message = $(this).attr('data-tip');
      if ((windowHeight + 205) >= topheight) {
        $(this).append(' <span class="tooltip-bottom">' + message + '</span>')
      } else {
        $(this).append(' <span class="tooltip-top">' + message + '</span>')
      }
    })
  });

  function syncDataIntoChunks(size, chunk, chunks, column, subAction, nonce) {
    const data = {
      'action': 'wooChunkAction',
      'subAction': subAction,
      'chunk': chunk,
      'columns': column,
      'nonce': nonce,
    };
    jQuery.post(WCGoogleSync.ajaxurl, data, function (response) {
      console.log(response, loopCount, size)
      const {error} = response;
      if (error === undefined) {
        loopCount++
        if (loopCount === size) {
          console.log('All done..')
        } else {
          setTimeout(function () {
            syncDataIntoChunks(size, chunks[loopCount], chunks, column, mainAction, nonce);
          }, 2000)
        }
        var run = ((loopCount) / size) * 100;
        $('.pb-run').css('width', `${Math.round(run)}%`);
        $('.pb-run').html(Math.round(run) + '%');
        if (loopCount == size) {
          $("#wcgs_progressbar").hide()
          currentElem.prop("disabled", false);
          console.log('SYNC OPERATION COMPLETED SUCCESSFULLY !!');
          $("<p class='sync-opt-msg'>SYNC OPERATION COMPLETED SUCCESSFULLY !!</p>").insertAfter("#wcgs_progressbar");
        }
      } else {
        $("#wcgs_progressbar").hide()
        console.log(error)
        $('#response-error').show().html('<pre>' + error + '</pre>')
        currentElem.prop("disabled", false);
      }
    }, 'json');
  }


  /**
   * select2 custom implementation
   */
 jQuery(document).ready(function (){
   jQuery('#wc_settings_tab_meta_keys').select2({
     minimumResultsForSearch: Infinity,
     dropdownCssClass: "gform-settings-field__select-enhanced-container"
   })
 })


})(jQuery);

// start syncing data into chunks

