<?php

namespace Spatie\UptimeMonitor\Commands;

use Spatie\UptimeMonitor\Models\Monitor;
use Spatie\Url\Url;
use Illuminate\Support\Facades\Auth;

class CreateMonitor extends BaseCommand
{
    protected $signature = 'monitor:create {url} {user_id}';

    protected $description = 'Create a monitor';

    public function handle()
    {
        if(Auth::check() && $this->argument('user_id')) {
            Auth::logout();
        }
        Auth::loginUsingId($this->argument('user_id'));

        $url = Url::fromString($this->argument('url'));

        if (! in_array($url->getScheme(), ['http', 'https'])) {
            if ($scheme = $this->choice("Which protocol needs to be used for checking `{$url}`?", [1 => 'https', 2 => 'http'], 1)) {
                $url = $url->withScheme($scheme);
            }
        }

        if ($this->confirm('Should we look for a specific string on the response?')) {
            $lookForString = $this->ask('Which string?');
        }

        $monitor = Monitor::create([
            'user_id' => Auth::check() ? Auth::id() : Null,
            'url' => trim($url, '/'),
            'look_for_string' => $lookForString ?? '',
            'uptime_check_method' => isset($lookForString) ? 'get' : 'head',
            'certificate_check_enabled' => $url->getScheme() === 'https',
            'uptime_check_interval_in_minutes' => config('uptime-monitor.uptime_check.run_interval_in_minutes'),
        ]);

        $this->warn("{$monitor->url} will be monitored!");
    }
}
