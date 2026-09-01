<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LandingPageSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public const ASSET_KEYS = ['nav_logo', 'hero_image', 'product_image', 'feature_image', 'about_image', 'footer_logo'];

    public const DEFAULTS = [
        'site_title' => 'Edlink — Run your school from one dashboard',
        'announcement' => 'Free 10-day demo — no card required',
        'hero_title' => 'Run your school from',
        'hero_highlight' => 'one dashboard,',
        'hero_title_suffix' => 'not ten notebooks',
        'hero_description' => 'Edlink brings admissions, attendance, fees, and report cards into a single system your whole staff can actually use.',
        'primary_cta' => 'Try a free demo', 'secondary_cta' => 'See features',
        'stat_one_value' => '15+', 'stat_one_label' => 'Connected modules', 'stat_two_value' => '6', 'stat_two_label' => 'Role-based portals', 'stat_three_value' => '10 days', 'stat_three_label' => 'Free demo',
        'trust_text' => 'Built for schools that want less paperwork, not more software',
        'features_heading' => 'Everything the front office and the staffroom need',
        'about_heading' => "Built by a team that's shipped school software before",
        'about_text' => 'Edlink is built by Spotnet Technologies, a software team serving educational institutions. We started Edlink after seeing how many schools were still running admissions, attendance, and fees from notebooks and spreadsheets.',
        'about_text_two' => 'Our goal is simple: give every school one system its whole staff can actually use, on a phone or desktop.',
        'pricing_heading' => 'Simple plans, priced per school',
        'contact_heading' => 'Need help, or want a school demo?',
        'contact_description' => 'Talk to our team, report an issue, or reach us directly.',
        'phone' => '+256 763 316 839', 'whatsapp' => '256763316839', 'support_email' => 'support@edlink.space',
        'facebook_url' => '', 'instagram_url' => '', 'x_url' => '', 'linkedin_url' => '', 'youtube_url' => '', 'tiktok_url' => '',
        'footer_text' => 'Edlink. Built by Spotnet Technologies.',
        'nav_logo' => 'img/logoneg.png', 'hero_image' => 'img/girl.png', 'product_image' => 'img/gradebook.png', 'feature_image' => 'img/gradebook.png', 'about_image' => 'img/teacher-explaining.png', 'footer_logo' => 'img/logo.png',
    ];

    public static function values(): array
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return self::DEFAULTS;
        }

        return array_replace(self::DEFAULTS, static::query()->pluck('value', 'key')->all());
    }

    public static function assetUrl(array $settings, string $key): string
    {
        $path = $settings[$key] ?? self::DEFAULTS[$key] ?? '';

        return str_starts_with($path, 'landing-page/') ? url('/media/landing/'.$key).'?v='.substr(sha1($path), 0, 12) : asset($path);
    }
}
