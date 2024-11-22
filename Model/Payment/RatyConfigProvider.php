<?php
namespace AliorBank\Raty\Model\Payment;

class RatyConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->method = $paymentHelper->getMethodInstance('aliorbank_raty');
        $this->storeManager = $storeManager;
    }


    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'aliorbank_raty' => [
                    'title' => $this->getTitle(),
                    'description' => $this->getDescription(),
                    'redirectUrl' => $this->getRedirectUrl(),
                    'logoUrl' => $this->getLogoUrl(),
                ],
            ],
        ] : [];
    }

    protected function getTitle()
    {
        return $this->method->getTitle();
    }

    protected function getDescription()
    {
        return $this->method->getDescription();
    }

    protected function getRedirectUrl()
    {
        return $this->method->getOrderPlaceRedirectUrl();
    }

    protected function getLogoUrl()
    {
        return $this->method->getLogoUrl();
    }

}
