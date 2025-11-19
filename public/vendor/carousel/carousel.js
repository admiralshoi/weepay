function owlBasic(owlContainer) {
    let loop = owlContainer.attr("data-loop"),
        dots = owlContainer.attr("data-dots"),
        nav = owlContainer.attr("data-nav"),
        pagination = owlContainer.attr("data-pagination"),
        autoplay = owlContainer.attr("data-autoplay"),
        responsive = owlContainer.attr("data-responsive"),
        autoplaySpeed = owlContainer.attr("data-autoplay-speed"),
        responsiveOptions = { 0:{ items:1 }, 100:{ items:2 }, 600:{ items:3 }, 1000:{ items:4 } };
    responsive = responsive === undefined  || !Object.keys(responsiveOptions).includes(responsive) ?
        responsiveOptions : {[responsive]: responsiveOptions[responsive]};

    loop = !(loop === undefined || (loop.toString()) === false);
    dots = !(dots === undefined || (dots.toString()) === false);
    nav = !(nav === undefined || (nav.toString()) === false);
    autoplaySpeed = (autoplaySpeed === undefined || empty(autoplaySpeed)) ? 1000 : parseInt(autoplaySpeed);
    pagination = !(pagination === undefined || (pagination.toString()) === false);
    autoplay = !(autoplay === undefined || (autoplay.toString()) === false);


    owlContainer.owlCarousel({
        loop,
        margin:50,
        nav,
        pagination,
        dots,
        responsive,
        autoplay,
        autoplayTimeout:autoplaySpeed,
        autoplayHoverPause:true,
    });
}

$(function() {
  'use strict';

  if($('.owl-basic').length) {
      $('.owl-basic').each(function (){
            owlBasic($(this));
      });
  }

  if($('.owl-auto-play').length) {
    $('.owl-auto-play').owlCarousel({
      items:4,
      loop:true,
      margin:10,
      autoplay:true,
      autoplayTimeout:1000,
      autoplayHoverPause:true,
      responsive:{
        0:{
            items:2
        },
        600:{
            items:3
        },
        1000:{
            items:4
        }
    }
    });
  }

  if($('.owl-fadeout').length) {
    $('.owl-fadeout').owlCarousel({
      animateOut: 'fadeOut',
      items:1,
      margin:30,
      stagePadding:30,
      smartSpeed:450
    });
  }

  if($('.owl-animate-css').length) {
    $('.owl-animate-css').owlCarousel({
      animateOut: 'slideOutDown',
      animateIn: 'flipInX',
      items:1,
      margin:30,
      stagePadding:30,
      smartSpeed:450
    });
  }

  if($('.owl-mouse-wheel').length) {
    var owl = $('.owl-mouse-wheel');
    owl.owlCarousel({
        loop:true,
        nav:false,
        margin:10,
        responsive:{
            0:{
                items:2
            },
            600:{
                items:3
            },            
            960:{
                items:3
            },
            1200:{
                items:4
            }
        }
    });
    owl.on('mousewheel', '.owl-stage', function (e) {
        if (e.deltaY>0) {
            owl.trigger('next.owl');
        } else {
            owl.trigger('prev.owl');
        }
        e.preventDefault();
    });

  }

});
