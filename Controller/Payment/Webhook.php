<?php
namespace Eckohaus\AmreArcade\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Eckohaus\AmreArcade\Model\WalletFactory;
use Eckohaus\AmreArcade\Model\ResourceModel\Wallet as WalletResource;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;

class Webhook extends Action implements CsrfAwareActionInterface
{
    protected $scopeConfig;
    protected $walletFactory;
    protected $walletResource;
    protected $encryptor;
    protected $logger;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        WalletFactory $walletFactory,
        WalletResource $walletResource,
        EncryptorInterface $encryptor,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->walletFactory = $walletFactory;
        $this->walletResource = $walletResource;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException { return null; }
    public function validateForCsrf(RequestInterface $request): ?bool { return true; }

    public function execute()
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $this->getRequest()->getHeader('Stripe-Signature');
        
        // Retrieve AND Decrypt the Webhook Secret
        $encryptedSecret = $this->scopeConfig->getValue('amre_arcade/stripe/webhook_secret');
        $endpointSecret = $this->encryptor->decrypt($encryptedSecret);

        $signatureParts = explode(',', $sigHeader);
        $timestamp = '';
        $v1 = '';

        foreach ($signatureParts as $part) {
            if (strpos($part, 't=') === 0) $timestamp = substr($part, 2);
            if (strpos($part, 'v1=') === 0) $v1 = substr($part, 3);
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $endpointSecret);

        if (!hash_equals($expectedSignature, $v1)) {
            $this->logger->error('AMRE Webhook: Invalid Stripe signature detected.');
            return $this->getResponse()->setStatusCode(400);
        }

        $event = json_decode($payload, true);

        if ($event['type'] == 'checkout.session.completed') {
            $session = $event['data']['object'];
            $customerId = $session['client_reference_id'];

            if ($customerId) {
                $wallet = $this->walletFactory->create();
                $this->walletResource->load($wallet, $customerId, 'customer_id');

                if (!$wallet->getId()) {
                    $wallet->setCustomerId($customerId);
                    $wallet->setTokenBalance(0);
                }

                $newBalance = $wallet->getTokenBalance() + 1;
                $wallet->setTokenBalance($newBalance);
                $this->walletResource->save($wallet);

                $this->logger->info("AMRE Webhook: Successfully credited 1 token to Customer ID {$customerId}.");
            }
        }

        return $this->getResponse()->setStatusCode(200);
    }
}