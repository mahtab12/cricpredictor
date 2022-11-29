/**
 * @file
 */
var searchAjaxReq;
(function ($) {
    // Drupal.Collapse = Drupal.Collapse || {};.
    $('#facet_filters_library .parent-checkbox > label > input').click(function (event) {
        select_all_checboxes(this, 'click_event');
    });




    $('.product-dropdown .btn-update').click(function (e) {
        var $form = $(this).closest('.product-dropdown').find('li.checkbox > label > :checkbox:checked').not('.parent-checkbox > label > :checkbox');
        var noChecks = $(this).closest('.product-dropdown').find('li.checkbox > label > :checkbox:checked').not('.parent-checkbox > label > :checkbox').length;
        var noParentChecks = $(this).closest('.product-dropdown').find('.parent-checkbox > label > :checkbox:checked').length;
        var numberOfLoneParents = $(this).closest('.product-dropdown').find('.main-parent > label > :checkbox:checked').length;

        var parentChecked = $(this).closest('.product-dropdown').find('.parent-checkbox.active').length;
        var loneParentChecked = $(this).closest('.product-dropdown').find('.main-parent.active').length;
        var totalCheckboxes = $(this).closest('.product-dropdown').find('.checkbox').not('.selectall-checkbox,.children-checkbox').length;
        var checkTotalStatus = loneParentChecked + parentChecked;

        if (noParentChecks === 1 && numberOfLoneParents < 1) {
            var parentName = $(this).closest('.btn-group').find('.parent-checkbox > label > :checkbox:checked').val();
            $(this).closest('.btn-group').find('.btn').addClass('filtered');
            $(this).closest('.btn-group').find('.btn-text').text(parentName);
        } else if (noChecks === 1) {
            var name = $(this).closest('.btn-group').find('li.checkbox > label > :checkbox:checked').not('.parent-checkbox > label > :checkbox').val();
            $(this).closest('.btn-group').find('.btn').addClass('filtered');
            $(this).closest('.btn-group').find('.btn-text').text(name);
        } else if (noChecks === 0) {
            $(this).closest('.btn-group').find('.btn').removeClass('filtered');
            $(this).closest('.btn-group').find('.btn-text').text('Choose a Solution Type');
        } else {
            $(this).closest('.btn-group').find('.btn').addClass('filtered');
            if (checkTotalStatus === totalCheckboxes) {
                $(this).closest('.btn-group').find('.btn-text').text('Any Solution Type');
            } else {
                $(this).closest('.btn-group').find('.btn-text').text(noChecks + ' Solutions');
            }
        }
        $("body").trigger("click");

        biopharmaSitemapAjaxRequest($form);
    });
    
    // remove date filter
    
    $("#facet_filters_library #publicationDate .remove-list .remove-filter").click(function (event) {
        event.preventDefault();
        $('#any_date').prop('checked', true);
        $('#any_date').trigger('click');
    });

    if (drupalSettings.insight_platform_search.search_result.digital_search) {
        // Publication date redirect filter.


        $("#facet_filters_library #publicationDate li.checkbox input[type='radio']").click(function () {
            var pub_date_url = $(this).attr('data-pubdate-href');
            if (typeof (pub_date_url) != 'undefined') {
                window.location.href = pub_date_url;
            }
        });

        // Deslect filters.
        $('#facet_filters_library').on('click', '.dropdown-menu .dropdown-footer .btn-deselect', function (e) {
            e.stopPropagation();
            var parent_identifier = $(this).attr("data-parent");
            if (typeof parent_identifier == 'undefined') {
                parent_identifier = $(this).parents('ul').attr("data-parent");
            }
            $('#' + parent_identifier + ' .dropdown-menu').find('.facet-search li input[type="checkbox"]').prop('checked', false);
            // Remove added filters from left Panel.
            $('#' + parent_identifier + ' .facet-search li.parent-checkbox').each(function (index, value) {
                var input_element = $(this).find('input[type="checkbox"]');
                append_selected_elements(input_element, 'parent-item');
            });
            $('#' + parent_identifier + ' .facet-search li.checkbox-parent').each(function (index, value) {
                var input_element = $(this).find('input[type="checkbox"]');
                append_selected_elements(input_element, 'therapy-area-parent');
            });
            $('#' + parent_identifier + ' .facet-search li.single-level').each(function (index, value) {
                var input_element = $(this).find('input[type="checkbox"]');
                append_selected_elements(input_element, 'single-level');
            });
        });

        // Select all checkboxes when parent checkbox of Facet filter is selected on load.
        onload_select_checboxes('parent-level');
        onload_select_checboxes('child-level');
        $('#facet_filters_library .parent-checkbox > label > input').each(function () {
            // Select_all_checboxes(this, 'load_event');.
        });

        // Select all checkboxes when parent checkbox of Facet filter is selected on click.
        $('#facet_filters_library .parent-checkbox > label > input').click(function (event) {
            select_all_checboxes(this, 'click_event');
        });

        // Functionality on selected  child facet checkbox on load.
        $("#facet_filters_library .child-facets .checkbox input").each(function () {
            // Child_facets_selection(this);.
        });

        // Functionality on click of child facet checkbox.
        $("#facet_filters_library .child-facets .checkbox input").on("click", function () {
            child_facets_selection(this);
        });

        // Onclick of remove filter button.
        $("#facet_filters_library").on('click', '.remove-list .added_onclick', function (event) {
            event.stopPropagation();
            var checkboxId = $(this).attr('data-checkboxid');
            $('input[data-identifier="' + checkboxId + '"]').prop('checked', false);
            var parentId = $(this).parents('.category-container').attr('id');
            if ($('#' + parentId + ' li.parent-checkbox').length) {
                child_facets_selection($('input[data-identifier="' + checkboxId + '"]'));
                append_selected_elements($('input[data-identifier="' + checkboxId + '"]'), 'child-item');
            } else {
                append_selected_elements($('input[data-identifier="' + checkboxId + '"]'), 'single-level');
            }
        });

        // On Load Remove filter Button click.
        $("#facet_filters_library .category-container .remove-list .added_onload").click(function () {
            var input_id = $(this).attr('data-checkboxid');
            var parent_id = $(this).parents('ul.remove-list').attr('data-parent');
            $('#' + parent_id + ' input[data-identifier="' + input_id + '"]').prop('checked', false);
            if (parent_id == 'therapy_area_facet') {
                therapy_area_selection($('#' + parent_id + ' input[data-identifier="' + input_id + '"]'));
            } else {
                child_facets_selection($('#' + parent_id + ' input[data-identifier="' + input_id + '"]'));
            }
            removeParams(input_id, parent_id);
            digital_search_update_filters(parent_id);

        });

        // On click of Update Button - Digital Search.
        $("#facet_filters_library .dropdown-footer-buttons .btn-update").click(function (event) {
            var parent_container = $(this).parents('.category-container').attr('id');
            // Select all checkboxes when parent checkbox of Facet filter is selected on click.
            if ($('#' + parent_container + ' .parent-checkbox').length) {
                $('#' + parent_container + ' .parent-checkbox > label > input').each(function () {
                    // Select_all_checboxes(this, 'click_event');.
                    append_selected_elements(this, 'parent-item');
                });
            }

            // Functionality on selected  therapy area/disease.
            if ($('#' + parent_container + ' #ta-selections').length) {
                $('#' + parent_container + ' #ta-selections .checkbox input').each(function () {
                    if ($(this).parents('.checkbox-parent').length) {
                        append_selected_elements(this, 'therapy-area-parent');
                    } else {
                        append_selected_elements(this, 'therapy-area-child');
                    }
                });
            }

            // Functionality on click of child facet checkbox.
            if ($('#' + parent_container + ' .child-facets').length) {
                $('#' + parent_container + ' .child-facets .checkbox input').each(function () {
                    // Child_facets_selection(this);.
                    append_selected_elements(this, 'child-item');
                });
            }

            // Functionality on click of Single level Parent.
            if ($('#' + parent_container + ' li.single-level').length) {
                $('#' + parent_container + ' li.single-level.checkbox input').each(function () {
                    append_selected_elements(this, 'single-level');
                });
            }
            digital_search_update_filters(parent_container);
        });
    }

    function onload_select_checboxes(level_item) {
        var current_facet_filters = drupalSettings.insight_platform_search.search_result.current_facet_filters;
        if (level_item == 'parent-level') {
            $(current_facet_filters).each(function (key, facet_selected) {
                var parent_id = $('input[data-identifier="' + facet_selected + '"]').parents('li').attr('id');
                $('.child-facets li.' + parent_id + ' input[type="checkbox"]').each(function () {
                    this.checked = true;
                });
            });
        } else if (level_item == 'child-level') {
            $(current_facet_filters).each(function (key, facet_selected) {
                var parent_id = $('input[data-identifier="' + facet_selected + '"]').parents('li').attr('id');
                child_facets_selection($('input[data-identifier="' + facet_selected + '"]'));
            });
        }
    }
    function removeParams(sParam, type)
    {

        var checkid = $('#' + sParam);
        checkid.prop("checked", false);

        $("#facet_filters_library .dropdown-footer-buttons .btn-update").click();

        // $("#facet_filters_library .dropdown-footer-buttons .btn-update.study").click();

    }

    //window.location = removeParams(parameter);

    // On click of Update Button - Digital Search.
    $("#facet_filters_library .dropdown-footer-buttons .btn-update").click(function (event) {
        var parent_container = $(this).parents('.category-container').attr('id');
        // Select all checkboxes when parent checkbox of Facet filter is selected on click.
        if ($('#' + parent_container + ' .parent-checkbox').length) {
            $('#' + parent_container + ' .parent-checkbox > label > input').each(function () {
                // Select_all_checboxes(this, 'click_event');.
                append_selected_elements(this, 'parent-item');
            });
        }

        // Functionality on selected  therapy area/disease.
        if ($('#' + parent_container + ' #ta-selections').length) {
            $('#' + parent_container + ' #ta-selections .checkbox input').each(function () {
                if ($(this).parents('.checkbox-parent').length) {
                    append_selected_elements(this, 'therapy-area-parent');
                } else {
                    append_selected_elements(this, 'therapy-area-child');
                }
            });
        }

        // Functionality on click of child facet checkbox.
        if ($('#' + parent_container + ' .child-facets').length) {
            $('#' + parent_container + ' .child-facets .checkbox input').each(function () {
                // Child_facets_selection(this);.
                append_selected_elements(this, 'child-item');
            });
        }

        // Functionality on click of Single level Parent.
        if ($('#' + parent_container + ' li.single-level').length) {
            $('#' + parent_container + ' li.single-level.checkbox input').each(function () {
                append_selected_elements(this, 'single-level');
            });
        }
        digital_search_update_filters(parent_container);
    });

    /*Drupal.Collapse.test = function() {
     $('.collapse').collapse();
     return false;
     }*/
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

    function digital_search_update_filters(parent_id) {
        var facet_filters = {};
        var current_facet_filters = drupalSettings.insight_platform_search.search_result.current_facet_filters;
        // If (origin_type == 'add-filter') {.
        if (parent_id == 'studyName') {
            // StudyName Filter.
            if ($("#studyName ul.facet-search li.parent-checkbox").length) {
                // Functionality if hierarchial taxonomy exists.
                $.each($("#studyName ul.facet-search li.parent-checkbox"), function (index, value) {
                    var elementClass = $(this).attr("id");
                    var parent_facetId = $(this).find("input[type='checkbox']").not('ul.child-facets li input[type="checkbox"]').attr("id");
                    if ($(this).find("input[type='checkbox']").not('ul.child-facets li input[type="checkbox"]').is(":checked")) {
                        facet_filters[elementClass] = parent_facetId;
                    } else {
                        $.each($("#studyName ul.child-facets li." + elementClass), function (key, child_value) {
                            if ($(this).find("input[type='checkbox']").is(":checked")) {
                                facet_index = elementClass + '_' + key;
                                var child_facetId = $(this).find("input[type='checkbox']").attr("id");
                                facet_filters[facet_index] = child_facetId;
                            }
                        });
                    }
                });
            } else {
                // Functionality if single level taxonomy exists.
                $.each($("#studyName ul.facet-search li"), function (index, value) {
                    if ($(this).find("input[type='checkbox']").is(":checked")) {
                        var studyName_facetId = $(this).find("input[type='checkbox']").attr("id");
                        var category_id = $(this).attr('id');
                        facet_filters[category_id] = studyName_facetId;
                    }
                });
            }
        }

        if (parent_id == 'deliverable') {
            // Deliverable Type filter.
            $.each($("#deliverable ul.facet-search li"), function (index, value) {
                if ($(this).find("input[type='checkbox']").is(":checked")) {
                    var deliverable_facetId = $(this).find("input[type='checkbox']").attr("id");
                    var deliverable_id = $(this).attr('id');
                    facet_filters[deliverable_id] = deliverable_facetId;
                }
            });
        }

        if (parent_id == 'researchType') {
            // Research Type Filter (Commented as per the wireframe).
            $.each($("#researchType ul.facet-search li"), function (index, value) {
                if ($(value).find("input[type='checkbox']").is(":checked")) {
                    var researchType_facetId = $(this).find("input[type='checkbox']").attr("id");
                    var type_id = $(this).attr('id');
                    facet_filters[type_id] = researchType_facetId;
                }
            });
        }

        if (parent_id == 'therapy_area_facet') {
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
        }

        if (parent_id == 'geography') {
            $.each($("#geography ul.facet-search li:not(.child-facets li)"), function (index, value) {
                if ($(this).hasClass('parent-checkbox')) {
                    var elementClass = $(this).attr("id");
                    var parent_facetId = $(this).find("input[type='checkbox']").not('ul.child-facets li input[type="checkbox"]').attr("id");

                    if ($(this).find("input[type='checkbox']").not('ul.child-facets li input[type="checkbox"]').is(":checked")) {
                        facet_filters[elementClass] = parent_facetId;
                    } else {
                        $.each($("#geography ul.child-facets li." + elementClass), function (key, child_value) {
                            if ($(this).find("input[type='checkbox']").is(":checked")) {
                                facet_index = elementClass + '_' + key;
                                var child_facetId = $(this).find("input[type='checkbox']").attr("id");
                                facet_filters[facet_index] = child_facetId;
                            }
                        });
                    }
                } else {
                    // Functionality if single level taxonomy exists.
                    // $.each($("#geography ul.facet-search li"), function(index, value) {.
                    if ($(this).find("input[type='checkbox']").is(":checked")) {
                        var geography_facetId = $(this).find("input[type='checkbox']").attr("id");
                        var type_id = $(this).attr('id');
                        facet_filters[type_id] = geography_facetId;
                    }
                    // });.
                }
            });
        }

        // Add existing filters.
        $(current_facet_filters).each(function (existing_index, existing_value) {
            if ($('input[data-identifier="' + existing_value + '"]').parents('.category-container').attr('id') != parent_id) {
                var index_id = existing_index + '_add';
                facet_filters[index_id] = existing_value;
            }
        });
        // }.
        var target_path = drupalSettings.digital_report_library.target_url;
        var count = 0;

        $.each(facet_filters, function (index, value) {
            if (value) {
                target_path += "&f[" + count + "]=" + value;
                count++;
            }
        });

        // Redirect to apply the filters at once.
        window.location.href = encodeURI(target_path);
    }

    function append_selected_elements(clicked_element, type) {
        if (type == 'parent-item' || type == 'therapy-area-parent') {
            var item_data = [];
            var item_id = [];
            if (type == 'therapy-area-parent') {
                var parent_identifier = $(clicked_element).parents('.checkbox-parent').attr('class').split(" ")[0];
            } else {
                var parent_identifier = $(clicked_element).parents('li.parent-checkbox').attr('id');
            }

            $('#facet_filters_library li.' + parent_identifier).each(function (key, value) {
                if (!($(this).hasClass('checkbox-parent'))) {
                    item_id[key] = $(this).find('input[type="checkbox"]').attr('id');
                    item_data[key] = $(this).find('input[type="checkbox"]').attr('data-value');
                }
            });

            var parent_facet = $(clicked_element).parents('.category-container').attr('id');
            if (clicked_element.checked) {
                $.each(item_data, function (index, data) {
                    if (!$('#' + parent_facet + ' ul.remove-list li a[data-checkboxid="' + item_id[index] + '"]').length && typeof data != 'undefined') {
                        $('#' + parent_facet + ' ul.remove-list').append('<li class="' + item_id[index] + '"><a href="javascript:void(0);" data-checkboxid="' + item_id[index] + '" class="added_onclick remove-filter" data-value="' + data + '">' + data + '</a></li>');
                    }
                });
                showDefaulttext(parent_facet, type);
            } else {
                $.each(item_data, function (index, data) {
                    if ($('#' + parent_facet + ' ul.remove-list li a[data-checkboxid="' + item_id[index] + '"]').length) {
                        $('#' + parent_facet + ' ul.remove-list li a[data-checkboxid="' + item_id[index] + '"]').parent('li').remove();
                    }
                });
                showDefaulttext(parent_facet, type);
            }
        } else if (type == 'child-item' || type == 'single-level' || type == 'therapy-area-child') {
            var item_id = $(clicked_element).attr('id');
            var item_name = $(clicked_element).parents('li').not('li.parent-checkbox').find('input[type="checkbox"]').attr('data-value');
            var parent_facet = $(clicked_element).parents('.category-container').attr('id');
            if (clicked_element.checked) {
                if (!$('#' + parent_facet + ' ul.remove-list li a[data-checkboxid="' + item_id + '"]').length) {
                    $('#' + parent_facet + ' ul.remove-list').append('<li class="' + item_id + '"><a href="javascript:void(0);" data-checkboxid="' + item_id + '" class="added_onclick remove-filter" data-value="' + item_name + '">' + item_name + '</a></li>');
                }
                showDefaulttext(parent_facet, type);
            } else {
                if ($('#' + parent_facet + ' ul.remove-list li a[data-checkboxid="' + item_id + '"]').length) {
                    $('#' + parent_facet + ' ul.remove-list li a[data-checkboxid="' + item_id + '"]').parent('li').remove();
                }
                showDefaulttext(parent_facet, type);
            }
        }
    }

    function showDefaulttext(parent_facet, type) {
        if (type == 'single-level' || type == 'therapy-area-child' || type == 'therapy-area-parent') {
            if (type == 'therapy-area-parent' || type == 'therapy-area-child') {
                var checkedCheckboxes = $('#' + parent_facet + ' #ta-selections li:not(.checkbox-parent) input[type="checkbox"]:checked').length;
            } else {
                var checkedCheckboxes = $('#' + parent_facet + ' li:not(.parent-checkbox) input[type="checkbox"]:checked').length;
            }
        } else {
            var checkedCheckboxes = $('#' + parent_facet + ' .child-facets li input[type="checkbox"]:checked').length;
        }

        if (!checkedCheckboxes) {
            $('#' + parent_facet + ' ul.remove-list li.default-text').removeClass('hide');
            $('#' + parent_facet + ' h5 .category-count').text('(' + checkedCheckboxes + ')').addClass('hide');
        } else {
            $('#' + parent_facet + ' ul.remove-list li.default-text').addClass('hide');
            $('#' + parent_facet + ' h5 .category-count').text('(' + checkedCheckboxes + ')').removeClass('hide');
        }
    }



    function biopharmaSitemapAjaxRequest(form_value) {
        $this_row = form_value;
        var solution_names = [];
        $this_row.each(function () {
            solution_names.push($(this).val());
        });
        var solutionNames = JSON.stringify(solution_names);
        var topic_type = 'disease';
        var access_checked = 0;
        var quick_filter = $this_row.closest('#browse-sitemap').find('.filters input#quick_search');
        var quick_filter_text = '';
        if ($this_row.closest('#browse-sitemap').find('.filters input#ownedCheckBox').is(':checked')) {
            access_checked = 1;
        }
        if (quick_filter.val() !== null && quick_filter.val() !== '') {
            quick_filter_text = quick_filter.val();
        }

        insightAjaxReq = $.ajax({
            type: 'POST',
            url: drupalSettings.insight_biopharma_sitemap.baseUrl + '/disease/sitemap',
            data: {topic_type: topic_type, solution_names: solutionNames, access_check: access_checked, filter_topic_value: quick_filter_text},
            beforeSend: function () {
                $('[rel=popover]').popover('hide');
                $('#content-container').html('<div class="panel shadow-panel" style="height:390px"><div class="tab-content"><div class="loading-more-data" style="padding: 120px 0 0 0;font-size: 15px;"><i class="fa fa-circle-o-notch fa-spin fa-2x fa-fw"></i> <span class="loading-more-text">Loading</span></div></div></div>');
            },
            success: function (sitemapData) {
                $("#content-container").removeClass("loading-more-data");
                if (sitemapData !== null) {
                    sitemapData = JSON.parse(sitemapData);
                    $("#content-container").html(sitemapData.sitemap);
                }
            },
            complete: function (sitemapData) {
                $('#content-container').fadeIn('slow');
                var ajaxPopupClass = 'ajax-popover-menu';
                sitemapPopupOnHover(ajaxPopupClass);

                if ($("body").hasClass("page-disease-sitemap")) {
                    $("body").attr({
                        'data-spy': "scroll",
                        'data-target': ".sitemap-quicklinks",
                        'data-offset': "180"
                    });
                }
                var offsetHeight = 165;
                $('.sitemap-quicklinks li a').click(function (event) {
                    var scrollPos = $('body .sitemap-wrapper').find($(this).attr('href')).offset().top - offsetHeight;
                    $('body,html').animate({
                        scrollTop: scrollPos
                    }, 100);
                    return false;
                });
            }
        });
    }




})(jQuery);

/**
 * Function to check if AJAX call is running already and if so abort it
 */
function searchCancelRequestIfRunning() {
    if (searchAjaxReq !== undefined && searchAjaxReq.readyState !== 4) {
        searchAjaxReq.abort();
    }
}
