$(document).ready(function () {

    //Listing Page View Change Script
    $(document).on('change', '#listing-style-form input[type=radio]', function () {
        let uri = window.location.href;
        if (uri.indexOf('#') !== -1) {
            uri = uri.replace('#', "");
        }
        if (uri.search('boxLayout') >= 0) {
            uri = uri.replace(/(boxLayout=)[^\&]+/, '$1' + $(this).val());
        } else {
            var matches = uri.match(/[a-z\d]+=[a-z\d]+/gi);
            var count = matches ? matches.length : 0;
            if (count > 0) {
                uri = uri + "&boxLayout=" + $(this).val();
            } else {
                uri = uri + "?boxLayout=" + $(this).val();
            }
        }
        window.location.href = uri;
    });

    $(".box-standard.owy-listbox").each(function (index) {
        $(this).parent('div').addClass('listview');
    });

    $(".box-minimal.owy-listbox").each(function (index) {
        $(this).parent('div').addClass('listview-default');
    });

    $('.cms-element-product-listing').on('DOMSubtreeModified', function () {
        $(".box-standard.owy-listbox").each(function (index) {
            $(this).parent('div').addClass('listview');
        });
        $(".box-minimal.owy-listbox").each(function (index) {
            $(this).parent('div').addClass('listview-default');
        });
    });

    //Listing Page View Change Script



    //Sticky

    window.onscroll = function () { fixedNavbar() };
    var navbar = document.getElementById("header-main-wraper");
    var sticky = navbar.offsetTop;
    function fixedNavbar() {
        if ($(window).width() > 1259) {
            if (window.pageYOffset >= sticky) {
                navbar.classList.add("is--sticky");
                $('main.content-main').css('padding-top', navbar.offsetHeight);
            } else {
                navbar.classList.remove("is--sticky");
                $('main.content-main').css('padding-top', '');
            }
        }

    }



    //Scrolling Script
    function scrollToElementWithOffset(elementId, offset) {
        const element = document.getElementById(elementId);
        if (element) {
            const elementTop = element.getBoundingClientRect().top; // Get the element's top relative to the viewport
            const offsetPosition = elementTop + window.pageYOffset - offset; // Calculate the final position including the offset

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    }

    $(document).on("click", "[data-scroll]", function () {
        var target = $(this).attr("data-scroll");
        $(".tabsnavigation-content ul li").removeClass("activeli");
        $("#bar-fixed ul li").not($(this)).removeClass("activeli");
        $(this).addClass("activeli");
        scrollToElementWithOffset(target, 185);
    });

    //End Scrolling Script





    //Mega Menu Script
    $('.main-navigation-link-text').hover(function () {
        $('.level-three').css("display", "none");
        $('.level-two').css("display", "block");
        $('.level-two').children().children().removeClass("is-active");
        $('.level-four').css("display", "none");
    });

    $('.level-two .list-group .list-group-item').each(function () {
        $(this).hover(function () {
            $('.level-four').css("display", "none");
            $('.level-three').css("display", "none");
            $('.level-three').children().css("display", "none");
            $('.level-two').children().children().removeClass("is-active");
            var lev1Id = $(this).attr('data-level1-id');

            if ($('#lev2-' + lev1Id).length > 0) {
                $('#lev2-' + lev1Id).css("display", "block");
                $('#lev2-' + lev1Id).parent().css("display", "block");
            } else {
                $('.level-three').css("display", "none");
                $('.level-three').children().css("display", "none");
            }
        });

    });

    $('.level-three .list-group .list-group-item').each(function () {
        $(this).hover(function () {
            $('.level-three').find('a').removeClass('is-active');
            $('.level-four').css("display", "none");
            $('.level-four').children().css("display", "none");
            var lev2Id = $(this).attr('data-level3-id');

            var Idlvl1 = $(this).parent().attr('data-id');
            $('#lvl1-' + Idlvl1).addClass('is-active');
            if ($('#lev3-' + lev2Id).length > 0) {

                $('#lev3-' + lev2Id).css("display", "block");
                $('#datalvel-' + lev2Id).addClass('is-active');
                $('#lev3-' + lev2Id).parent().css("display", "block");
            } else {
                $('.level-four').css("display", "none");
                $('.level-four').children().css("display", "none");
            }
        });
    });
    //End Mega Menu Script
});



/*
$(document).on('change', '#product-limit', function(){
    var limit = $(this).val();
    var currentUrl = window.location.href;

    var url = new URL(currentUrl);
    var params = new URLSearchParams(url.search);

    if (params.has('p') && params.has('limit')) {
        params.delete('p');
        currentUrl = url.origin + url.pathname + '?' + params.toString();
    }else if (params.has('p') || params.has('limit')) {
        params.delete('p');
        params.delete('limit');
        currentUrl = url.origin + url.pathname + "?limit="+limit;
    }else{
        currentUrl = url.origin + url.pathname + '?' + params.toString();
    }
    window.location.href = currentUrl;

});
*/

if ($('body').hasClass('is-ctl-navigation is-act-index') || $('body').hasClass('is-ctl-photoexchange')) {
    $(document).on('change', '#product-limit', function () {
        var limit = $(this).val();
        var url = new URL(window.location.href);
        searchParams = url.searchParams;
        if (searchParams.has('p') && searchParams.get('p') !== "1" && searchParams.get('limit') !== limit){
            searchParams.set('p', 1);
        }
        searchParams.set('limit', limit);
        window.location.href = url.href;

    });

    $(document).on('click', '.page-item input[type="radio"]', function (e) {

        e.preventDefault();

        // Get the value of the clicked input
        var newValue = $(this).val();

        // Parse current URL
        var url = new URL(window.location.href);

        // Update 'p' parameter with the new value
        url.searchParams.set('p', newValue);

        // Reload the page with the updated URL
        window.location.href = url.href;
    });
}

if ($('body').hasClass('is-ctl-photoexchange')) {
    /*
    $(document).on('change', '#product-limit', function () {

        var limit = $(this).val();
        var currentUrl = window.location.href;

        var url = new URL(currentUrl);
        var params = new URLSearchParams(url.search);

        if (params.has('p') && params.has('limit')) {
            params.delete('p');
            currentUrl = url.origin + url.pathname + '?' + params.toString();
        } else if (params.has('p') || params.has('limit')) {
            params.delete('p');
            params.delete('limit');
            currentUrl = url.origin + url.pathname + "?limit=" + limit;
        } else {
            currentUrl = url.origin + url.pathname + '?' + params.toString();
        }
        window.location.href = currentUrl;

    });
    */
}


//Embed Owl carousel

$(document).ready(function () {
    var owl = $(".detailgallery-slider");
    owl.owlCarousel({
        margin: 10,
        nav: true,
        dots: false,
        loop: true,
        responsive: {
            0: {
                items: 1,
            },
            480: {
                items: 2,
            },
            600: {
                items: 3,
            },
            800: {
                items: 4,
            },
            1000: {
                items: 4,
            },
            1200: {
                items: 4,
            },
            1400: {
                items: 4,
            },

        },

    });

    //Embed Owl carousel


    if ($("body").hasClass("is-act-home") && ($('#carouselIndicators').length > 0)) {
        const myCarousel = document.getElementById("carouselIndicators");
        const carouselIndicators = myCarousel.querySelectorAll(
            ".carousel-indicators button span"
        );
        let intervalID;
        let progress = 0;
        const carousel = new bootstrap.Carousel(myCarousel);

        myCarousel.addEventListener("slide.bs.carousel", function (e) {
            let index = e.to;
            fillCarouselIndicator(index);
        });
        function fillCarouselIndicator(index) {
            let i = 0;
            for (const carouselIndicator of carouselIndicators) {
                carouselIndicator.style.width = 0;
            }
            clearInterval(intervalID);
            carousel.pause();
            intervalID = setInterval(function () {
                i++;
                progress = (i / 100) * 100; // Adjust this based on your desired duration
                myCarousel.querySelector(".carousel-indicators .active span").style.width =
                    progress + "%";

                if (i >= 100) {
                    carousel.next();
                }
            }, 50); // Adjust the interval as needed
        }
        fillCarouselIndicator(0);
    }
});

//Shop pages Iteration

$('.is--parentactive').each(function () {

    var $parent = $(this);
    var $ulElements = $parent.find('ul');

    // Check if any child 'a' elements of $parent have the class 'is-active'
    var hasActiveChild = $parent.find('a.is-active').length > 0;
    if (!hasActiveChild) {
        $ulElements.each(function () {
            var $ul = $(this);
            var $activeLi = $ul.find('li a.is-active');

            if ($activeLi.length === 0) {
                $ul.remove();
            }
        });
    }
});


$(document).ready(function () {
    var submitIcon = $('.searchbox-icon');
    var inputBox = $('.searchbox-input');
    var searchBox = $('.searchbox');
    var submitButton = $('.searchbox-submit');
    var isOpen = false;
    submitIcon.click(function () {
        if (isOpen == false) {
            searchBox.addClass('searchbox-open');
            submitButton.css('visibility', 'visible');
            inputBox.focus();
            isOpen = true;
        } else {
            searchBox.removeClass('searchbox-open');
            inputBox.focusout();
            isOpen = false;
        }
    });

    function buttonUp() {
        var inputVal = $('.searchbox-input').val();
        inputVal = $.trim(inputVal).length;
        if (inputVal !== 0) {

        } else {
            $('.searchbox-input').val('');
            $('.searchbox-icon').css('display', 'block');
        }
    }
    inputBox.keyup(buttonUp);
});



