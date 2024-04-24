import template from './sw-text-editor.html.twig'

Shopware.Component.override('sw-text-editor', {
    template,
    data() {
        return {
            mediaModalIsOpen: false,
            lastSelectionRange: null
        }
    },
    created() {
        this.buttonConfig.push({
            title: 'Media',
            icon: 'regular-image',
            position: 'left',
            handler: () => {
                this.mediaModalIsOpen = true
            }
        });
    },
    mounted() {
        document.addEventListener('selectionchange', this.onDocumentSelectionChange);
    },
    unmouted() {
        document.removeEventListener('selectionchange', this.onDocumentSelectionChange);
    },
    methods: {
        onModalClosed(selection) {
            if (selection.length) {
                this.restoreSelectionRange();
                const media = selection[0]
                document.execCommand('insertHTML', false, '<img src="' + media.url + '" class="img-fluid cms-image" alt="' + media.alt + '" title="' + media.title +'" />')
            }
        },
        onDocumentSelectionChange() {
            this.saveSelectionRange();
        },
        getContentValue() {
            if (!this.$refs.textEditor || !this.$refs.textEditor.innerHTML) {
                return null;
            }

            // do not return null if there are only images in this editor
            if (!this.$refs.textEditor.getElementsByTagName('img') &&
                (
                !this.$refs.textEditor.textContent ||
                !this.$refs.textEditor.textContent.length ||
                this.$refs.textEditor.textContent.length <= 0
                )
            ) {
                return null;
            }

            return this.$refs.textEditor.innerHTML;
        },
        saveSelectionRange() {
            if (this.isActive) {
                const selection = document.getSelection();
                console.log('saveSelectionRange', this, selection);
                if (selection.rangeCount > 0) {
                    this.lastSelectionRange = selection.getRangeAt(0).cloneRange();
                }else{
                    this.lastSelectionRange = null;
                }
            }
        },
        restoreSelectionRange() {
            if (!this.lastSelectionRange) {
                return;
            }
            this.$refs.textEditor.focus();
            const selection = document.getSelection();
            selection.removeAllRanges();
            selection.addRange(this.lastSelectionRange);
        },
    }
});

