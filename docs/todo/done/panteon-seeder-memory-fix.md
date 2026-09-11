# Panteon Seeder Memory Fix — Reading is the Problem, Not Seeding

## Summary

**Reading is the problem, not seeding.** `database/seeders/PanteonDataSeeder.php:212` `deceasedRecordsBurial()` correctly picks column indexes (`B=burial date`, `C=deceased name`, `E=phase`, `F=cluster`, `G=apt`, `H=address`) and seeds exactly `~21k` rows. The spike (`3.5GB -> 7GB`, previously `100k+` / `1M` rows reported) came from **how the Excel was read**, not how it was seeded.

Two phantom formatting issues in `public/data/pppanteon-cleaned-data.xlsx`:

| Axis | Phantom | Real | File Evidence |
|---|---|---|---|
| Rows | `G1048563="B"` at Excel max row (`1,048,563`) | `21314` | `xl/worksheets/sheet1.xml` `dimension ref="A1:Q1048563"` + `<row r="1048563"><c r="G1048563" t="s"><v>42648</v></c></row>` (`sharedStrings[42648]="B"`) |
| Columns | Formatting to `WVR` (`16138` = `16k` cols) | `Q` (`17` cols) | `xl/worksheets/sheet1.xml` `577` `<col>` entries covering `max="16384"` to `WVR`; `getHighestColumn()=WVR` vs `getHighestDataColumn()=Q` |

`$worksheet->toArray()` `vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Worksheet/Worksheet.php:3361` expands to `getHighestColumn()`/`getHighestRow()` (formatted extents), so old code built `21314 x 16138 = 340M` cells (`~6GB`, `27s` measured) instead of `21314 x 17 = 362k` cells (`16MB`, `0.87s`). Row phantom previously inflated to `1M x 16k` (~1,048,563 rows). User fixed rows (`A1:Q1048563` -> `A1:Q21314`, `21314` tags) but columns remained painted, so `7GB` jump persisted.

---

## 1. Current State (Verified Read-Only)

| Check | Result |
|---|---|
| `public/data/panteon-cleaned-data.xlsx` | Healthy: `21311` tags, `H/H` cols `5`, `toArray` `8MB` |
| `public/data/pppanteon-cleaned-data.xlsx` (HEAD) | Broken: `21315` tags, `dimension A1:Q1048563`, `G1048563`, `colDims 16138`, `toArray 1048563x16138` |
| `public/data/pppanteon-cleaned-data.xlsx` (disk, user cleaned) | Partially fixed: `21314` tags, `dimension A1:Q21314`, no `1048563`, but `colDims 577`, `highestColumn WVR`, `highestDataColumn Q` |
| `PanteonDataSeeder.php:227` old | `IOFactory::load()->toArray()` -> `6GB` array even after row fix |
| `PanteonDataSeeder.php:268` old | `Lot::whereHas(...)->whereDoesntHave()->first()` per row = `21k` queries (N+1) |
| `DeceasedRecord.php:43` | `saving::computeAge()` fires per `create()` - correct, not the memory cause |
| `app/Http/Controllers/Admin/ImportingController.php:54` | Same `toArray()` bug (fixed now) |

Measurement (read-only `php -r`):
```
readDataOnly=false toArray: 21314x16138, 27.02s, mem diff 6094MB, peak 6234MB
readDataOnly=true  rangeToArray A1:Q21314: 21314x17, 0.87s, mem diff 16MB
rangeToArray with fixed code: 132MB load, 16MB array, peak 144MB after gc
```

---

## 2. What Was Fixed (Seeder Only, per Request)

### 2.1 Bounded Reading `database/seeders/PanteonDataSeeder.php:224-233`

```php
// Before
$spreadsheet = IOFactory::load($excelPath);
$worksheet = $spreadsheet->getActiveSheet();
$rows = $worksheet->toArray();

// After
$reader = IOFactory::createReaderForFile($excelPath);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($excelPath);
$worksheet = $spreadsheet->getActiveSheet();
$highestDataRow = $worksheet->getHighestDataRow(); // 21314
$highestDataColumn = $worksheet->getHighestDataColumn(); // Q
$rows = $worksheet->rangeToArray("A1:{$highestDataColumn}{$highestDataRow}", null, true, false, false);
```

Why `rangeToArray` not just `setReadDataOnly`: `ColumnAndRowAttributes.php:188` keeps `width` even with `readDataOnly=true`, so `highestColumn` stays `WVR`. `rangeToArray` with `highestDataColumn` is the reliable bound. Seeding indexes (`$row[1], [2], [4], [5], [6], [7]`) unchanged.

### 2.2 Lot Cache `database/seeders/PanteonDataSeeder.php:245-286`

```php
// Before: 21k queries
$lot = Lot::where('column', $column)->where('row', $rowLetter)->whereHas('cluster', ...)->whereDoesntHave('burialRecords')->first();

// After: 1 query
$lotsMap = Lot::with('cluster.phase')->get()->keyBy(fn(Lot $lot) => $lot->cluster->phase->phase_name.'|'.$lot->cluster->cluster_name.'|'.$lot->row.'|'.$lot->column);
$usedLotIds = [];
// inside loop
$key = $phaseName.'|'.$clusterName.'|'.$rowLetter.'|'.$column;
$candidate = $lotsMap[$key] ?? null;
if ($candidate && !isset($usedLotIds[$candidate->id])) { $lot = $candidate; $usedLotIds[$lot->id] = true; }
```

### 2.3 Memory Hygiene `database/seeders/PanteonDataSeeder.php:346-351`

```php
$spreadsheet->disconnectWorksheets();
unset($spreadsheet, $worksheet, $rows, $lotsMap, $usedLotIds, $chunk);
gc_collect_cycles();
```

`DeceasedRecord::create()` kept (fires `DeceasedRecord.php:44` `saving::computeAge`) — bulk `insert()` would skip events and leave `age` null unless manually precomputed via `RecordNormalizationService.php:100`. Per-row `create()` is fine after `6GB -> 16MB` fix.

---

## 3. Files Modified

| File | Change |
|---|---|
| `database/seeders/PanteonDataSeeder.php` | Bounded read + lot cache + gc (`224-233`, `245-286`, `346-351`) |
| `app/Http/Controllers/Admin/ImportingController.php` | Same fix applied: `setReadDataOnly` + `rangeToArray` (`54-62`), lot cache (`81-85`, `134-150`), gc (`212-219`, `257-263`) |
| `public/data/pppanteon-cleaned-data.xlsx` | User cleaned rows (commit pending: `A1:Q1048563` -> `A1:Q21314`) - columns still painted to `WVR` |

### Files NOT Modified

* `public/data/panteon-cleaned-data.xlsx` - already healthy
* `app/Models/DeceasedRecord.php` - `computeAge` unchanged

---

## 4. Verification

```bash
php -r 'require "vendor/autoload.php"; use PhpOffice\PhpSpreadsheet\IOFactory; $s=IOFactory::createReaderForFile("public/data/pppanteon-cleaned-data.xlsx")->setReadDataOnly(true)->load("public/data/pppanteon-cleaned-data.xlsx")->getActiveSheet(); echo $s->getHighestDataRow()." ".$s->getHighestDataColumn()." ".$s->getHighestColumn();'
# expect: 21314 Q WVR (WVR pending file strip)

vendor/bin/pint --dirty --format agent # passed
php artisan test --compact # 60 passed, 2 pre-existing failures (AdminBackupTest, AdminUserManagementTest)
php artisan test --compact --filter="BurialRecordDuplicate|Dashboard" # 18 passed
```

Manual: `php artisan db:seed --class=PanteonDataSeeder` should log `Total rows to process: 21313` (not `1M`) and peak `<500MB` (was `7GB`). Monitor `memory_get_peak_usage(true)`.

---

## 5. Simple Explanation

Excel is like a small `21k x 8` table painted on a huge `21k x 16k` sheet. Old code read the whole painted sheet (`16k` cols). New code reads only `A-Q` (`17` cols). Seeding always picked the right columns (`B,C,E,F,G,H`) — it was correct, just forced to loop over a giant empty table first.

---

## 6. Next Steps / Out of Scope

* **Fully clean file:** Strip `<cols>` beyond `Q` (`colDims 577 -> ~5`) so `getHighestColumn()=Q` even without code fix. Script or re-export `A:Q` only.
* **Optional speed:** Bulk `DeceasedRecord::insert()` with manual `age` precompute if `21k` per-row `create()` still too slow (tradeoff: skips `saving` events - `DeceasedRecord.php:43`).

## 7. Importing Fix Details (Applied 2026-09-11)

Same root cause (`toArray()` -> `WVR` 16k cols). Applied to `ImportingController.php:54`:

```php
$reader = IOFactory::createReaderForFile($file->getRealPath());
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file->getRealPath());
$worksheet = $spreadsheet->getActiveSheet();
$highestDataRow = $worksheet->getHighestDataRow();
$highestDataColumn = $worksheet->getHighestDataColumn();
$rows = $worksheet->rangeToArray("A1:{$highestDataColumn}{$highestDataRow}", null, true, false, false);
```

Lot cache extended to import types `normal`/`muslim`/`columbarium` with `strtoupper` for row letter and `usedLotIds` seeded from `Lot::whereHas('burialRecords')->pluck('id')` to respect already occupied lots transactionally.
