<?php

namespace FerProjekt\AntiBot\View\Components;

use Illuminate\View\Component;

class Fields extends Component
{
    public string $form;
    public int $ts;
    public string $sig;
    public string $honeyName;

    public function __construct(string $form = 'default')
    {
        $this->form = $form;
        $this->ts = time();

        $prefix = (string) config('antibot.honeypot_prefix', '_hp_');
        $this->honeyName = $prefix . substr(
            hash('sha1', $this->form . session()->getId() . random_int(1, PHP_INT_MAX)),
            0,
            10
        );

        $data = session()->getId() . '|' . $this->form . '|' . $this->ts;

        if (config('antibot.include_ip_in_signature', false)) {
            $data .= '|' . request()->ip();
        }

        $key = (string) config('app.key');
        if (strpos($key, 'base64:') === 0) {
            $key = (string) base64_decode(substr($key, 7));
        }

        $this->sig = hash_hmac('sha256', $data, $key);
    }

    public function render()
    {
        return view('antibot::fields');
    }
}
