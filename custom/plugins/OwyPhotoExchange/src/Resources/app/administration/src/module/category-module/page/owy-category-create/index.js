

const { Component } = Shopware;

Component.extend('owy-category-create', 'owy-category-detail', {
    methods: {
        
        getBundle() {
            Shopware.Context.api.fromCreate = true;
            this.Category = this.repository.create(Shopware.Context.api);
        },

        onClickSave() {
            
            this.isLoading = true;
            if(this.Category.name == null || this.Category.name == ""){
                this.createNotificationError({
                    message: this.$tc('owy-category-module.detail.data.requiredField'),
                });
                this.isLoading = false;

                return;
            }
            

            if(this.Category.isActive == null || this.Category.isActive == ""){
                this.createNotificationError({
                    message: this.$tc('owy-category-module.detail.data.requiredField'),
                });
                this.isLoading = false;
                return;
            }
            

            this.repository
                .save(this.Category, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        message: this.$tc('owy-category-module.detail.data.successMessage'),
                    });
                    this.$router.push({ name: 'category.module.list'});
                }).catch((exception) => {
                    this.isLoading = false;

                    this.createNotificationError({
                        title: this.$t('owy-category-module.detail.data.error'),
                        message: exception
                    });
                });
        }
    }
});