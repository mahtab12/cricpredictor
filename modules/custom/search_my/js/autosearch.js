(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.autocomplete = {
    attach: function (context, settings) {
      jQuery('#insight-platform-search').keyup(function () {
        $('.advance-search-clear').removeClass('hide');
        if ($('#searchDropdown button').text().trim() == 'US Market Access') {
          var valThis = $(this).val().toLowerCase();
          var wrapper = $(this).closest('.navbar-form');
          wrapper.find('#usaccess-search li').remove();

          if (valThis == "") {
            wrapper.find('#usaccess-search').hide();
            wrapper.find('.clearer').addClass('hide');
          } else {
            wrapper.find('#usaccess-search').show();
            wrapper.find('.clearer').removeClass('hide');

            var searchMatches = [];
            var first = [];
            var others = [];
            //$('#GeographyList li, #AccountsList li, #ProductList li').each(function () {
            for (var key in objects) {
              var object = objects[key];
              // console.log(object['type']);
              var value = object['text'];
              var text = value.toLowerCase();
              var type = object['type'];
              var category = object['category'];
              var tid = object['tid'];
              if (text.indexOf(valThis) > -1) {
                var regEx = new RegExp(valThis, 'gi');
                displayText = value.replace(regEx, function (a, b) {
                  return '<strong>' + a + '</strong>'
                });
                if (text.indexOf(valThis) == 0) {
                  first.push({
                    value: value,
                    text: text,
                    displayText: displayText,
                    type: type,
                    category: category,
                    tid: tid
                  });
                } else {
                  others.push({
                    value: value,
                    text: text,
                    displayText: displayText,
                    type: type,
                    category: category,
                    tid: tid
                  });
                }

                function compare(a, b) {
                  if (a.text < b.text)
                    return -1;
                  if (a.text > b.text)
                    return 1;
                  return 0;
                }

                first.sort(compare);
                others.sort(compare);
                searchMatches = first.concat(others);
              }
            }
            $.each(searchMatches, function (index, value) {
              wrapper.find('#usaccess-search').append('<li><a href="/search?'+encodeURI(value.category +'['+value.tid+']' + '=' + value.tid)+'"><span class="category-icon '+(value.category  == 'geo' ? 'GeographyList' : 'AccountsList')+'"></span>' + value.displayText + '<span class="smalltext">' + value.type + '</span></a></li>');
              return index < 9;
            });
            wrapper.find('#usaccess-search').append('<li class="search-all"><a href="/search?query=' + $(this).val() + '">See all result for <strong>"' + $(this).val() + '"</strong></a></li>');
          }
        }
      });
    }
  }
  var objects = [];
  $.ajax({
    url: "/autosearch",
    success: function (result) {
      objects = JSON.parse(result);
    }
  });

  $(document).on('click','.advance-search-clear', function () {
    $('#insight-platform-search').val('');
    $('#usaccess-search').hide();
    $(this).addClass('hide');
  });

  $(document).click(function(e) {
    var container = $("#usaccess-search");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
      container.hide();
    }
  });
  $('#searchDropdown ul li').click(function(){
    $('#block-insightsearchblock #searchDropdown').removeClass('open');
    $('.advance-search-clear').click();
  })
  $('.result-title').click(function () {
    if ($('#searchDropdown button').text().trim() == 'US Market Access') {
         $('.page-loader').show();
         $('.two-tier-select').hide();
         $('.page-loader').addClass('loading');
    }  
  });
})(jQuery, Drupal);
