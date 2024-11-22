/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'aliorbank_raty',
                component: 'AliorBank_Raty/js/view/payment/method-renderer/raty-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);