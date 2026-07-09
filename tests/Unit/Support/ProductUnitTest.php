<?php

use App\Support\ProductUnit;

describe('genericLabel', function () {
    it('returns combined unit label', function () {
        expect(ProductUnit::genericLabel())->toBe('Bag / Ton / KG');
    });
});

// ─────────────────────────────────────────────

describe('quantityFieldLabel', function () {
    it('returns No of Bag for Bag unit', function () {
        expect(ProductUnit::quantityFieldLabel('Bag'))->toBe('No of Bag');
    });

    it('returns No of Ton for Ton unit', function () {
        expect(ProductUnit::quantityFieldLabel('Ton'))->toBe('No of Ton');
    });

    it('returns No of KG for KG unit', function () {
        expect(ProductUnit::quantityFieldLabel('KG'))->toBe('No of KG');
    });

    it('returns generic label for null unit', function () {
        expect(ProductUnit::quantityFieldLabel(null))->toBe('No of Bag / Ton / KG');
    });

    it('returns generic label for unknown unit', function () {
        expect(ProductUnit::quantityFieldLabel('Quintal'))->toBe('No of Bag / Ton / KG');
    });
});

// ─────────────────────────────────────────────

describe('requiredMessage and minMessage', function () {
    it('requiredMessage returns the correct message', function () {
        expect(ProductUnit::requiredMessage())->toBe('No of bag / ton / kg is required.');
    });

    it('minMessage returns the correct message', function () {
        expect(ProductUnit::minMessage())->toBe('No of bag / ton / kg must be at least 1.');
    });
});

// ─────────────────────────────────────────────

describe('maxAllowedHint', function () {
    it('formats hint with unit when known unit is provided', function () {
        expect(ProductUnit::maxAllowedHint(10, 'Bag'))->toBe('Maximum allowed: 10 Bag');
    });

    it('formats generic hint when unit is null', function () {
        expect(ProductUnit::maxAllowedHint(10, null))->toBe('Maximum allowed: 10 bag / ton / kg');
    });

    it('formats generic hint when unit is unknown', function () {
        expect(ProductUnit::maxAllowedHint(5, 'Quintal'))->toBe('Maximum allowed: 5 bag / ton / kg');
    });
});

// ─────────────────────────────────────────────

describe('formatWithUnit', function () {
    it('appends Bag suffix when unit is Bag', function () {
        expect(ProductUnit::formatWithUnit(5, 'Bag'))->toBe('5 Bag');
    });

    it('appends Ton suffix when unit is Ton', function () {
        expect(ProductUnit::formatWithUnit(3, 'Ton'))->toBe('3 Ton');
    });

    it('appends KG suffix when unit is KG', function () {
        expect(ProductUnit::formatWithUnit(20, 'KG'))->toBe('20 KG');
    });

    it('returns quantity as string without suffix when unit is null', function () {
        expect(ProductUnit::formatWithUnit(7, null))->toBe('7');
    });

    it('returns quantity as string without suffix when unit is unknown', function () {
        expect(ProductUnit::formatWithUnit(7, 'Quintal'))->toBe('7');
    });

    it('handles float quantity', function () {
        expect(ProductUnit::formatWithUnit(2.5, 'Ton'))->toBe('2.5 Ton');
    });
});

// ─────────────────────────────────────────────

describe('toBags', function () {
    it('keeps Bag quantity as-is', function () {
        expect(ProductUnit::toBags(375, 'Bag'))->toBe(375.0);
    });

    it('converts Ton to bags using 1000kg / 60kg', function () {
        expect(ProductUnit::toBags(1, 'Ton'))->toBe(1000 / 60);
        expect(ProductUnit::toBags(25, 'Ton'))->toBe(25 * (1000 / 60));
    });

    it('converts KG to bags using / 60', function () {
        expect(ProductUnit::toBags(60, 'KG'))->toBe(1.0);
        expect(ProductUnit::toBags(120, 'KG'))->toBe(2.0);
    });

    it('defaults unknown/null unit to raw quantity', function () {
        expect(ProductUnit::toBags(10, null))->toBe(10.0);
        expect(ProductUnit::toBags(10, 'Quintal'))->toBe(10.0);
    });
});

// ─────────────────────────────────────────────

describe('dispatchedSuffix', function () {
    it('returns bag(s) for Bag unit', function () {
        expect(ProductUnit::dispatchedSuffix('Bag'))->toBe('bag(s)');
    });

    it('returns ton(s) for Ton unit', function () {
        expect(ProductUnit::dispatchedSuffix('Ton'))->toBe('ton(s)');
    });

    it('returns kg for KG unit', function () {
        expect(ProductUnit::dispatchedSuffix('KG'))->toBe('kg');
    });

    it('returns unit(s) when unit is null', function () {
        expect(ProductUnit::dispatchedSuffix(null))->toBe('unit(s)');
    });

    it('returns lowercased suffix with (s) for unknown unit', function () {
        expect(ProductUnit::dispatchedSuffix('Crate'))->toBe('crate(s)');
    });
});
