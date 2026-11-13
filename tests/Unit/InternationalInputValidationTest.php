<?php

namespace Tests\Unit;

use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Rules\ValidStudentDocument;
use App\Support\InternationalPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InternationalInputValidationTest extends TestCase
{
    #[Test]
    public function phone_numbers_are_checked_against_the_selected_country_and_normalized(): void
    {
        $valid = Validator::make(
            ['phone' => '0771234567'],
            ['phone' => [new ValidInternationalPhone('LK')]]
        );
        $this->assertTrue($valid->passes());

        $invalidLength = Validator::make(
            ['phone' => '077 12'],
            ['phone' => [new ValidInternationalPhone('LK')]]
        );
        $this->assertTrue($invalidLength->fails());
        $this->assertArrayHasKey('phone', $invalidLength->errors()->messages());

        $wrongCountry = Validator::make(
            ['phone' => '0771234567'],
            ['phone' => [new ValidInternationalPhone('GB')]]
        );
        $this->assertTrue($wrongCountry->fails());

        $formatted = Validator::make(
            ['phone' => '077 123 4567'],
            ['phone' => [new ValidInternationalPhone('LK')]]
        );
        $this->assertTrue($formatted->fails());

        $normalizedInternational = Validator::make(
            ['phone' => '+94771234567'],
            ['phone' => [new ValidInternationalPhone('LK')]]
        );
        $this->assertTrue($normalizedInternational->passes());

        $tooManyInternationalDigits = Validator::make(
            ['phone' => '+9477123456789012'],
            ['phone' => [new ValidInternationalPhone('LK')]]
        );
        $this->assertTrue($tooManyInternationalDigits->fails());

        $invalidRegion = Validator::make(
            ['phone' => '077 123 4567'],
            ['phone' => [new ValidInternationalPhone(['LK'])]]
        );
        $this->assertTrue($invalidRegion->fails());

        $request = Request::create('/', 'POST', [
            'phone' => '0771234567',
            'phone_region' => 'LK',
        ]);
        InternationalPhoneNumber::normalizeRequest($request);
        $this->assertSame('+94771234567', $request->input('phone'));
    }

    #[Test]
    public function student_document_validation_uses_type_and_issuing_country(): void
    {
        $passport = Validator::make(
            ['document_number' => 'ab123456'],
            ['document_number' => [new ValidStudentDocument('passport', 'LK')]]
        );
        $this->assertTrue($passport->passes());

        $shortPassport = Validator::make(
            ['document_number' => 'AB123'],
            ['document_number' => [new ValidStudentDocument('passport', 'LK')]]
        );
        $this->assertTrue($shortPassport->fails());

        $sriLankanNationalId = Validator::make(
            ['document_number' => '123456789V'],
            ['document_number' => [new ValidStudentDocument('national_id', 'LK')]]
        );
        $this->assertTrue($sriLankanNationalId->passes());

        $wrongIssuingCountry = Validator::make(
            ['document_number' => '123456789V'],
            ['document_number' => [new ValidStudentDocument('national_id', 'US')]]
        );
        $this->assertTrue($wrongIssuingCountry->fails());

        $otherCountryFallback = Validator::make(
            ['document_number' => 'AB-12345'],
            ['document_number' => [new ValidStudentDocument('national_id', 'BR')]]
        );
        $this->assertTrue($otherCountryFallback->passes());

        $australianNationalId = Validator::make(
            ['document_number' => '123456789'],
            ['document_number' => [new ValidStudentDocument('national_id', 'AU')]]
        );
        $this->assertTrue($australianNationalId->fails());
    }

    #[Test]
    public function international_demo_phone_examples_match_each_selected_region(): void
    {
        foreach ([
            ['LK', '+94770000001'],
            ['IN', '+919860000001'],
            ['GB', '+447400000001'],
            ['US', '+12025550101'],
            ['AE', '+971501234001'],
            ['FR', '+33612000001'],
            ['KE', '+254701234001'],
            ['CN', '+8613800000001'],
            ['JP', '+818012340001'],
            ['SG', '+6581000001'],
            ['ZA', '+27711234001'],
            ['AU', '+61412340001'],
        ] as [$region, $phone]) {
            $validator = Validator::make(
                ['phone' => $phone],
                ['phone' => [new ValidInternationalPhone($region)]]
            );
            $this->assertTrue($validator->passes(), "Expected {$phone} to be valid for {$region}.");
        }
    }

    #[Test]
    public function person_names_accept_international_punctuation_and_reject_invalid_characters(): void
    {
        foreach (['عائشة حسن', "Aisha O'Neil", 'Anne-Marie Smith', 'Dr. Nisha Rao'] as $name) {
            $validator = Validator::make(
                ['name' => $name],
                ['name' => [new ValidPersonName]]
            );
            $this->assertTrue($validator->passes(), "Expected {$name} to be valid.");
        }

        foreach (['Aisha2 Hassan', 'Aisha_Hassan', 'A', 'Aisha @ Hassan'] as $name) {
            $validator = Validator::make(
                ['name' => $name],
                ['name' => [new ValidPersonName]]
            );
            $this->assertTrue($validator->fails(), "Expected {$name} to be rejected.");
        }
    }

    #[Test]
    public function phone_input_renders_country_codes_and_field_level_feedback(): void
    {
        $html = Blade::render(
            '<x-international-phone-input name="phone" value="+94771234567" />',
            ['errors' => new ViewErrorBag]
        );

        $this->assertStringContainsString('name="phone_region"', $html);
        $this->assertStringContainsString('name="phone"', $html);
        $this->assertStringContainsString('data-international-phone', $html);
        $this->assertStringContainsString('data-phone-selected-code>+94</span>', $html);
        $this->assertStringContainsString('data-phone-number', $html);
        $this->assertStringContainsString('data-phone-search', $html);
        $this->assertStringContainsString('inputmode="numeric"', $html);
        $this->assertStringContainsString('data-phone-selected-flag', $html);
        $this->assertStringContainsString('fi fi-lk', $html);
        $this->assertStringContainsString('data-region="LK"', $html);
        $this->assertStringContainsString('phone-country-menu', $html);
        $this->assertStringContainsString('data-phone-lengths="9"', $html);
        $this->assertStringContainsString('maxlength="10"', $html);

        $countryPicker = Blade::render(
            '<x-international-country-select name="issuing_country" value="SG" required />',
            ['errors' => new ViewErrorBag]
        );
        $this->assertStringContainsString('name="issuing_country"', $countryPicker);
        $this->assertStringContainsString('fi fi-sg', $countryPicker);
        $this->assertStringContainsString('Singapore', $countryPicker);
        $this->assertStringContainsString('international-country-menu', $countryPicker);
        $this->assertStringContainsString('data-country-search', $countryPicker);
    }

    #[Test]
    public function nationality_picker_is_searchable_and_uses_the_demo_country_set(): void
    {
        $html = Blade::render(
            '<x-international-nationality-select />',
            ['errors' => new ViewErrorBag]
        );

        $this->assertStringContainsString('data-nationality-picker', $html);
        $this->assertStringContainsString('data-nationality-search', $html);
        foreach (['Sri Lankan', 'Indian', 'British', 'American', 'Emirati', 'French', 'Kenyan', 'Chinese', 'Japanese', 'Singaporean', 'South African', 'Australian'] as $nationality) {
            $this->assertStringContainsString($nationality, $html);
        }
    }
}
