<?php
namespace AliorBank\Raty\Block\Cart;

class Info extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \AliorBank\Raty\Helper\Data $aliorsHelper,
        array $data = []
    ) {
        $this->aliorsHelper = $aliorsHelper;
        parent::__construct($context, $data);
    }

    public function getCalculatorUrl()
    {
        $quote = $this->getQuote();
        if (empty($quote)){
            return '';
        }
        $promotion = $this->aliorsHelper->getAliorsConfig('promotion');
        $baseUrl = $this->aliorsHelper->getAliorsConfig('kalkulator');
        
        return $baseUrl ?
            ($baseUrl . '?installmentNumber=21&offerCode=' . $promotion . '&cartValue=' . $this->aliorsHelper->getQuoteTotal($quote)) :
            '';
    }

    protected function _toHtml()
    {
        $h = $this->aliorsHelper;
        $quote = $this->getQuote();
        if (empty($quote)){
            return '';
        }
        if (
            !$h->isActive() ||
            !$h->isValidAmount($h->getQuoteTotal($quote)) ||
            !$h->isValidCurrency($quote->getCurrency()) ||
            !$this->getCalculatorUrl()
        ){
            return '';
        }
        
        return parent::_toHtml();
    }

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    private function getQuote()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        return $cart->getQuote();
    }
}