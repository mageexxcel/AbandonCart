<?php
namespace Excellence\AbandonCart\Model\Config\Source;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as rule;

class AllCouponCode implements \Magento\Framework\Option\ArrayInterface{

      /**
      * 
      */
      protected $_rule;

      public function __construct(
        rule $rule
      ){
        $this->_rule = $rule;
      }

      public function toOptionArray(){
            $options = [];
            $rules = $this->_rule->create()
                              ->load()
                              ->addFieldToFilter('code',array('neq' =>''));
            foreach($rules as $rule){
                  $options[$rule->getCode()] = __($rule->getCode());
            }
            return $options;
      }
}