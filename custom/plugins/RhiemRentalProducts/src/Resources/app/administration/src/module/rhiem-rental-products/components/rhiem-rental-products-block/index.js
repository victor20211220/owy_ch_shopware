import template from './rhiem-rental-products-block.html.twig';
import DatePicker from 'v-calendar/lib/components/date-picker.umd'

const { Component } = Shopware;

Component.register('v-calendar', DatePicker);

Component.register('rhiem-rental-products-block', {
    template,

    props: {
        actualConfigData: {
            type: Object,
            required: true,
        },
        isLoading: {
            type: Boolean,
            required: true,
        }
    },

    data() {
        return {
            days: [],
            componentKey: 0,
            blockableDays: [
                {
                    value: 1,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.monday')
                },
                {
                    value: 2,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.tuesday')
                },
                {
                    value: 3,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.wednesday')
                },
                {
                    value: 4,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.thursday')
                },
                {
                    value: 5,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.friday')
                },
                {
                    value: 6,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.saturday')
                },
                {
                    value: 0,
                    label: this.$tc('rhiem-rental-products.config.blockedDays.blockableDays.sunday')
                },
            ],
        }
    },

    computed: {
        attributes() {
            return this.days.map(day => ({
                highlight: true,
                dates: day.date,
            }));
        },
    },

    mounted() {
        this.days = this.actualConfigData['RhiemRentalProducts.config.blockedDays'] ?? [];
    },

    methods: {
        onDayClick(day) {
            const idx = this.days.findIndex(d => d.id === day.id);

            if (idx >= 0) {
                this.days.splice(idx, 1);
            } else {
                this.days.push({
                    id: day.id,
                    date: day.date,
                });
            }

            this.actualConfigData['RhiemRentalProducts.config.blockedDays'] = this.days;

            this.componentKey += 1;
        },
    }
});
