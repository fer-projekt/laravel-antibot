# Laravel AntiBot (fer-projekt)

**Advanced bot protection for Laravel forms** — Honeypot + Time validation + HMAC signatures + Rate limiting + JavaScript detection + **Automatic unique form IDs**.  
Works with **Laravel 7–11** and **PHP 7.4+**.

## 🛡️ Protection Features

✅ **Automatic unique form IDs** - Zero-config protection for multiple forms  
✅ **Honeypot fields** - Hidden traps for bots  
✅ **Time validation** - Prevents instant form submissions  
✅ **HMAC signatures** - Cryptographic form integrity  
✅ **Rate limiting** - Configurable attempts per hour (20/hour default)  
✅ **JavaScript detection** - Blocks non-JS clients (bots)  
✅ **Browser fingerprinting** - Screen resolution, language, timezone validation  
✅ **Automatic error handling** - Built-in user-friendly error messages  

---

## 📦 Installation

```bash
composer config repositories.fer-antibot vcs https://github.com/fer-projekt/laravel-antibot
composer require fer-projekt/laravel-antibot
php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=config
# (optional) publish views if you want to override:
# php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=views
```

---

## 🚀 Quick Start

**🎯 Auto Mode (Recommended - Zero Config):**

**1) Add to your Blade form:**
```blade
@include('antibot::fields', antibot_data())
```

**2) Add to your controller:**
```php
use FerProjekt\AntiBot\AntiBot;

public function contactMail(Request $request)
{
    AntiBot::check($request); // No form ID needed!
    
    // ... rest of your validation/logic
}
```

**🛠️ Manual Mode (For specific control):**

**1) Add to your Blade form:**
```blade
@include('antibot::fields', antibot_data('contact'))
```

**2) Add to your controller:**
```php
AntiBot::check($request, 'contact'); // Specific form ID
```

That's it! Your form is now protected against bots with **automatic unique IDs** or manual control.

---

## 📋 Table of Contents

- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Usage Examples](#-usage-examples)
- [Configuration](#-configuration)
- [Protection Features](#-protection-features)
- [Error Handling](#-error-handling)
- [Testing](#-testing)
- [Troubleshooting](#-troubleshooting)

---

## 💡 Usage Examples

### Basic Form Protection (Auto Mode)

```blade
<form method="POST" action="{{ route('contact-mail') }}">
    @include('antibot::fields', antibot_data()) {{-- Auto unique ID --}}
    
    <!-- Your form fields -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>
    
    <button type="submit">Send Message</button>
</form>
```

### Multiple Forms on Same Page (Auto Mode)

```blade
{{-- Newsletter form --}}
<form method="POST" action="{{ route('newsletter') }}">
    @include('antibot::fields', antibot_data()) {{-- Unique ID: auto_a1b2c3d4 --}}
    <input type="email" name="email" required>
    <button type="submit">Subscribe</button>
</form>

{{-- Contact form --}}  
<form method="POST" action="{{ route('contact') }}">
    @include('antibot::fields', antibot_data()) {{-- Unique ID: auto_e5f6g7h8 --}}
    <input type="text" name="name" required>
    <textarea name="message" required></textarea>
    <button type="submit">Send</button>
</form>
```

### Controller Implementation (Auto Mode)

```php
use Illuminate\Http\Request;
use FerProjekt\AntiBot\AntiBot;

class MailController
{
    public function contactMail(Request $request)
    {
        // AntiBot validation - no form ID needed!
        AntiBot::check($request);
        
        // Your normal validation
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'message' => 'required|string|min:10',
        ]);
        
        // Process the form...
        return back()->with('success', 'Message sent!');
    }
    
    public function newsletter(Request $request)
    {
        AntiBot::check($request); // Works automatically!
        // ... newsletter logic
    }
}
```

### Controller Implementation (Manual Mode)

```php
class MailController
{
    public function contactMail(Request $request)
    {
        // Specific form ID validation
        AntiBot::check($request, 'contact');
        // ... rest of logic
    }
    
    public function newsletter(Request $request)
    {
        AntiBot::check($request, 'newsletter');
        // ... newsletter logic
    }
}
```

### FormRequest Integration (Auto Mode)

```php
use Illuminate\Foundation\Http\FormRequest;
use FerProjekt\AntiBot\AntiBot;

class ContactFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'min:10'],
        ];
    }
    
    protected function passedValidation(): void
    {
        AntiBot::check($this); // Auto mode - no form ID needed!
    }
}
```

### FormRequest Integration (Manual Mode)

```php
class ContactFormRequest extends FormRequest
{
    // ... rules ...
    
    protected function passedValidation(): void
    {
        AntiBot::check($this, 'contact'); // Specific form ID
    }
}
```

---

## ⚙️ Configuration

File: `config/antibot.php`

```php
<?php

return [
    // Basic time validation
    'min_seconds' => 3,        // Minimum time to fill form (seconds)
    'max_seconds' => 7200,     // Maximum form validity (2 hours)
    
    // Honeypot configuration  
    'honeypot_prefix' => '_hp_', // Prefix for honeypot field names
    
    // Signature security
    'include_ip_in_signature' => false, // Bind signature to IP address
    
    // Rate limiting (NEW)
    'max_attempts_per_hour' => 20, // Max submissions per IP per hour (0 = disabled)
    
    // JavaScript detection (NEW)
    'require_javascript' => true,  // Require JavaScript-enabled browsers
    'js_max_age' => 3600,         // Max age of JavaScript timestamp (1 hour)
];
```

---

## 🛡️ Protection Features Detail

### 1. Automatic Unique Form IDs (NEW)
- Zero-config protection for multiple forms
- Each form gets unique ID based on file location and URL
- Prevents form signature reuse across different forms
- **How it works**: `file_path:line_number:url` → `auto_a1b2c3d4`

### 2. Honeypot Fields
- Hidden input fields with dynamic names
- Bots often fill all visible fields
- Legitimate users never see these fields
- **Detection**: Field contains any value = Bot

### 2. Time Validation  
- Tracks form render time vs submission time
- Prevents instant bot submissions
- Allows reasonable human interaction time
- **Detection**: Too fast (< 3s) or too slow (> 2h) = Suspicious

### 4. HMAC Signatures
- Cryptographically signed form data
- Uses Laravel's `APP_KEY` for security
- Prevents form tampering
- **Detection**: Invalid signature = Tampered form

### 5. Rate Limiting 
- IP-based submission tracking
- Configurable attempts per hour (default: 20)
- Uses Laravel Cache for persistence
- **Detection**: Exceeds limit = Blocked for 1 hour

### 6. JavaScript Detection  
- Requires JavaScript to populate hidden fields
- Detects screen resolution, language, timezone
- Validates JavaScript timestamp age
- **Detection**: Missing JS data = Bot or JS disabled

---

## 🚨 Error Handling

The package automatically displays user-friendly error messages:

- **"Neispravan identifikator forme."** - Form ID mismatch
- **"Detektiran bot unos."** - Honeypot triggered  
- **"Prebrzo slanje forme."** - Submitted too quickly
- **"Forma je istekla, pokušaj ponovno."** - Form expired
- **"Neispravan potpis."** - Invalid signature
- **"Preveći broj pokušaja. Pokušajte ponovno za sat vremena."** - Rate limited
- **"JavaScript mora biti omogućen."** - JavaScript required

Errors are styled with inline CSS (no framework dependencies).

---

## 🧪 Testing

### Test Rate Limiting
Submit the form 21 times within an hour from the same IP.

### Test JavaScript Detection  
1. Disable JavaScript in your browser, or
2. Edit form data: change `_ab_js` value from "1" to "0"
3. Submit form - should get JavaScript error

### Test Honeypot
1. Use browser dev tools to make honeypot field visible
2. Fill the honeypot field with any value
3. Submit form - should get "Detektiran bot unos" error

### Debug Form Data
Add this to your controller for debugging:
```php
dd($request->all());
AntiBot::check($request, 'contact');
```

Expected fields:
- `_ab_form`, `_ab_ts`, `_ab_sig` (basic antibot)
- `_ab_js`, `_ab_js_ts`, `_ab_screen` (JavaScript detection)
- `_hp_xxxxxxxxxx` => null (honeypot, should be empty)

---

## 🔧 Advanced Usage

### Automatic vs Manual Form IDs

**🎯 Auto Mode (Recommended):**
```blade
{{-- Each form gets unique auto-generated ID --}}
@include('antibot::fields', antibot_data())
@include('antibot::fields', antibot_data())
@include('antibot::fields', antibot_data())
```

```php
// All controllers use the same simple call
AntiBot::check($request); // Works for all forms automatically!
```

**🛠️ Manual Mode (Full Control):**
```blade
{{-- Specific IDs for each form --}}
@include('antibot::fields', antibot_data('contact'))
@include('antibot::fields', antibot_data('newsletter'))  
@include('antibot::fields', antibot_data('register'))
```

```php
// Controllers must specify exact form IDs
AntiBot::check($request, 'contact');
AntiBot::check($request, 'newsletter');
AntiBot::check($request, 'register');
```

### Disable Specific Features
```php
// In config/antibot.php

'max_attempts_per_hour' => 0,    // Disable rate limiting
'require_javascript' => false,   // Disable JavaScript requirement
```

### Helper Functions
```php
// Main validation methods
AntiBot::check($request);           // Auto mode
AntiBot::check($request, 'contact'); // Manual mode

// Alternative validation helper  
antibot_verify($request);           // Auto mode
antibot_verify($request, 'contact'); // Manual mode

// Get antibot data for forms
$data = antibot_data();           // Auto-generated unique ID
$data = antibot_data('contact');  // Specific form ID
```

---

## ❓ Troubleshooting

### "Unable to locate a class or view for component"
- **Old versions used Blade components (no longer supported)**
- **Solution**: Use only `@include('antibot::fields', antibot_data('form_id'))`

### "Undefined variable $ts"  
- **Cause**: Using `@include` without `antibot_data()` helper
- **Solution**: Always use `@include('antibot::fields', antibot_data('form_id'))`

### JavaScript Detection Failing
- Check if JavaScript is enabled in browser
- Verify form fields aren't cached/prefilled incorrectly
- Check browser dev tools for JavaScript errors

### Rate Limiting Too Strict
- Increase `max_attempts_per_hour` in config
- Consider if users share IP addresses (offices, public WiFi)
- Set to `0` to disable rate limiting

### False Positive Detections
- Users with JavaScript disabled: Set `require_javascript => false`
- Slow internet/users: Increase `max_seconds` 
- Shared IPs: Increase `max_attempts_per_hour` or disable

---

## 🔗 Helper Functions

### Available Functions
```php
// Main validation (throws ValidationException)
AntiBot::check(Request $request, ?string $formId = null);

// Alternative helper  
antibot_verify(Request $request, ?string $formId = null);

// Get form data for @include
antibot_data(?string $formId = null): array;

// Examples:
AntiBot::check($request);           // Auto mode
AntiBot::check($request, 'contact'); // Manual mode
antibot_data();                     // Auto-generated ID: auto_a1b2c3d4
antibot_data('contact');            // Manual ID: contact
```

---

## 📝 How It Works

1. **Form Rendering**: `@include('antibot::fields', antibot_data())` generates:
   - **Unique form ID** (auto: `auto_a1b2c3d4` or manual: `contact`)
   - Hidden antibot fields (`_ab_form`, `_ab_ts`, `_ab_sig`)
   - JavaScript detection fields (`_ab_js`, `_ab_js_ts`, `_ab_screen`)
   - Honeypot field with random name (`_hp_xxxxxxxxxx`)
   - JavaScript code to populate detection fields

2. **Form Submission**: User submits form with all antibot data

3. **Server Validation**: `AntiBot::check($request)` validates:
   - Rate limiting (IP-based attempt counting)
   - Form ID matches expected (auto mode: any valid ID, manual: specific ID)
   - Honeypot fields are empty
   - Time constraints (min/max seconds)
   - JavaScript fields are properly filled
   - HMAC signature is valid

4. **Error Handling**: ValidationException thrown on failure, Laravel redirects back with errors

**Auto Mode ID Generation:**
- Uses `debug_backtrace()` to get file path and line number
- Combines with current URL path
- Creates hash: `auto_` + first 8 chars of SHA256
- Example: `/views/contact.blade.php:15:/contact` → `auto_a1b2c3d4`

---

## 📄 License

MIT © fer-projekt

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

**🛡️ Secure your Laravel forms with confidence!**