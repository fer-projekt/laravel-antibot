# Laravel AntiBot (fer-projekt)

Honeypot + minimal time + HMAC signature for Laravel forms — **zero-config** and plug-and-play.  
Works with **Laravel 7–11** and **PHP 7.4+**.

### Installation

- composer config repositories.fer-antibot vcs https://github.com/fer-projekt/laravel-antibot
- composer require fer-projekt/laravel-antibot
- php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=config
- - (optional) publish views if you want to override:
- -  php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=views

### ✅ Quick integration:
1. add -> @include('antibot::fields', antibot_data('contact')) **--into your form--**
2. add -> AntiBot::check($request, 'contact') **--into your controller--**  
- 2.1  **or add**  route middleware ->middleware('antibot:contact').

---

## Table of contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Usage](#usage)
  - [Blade component](#blade-component)
  - [Controller (1 line)](#controller-1-line)
  - [Route middleware (no controller code)](#route-middleware-no-controller-code)
  - [Helper function](#helper-function)
  - [FormRequest example](#formrequest-example)
- [Rate limiting (recommended)](#rate-limiting-recommended)
- [Configuration](#configuration)
- [Override views](#override-views)
- [How it works](#how-it-works)
- [Tips and notes](#tips-and-notes)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Requirements

- PHP **7.4+**
- Laravel **7.x – 11.x**

---

## Installation

```bash
composer config repositories.fer-antibot vcs https://github.com/fer-projekt/laravel-antibot
composer require fer-projekt/laravel-antibot
php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=config
# (optional) publish views if you want to override:
# php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=views
```

---

## Quick start

1) In your **Blade form**, add the fields:

```blade
@include('antibot::fields', antibot_data('contact'))
```

2) In your **controller**, check anti-bot in one line **or** use **middleware**:

```php
use FerProjekt\AntiBot\AntiBot;

AntiBot::check($request, 'contact');
// ...rest of validation / saving
```

**Alternative:**

```php
Route::post('/contact', [ContactController::class, 'store'])
     ->middleware('antibot:contact');
```

---

## Usage

### Blade component

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    @include('antibot::fields', antibot_data('contact'))

    <!-- your fields -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>

    <button type="submit">Send</button>
</form>
```

### Controller (1 line)

```php
use Illuminate\Http\Request;
use FerProjekt\AntiBot\AntiBot;

class ContactController
{
    public function store(Request $request)
    {
        AntiBot::check($request, 'contact'); // ✅

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'message' => 'required|string|min:10',
        ]);

        // ... save / mail ...
        return back()->with('status', 'Message sent!');
    }
}
```

### Route middleware (no controller code)

```php
use App\Http\Controllers\ContactController;

Route::post('/contact', [ContactController::class, 'store'])
     ->middleware('antibot:contact');
```

### Helper function

This package ships with a helper `antibot_verify()`:

```php
antibot_verify($request, 'contact'); // same as AntiBot::check($request, 'contact')
```

### FormRequest example

```php
use Illuminate\Foundation\Http\FormRequest;
use FerProjekt\AntiBot\AntiBot;

class StoreContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required','string','max:120'],
            'email'   => ['required','email'],
            'message' => ['required','string','min:10'],
        ];
    }

    protected function passedValidation(): void
    {
        AntiBot::check($this, 'contact');
    }
}
```

---

## Rate limiting (recommended)

### Laravel 7 — classic throttle
```php
Route::post('/contact', [ContactController::class, 'store'])
     ->middleware(['antibot:contact','throttle:10,1']); // 10 req/min per IP
```

### Laravel 8+ — `RateLimiter` API
```php
// App\Providers\RouteServiceProvider@boot
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

RateLimiter::for('form-contact', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->ip()),
        Limit::perMinute(10)->by($request->session()->getId()),
    ];
});

// route
Route::post('/contact', [ContactController::class, 'store'])->middleware(['antibot:contact','throttle:form-contact']);
```

---

## Configuration

File: `config/antibot.php`

```php
return [
    'min_seconds' => 3,        // minimal time to fill the form
    'max_seconds' => 7200,     // maximum validity of signature
    'honeypot_prefix' => '_hp_',

    // Optionally include IP in signature (stricter; watch out for proxy/load balancer setup):
    'include_ip_in_signature' => false,
];
```
---

## Override views

If you want to customize the markup / hiding of honeypot fields:

```bash
php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=views
```

Then edit `resources/views/vendor/antibot/fields.blade.php`.

---

## How it works

1. **Component** generates:
   - `_ab_form` (expected form ID)
   - `_ab_ts` (timestamp when form was rendered)
   - `_ab_sig` (HMAC signature of the data; key is `APP_KEY`)
   - **honeypot** field with a **dynamic name** (prefix defined in config)

2. **Check** (`AntiBot::check` or `middleware`):
   - Form ID must match
   - Honeypot must be **empty**
   - At least `min_seconds` passed, but less than `max_seconds`
   - HMAC signature is valid (optionally bound to IP)

3. **CSRF** protection works separately (Laravel `VerifyCsrfToken` middleware).

---

## License

MIT © fer-projekt
