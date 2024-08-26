const formatCHF = number => {
    let formattedNumber = new Intl.NumberFormat(document.documentElement.lang, {
        style: 'currency',
        currency: 'CHF',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);

    // Ensure there is a space after 'CHF' if needed
    return formattedNumber;
};


$(document).ready(function () {
    var fetchUrl = $('#fetchPage').val();

    if (typeof fetchUrl !== 'undefined') {
        var firstCmsPageId = $('#defaultCmsPage').val();
        const defaultformData = new FormData();
        defaultformData.append('firstCmsPageId', firstCmsPageId);
        setTimeout(() => {
            fetch(fetchUrl, {
                method: 'POST',
                body: defaultformData,
            }).then(response => response.json())
                .then(response => {
                    $('#owyId').html(response.result);
                });
        }, 800);
    }

    $('select[name="cmscategory"]').on('change', function () {
        const ddVal = $(this).val();
        const cmsPageformData = new FormData();
        cmsPageformData.append('firstCmsPageId', ddVal);
        setTimeout(() => {
            fetch(fetchUrl, {
                method: 'POST',
                body: cmsPageformData,
            }).then(response => response.json())
                .then(response => {
                    $('#owyId').html(response.result);
                });
        }, 800);
    });

    if ($('body').hasClass('is-ctl-navigation is-act-index')) {
        setInterval(function () {
            var totalProductCount = $('.js-listing-wrapper').attr('data-listing-total');
            $('#owy-tot1').text(totalProductCount);
            $('#owy-tot2').text(totalProductCount);

            var strongTag = $('#owy-product-count strong');
            if (strongTag.length > 0) {
                var strongText = strongTag.text();
                $('#filter-product-count').text(strongText);
            }
        }, 600);


    }


    //Contact Form Submit

    //Contact and Support form

    $('#owy_submit').click(async function (event) {

        var formData = {
            message: $("#message").val(),
            firma: $("#firma").val(),
            name: $("#name").val(),
            vorname: $("#vorname").val(),
            strasse: $("#strasse").val(),
            plz: $("#plz").val(),
            telephone: $("#telephone").val(),
            email: $("#email").val(),
            page_val: $("#page_val").val(),

        };
        if ($("#email").val().length < 1 || $("#message").val().length < 1) {
            $("#response").text('Please fill required fields');
            $("#response").attr('style', 'display:block');
            $("#response").removeClass('bg-success');
            $("#response").addClass('bg-danger');
            setTimeout(function () {
                $("#response").attr('style', 'display:none');
            }, 2000);
        } else {
            url = $("#getUrl").val();

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            if (response.status == 200) {
                $("#response").text('Email Send Successfully');
                $("#response").attr('style', 'display:block');
                $("#response").removeClass('bg-danger');
                $("#response").addClass('bg-success');

                setTimeout(function () {
                    $("#response").attr('style', 'display:none');
                    $("#owy-form")[0].reset();
                }, 5000);
            } else {
                $("#response").text(response.headers.get('bcErrorText'));
                $("#response").attr('style', 'display:block');
                $("#response").removeClass('bg-success');
                $("#response").addClass('bg-danger');
                setTimeout(function () {
                    $("#response").attr('style', 'display:none');
                }, 2000);
            }
        }
        return false;
    });

    //Report photo exchange

    $('.report-photo-exchange').click(async function (event) {
        event.preventDefault();
        url = $(this).attr('href');
        const id = $(this).data('id');
        var formData = {
            id: id,
            title: $(`.photo-exchange-title[data-id="${id}"]`).text(),
        };

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        const reportResponseBlock = $(`.report-response-block[data-id="${id}"]`)
        if (response.status == 200) {
            reportResponseBlock.text('Email Send Successfully');
            reportResponseBlock.attr('style', 'display:block');
            reportResponseBlock.removeClass('bg-danger');
            reportResponseBlock.addClass('bg-success');

            setTimeout(function () {
                reportResponseBlock.attr('style', 'display:none');
            }, 5000);
        } else {
            reportResponseBlock.text(response.headers.get('bcErrorText'));
            reportResponseBlock.attr('style', 'display:block');
            reportResponseBlock.removeClass('bg-success');
            reportResponseBlock.addClass('bg-danger');
            setTimeout(function () {
                reportResponseBlock.attr('style', 'display:none');
            }, 2000);
        }
    });

    //Compare work
    setInterval(function () {
        var checkiconRed = $('.product-info').find('span.icon--red').parent().children()[1];
        $(checkiconRed).children().removeClass('is-added-to-compare');

        $('.product-info').find('span.icon--red').removeClass('icon--red').addClass('icon--empty');

        $('.is-added-to-compare').each(function () {
            var productInfo = $(this).parent().parent().parent().find('.product-info');

            // Check if it contains a <span> with the class .icon--empty
            var spanIconEmpty = productInfo.find('span.icon--empty');
            if (spanIconEmpty.length) {

                // If it contains the icon--empty class, update it to icon--red

                spanIconEmpty.removeClass('icon--empty').addClass('icon--red');
                var checkred = productInfo.find('span.icon--red');
                if (checkred.length) {
                    var pickData = $(checkred).parent().children()[1];
                    $(pickData).children().addClass('is-added-to-compare');

                }
            }
        });


        //Second Button



        $('.owy_clicked').each(function () {
            var productInfo1 = $(this).children().hasClass('is-added-to-compare');
            var boxList = $('.product-box').hasClass('box-list');

            if (productInfo1 == true) {
                var anotherDiv = $(this).parent().parent().parent().find('.product-image-wrapper').children()[2];

                if (boxList == true) {
                    var anotherDiv = $(this).parent().parent().parent().parent().find('.product-image-wrapper').children()[3];
                }
                if ($(anotherDiv).length > 0) {
                    $(anotherDiv).children().addClass('is-added-to-compare');


                }
            }
            var owybtnCustom = $(this).parent().parent().parent().find('.product-image-wrapper').children()[3];
            var owybtnCustom1 = $(owybtnCustom).children().hasClass('is-added-to-compare');
            if (boxList == true && owybtnCustom1) {
                $(this).children().addClass('is-added-to-compare');
            }

        });


    }, 400);


    //wishlist page quantity change

    if ($('body').hasClass('is-ctl-wishlist')) {
        $('.js-btn-plus').on('click', function () {
            // Get the target ID from the data attribute
            var targetId = $(this).attr('id');
            var selector = '#myval-' + targetId;
            // Get the current quantity and increment it
            var currentQuantity = parseInt($(selector).val());
            var newQuantity = currentQuantity + 1;

            // Update the quantity on the element
            $(selector).val(newQuantity);
            var selecttotPrice = '#myprice-' + targetId;

            var multiplyPrice = parseInt($(selecttotPrice).attr('data-price') * newQuantity);

            $(selecttotPrice).text(formatCHF(multiplyPrice));


        });

        $('.js-btn-minus').on('click', function () {
            // Get the target ID from the data attribute
            var targetId = $(this).attr('id');
            // Build the selector for the element to update
            var selector = '#myval-' + targetId;
            // Get the current quantity and increment it
            var currentQuantity = parseInt($(selector).val());
            if (currentQuantity > 1) {
                var newQuantity = currentQuantity - 1;
                $(selector).val(newQuantity);
                var selecttotPrice = '#myprice-' + targetId;
                var multiplyPrice = parseInt($(selecttotPrice).attr('data-price') * newQuantity);
                $(selecttotPrice).text(formatCHF(multiplyPrice));
            }

        });
    }

    $(document).on('click', '#wishlist-mob', function (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        var owyLink = $('#owyshopLink').attr('data-shop-url');

        window.location.replace(owyLink + "/wishlist");

    });


    if ($('body').hasClass('is-act-create')) {
        setInterval(function () {
            const fileInput1 = $('#picture_1')[0];
            const file1 = fileInput1.files[0];

            const fileInput2 = $('#picture_2')[0];
            const file2 = fileInput2.files[0];

            const fileInput3 = $('#picture_3')[0];
            const file3 = fileInput3.files[0];

            const fileInput4 = $('#picture_4')[0];
            const file4 = fileInput4.files[0];

            const fileInput5 = $('#picture_5')[0];
            const file5 = fileInput5.files[0];

            if ((file1 != undefined) || (file2 != undefined) || (file3 != undefined) || (file4 != undefined) || (file5 != undefined)) {

                $('#pxbtn').attr("disabled", false);
            } else {

                $('#pxbtn').attr("disabled", true);
            }

        }, 200);
    }

    /*
    $('#picture_1').on('change', function () {
        const fileInput = $('#picture_1')[0];
        const warningMessage = $('#warningMessage1');

        const file = fileInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (file && allowedTypes.indexOf(file.type) === -1) {
            warningMessage.text('Please select a .jpg or .png or .gif file.');
            fileInput.value = ''; // Clear the file input
        } else {
            warningMessage.text(''); // Clear the warning message
        }
    });


    $('#picture_2').on('change', function () {
        const fileInput = $('#picture_2')[0];
        const warningMessage = $('#warningMessage2');

        const file = fileInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (file && allowedTypes.indexOf(file.type) === -1) {
            warningMessage.text('Please select a .jpg or .png or .gif file.');
            fileInput.value = ''; // Clear the file input
        } else {
            warningMessage.text(''); // Clear the warning message
        }
    });


    $('#picture_3').on('change', function () {
        const fileInput = $('#picture_3')[0];
        const warningMessage = $('#warningMessage3');

        const file = fileInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (file && allowedTypes.indexOf(file.type) === -1) {
            warningMessage.text('Please select a .jpg or .png or .gif file.');
            fileInput.value = ''; // Clear the file input
        } else {
            warningMessage.text(''); // Clear the warning message
        }
    });


    $('#picture_4').on('change', function () {
        const fileInput = $('#picture_4')[0];
        const warningMessage = $('#warningMessage4');

        const file = fileInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (file && allowedTypes.indexOf(file.type) === -1) {
            warningMessage.text('Please select a .jpg or .png or .gif file.');
            fileInput.value = ''; // Clear the file input
        } else {
            warningMessage.text(''); // Clear the warning message
        }
    });


    $('#picture_5').on('change', function () {
        const fileInput = $('#picture_5')[0];
        const warningMessage = $('#warningMessage5');

        const file = fileInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (file && allowedTypes.indexOf(file.type) === -1) {
            warningMessage.text('Please select a .jpg or .png or .gif file.');
            fileInput.value = ''; // Clear the file input
        } else {
            warningMessage.text(''); // Clear the warning message
        }
    });
    */
    // Initialize groups with pre-selected images
    $('.photo-exchange-image-group').each(function () {
        const group = $(this);
        const previewImage = group.find('.preview');
        if (previewImage.attr('src')) {
            previewImage.show();
            group.find('.delete-button').show();
            // group.find('.file-input-label').text('Replace File');
        }
    });

    $('.photo-exchange-image-group .file-input-label').click(function () {
        $(this).siblings('.file-input').click();
    })

    $('.photo-exchange-image-group .file-input').on('change', function (event) {
        const fileInput = $(this);
        const file = event.target.files[0];
        const group = fileInput.closest('.photo-exchange-image-group');
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                group.find('.preview').attr('src', e.target.result).show();
                group.find('.delete-button').show();
                // group.find('.file-input-label').text('Replace File');
                removeImageId(fileInput);
            }
            reader.readAsDataURL(file);
        }
    });

    $('.photo-exchange-image-group .delete-button').on('click', function () {
        const button = $(this);
        const group = button.closest('.photo-exchange-image-group');
        group.find('.preview').attr('src', '').hide();
        group.find('.file-input').val('');
        button.hide();
        // group.find('.file-input-label').text('Choose File');
        removeImageId(button);
    });

    removeImageId = (element) => {
        element.closest('.photo-exchange-image-group').find('.picture_id').val("");
    }

});
// Listing Page filter Script
var elements = document.getElementsByClassName("filter-multi-select-count");

if (elements.length > 0) {
    setInterval(function () {
        for (var i = 0; i < elements.length; i++) {
            var element = elements[i];

            // Check if the element has text content
            if (element.textContent.trim().length > 0) {
                // If it has text content, remove round brackets, trim the text, add an additional class, and update the text content
                var newText = element.textContent.replace(/[()]/g, "").trim();
                element.classList.add("bc-filter-count");
                element.textContent = newText;
            } else {
                // If it doesn't have text content, remove the 'filter-multi-select-count' class
                element.classList.remove("bc-filter-count");
            }
        }
    }, 100);
}
//End of filter script

// footer navigation link add active class OLD
/*
$(function(){
    var current = location.pathname;
    $('.footer-service-menu-list li a').each(function(){
        var $this = $(this);
        // if the current path is like this link, make it active
        if($this.attr('href').indexOf(current) !== -1){
            $this.addClass('fo--active');
        }
    })
})
*/

// footer navigation link add active class
$(function () {
    // Extracts the path from a full URL or a relative path
    function getPathFromUrl(url) {
        var link = document.createElement('a');
        link.href = url;
        return link.pathname.startsWith('/') ? link.pathname.substr(1) : link.pathname;
    }

    // Define an array of language codes to exclude
    var excludedSegments = ['fr', 'en'];

    // Get the current page's first path segment
    var currentPath = getPathFromUrl(location.href);
    var currentSegments = currentPath.split('/').filter(Boolean);
    var currentFirstSegment = currentSegments[0];

    // Check if the current first segment is a language code, and exclude it if so
    if (excludedSegments.includes(currentFirstSegment)) {
        // If the first segment is a language code, use the second segment instead
        currentFirstSegment = currentSegments[1] || currentFirstSegment;
    }

    $('.footer-service-menu-list li a').each(function () {
        var $this = $(this);
        // Extract the first path segment from each footer link
        var linkPath = getPathFromUrl($this.attr('href'));
        var linkSegments = linkPath.split('/').filter(Boolean);
        var linkFirstSegment = linkSegments[0];

        // Exclude links if the first segment is a language code
        if (excludedSegments.includes(linkFirstSegment)) {
            linkFirstSegment = linkSegments[1] || linkFirstSegment;
        }

        // Compare the first segment of the current page with the link
        if (currentFirstSegment === linkFirstSegment) {
            //console.log('Adding class to:', $this.attr('href')); // Debugging
            $this.addClass('fo--active');
        }
    });
});




// tabs sidebar sticky menu

var hasClassDetail = $('body').hasClass('is-ctl-product is-act-index');
if (hasClassDetail == true) {
    var topLimit = $('#bar-fixed').offset().top;
    $(window).scroll(function () {
        //console.log(topLimit <= $(window).scrollTop())
        if (topLimit <= $(window).scrollTop()) {
            $('#bar-fixed').addClass('stickIt')
        } else {
            $('#bar-fixed').removeClass('stickIt')
        }
    })
}

const currentUrl = window.location.href;
if (currentUrl.includes("photo-exchange")) {
    // If it does, add a class using jQuery
    $(document).ready(function () {
        $('.main-navigation-link').each(function (index, value) {
            var photoUrl = $(value).attr('href');
            if (photoUrl && photoUrl.includes("photo-exchange")) {
                $(value).addClass('active');
            }
        });
    });
}