<?php

namespace App\Support;

/**
 * ERP email color tokens — aligned with public/assets/css/style.css
 * and resources/views/emails/layouts/app.blade.php.
 */
class EmailBrandTheme
{
    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            'primary'        => '#014e9c',
            'text_primary'   => '#262A2A',
            'text_muted'     => '#6F6F6F',
            'border'         => '#E8E8E8',
            'section_bg'     => '#F6F6F6',
            'panel_bg'       => '#F9F9FC',
            'card_bg'        => '#ffffff',
            'email_cream'    => '#fffce3',
            'link_accent'    => '#ddbe8d',
            'callout_bg'     => '#FFF7D8',
            'callout_border' => '#FDA700',
            'emphasize_bg'   => '#F9F9FC',
            'emphasize_text' => '#014e9c',
        ];
    }

    public static function badgeStyles(string $status): string
    {
        $palette = match ($status) {
            'Paid' => [
                'bg'     => '#D3FFD3',
                'text'   => '#5CB85C',
                'border' => '#5CB85C',
            ],
            'Partial Payment' => [
                'bg'     => '#FFEECD',
                'text'   => '#FDA700',
                'border' => '#FDA700',
            ],
            default => [
                'bg'     => '#FFEEEC',
                'text'   => '#014e9c',
                'border' => '#014e9c',
            ],
        };

        return sprintf(
            'background-color:%s;color:%s;border:1px solid %s;',
            $palette['bg'],
            $palette['text'],
            $palette['border']
        );
    }
}
