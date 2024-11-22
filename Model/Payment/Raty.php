<?php
namespace AliorBank\Raty\Model\Payment;

// version 0.1.1

class Raty extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'aliorbank_raty';

    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;

    private $storeId;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $_storeManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \AliorBank\Raty\Helper\Data $aliorsHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
        $this->_storeManager = $storeManager;
        $this->storeId = $this->_storeManager->getStore()->getStoreId();
        $this->aliorsHelper = $aliorsHelper;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('aliorbank/raty/redirect', array('noCache' => uniqid(true)));
    }

    public function getCheckout()
    {
        return $this->objectManager->get('Magento\Checkout\Model\Session');
    }

    public function getLogoUrl()
    {
        $assetRepository = $this->objectManager->get('Magento\Framework\View\Asset\Repository');
        return $assetRepository->createAsset('AliorBank_Raty::images/logo.png')->getUrl();
    }

    public function getTitle()
    {
        return $this->scopeConfig->getValue('payment/aliorbank_raty/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getDescription()
    {
        return $this->scopeConfig->getValue('payment/aliorbank_raty/text', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $h = $this->aliorsHelper;
        if (!$h->isActive()){ // module's status
            return false;
        }
        if (empty($quote)){
            return parent::isAvailable($quote);
        }
        if (!$h->isValidCurrency($quote->getCurrency())){
            return false;
        }
        if (!$h->isValidAmount($h->getQuoteTotal($quote))){
            return false;    
        }

        return parent::isAvailable($quote);
    }
}
