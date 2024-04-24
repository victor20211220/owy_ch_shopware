import template from './acris-store-media-form.html.twig';
import './acris-store-media-form.scss';

const { Component, Mixin } = Shopware;

Component.extend('acris-store-media-form', 'sw-product-media-form', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        store: {
            type: Object,
            required: false
        },

        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        }
    },

    data() {
        return {
            showCoverLabel: true,
            isMediaLoading: false,
            columnCount: 5,
            columnWidth: 90,
            disabled: false
        };
    },

    computed: {
        mediaItems() {
            const mediaItems = this.storeMedia.slice();
            const placeholderCount = this.getPlaceholderCount(this.columnCount);

            if (placeholderCount === 0) {
                return mediaItems;
            }

            for (let i = 0; i < placeholderCount; i += 1) {
                mediaItems.push(this.createPlaceholderMedia(mediaItems));
            }
            return mediaItems;
        },

        cover() {
            if (!this.store) {
                return null;
            }
            const coverId = this.store.cover ? this.store.cover.mediaId : this.store.coverId;
            return this.store.media.find(media => media.id === coverId);
        },

        isLoading() {
            return this.isMediaLoading || this.isStoreLoading;
        },

        storeMediaRepository() {
            return this.repositoryFactory.create('acris_store_media');
        },

        storeMedia() {
            if (!this.store) {
                return [];
            }
            return this.store.media;
        },

        storeMediaStore() {
            return this.store.getAssociation('media');
        },

        gridAutoRows() {
            return `grid-auto-rows: ${this.columnWidth}`;
        },

        currentCoverID() {
            const coverMediaItem = this.storeMedia.find(coverMedium => coverMedium.media.id === this.store.coverId);

            return coverMediaItem.id;
        },
    },

    methods: {
        onOpenMedia() {
            this.$emit('media-open');
        },

        updateColumnCount() {
            this.$nextTick(() => {
                if (this.isLoading) {
                    return false;
                }

                const cssColumns = window.getComputedStyle(this.$refs.grid, null)
                    .getPropertyValue('grid-template-columns')
                    .split(' ');
                this.columnCount = cssColumns.length;
                this.columnWidth = cssColumns[0];

                return true;
            });
        },

        getPlaceholderCount(columnCount) {
            if (this.storeMedia.length + 3 < columnCount * 2) {
                columnCount *= 2;
            }

            let placeholderCount = columnCount;

            if (this.storeMedia.length !== 0) {
                placeholderCount = columnCount - ((this.storeMedia.length) % columnCount);
                if (placeholderCount === columnCount) {
                    return 0;
                }
            }

            return placeholderCount;
        },

        createPlaceholderMedia(mediaItems) {
            return {
                isPlaceholder: true,
                isCover: mediaItems.length === 0,
                media: {
                    isPlaceholder: true,
                    name: '',
                },
                mediaId: mediaItems.length.toString(),
            };
        },

        buildStoreMedia(mediaId) {
            this.isLoading = true;

            const storeMedia = this.storeMediaStore.create();
            storeMedia.mediaId = mediaId;

            if (this.storeMedia.length === 0) {
                storeMedia.position = 0;
                this.store.cover = storeMedia;
                this.store.coverId = storeMedia.id;
            } else {
                storeMedia.position = this.storeMedia.length + 1;
            }
            this.isLoading = false;

            return storeMedia;
        },

        successfulUpload({ targetId }) {
            // on replace
            if (this.store.media.find((storeMedia) => storeMedia.mediaId === targetId)) {
                return;
            }

            const storeMedia = this.createMediaAssociation(targetId);
            this.store.media.add(storeMedia);
        },

        createMediaAssociation(targetId) {
            const storeMedia = this.storeMediaRepository.create();

            storeMedia.storeId = this.store.id;
            storeMedia.mediaId = targetId;

            if (this.store.media.length <= 0) {
                storeMedia.position = 0;
                this.store.coverId = storeMedia.id;
            } else {
                storeMedia.position = this.store.media.length;
            }
            return storeMedia;
        },

        onUploadFailed(uploadTask) {
            const toRemove = this.store.media.find((storeMedia) => {
                return storeMedia.mediaId === uploadTask.targetId;
            });
            if (toRemove) {
                if (this.store.coverId === toRemove.id) {
                    this.store.coverId = null;
                }
                this.store.media.remove(toRemove.id);
            }
            this.store.isLoading = false;
        },

        removeCover() {
            this.store.cover = null;
            this.store.coverId = null;
        },

        isCover(storeMedia) {
            const coverId = this.store.cover ? this.store.cover.id : this.store.coverId;

            if (this.store.media.length === 0 || storeMedia.isPlaceholder) {
                return false;
            }

            return storeMedia.id === coverId;
        },

        removeFile(storeMedia) {
            // remove cover id if mediaId matches
            if (this.store.coverId === storeMedia.id) {
                this.store.cover = null;
                this.store.coverId = null;
            }

            if (this.store.coverId === null && this.store.media.length > 0) {
                this.store.coverId = this.store.media.first().id;
            }

            this.store.media.remove(storeMedia.id);
        },

        markMediaAsCover(storeMedia) {
            this.store.cover = storeMedia;
            this.store.coverId = storeMedia.id;

            this.store.media.moveItem(storeMedia.position, 0);
            this.updateMediaItemPositions();
        },

        onDropMedia(dragData) {
            if (this.store.media.find((storeMedia) => storeMedia.mediaId === dragData.id)) {
                return;
            }

            const storeMedia = this.createMediaAssociation(dragData.mediaItem.id);
            if (this.store.media.length === 0) {
                // set media item as cover
                storeMedia.position = 0;
                this.store.cover = storeMedia;
                this.store.coverId = storeMedia.id;
            }

            this.store.media.add(storeMedia);
        },

        onMediaItemDragSort(dragData, dropData, validDrop) {
            if (validDrop !== true
                || (dragData.id === this.store.coverId && dragData.position === 0)
                || (dropData.id === this.store.coverId && dropData.position === 0)) {
                return;
            }

            this.store.media.moveItem(dragData.position, dropData.position);

            this.updateMediaItemPositions();
        },

        updateMediaItemPositions() {
            this.storeMedia.forEach((medium, index) => {
                medium.position = index;
            });
        },
    },
});
