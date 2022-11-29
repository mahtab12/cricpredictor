(function ($) {
  Drupal.behaviors.initSlider = {
    attach: function (context, settings) {
      $(document).ready(function(){
        setTimeout(refresh,60000);
        slickCarousel();
      });

      function slickCarousel(){
        $('.slick-parent .slick-slider').slick({
          dots: true,
          infinite: false,
          speed: 300,
          slidesToShow: 3,
          slidesToScroll: 1,
          prevArrow:"<button type='button' class='slick-prev pull-left'><i class='fa fa-angle-left' aria-hidden='true'></i></button>",
          nextArrow:"<button type='button' class='slick-next pull-right'><i class='fa fa-angle-right' aria-hidden='true'></i></button>",
          responsive: [
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
                infinite: true,
                dots: true
              }
            },
            {
              breakpoint: 600,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2
              }
            },
            {
              breakpoint: 480,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1
              }
            }
          ]
        });
      }

      function refresh(){
        console.log("Refreshed");
        if ( window.location.pathname == '/' ){
          console.log("coming Here");
          setInterval( function (){
            let URL = '/live/matches/fetch';
            $.ajax({
              url: URL,
              // dataType: "json",
              type: "GET",
              beforeSend: function () {
                var loader_html = '';
                loader_html += '<div id="overlay" class="overlaysave"><div class="cv-spinner">';
                loader_html += '<span class="spinner"></span>';
                loader_html += '<div id="progress-text" class="progress-text"></div>';
                loader_html += '</div></div>';

                if ($('#overlay').length == 0) {
                  $('.slick-slider').append(loader_html);
                  $("#overlay").fadeOut(300);
                }
              },
              success: function (result) {
                $('#block-cricketmatchtopbar .content').html(result[3].data);
                slickCarousel();
              },
              error: function (jqXHR, textStatus, errorThrown) {
                // Error
              }
            });
            //setTimeout(refresh,60000);
          }, 60000);
        }
      }
    }
  };
})(jQuery);