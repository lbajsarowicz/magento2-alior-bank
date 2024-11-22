<?php
namespace AliorBank\Raty\Block\Payment;

class Redirect extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;
    protected $aliorsHelper;
    protected $taxHelper;
    private $order;

    /**
     * Redirect constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \AliorBank\Raty\Helper\Data $aliorsHelper,
        \Magento\Catalog\Helper\Data $taxHelper,
        array $data = []
    ) {
        $this->formFactory = $formFactory;
        $this->aliorsHelper = $aliorsHelper;
        $this->taxHelper = $taxHelper;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        if (!$this->aliorsHelper->isActive() || !$this->order) {
            return '';
        }
        
        $this->order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, __('Waiting for payment.'));
        $this->order->save();

        $form = $this->formFactory->create();
        $form->setAction($this->aliorsHelper->getAliorUrl())
            ->setId('alior_raty_checkout')
            ->setName('alior_raty_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

        $hiddenFields = $this->getHiddenFields();

        foreach ($hiddenFields as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $form->toHtml() .
           '<script type="text/javascript">document.getElementById("alior_raty_checkout").submit();</script></body></html>';
    }

    public function getHtml($order)
    {
        $this->order = $order;
        return $this->_toHtml();
    }

    private function getHiddenFields()
    {
        $firstName = $this->order->getCustomerFirstname();
        $lastName = $this->order->getCustomerLastname();

        $partnerId = $this->aliorsHelper->getAliorsConfig('partnerid');
        $subpartnerId = $this->aliorsHelper->getAliorsConfig('subpartnerid');
        $mcc = $this->aliorsHelper->getAliorsConfig('mcc');
        $salt = $this->aliorsHelper->getAliorsConfig('salt');
        $calculatedIncome = '';
        $limit = '';
        $transactionCode = $this->order->getIncrementId();
        $total = $this->order->getGrandTotal();
        $promotion = $this->aliorsHelper->getPromotion($this->order->getAllVisibleItems(), false);

        $date = date('Y-m-d');
        $time = date('H:i:s');
        $dateAndTime = $date . 'T' . $time;

        $verificationCode = hash('sha256', $salt . $this->changeNumberFormat($total) .
            $transactionCode . $partnerId . $subpartnerId . $dateAndTime . $mcc . $firstName . $lastName . $limit . $calculatedIncome . $promotion);

        $array = $this->getAliorsArticlesListJson($this->order); //sepsite

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'partnerId' => $partnerId,
            'subPartnerId' => $subpartnerId,
            'mcc' => $mcc,
            'limit' => $limit,
            'promotion' => $promotion,
            'calculatedIncome' => $calculatedIncome,
            'verificationCode' => $verificationCode,
            'transactionCode' => $transactionCode,
            'dateAndTime' => $dateAndTime,
            'amount' => $this->changeNumberFormat($total),
            'articlesList' => base64_encode(json_encode($array)) // septsite.pl - change getItems to getAllVisibleItems
        ];
    }

    private function getAliorsArticlesListJson($order)
    {
        // @see https://stackoverflow.com/questions/42981409/php7-1-json-encode-float-issue
        // json_encode makes float values wrong. This is simple fix
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }
        $json = ['articlesList' => []];
        $discount = $order->getDiscountAmount();
        $totalShippingCost = $this->order->getShippingInclTax() ?? $this->order->getShippingAmount();
        $total = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $price = $this->getPrice($item);
            $total += $item->getQtyOrdered() * $price;

            $json['articlesList'][] = [
                "category" =>  $this->getAliorsCategory($item->getProduct()),
                "name" => $this->clearName($item->getName()),
                "number" => (int)$item->getQtyOrdered(),
                "price" => (int)($this->changeNumberFormat($price)*100)/100,
            ];
        }

        if ($totalShippingCost > 0) {
            $json['articlesList'][] = [
                "category" => 'TKC_USLUGI', // from Alior's docs
                "name" => 'Shipping costs',
                "number" => 1,
                "price" => (int)($this->changeNumberFormat($totalShippingCost)*100)/100,
            ];
            $total += $totalShippingCost;
        }

        if((int)$discount !== 0) {
            $json['articlesList'][] = [
                "category" => 'TKC_RABAT', // from Alior's docs
                "name" => 'Discount',
                "number" => 1,
                'price' => (int)($this->changeNumberFormat($discount)*100)/100,
            ];
            $total += $discount;
        }

        if($order->getGrandTotal() != $total) {
            $tax = $order->getGrandTotal() - $total;
            $json['articlesList'][] = [
                "category" =>  'TKC_USLUGI',
                "name" => 'Tax',
                "number" => 1,
                "price" => (float)$this->changeNumberFormat($tax),
            ];
        }
        return $json;
    }

    private function getOrdersCode($incrementId)
    {
        return strtoupper(substr(md5($incrementId), 0, 10));
    }

    /**
     * Returns Alior's ID of category based on mapping (see map.php)
     * @return string
     */
    private function getAliorsCategory($product)
    {
        $map = require(dirname(dirname(dirname(__FILE__))) . '/map.php');
        $default = !empty($map['default']) ? $map['default'] : '';
        if (empty($map)) {
            return '';
        }
        if (!$product) {
            return $default;
        }
        $ids = $product->getCategoryIds();
        if (empty($ids[0])) {
            return $default;
        }

        foreach ($map as $aliorId => $shopCats) {
            if (is_array($shopCats) && in_array($ids[0], $shopCats)) {
                return $aliorId;
            }
        }

        return $default;
    }
    
    private function changeNumberFormat($value)
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * @param $name
     * @return string
    */
    private function clearName($name) {
        $name = str_replace('|', '', $name);
        return preg_replace('/[\x00-\x1F]/', '', $name);
    }

    private function getPrice($item) {
        if($this->aliorsHelper->getConfig('tax/calculation/algorithm') === 'UNIT_BASE_CALCULATION') {
            return $item->getPriceInclTax();
        }
        return $item->getPrice();
    }
}
