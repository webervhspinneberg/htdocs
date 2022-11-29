    $(window).scroll(function(){
        if ($(this).scrollTop() > 120) {
            $('.scrollicon').fadeIn();
        } else {
            $('.scrollicon').fadeOut();
        }
    });

    $('.scrollicon').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 1200);
        return false;
    });