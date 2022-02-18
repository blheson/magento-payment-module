<?php

namespace RKFL\Rocketfuel\Model;

class UrlList implements \Magento\Framework\Option\ArrayInterface
{
    protected $IFRAMES = [
        'https://iframe-stage1.rocketdemo.net',
        'https://iframe.rocketdemo.net',
        'https://iframe-stage2.rocketdemo.net',
        'https://iframe.rocketfuelblockchain.com'
    ];
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        $out = [];
        foreach ($this->IFRAMES as $iframe){
            $out[] = ['value' => $iframe, 'label' => $iframe];
        }
        return $out;
    }
     /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function envArray()
    {
        return array(
            'prod'=>'Production',
            'stage2'=>'QA',
            'dev'=>'Development',
        );
    }
}
