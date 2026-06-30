<?php

use App\Support\EmailBrandTheme;

describe('colors', function () {
    it('returns an array of color tokens', function () {
        $colors = EmailBrandTheme::colors();
        expect($colors)->toBeArray();
    });

    it('contains expected color keys', function () {
        $colors = EmailBrandTheme::colors();
        expect($colors)->toHaveKeys([
            'primary', 'text_primary', 'text_muted', 'border',
            'section_bg', 'panel_bg', 'card_bg', 'email_cream',
            'link_accent', 'callout_bg', 'callout_border',
            'emphasize_bg', 'emphasize_text',
        ]);
    });

    it('returns the brand primary color as hex', function () {
        expect(EmailBrandTheme::colors()['primary'])->toBe('#014e9c');
    });

    it('returns all values as non-empty strings', function () {
        foreach (EmailBrandTheme::colors() as $key => $value) {
            expect($value)->toBeString()->not->toBeEmpty();
        }
    });
});

// ─────────────────────────────────────────────

describe('badgeStyles', function () {
    it('returns green badge styles for Paid status', function () {
        $style = EmailBrandTheme::badgeStyles('Paid');
        expect($style)->toContain('#D3FFD3')  // green bg
            ->and($style)->toContain('#5CB85C'); // green text
    });

    it('returns amber badge styles for Partial Payment status', function () {
        $style = EmailBrandTheme::badgeStyles('Partial Payment');
        expect($style)->toContain('#FDF3E0')  // amber bg
            ->and($style)->toContain('#B7791F'); // amber text
    });

    it('returns blue/default badge styles for unknown status', function () {
        $style = EmailBrandTheme::badgeStyles('Unpaid');
        expect($style)->toContain('#FFEEEC')  // default bg
            ->and($style)->toContain('#014e9c'); // primary text
    });

    it('badge style contains all required css properties', function () {
        $style = EmailBrandTheme::badgeStyles('Paid');
        expect($style)->toContain('background-color:')
            ->and($style)->toContain('color:')
            ->and($style)->toContain('border:');
    });
});
