<?php
namespace RKFL\Rocketfuel\Model;
/**
 * Pay In Store payment method model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
     public const CODE = 'rocketfuel';
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'rocketfuel';
   
    protected $_canUseForMultishipping  = true;
    protected $_isOffline = true;
}
