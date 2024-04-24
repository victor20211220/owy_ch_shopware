import template from './sendinblue-index.html.twig';

const {Mixin} = Shopware;

Shopware.Component.register('sendinblue-index', {
    template,

    inject: ['ConnectionService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            setting: {
                connectionMessage: '',
                connectionIconName: '',
                connectionIconColor: '',
                mainButtonLink: '',
            },
            salesChannelId: null,
            isConnected: false,
            isLoading: true,
            mainButtonTitle: '',
            isAccountConfigLoading: true,
            userConnectionId: null
        };
    },

    created() {
        this.setMainButtonLink();
    },

    computed: {},

    methods: {
        setMainButtonLink() {
            this.ConnectionService.getConnectionSettingsLink(this.salesChannelId).then((response) => {
                if (response.success) {
                    this.isConnected = response.connected;
                    this.setting.mainButtonLink = response.link;
                } else {
                    this.createNotificationError({
                        title: this.$tc('sendinblue.settingForm.titleSaveError'),
                        message: `${response.error}`
                    });
                }

                this.isLoading = false;
                this.isAccountConfigLoading = false;
            })
        },

        onSalesChannelChanged(salesCHannelId) {
            this.isLoading = true;
            this.salesChannelId = salesCHannelId;
            this.setMainButtonLink();
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
});
