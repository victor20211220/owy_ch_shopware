import template from './rental-calendar-day.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.register('rental-calendar-day', {
    template,

    inject: [
        'repositoryFactory'
    ],

    props: {
        day: {
            type: Object,
            required: true
        },
        events: {
            type: Array,
            required: true,
            default: []
        },
        weekday: {
            type: Number,
            required: true
        },
        eventShowCount: {
            type: Number,
            required: true
        }
    },

    data() {
        return {
            showAllEventsModal: false,
            overviewColumns: [
                {
                    property: 'type',
                    label: this.$t('rental-calendar-form.event-overview-modal.type'),
                    rawData: true
                },
                {
                    property: 'comment',
                    label: this.$t('rental-calendar-form.event-overview-modal.comment'),
                    rawData: true
                },
                {
                    property: 'startDate',
                    label: this.$t('rental-calendar-form.event-overview-modal.startDate'),
                    rawData: true
                },
                {
                    property: 'endDate',
                    label: this.$t('rental-calendar-form.event-overview-modal.endDate'),
                    rawData: true
                }
            ],
        }
    },

    computed: {
        isCurrentDay() {
            const currentDay = new Date();

            return this.day.day === currentDay.getDate() && this.day.month === (currentDay.getMonth() + 1);
        },

        dayDate() {
            const dayDate = new Date(`${this.day.year}-${this.day.month}-${this.day.day}`);

            return dayDate;
        },

        showMore() {
            const hiddenEvents = this.events.filter(event => event.position === undefined);

            return hiddenEvents.length > 0;
        },

        filteredEvents() {
            return this.events.filter(event => event.position >= 1 && event.position <= this.eventShowCount).sort((a, b) => a.position - b.position);
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },
    },

    methods: {
        getEventClass(position) {
            const event = this.getEventAtPosition(position);

            if (!event) {
                return ['is-placeholder'];
            };

            return {
                'is-order-entry': event.type == 'rent',
                'is-block-entry': event.type == 'block',

                'is-period-start': this.dateMatchesDay(event.startDate),
                'is-period-end': this.dateMatchesDay(event.endDate)
            }
        },

        dateMatchesDay(dateString) {
            const date = new Date(dateString);

            return date.getFullYear() === this.dayDate.getFullYear() &&
                date.getMonth() === this.dayDate.getMonth() &&
                date.getDate() === this.dayDate.getDate();
        },

        showEventText(position) {
            const event = this.getEventAtPosition(position);

            if (!event) return false;

            return this.weekday === 0 || this.dateMatchesDay(event.startDate);

        },

        onShowMoreClick() {
            this.showAllEventsModal = true;
        },

        onCloseEventOverviewModal() {
            this.showAllEventsModal = false;
        },

        getEventParam(position, param) {
            const event = this.getEventAtPosition(position);

            if (!event) return null;

            return event[param];
        },

        getEventAtPosition(position) {
            return this.events.find(event => event.position === position);
        },

        onEventClick(position) {
            const event = this.getEventAtPosition(position);

            if (!event) return;

            if (event.type === 'rent') {
                this.openOrder(event.comment);
            } else if (event.type === 'block') {
                this.openBlockModal(event);
            }
        },

        async openOrder(orderNumber) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('orderNumber', orderNumber));

            const orderIds = await this.orderRepository.searchIds(criteria, Shopware.Context.api);

            if (orderIds.data[0]) {
                this.$router.push({
                    name: 'sw.order.detail',
                    params: {
                        id: orderIds.data[0]
                    }
                });
            }
        },

        openBlockModal(event = {}) {
            this.$emit('open-block-modal', event);
        },

        onEventMouseOver(position) {
            const event = this.getEventAtPosition(position);

            if (!event) return;

            this.$emit("mouse-over", event);
        },

        onEventMouseLeave(position) {
            const event = this.getEventAtPosition(position);

            if (!event) return;

            this.$emit("mouse-leave", event);
        }
    }
});