<?php
namespace Eckohaus\AmreArcade\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Eckohaus\AmreArcade\Model\WalletFactory;
use Eckohaus\AmreArcade\Model\ResourceModel\Wallet as WalletResource;
use Psr\Log\LoggerInterface;

class CreditWalletOnInvoice implements ObserverInterface
{
    protected $walletFactory;
    protected $walletResource;
    protected $logger;

    public function __construct(
        WalletFactory $walletFactory,
        WalletResource $walletResource,
        LoggerInterface $logger
    ) {
        $this->walletFactory = $walletFactory;
        $this->walletResource = $walletResource;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();
            $customerId = $order->getCustomerId();

            if (!$customerId) {
                return;
            }

            $tokensToAdd = 0;

            foreach ($invoice->getAllItems() as $item) {
                if ($item->getSku() === 'AMRE_TOKEN') {
                    $tokensToAdd += (int)$item->getQty();
                }
            }

            if ($tokensToAdd > 0) {
                $wallet = $this->walletFactory->create();
                
                $this->walletResource->load($wallet, $customerId, 'customer_id');

                if (!$wallet->getId()) {
                    $wallet->setCustomerId($customerId);
                    $wallet->setTokenBalance(0);
                }

                $newBalance = $wallet->getTokenBalance() + $tokensToAdd;
                $wallet->setTokenBalance($newBalance);
                
                $this->walletResource->save($wallet);
                
                $this->logger->info("AMRE Arcade: Credited {$tokensToAdd} tokens to Customer ID {$customerId}. New Balance: {$newBalance}");
            }
        } catch (\Exception $e) {
            $this->logger->error("AMRE Arcade Wallet Error: " . $e->getMessage());
        }
    }
}