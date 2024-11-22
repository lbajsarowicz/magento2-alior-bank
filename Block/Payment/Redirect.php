<?php
namespace AliorBank\Raty\Block\Payment;

class Redirect extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;
    protected $aliorsHelper;
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
        array $data = []
    ) {
        $this->formFactory = $formFactory;
        $this->aliorsHelper = $aliorsHelper;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        if (!$this->aliorsHelper->isActive() || !$this->order) {
            return '';
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, __('Waiting for payment.'));
        $this->order->save();

        $form = $this->formFactory->create();
        $form->setAction($this->aliorsHelper->getAliorsConfig('url'))
            ->setId('alior_raty_checkout')
            ->setName('alior_raty_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

        foreach ($this->getHiddenFields() as $field => $value) {
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

        $url = $this->aliorsHelper->getAliorsConfig('url');
        $partnerId = $this->aliorsHelper->getAliorsConfig('partnerid');
        $subpartnerId = $this->aliorsHelper->getAliorsConfig('subpartnerid');
        $mcc = $this->aliorsHelper->getAliorsConfig('mcc');
        $promotion = $this->aliorsHelper->getAliorsConfig('promotion');
        $salt = $this->aliorsHelper->getAliorsConfig('salt');
        $calculatedIncome = '';
        $limit = '';
        $transactionCode = $this->getOrdersCode($this->order->getIncrementId());
        $total = $this->order->getGrandTotal();

        $date = date('Y-m-d');
        $time = date('H:i:s');
        $dateAndTime = $date . 'T' . $time;

        $verificationCode = hash('sha256', $salt . $this->changeNumberFormat($total) .
            $transactionCode . $partnerId . $subpartnerId . $dateAndTime . $mcc . $firstName . $lastName . $limit . $calculatedIncome . $promotion);
        $shipping = $this->order->getShippingInclTax() ?? $this->order->getShippingAmount();

        //$array = $this->getAliorsArticlesListJson($this->order->getItems(), $shipping);
        $array = $this->getAliorsArticlesListJson($this->order->getAllVisibleItems(), $shipping); //sepsite

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
            'articlesList' => base64_encode(json_encode(
                $this->getAliorsArticlesListJson($this->order->getAllVisibleItems(), $shipping) // septsite.pl - change getItems to getAllVisibleItems
            ))
        ];
    }

    private function getAliorsArticlesListJson($items, $totalShippingCost)
    {
        // @see https://stackoverflow.com/questions/42981409/php7-1-json-encode-float-issue
        // json_encode makes float values wrong. This is simple fix
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }

        $json = ['articlesList' => []];
        foreach ($items as $item) {
            $price = ($item->getRowTotal()-$item->getDiscountAmount()+$item->getTaxAmount()+$item->getDiscountTaxCompensationAmount())/$item->getQtyOrdered();
            $price = round($price, 2);

            //check price
            $priceRowTotalInclTax = $item->getRowTotalInclTax() - $item->getDiscountAmount();
            $checkPrice = $price * $item->getQtyOrdered();

            if ($checkPrice != $priceRowTotalInclTax && $item->getQtyOrdered() > 1) {
                $difference = $priceRowTotalInclTax - $checkPrice;
                $priceFix = round(($price + $difference), 2);
                $iloscFrist = $item->getQtyOrdered() -1;

                $json['articlesList'][] = [
                    "category" => $this->getAliorsCategory($item->getProduct()),
                    "name" => $item->getName(),
                    "number" => (int)$iloscFrist,
                    "price" => $price,
                    ];
                
                $json['articlesList'][] = [
                    "category" => $this->getAliorsCategory($item->getProduct()),
                    "name" => $item->getName(),
                    "number" => 1,
                    "price" => $priceFix,
                    ];
            } else {
                $json['articlesList'][] = [
                "category" => $this->getAliorsCategory($item->getProduct()),
                "name" => $item->getName(),
                "number" => (int)$item->getQtyOrdered(),
                "price" => $price,
                ];
            }
        }
        if ($totalShippingCost) {
            $json['articlesList'][] = [
                "category" => 'TKC_USLUGI', // from Alior's docs
                "name" => 'Shipping costs',
                "number" => 1,
                "price" => (int)($this->changeNumberFormat($totalShippingCost)*100)/100,
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
}
