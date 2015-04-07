<?php
/**
 * File to handle payment processing
 *
 * PHP version 5.3
 *
 * @category Authorize.net Wrapper
 * @package  Authorize.net Wrapper
 * @author   Jason Turpin <jasonaturpin@gmail.com>
 */

/**
 * Handles Payment processing for Aut
 *
 * @category Authorize.net Wrapper
 * @package  Authorize.net Wrapper
 * @author   Jason Turpin <jasonaturpin@gmail.com>
 */
class PaymentProcessor {
    /**
     * Creates a new subscription
     *
     * @param string $clientName The client's name
     * @param string $amount Subscription amount
     * @param string $startDate  When the first billing date will be
     * @param string $firstName  Credit Card holder's first name
     * @param string $lastName   Credit Card holder's last name
     * @param string $code       Special code found on the back of a card
     * @param string $expDate    Credit Card expiration date
     * @param string $number     Credit Card number
     * @param string $address    Billing address
     * @param string $zip        Billing zipcode
     *
     * @return int Subscription ID
     */
    public function subscribe($clientName, $amount, $startDate, $firstName, $lastName, $code, $expDate, $number,
        $address, $zip
    ) {
        // Validate Parameters
        if (false === strtotime($startDate) || (!is_numeric($amount) || $amount < 0)
            || false === $this->hasValidCC($number, $expDate, $address, $zip)
        ) {
            return false;
        }

        // Create new Authorize.net Subscription
        $authSubscription = new AuthorizeNet_Subscription;
        $authSubscription->name                     = $clientName;
        $authSubscription->intervalLength           = "1";
        $authSubscription->intervalUnit             = "months";
        $authSubscription->startDate                = date('Y-m-d', strtotime('tomorrow'));
        $authSubscription->totalOccurrences         = "9999";  // 9999 = indefinite
        $authSubscription->amount                   = $amount;
        $authSubscription->creditCardCardNumber     = $number;
        $authSubscription->creditCardExpirationDate = $expDate;
        $authSubscription->creditCardCardCode       = $code;
        $authSubscription->billToFirstName          = $firstName;
        $authSubscription->billToLastName           = $lastName;

        // Send subscription request
        $request  = new AuthorizeNetARB;
        $response = $request->createSubscription($authSubscription);

        // IF subscription was successfully created
        if (isset($response->xml->subscriptionId)
            && isset($response->xml->messages->message->text[0])
            && 'successful.' === strtolower($response->xml->messages->message->text[0])
        ) {
            return $response->xml->subscriptionId;
        }
        return false;
    }

    /**
     * Update ARB Subscription Amount
     *
     * @param int   $auth_sub_id Authorize.net Subscription ID
     * @param float $amount      Subscription amount
     *
     * @return bool
     */
    public function updateSubscriptionAmount($auth_sub_id, $amount) {
        // Validate parameters
        if (!is_numeric($auth_sub_id) || !is_numeric($amount)) {
            return false;
        }

        // Create new Authorize.net Subscription
        $authSubscription = new AuthorizeNet_Subscription;
        $authSubscription->amount = $amount;

        /** @var AuthorizeNetARB $arb */
        $arb = new AuthorizeNetARB;
        $response = $arb->updateSubscription($auth_sub_id, $authSubscription);

        // IF request was successful
        if (isset($response->xml->messages->message->text[0])
            && 'successful.' === strtolower((string)$response->xml->messages->message->text[0])) {
            return true;
        }
        return false;
    }

    /**
     * Updates the credit card for an Authorize.net Subscription
     *
     * @param int    $auth_sub_id Authorize.net ID
     * @param string $firstName   Cardholder's first name
     * @param string $lastName    Cardholder's last name
     * @param string $code        Card code
     * @param string $expDate     Card expiration date
     * @param string $number      Card number
     * @param string $address     Cardholder address
     * @param string $zip         Cardholder zip code
     *
     * @return bool
     */
    public function updateSubscriptionCard($auth_sub_id, $firstName, $lastName, $code, $expDate, $number, $address, $zip) {
        // Validate Parameters
        if (!is_numeric($auth_sub_id) || false === $this->hasValidCC($number, $expDate, $address, $zip)) {
            return false;
        }

        // Create new Authorize.net Subscription
        $authSubscription = new AuthorizeNet_Subscription;
        $authSubscription->creditCardCardNumber     = $number;
        $authSubscription->creditCardExpirationDate = $expDate;
        $authSubscription->creditCardCardCode       = $code;
        $authSubscription->billToFirstName          = $firstName;
        $authSubscription->billToLastName           = $lastName;

        /** @var AuthorizeNetARB $arb */
        $arb = new AuthorizeNetARB;
        $response = $arb->updateSubscription($auth_sub_id, $authSubscription);

        // IF request was successful
        if (isset($response->xml->messages->message->text[0])
            && 'successful.' === strtolower((string)$response->xml->messages->message->text[0])) {
            return true;
        }
        return false;
    }

    /**
     * Process the transactions for the date passed
     *
     * @param string $day   Day   (DD)
     * @param string $month Month (MM)
     * @param string $year  Year  (YYYY)
     *
     * @return void
     */
    public function fetchTransactionsForDate($day, $month, $year) {
        // Validate parameters
        if (!strtotime($year.'-'.$month.'-'.$day)) {
            return false;
        }

        // Fetch yesterday's transactions
        $request  = new AuthorizeNetTD;
        return $request->getTransactionsForDay($month, $day, $year);
    }

    /**
     * Tests to see if a credit card is valid
     *
     * @param string $number  Credit Card Number
     * @param string $expDate Credit Card Expiration Date
     * @param string $address Billing address
     * @param string $zip     Zip code
     *
     * @return bool
     */
    public function hasValidCC($number, $expDate, $address, $zip) {
        // Validate parameters
        if (false === Helper::isValidCCNumber($number) || false === Helper::isValidExpirationDate($expDate)
            || false === Helper::isValidZip($zip)      || empty($address)
        ) {
            return false;
        }

        // Attempts a purchase of $0
        $sale     = new AuthorizeNetAIM;
        $sale->setField('address', $address);
        $sale->setField('zip', $zip);
        $response = $sale->authorizeOnly('0.00', $number, $expDate);

        // IF approved, return true
        if (isset($response->approved) && true === $response->approved) {
            return true;
        }
        return false;
    }

    /**
     * Cancels a subscription
     *
     * @param int $subscription_id Subscription ID
     *
     * @return bool
     */
    public function cancelSubscription($subscription_id) {
        $cancellation = new AuthorizeNetARB;
        $response     = $cancellation->cancelSubscription($subscription_id);

        // IF it was successfull, return true
        if ($response->xml->messages->message->text === 'Successful.') {
            return true;
        }
        return false;
    }
}
