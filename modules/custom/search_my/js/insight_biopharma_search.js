var searchAjaxReq;
(function ($, Drupal) {
    Drupal.behaviors.insight_biopharma_search = {
        attach: function (context, settings) {
            $('body').once().on('click', '.result-meta > .matches', function (e) {
                var current_working_object = $(this);
                    var urlParams = new URLSearchParams(window.location.search);
                    let query_string = urlParams.get('query');
                    let report_id = $(this).attr('data-id');
                    var more_result_ajax_url = '';
                    if (!$(this).hasClass('viewed')) {
                        if($('.search-main-results').attr('search-type') == 'biopharmasearch'){
                             more_result_ajax_url = 'insight-search-more-result';
                        }
                        if($('.search-main-results').attr('search-type') == 'medtechsearch'){
                            more_result_ajax_url = 'insight-search-more-result-medtech';
                        }
                        $.ajax({
                            url: drupalSettings.path.baseUrl + more_result_ajax_url,
                            type: "get", //send it through get method
                            beforeSend: function () {
                                jQuery('#result-' + report_id).html('<ul class="list-unstyled list-chapters"><li class="spinner"><span><i class="fa fa-circle-o-notch fa-spin fa-1x fa-fw"></i>Loading...</span></li></ul>');
                            },
                            data: {
                                query: query_string,
                                rid: report_id,
                                type: 'container'
                            },
                            success: function (response) {
                                jQuery('#result-' + report_id).html(response);
                                current_working_object.addClass('viewed');
                            },
                            error: function (xhr) {
                                //Do Something to handle error
                            }
                        });
                    }

               // }
            });

            $('#biopharma-search-filter-form').once().submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                
                jQuery('#searchResults').addClass('shadow-box');
                $(this).find('ul.excluded input[type="checkbox"]').prop("disabled", true);
                var data = $(this).serializeArray();
                let basepath = settings.path.currentPath
                url = '/' + basepath + '?' + $.trim($.param(data));
                $('.search-main-results .loader').addClass('show-loader');
                var urlParams = new URLSearchParams(window.location.search);
                let query_string = urlParams.get('tabs');
                if (!query_string) {
                    query_string = 'container';
                }
                var _final_selector_biopharma = '';
                _final_selector_biopharma = 'searchResults';
                jQuery('#searchResults').html("<div class=\"loader text-center\" style=\"opacity:1;display:block;padding-top:0px;padding-bottom:0px;position:relative;\"><p><i class=\"fa fa-circle-o-notch fa-spin fa-3x fa-fw\"></i></p><h2>Loading</h2></div>");
                let obj = {url: url, selector: _final_selector_biopharma}
                let request = Drupal.ajax(obj);
                request.commands.insert = function (ajax, response, status) {
                    var additional_dataset = '';
                    if(drupalSettings.totalItems > 0){
                     additional_dataset = '<div class="search-results-details"><p class="pull-left">Matches found in <span id="biopharma-totalcount">'+drupalSettings.totalItems+'</span> Reports</p><div class="sort-btn pull-right"><span class="btn-label">Sorted by</span><div class="btn-group title-button"><button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"> <span id="sortBy">'+drupalSettings.sorttypelabel+'</span> <span class="caret"></span></button><ul class="dropdown-menu pull-right search_sort_tabs" role="menu"><li class="'+((drupalSettings.sort_type == 'score') ? 'active': '')+'" name="sort" id="score"><a class="search_score_tabs" name="sort" value="score" data-key="score" href="javascript:void(0);">Relevance</a></li><li class="'+((drupalSettings.sort_type == 'date') ? 'active': '')+'" name="sort" id="date"><a class="search_score_tabs" name="sort" value="date" data-key="date" href="javascript:void(0);">Publication Date</a></li></ul></div></div><div class="clearfix"></div></div>';
                    } else {
                     additional_dataset = '';    
                     jQuery('#searchResults').removeClass('shadow-box');
                    }
                    
                    $('#' + ajax.selector).html(additional_dataset + response.data);
                    //$('#biopharma-totalcount').text(drupalSettings.totalItems);
                    $('.search-main-results .loader').removeClass('show-loader');
                    $('#biopharma-search-filter-form').find('ul.excluded input[type="checkbox"]').prop("disabled", false);
                    window.history.replaceState(null, null, url);
                    var settings = response.settings || ajax.settings || drupalSettings;
                    settings.page = 0;
                    Drupal.attachBehaviors(document.getElementById(ajax.selector), settings);
                };
                request.execute();

            });

  var filter_obj = {};
      $(document).on('click', ".search_filter, .search_tabs, .search_score_tabs", function (event) {

        $('.category-container').find('ul.list-unstyled li input').prop("disabled", false);
        $('.search-main-results').find('.search-tabs-shadow li').removeClass("disabled");
        $('.search-main-results').find('.search-tabs-shadow li').removeClass("inactiveLink");
        $('.search-main-results').find('.search-tabs-shadow li a').prop("disabled", false);
        $(this).parents('.category-container').find('ul.list-unstyled li').removeClass('active');
        $(this).parents('ul.list-unstyled li').addClass('active');
        $(this).parents('.search_sort_tabs').find('li').removeClass('active');
        $(this).parents('ul.search_sort_tabs li').addClass('active');

        // $(this).prop("disabled", true);.
        var height = $(document).height();
        $(this).addClass('active');
        var query_param = '';

        var name = $(this).attr('name');
        if (name === 'tabs' || name === 'sort') {
          var tab_value = $(this).attr('value');
        } else {
          tab_value = $(this).val();
        }
        if (name === 'tabs' && tab_value !== '') {
          $('#researchType .proprietary').prop("disabled", true);
        }
        //adding condition to disable research type filters when information is selected
        if(name === 'ownership'){
          if($(this).val() == 'information'){
           $('#researchType input[name="research_type"]').prop("disabled", true);
          }
        }
      
        if ($("input[name=research_type]:checked").val() == 'proprietary research') {
          _disabletabs();
        } else {
          $(this).prop("disabled", false);
        }
     
      });
     




            $('div.sort-btn ul li a', context).once().click(function (e) {
                let key = $(this).attr('data-key');
                var urlParams = new URLSearchParams(window.location.search);
                let query = urlParams.get('query');
                $('#biopharma-search-filter-form input[name="sort"]').val(key);
                $('#biopharma-search-filter-form input[name="query"]').val(query);
                $('#biopharma-search-filter-form').submit();
                // }
            });

            jQuery("#biopharma-search-filter-form").find('.search_filter').once().click(function () {
                var urlParams = new URLSearchParams(window.location.search);
                let query = urlParams.get('query');
                $('#biopharma-search-filter-form input[name="search"]').val(query);
                $('#biopharma-search-filter-form').submit();
            });


            jQuery('.search-main-results').find('ul.nav-tabs li a').once().click(function (e) {
                let current_tab = jQuery(this).attr('data');
                if (typeof current_tab !== typeof undefined && current_tab !== false) {
                 $('#biopharma-search-filter-form input[name="tabs"]').val(current_tab);
                 $('#biopharma-search-filter-form').submit();
                }
            });


            $('div.nav-tabs ul li a', context).once().click(function (e) { //alert("jhjhj");
                //if (!$(this).parent().hasClass('active')) {
                let key = $(this).attr('data-key');
                var urlParams = new URLSearchParams(window.location.search);
                let query = urlParams.get('query');
                $('#biopharma-search-filter-form input[name="sort"]').val(key);
                $('#biopharma-search-filter-form input[name="query"]').val(query);
                $('#biopharma-search-filter-form').submit();
                // }
            });




            var isWorking = 0;
            $(window).once().scroll(function () {
                if (isWorking == 0) {
                    // End of the document reached?

                    var _final_selector_biopharma = '';
                    var urlParams = new URLSearchParams(window.location.search);
                    let query_string = urlParams.get('tabs');
                    
                     var total_no_of_count_dom = jQuery('.search-result-new').length;
                     var total_item_server =  drupalSettings.totalItems;

                    if(total_no_of_count_dom == total_item_server){
                       // console.log("hi");
                        return;
                    }
                    if (!query_string) {
                        query_string = 'container';
                    }
                    _final_selector_biopharma = 'searchResults';

                    if ($(document).height() - $(this).height() == $(this).scrollTop() && $('#' + _final_selector_biopharma + ' .shadow-box .search-result-new').length < settings.totalItems) {

                        isWorking = 1;
                        
                        let data = $('#biopharma-search-filter-form').serializeArray();
                        let basepath = settings.path.currentPath
                        settings.page = settings.page || 1;
                        data.push({name: 'page', value: settings.page++});
                        let url = '/' + basepath + '?' + $.trim($.param(data));

                        let obj = {
                            url: url,
                            selector: '#' + _final_selector_biopharma,
                            progress: {
                                type: 'loader',
                                message: Drupal.t('Loading more items...')
                            },
                            element: '#' + _final_selector_biopharma + ' .search-result-new:last',
                        }

                        searchAjaxReq = Drupal.ajax(obj);
                        searchAjaxReq.commands.insert = function (ajax, response, status) {
                            $(ajax.selector).append(response.data);
                        };
                        searchAjaxReq.setProgressIndicatorLoader = function () {
                            this.progress.element = '#' + _final_selector_biopharma + ' .loader';
                            $(this.element).after("<div class=\"loader text-center\" style=\"opacity:1;display:block;padding-top:0px;padding-bottom:0px;position:relative;\"><p><i class=\"fa fa-circle-o-notch fa-spin fa-3x fa-fw\"></i></p><h2>Loading</h2></div>");
                        };
                        searchCancelRequestIfRunning();
                        searchAjaxReq.execute();

                        setTimeout(function () {
                            isWorking = 0
                        }, 2000);
                    }
                }
            });



        }
    };

  _disabletabs = function (tab_value) {
    $("ul.list-unstyled li input[name='ownership']").each(function () {
      if ($(this).attr('value') !== 'owned') {
        $(this).prop("disabled", true);
        $(this).removeAttr("checked");
        $(this).parents('ul.list-unstyled li').removeClass('active');
        // $(this).off(event);
      } else {
        $(this).parents('ul.list-unstyled li').addClass('active');
        $(this).prop('checked', true);
      }
    });
    $(".search-main-results  .search-tabs-shadow li .search_tabs").each(function () {
      if ($(this).attr('value') !== '') {
        $(this).prop("disabled", true);
        $(this).parents('li').addClass("disabled");
        $(this).parents('li').removeClass('active');
      } else {
        $(this).prop("disabled", false);
        $(this).addClass('active');
        $(this).parents('li').addClass('active');
      }
    });
  }

})(jQuery, Drupal);



/**
 * Function to check if AJAX call is running already and if so abort it
 */
function searchCancelRequestIfRunning() {
    if (searchAjaxReq !== undefined && searchAjaxReq.readyState !== 4) {
        return false;
    }
}