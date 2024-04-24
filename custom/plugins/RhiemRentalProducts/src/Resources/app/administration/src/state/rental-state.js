export default {
    namespaced: true,

    state() {
        return {
            rentalProduct: {},
            parentRentalProduct: {},
            loadingRental: true,
            taxes: []
        };
    },

    getters: {
        rentalProduct: state => state.rentalProduct,

        parentRentalProduct: state => state.parentRentalProduct,

        isLoadingRental: state => state.loadingRental,

        priceTaxRate(state) {
            if (!state.taxes) {
                return {};
            }

            return state.taxes.find((tax) => {
                let taxId = state.rentalProduct.taxId ?
                    state.rentalProduct.taxId
                    : state.parentRentalProduct.taxId;
                return tax.id === taxId;
            });
        },

        bailTaxRate(state) {
            if (!state.taxes) {
                return {};
            }

            return state.taxes.find((tax) => {
                let taxId = state.rentalProduct.bailTaxId ?
                                state.rentalProduct.bailTaxId
                                : state.parentRentalProduct.bailTaxId
                return tax.id === taxId;
            });
        }
    },

    mutations: {
        setRentalProduct(state, rentalProduct) {
            state.rentalProduct = rentalProduct;
        },

        setParentRentalProduct(state, parentRentalProduct) {
            state.parentRentalProduct = parentRentalProduct;
        },

        setLoadingRental(state, value) {
            state.loadingRental = value;
        },

        setTaxes(state, newTaxes) {
            state.taxes = newTaxes;
        },
    }
};
