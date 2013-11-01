<?php

App::import('Vendor', 'Stripe', ['file' => 'stripe/stripe-php/lib/Stripe.php']);

class PaymentUtility
{   
    public static function getFingerprint($token, $type = 'card')
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.public'));
        
        try { 
            $response = Stripe_Token::retrieve($token);
            
            $data =  $response[$type]->__toArray();
        } catch (Exception $e) {
            throw new ApiException($e->getMessage());
        }
        
        return $data['fingerprint'];
    }
    
    public static function createCustomer($description, $token, $email)
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try {
            $customer = Stripe_Customer::create([
                'description' => $description, 
                'card' => $token, 
                'email' => $email
            ]);

            if (empty($customer['cards']->data[0])) {
                throw new ApiException('There was an error adding this credit card, please try again later.');
            }
            
            $customer_card = $customer['cards']->data[0]->__toArray();
             
            if ($customer_card['cvc_check'] != 'pass') {
                throw new ApiException('CVC check failed, please check your information.');
            }
        } catch (Exception $e) {
            throw new ApiException($e->getMessage());
        }
        
        return $customer;
    }
    
    public static function createRecipient($description, $token, $name)
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try {
            $recipient = Stripe_Recipient::create([
                'type' => 'individual', 
                'bank_account' => $token, 
                'name' => $name
            ]);
        } catch (Exception $e) {
            throw new ApiException($e->getMessage());
        }
        
        return $recipient;
    }

    public static function userPayout($recipient_id, $amount, $paidMarks, $currency = 'usd')
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
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
            
            $transfer = Stripe_Transfer::create([
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
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        
        try{
            $charge = Stripe_Charge::create([
                'amount' => $amount, 
                'currency' => $currency,
                'customer' => $customer_id,
                'description' => $description
            ]);
        } catch(Stripe_CardError $e) {
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
        
        return $charge;
    }
    
    public static function createTestToken(){
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        $token = Stripe_Token::create(["card" => ["number" => "4242424242424242", "exp_month" => 4, "exp_year" => 2016, "cvc" => 888]]);
        return $token->id;
    }

    public static function getTestToken()
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        $data = ["card" =>["number" => "4012888888881881", "exp_month" => 2, "exp_year" => 2016, "cvc" => 314]];
        return Stripe_Token::create($data)->id;
    }
    public static function getTestToken2()
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        $data = ["card" => ["number" => "4000000000000002", "exp_month" => 2, "exp_year" => 2016, "cvc" => 314]];
        return Stripe_Token::create($data)->id;
    }

    public static function getTestBank()
    {
        Stripe::setApiKey(Configure::read('Stripe.keys.secret'));
        $data = ["bank_account" =>["account_number" => "000123456789",  "country"=>"US", "routing_number"=> '110000000']];
        return Stripe_Token::create($data)->id;
    }
    
}