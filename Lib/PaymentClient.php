<?php

App::import('Vendor', 'Stripe', ['file' => 'stripe/stripe-php/lib/Stripe.php']);
    
class PaymentClient
{
    public function createCustomer($card, $email, $description)
    {
        $params = compact('card', 'email', 'description');

        $customer = $this->makeStripeCall('Stripe_Customer', 'create', $params);
        $this->checkCustomerCardData($customer);
        
        return $customer;
    }
    
    public function createRecipient($bank_account, $name, $description, $type = 'individual')
    {
        $params = compact('bank_account', 'name', 'description', 'type');
        
        return $this->makeStripeCall('Stripe_Recipient', 'create', $params);
    }
    
    public function userPayout($recipient, $amount, $description, $currency = 'usd')
    {
        $params = compact('recipient', 'amount', 'description', 'currency');
        
        return $this->makeStripeCall('Stripe_Transfer', 'create', $params);
    }
    
    public function chargeCustomer($customer, $amount, $description, $currency = 'usd')
    {
        $params = compact('customer', 'amount', 'description', 'currency');
        
        return $this->makeStripeCall('Stripe_Charge', 'create', $params);
    }
    
    public function checkBalance()
    {
        return $this->makeStripeCall('Stripe_Balance', 'retrieve');
    }
    
    public function checkBalanceTransaction($transactionId)
    {
        return $this->makeStripeCall('Stripe_BalanceTransaction', 'retrieve', $transactionId);
    }
    
    public function getStripeTokenData($token, $type = 'card')
    {
        $response = $this->makeStripeCall('Stripe_Token', 'retrieve', $token);
        
        return $response[$type]->__toArray();
    }
    
    public function getStripeTokenAttribute($data, $attr)
    {
        return $data[$attr];
    }
    
    public function createTestToken($number = '4242424242424242', $exp_month = 12, $exp_year = 2020, $cvc = 123)
    {
        $params = ['card' => compact('number', 'exp_month', 'exp_year', 'cvc')];
        
        $data = $this->makeStripeCall('Stripe_Token', 'create', $params);
        
        return $data->id;
    }
    
    public function createTestBank($account_number = '000123456789', $routing_number = '110000000', $country = 'US')
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
                $reply = $object::{$call}();
            } else {
                $reply = $object::{$call}($params);
            }
        } catch (Stripe_CardError $e) {
            throw new ApiException($e->getMessage());
        } catch (Stripe_InvalidRequestError $e) {
            throw new ApiException($e->getMessage());
        } catch (Stripe_AuthenticationError $e) {
            throw new ApiException($e->getMessage());
        } catch (Stripe_ApiConnectionError $e) {
            throw new ApiException($e->getMessage());
        } catch (Stripe_Error $e) {
            throw new ApiException($e->getMessage());
        } catch (Exception $e) {
            throw new ApiException($e->getMessage());
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