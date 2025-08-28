@csrf
<input type="hidden" name="_ab_form" value="{{ $form }}">
<input type="hidden" name="_ab_ts" value="{{ $ts }}">
<input type="hidden" name="_ab_sig" value="{{ $sig }}">

{{-- Honeypot (mora ostati prazno) --}}
<input type="text"
       name="{{ $honeyName }}"
       value=""
       tabindex="-1"
       autocomplete="off"
       aria-hidden="true"
       style="position:absolute; left:-9999px; top:-9999px; height:1px; width:1px; opacity:0;">
