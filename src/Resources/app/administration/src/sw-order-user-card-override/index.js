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
            const mediaId = this[name];
            const DL = `${window.location.origin}/store-api/v3/files/download?mediaId=${mediaId}`;
            downloadFile(DL);
        }
    }
});

function downloadFile(filePath){
    var link=document.createElement('a');
    link.href = filePath;
    link.download = filePath.substr(filePath.lastIndexOf('/') + 1);
    link.click();
}
