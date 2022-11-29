/**
 * @file
 */
var searchAjaxReq;
(function ($) {
    //alert('a');
    // Drupal.Collapse = Drupal.Collapse || {};.


    // Select all checkboxes when parent checkbox of Facet filter is selected on load.
    onload_select_checboxes('parent-level');
    onload_select_checboxes('child-level');
    $('#facet_filters_library .parent-checkbox > label > input').each(function () {
        select_all_checboxes(this, 'load_event');
    });

    $(".report-result div.report-cover, .report-result .result-title").off().on('click', function (e) {
        e.preventDefault();
        if (typeof $(this).data('target') === typeof undefined) {
            return false;
        }

        var html = '<div class="search-result report-result">' + $(this).parents('.report-result').html() + '</div>';
        $("#reportModal div.modal-content").append(html);
        $("#reportModal div.modal-content").find('.report-cover,.result-title').removeAttr('data-toggle').removeAttr('data-target');
        $("#reportModal div.modal-content").find('.result-description .btn-group.result-title').css('display', 'none');
        $("#reportModal div.modal-content h1.result-title").html($("#reportModal div.modal-content h1.result-title a").html());
        $("#reportModal div.modal-content").find('span.pub-date,p.result-description').removeClass('hide');
        $("#reportModal div.modal-content").find('span.pub-text').addClass('hide');
        $("#reportModal div.modal-content").first().find('p.result-description').insertAfter("#reportModal div.modal-content div.report-result p.result-meta");
        $("#reportModal div.modal-content").first().find(".qualative-content").insertAfter("#reportModal div.modal-content div.report-result").show();
        $.fn.modal.Constructor.prototype.enforceFocus = function () {};
        $("#reportModal div.modal-content .report-result a").removeAttr("style");
        new Clipboard('.copyLink');
    });

    //   copy text from clipboard

//    $(document).on('click', '#copyLink, .copyLink', function (event) {
//        var reporturl = $(this).parents('.result-description').find('.copyLink').attr('data-clipboard-text');
//       // alert(reporturl);
//        //reporturl.select();
//        // document.execCommand("copy");
//        //new Clipboard('.copyLink');
//        let  copy = new Clipboard('#copy-button');
//        try {
//            trackEvent($(this), '', '', window.location.href, 'request-content', reporturl, '', '', '', 'false');
//        } catch (e) {
//            console.warn(e.message);
//        }
//        $(this).closest('.report-result ').find('.copySuccess').removeClass('hide');
//        setTimeout(function () {
//            $('.copySuccess').addClass('hide');
//        }, 10000);
//    });

    $(document).on('click', '#copy-btn, .copyLink', function (event) {
        input = $("#copy-me");
        copyToClipboard();
//        var reporturl = $(this).parents('.result-description').find('.copyLink').attr('data-clipboard-text');
//       // alert(reporturl);
//        //reporturl.select();
//        // document.execCommand("copy");
//        //new Clipboard('.copyLink');
//        let  copy = new Clipboard('#copy-button');
//        try {
//            trackEvent($(this), '', '', window.location.href, 'request-content', reporturl, '', '', '', 'false');
//        } catch (e) {
//            console.warn(e.message);
//        }
        $(this).closest('.report-result ').find('.copySuccess').removeClass('hide');
        setTimeout(function () {
            $('.copySuccess').addClass('hide');
        }, 10000);
    });

//    var copyBtn = $("#copy-btn"),
//            input = $("#copy-me");

    function copyToClipboardFF(text) {
        window.prompt("Copy to clipboard: Ctrl C, Enter", text);
    }

    function copyToClipboard() {
        var success = true,
                range = document.createRange(),
                selection;

        // For IE.
        if (window.clipboardData) {
            window.clipboardData.setData("Text", input.val());
        } else {
            // Create a temporary element off screen.
            var tmpElem = $('<div>');
            tmpElem.css({
                position: "absolute",
                left: "-1000px",
                top: "-1000px",
            });
            // Add the input value to the temp element.
            tmpElem.text(input.val());
            $("body").append(tmpElem);
            // Select temp element.
            range.selectNodeContents(tmpElem.get(0));
            selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            // Lets copy.
            try {
                success = document.execCommand("copy", false, null);
            } catch (e) {
                copyToClipboardFF(input.val());
            }
            if (success) {
                tmpElem.remove();
            }
        }
    }


// for requese access
    $(document).on('click', '.report-result .result-description button', function (e) {
        if ($.trim($(this).text()) == 'Request Access') {

        }
    });

    $('body').on('click', '.ui-widget-overlay', function () {
        $('.ui-dialog-titlebar-close').click()
    })
    $('body').on('click', '.ui-dialog-titlebar-close', function () {
        $('.ui-dialog-titlebar-close').click()
    })


    // search facet filters
    $("#facet_filters_library .apply-facet-filter button").click(function (event) {
        var facet_filters = {};

        // Therapy Area or Disease Filter.
        $.each($("ul.facetapi-facet-field-therapy-area-disease li.checkbox-parent"), function (index, value) {
            var parent_facetId = $(this).find("input[type='checkbox']").attr("id");
            var elementClass = $(this).attr("class").split(' ')[0];
            if ($(this).find("input[type='checkbox']").is(":checked")) {
                facet_filters[elementClass] = parent_facetId;
            } else {
                $.each($("ul.facetapi-facet-field-therapy-area-disease li." + elementClass), function (key, child_value) {
                    if (!($(this).hasClass('checkbox-parent')) && $(this).find("input[type='checkbox']").is(":checked")) {
                        facet_index = elementClass + '_' + key;
                        var child_facetId = $(this).find("input[type='checkbox']").attr("id");
                        facet_filters[facet_index] = child_facetId;
                    }
                });
            }
        });

        // Any Solution (Destination Category) Filter.
        $.each($("#destination_category_facet ul.facet-search li"), function (index, value) {
            if ($(this).find("input[type='checkbox']").is(":checked")) {
                var destnCatg_facetId = $(this).find("input[type='checkbox']").attr("id");
                var category_id = $(this).attr('id');
                facet_filters[category_id] = destnCatg_facetId;
            }
        });

        // Research Type Filter.
        $.each($("#research_type_facet ul.facet-search li"), function (index, value) {
            if ($(this).find("input[type='checkbox']").is(":checked")) {
                var researchType_facetId = $(this).find("input[type='checkbox']").attr("id");
                var type_id = $(this).attr('id');
                facet_filters[type_id] = researchType_facetId;
            }
        });

        var target_path = '/search?category=library';
        target_path = decodeURI(target_path);
        var count = 0;

        $.each(facet_filters, function (index, value) {
            if (value) {
                target_path += "&f[" + count + "]=" + value;
            }
            count++;
        });

        // Add Publication filter parameter.
        $.each($("#publication_date_facet ul li"), function (index, value) {
            if ($(this).hasClass('active') && !($(this).hasClass('any-date'))) {
                var pub_date = $(this).attr('data-pub-date');
                target_path += "&pd=" + pub_date;
            }
        });
        // Redirect to apply the filters at once.
        window.location.href = encodeURI(target_path);
    });


    // feedback form

    $('#consultingButton').click(function () {
        $('#consultingButton').addClass('disabled');
        $('#consultingButton').html('<i class="fa fa-circle-o-notch fa-spin"></i> Sending');
        var checkedValues = $('#consultingCheckboxes input:checked').map(function () {
            return $.trim($(this).closest('label').text());
        }).get();
        var currenturl = encodeURIComponent(window.location.href);
        var pagetitle = $(document).attr('title');
        var callback_method = "feedback-notification";
        $.ajax({
            type: 'POST',
            url: Drupal.url(callback_method),
            async: true,
            cache: false,
            data: 'message=' + checkedValues + '&current_url=' + currenturl + '&page_title=' + pagetitle + '&subject=Consulting Request',
            success: function (result) {
                var response = result;
                $('#consultingForm').hide();
                $('#consultingAdvert .contact-success-message').removeAttr("style");
                $('#consultingButton i').remove();
                $('#consultingButton').html('Send');
                $('#consultingCheckboxes').val('');
            },
            error: function (e) {
                $('#consultingForm .contact-intro-message').hide();
                $('#consultingForm .contact-error-message').show();
                $('#consultingButton').removeClass('disabled');
                $('#consultingButton').html('Send');
                $('#consultingButton i').remove();
                return false;
            }
        });
    });


    $('#consultingCheckboxes .checkbox').click(function () {
        if ($("#consultingCheckboxes input:checkbox:checked").length) {
            $('#consultingButton').removeClass('disabled');
        } else {
            $('#consultingButton').addClass('disabled');
        }
    });

    $('.request-information').click(function () {
        // $('#consultingAdvert .contact-success-message').hide();.
        $('#consultingForm').show();
        $('#consultingForm .contact-error-message').hide();
        $('#consultingCheckboxes').find('input:checkbox').prop('checked', false);
    });

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    function select_all_checboxes(selected_parent, selector_event) {
        var checkbox_identifier = $(selected_parent).parents('li').attr('id');

        if (selected_parent.checked) {
            $('li.' + checkbox_identifier + ' input[type="checkbox"]').each(function () {
                this.checked = true;
            });
        } else {
            $('li.' + checkbox_identifier + ' input[type="checkbox"]').each(function () {
                if (selector_event == 'load_event') {
                    child_facets_selection(this);
                } else if (selector_event == 'click_event') {
                    this.checked = false;
                }
            });
        }
    }

    function pubDate_active_toggle() {
        var pub_date = getParameterByName('pd');
        if (pub_date) {
            $('#publication_date_facet ul li').each(function (index, value) {
                $(this).removeClass('active');
                var pub_date_attr = $(this).attr('data-pub-date');
                if (pub_date === pub_date_attr) {
                    $(this).addClass('active');
                    var active_pd_text = $(this).find('a').text();
                    $('#publication_date_facet .btn-group .btn-block .filter_placeholder').text(active_pd_text);
                    $('#publication_date_facet .btn-group .btn-block').addClass('filtered');
                }
            });
        } else {
            $('#publication_date_facet ul li.any-date').addClass('active');
            $('#publication_date_facet .btn-group .btn-block .filter_placeholder').text('Any Publication Date');
        }
    }

    function therapy_area_selection(clicked_element) {
        var elementClass = $(clicked_element).parents("li").attr("class").split(" ")[0];
        if (!($(clicked_element).parents("li").hasClass('checkbox-parent')) && !($(clicked_element).is(":checked"))) {
            $("ul.facetapi-facet-field-therapy-area-disease li#parent-" + elementClass).find("input[type='checkbox']").prop("checked", false);
        } else if (!($(clicked_element).parents("li").hasClass('checkbox-parent')) && $(clicked_element).is(":checked")) {
            var checked_count = 0;
            $.each($("ul.facetapi-facet-field-therapy-area-disease li:not(.checkbox-parent)." + elementClass), function (index, value) {
                if (!($(this).find("input[type='checkbox']").is(":checked"))) {
                    checked_count++;
                }
            });
            if (checked_count == 0) {
                $("ul.facetapi-facet-field-therapy-area-disease li#parent-" + elementClass).find("input[type='checkbox']").prop("checked", true);
            }
        }
    }

    if ($('#facet_filters_library').length > 0) {
        $.each($("ul.facetapi-facet-field-therapy-area-disease li.checkbox-parent"), function (index, therapy) {
            if ($(this).find("input[type='checkbox']").is(":checked")) {
                var elementClass = $(this).attr("class").split(" ")[0];
                $.each($("ul.facetapi-facet-field-therapy-area-disease li." + elementClass), function (key, diseases) {
                    $(this).find("input[type='checkbox']").prop('checked', true);
                });
            }
        });

        $("ul.facetapi-facet-field-therapy-area-disease li input[type='checkbox']").click(function () {
            therapy_area_selection(this);
        });

        $("ul.facetapi-facet-field-therapy-area-disease li.checkbox-parent input[type='checkbox']").click(function () {
            var elementClass = $(this).parents("li").attr("class").split(" ")[0];
            var that = $(this);
            var element = "ul.facetapi-facet-field-therapy-area-disease li." + elementClass;
            $.each($(element), function (index, value) {
                if (!($(this).hasClass('checkbox-parent'))) {
                    if ($(that).is(":checked")) {
                        $(this).find("input[type='checkbox']").prop('checked', true);
                    } else {
                        $(this).find("input[type='checkbox']").prop('checked', false);
                    }

                }
            });
        });
    }

    pubDate_active_toggle();
    $("#publication_date_facet ul li").click(function (event) {
        $("#publication_date_facet ul li").removeClass('active');
        $(this).addClass('active');
        $('#publication_date_facet .btn-group .btn-block').addClass('filtered');
        var active_pd_text = $(this).find('a').text();
        if ($(this).hasClass('any-date')) {
            active_pd_text = 'Any Publication Date';
            $('#publication_date_facet .btn-group .btn-block').removeClass('filtered');
        }
        $('#publication_date_facet .btn-group .filter_placeholder').text(active_pd_text);
    });

    function onload_select_checboxes(level_item) {


        var current_facet_filters = drupalSettings.insight_platform_search.search_result.current_facet_filters;
        jQuery('.facet-search li').find('input').each(function (key, value) {
            let loopid = jQuery(this).attr('id');
            if (current_facet_filters.includes(loopid))
            {
                this.checked = true;
            }
        })



//        console.log(current_facet_filters);
//        console.log(level_item);
        if (level_item == 'parent-level') {
            $(current_facet_filters).each(function (key, facet_selected) {
                var parent_id = $('input[data-identifier="' + facet_selected + '"]').parents('li').attr('id');
                $('.child-facets li.' + parent_id + ' input[type="checkbox"]').each(function () {
                    this.checked = true;
                });
            });
        } else if (level_item == 'child-level') {
            $(current_facet_filters).each(function (key, facet_selected) {
                var parent_id = jQuery('input[data-identifier="' + facet_selected + '"]').parents('li').attr('id');
                child_facets_selection(jQuery('input[data-identifier="' + facet_selected + '"]'));
            });
        }
    }

    function child_facets_selection(clicked_checkbox) {
        var parent_identifier = $(clicked_checkbox).parents('.category-container').attr('id');
        $('#' + parent_identifier + ' .child-facets li').each(function () {
            var checkbox_identifier = $(this).attr('class').split(' ')[0],
                    checkedCheckboxes = $('.child-facets li.' + checkbox_identifier + ' input[type="checkbox"]:checked').length,
                    totalCheckboxes = $('.child-facets li.' + checkbox_identifier + ' input[type="checkbox"]').length;
            if (checkedCheckboxes == totalCheckboxes) {
                $('#facet_filters_library li.parent-checkbox#' + checkbox_identifier + ' input[type="checkbox"]').not('#facet_filters_library .child-facets .checkbox input').prop({indeterminate: false, checked: true});
            } else if (checkedCheckboxes > 0) {
                $('#facet_filters_library li.parent-checkbox#' + checkbox_identifier + ' input[type="checkbox"]').not('#facet_filters_library .child-facets .checkbox input').prop({indeterminate: true, checked: false});
            } else {
                $('#facet_filters_library li.parent-checkbox#' + checkbox_identifier + ' input[type="checkbox"]').not('#facet_filters_library .child-facets .checkbox input').prop({indeterminate: false, checked: false});
            }
        });
    }


    // Therapy area/Disease filter - auto select active itmes from dropdown.
    disease_selected_count();
    $('#therapy_area_facet .btn-group .dropdown-toggle .multi-select-clear').click(function (event) {
        event.stopPropagation();
        $.each($("#therapy_area_facet ul.facet-search li"), function (index, value) {
            $(this).find("input[type='checkbox']").prop("checked", false);
        });
        disease_selected_count();
    });

    $("#therapy_area_facet ul.facet-search li").click(function (event) {
        disease_selected_count();
    });

    // Solution filter - auto select active itmes from dropdown.
    solution_selected_count();
    $('#destination_category_facet .btn-group .dropdown-toggle .multi-select-clear').click(function (event) {
        event.stopPropagation();
        $.each($("#destination_category_facet ul.facet-search li"), function (index, value) {
            $(this).find("input[type='checkbox']").prop("checked", false);
        });
        solution_selected_count();
    });

    $("#destination_category_facet ul.facet-search li").click(function (event) {
        solution_selected_count();
    });

    // Research type filter - auto select active itmes from dropdown.
    research_selected_count();
    $('#research_type_facet .btn-group .dropdown-toggle .multi-select-clear').click(function (event) {
        event.stopPropagation();
        $.each($("#research_type_facet ul.facet-search li"), function (index, value) {
            $(this).find("input[type='checkbox']").prop("checked", false);
        });
        research_selected_count();
    });

    $("#research_type_facet ul.facet-search li, #researchType ul.facet-search li").click(function (event) {
        research_selected_count();
    });

    function solution_selected_count() {
        var solutions_slctd_count = $('#destination_category_facet .dropdown-menu li input:checked').length;
        var placeholder_text = 'Any Solution';
        if (solutions_slctd_count == 1) {
            placeholder_text = solutions_slctd_count + ' Solution';
            $('#destination_category_facet .btn-group .dropdown-toggle').addClass('filtered');
        } else if (solutions_slctd_count > 1) {
            placeholder_text = solutions_slctd_count + ' Solutions';
            $('#destination_category_facet .btn-group .dropdown-toggle').addClass('filtered');
        } else {
            $('#destination_category_facet .btn-group .dropdown-toggle').removeClass('filtered');
        }
        $('#destination_category_facet .btn-group .dropdown-toggle .filter_placeholder').text(placeholder_text);
    }


    function research_selected_count() {
        var research_slctd_count = $('#research_type_facet .dropdown-menu li input:checked').length;

        var placeholder_text = 'Any Research Type';
        $("#popoverAlert").show();
        if (research_slctd_count == 1) {
            placeholder_text = research_slctd_count + ' Research Type';
            $('#research_type_facet .btn-group .dropdown-toggle').addClass('filtered');
            if ($("#research_type_facet .dropdown-menu li input:checked").attr("id") == "field_report_research_type:7855") {
                $("#popoverAlert").hide();
            }
        } else if (research_slctd_count > 1) {
            placeholder_text = research_slctd_count + ' Research Types';
            $('#research_type_facet .btn-group .dropdown-toggle').addClass('filtered');
            $("#popoverAlert").removeClass("hide");
        } else {
            $('#research_type_facet .btn-group .dropdown-toggle').removeClass('filtered');
        }
        $('#research_type_facet .btn-group .dropdown-toggle .filter_placeholder').text(placeholder_text);

    }

    // Change the active class for Publication facet filter.
    pubDate_active_toggle();
    $("#publication_date_facet ul li").click(function (event) {
        $("#publication_date_facet ul li").removeClass('active');
        $(this).addClass('active');
        $('#publication_date_facet .btn-group .btn-block').addClass('filtered');
        var active_pd_text = $(this).find('a').text();
        if ($(this).hasClass('any-date')) {
            active_pd_text = 'Any Publication Date';
            $('#publication_date_facet .btn-group .btn-block').removeClass('filtered');
        }
        $('#publication_date_facet .btn-group .filter_placeholder').text(active_pd_text);
    });


    function disease_selected_count() {
//        var digital_search = Drupal.settings.insight_platform_search.search_result.digital_search;
//        if (!digital_search) {
        var disease_slctd_count = $('#therapy_area_facet ul#ta-selections li:not(.checkbox-parent) input:checked').length;
        var placeholder_text = ' Any Therapy Area or Disease ';

        if (disease_slctd_count == 1) {
            placeholder_text = disease_slctd_count + ' Disease';
            $('#therapy_area_facet .btn-group .dropdown-toggle').addClass('filtered');
        } else if (disease_slctd_count > 1) {
            placeholder_text = disease_slctd_count + ' Diseases';
            $('#therapy_area_facet .btn-group .dropdown-toggle').addClass('filtered');
        } else {
            $('#therapy_area_facet .btn-group .dropdown-toggle').removeClass('filtered');
        }
        $('#therapy_area_facet .btn-group .dropdown-toggle .filter_placeholder').text(placeholder_text);
//        }
    }


    Drupal.behaviors.insightSearch = {

        attach: function (context, settings) {

            // Lazy Loading.
            _iplazyloding();




            $(document).on('click', '.search-result-new div.result-meta span.matches:not(.clicked)', function (event) {

                $(this).addClass("clicked");
                if ($(this).hasClass('viewed')) {
                    return;
                }
                var base = $(this).data('id');
                var report_type = $(this).data('report');
                var element = $(this);
                currentURL = window.location.search;
                currentURL = currentURL.replace(/\+$/, '');
                var appendTo = $(this).parent().parent().find('.chapter-matches ul.list-chapters');
                var loaderText = '<li class="spinner"><span><i class="fa fa-circle-o-notch fa-spin fa-1x fa-fw"></i>Loading...</span></li>'
                searchCancelRequestIfRunning();
                searchAjaxReq = $.ajax({
                    url: 'insight-search-more-result/nojs' + currentURL + '&rid=' + base + '&type=' + report_type,
                    beforeSend: function (jqXHR, settings) {
                        $(loaderText).appendTo(appendTo);
                    },
                    success: function (response, status) {
                        $('li.spinner').remove();
                        element.addClass('viewed');
                        $(response.data).appendTo(appendTo);
                    }
                });

                if ($(this).hasClass('viewed')) {
                    $('.search-result-new div.result-meta span[data-id="' + base + '"]').off(event);
                }
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
                if (name === 'ownership') {
                    if ($(this).val() == 'information') {
                        $('#researchType input[name="research_type"]').prop("disabled", true);
                    }
                }
                var searchURL = window.location.search;
                var appendTo = $('#searchResults').find('.loader');
                $(appendTo).css('height', height);
                var loaderText = '<p><i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i></p><h2>Loading</h2>';
                var currentURL = searchURL.split('&');
                if ($("input[name=research_type]:checked").val() == 'proprietary research') {
                    _disabletabs();
                } else {
                    $(this).prop("disabled", false);
                }
                query_param = _createqueryparam();
                var d7 = formatLocalDate();
                var url_append = '';
                if (currentURL[1] == 'spellcheck=0') {
                    url_append = currentURL[0] + '&' + currentURL[1] + '&' + query_param;
                } else {
                    url_append = currentURL[0] + '&' + query_param;
                }
                var newurl = window.location.href.split('?')[0] + url_append;
                setsearchcriteriajson('search-tab', newurl, query_param, d7);
                var last1 = query_param.substr(-1);
                if (last1 == '&') {
                    query_param = query_param.slice(0, -1);
                }
                searchCancelRequestIfRunning()
                searchAjaxReq = $.ajax({
                    url: location.pathname + '/nojs/' + url_append,
                    beforeSend: function (jqXHR, settings) {
                        if (!$('body').hasClass('show-loader')) {
                            $(loaderText).appendTo(appendTo);
                            $('body').addClass('show-loader');
                        }
                    },
                    success: function (response, status) {
                        $(loaderText).remove();
                        $('body').removeClass('show-loader');
                        $('#searchResults').replaceWith(response.data);
                        history.replaceState(null, null, url_append);
                        _iplazyloding();
                    }
                });

                // if($(this).hasClass('active')) {
                // $('.search-tabs-shadow li a[value="'+tab_value+'"]').off(event);
                // }
                // for disabling guided tour on other tabs.
                if ($(this).text() == ' Reports') {
                    var topic_type = window.location.pathname.split('/')[1];
                    var usercookie = topic_type + Drupal.settings.insight_guided_tour.user_uuid;
                    if (!(document.cookie && document.cookie.indexOf(usercookie + '=1') == -1)) {
                        $(".beta-notice").remove();
                    } else {
                        $(".beta-notice").show();
                    }
                    $(document).on('click', 'button[data-role="end"] ', function () {
                        close_tour(usercookie);
                    });
                } else {
                    $('.tour-tour').hide();
                    $(".beta-notice").hide();

                }
            });


        }
    };

   _iplazyloding = function () {
        $('.lazyloader-search').jscroll({
            autoTrigger: true,
            nextSelector: 'a.jscroll-next',
            loadingHtml: '<article class="search-result-new search-loading"><p><i class="fa fa-circle-o-notch fa-spin"></i> &nbsp;Loading More Reports</p></article>',
            contentSelector: '.lazyloader-search',
            callback: function() {
                Drupal.ajax.bindAjaxLinks(document.body);
            }
        });
    }
    


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

    _createqueryparam = function () {
        var query_param = ''
        $('.category-container li').each(function () {
            if ($(this).find("input[type='checkbox']").is(":checked") || $(this).find("input[type='radio']").is(":checked")) {
                var name = $(this).find('input').attr('name');
                if (name === 'sort') {
                    var tab_value = $(this).find('input').attr('value');

                } else {
                    tab_value = $(this).find('input').val();

                }
                query_param += name + '=' + tab_value + '&';
            }

        });
        $('.search-tabs-shadow li a').each(function () {
            var name = $(this).attr('name');
            var tab_value = $(this).attr('value');

            if ($(this).hasClass("active") && $(this).parents("li").hasClass("active")) {
                if (tab_value !== '') {
                    query_param += name + '=' + tab_value + '&';
                }

            }

        });
        $('.search_sort_tabs a').each(function () {
            var name = $(this).attr('name');
            var tab_value = $(this).attr('value');

            if ($(this).parents("li").hasClass("active")) {
                query_param += name + '=' + tab_value;
            }

        });
        return query_param;
    }

 $('.marketing-buttons .btn-primary, .result-description .marketing-buttons').on('click', function (event) {
        $('.contact-success-message').show();
        var search_page = $("body").hasClass("page-search").toString();
        var currenturl = window.location.href;
        var pagetitle = $(document).attr('title');
        if (search_page == 'true') {
          pagetitle = $(this).closest('.search-result').find('.result-title').first().text();
          $(this).closest('.search-result').find('.marketing-buttons .btn-primary').text(" We will contact you shortly");
          $(this).closest('.search-result').find('.marketing-buttons .btn-primary').attr("disabled", "");
        }
        else {
          $(this).closest('.search-result').find('.marketing-buttons .btn-primary').text(" We will contact you shortly");
          $(this).closest('.search-result').find('.marketing-buttons .btn-primary').attr("disabled", "");
        }
        var subject = 'Insights Platform Access Request';
        var message = 'User requested for access';
        var callback_method = "salesforce-api-call";
        $.ajax({
          type: 'POST',
          url: Drupal.url(callback_method),
          async: true,
          cache: false,
          data: 'message=' + encodeURIComponent(message) + '&current_url=' + encodeURIComponent(currenturl) + '&page_title=' + encodeURIComponent(pagetitle) + '&subject=' + encodeURIComponent(subject)+ '&request=' + encodeURIComponent('requestpricing'),
          success: function (result) {
            if (result.code == 200 && result.status.toLowerCase() == 'success') {
            }
            else {
              $('.contact-success-message').hide();
              $('.contact-error-message').show();
              $(this).removeAttr("disabled");
              $(this).text("Contact us");
              return false;
            }
          },
          error: function (e) {
            //$('.marketing-buttons').html();
          }
        });
      });





    /*Drupal.Collapse.test = function() {
     $('.collapse').collapse();
     return false;
     }*/
})(jQuery);

/**
 * Function to check if AJAX call is running already and if so abort it
 */
function searchCancelRequestIfRunning() {
    if (searchAjaxReq !== undefined && searchAjaxReq.readyState !== 4) {
        searchAjaxReq.abort();
    }
}
