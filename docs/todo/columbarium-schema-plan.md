# Columbarium Support — Schema Plan

## Context

The system currently handles **underground** and **apartment** type lots, each
classified by the `cluster` they belong to. We now need to accommodate
**columbarium** type lots (niches for cremated remains).

## Current Schema (relevant parts)

- `phases` → `clusters` — a cluster belongs to a phase and carries
  `cluster_type` (`enum: underground, apartment, columbarium`).
- `clusters` → `lots` — a lot is identified by `column` + `row` and has spatial
  `coordinates` (geometry, SRID 4326).
- `lots` → `burial_records` → `deceased_records`.

Key observations:

1. `clusters.cluster_type` **already includes `'columbarium'`** in its enum —
   the type dimension is already modeled at the cluster level.
2. The `lots` table has **no `type` column**; a lot's type is implied purely by
   its parent cluster's `cluster_type`.

## Decisions

- **One type per cluster.** A cluster is always a single type; mixing types
  inside one cluster is not supported.
- **Columbarium niches are addressed separately** from underground/apartment
  lots (the exact niche-addressing scheme is not yet finalized).

## Recommendation: Reuse the `lots` table — do NOT create a new table

A columbarium is effectively "a cluster whose slots are niches instead of
burial plots." Its data shape matches the existing model:

- Still grouped under a cluster/phase ✅
- Still needs spatial `coordinates` for mapping ✅
- Still links to `burial_records` → `deceased_records` ✅
- A cluster is always one type, so `cluster_type` already distinguishes it ✅

Creating a separate `columbariums` table would duplicate `cluster_id`,
`coordinates`, the spatial index, the `column`/`row` addressing, and all the
burial-record linkage logic — pure redundancy.

## Implementation Plan

### 1. Migration — extend `lots` (no new table)
Add a nullable `slot_label` string column (e.g. niche number like `"N-12"`)
so columbarium niches can be identified independently of `column`/`row`.
Make `column` and `row` nullable so columbarium lots that only use
`slot_label` are not forced to populate the grid columns.

```php
Schema::table('lots', function (Blueprint $table) {
    $table->string('slot_label')->nullable()->after('row')
        ->comment('Niche/slot label for columbarium lots (e.g. N-12)');
    $table->string('column')->nullable()->change();
    $table->string('row')->nullable()->change();
});
```

### 2. Models
- `Cluster` — already supports `columbarium` in `cluster_type`; optionally add
  a `TYPE_COLUMBARIUM` constant for clarity.
- `Lot` — add `slot_label` to `$fillable`; add a helper:
  ```php
  public function isColumbarium(): bool
  {
      return $this->cluster?->cluster_type === Cluster::TYPE_COLUMBARIUM;
  }
  ```

### 3. Factory / Seeders
- `LotFactory` — allow nullable `column`/`row` and set `slot_label` for
  columbarium lots.

### 4. Validation / UI
- Lot form requests and the Vue Lot form: when
  `cluster.cluster_type === 'columbarium'`, require `slot_label` instead of (or
  in addition to) `column`/`row`.

## Files to touch (when implemented)

- `database/migrations/2026_07_18_xxxxxx_add_slot_label_to_lots_table.php` (new)
- `app/Models/Lot.php`
- `app/Models/Cluster.php` (optional constant)
- `database/factories/LotFactory.php`
- Lot form request(s) and Vue Lot form components

## Open questions (for later)

- Exact columbarium niche-addressing format (numeric, alphanumeric, tier/column/row?).
- Whether a columbarium cluster can hold multiple remains per niche
  (affects `burial_records` cardinality).
