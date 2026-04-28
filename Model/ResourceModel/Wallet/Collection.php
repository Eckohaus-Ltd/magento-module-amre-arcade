<?php
namespace Eckohaus\AmreArcade\Model\ResourceModel\Wallet;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected \ = 'entity_id';

    protected function _construct()
    {
        \->_init(
            \Eckohaus\AmreArcade\Model\Wallet::class,
            \Eckohaus\AmreArcade\Model\ResourceModel\Wallet::class
        );
    }
}
