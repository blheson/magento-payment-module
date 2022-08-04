<?php

namespace RKFL\Rocketfuel\Api;

interface BackendInterface
{
    /**
     * @return mixed
     * @api
     */
    public function postCallback();

    /**
     * Get order payload for rocketfuel extension
     *
     * @param $id
     * @return array|false|string
     */
    public function getRocketfuelPayload(int $id);

    /**
     * callback for test GET
     * @return mixed
     */
    public function getCallback();

    /**
     * callback for Update order
     * @return mixed
     */
    public function updateOrder();

     /**
     * callback for get Auth
     * @return mixed
     */
    public function getAuth();
    /**
     * callback for get UUID
     * @return mixed
     */
    public function getUUID();
    /**
     * callback for post UUID
     * @return mixed
     */
    public function postUUID();
     /**
     * Swap temporary orderId with New orderId
     * @return mixed
     */
    public function swapOrderId();
    
}