<?php
namespace Eckohaus\AmreArcade\Model;

use Magento\Framework\Model\AbstractModel;

class Wallet extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Eckohaus\AmreArcade\Model\ResourceModel\Wallet::class);
    }
}