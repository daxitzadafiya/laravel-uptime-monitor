<?php

namespace Spatie\UptimeMonitor\Commands;

use Spatie\UptimeMonitor\Models\Monitor;
use Spatie\Url\Url;
use Illuminate\Support\Facades\Session;

class CreateMonitor extends BaseCommand
{
    protected $signature = 'monitor:create {url} {look_for_string} {user_id}';

    protected $description = 'Create a monitor';

    public function handle()
    {
        Session::put('user_id', $this->argument('user_id'));

        $url = Url::fromString($this->argument('url'));

        $lookForString = $this->argument('look_for_string');

        if (! in_array($url->getScheme(), ['http', 'https'])) {
            if ($scheme = $this->choice("Which protocol needs to be used for checking `{$url}`?", [1 => 'https', 2 => 'http'], 1)) {
                $url = $url->withScheme($scheme);
            }
        }

        $monitor = Monitor::create([
            'user_id' => Session::has('user_id') ? Session::get('user_id') : Null,
            'url' => trim($url, '/'),
            'look_for_string' => $lookForString ?? '',
            'uptime_check_method' => isset($lookForString) ? 'get' : 'head',
            'certificate_check_enabled' => $url->getScheme() === 'https',
            'uptime_check_interval_in_minutes' => config('uptime-monitor.uptime_check.run_interval_in_minutes'),
        ]);

        $this->warn("{$monitor->url} will be monitored!");
    }
}
