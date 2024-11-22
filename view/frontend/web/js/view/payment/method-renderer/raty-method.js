/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/translate',
        'jquery'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'AliorBank_Raty/payment/raty-form'
            },
            getCode: function () {
                return 'aliorbank_raty';
            },
            getTitle: function () {
                return window.checkoutConfig.payment.aliorbank_raty.title;
            },
            getDescription: function () {
                return window.checkoutConfig.payment.aliorbank_raty.description;
            },
            getLogoUrl: function () {
                return window.checkoutConfig.payment.aliorbank_raty.logoUrl;
            },
            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.payment.aliorbank_raty.redirectUrl);
            }
        });
    }
);