import Plugin from 'src/plugin-system/plugin.class';

export default class OwyWishlist extends Plugin {
    init() {

        if (screen.width < 992){

            $(document).on('click','.btn-link', function(event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                var owyLink = $('#owyshopLink').attr('data-shop-url');

                window.location.replace(owyLink + "/cart");


            });


            $(document).on('click','#owy-remove', function() {

                var idToRemove = $(this).closest('.line-item-remove-button').data('id');


                var storedIDs = JSON.parse(localStorage.getItem('compare-widget-added-products'));


                var updatedIDs = storedIDs.filter(function(id) {
                    return id !== idToRemove;
                });


                localStorage.setItem('compare-widget-added-products', JSON.stringify(updatedIDs));


                location.reload();
                event.stopPropagation();
            });

            $(document).on('click', ".deleteWishlist", function(){
                const deleteWishlist = $(this).data('delurl');
                const productId = $(this).data('prdid');
                var wishlistPrd = $(".product-wishlist-"+productId);
                if(!$(this).hasClass('deleted')){
                    $(this).addClass('deleted');
                    $.post(deleteWishlist, function(data){
                        getWishlist();
                        let wishlistCount = parseInt($("#wishlist-basket").text()) - 1;
                        $("#wishlist-basket").text(wishlistCount);
                        wishlistPrd.removeClass('product-wishlist-added');
                        wishlistPrd.addClass('product-wishlist-not-added');

                    });
                }
            });
        }


        $(document).on('click', '#wishlist-btn', function(){
            if($('.dropdown-menu').hasClass('show')){
                $('.dropdown-menu').removeClass('owy-compare-inner-cart show');
            }
            if($('#wishlist-btn').hasClass('show')){

                var newVal = $('#wishlist-btn').parent().children()[1];
                $(newVal).addClass('show');
            }
            getWishlist();
            setTimeout(() => {
                $(".owy-wishlist-cart").find('.dropdown').remove();
            }, 300);
        });

        $(document).on('mouseleave', '.dropdown', function(){
            $("#wishlist-btn").off('click');
        });

        $(document).on('click', ".deleteWishlist", function(){
            const deleteWishlist = $(this).data('delurl');
            const productId = $(this).data('prdid');
            var wishlistPrd = $(".product-wishlist-"+productId);
            if(!$(this).hasClass('deleted')){
                $(this).addClass('deleted');
                $.post(deleteWishlist, function(data){
                    getWishlist();
                    let wishlistCount = parseInt($("#wishlist-basket").text()) - 1;
                    $("#wishlist-basket").text(wishlistCount);
                    wishlistPrd.removeClass('product-wishlist-added');
                    wishlistPrd.addClass('product-wishlist-not-added');
                });
            }
        });

        async function getWishlist(){

            const wishlistUrl = $("#wishlistUrl").val();
            const customerId = $("#customerId").val();
            await $.post(wishlistUrl, {customerId: customerId}, function(data){
                if(data.status){
                    $('.header-wishlist').find(".dropdown-menu").removeClass('wishlist-login');

                    var checkWLClasshow = $('.header-wishlist').find(".dropdown").children()[0];
                    if ($(checkWLClasshow).hasClass('show')){
                        setTimeout(() => {
                            var secondChildWishlist =  $('.header-wishlist').find(".dropdown").children()[1];
                            $(secondChildWishlist).addClass('show');
                        }, 700);
                    }
                    $('.header-wishlist').find('.dropdown-menu').html(data.wishlist);

                }else{
                    if(data.wishlist != null){
                        $('.header-wishlist').find(".dropdown-menu").addClass('wishlist-login');
                        $('.header-wishlist').find(".dropdown").removeClass('owy-wishlist-cart');
                        $('.header-wishlist').find('.dropdown-menu').html(data.wishlist);
                    }
                }
            });
        }


    }
}