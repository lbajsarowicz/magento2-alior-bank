<?php
namespace AliorBank\Raty\Helper;

use \Magento\Sales\Model\Order;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SUPPORTED_CURRENCY = 'PLN';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function isActive()
    {
        return (bool) $this->getAliorsConfig('active');
    }

    public function isValidAmount($total)
    {
        $minTotal = (float) $this->getAliorsConfig('minOrderTotal');
        if ($minTotal > 0) {
            return $minTotal <= $total;
        }

        return true;
    }

    /**
     * @param $curreny \Magento\Quote\Api\Data\CurrencyInterface|null
     */
    public function isValidCurrency($currency)
    {
        if (!$currency || $currency->getQuoteCurrencyCode() !== self::SUPPORTED_CURRENCY){
            return false;
        }

        return true;
    }

    public function getAliorsConfig($name)
    {
        return $this->getConfig('payment/aliorbank_raty/' . $name);
    }

    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isOrderProcessable(Order $order)
    {
        return !in_array($order->getStatus(), [
            Order::STATE_COMPLETE,
            Order::STATE_CLOSED,
            Order::STATE_CANCELED,
            Order::STATE_HOLDED,
            Order::STATUS_FRAUD,
        ]);
    }

    public function getQuoteTotal($quote)
    {
        $total = $quote->getGrandTotal();
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress){
            return $total;
        }

        return $total + $shippingAddress->getShippingAmount();
    }
}