<?php
namespace AliorBank\Raty\Controller\Raty;

class Redirect extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \AliorBank\Raty\Helper\Data $aliorsHelper
    )
    {
        $this->aliorsHelper = $aliorsHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $session = $this->_objectManager->get('Magento\Checkout\Model\Session');
        $orderId = (int)$session->getLastRealOrderId();

        if ($orderId) {
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
            if (!$this->aliorsHelper->isOrderProcessable($order)){
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('checkout/cart');
                return $resultRedirect;
            }
            $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock('AliorBank\Raty\Block\Payment\Redirect')->getHtml($order)
            );
            $session->unsQuoteId();
        }
    }
}
