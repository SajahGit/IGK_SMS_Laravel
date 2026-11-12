<?php

namespace App\Services;

use App\Support\Money;
use Illuminate\Validation\ValidationException;

class SriLankaPayrollCalculator
{
    /** @return array{gross_pay:string,employee_epf:string,employer_epf:string,employer_etf:string,apit_deduction:string} */
    public function calculate(string $baseSalary, string $allowance, string $apitMode, string $periodStart): array
    {
        if ($periodStart < config('payroll.sri_lanka.effective_from')) {
            throw ValidationException::withMessages([
                'period_start' => 'The configured Sri Lankan statutory payroll schedule is effective from 1 April 2025. Configure the historical schedule before generating an earlier payroll.',
            ]);
        }

        $gross = Money::add($baseSalary, $allowance);
        $employeeEpf = Money::mul($gross, config('payroll.sri_lanka.epf_employee_rate'));
        $employerEpf = Money::mul($gross, config('payroll.sri_lanka.epf_employer_rate'));
        $employerEtf = Money::mul($gross, config('payroll.sri_lanka.etf_employer_rate'));
        $apit = match ($apitMode) {
            'primary' => $this->primaryApit($gross),
            'secondary' => Money::mul($gross, config('payroll.sri_lanka.secondary_apit_rate')),
            'exempt', 'manual' => '0.00',
            default => throw ValidationException::withMessages(['apit_mode' => 'Select a supported Sri Lankan APIT treatment.']),
        };

        return [
            'gross_pay' => $gross,
            'employee_epf' => $employeeEpf,
            'employer_epf' => $employerEpf,
            'employer_etf' => $employerEtf,
            'apit_deduction' => $apit,
        ];
    }

    private function primaryApit(string $monthlyRemuneration): string
    {
        $annualTaxable = Money::sub(
            Money::mul($monthlyRemuneration, '12'),
            config('payroll.sri_lanka.primary_apit_personal_relief_annual')
        );
        if (Money::comp($annualTaxable, '0.00') <= 0) {
            return '0.00';
        }

        $remaining = $annualTaxable;
        $annualTax = '0.00';
        foreach (config('payroll.sri_lanka.primary_apit_annual_bands') as $band) {
            $taxableInBand = $band['amount'] === null || Money::comp($remaining, $band['amount']) < 0
                ? $remaining
                : $band['amount'];
            $annualTax = Money::add($annualTax, Money::mul($taxableInBand, $band['rate']));
            $remaining = Money::sub($remaining, $taxableInBand);
            if ($band['amount'] === null || Money::comp($remaining, '0.00') <= 0) {
                break;
            }
        }

        return Money::div($annualTax, '12');
    }
}
