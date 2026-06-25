<?php

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/* ─────────────────────────────────────────────────────────────────────────
 |  Helper — verified factory user (email_verified_at is set by default in
 |  UserFactory, so plain create() already produces a verified user).
 ───────────────────────────────────────────────────────────────────────── */
function settingsUser(): User
{
    return User::factory()->create();
}

/* ═══════════════════════════════════════════════════════════════════════
 |  1. ACCESS CONTROL
 ══════════════════════════════════════════════════════════════════════ */
describe('access control', function () {

    test('guest is redirected to login from the settings page', function () {
        get(route('generalsetting.create'))
            ->assertRedirect(route('login'));
    });

    test('guest cannot post to the store endpoint', function () {
        post(route('generalsetting.store'), [
            'form_type'     => 'general-setting',
            'copyright_msg' => 'Test',
        ])->assertRedirect(route('login'));
    });

    test('authenticated verified user can view the settings page', function () {
        actingAs(settingsUser())
            ->get(route('generalsetting.create'))
            ->assertOk()
            ->assertSee('General Setting')
            ->assertSee('Company Details')
            ->assertSee('Sales');
    });

    test('unverified user can still access the settings page (MustVerifyEmail not enforced)', function () {
        // The User model has MustVerifyEmail commented out, so the `verified` middleware
        // is a no-op. Unverified users are treated the same as verified ones.
        $unverified = User::factory()->unverified()->create();

        actingAs($unverified)
            ->get(route('generalsetting.create'))
            ->assertOk();
    });

    test('unverified user can post to the store endpoint (MustVerifyEmail not enforced)', function () {
        $unverified = User::factory()->unverified()->create();

        actingAs($unverified)
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => 'Test',
            ])->assertSessionHasNoErrors();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  2. GENERAL SETTING TAB — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('general setting tab — validation', function () {

    test('copyright_msg is required', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => '',
            ])
            ->assertSessionHasErrors('copyright_msg');
    });

    test('copyright_msg missing from payload is a validation error', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type' => 'general-setting',
                // copyright_msg omitted entirely
            ])
            ->assertSessionHasErrors('copyright_msg');
    });

    test('login_page_image must be an image file', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'        => 'general-setting',
                'copyright_msg'    => 'Test',
                'login_page_image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
            ])
            ->assertSessionHasErrors('login_page_image');
    });

    test('login_page_image rejects files larger than 2MB', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'        => 'general-setting',
                'copyright_msg'    => 'Test',
                'login_page_image' => UploadedFile::fake()->image('huge.png')->size(3000), // 3 MB
            ])
            ->assertSessionHasErrors('login_page_image');
    });

    test('login_page_image rejects disallowed mime type (webp)', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'        => 'general-setting',
                'copyright_msg'    => 'Test',
                'login_page_image' => UploadedFile::fake()->create('image.webp', 100, 'image/webp'),
            ])
            ->assertSessionHasErrors('login_page_image');
    });

    test('login_page_image is optional — omitting it passes validation', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => 'Valid copyright',
            ])
            ->assertSessionHasNoErrors();
    });

    test('accepted raster image mimes — jpg, jpeg, png, gif all pass validation', function () {
        Storage::fake('public');
        $user = settingsUser();

        // SVG is excluded: UploadedFile::fake()->image() falls back to PNG binary for
        // unsupported extensions, so the detected mime is image/png — not image/svg+xml —
        // and the mimes:svg check fails. Additionally, the `image` rule relies on PHP's
        // getimagesize() which does not support SVG, so SVG will always be rejected by
        // the `image` rule regardless of file content. This is a known Laravel limitation.
        foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
            $response = actingAs($user)
                ->post(route('generalsetting.store'), [
                    'form_type'        => 'general-setting',
                    'copyright_msg'    => 'Test',
                    'login_page_image' => UploadedFile::fake()->image("login.{$ext}"),
                ]);

            $response->assertSessionHasNoErrors();
        }
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  3. GENERAL SETTING TAB — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('general setting tab — persistence', function () {

    test('copyright_msg is persisted to the database', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => '© 2025 Cattle Food CRM. All rights reserved.',
            ]);

        assertDatabaseHas('general_settings', [
            'key'   => 'copyright_msg',
            'value' => '© 2025 Cattle Food CRM. All rights reserved.',
        ]);
    });

    test('copyright_msg is updated when a record already exists', function () {
        GeneralSetting::create(['key' => 'copyright_msg', 'value' => 'Old message']);

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => 'New updated message',
            ]);

        assertDatabaseHas('general_settings', [
            'key'   => 'copyright_msg',
            'value' => 'New updated message',
        ]);
        expect(GeneralSetting::where('key', 'copyright_msg')->count())->toBe(1);
    });

    test('form_type is not stored as a setting key', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => 'Test',
            ]);

        assertDatabaseMissing('general_settings', ['key' => 'form_type']);
    });

    test('login_page_image file is uploaded and filename stored in database', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'        => 'general-setting',
                'copyright_msg'    => 'Test',
                'login_page_image' => UploadedFile::fake()->image('login-bg.png', 1920, 1080),
            ]);

        $setting = GeneralSetting::where('key', 'login_page_image')->first();

        expect($setting)->not->toBeNull();
        expect(Storage::disk('public')->exists('login_page_image/' . $setting->value))->toBeTrue();
    });

    test('login_page_image replaces previous file record on second upload', function () {
        Storage::fake('public');
        $user = settingsUser();

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'        => 'general-setting',
            'copyright_msg'    => 'First',
            'login_page_image' => UploadedFile::fake()->image('first.png'),
        ]);

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'        => 'general-setting',
            'copyright_msg'    => 'Second',
            'login_page_image' => UploadedFile::fake()->image('second.png'),
        ]);

        expect(GeneralSetting::where('key', 'login_page_image')->count())->toBe(1);
    });

    test('store redirects back after saving general settings', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => 'Test',
            ])
            ->assertRedirect();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  4. COMPANY DETAILS TAB — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('company details tab — validation', function () {

    /* -- Required fields -- */

    test('company_email is required', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => '',
                'company_phone'   => '9876543210',
                'company_address' => '123 Farm Road, Gujarat',
            ])
            ->assertSessionHasErrors('company_email');
    });

    test('company_phone is required', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '',
                'company_address' => '123 Farm Road, Gujarat',
            ])
            ->assertSessionHasErrors('company_phone');
    });

    test('company_address is required', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => '',
            ])
            ->assertSessionHasErrors('company_address');
    });

    /* -- Email format -- */

    test('company_email must be a valid email address', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'not-an-email',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
            ])
            ->assertSessionHasErrors('company_email');
    });

    test('company_email rejects email without domain', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'user@',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
            ])
            ->assertSessionHasErrors('company_email');
    });

    test('company_email accepts a valid email format', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'contact@cattle-food.co.in',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
            ])
            ->assertSessionHasNoErrors();
    });

    /* -- Logo image rules -- */

    test('company_logo must be an image file', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
                'company_logo'    => UploadedFile::fake()->create('logo.pdf', 500, 'application/pdf'),
            ])
            ->assertSessionHasErrors('company_logo');
    });

    test('company_logo rejects files larger than 2MB', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
                'company_logo'    => UploadedFile::fake()->image('big-logo.jpg')->size(3000),
            ])
            ->assertSessionHasErrors('company_logo');
    });

    test('company_logo is optional — submitting without it passes validation', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
            ])
            ->assertSessionHasNoErrors();
    });

    test('all three required fields missing produces three distinct errors', function () {
        $response = actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type' => 'company-detail',
            ]);

        $response->assertSessionHasErrors(['company_email', 'company_phone', 'company_address']);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  5. COMPANY DETAILS TAB — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('company details tab — persistence', function () {

    test('all three company detail fields are persisted', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => '123 Farm Road, Ahmedabad, Gujarat 380001',
            ]);

        assertDatabaseHas('general_settings', ['key' => 'company_email',   'value' => 'info@cattle.com']);
        assertDatabaseHas('general_settings', ['key' => 'company_phone',   'value' => '9876543210']);
        assertDatabaseHas('general_settings', ['key' => 'company_address', 'value' => '123 Farm Road, Ahmedabad, Gujarat 380001']);
    });

    test('existing company email record is updated without duplicating', function () {
        GeneralSetting::create(['key' => 'company_email', 'value' => 'old@example.com']);

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'new@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
            ]);

        assertDatabaseHas('general_settings', ['key' => 'company_email', 'value' => 'new@cattle.com']);
        expect(GeneralSetting::where('key', 'company_email')->count())->toBe(1);
    });

    test('company_logo file is uploaded and filename stored in database', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
                'company_logo'    => UploadedFile::fake()->image('logo.png', 300, 100),
            ]);

        $setting = GeneralSetting::where('key', 'company_logo')->first();

        expect($setting)->not->toBeNull();
        expect(Storage::disk('public')->exists('company_logo/' . $setting->value))->toBeTrue();
    });

    test('company_logo replaces previous file record on second upload', function () {
        Storage::fake('public');
        $user = settingsUser();

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'       => 'company-detail',
            'company_email'   => 'info@cattle.com',
            'company_phone'   => '9876543210',
            'company_address' => 'Farm Road',
            'company_logo'    => UploadedFile::fake()->image('logo-v1.png'),
        ]);

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'       => 'company-detail',
            'company_email'   => 'info@cattle.com',
            'company_phone'   => '9876543210',
            'company_address' => 'Farm Road',
            'company_logo'    => UploadedFile::fake()->image('logo-v2.png'),
        ]);

        expect(GeneralSetting::where('key', 'company_logo')->count())->toBe(1);
    });

    test('company logo filename stored contains original file name', function () {
        Storage::fake('public');

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
                'company_logo'    => UploadedFile::fake()->image('my-logo.jpg'),
            ]);

        $setting = GeneralSetting::where('key', 'company_logo')->first();
        expect($setting->value)->toContain('my-logo.jpg');
    });

    test('store redirects back after saving company details', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => 'Farm Road',
            ])
            ->assertRedirect();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  6. SALES TAB — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('sales tab — validation', function () {

    test('payment_due_days must be an integer — decimal fails', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 1.5,
                'payment_due_amount' => 100,
            ])
            ->assertSessionHasErrors('payment_due_days');
    });

    test('payment_due_days must not be negative', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => -1,
                'payment_due_amount' => 100,
            ])
            ->assertSessionHasErrors('payment_due_days');
    });

    test('payment_due_amount must be numeric — string fails', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 30,
                'payment_due_amount' => 'not-a-number',
            ])
            ->assertSessionHasErrors('payment_due_amount');
    });

    test('payment_due_amount must not be negative', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 30,
                'payment_due_amount' => -0.01,
            ])
            ->assertSessionHasErrors('payment_due_amount');
    });

    test('zero is valid for payment_due_days (min:0 boundary)', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 0,
                'payment_due_amount' => 0,
            ])
            ->assertSessionHasNoErrors();
    });

    test('both fields are optional — omitting them passes validation', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type' => 'sales',
            ])
            ->assertSessionHasNoErrors();
    });

    test('decimal payment_due_amount passes validation', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 7,
                'payment_due_amount' => 12.75,
            ])
            ->assertSessionHasNoErrors();
    });

    test('large integer payment_due_days passes validation', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 365,
                'payment_due_amount' => 9999.99,
            ])
            ->assertSessionHasNoErrors();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  7. SALES TAB — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('sales tab — persistence', function () {

    test('payment_due_days and payment_due_amount are persisted', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 30,
                'payment_due_amount' => 500,
            ]);

        assertDatabaseHas('general_settings', ['key' => 'payment_due_days',   'value' => '30']);
        assertDatabaseHas('general_settings', ['key' => 'payment_due_amount', 'value' => '500']);
    });

    test('existing sales settings are updated without duplicating rows', function () {
        GeneralSetting::create(['key' => 'payment_due_days',   'value' => '10']);
        GeneralSetting::create(['key' => 'payment_due_amount', 'value' => '100']);

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 45,
                'payment_due_amount' => 750,
            ]);

        assertDatabaseHas('general_settings', ['key' => 'payment_due_days',   'value' => '45']);
        assertDatabaseHas('general_settings', ['key' => 'payment_due_amount', 'value' => '750']);
        expect(GeneralSetting::where('key', 'payment_due_days')->count())->toBe(1);
        expect(GeneralSetting::where('key', 'payment_due_amount')->count())->toBe(1);
    });

    test('empty payment_due_days defaults to 0 in the database', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => '',
                'payment_due_amount' => 200,
            ]);

        assertDatabaseHas('general_settings', ['key' => 'payment_due_days', 'value' => '0']);
    });

    test('empty payment_due_amount defaults to 0 in the database', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 15,
                'payment_due_amount' => '',
            ]);

        assertDatabaseHas('general_settings', ['key' => 'payment_due_amount', 'value' => '0']);
    });

    test('both fields empty both default to 0', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => '',
                'payment_due_amount' => '',
            ]);

        assertDatabaseHas('general_settings', ['key' => 'payment_due_days',   'value' => '0']);
        assertDatabaseHas('general_settings', ['key' => 'payment_due_amount', 'value' => '0']);
    });

    test('decimal payment_due_amount is stored correctly', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 7,
                'payment_due_amount' => 12.75,
            ]);

        $setting = GeneralSetting::where('key', 'payment_due_amount')->first();
        expect((float) $setting->value)->toBe(12.75);
    });

    test('store redirects back after saving sales settings', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'          => 'sales',
                'payment_due_days'   => 30,
                'payment_due_amount' => 500,
            ])
            ->assertRedirect();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  8. getSetting() HELPER
 ══════════════════════════════════════════════════════════════════════ */
describe('getSetting helper', function () {

    test('returns empty string when the key does not exist', function () {
        expect(getSetting('nonexistent_setting_key'))->toBe('');
    });

    test('returns the stored value when the key exists', function () {
        GeneralSetting::create(['key' => 'copyright_msg', 'value' => '© Cattle CRM 2025']);

        expect(getSetting('copyright_msg'))->toBe('© Cattle CRM 2025');
    });

    test('returns the updated value after the record is changed', function () {
        GeneralSetting::create(['key' => 'company_email', 'value' => 'old@example.com']);
        GeneralSetting::where('key', 'company_email')->update(['value' => 'new@cattle.com']);

        expect(getSetting('company_email'))->toBe('new@cattle.com');
    });

    test('returns empty string for a key with a null value', function () {
        GeneralSetting::create(['key' => 'nullable_field', 'value' => null]);

        expect(getSetting('nullable_field'))->toBe('');
    });

    test('each different key returns its own independent value', function () {
        GeneralSetting::create(['key' => 'company_email',   'value' => 'email@example.com']);
        GeneralSetting::create(['key' => 'company_phone',   'value' => '9876543210']);
        GeneralSetting::create(['key' => 'company_address', 'value' => 'Farm Road']);

        expect(getSetting('company_email'))->toBe('email@example.com');
        expect(getSetting('company_phone'))->toBe('9876543210');
        expect(getSetting('company_address'))->toBe('Farm Road');
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  9. EDGE CASES & CROSS-TAB BEHAVIOUR
 ══════════════════════════════════════════════════════════════════════ */
describe('edge cases and cross-tab behaviour', function () {

    test('second save for the same tab updates records without creating duplicates', function () {
        $user = settingsUser();

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'     => 'general-setting',
            'copyright_msg' => 'First version',
        ]);

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'     => 'general-setting',
            'copyright_msg' => 'Second version',
        ]);

        expect(GeneralSetting::where('key', 'copyright_msg')->count())->toBe(1);
        assertDatabaseHas('general_settings', ['key' => 'copyright_msg', 'value' => 'Second version']);
    });

    test('settings from different tabs coexist in the database independently', function () {
        $user = settingsUser();

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'     => 'general-setting',
            'copyright_msg' => 'CRM Copyright',
        ]);

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'       => 'company-detail',
            'company_email'   => 'info@cattle.com',
            'company_phone'   => '9876543210',
            'company_address' => 'Farm Road, Gujarat',
        ]);

        actingAs($user)->post(route('generalsetting.store'), [
            'form_type'          => 'sales',
            'payment_due_days'   => 30,
            'payment_due_amount' => 500,
        ]);

        assertDatabaseHas('general_settings', ['key' => 'copyright_msg']);
        assertDatabaseHas('general_settings', ['key' => 'company_email']);
        assertDatabaseHas('general_settings', ['key' => 'company_phone']);
        assertDatabaseHas('general_settings', ['key' => 'company_address']);
        assertDatabaseHas('general_settings', ['key' => 'payment_due_days']);
        assertDatabaseHas('general_settings', ['key' => 'payment_due_amount']);
    });

    test('form_type field is never persisted as a setting', function () {
        $user = settingsUser();

        foreach (['general-setting', 'company-detail', 'sales'] as $type) {
            actingAs($user)->post(route('generalsetting.store'), [
                'form_type'       => $type,
                'copyright_msg'   => 'Test',        // general-setting
                'company_email'   => 'x@x.com',     // company-detail
                'company_phone'   => '1234567890',   // company-detail
                'company_address' => 'Test Address', // company-detail
            ]);
        }

        assertDatabaseMissing('general_settings', ['key' => 'form_type']);
    });

    test('unknown form_type still persists submitted fields without validation errors', function () {
        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'  => 'unknown-tab',
                'custom_key' => 'custom_value',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        assertDatabaseHas('general_settings', [
            'key'   => 'custom_key',
            'value' => 'custom_value',
        ]);
    });

    test('long text value is stored correctly in copyright_msg', function () {
        // Avoid trailing spaces — some DB drivers silently strip them from TEXT columns.
        // 'CRM System Copyright' = 20 chars × 105 = 2100 chars, ends with 't'.
        $longMsg = str_repeat('CRM System Copyright', 105);

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'     => 'general-setting',
                'copyright_msg' => $longMsg,
            ]);

        // assertDatabaseHas generates a SQL WHERE clause that can be unreliable for very
        // long strings on TEXT/LONGTEXT columns. Query by key and compare in PHP instead.
        $setting = GeneralSetting::where('key', 'copyright_msg')->first();
        expect($setting)->not->toBeNull();
        expect(strlen($setting->value))->toBe(2100);
        expect($setting->value)->toBe($longMsg);
    });

    test('multi-line company address is stored correctly', function () {
        $address = "Plot 12, GIDC Industrial Area\nAnkleshwar, Bharuch\nGujarat - 393002";

        actingAs(settingsUser())
            ->post(route('generalsetting.store'), [
                'form_type'       => 'company-detail',
                'company_email'   => 'info@cattle.com',
                'company_phone'   => '9876543210',
                'company_address' => $address,
            ]);

        assertDatabaseHas('general_settings', [
            'key'   => 'company_address',
            'value' => $address,
        ]);
    });

    test('settings page renders existing saved values for display', function () {
        GeneralSetting::create(['key' => 'copyright_msg', 'value' => '© Cattle Food CRM']);

        actingAs(settingsUser())
            ->get(route('generalsetting.create'))
            ->assertOk()
            ->assertSee('© Cattle Food CRM');
    });

});
