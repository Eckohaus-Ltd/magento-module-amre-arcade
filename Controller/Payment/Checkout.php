<?php
namespace Eckohaus\AmreArcade\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;

class Checkout extends Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $customerSession;
    protected $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['status' => 'error', 'message' => 'ACCESS DENIED: Please log in.']);
        }

        // Retrieve the encrypted API key from the database
        $stripeSecret = $this->scopeConfig->getValue('amre_arcade/stripe/secret_key');
        
        if (empty($stripeSecret)) {
            return $result->setData(['status' => 'error', 'message' => 'GATEWAY OFFLINE: Configuration missing.']);
        }

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $customerId = $this->customerSession->getCustomerId();

        // Build the Stripe API Request Payload
        $stripePayload = http_build_query([
            'success_url' => $baseUrl . 'downloadable/customer/products/',
            'cancel_url' => $baseUrl . 'downloadable/customer/products/',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'gbp',
                        'product_data' => ['name' => 'AMRE Calculation Token // JUPITER-IV'],
                        'unit_amount' => 100, // 100 pence = £1.00
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'client_reference_id' => $customerId // Crucial: Tags the payment to your Magento user
        ]);

        // Connect to Stripe via cURL
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $stripePayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $stripeSecret,
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $stripeData = json_decode($response, true);

        if ($httpCode === 200 && isset($stripeData['url'])) {
            return $result->setData(['status' => 'success', 'url' => $stripeData['url']]);
        } else {
            return $result->setData(['status' => 'error', 'message' => 'STRIPE FAILURE: ' . ($stripeData['error']['message'] ?? 'Unknown error')]);
        }
    }
}