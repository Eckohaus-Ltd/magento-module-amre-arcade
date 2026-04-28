<?php
namespace Eckohaus\AmreArcade\Model;

use Magento\Framework\Model\AbstractModel;

class Wallet extends AbstractModel
{
    protected function _construct()
    {
        \->_init(\Eckohaus\AmreArcade\Model\ResourceModel\Wallet::class);
    }
}
