<?php

namespace App\TaxTables;

/**
 * The twenty single figures a tax year is made of, as stored in `tax_constants`.
 *
 * They live here rather than in the domain on purpose: the engine takes a TaxYearConfig and
 * has no idea the parameters were ever rows. These names exist only because the table stores
 * key and value, which is a persistence decision — choose one column per figure and this enum
 * disappears without the domain noticing.
 *
 * Everything that varies by where you live is absent: the municipal rate and its exemption
 * are columns of `tax_municipalities`, and every list of bands is in `tax_brackets`.
 */
enum TaxConstantKey: string
{
    case ContributionRateCommerce = 'contribution_rate_commerce';
    case ContributionRateIndustry = 'contribution_rate_industry';
    case ContributionAdditionalRate = 'contribution_additional_rate';
    case ContributionAdditionalRateThreshold = 'contribution_additional_rate_threshold';
    case ContributionAnnualCeiling = 'contribution_annual_ceiling';

    case EmploymentReliefFlatUpTo = 'employment_relief_flat_up_to';
    case EmploymentReliefFlatAmount = 'employment_relief_flat_amount';
    case EmploymentReliefFirstTaperUpTo = 'employment_relief_first_taper_up_to';
    case EmploymentReliefFirstTaperBase = 'employment_relief_first_taper_base';
    case EmploymentReliefFirstTaperVariable = 'employment_relief_first_taper_variable';
    case EmploymentReliefSecondTaperUpTo = 'employment_relief_second_taper_up_to';
    case EmploymentReliefSecondTaperBase = 'employment_relief_second_taper_base';

    case WedgeCutExemptBonusUpTo = 'wedge_cut_exempt_bonus_up_to';
    case WedgeCutReliefFlatUpTo = 'wedge_cut_relief_flat_up_to';
    case WedgeCutReliefFlatAmount = 'wedge_cut_relief_flat_amount';
    case WedgeCutReliefTaperUpTo = 'wedge_cut_relief_taper_up_to';

    case SupplementaryAllowanceFullUpTo = 'supplementary_allowance_full_up_to';
    case SupplementaryAllowanceFullAmount = 'supplementary_allowance_full_amount';
    case SupplementaryAllowancePartialUpTo = 'supplementary_allowance_partial_up_to';
    case SupplementaryAllowanceCapacityTestReliefOffset = 'supplementary_allowance_capacity_test_relief_offset';
}
