<?php

namespace Rocketfuel\Rocketfuel\Model;

use http\Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Rocketfuel\Rocketfuel\Api\BackendInterface;
use Magento\Framework\App\RequestInterface;
use Rocketfuel\Rocketfuel\Model\Rocketfuel;

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

    /**
     * Callback constructor.
     * @param RequestInterface $request
     * @param OrderInterface $order
     * @param \Rocketfuel\Rocketfuel\Model\Rocketfuel $rocketfuel
     */
    public function __construct(
        RequestInterface $request,
        OrderInterface $order,
        Rocketfuel $rocketfuel
    )
    {
        $this->rfService = $rocketfuel;
        $this->request = $request;
        $this->order = $order;
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
            //todo response
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
     *  get callback function
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
    protected function validateSignature($request)
    {
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
            'SHA256'
        )) {
            return $order;
        } else {
            //todo throw exception
            print_r('error');
            return false;
        }
    }

}
