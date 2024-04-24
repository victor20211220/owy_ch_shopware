import template from './rental-calendar-block-modal.html.twig';

Shopware.Component.register('rental-calendar-block-modal', {
    template,

    props: {
        event: {
            type: Object,
            required: true
        },
        maxValue: {
            type: Number,
            required: false
        }
    },

    data() {
        return {
            error: false,
            minValue: 1
        }
    },

    computed: {
        allowSave() {
            return this.event.startDate && this.event.endDate && this.event.quantity > 0 && !this.error;
        }
    },

    watch: {
        event: {
            handler() {
                if (!this.event.startDate || !this.event.endDate) return;

                this.$emit('modal-change', { "startDate": this.event.startDate, "endDate": this.event.endDate })
            }
        },

        maxValue: {
            handler() {
                if (this.maxValue <= 0) {
                    this.minValue = 0;
                    this.error = true;
                }
            }
        }
    },

    methods: {
        onCloseBlockModal() {
            this.$emit('modal-close')
        },

        deleteBlock() {
            this.$emit('delete-block', this.event)
        },

        saveBlock() {
            this.$emit('save-block', this.event)
        },
    }
});