<?php

namespace App\Containers\Payments\Services;

use App\Containers\Payments\Contracts\Chargeable;
use App\Containers\Payments\Exceptions\ObjectNonChargeableException;
use App\Containers\Payments\Exceptions\PaymentMethodNotFoundException;
use App\Containers\Payments\Exceptions\UserNotSetInThePaymentServiceException;
use App\Containers\Paypal\Services\ChargeWithPaypalService;
use App\Containers\Stripe\Services\ChargeWithStripeService;
use App\Containers\User\Models\User;
use Illuminate\Support\Facades\App;

/**
 * Class PaymentsFactory
 *
 * @author  Mahmoud Zalt  <mahmoud@zalt.me>
 */
class PaymentsFactory
{

    protected $method = null;

    /**
     * @param \App\Containers\User\Models\User $user
     * @param                                  $amount
     * @param string                           $currency
     *
     * @return  mixed
     */
    public function charge(User $user, $amount, $currency = 'USD')
    {
        $this->setUserPaymentMethod($user);

        return $this->method->charge($user, $amount, $currency);
    }

    /**
     * @param \App\Containers\User\Models\User $user
     */
    public function setUserPaymentMethod(User $user)
    {
        if ($user->stripeAccount !== null) {
            $this->method = App::make(ChargeWithStripeService::class);
        } elseif ($user->paypalAccount !== null) {
            $this->method = App::make(ChargeWithPaypalService::class);
        }
//        elseif ($user->...Account !== null) {
//            $this->method = App::make(ChargeWith...Service::class);
//        }

        // validate a payment method was found
        if ($this->method == null) {
            throw new PaymentMethodNotFoundException();
        }

        // validate containing charge function
        if (!$this->method instanceof Chargeable) {
            throw (new ObjectNonChargeableException())
                ->debug('The payment services must implement the Chargeable interface');
        }
    }

    /**
     * @return  null
     */
    public function getPaymentMethod()
    {
        return $this->method;
    }

}
