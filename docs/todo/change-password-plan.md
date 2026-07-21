# Change Password — Implementation Plan

This plan adds a **logged-in user change-password** feature (distinct from the
existing guest forgot/reset-password flow). It follows the app's existing
conventions: FormRequest validation, a controller in `app/Http/Controllers/Settings`,
Inertia Vue pages under `resources/js/Pages/Settings`, and routes in `routes/settings.php`.

> The `User` model already has a `password` fillable field and a `hashed` cast
> (`app/Models/User.php:21`, `:51`), so **no model or migration changes are needed**.

---

## 1. Request Class — `ChangePasswordRequest`

**File:** `app/Http/Requests/Settings/ChangePasswordRequest.php`
**Namespace:** `App\Http\Requests\Settings`

Rules:
- `current_password` — `required`, must match the user's existing password.
  Use Laravel's `current_password` rule: `['required', 'current_password']`.
- `password` — `required`, `confirmed`, and meets the same strength rules used in
  `RegistrationRequest` (`Password::min(8)->letters()->mixedCase()->numbers()->symbols()`).
- `password_confirmation` — handled by `confirmed`; not separately declared.

Authorize `true` (any authenticated user may change their own password).

---

## 2. Controller — `PasswordController`

**File:** `app/Http/Controllers/Settings/PasswordController.php`
**Namespace:** `App\Http\Controllers\Settings`

Extend `App\Http\Controllers\Controller` (matches `ProfileController`).

Methods:
- `index()` → `Inertia::render('Settings/ChangePasswordView')`.
- `update(ChangePasswordRequest $request)`:
  1. `$request->user()->update(['password' => Hash::make($request->password)]);`
     - The `hashed` cast means `Hash::make` is optional, but keep it explicit
       for clarity/correctness if cast behavior changes.
  2. return `back()->with('success', 'Password changed successfully.')`.

---

## 3. Routes — `routes/settings.php`

Add inside the existing `auth` + `verified` prefixed group:

```php
use App\Http\Controllers\Settings\PasswordController;

Route::controller(PasswordController::class)->group(function () {
    Route::get('/password', 'index')->name('password.index');
    Route::post('/password', 'update')->name('password.update');
});
```

(These inherit the `settings.` name prefix and `auth`/`verified` middleware.)

---

## 4. Model

**No change required.** `User` already supports `password` via `$fillable`
and the `hashed` cast (`app/Models/User.php`).

---

## 5. UI — `resources/js/Pages/Settings/ChangePasswordView.vue`

Follow the structure/style of `Settings/ProfileView.vue` (sidebar nav, green
theme, `vue-toast-notification`).

Features:
- A sidebar "Security" link that is active on this page (mirrors the disabled
  Security link already present in `ProfileView.vue:163`).
- A form with three fields (reuse the `Display` component or a password input
  variant):
  - `current_password`
  - `password`
  - `password_confirmation`
- Submit via `router.post(route('settings.password.update'), {...})` with
  `preserveScroll: true`.
- On `onSuccess`: toast success + optionally reset fields.
- On `onError`: show server validation errors (`page.props.errors`) and toast error.
- Show `errors.current_password`, `errors.password`, `errors.password_confirmation`
  inline.

---

## 6. Navigation Wiring (optional but recommended)

In `Settings/ProfileView.vue`, change the "Security" sidebar link (currently
`href="#"`) to `:href="route('settings.password.index')"` so users can reach the
change-password page from Profile.

---

## 7. Verification

- Run `vendor/bin/pint --dirty --format agent` on the new PHP files.
- Manual: log in → Profile → Security → enter wrong current password (expect
  validation error) → enter valid new password + confirmation → success toast →
  log out and log back in with the new password.
- Add a Pest feature test (`php artisan make:test --pest ChangePasswordTest`)
  covering: wrong current password rejected, successful change, and password
  strength/confirmation rules.

---

## Summary of Files

| Action | File |
| ------ | ---- |
| Create | `app/Http/Requests/Settings/ChangePasswordRequest.php` |
| Create | `app/Http/Controllers/Settings/PasswordController.php` |
| Create | `resources/js/Pages/Settings/ChangePasswordView.vue` |
| Edit   | `routes/settings.php` (add 2 routes) |
| Edit   | `resources/js/Pages/Settings/ProfileView.vue` (wire Security link) |
| Test   | `tests/Feature/ChangePasswordTest.php` |
