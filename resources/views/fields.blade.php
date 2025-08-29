{{-- AntiBot error messages --}}
@if ($errors->has('form') || $errors->has('bot') || $errors->has('speed') || $errors->has('expired') || $errors->has('signature') || $errors->has('rate_limit') || $errors->has('javascript'))
    <div style="background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 16px; border: 1px solid #f5c6cb; border-radius: 4px; font-size: 14px;">
        @error('form')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
        @error('bot')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
        @error('speed')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
        @error('expired')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
        @error('signature')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
        @error('rate_limit')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
        @error('javascript')<div style="margin-bottom: 4px;">{{ $message }}</div>@enderror
    </div>
@endif

@csrf
<input type="hidden" name="_ab_form" value="{{ $form }}">
<input type="hidden" name="_ab_ts" value="{{ $ts }}">
<input type="hidden" name="_ab_sig" value="{{ $sig }}">

{{-- JavaScript detection fields (populated by script below) --}}
<input type="hidden" name="_ab_js" value="0">
<input type="hidden" name="_ab_js_ts" value="0">
<input type="hidden" name="_ab_screen" value="">

{{-- Honeypot (mora ostati prazno) --}}
<input type="text"
       name="{{ $honeyName }}"
       value=""
       tabindex="-1"
       autocomplete="off"
       aria-hidden="true"
       style="position:absolute; left:-9999px; top:-9999px; height:1px; width:1px; opacity:0;">

{{-- JavaScript detection script --}}
<script>
(function() {
    // Mark JavaScript as enabled
    document.querySelector('[name="_ab_js"]').value = "1";
    
    // Set JavaScript timestamp
    document.querySelector('[name="_ab_js_ts"]').value = Date.now();
    
    // Set screen and browser info
    var screen_info = screen.width + 'x' + screen.height + '|' + 
                     navigator.language + '|' + 
                     new Date().getTimezoneOffset();
    document.querySelector('[name="_ab_screen"]').value = screen_info;
})();
</script>
