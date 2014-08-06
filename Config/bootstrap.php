<?php

/**
 * Please copy the config below and place it on your /app/Config/bootstrap.php
 * Remember to fill in the fields!
 */

Configure::write('Stripe.test.secret', '');
Configure::write('Stripe.test.public', '');

Configure::write('Stripe.live.secret', '');
Configure::write('Stripe.live.public', '');

if (class_exists('EnvironmentUtility') && EnvironmentUtility::is('production')) {
    Configure::write('Stripe', Configure::read('Stripe.live'));
} else {
    Configure::write('Stripe', Configure::read('Stripe.test'));
}

require_once APP . 'Plugin' . DS . 'PaymentManager' . DS . 'Lib' . DS . 'PaymentUtility.php';
require_once APP . 'Plugin' . DS . 'PaymentManager' . DS . 'Lib' . DS . 'PaymentClient.php';