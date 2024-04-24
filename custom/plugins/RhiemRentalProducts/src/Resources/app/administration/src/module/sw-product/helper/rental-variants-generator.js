import VariantsGenerator from "../../../../../../../../../../../vendor/shopware/administration/Resources/app/administration/src/module/sw-product/helper/sw-products-variants-generator";

export default class RentalVariantsGenerator extends VariantsGenerator {
    constructor() {
        super();
    }

    processQueue(type, queue, offset, limit, resolve) {
        // Create a chunk
        const chunk = queue.slice(offset, offset + limit);
        if (chunk.length <= 0) {
            resolve();
            return;
        }

        // Emit the progress to the view
        this.emit('progress-actual', { type: type, progress: offset });

        // Add a new rentalProduct database entry for each variant
        if (type === "upsert" && this.product.extensions.rentalProduct) {
            chunk.forEach(variant => variant.rentalProduct = {
                "parentId": this.product.extensions.rentalProduct.id,
                "parentVersionId": this.product.extensions.rentalProduct.versionId,
                "originalStock": 0
            });
        }

        const payload = [{
            action: type,
            entity: 'product',
            payload: chunk
        }];

        if (type === "upsert" && !this.product.extensions.rentalProduct) {
            payload.push({
                action: type,
                entity: 'rental_product',
                payload: [{
                    active: false,
                    mode: 1,
                    purchasable: false,
                    originalStock: this.product.stock,
                    price: this.product.price,
                    taxId: this.product.taxId,
                    productId: this.product.id,
                    productVersionId: this.product.versionId,
                    bailActive: false,
                    bailPrice: [{"net": 100, "gross": 100, "linked": true, "currencyId": this.product.price[0].currencyId}],
                    bailTaxId: this.product.taxId
                }]
            });
        }

        // Send the payload to the server
        //const header = this.EntityStore.getLanguageHeader(Shopware.Context.api.languageId);
        //header['single-operation'] = 1;

        // Send the payload to the server
        const header = { 'single-operation': 1 };

        this.syncService.sync(payload, {}, header).then(() => {
            this.processQueue(type, queue, offset + limit, limit, resolve);
        });
    }
    //
    // processQueue(type, queue, offset, limit, resolve) {
    //     // Add a new rentalProduct database entry for each variant
    //     if (type === "upsert" && this.product.extensions.rentalProduct) {
    //         queue.forEach(variant => variant.rentalProduct = {
    //             "parentId": this.product.extensions.rentalProduct.id,
    //             "parentVersionId": this.product.extensions.rentalProduct.versionId
    //         });
    //     }
    //
    //     super.processQueue(type, queue, offset, limit, resolve);
    // }
}