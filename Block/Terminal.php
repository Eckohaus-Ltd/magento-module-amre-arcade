<?php
namespace Eckohaus\AmreArcade\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Eckohaus\AmreArcade\Model\WalletFactory;
use Eckohaus\AmreArcade\Model\ResourceModel\Wallet as WalletResource;

class Terminal extends Template
{
    protected $customerSession;
    protected $walletFactory;
    protected $walletResource;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        WalletFactory $walletFactory,
        WalletResource $walletResource,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->walletFactory = $walletFactory;
        $this->walletResource = $walletResource;
        parent::__construct($context, $data);
    }

    /**
     * Retrieves the current logged-in customer's AMRE token balance.
     * Returns '0.00' if no wallet exists or user is not logged in.
     */
    public function getTokenBalance()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return '0.00';
        }

        $customerId = $this->customerSession->getCustomerId();
        $wallet = $this->walletFactory->create();
        $this->walletResource->load($wallet, $customerId, 'customer_id');

        $balance = $wallet->getTokenBalance();
        
        return $balance ? number_format((float)$balance, 2, '.', '') : '0.00';
    }
}