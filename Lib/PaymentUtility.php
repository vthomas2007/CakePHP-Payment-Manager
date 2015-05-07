<?php

class PaymentUtility
{   
    public static function getFingerprint($token, $type = 'card')
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.public'));
        
        try { 
            $response = Stripe\Token::retrieve($token);
            
            $data =  $response[$type]->__toArray();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $data['fingerprint'];
    }
    
    public static function createCustomer($description, $token, $email)
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try {
            $customer = Stripe\Customer::create([
                'description' => $description, 
                'card' => $token, 
                'email' => $email
            ]);

            if (empty($customer['cards']->data[0])) {
                return 'There was an error adding this credit card, please try again later.';
            }
            
            $customer_card = $customer['cards']->data[0]->__toArray();
             
            if ($customer_card['cvc_check'] != 'pass') {
                return 'CVV2/CVC2 check failed, please check your information.';
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $customer;
    }
    
    public static function createRecipient($description, $token, $name)
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try {
            $recipient = Stripe\Recipient::create([
                'type' => 'individual', 
                'bank_account' => $token, 
                'name' => $name
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $recipient;
    }

    public static function userPayout($recipient_id, $amount, $paidMarks, $currency = 'usd')
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        try {  
            $description = [];
            
            foreach ($paidMarks as $paid) {
                $description[] = sprintf(
                    'Marker %s Mark #%d (%s %s)',
                    $paid['Marker']['name'],
                    $paid['MarkerUserMark']['id'],
                    $paid['Payee']['first_name'],
                    $paid['Payee']['last_name']
                );
            }
            
            $transfer = Stripe\Transfer::create([
                'amount' => $amount, 
                'currency' => $currency,
                'recipient' => $recipient_id,
                'description' => 'Marker payments for '.implode(', ', $description)
            ]);
        } catch (Exception $e) { 
            return $e->getMessage();
        }
        
        return $transfer;
    }
    
    public static function chargeCustomer($customer_id, $amount, $description, $currency = 'usd')
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try{
            $charge = Stripe\Charge::create([
                'amount' => $amount, 
                'currency' => $currency,
                'customer' => $customer_id,
                'description' => $description
            ]);
        } catch(Stripe\Error\Card $e) {
            return $e->getMessage();
        } catch (Stripe\Error\InvalidRequest $e) {
            return $e->getMessage();
        } catch (Stripe\Error\Authentication $e) {
            return $e->getMessage();
        } catch (Stripe\Error\ApiConnection $e) {
            return $e->getMessage();
        } catch (Stripe\Error\Base $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $charge;
    }
    
    public static function checkBalance()
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try{
            $balance = Stripe\Balance::retrieve();
        } catch (Stripe\Error\InvalidRequest $e) {
            return $e->getMessage();
        } catch (Stripe\Error\Authentication $e) {
            return $e->getMessage();
        } catch (Stripe\Error\ApiConnection $e) {
            return $e->getMessage();
        } catch (Stripe\Error\Base $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $balance;
    }
    
    public static function checkBalanceTransaction($transactionId)
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try{
            $balanceTransaction = Stripe\BalanceTransaction::retrieve($transactionId);
        } catch (Stripe\Error\InvalidRequest $e) {
            return $e->getMessage();
        } catch (Stripe\Error\Authentication $e) {
            return $e->getMessage();
        } catch (Stripe\Error\ApiConnection $e) {
            return $e->getMessage();
        } catch (Stripe\Error\Base $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $balanceTransaction;
    }
    
    public static function createTestToken($number = '4242424242424242', $exp_month = 4, $exp_year = 2016, $cvc = 888)
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        return Stripe\Token::create(["card" => compact('number', 'exp_month', 'exp_year', 'cvc')])->id;
    }
    
    public static function createTestBank($account_number = '000123456789', $routing_number = '110000000', $country = 'US')
    {
        Stripe\Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        return Stripe\Token::create(["bank_account" => compact('account_number', 'routing_number', 'country')])->id;
    }
    
}
