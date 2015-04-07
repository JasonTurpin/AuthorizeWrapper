<?php
/**
 * Helper functions
 *
 * @category Authorize.net Wrapper
 * @package  Authorize.net Wrapper
 * @author   Jason Turpin <jasonaturpin@gmail.com>
 */

/**
 * Helper functions used throughout app
 *
 * @category Authorize.net Wrapper
 * @package  Authorize.net Wrapper
 * @author   Jason Turpin <jasonaturpin@gmail.com>
 */
class Helper {
    /**
     * Checks to see if the credit card number passed is valid
     *
     * @param string $number
     *
     * @return bool
     */
    public static function isValidCCNumber($number) {
        // @codingStandardsIgnoreStart
        /**
         * 4[0-9]{12}(?:[0-9]{3})?        - Visa
         * 5[1-5][0-9]{14}                - MasterCard
         * 3[47][0-9]{13}                 - American Express
         * 3(?:0[0-5]|[68][0-9])[0-9]{11} - Diners Club
         * 6(?:011|5[0-9]{2})[0-9]{12}    - Discover
         * (?:2131|1800|35\d{3})\d{11}    - JCB
         *
         * @var  string $regex
         * @link http://www.regular-expressions.info/creditcard.html
         * @link http://www.paypalobjects.com/en_US/vhelp/paypalmanager_help/credit_card_numbers.htm
         */
        $regex = '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|6(?:011|5[0-9]{2})[0-9]{12}|(?:2131|1800|35\d{3})\d{11})$/';
        // @codingStandardsIgnoreEnd

        // Runs test
        if (preg_match($regex, $number)) {
            return true;
        }
        return false;
    }

    /**
     * Checks to see if a zipcode is valid
     *
     * @param string $zip Expiration date being tested
     *
     * @return bool
     */
    public static function isValidZip($zip) {
        // Regular expression
        $regex  = '/^\d{5}(?:-\d{4})?$/';

        // Runs test
        if (preg_match($regex, $zip)) {
            return true;
        }
        return false;
    }

    /**
     * Checks to see if an expiration date is valid
     *
     * @param string $expDate Expiration date being tested
     *
     * @return bool
     */
    public static function isValidExpirationDate($expDate) {
        // Regular expression
        $regex = '/^(0[1-9]|1[0-2])\/\d{2}$/';

        // Runs test
        if (preg_match($regex, $expDate)) {
            return true;
        }
        return false;
    }
}
