<?php
namespace Excellence\AbandonCart\Model\Config\Source;

class AbandonDuration implements \Magento\Framework\Option\ArrayInterface{
 public function toOptionArray(){
  return [
            ['value' => '1', 'label' => __('1 Hour')],
            ['value' => '2', 'label' => __('2 Hours')],
            ['value' => '3', 'label' => __('3 Hours')],
            ['value' => '4', 'label' => __('4 Hours')],
            ['value' => '5', 'label' => __('5 Hours')],
            ['value' => '6', 'label' => __('6 Hours')],
            ['value' => '7', 'label' => __('7 Hours')],
            ['value' => '8', 'label' => __('8 Hours')],
            ['value' => '9', 'label' => __('9 Hours')],
            ['value' => '10', 'label' => __('10 Hours')],
            ['value' => '11', 'label' => __('11 Hours')],
            ['value' => '12', 'label' => __('12 Hours')],
            ['value' => '13', 'label' => __('13 Hours')],
            ['value' => '14', 'label' => __('14 Hours')],
            ['value' => '15', 'label' => __('15 Hours')],
            ['value' => '16', 'label' => __('16 Hours')],
            ['value' => '17', 'label' => __('17 Hours')],
            ['value' => '18', 'label' => __('18 Hours')],
            ['value' => '19', 'label' => __('19 Hours')],
            ['value' => '20', 'label' => __('20 Hours')],
            ['value' => '21', 'label' => __('21 Hours')],
            ['value' => '22', 'label' => __('22 Hours')],
            ['value' => '23', 'label' => __('23 Hours')]
        ];
 }
}