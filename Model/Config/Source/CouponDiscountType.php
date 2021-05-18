<?php
namespace Excellence\AbandonCart\Model\Config\Source;
 
class CouponDiscountType implements \Magento\Framework\Option\ArrayInterface
{
    const BYPERCENTAGE = 'by_percent';
    const BYFIXED = 'by_fixed';
    const CARTFIXED = 'cart_fixed';
    const BUYXGETY = 'buy_x_get_y';
    
    public function toOptionArray()
    {
        return [['value' => NULL, 'label' => __('-- Select Page --')],
                ['value' => self:: BYPERCENTAGE, 'label' => __('Percent of product price discount')], 
                ['value' => self:: BYFIXED, 'label' => __('Fixed amount discount')],
                ['value' => self:: CARTFIXED, 'label' => __('Fixed amount discount for whole cart')], 
                ['value' => self:: BUYXGETY, 'label' => __('Buy X get Y free (discount amount is Y)')],
                ];            
    }
}