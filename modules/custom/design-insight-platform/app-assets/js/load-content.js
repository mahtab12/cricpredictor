$(function () {

  // load partials into his container
  var loadPartial = function (element) {
    console.log(element.data('partial'));
    element.load(element.data('partial'), function () {
      $(this).find('[data-partial]').each(function () {
        loadPartial($(this));
      });
    });
  };

  // initial container partials load
  $('[data-partial]').each(function () {
    loadPartial($(this));
  });
});
