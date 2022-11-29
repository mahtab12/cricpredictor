(function ($, Drupal, drupalSettings) {
  'use strict'

  Drupal.behaviors.searchAutoSuggestion = {
    attach: function (context, settings) {

      $(document).ready(function() {
        $('.ui-autocomplete').attr("id","autocomplete");
      });

      $("#insight-platform-search").autocomplete({
        appendTo: "#search_autocomplete",
        select: function (event, ui) {
          $(this).val(ui.item.value);
          $('#edit-submit', this.form).trigger('click');
          return false;
        },
        source: function(request, response) {
          if ($('#searchDropdown button').text().trim() == 'US Market Access') {
            return;
          }
          let url = $("#insight-platform-search").data("autocompletePath");
          let search_bunit = $("#search_bunit").val();
          $.getJSON(url, { q: request.term, bu: search_bunit }, response);
        },
        open: function(){
          let filterWidth = Math.round($("#searchDropdown").width() + 2);
          $('.ui-autocomplete').css({'width':'450px','left':filterWidth});
        }
      }).data("ui-autocomplete")._renderItem = function (ul, item) {
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append(item.label)
            .appendTo(ul);
      };

    }
  };
})(jQuery, Drupal, drupalSettings);
