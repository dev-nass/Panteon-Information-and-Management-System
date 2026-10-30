<?php

use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\Phase;
use App\Models\User;
use App\Services\BurialRecordService;
use App\Services\RecordNormalizationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_12_015650_create_imported_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_13_213303_create_activity_logs_table.php']);

    Schema::create('phases', function (Blueprint $table) {
        $table->id();
        $table->string('phase_name');
        $table->timestamps();
    });

    Schema::create('clusters', function (Blueprint $table) {
        $table->id();
        $table->foreignId('phase_id');
        $table->string('cluster_name');
        $table->string('cluster_type');
        $table->bigInteger('total_capacity')->nullable();
        $table->timestamps();
    });

    Schema::create('lots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cluster_id');
        $table->string('column');
        $table->string('row');
        $table->timestamps();
    });

    Schema::create('burial_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lot_id')->nullable();
        $table->foreignId('deceased_record_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->string('archived_reason')->nullable();
        $table->foreignId('archived_by')->nullable();
        $table->text('archived_notes')->nullable();
        $table->timestamps();
    });

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123456789',
        'role' => 'clerk',
    ]);

    $this->phase = Phase::create(['phase_name' => '1A']);
    $this->cluster = Cluster::create([
        'phase_id' => $this->phase->id,
        'cluster_name' => '1',
        'cluster_type' => 'apartment',
        'total_capacity' => 10,
    ]);
    $this->lot = Lot::create([
        'cluster_id' => $this->cluster->id,
        'column' => '1',
        'row' => 'A',
    ]);
});

function sharedLotDeceasedData(string $firstName, string $lastName, string $birthDate): array
{
    return [
        'first_name' => $firstName,
        'middle_name' => null,
        'last_name' => $lastName,
        'birth_date' => $birthDate,
        'death_date' => '2020-01-01',
        'burial_date' => '2020-01-02',
        'burial_time' => '09:00',
        'corpse_disposal' => 'burial',
        'address' => 'Dasmarinas',
    ];
}

function sharedLotApplicantData(string $firstName, string $contactNumber): array
{
    return [
        'applicant_first_name' => $firstName,
        'applicant_middle_name' => null,
        'applicant_last_name' => 'Dela Cruz',
        'applicant_contact_number' => $contactNumber,
        'applicant_relationship' => 'Spouse',
    ];
}

it('stores two active burial records on the same lot', function () {
    $service = app(BurialRecordService::class);

    $service->store(
        sharedLotApplicantData('Maria', '09171234567'),
        sharedLotDeceasedData('Juan', 'Dela Cruz', '1950-01-01'),
        ['lot_id' => $this->lot->id],
        $this->clerk->id,
    );

    $service->store(
        sharedLotApplicantData('Pedro', '09171234568'),
        sharedLotDeceasedData('Jose', 'Santos', '1960-02-02'),
        ['lot_id' => $this->lot->id],
        $this->clerk->id,
    );

    expect(BurialRecord::count())->toBe(2)
        ->and(BurialRecord::where('lot_id', $this->lot->id)->count())->toBe(2);
});

it('includes occupied lots in the create data', function () {
    $deceased = DeceasedRecord::factory()->create();

    BurialRecord::create([
        'deceased_record_id' => $deceased->id,
        'lot_id' => $this->lot->id,
        'user_id' => $this->clerk->id,
    ]);

    $phase = app(BurialRecordService::class)
        ->getCreateData()
        ->firstWhere('id', $this->phase->id);

    $cluster = collect($phase['clusters'])->firstWhere('id', $this->cluster->id);
    $lot = collect($cluster['lots'])->firstWhere('id', $this->lot->id);

    expect($lot)->not->toBeNull()
        ->and($lot['is_occupied'])->toBeTrue()
        ->and($lot['burial_count'])->toBe(1);
});

it('keeps the lot when restoring an archived record', function () {
    $first = BurialRecord::create([
        'deceased_record_id' => DeceasedRecord::factory()->create()->id,
        'lot_id' => $this->lot->id,
        'user_id' => $this->clerk->id,
    ]);

    BurialRecord::create([
        'deceased_record_id' => DeceasedRecord::factory()->create()->id,
        'lot_id' => $this->lot->id,
        'user_id' => $this->clerk->id,
    ]);

    $service = app(BurialRecordService::class);
    $service->archive($first, ['archived_reason' => 'transfer'], $this->clerk->id);

    $service->restore($first);

    expect($first->fresh()->lot_id)->toBe($this->lot->id);
});

it('resolves the same lot repeatedly even when it already has a burial record', function () {
    BurialRecord::create([
        'deceased_record_id' => DeceasedRecord::factory()->create()->id,
        'lot_id' => $this->lot->id,
        'user_id' => $this->clerk->id,
    ]);

    $normalizer = app(RecordNormalizationService::class);
    [$lotsMap, $lotsMapGeneric] = $normalizer->buildLotMaps(
        Lot::with('cluster.phase')->get()
    );

    $first = $normalizer->findLotByClusterAndApt(
        $lotsMap, $lotsMapGeneric, '1A', '1', null, 'A', '1'
    );
    $second = $normalizer->findLotByClusterAndApt(
        $lotsMap, $lotsMapGeneric, '1A', '1', null, 'A', '1'
    );

    expect($first?->id)->toBe($this->lot->id)
        ->and($second?->id)->toBe($this->lot->id);
});

it('imports two deceased rows sharing the same lot without an occupied warning', function () {
    $admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123478901',
        'role' => 'admin',
    ]);

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['No.', 'Burial Date', 'Deceased', 'Applicant', 'Phase', 'Cluster', 'Apt', 'Address'],
        [1, '2024-01-01', 'John Doe', '', '1A', '1', '1A', 'Dasmarinas'],
        [2, '2024-01-02', 'Jane Smith', '', '1A', '1', '1A', 'Dasmarinas'],
    ]);

    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'shared-lot-'.uniqid().'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $file = new UploadedFile(
        $path,
        'shared-lot.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );

    actingAs($admin)
        ->post(route('admin.import.store'), [
            'file' => $file,
            'import_type' => 'normal',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    @unlink($path);

    expect(BurialRecord::where('lot_id', $this->lot->id)->count())->toBe(2)
        ->and(session('importErrors'))->toBe([]);
});
