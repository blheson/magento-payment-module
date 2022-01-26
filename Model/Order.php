<?php

namespace Rocketfuel\Rocketfuel\Model;

use Rocketfuel\Rocketfuel\Model\Rocketfuel;

class Order extends \Magento\Sales\Block\Order\Totals
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;

    /**
     * Order constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Rocketfuel $rocketfuel
     * @param array $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        Rocketfuel $rocketfuel,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->rfService = $rocketfuel;
    }

    /**
     * get order entity
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId());
    }

    /**
     * get active logged in user customer id
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }

    /**
     *  get iframe url from settings
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->rfService->getIframeUrl();
    }

    /**
     * Return payment method title for specific order Id
     */
    public function getPaymentCode()
    {
        return 'RocketFuel';
        //$order = $this->getOrder();
        //$payment = $order->getPayment();
        //$method = $payment->getMethodInstance();
        //echo $method->getTitle();
        //return $method->getCode();
    }

    /**
     * check is order pay,ent is rocketfuel and status processing
     *
     * @return bool
     */
    public function isNotPayed()
    {
        return (($this->getPaymentCode() === 'rocketfuel') & ($this->getOrder()->getStatus() === 'processing'));
    }
}
