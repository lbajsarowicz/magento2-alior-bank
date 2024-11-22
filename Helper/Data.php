<?php
namespace AliorBank\Raty\Helper;

use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class Data extends AbstractHelper
{
    const SUPPORTED_CURRENCY = 'PLN';
    const URL = 'https://raty.aliorbank.pl/directcreditsystem-frontend-consumerfinance-internet-standard/paymentprovider/';
    const kalkualtorUrl = 'https://kalkulator.raty.aliorbank.pl/';
    protected CategoryRepositoryInterface $categoryRepository;

    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, CategoryRepositoryInterface $categoryRepository)
    {
        $this->scopeConfig = $scopeConfig;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context);
    }

    public function isActive()
    {
        return (bool) $this->getAliorsConfig('active');
    }

    public function showCalc()
    {
        return (bool) $this->getAliorsConfig('showCalc');
    }

    public function isValidAmount($total)
    {
        $minTotal = (float) $this->getAliorsConfig('minOrderTotal');
        $maxTotal = (float) $this->getAliorsConfig('maxOrderTotal');
        if ($minTotal > 0 && $maxTotal > 0) {
            return $minTotal <= $total && $maxTotal >= $total;
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
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }

    public function getAliorUrl() {
        return self::URL;
    }

    public function getKalkulatorUrl() {
        return self::kalkualtorUrl;
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

    public function getPromotion($products, $isProduct = true) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $todayDate = date("Y/m/d");
        $promotionsType = [];
        $isProductPromotionValid = true;
        $isCategoryPromotionValid = true;

        if(
            !$this->getAliorsConfig('product_promotion_start') ||
            $this->formatDate($this->getAliorsConfig('product_promotion_start')) > $todayDate ||
            $this->formatDate($this->getAliorsConfig('product_promotion_end')) < $todayDate
        ) {
            $isProductPromotionValid = false;
        }

        if(
            !$this->getAliorsConfig('category_promotion_start') ||
            $this->formatDate($this->getAliorsConfig('category_promotion_start')) > $todayDate ||
            $this->formatDate($this->getAliorsConfig('category_promotion_end')) < $todayDate
        ) {
            $isCategoryPromotionValid = false;
        }

        foreach($products as $product) {
            array_push($promotionsType, $this->getProductPromotion($product, $isProduct, $isProductPromotionValid, $isCategoryPromotionValid, $objectManager));
        }
        
        $promotion = explode('${sep}', $this->getAliorsConfig($promotionsType[0] .'_promotion'));
        if(count($promotionsType) > 1) {
            for($i = 1; $i < count($promotionsType); $i++) {
                $promotionType = explode('${sep}', $this->getAliorsConfig($promotionsType[$i] .'_promotion'));
                $promotion = array_intersect($promotion, $promotionType);
            }
        }
        return implode('${sep}', $promotion);
    }

    private function formatDate($date){
        if(!$date){
            return 0;
        }
        return (new \DateTime($date))->format('Y/m/d');
    }

    private function checkCategoryPromotion($product, $objectManager) {
        $categories = $product->getCategoryIds();
        foreach($categories as $category){
            $category = $objectManager->create('Magento\Catalog\Model\Category')->load($category);
            if($category->getData('aliorbank_category_promotion')) {
                return true;
            }
        }
        return false;
    }

    private function getProductPromotion($product, $isProduct, $isProductPromotionValid, $isCategoryPromotionValid, $objectManager) {
        if(!$isProduct) {
            $productId= $product->getProduct()->getId();
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        }
        if($isProductPromotionValid && $product->getData('aliorbank_product_promotion')) {
            return 'product';
        }
        if($isCategoryPromotionValid && $this->checkCategoryPromotion($product, $objectManager)) {
            return 'category';
        }
        return 'standard';
    }
}