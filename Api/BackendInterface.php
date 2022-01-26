<?php

namespace Rocketfuel\Rocketfuel\Api;

interface BackendInterface
{


    /**
     * @return mixed
     * @api
     */
    public function postCallback();

    /**
     * get order payload for rocketfuel extension
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
}
