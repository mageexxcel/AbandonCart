<?php
namespace Excellence\AbandonCart\Model\Config\Source;
 
class SendCoupon implements \Magento\Framework\Option\ArrayInterface
{
    const FIRST = 0;
    const SECOND = 1;
    
    public function toOptionArray()
    {
        return [['value' => NULL, 'label' => __('-- Select Page --')],
                ['value' => self:: FIRST, 'label' => __('first Mail Send')], 
                ['value' => self:: SECOND, 'label' => __('Second Mail Send')],
                ];            
    }
}