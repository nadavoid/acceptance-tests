<?php
namespace AcceptanceTester;

class SpringboardSteps extends \AcceptanceTester\DrupalSteps
{
    public function makeADonation(array $details = array(), $recurs = FALSE)
    {
        $defaults = array(
            'ask' => '10',
            'first' => 'John',
            'last' => 'Tester',
            'email' => 'bob@example.com',
            'address' => '',
            'address2' => '',
            'city' => 'Washington',
            'state' => 'DC',
            'zip' => '20036',
            'country' => 'United States',
            'number' => '4111111111111111',
            'year' => date('Y', strtotime('+ 1 year')),
            'month' => 'January',
            'cvv' => '666',
        );

        $settings = array_merge($defaults, $details);

        $I = $this;

        $I->selectOption(\DonationFormPage::$askAmountField, $settings['ask']);
        $I->fillInMyName($settings['first'], $settings['last']);
        $I->fillField(\DonationFormPage::$emailField, $settings['email']);
        $I->fillInMyAddress($settings['address'], $settings['address2'], $settings['city'], $settings['state'], $settings['zip'], $settings['country']);
        $I->fillInMyCreditCard($settings['number'], $settings['year'], $settings['month'], $settings['cvv']);

        if ($recurs) {
            $I->selectOption(\DonationFormPage::$recursField, 'recurs');
        }

        $I->click(\DonationFormPage::$donateButton);
    }

    public function fillInMyName($first = 'John', $last = 'Tester') {
        $I = $this;
        $I->fillField(\DonationFormPage::$firstNameField, $first);
        $I->fillField(\DonationFormPage::$lastNameField, $last);
    }

    public function fillInMyCreditCard($number = '4111111111111111', $year = NULL, $month = 'January', $cvv = '456') {
        $I = $this;

        $I->fillField(\DonationFormPage::$creditCardNumberField, $number);
        $I->selectOption(\DonationFormPage::$creditCardExpirationMonthField, $month);

        if (is_null($year)) {
            $year = date('Y', strtotime('+ 1 year'));
        }

        $I->selectOption(\DonationFormPage::$creditCardExpirationYearField, $year);

        $I->fillField(\DonationFormPage::$CVVField, $cvv);
    }

    public function fillInMyAddress($address = '1234 Main St', $address2 = '', $city = 'Washington', $state = 'District Of Columbia', $zip = '00000', $country = 'United States') {
        $I = $this;

        $I->fillField(\DonationFormPage::$addressField, $address);
        // @todo Address 2
        $I->fillField(\DonationFormPage::$cityField, $city);
        $I->selectOption(\DonationFormPage::$countryField, $country);
        $I->selectOption(\DonationFormPage::$stateField, $state);
        $I->fillField(\DonationFormPage::$zipField, $zip);
    }

    /**
     * Clones a donation form.
     *
     * @param $nid
     *   The node id of the form to clone. Defaults to the build in
     *   donation form nid.
     *
     * @return $nid of newly created form.
     */
    public function cloneADonationForm($nid = 2) {
        $I = $this;

        $I->amOnPage('/node/' . $nid . '/clone');
        $I->click('Clone');
        $cloneNid = $I->grabFromCurrentUrl('~/springboard/node/(\d+)/edit~');
        codecept_debug($cloneNid);
        return $cloneNid;
    }

    /**
     * Configures a confirmation page title and message.
     *
     * @param $nid
     *   The id of the form to configue.
     *
     * @param $pageTitle
     *   The title to user for the confirmation page.
     *
     * @param $pageContent
     *   The content to use for the confirmation page.
     */
    public function configureConfirmationPage($nid, $pageTitle, $pageContent) {
        $I = $this;

        $I->amOnPage('/node/' . $nid . '/edit');
        $I->click('Form components');
        $I->click('Confirmation page & settings');
        $I->fillField('#edit-confirmation-confirmation-page-title', $pageTitle);
        $I->fillField('#edit-confirmation-value', $pageContent);
        $I->selectOption('confirmation[format]', 'full_html');
        $I->click('Save configuration');
    }

    /**
     * Make multiple donations with random info.
     *
     * @param string $path
     *   The path of the donation form. For example, '/node/2'.
     * @param int $numberOfDonations
     *   How many donations to make.
     */
    public function makeMultipleDonations($path, $numberOfDonations = 10) {
        $I = $this;
        // Used in combination with an iterator number to create a unique email address on each donation.
        $request_time = strtotime('now');

        $I->am('a donor');
        $I->wantTo('donate.');

        $asks = array('10', '20', '50', '100');
        $firsts = array('Alice', 'Tom', 'TJ', 'Phillip', 'David', 'Shaun', 'Ben', 'Jennie', 'Sheena', 'Danny', 'Allen', 'Katie', 'Jeremy', 'Julia', 'Kate', 'Misty', 'Pat', 'Jenn', 'Joel', 'Katie', 'Matt', 'Meli', 'Jess');
        $lasts = array('Hendricks', 'Williamson', 'Griffen', 'Cave', 'Barbarisi', 'Brown', 'Clark', 'Corman', 'Donnelly', 'Englander', 'Freeman', 'Grills', 'Isett', 'Kulla-Mader', 'McKenney', 'McLaughlin', 'O\'Brien', 'Olivia', 'Rothschild', 'Shaw', 'Thomas', 'Trumbo', 'Walls');
        $numbers = array('4111111111111111');
        $months = cal_info(0)['months'];

        for ($iterator = 0; $iterator < $numberOfDonations; $iterator++) {
            $defaults = array(
                'ask' => $asks[array_rand($asks)],
                'first' => $firsts[array_rand($firsts)],
                'last' => $lasts[array_rand($lasts)],
                'email' => 'test_' . $iterator . '_' . $request_time . '@example.com',
                'address' => '1234 Main St',
                'address2' => '',
                'city' => 'Washington',
                'state' => 'DC',
                'zip' => '20036',
                'country' => 'United States',
                'number' => $numbers[array_rand($numbers)],
                'year' => date('Y', strtotime('+ ' . ($i + 1) % 14 . ' years')),
                'month' => $months[array_rand($months)],
                'cvv' => rand(100, 999),
            );

            $recurring = ($iterator % 2) ? TRUE : FALSE;
            $I->amOnPage($path);
            $I->makeADonation($defaults, $recurring);
        }
    }
}
