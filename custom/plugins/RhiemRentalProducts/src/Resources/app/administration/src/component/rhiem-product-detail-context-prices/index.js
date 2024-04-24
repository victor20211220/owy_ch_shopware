import template from './rhiem-product-detail-context-prices.html.twig'
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;
const {mapState,mapGetters} = Shopware.Component.getComponentHelper();

Component.extend('rhiem-product-detail-context-prices', 'sw-product-detail-context-prices', {
    template,
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    computed:{
        ...mapState('swProductDetail', [
            'repositoryFactory',
            'product',
            'parentProduct',
            'taxes',
            'currencies'
        ]),
        ...mapState('rhiemRentalProduct', [
            'rentalProduct',
            'parentRentalProduct'
        ]),
        ...mapGetters('swProductDetail', [
            'isLoading',
            'defaultCurrency',
            'defaultPrice',
            'productTaxRate',
            'isChild'
        ]),
        graduationModeOptions() {
            return [{
                label: this.$tc('rental-product-detail-context-prices.graduationByQuantity'),
                value: 0
            }, {
                label: this.$tc('rental-product-detail-context-prices.graduationByDuration'),
                value: 1
            }];
        },
        priceRepository() {
            if (this.rentalProduct && this.rentalProduct.prices) {
                return this.repositoryFactory.create(
                    this.rentalProduct.prices.entity,
                    this.rentalProduct.prices.source
                );
            }
            return null;
        },

        priceRuleGroups() {
            const priceRuleGroups = {};

            if (!this.rentalProduct.prices) {
                return priceRuleGroups;
            }

            if (!this.rules) {
                return priceRuleGroups;
            }

            const sortedPrices = this.rentalProduct.prices.sort((a, b) => {
                const aRule = this.findRuleById(a.ruleId);
                const bRule = this.findRuleById(b.ruleId);

                if (!aRule || !aRule.name || !bRule || !bRule.name) {
                    return 0;
                }

                return aRule.name > bRule.name ? 1 : -1;
            });

            sortedPrices.forEach((rule) => {
                if (!priceRuleGroups[rule.ruleId]) {
                    priceRuleGroups[rule.ruleId] = {
                        ruleId: rule.ruleId,
                        rule: this.findRuleById(rule.ruleId),
                        prices: this.findPricesByRuleId(rule.ruleId),
                        mode:rule.mode
                    };
                }
            });

            // Sort prices
            Object.values(priceRuleGroups).forEach((priceRule) => {
                priceRule.prices.sort((a, b) => {
                    return a.quantityStart - b.quantityStart;
                });
            });

            return priceRuleGroups;
        },
        isLoaded() {
            return !this.isLoading &&
                this.currencies &&
                this.taxes &&
                this.rentalProduct;
        },
    },
    methods: {
        mountedComponent() {
            const ruleCriteria = new Criteria(1, 500);
            ruleCriteria.addFilter(
                Criteria.multi('OR', [
                    Criteria.contains('rule.moduleTypes.types', 'price'),
                    Criteria.equals('rule.moduleTypes', null)
                ])
            );

            Shopware.State.commit('swProductDetail/setLoading', ['rules', true]);
            this.ruleRepository.search(ruleCriteria, Shopware.Context.api).then((res) => {
                this.rules = res;
                this.totalRules = res.total;

                Shopware.State.commit('swProductDetail/setLoading', ['rules', false]);
            });

            this.isInherited = this.isChild && !this.rentalProduct.prices.total;
        },
        onModeChange(value, ruleId) {
            this.rentalProduct.prices.forEach((priceRule) => {
                if (priceRule.ruleId === ruleId) {
                    priceRule.mode = value;
                }
            });
        },
        onRuleChange(value, ruleId) {
            this.rentalProduct.prices.forEach((priceRule) => {
                if (priceRule.ruleId === ruleId) {
                    priceRule.ruleId = value;
                }
            });
        },
        onAddNewPriceGroup(ruleId = null) {
            if (this.emptyPriceRuleExists) {
                return;
            }

            const newPriceRule = this.priceRepository.create(Shopware.Context.api);

            newPriceRule.ruleId = ruleId;
            newPriceRule.rentalProductId = this.rentalProduct.id;
            newPriceRule.quantityStart = 1;
            newPriceRule.quantityEnd = null;
            newPriceRule.currencyId = this.defaultCurrency.id;
            newPriceRule.mode = 0;
            let price = this.rentalProduct.price ? this.rentalProduct.price[0] : this.parentRentalProduct.price[0];
            newPriceRule.price = [{
                currencyId: price.currencyId,
                gross: price.gross,
                linked: price.linked,
                net: price.net
            }];

            this.rentalProduct.prices.add(newPriceRule);
            this.$nextTick(() => {
                const scrollableArea = this.$parent.$el.children.item(0);

                if (scrollableArea) {
                    scrollableArea.scrollTo({
                        top: scrollableArea.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            });
        },
        onPriceGroupDelete(ruleId) {
            const allPriceRules = this.rentalProduct.prices.map(priceRule => {
                return { id: priceRule.id, ruleId: priceRule.ruleId };
            });

            allPriceRules.forEach((priceRule) => {
                if (ruleId !== priceRule.ruleId) {
                    return;
                }

                this.rentalProduct.prices.remove(priceRule.id);
            });
        },
        onPriceRuleDelete(priceRule) {
            // get the priceRuleGroup for the priceRule
            const matchingPriceRuleGroup = this.priceRuleGroups[priceRule.ruleId];

            // if it is the only item in the priceRuleGroup
            if (matchingPriceRuleGroup.prices.length <= 1) {
                this.createNotificationError({
                    message: this.$tc('sw-product.advancedPrices.deletionNotPossibleMessage')
                });

                return;
            }

            // get actual rule index
            const actualRuleIndex = matchingPriceRuleGroup.prices.indexOf(priceRule);

            // if it is the last item
            if (typeof priceRule.quantityEnd === 'undefined' || priceRule.quantityEnd === null) {
                // get previous rule
                const previousRule = matchingPriceRuleGroup.prices[actualRuleIndex - 1];

                // set the quantityEnd from the previous rule to null
                previousRule.quantityEnd = null;
            } else {
                // get next rule
                const nextRule = matchingPriceRuleGroup.prices[actualRuleIndex + 1];

                // set the quantityStart from the next rule to the quantityStart from the actual rule
                nextRule.quantityStart = priceRule.quantityStart;
            }

            // delete rule
            this.rentalProduct.prices.remove(priceRule.id);
        },
        findPricesByRuleId(ruleId) {
            return this.rentalProduct.prices.filter((item) => {
                return item.ruleId === ruleId;
            });
        },
        createPriceRule(priceGroup) {
            // create new price rule
            const newPriceRule = this.priceRepository.create(Shopware.Context.api);
            newPriceRule.rentalProductId = this.rentalProduct.id;
            newPriceRule.ruleId = priceGroup.ruleId;

            newPriceRule.mode = priceGroup.mode;

            const highestEndValue = Math.max(...priceGroup.prices.map((price) => price.quantityEnd));
            newPriceRule.quantityStart = highestEndValue + 1;

            let price = this.rentalProduct.price ? this.rentalProduct.price[0] : this.parentRentalProduct.price[0];
            newPriceRule.price = [{
                currencyId: price.currencyId,
                gross: price.gross,
                linked: price.linked,
                net: price.net
            }];

            this.rentalProduct.prices.add(newPriceRule);
        },
        duplicatePriceRule(referencePrice, ruleId = null) {
            const newPriceRule = this.priceRepository.create(Shopware.Context.api);

            newPriceRule.rentalProductId = referencePrice.rentalProductId;
            newPriceRule.quantityEnd = referencePrice.quantityEnd;
            newPriceRule.quantityStart = referencePrice.quantityStart;
            newPriceRule.ruleId = ruleId;

            newPriceRule.mode = referencePrice.mode;

            // add prices
            newPriceRule.price = [];

            referencePrice.price.forEach((price, index) => {
                this.$set(newPriceRule.price, index, { ...price });
            });

            this.rentalProduct.prices.add(newPriceRule);
        },
    }
});