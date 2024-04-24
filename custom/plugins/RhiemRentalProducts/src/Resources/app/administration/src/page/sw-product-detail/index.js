import template from './sw-product-detail.html.twig';

const { Entity } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Shopware.Component.override('sw-product-detail', {
    template,

    props: {
        variants: {
            type: Boolean,
            required: false,
            default: false
        },
    },

    data: function () {
        return {
            variantsDisabled: this.variants,
            taxesLoaded: false,
            rentalTimeRemoveParams: [
                'id',
                'position'
            ]
        }
    },

    watch: {
        isLoading: {
            handler() {
                if (!this.isLoading) {
                    let promise = this.variantPromise();

                    promise.then(() => {
                        this.initializeRentalProduct();
                    });
                }
            }
        }
    },

    computed:
    {
        ...mapState('rhiemRentalProduct', [
            'rentalProduct',
            'parentRentalProduct',
            'isLoadingRental'
        ]),

        ...mapGetters('rhiemRentalProduct', [
            'rentalProduct',
            'parentRentalProduct'
        ]),

        productCriteria() {
            const criteria = this.$super('productCriteria');

            criteria.addAssociation('children');
            criteria.addAssociation('rentalProduct');
            criteria.addAssociation('rentalProduct.tax');
            criteria.addAssociation('rentalProduct.children');
            criteria.addAssociation('rentalProduct.prices');
            criteria.addAssociation('orderLineItems');
            criteria.addAssociation('orderLineItems.order');

            return criteria;
        },

        rentalProductRepository() {
            return this.repositoryFactory.create('rental_product');
        },
    },

    methods: {
        onSave() {
            this.missedVariants = this.createMissingRentalVariants();
            if (!this.product._isNew) {
                this.updateProductRental();
            }
            this.$super('onSave');
        },

        saveFinish() {
            this.checkForChangedRentalState();
            if (this.product.extensions && this.product.extensions.rentalProduct) {
                Shopware.State.commit('rhiemRentalProduct/setRentalProduct', this.product.extensions.rentalProduct);
                this.checkForMissedVariants();
            }

            this.isSaveSuccessful = false;

            if (!this.productId) {
                this.$router.push({ name: 'sw.product.detail', params: { id: this.product.id } });
            }
        },

        createMissingRentalVariants() {
            if (
                !this.rentalProduct.parentId && this.rentalProduct.active
                && ((this.product.childCount > this.rentalProduct.childCount)
                    || this.rentalProduct.childCount === undefined && this.product.childCount > 0)
            ) {
                const productChildren = this.product.children;
                const rentalProductChildren = this.rentalProduct.children;
                let missingChildren = [];

                // find products without rentalProduct
                productChildren.forEach(variant => {
                    if (rentalProductChildren.findIndex(rentalChild => rentalChild.productId === variant.id) < 0) {
                        missingChildren.push(variant);
                    }
                })

                missingChildren.forEach(productVariant => {
                    let rentalProduct = this.rentalProductRepository.create(Shopware.Context.api);
                    rentalProduct.parentId = this.rentalProduct.id;
                    rentalProduct.parentVersionId = this.rentalProduct.versionId;
                    rentalProduct.productId = productVariant.id;
                    rentalProduct.productVersionId = productVariant.versionId;
                    rentalProduct.originalStock = productVariant.stock;
                    this.rentalProductRepository.save(rentalProduct, Shopware.Context.api)
                });
                return true;
            }
            return false;
        },

        checkForMissedVariants() {
            if (this.missedVariants) {
                let criteria = new Criteria();
                criteria.addFilter(Criteria.equals('parentId', this.rentalProduct.id));
                criteria.addFilter(Criteria.equals('parentVersionId', null));

                this.rentalProductRepository.search(criteria, Shopware.Context.api).then(response => {
                    response.forEach(rentalProduct => rentalProduct.parentVersionId = this.rentalProduct.versionId)
                    this.rentalProductRepository.saveAll(response, Shopware.Context.api);
                });
            }
        },

        checkForChangedRentalState() {
            this.initialState = this.rentalProduct.active;
        },

        loadRentalTaxes() {
            return this.taxRepository.search(new Criteria(1, 500), Shopware.Context.api).then((res) => {
                Shopware.State.commit('rhiemRentalProduct/setTaxes', res);
                Shopware.State.commit('rhiemRentalProduct/setLoadingRental', false);
            })
        },

        updateProductRental() {
            for (const rentalTime in this.rentalProduct.rentalTimes) {
                for (const property in this.rentalProduct.rentalTimes[rentalTime]) {
                    if (this.rentalTimeRemoveParams.indexOf(property) !== -1) {
                        delete this.rentalProduct.rentalTimes[rentalTime][property];
                    }
                }
            }

            this.product.extensions.rentalProduct = this.rentalProduct;
        },

        initializeRentalProduct() {
            if (this.isChild) {
                Shopware.State.commit('rhiemRentalProduct/setParentRentalProduct', this.parentProduct.extensions.rentalProduct);
            }

            let rentalProduct = !this.product.extensions.rentalProduct || this.product._isNew || (this.product.extensions.rentalProduct._isNew === undefined && this.isChild) ? this.repositoryFactory.create('rental_product').create(Shopware.Context.api) : this.product.extensions.rentalProduct;

            // if is new variant and not associated to existing parent
            if (this.isChild && this.parentProduct.extensions && this.parentProduct.extensions.rentalProduct && rentalProduct._isNew && !rentalProduct.parentId) {
                rentalProduct.parentId = this.parentProduct.extensions.rentalProduct.id;
                rentalProduct.parentVersionId = this.parentProduct.extensions.rentalProduct.versionId;
                rentalProduct.active = null;
                rentalProduct.price = null;
                rentalProduct.mode = null;
                rentalProduct.purchasable = null;
                rentalProduct.originalStock = this.product.stock;
                rentalProduct.taxId = null;

                // originalProduct and no rentalProduct exists
            } else if (rentalProduct._isNew) {
                rentalProduct.active = rentalProduct.active !== undefined ? rentalProduct.active : false;
                rentalProduct.price = rentalProduct.price !== undefined ? rentalProduct.price : this.product.price || this.parentProduct.price;
                rentalProduct.mode = rentalProduct.mode !== undefined ? rentalProduct.mode : 1;
                rentalProduct.purchasable = rentalProduct.purchasable !== undefined ? rentalProduct.purchasable : false;
                rentalProduct.taxId = rentalProduct.taxId || this.product.taxId;
                rentalProduct.originalStock = rentalProduct.originalStock || this.product.stock;

                rentalProduct.bailPrice = rentalProduct.bailPrice || [{ "net": 100, "gross": 100, "linked": true, "currencyId": this.defaultCurrency.id }];
                rentalProduct.bailTaxId = rentalProduct.bailTaxId || this.product.taxId;
                rentalProduct.bailActive = rentalProduct.bailActive || false;

            } else if (Object.keys(rentalProduct).length === 0) {
                return;
            }
            rentalProduct.prices = rentalProduct.prices || null;
            rentalProduct._isNew = false;
            this.loadRentalTaxes();

            // this.product.extensions.rentalProduct = this.rentalProduct;
            Shopware.State.commit('rhiemRentalProduct/setRentalProduct', rentalProduct);
        },

        variantPromise() {
            let me = this;
            return new Promise((resolve) => {
                // if is variant but no parentRentalProduct exists create new parentRentalProduct
                if (me.isChild && !me.parentProduct.extensions.rentalProduct) {
                    let rentalProduct = me.rentalProductRepository.create(Shopware.Context.api);
                    rentalProduct.productId = me.parentProduct.id;
                    rentalProduct.productVersionId = me.parentProduct.versionId;
                    rentalProduct.price = me.parentProduct.price;
                    rentalProduct.taxId = me.parentProduct.taxId;
                    rentalProduct.originalStock = me.parentProduct.stock;

                    rentalProduct.bailPrice = [{ "net": 100, "gross": 100, "linked": true, "currencyId": me.defaultCurrency.id }];
                    rentalProduct.bailTaxId = me.parentProduct.taxId;
                    rentalProduct.bailActive = false;

                    me.rentalProductRepository.save(rentalProduct, Shopware.Context.api).then(() => {
                        let criteria = new Criteria();
                        criteria.addFilter(Criteria.equals('productId', me.parentProduct.id));

                        resolve(me.rentalProductRepository.search(criteria, Shopware.Context.api).then(response => {
                            me.parentProduct.extensions.rentalProduct = response.first();
                        }));

                    }
                    );
                } else {
                    resolve();
                }
            });
        }
    }
});
