<?php

namespace RKFL\Rocketfuel\Api;

interface OrderInterface{

     /**
     * callback for get Auth
     * @return mixed
     */
    public function getAuth();
     /**
     * callback for get Uuid
     * @return mixed
     */
    public function getUuid();
    
}
