<?php
namespace Eckohaus\AmreArcade\Model\ResourceModel\Wallet;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Eckohaus\AmreArcade\Model\Wallet::class,
            \Eckohaus\AmreArcade\Model\ResourceModel\Wallet::class
        );
    }
}