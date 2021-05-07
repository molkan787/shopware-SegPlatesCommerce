import template from './sw-order-user-card.html.twig';
import { extension } from 'mime-types';

Shopware.Component.override('sw-order-user-card', {
    template,
    computed: {
        proof_identity(){
            const o = this.currentOrder;
            return o && o.customFields && o.customFields.proof_of_identity;
        },
        proof_ownership(){
            const o = this.currentOrder;
            return o && o.customFields && o.customFields.proof_of_registration_ownership;
        }
    },
    methods: {
        downloadAttachment(name){
            const data = this[name];
            const mime = data.substr(5, 100).split(';')[0];
            console.log('mime', mime);
            const ext = extension(mime);
            console.log('ext', ext);
            const filename = `Order ${this.currentOrder.orderNumber} ${name}.${ext}`;
            downloadBase64File(data, filename);
        }
    }
});

function downloadBase64File(linkSource, fileName) {
    const downloadLink = document.createElement("a");
    downloadLink.href = linkSource;
    downloadLink.download = fileName;
    downloadLink.click();
}