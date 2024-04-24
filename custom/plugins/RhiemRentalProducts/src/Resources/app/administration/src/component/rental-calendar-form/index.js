import template from './rental-calendar-form.html.twig';
import './rental-calendar-form.scss';

const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Shopware.Component.register('rental-calendar-form', {
    template,

    props: {
        isDisabled: {
            type: Boolean,
            required: false,
            default: false
        },
    },

    data() {
        return {
            events: [],
            eventShowCount: 8,
            monthData: null,
            displayDate: {
                day: null,
                month: null,
                year: null,
                weekDay: null
            },
            selection: {
                mouseDown: false,
                startDate: null,
                endDate: null,
                comment: null,
            },
            showBlockModal: null,
            blockModalMaxQuantity: null,
            eventOverviewModal: {
                showEventOverviewModal: false,

                events: [],
                title: null,
                titlePrefix: this.$t('rental-calendar-form.event-overview-modal.header')
            }
        }
    },

    computed: {
        ...mapGetters('swProductDetail', [
            'isLoading'
        ]),

        ...mapState('swProductDetail', [
            'product',
            'parentProduct',
            'taxes',
            'currencies'
        ]),

        ...mapState('rhiemRentalProduct', [
            'rentalProduct'
        ]),

        blockedPeriods() {
            return this.rentalProduct.rentalTimes ?? [];
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },
    },

    watch: {
        isLoading: {
            handler() {
                if (!this.isLoading) {
                    this.createdComponent();
                }
            }
        }
    },

    created() {
        if (!this.isLoading) {
            this.createdComponent();
        }
    },

    methods: {
        createdComponent() {
            this.setInitialDates();
            this.blockModalMaxQuantity = this.rentalProduct.originalStock;
        },

        setInitialDates() {
            this.setDate(new Date());
        },

        setDate(date) {
            this.displayDate.day = date.getDate();
            this.displayDate.month = date.getMonth() + 1;
            this.displayDate.year = date.getFullYear();
            this.displayDate.weekDay = this.weekDayFormat(date.getDay());

            this.updateMonthData(this.displayDate.month, this.displayDate.year);
        },

        getEvents() {
            let events = [],
                orderLineItems = this.product.orderLineItems;

            orderLineItems.forEach(function (orderLineItem) {
                let rentalProduct = orderLineItem.payload.rentalProduct;

                if (typeof rentalProduct !== 'undefined') {
                    const event = rentalProduct.rentalTimePayload;
                    event.comment = orderLineItem.order.orderNumber;

                    events.push(event)
                }
            }.bind(this))

            events = events.concat(this.blockedPeriods);

            return this.assignUniqueIds(events);
        },

        updateMonthData(month, year) {
            let firstDateOfMonth = new Date(this.dateFormat(1, month, year)),
                daysOfCurrentMonth = this.getDaysOfMonth(month, year),
                lastDateOfMonth = new Date(this.dateFormat(
                    daysOfCurrentMonth[daysOfCurrentMonth.length - 1],
                    month,
                    year
                )),

                weekDayOfFirstDateOfMonth = this.weekDayFormat(firstDateOfMonth.getDay()),
                weekDayOfLastDateOfMonth = this.weekDayFormat(lastDateOfMonth.getDay()),

                previousMonth = this.getPreviousMonth(month, year),
                daysOfPreviousMonth = this.getDaysOfMonth(previousMonth.month, previousMonth.year),

                nextMonth = this.getNextMonth(month, year),
                daysOfNextMonth = this.getDaysOfMonth(nextMonth.month, nextMonth.year),

                daysFromPreviousMonth = this.getDaysFromPreviousMonth(daysOfPreviousMonth, weekDayOfFirstDateOfMonth),
                daysFromNextMonth = this.getDaysFromNextMonth(
                    daysOfNextMonth,
                    weekDayOfLastDateOfMonth,
                    daysFromPreviousMonth.length + daysOfCurrentMonth.length
                ),

                mergedMonthDays = this.mergeMonthDays(
                    daysFromPreviousMonth,
                    previousMonth,
                    daysOfCurrentMonth,
                    month,
                    year,
                    daysFromNextMonth,
                    nextMonth
                );

            this.monthData = this.formatMonthDays(mergedMonthDays);

            this.events = this.getEvents();
        },

        getDayEvents(day) {
            const dayDate = new Date(`${day.year}-${day.month}-${day.day}`)

            let events = this.events.filter(function (event) {
                const startDate = new Date(event.startDate);
                const endDate = new Date(event.endDate);

                return startDate <= dayDate && endDate >= dayDate;
            });

            const eventsWithPosition = events.filter(event => event.position !== undefined);

            if (eventsWithPosition.length >= this.eventShowCount) return events;

            for (let i = 1; i <= this.eventShowCount; i++) {
                if (this.eventAtPosition(events, i)) continue;

                let fittingEvent = this.getfirstStartingEvent(events, dayDate);

                if (fittingEvent) {
                    this.events.find(event => event.id === fittingEvent.id).position = i;
                }
            }

            return events;
        },

        eventAtPosition(events, position) {
            return events.filter(event => event.position === position).length > 0;
        },

        getfirstStartingEvent(events, date) {
            const startingEvents = events.filter(event => {
                const startDate = new Date(event.startDate);

                return this.onSameDay(startDate, date) && event.position === undefined;
            });

            if (startingEvents.length === 0) return null;

            return startingEvents[0];
        },

        onSameDay(date1, date2) {
            return date1.getDate() === date2.getDate() &&
                date1.getMonth() === date2.getMonth() &&
                date1.getFullYear() === date2.getFullYear();
        },

        assignUniqueIds(events) {
            let idCounter = 1;

            events.forEach(event => {
                event.id = idCounter;
                idCounter++;
            });

            return events;
        },

        createNewEvent() {
            return {
                mode: 1,
                productId: this.product.id,
                quantity: 1,
                timezone: "UTC",
                type: "block"
            }
        },

        calculateBlockModalMaxQuantity({ startDate, endDate }) {
            let maxQuantity = this.getMaxQuantity(startDate, endDate);

            this.blockModalMaxQuantity = this.rentalProduct.originalStock - maxQuantity;
        },

        openBlockModal(event) {
            this.showBlockModal = event;
        },

        onCloseBlockModal() {
            this.blockModalMaxQuantity = this.rentalProduct.originalStock;;
            this.showBlockModal = null;
        },

        onDeleteBlock(event) {
            let blockIndex = this.rentalProduct.rentalTimes.findIndex(blockEvent => blockEvent.id === event.id);
            this.rentalProduct.rentalTimes.splice(blockIndex, 1);
            this.createdComponent();
            this.onCloseBlockModal();
        },

        onSaveBlock(event) {
            if (!event.comment) {
                event.comment = this.$tc('rental-calendar-block-modal.commentPlaceholder');
            }

            this.rentalProduct.rentalTimes = this.rentalProduct.rentalTimes || [];
            this.rentalProduct.rentalTimes.push(event);

            this.createdComponent();
            this.onCloseBlockModal();
        },

        /**
         * @param {string} startDate
         * @param {string} endDate
         * @return {int}
         */
        getMaxQuantity(startDate, endDate) {
            let maxQuantity = 0;

            const events = this.selectEventsByDates(
                startDate,
                endDate
            );

            events.forEach(function (dates) {
                let tempQuantity = 0;

                events.forEach(function (block) {
                    if (block.startDate >= dates.startDate && block.startDate <= dates.endDate || block.endDate >= dates.startDate && block.endDate <= dates.endDate) {
                        tempQuantity += parseInt(block.quantity);
                    }
                }.bind(this))

                if (tempQuantity > maxQuantity) {
                    maxQuantity += tempQuantity;
                }
            }.bind(this))

            return maxQuantity;
        },

        /** 
         * @param {string} startDate
         * @param {string} endDate
         * @param {{}} events
         * @returns [{}]
         */
        selectEventsByDates(startDate, endDate) {
            startDate = new Date(startDate);
            endDate = new Date(endDate);
            let selectedEvents = [];

            this.events.forEach(function (block) {
                let blockFrom = new Date(block.startDate);
                let blockTo = new Date(block.endDate);

                if ((blockTo >= startDate && blockTo <= endDate) || (blockFrom >= startDate && blockFrom <= endDate)) {
                    selectedEvents.push(block)
                }
            })

            return selectedEvents;
        },

        onChangeMonth(direction = 1) {
            let month = this.displayDate.month,
                year = this.displayDate.year;

            if (direction === 1 && month === 12) {
                month = 1;
                year += 1;
            } else if (direction === -1 && month === 1) {
                month = 12;
                year -= 1;
            } else {
                month += direction;
            }

            this.displayDate.month = month;
            this.displayDate.year = year;

            this.updateMonthData(month, year);
        },

        /**
         * @param {number|string} day
         * @param {number|string} month
         * @param {number|string} year
         * @returns {string}
         */
        dateFormat(day, month, year) {
            let monthLeadingZero = ('0' + month).slice(-2);
            let dayLeadingZero = ('0' + day).slice(-2);
            return String(monthLeadingZero + '/' + dayLeadingZero + '/' + year);
        },

        /**
         * @param {number} month
         * @param {number} year
         * @returns {[]}
         */
        getDaysOfMonth(month, year) {
            let days = [];
            let date = new Date(this.dateFormat(1, month, year));

            while (date.getMonth() + 1 === month) {
                days.push(date.getDate());

                date.setDate(date.getDate() + 1);
            }

            return days;
        },

        /**
         * @param {number|string} weekDay
         * @returns {number}
         */
        weekDayFormat(weekDay) {
            if (parseInt(weekDay) === 0) {
                return 7;
            }

            return weekDay;
        },

        /**
         * @param {number|string} month
         * @param {number|string} year
         * @returns {{month: number, year: number}}
         */
        getNextMonth(month, year) {
            month = parseInt(month) + 1;

            if (month > 12) {
                return {
                    month: 1,
                    year: year + 1
                };
            }

            return {
                month,
                year
            };
        },

        /**
         * @param {number|string} month
         * @param {number|string} year
         * @returns {{month: number, year: number}}
         */
        getPreviousMonth(month, year) {
            month = parseInt(month) - 1;

            if (month < 1) {
                return {
                    month: 12,
                    year: year - 1
                };
            }

            return {
                month,
                year
            };
        },

        /**
         * @param {[]} days
         * @param {number} weekDay
         * @returns {[]}
         */
        getDaysFromPreviousMonth(days, weekDay) {
            if (weekDay === 1) {
                return [];
            }

            return days.slice((weekDay - 1) * -1);
        },

        /**
         * @param {[]} days
         * @param {number} weekDay
         * @param {number} daysSum
         * @returns {[]}
         */
        getDaysFromNextMonth(days, weekDay, daysSum) {
            let daysFromNextMonth = [];

            if (weekDay < 7) {
                daysFromNextMonth = days.splice(0, (7 - weekDay));
            }

            if (daysSum <= 28) {
                daysFromNextMonth = daysFromNextMonth.concat(days.splice(0, 7));
            }

            if (daysSum <= 35) {
                daysFromNextMonth = daysFromNextMonth.concat(days.splice(0, 7));
            }

            return daysFromNextMonth;
        },

        /**
         * @param {[]} daysOfPreviousMonth
         * @param {{}} previousMonthData
         * @param {[]} daysOfCurrentMonth
         * @param {number} currentMonth
         * @param {number} currentYear
         * @param {[]} daysOfNextMonth
         * @param {{}} nextMonthData
         * @returns {[]}
         */
        mergeMonthDays(
            daysOfPreviousMonth,
            previousMonthData,
            daysOfCurrentMonth,
            currentMonth,
            currentYear,
            daysOfNextMonth,
            nextMonthData
        ) {
            let mergedMonthDays = [];

            daysOfPreviousMonth.forEach((day) => {
                mergedMonthDays.push({
                    day,
                    month: previousMonthData.month,
                    year: previousMonthData.year,
                    events: []
                });
            });

            daysOfCurrentMonth.forEach((day) => {
                mergedMonthDays.push({
                    day,
                    month: currentMonth,
                    year: currentYear,
                    events: []
                });
            });

            daysOfNextMonth.forEach((day) => {
                mergedMonthDays.push({
                    day,
                    month: nextMonthData.month,
                    year: nextMonthData.year,
                    events: []
                });
            });

            return mergedMonthDays;
        },

        /**
         * @param {[]} monthDays
         * @returns {[]}
         */
        formatMonthDays(monthDays) {
            let formattedMonthDays = [];
            let weekDays;

            for (let i = 0; i < 6; i++) {
                weekDays = monthDays.splice(0, 7);

                formattedMonthDays.push(weekDays);
            }

            return formattedMonthDays;
        },

        toLocaleDateFormat(dateString) {
            const date = new Date(dateString);

            return date.toLocaleDateString();
        },

        /**
        * @param {{}} event
        */
        onEventMouseOver(event) {
            this.changeEventColor(this.getEventsById(event.id), 'highlight');
        },

        /**
         * @param {{}} event
         */
        onEventMouseLeave(event) {
            this.changeEventColor(this.getEventsById(event.id), 'normal');
        },

        /**
         * @param {NodeList} eventNodes
         * @param {string} mode
         */
        changeEventColor(eventNodes, mode) {
            eventNodes.forEach((eventNode) => {
                if (mode === 'highlight') {
                    eventNode.classList.add('highlighted');
                } else {
                    eventNode.classList.remove('highlighted');
                }
            });
        },

        /**
         * @param {string|number} id
         * @returns {NodeList}
         */
        getEventsById(id) {
            return document.querySelectorAll('[data-id="' + id + '"]');
        },






























































































        /**
         * switches date format startDate mm/dd/yyyy to yyyy/mm/dd
         * @param {Date} date
         */
        toStorageDateFormat(date) {
            let month = date.getMonth() + 1,
                day = date.getDate(),
                year = date.getFullYear(),
                hour = "00",
                minute = "00",
                second = "00";

            return String(year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second);
        },






































    }
});