<?php
namespace Eckohaus\AmreArcade\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Eckohaus\AmreArcade\Model\WalletFactory;
use Eckohaus\AmreArcade\Model\ResourceModel\Wallet as WalletResource;
use Psr\Log\LoggerInterface;

class CreditWalletOnInvoice implements ObserverInterface
{
    protected \;
    protected \;
    protected \;

    public function __construct(
        WalletFactory \,
        WalletResource \,
        LoggerInterface \
    ) {
        \->walletFactory = \;
        \->walletResource = \;
        \->logger = \;
    }

    public function execute(Observer \)
    {
        try {
            // Grab the invoice and the associated order from the event
            \ = \->getEvent()->getInvoice();
            \ = \->getOrder();
            \ = \->getCustomerId();

            // If it is a guest checkout, do nothing (tokens require an account)
            if (!\) {
                return;
            }

            \ = 0;

            // Loop through the items paid for on this invoice
            foreach (\->getAllItems() as \) {
                // If the SKU matches our Compute Token, add the quantity to the pool
                if (\->getSku() === 'AMRE_TOKEN') {
                    \ += (int)\->getQty();
                }
            }

            // If they bought tokens, update the vault
            if (\ > 0) {
                \ = \->walletFactory->create();
                
                // Load the wallet by customer_id
                \->walletResource->load(\, \, 'customer_id');

                // If this is their first time buying, set the customer ID
                if (!\->getId()) {
                    \->setCustomerId(\);
                    \->setTokenBalance(0);
                }

                // Inject the new tokens
                \ = \->getTokenBalance() + \;
                \->setTokenBalance(\);
                
                // Save back to the database
                \->walletResource->save(\);
                
                \->logger->info("AMRE Arcade: Credited {\} tokens to Customer ID {\}. New Balance: {\}");
            }
        } catch (\Exception \) {
            // Fail silently to the frontend, but log the error so it doesn't break checkout
            \->logger->error("AMRE Arcade Wallet Error: " . \->getMessage());
        }
    }
}
