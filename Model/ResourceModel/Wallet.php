<?php
namespace Eckohaus\AmreArcade\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Wallet extends AbstractDb
{
    protected function _construct()
    {
        // Points to the table name and the primary key column
        \->_init('eckohaus_amre_wallet', 'entity_id');
    }
}
