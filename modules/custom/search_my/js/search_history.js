/**
 * @file
 */
(function ($) {
    Drupal.behaviors.insightPlatformSearch = {
        attach: function (context, settings) {

            $('#insight-search-form #edit-submit, #insight-search-form #edit-submit-button').click(function () {
                var textLength = $(this).parents('form.navbar-form').find('input[type="text"]').val().length;
                if (textLength < 3) {
                    $("#insight-search-form, #views-exposed-form-search-page").find('div.searchError').remove();
                    $("#insight-search-form, #views-exposed-form-search-page").append('<div class="searchError">Your search should contain minimum 3 characters.</div>');
                    $(".searchError").fadeOut(5000, "linear");
                    return false;
                } else if (textLength == 0 && $("div.scope-tag").text() != '') {
                    var keyword = $("div.scope-tag").text();
                    $("div.scope-tag").remove();
                    $("#views-exposed-form-search-page input[name='search_within']").remove();
                    $("#views-exposed-form-search-page input[name='query']").css({"padding-left": "10px"});
                    $("#views-exposed-form-search-page input[name='query']").val(keyword);
                    $("#search-suggestions").hide();
                }
            });

            window.displayBoxIndex = -1;
            var mouse_is_inside = false;
            $('#insight-search-form, #views-exposed-form-search-page').hover(function () {
                mouse_is_inside = true;
            }, function () {
                mouse_is_inside = false;
            });

            $("body").mouseup(function () {
                if (!mouse_is_inside) {
                    $('#search-suggestions').hide();
                    $("#search-suggestions li").removeClass("display_box_hover");
                }
            });

            $("#edit-home-search-box, #insight-platform-search").once().focus(function () {
                if ($(this).val().length > 0) {
                    $("#search-suggestions").hide();
                    $("#search-suggestions li").removeClass("display_box_hover");
                    window.displayBoxIndex = -1;
                    // console.log('length');
                } else {
                    // console.log('out');
                    // update the content here only if the recent search provided is not same as present in the block
                    var business_filter_selected = $("#searchDropdown li.active a").attr('id');
                    // if($(".searchhistory").length){
                    // to handle cases when in block search_history block there is no searchhistory div present.
                    // if($(".searchhistory").attr('class').split(" ").pop() != business_filter_selected){
                    $.ajax({
                        type: 'POST',
                        url: '/business_filter_recent_searches',
                        data: 'business_filter=' + business_filter_selected,
                        success: function (result) {
                            if (result != null) {
                                // to set the class of actively selected.
                                $(".searchhistory").show();
//                    var already_present_class = $(".searchhistory").attr('class').split(" ").pop();
//                    $(".searchhistory").removeClass(already_present_class);
                                $(".searchhistory").addClass(business_filter_selected);
                                $(".searchhistory").replaceWith(result);
                                $("#search-suggestions").show();
                            } else {
                                $("#search-suggestions").hide();
                                $("#search-suggestions li").removeClass("display_box_hover");
                                window.displayBoxIndex = -1;
                                return;
                            }
                        },
                        error: function (e) {
                            console.log("error " + e);
                            $(".searchhistory").show();
                            $("#search-suggestions").show();
                        }
                    });
                    // }else{
                    if ($("#search-suggestions .display_box").length) {
                        $(".searchhistory").show();
                        $("#search-suggestions").show();
                    }
                    //}
//             }else{
//                 // not present call ajax
//                 $.ajax({
//            type: 'POST',
//            url: Drupal.settings.basePath + 'business_filter_recent_searches',
//            data: 'business_filter=' + business_filter_selected,
//            success: function (result) {
//                if(result !=null){
//                    $(".searchhistory").show();
//                    $(".searchhistory").addClass(business_filter_selected);
//                    $(".searchhistory").html(result);
//                    
//                    $("#search-suggestions").show();
//                }else{
//                    $("#search-suggestions").hide();
//                    $("#search-suggestions li").removeClass("display_box_hover");
//                    window.displayBoxIndex = -1;
//                    return;
//                }
//              },
//              error: function (e) {
//                  console.log("error "+e);
//                  $(".searchhistory").show();
//                  $("#search-suggestions").show();
//              }
//              }); 
//             }  
                }
                $('#search-suggestions .deleteRow').show();
            });

            $("#edit-home-search-box, #insight-platform-search").keyup(function (e) {
                var typedKeywordLength = $(this).val().length;
                var parent_id = $(this).prev().attr("id");
                if (typedKeywordLength > 0 && parent_id == "autocomplete") {
                    return false;
                }
                if ($('#search-suggestions li').length <= 1) {
                    $("#search-suggestions").hide();
                    $("#search-suggestions li").removeClass("display_box_hover");
                    window.displayBoxIndex = -1;
                    return;
                }

                if (typedKeywordLength <= 0) {
                    $("#search-suggestions").show();
                } else if ((e.keyCode == 40 || e.keyCode == 38) && typedKeywordLength > 0) {
                    // TO DO.
                } else {
                    $("#search-suggestions").hide();
                    $("#search-suggestions li").removeClass("display_box_hover");
                    window.displayBoxIndex = -1;
                }

                if (e.keyCode == 46 || e.keyCode == 8) {
                    if (typedKeywordLength <= 0) {
                        $("#search-suggestions").show();
                    } else {
                        $("#search-suggestions").hide();
                        $("#search-suggestions li").removeClass("display_box_hover");
                        window.displayBoxIndex = -1;
                    }
                }

                if (e.keyCode == 40) { // Keyup.
                    Navigate(1);
                    var searchItem = $(".display_box_hover a").attr('title');
                    $("#edit-home-search-box, #insight-platform-search").val(searchItem);
                }

                if (e.keyCode == 38) { // Keydown.
                    Navigate(-1);
                    var searchItem = $(".display_box_hover a").attr('title');
                    $("#edit-home-search-box, #insight-platform-search").val(searchItem);
                }
            });

            var Navigate = function (diff) {
                displayBoxIndex += diff;
                var oBoxCollection = $(".display_box");
                // console.log(diff + ' ' + displayBoxIndex + ' ' + oBoxCollection.length)
                if (displayBoxIndex > oBoxCollection.length) {
                    displayBoxIndex = 0;
                }
                if (displayBoxIndex < 0) {
                    displayBoxIndex = oBoxCollection.length;
                }
                var cssClass = "display_box_hover";
                oBoxCollection.removeClass(cssClass).eq(displayBoxIndex).addClass(cssClass);
            }




            // Removing the border if the featured result is not rendered.
            if (!$('.view-search .special-result, .view-search .report-result').length) {
                $(".views-row-1.search-result:first").css({"border-top": "none", "padding-top": "0px"});
            }

            // Dynamic styling for the placeholder text in search field for scope search.
            if ($("div.scope-tag").text() != '') {
                var scope_text_width = $("div.scope-tag").width();
                var padding_text = parseInt(scope_text_width) + 45;
                $("#views-exposed-form-search-page input[name='query']").css({"padding-left": padding_text + "px"});

                $("div.scope-tag").click(function () {
                    $(this).remove();
                    $("#views-exposed-form-search-page input[name='search_within']").remove();
                    $("#views-exposed-form-search-page input[name='query']").attr("placeholder", Drupal.t("Type your interest area or question"));
                    $("#views-exposed-form-search-page input[name='query']").css({"padding-left": "10px"});
                });
            }


            $(document).on('click', '.result-meta > .matches', function (e) {
                if ($(this).hasClass('collapsed')) {
                    $(this).html('<i class="fa fa-plus-square"></i> Show Matches')
                } else {
                    $(this).html('<i class="fa fa-minus-square"></i> Hide Matches')
                }
            });

            $(document).on('click', '.list-chapters .matches', function (e) {
                if ($(this).hasClass('collapsed')) {
                    $(this).children().removeClass('fa-minus-square').addClass('fa-plus-square');
                } else {
                    $(this).children().addClass('fa-minus-square').removeClass('fa-plus-square');
                }
            });

        }
    };
    $(document).on('click', 'body #search-suggestions .deleteRow', function () {
        var id = this.id;
        id = id.substring(5);
        $(this).closest('li').remove();
        if ($('#search-suggestions li').length <= 1) {
            $("#search-suggestions").hide();
            window.displayBoxIndex = -1;
            $(".clearer.fa.fa-times-circle.form-control-feedback").hide();
        }
        var business_filter_selected = $("#searchDropdown li.active a").attr('id');
        $.ajax({
            url: "/delete_search_item/" + id + '/' + business_filter_selected,
        });
    });

    $(document).on('click', 'body #search-suggestions .clear-all-search-keywords', function () {
        $("#search-suggestions").hide();
        window.displayBoxIndex = -1;
        $("#search-suggestions li").remove();
        $(".clearer.fa.fa-times-circle.form-control-feedback").hide();
        //var path = Drupal.settings.search_history.basepath;
        var business_filter_selected = $("#searchDropdown li.active a").attr('id');
        $.ajax({
            url: "/delete_all_search_items/" + business_filter_selected,
        });
    });

})(jQuery);
