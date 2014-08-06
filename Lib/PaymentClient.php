<?php

App::import('Vendor', 'Stripe', ['file' => 'stripe/stripe-php/lib/Stripe.php']);
    
class PaymentClient
{
    public function getFingerprint($token, $type = 'card')
    {
        $response = $this->makeStripeCall('Stripe_Token', 'retrieve', $token);
        $data = $response[$type]->__toArray();
        
        return $data['fingerprint'];
    }
    
    public function createCustomer($description, $card, $email)
    {
        $params = compact('description', 'card', 'email');

        $customer = $this->makeStripeCall('Stripe_Customer', 'create', $params);
        $this->checkCustomerCardData($customer);
        
        return $customer;
    }
    
    public function createRecipient($bank_account, $name, $description, $type = 'individual')
    {
        $params = compact('bank_account', 'name', 'description', 'type');
        
        return $this->makeStripeCall('Stripe_Recipient', 'create', $params);
    }
    
    public static function userPayout($recipient, $amount, $description, $currency = 'usd')
    {
        $params = compact('recipient', 'amount', 'description', 'currency');
        
        return $this->makeStripeCall('Stripe_Transfer', 'create', $params);
    }
    
    public static function chargeCustomer($customer, $amount, $description, $currency = 'usd')
    {
        $params = compact('customer', 'amount', 'description', 'currency');
        
        return $this->makeStripeCall('Stripe_Charge', 'create', $params);
    }
    
    public static function checkBalance()
    {
        return $this->makeStripeCall('Stripe_Balance', 'retrieve');
    }
    
    public static function checkBalanceTransaction($transactionId)
    {
        return $this->makeStripeCall('Stripe_BalanceTransaction', 'retrieve', $transactionId);
    }
    
    public static function createTestToken($number = '4242424242424242', $exp_month = 12, $exp_year = 2020, $cvc = 123)
    {
        $params = ['card' => compact('number', 'exp_month', 'exp_year', 'cvc')];
        
        $data = $this->makeStripeCall('Stripe_Token', 'create', $params);
        
        return $data->id;
    }
    
    public static function createTestBank($account_number = '000123456789', $routing_number = '110000000', $country = 'US')
    {
        $params = ['bank_account' => compact('account_number', 'routing_number', 'country')];
        
        $data = $this->makeStripeCall('Stripe_Token', 'create', $params);
        
        return $data->id;
    }
    
    private function makeStripeCall($object, $call, $params = null)
    {
        Stripe::setApiKey(Configure::read('Stripe.secret'));
        
        try {
            if (is_null($params)) {
                $reply = {$object}::{$call}();
            } else {
                $reply = {$object}::{$call}($params);
            }
        } catch (Stripe_CardError $e) {
            return $e->getMessage();
        } catch (Stripe_InvalidRequestError $e) {
            return $e->getMessage();
        } catch (Stripe_AuthenticationError $e) {
            return $e->getMessage();
        } catch (Stripe_ApiConnectionError $e) {
            return $e->getMessage();
        } catch (Stripe_Error $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $reply;
    }
    
    private function checkCustomerCardData($customer)
    {
        if (empty($customer['cards']->data[0])) {
            return 'There was an error adding this credit card, please try again later.';
        }
        
        $customerCard = $customer['cards']->data[0]->__toArray();
         
        if ($customerCard['cvc_check'] != 'pass') {
            return 'CVV2/CVC2 check failed, please check your information.';
        }
        
        return true;
    }
}
    
?>