@props(['url'])
@inject('settings', 'App\Services\SettingService')
@php
    $logo = $settings->get('logo_dark') ?? $settings->get('logo_light');
    $appName = $settings->get('app_name', config('app.name'));
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none; color: #ffffff; font-weight: bold; font-size: 20px;">
@if ($logo)
<img src="{{ asset('storage/' . $logo) }}" class="logo" alt="{{ $appName }} Logo" style="max-height: 50px; width: auto; margin-bottom: 10px;">
<br>
@endif
{{ $appName }}
</a>
</td>
</tr>
