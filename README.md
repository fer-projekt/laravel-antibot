# Laravel AntiBot (fer-projekt)

Honeypot + minimal time + HMAC potpis za Laravel forme — **zero‑config** i plug‑and‑play.  
Radi na **Laravel 7–11** i **PHP 7.4+**.

> ✅ Brza integracija: `<x-antibot::fields form="contact" />` u formu + `AntiBot::check($request, 'contact')` u kontroler **ili** route middleware `->middleware('antibot:contact')`.

---

## Sadržaj
- [Značajke](#značajke)
- [Zahtjevi](#zahtjevi)
- [Instalacija](#instalacija)
- [Brzi start](#brzi-start)
- [Upotreba](#upotreba)
  - [Blade komponenta](#blade-komponenta)
  - [Kontroler (1 linija)](#kontroler-1-linija)
  - [Route middleware (bez koda u kontroleru)](#route-middleware-bez-koda-u-kontroleru)
  - [Helper funkcija](#helper-funkcija)
  - [Primjer s FormRequest-om](#primjer-s-formrequest-om)
- [Rate limiting (preporuka)](#rate-limiting-preporuka)
- [Konfiguracija](#konfiguracija)
- [Override pogleda (views)](#override-pogleda-views)
- [Kako radi](#kako-radi)
- [Savjeti i napomene](#savjeti-i-napomene)
- [Rješavanje problema](#rješavanje-problema)
- [Licenca](#licenca)

---

## Značajke

- 🪤 **Honeypot** polje s dinamičnim imenom (teže gađanje botovima).
- ⏱️ **Minimalno vrijeme ispunjavanja** (npr. ≥ 3s).
- 🔐 **HMAC potpis** (`session_id + form_id + timestamp`), opcionalno veže i **IP**.
- 🛡️ Radi uz **CSRF** middleware (Laravel default).
- 🚦 Jednostavno dodaj **rate limit** za dodatni sloj zaštite.
- 🧩 **Komponenta + middleware + helper** — koristiš što ti paše.

---

## Zahtjevi

- PHP **7.4+**
- Laravel **7.x – 11.x**

---

## Instalacija

```bash
composer require fer-projekt/laravel-antibot
php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=config
# (opcionalno) publish views ako želiš override:
# php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=views
```

---

## Brzi start

1) U **Blade** formu ubaci polja:

```blade
<x-antibot::fields form="contact" />
```

2) U **kontroleru** provjeri anti‑bot u jednoj liniji **ili** koristi **middleware**:

```php
use FerProjekt\AntiBot\AntiBot;

AntiBot::check($request, 'contact');
// ...ostatak validacije / spremanja
```

**Alternativa:**

```php
Route::post('/contact', [ContactController::class, 'store'])
     ->middleware('antibot:contact');
```

---

## Upotreba

### Blade komponenta

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    <x-antibot::fields form="contact" />

    <!-- tvoja polja -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>

    <button type="submit">Pošalji</button>
</form>
```

### Kontroler (1 linija)

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
        return back()->with('status', 'Poruka poslana!');
    }
}
```

### Route middleware (bez koda u kontroleru)

```php
use App\Http\Controllers\ContactController;

Route::post('/contact', [ContactController::class, 'store'])
     ->middleware('antibot:contact');
```

### Helper funkcija

Paket sadrži helper `antibot_verify()`:

```php
antibot_verify($request, 'contact'); // isto kao AntiBot::check($request, 'contact')
```

### Primjer s FormRequest-om

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

## Rate limiting (preporuka)

### Laravel 7 — klasični throttle
```php
Route::post('/contact', [ContactController::class, 'store'])
     ->middleware(['antibot:contact','throttle:10,1']); // 10 req/min po IP-u
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

// ruta
Route::post('/contact', [ContactController::class, 'store'])
     ->middleware(['antibot:contact','throttle:form-contact']);
```

---

## Konfiguracija

Datoteka: `config/antibot.php`

```php
return [
    'min_seconds' => 3,        // minimalno vrijeme ispunjavanja
    'max_seconds' => 7200,     // maksimalna valjanost potpisa
    'honeypot_prefix' => '_hp_',

    // Opcionalno u potpis uključi IP (strože; pazi na proxy/load balancer setup):
    'include_ip_in_signature' => false,
];
```

> 🔑 **Napomena za APP_KEY**: Laravel APP_KEY u `.env` često počinje s `base64:` — paket to već obrađuje.

---

## Override pogleda (views)

Ako želiš prilagoditi markup/sk hiding honeypot polja:

```bash
php artisan vendor:publish --provider="FerProjekt\AntiBot\AntiBotServiceProvider" --tag=views
```

Zatim mijenjaj `resources/views/vendor/antibot/fields.blade.php`.

---

## Kako radi

1. **Komponenta** generira:
   - `_ab_form` (ID forme koju očekuješ na backendu)
   - `_ab_ts` (timestamp rendera forme)
   - `_ab_sig` (HMAC potpis podataka; ključ je `APP_KEY`)
   - **honeypot** polje s **dinamičnim imenom** (prefiks iz konfiguracije)

2. **Provjera** (`AntiBot::check` ili `middleware`):
   - Form ID mora odgovarati
   - Honeypot mora biti **prazan**
   - Prošlo je barem `min_seconds`, a manje od `max_seconds`
   - HMAC potpis je ispravan (opcija: veže i na IP)

3. **CSRF** štiti zasebno (Laravel `VerifyCsrfToken` middleware).

---

## Savjeti i napomene

- Ne koristi `display:none` za honeypot; bolje ga pomaknuti izvan ekrana (default u view-u).
- Dimenzije/pozicija honeypota su minimalne da ne ometaju UX niti screenreadere.
- **Logiraj promašaje** (ValidationException) po želji za praćenje patterna botova.
- **Dodaj reCAPTCHA/hCaptcha/Turnstile** samo za sumnjive slučajeve ako treba — često neće trebati.
- Za **SPAs/AJAX**: komponenta se mora renderirati pri svakom prikazu forme (timestamp/potpis su per‑render).

---

## Rješavanje problema

- **`Neispravan potpis`**: provjeri `APP_KEY` i session (isti user/session mora poslati formu).
- **`Prebrzo slanje forme`**: korisnik je submit-ao brže od `min_seconds`. Povećaj ili prilagodi UI.
- **`Forma je istekla`**: proteklo više od `max_seconds`. Re-renderiraj formu ili povećaj limit.
- **Reverse proxy/CDN**: ako uključiš `include_ip_in_signature`, pobrini se da `Request::ip()` vraća stvarni IP.
- **Session driver**: treba biti omogućen (standardni Laravel session middleware).

---

## Licenca

MIT © fer-projekt
