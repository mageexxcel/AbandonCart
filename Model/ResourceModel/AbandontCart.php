<?php
namespace Excellence\AbandonCart\Model\ResourceModel;
class AbandontCart extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct(){
        $this->_init('excellence_abandontcart_main','abandont_cart_id');
    }
}
