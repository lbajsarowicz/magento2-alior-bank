<?php

namespace AliorBank\Raty\Block\Cart;

use AliorBank\Raty\Helper\Data;

class CartView
{
    public $promotion;
    public $isValid;
    protected $aliorsHelper;
    protected $quote;

    public function __construct(Data $aliorsHelper) {
        $this->aliorsHelper = $aliorsHelper;
        $this->quote = $this->getQuote();
        $this->isValid = $this->init();
    }

    protected function init()
    {
        if (empty($this->quote)){
            return false;
        }
        if (
            !$this->aliorsHelper->isActive() ||
            !$this->aliorsHelper->isValidAmount($this->aliorsHelper->getQuoteTotal($this->quote)) ||
            !$this->aliorsHelper->isValidCurrency($this->quote->getCurrency())
        ){
            return false;
        }
        $this->promotion = $this->getPromotion();
        if(!$this->promotion) {
            return false;
        }
        
        return true;
    }

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    private function getQuote() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        return $cart->getQuote();
    }

    private function getPromotion() {
        return $this->aliorsHelper->getPromotion($this->getQuote()->getItems(), false);
    }

    public function getPartnerId() {
        return $this->aliorsHelper->getAliorsConfig('partnerid');
    }

    public function getKalkulatorUrl() {
        return $this->aliorsHelper->getKalkulatorUrl();
    }
}