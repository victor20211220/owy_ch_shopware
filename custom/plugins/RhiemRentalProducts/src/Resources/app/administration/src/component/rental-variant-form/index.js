import template from './rental-variant-form.html.twig'
import './rental-variant-form.scss'

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Criteria } = Shopware.Data;

Shopware.Component.register('rental-variant-form', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    data() {
        return {
            productId: this.$route.params.id,
            initialVariant: 0
        };
    },

    computed: {
        variantCriteria() {
            return new Criteria().addFilter(Criteria.multi(
                'OR',
                [
                    Criteria.equals('product.parentId', this.productId),
                    Criteria.equals('product.id', this.productId)
                ]
            )).addAssociation('options');
        }
    }
});
