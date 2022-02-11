<?php

namespace RKFL\Rocketfuel\Model;

class EnvList implements \Magento\Framework\Option\ArrayInterface
{


    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return array(
            'prod'=>'Production',
            'stage2'=>'QA',
            'dev'=>'Development',
            'preprod'=>'Pre-Production',
        );
    }
}
