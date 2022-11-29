(function ($, Drupal,drupalSettings) {
  'use strict';
  Drupal.behaviors.tocSearch = {
    attach: function (context, settings) {


      $('body', context).once().each(function () {
        var chapter_url = window.location.pathname.split('/').slice(1,4).join('/');
        var retrievedObject = JSON.parse(localStorage.getItem('user_toc_search_cookie'));
        $('.toc-search-error-message').hide();
        if(retrievedObject){
          if(chapter_url == retrievedObject.chapter_url){
            var toc_search_keyword = retrievedObject.toc_search_keyword;
            $("#searchtoc").val(toc_search_keyword);
            $('form.toc-search .clearer').show();
            var report_nids = [];
            $(".toc-panel .content .toc-divider").each(function () {
              report_nids.push($(this).parent().attr('id'));
            });
            get_toc_match_counts(toc_search_keyword,report_nids);
          } else {
            localStorage.removeItem('user_toc_search_cookie');
          }
        }
      });


      $('form.toc-search').once().on('submit', function (e) {
        e.preventDefault();
        get_toc_match_counts();

      });

      $('#search_btn').once().on('click', function (e) {
        e.preventDefault();
        get_toc_match_counts();

      });


      // for search toc close button.
      $(document).on('click', 'form.toc-search .clearer', function (e) {
        // remove the local storage object  and counts
        $('span.highlight').contents().unwrap();
        $('span.search-count').remove();
        localStorage.removeItem('user_toc_search_cookie');
        $('.toc-search-error-message').hide();
      });

      function findandHighlight(thisvar, toc_search_keyword) {
        var src_str = thisvar.html();
        toc_search_keyword = toc_search_keyword.replace(/(\s+)/,"(<[^>]+>)*$1(<[^>]+>)*");
        var pattern = new RegExp("(?<!</?[^>]*|&[^;]*)("+toc_search_keyword+")", "gi");

        src_str = src_str.replace(pattern, "<span class='highlight'>$1</span>");
        if((src_str.match(pattern) || []).length > 0) {
          thisvar.html(src_str);
        }
      }

      function get_toc_match_counts() {

        //remove the counts
        $('span.search-count').remove();
        //remove the highlighted keywords.
        $('span.highlight').contents().unwrap();

        // pass the searched keyword here.
        let toc_search_keyword = $("#searchtoc").val().toLowerCase();

        let toc_search_keyword_length = toc_search_keyword.split(" ").length;

        if (toc_search_keyword_length > 5 || toc_search_keyword.length < 3) {
          if (toc_search_keyword_length > 5) {
            $(".toc-search-error-message").html('The searched text should be less than 6 words.').show();
          } else if (toc_search_keyword.length < 3) {
            $(".toc-search-error-message").html('Minimum of 3 characters is required.').show();
          }
        } else {

          var chapter_url = window.location.pathname.split('/').slice(1, 4).join('/');
          //set the cookie for user. - toc_search-chapter_url-user_uid-=1
          var user_toc_search_cookie = { 'type': 'toc_search', 'chapter_url': chapter_url, 'toc_search_keyword': toc_search_keyword};
          localStorage.setItem('user_toc_search_cookie', JSON.stringify(user_toc_search_cookie));
          var retrievedObject = localStorage.getItem('user_toc_search_cookie');
          var report_nids = [];
          $(".toc-panel .content .toc-divider").each(function () {
            report_nids.push($(this).parent().attr('id'));
          });
          var callback_method = "toc-search";
          $.ajax({
            type: 'POST',
            url: Drupal.url(callback_method),
            dataType: "json",
            data:  JSON.stringify({'search_keyword':toc_search_keyword,'report_nids': report_nids, 'bunit': $('#toc-bunit').val()}),
            success: function (response, status) {

              $(".toc-search-error-message").html('').hide();
              if(_.isEmpty(response)) {
                $(".toc-search-error-message").html('No results were found').show();
                return false;
              }
              $.each(response, function(element, index) {
                if( $('#'+element + ' :first').hasClass('sub-plus-icon') ){
                  $('#'+element + ' .sub-plus-icon').next().append("<span class='search-count csg-search-count'>"+index+"</span>");
                } else {
                  $('#'+element + ' :first').append("<span class='search-count csg-search-count'>"+index+"</span>");
                }
              });

              $(".macci.report-section[data^='node-']").each(function () {
                findandHighlight($(this), toc_search_keyword);
              });

              $(".region-content .content .content div[id^='node-']").each(function( index ) {
                findandHighlight($(this), toc_search_keyword);
              });

            }
          });
        }

      };



      // console.log('hello');
    }
  };
})(jQuery, Drupal, drupalSettings);
