$(function(){
var counter;
  $('.popover-menu').popover({
    'html' : true, 
    'placement':'top',
    'trigger': 'manual',
    'animation': false,    
    'container': 'body',
    'template': '<div class="popover popover-menu-wrapper"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"></div></div></div>',
    'content': function() {
      var content = $(this).attr('id');
      return $("#"+content+"Popover").html();
    }
  }).on("mouseenter",function () {
    var _this = this;
    clearTimeout(counter);
    counter = setTimeout(function(){
      if($(_this).is(':hover')) {        

        $(_this).popover("show");
        $(_this).addClass("popover-open");

        var offset = $(_this).offset(),
            browserWidth   = $( document ).width(),
            hoverItemWidth = $(_this).outerWidth(),
            popoverWidth   = $('.popover').outerWidth();

        if ((browserWidth - offset.left) > popoverWidth) {
          $('.popover').css('left',+offset.left-10+'px').css('opacity','0').css('margin-top','-22px');
          $('.popover .arrow').css('left','22px');
        } else {
          $('.popover').css('left',+offset.left+hoverItemWidth-popoverWidth+'px').css('opacity','0').css('margin-top','-15px');
          $('.popover .arrow').css('left',+popoverWidth-hoverItemWidth+22+'px');
        }
        $('.popover').animate({
          opacity: 1,
          top: "+=10"
        }, 200, function() {}); 
      }
      $(".popover").on("mouseleave", function () {
        $('.popover-menu').popover('hide');
        $(_this).removeClass("popover-open");
      });
    }, 400);
  }).on("mouseleave", function () {
      var _this = this;
      setTimeout(function () {
        if (!$(".popover:hover").length) {
          $(_this).popover("hide");
          $(_this).removeClass("popover-open");
        }
      }, 200);
  });
});

