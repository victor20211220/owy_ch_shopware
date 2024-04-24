const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './owy-category-detail.html.twig';

Component.register('owy-category-detail', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],
    

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
    props: {
        CategoryId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            Category: null,
            isLoading: false,
            processSuccess: false,
            repository: null

        };
    },
    computed: {


        categoryRepository() {
            return this.repositoryFactory.create('photo_exchange_category');
        }
    },


    created() {
        this.repository = this.repositoryFactory.create('photo_exchange_category');
        this.getBundle();

    },

    methods: {

        getBundle() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.Category = entity;
                });
        },

        loadEntityData() {
            this.Category = this.Category.get(this.CategoryId, Shopware.Context.api).then((Category) => {
                this.Category = Category;
            });
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
                this.Category.isActive = false;
             }


            this.repository
                .save(this.Category, Shopware.Context.api)
                .then(() => {

                    this.getBundle();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        message: this.$tc('owy-category-module.detail.data.UpdateSuccessMessage'),
                    });
                    this.$router.push({ name: 'category.module.list'});
                }).catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('category-module.detail.data.error'),
                        message: exception
                    });
                });
        },

        saveFinish() {
            this.processSuccess = false;
        }
    }
});
