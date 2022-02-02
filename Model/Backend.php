<?php

namespace Rocketfuel\Rocketfuel\Model;

use http\Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Rocketfuel\Rocketfuel\Api\BackendInterface;
use Magento\Framework\App\RequestInterface;
use Rocketfuel\Rocketfuel\Model\Rocketfuel;
use Rocketfuel\Rocketfuel\Model\Curl;
use Rocketfuel\Rocketfuel\Model\Order;

/**
 * @api
 */
class Backend extends \Magento\Framework\Model\AbstractModel implements BackendInterface
{
    const TESTING = true;

    protected $request_keys = [
        'type',
        'signature',
        'data'
    ];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var string
     */
    protected $merchantId;

    protected $rfService;
    protected $modelOrder;

    /**
     * Callback constructor.
     * @param RequestInterface $request
     * @param OrderInterface $order
     * @param Curl $curl
     * @param \Rocketfuel\Rocketfuel\Model\Rocketfuel $rocketfuel
     * @param \Rocketfuel\Rocketfuel\Model\Order $modelOrder
     */
    public function __construct(
        RequestInterface $request,
        OrderInterface $order,
        Curl $curl,
        Rocketfuel $rocketfuel,
        Order $modelOrder
    ) {
        $this->rfService = $rocketfuel;
        $this->request = $request;
        $this->order = $order;
        $this->curl = $curl;
        $this->modelOrder = $modelOrder;
    }


    /**
     * api callback endpoint
     *
     * @return mixed
     */
    public function postCallback()
    {

        $post = $this->validate($this->request->getPost());

        $order = $this->validateSignature($post);

        if ($order) {

            $order
                ->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)
                ->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);

            $order->save();

            //Todo response
            echo json_encode([
                'status' => 'ok'
            ]);

        } else {

            echo json_encode([
                'status' => 'error',
                'signature not valid'
            ]);

        }
    }

    /**
     *  Get callback function
     *  for check exists callback url
     */
    public function getCallback()
    {
        return json_encode([
            'callback_status' => 'ok'
        ]);
    }

    /**
     * get order payload for rocketfuel extension
     *
     * @param $id
     * @return array|false|string
     */
    public function getRocketfuelPayload(int $id)
    {
        return json_encode(
            $this->rfService->getOrderPayload(
                $this->order->load($id)
            )
        );
    }

    /**
     * validate post body
     *
     * @param $request
     * @return object
     */
    protected function validate($request)
    {
        foreach ($this->request_keys as $key) {
            if (!array_key_exists($key, $request)) {
                //todo throw exception
            };
        }
        return (object)$request;
    }

    /**
     * @param $request
     * @return int
     */
    protected function validateSignature($request){

        $public_key = openssl_pkey_get_public(
            file_get_contents(dirname(__FILE__) . '/../key/.rf_public.key')
        );
        $data = json_decode($request->data);

        $order = $this->order->load($data->offerId);
        //todo for testing
        //return $order;

        if (openssl_verify(
            json_encode($this->rfService->getOrderPayload($order)),
            base64_decode($request->signature),
            $public_key,
            'SHA256')
        ) {
            return $order;
        } else {
            //todo throw exception
            print_r('error');
            return false;
        }
    }

    /**
     * Update order post body
     *
     * @param $request
     * @return object
     */
    public function updateOrder(){

        $post = $this->validate($this->request->getPost());

        $order = $this->order->load($post->orderId);

        switch ($post->status) {
            case '101':
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
            case '-1':
                $status = \Magento\Sales\Model\Order::STATE_CANCELED;
                break;
            case '1':
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING; //Fix partial payment
            default:
                break;
        }

        $order
            ->setState($status)
            ->setStatus($status);
        $order->addStatusHistoryComment('Order has been automatically set from Rocketfuel plugin.', false);

        $order->save();

        echo json_encode(array('trea' =>   $post));
    }
    /**
     * Validate post body
     *
     * @param int $orderId
     * @return object
     */
    public function getAuth(){

        file_put_contents(__DIR__ . '/log.json', "\n" . 'Body Auth: ' . "\n" . 'Auth has been called' . "\n", FILE_APPEND);

        $result = $this->modelOrder->processOrderWithRKFL(1);

        echo json_encode(array('trea' =>   $result));
    }
}
