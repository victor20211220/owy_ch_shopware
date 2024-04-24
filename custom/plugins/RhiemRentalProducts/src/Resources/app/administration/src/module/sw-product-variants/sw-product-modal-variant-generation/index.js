import RentalVariantsGenerator from '../../sw-product/helper/rental-variants-generator';


Shopware.Component.override('sw-product-modal-variant-generation', {
    data() {
        return {
            variantsGenerator: new RentalVariantsGenerator()
        };
    },

});
