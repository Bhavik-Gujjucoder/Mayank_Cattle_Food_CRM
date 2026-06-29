<?php

use App\Models\SystemBackup;
use App\Models\User;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

describe('access control', function () {
    it('redirects guests from backup index to login', function () {
        get(route('system.backup.index'))
            ->assertRedirect(route('login'));
    });

    it('returns unauthorized for guests on backup list', function () {
        getJson(route('system.backup.list'))
            ->assertUnauthorized();
    });

    it('forbids non-super-admin users from backup index', function () {
        actingAs(adminUser())
            ->get(route('system.backup.index'))
            ->assertForbidden();
    });

    it('forbids dealer users from backup index', function () {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);
        $dealer = User::factory()->create(['status' => 1, 'email_verified_at' => now()]);
        $dealer->assignRole('dealer');

        actingAs($dealer)
            ->get(route('system.backup.index'))
            ->assertForbidden();
    });

    it('allows super admin to access backup index', function () {
        actingAs(superAdminUser())
            ->get(route('system.backup.index'))
            ->assertOk()
            ->assertViewIs('system.backup.index')
            ->assertSee('System Backup');
    });
});

describe('backup list API', function () {
    it('returns backup options payload for super admin', function () {
        actingAs(superAdminUser())
            ->getJson(route('system.backup.list', ['for' => 'options']))
            ->assertOk()
            ->assertJsonStructure(['success', 'initialized', 'backups']);
    });

    it('returns DataTables JSON for super admin', function () {
        actingAs(superAdminUser())
            ->getJson(route('system.backup.list'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });
});

describe('backup destroy', function () {
    it('deletes a backup record and file for super admin', function () {
        $actor = superAdminUser();
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);

        $filename = 'smoke_test_'.uniqid().'.mcfbackup';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, 'test-backup-content');

        $backup = SystemBackup::query()->create([
            'filename' => $filename,
            'file_size' => filesize($path),
            'passphrase' => 'test-pass',
            'created_by' => $actor->id,
        ]);

        actingAs($actor)
            ->delete(route('system.backup.destroy', $backup))
            ->assertOk()
            ->assertJson(['success' => true]);

        expect(SystemBackup::query()->whereKey($backup->id)->exists())->toBeFalse();
        expect(file_exists($path))->toBeFalse();
    });

    it('forbids non-super-admin from deleting backups', function () {
        $backup = SystemBackup::query()->create([
            'filename' => 'forbidden.mcfbackup',
            'file_size' => 0,
            'passphrase' => null,
            'created_by' => null,
        ]);

        actingAs(adminUser())
            ->delete(route('system.backup.destroy', $backup))
            ->assertForbidden();
    });
});

describe('backup download', function () {
    it('returns 404 JSON when backup file is missing', function () {
        actingAs(superAdminUser())
            ->get(route('system.backup.download', 'missing-backup.mcfbackup'))
            ->assertNotFound();
    });

    it('downloads an existing backup file', function () {
        $actor = superAdminUser();
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);

        $filename = 'download_test_'.uniqid().'.mcfbackup';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, 'downloadable-backup');

        SystemBackup::query()->create([
            'filename' => $filename,
            'file_size' => filesize($path),
            'passphrase' => null,
            'created_by' => $actor->id,
        ]);

        actingAs($actor)
            ->get(route('system.backup.download', $filename))
            ->assertOk();

        @unlink($path);
    });
});
