<?php
namespace Excellence\AbandonCart\Model;

class AbandontCart extends \Magento\Framework\Model\AbstractModel 
{
    protected function _construct(){
        $this->_init('Excellence\AbandonCart\Model\ResourceModel\AbandontCart');
    }
}
