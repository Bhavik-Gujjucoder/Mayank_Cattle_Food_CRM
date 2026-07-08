<?php

use App\Support\FinancialYear;
use Carbon\Carbon;

describe('FinancialYear', function () {
    it('returns the April–March financial year label', function () {
        expect(FinancialYear::label(Carbon::parse('2026-04-01')))->toBe('2026-27')
            ->and(FinancialYear::label(Carbon::parse('2026-03-31')))->toBe('2025-26')
            ->and(FinancialYear::label(Carbon::parse('2026-01-15')))->toBe('2025-26');
    });

    it('returns the full financial year date range', function () {
        $range = FinancialYear::range(Carbon::parse('2026-07-08'));

        expect($range['start']->toDateTimeString())->toBe('2026-04-01 00:00:00')
            ->and($range['end']->toDateTimeString())->toBe('2027-03-31 23:59:59');
    });

    it('detects whether a date falls inside the financial year', function () {
        expect(FinancialYear::contains(Carbon::parse('2026-05-01'), Carbon::parse('2026-07-08')))->toBeTrue()
            ->and(FinancialYear::contains(Carbon::parse('2025-12-01'), Carbon::parse('2026-07-08')))->toBeFalse();
    });
});
