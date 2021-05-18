<?php
namespace Excellence\AbandonCart\Model\ResourceModel\AbandontCart;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Excellence\AbandonCart\Model\AbandontCart','Excellence\AbandonCart\Model\ResourceModel\AbandontCart');
    }
}
