import template from './sw-order-lineitem.html.twig';

Shopware.Component.override('sw-order-line-items-grid', {
    template,
    computed: {
        regNo(){
            const p = this.item && this.item.payload;
            return p && p.customFields && p.customFields.registration_no;
        } // item && item.payload && item.payload.customFields && item.payload.customFields.registration_no
    }
});