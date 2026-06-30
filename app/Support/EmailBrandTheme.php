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
            'border'         => '#dae0e2',
            'section_bg'     => '#F6F6F6',
            'panel_bg'       => '#F9F9FC',
            'card_bg'        => '#ffffff',
            'table_bg'        => '#f2f9fc',
            'email_cream'    => '#fffce3',
            'link_accent'    => '#ddbe8d',
            'callout_bg'     => '#FFF7D8',
            'callout_border' => '#FDA700',
            'emphasize_bg'   => '#FDEAEA',
            'emphasize_text' => '#D64545',
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
                'bg'     => '#FDF3E0',
                'text'   => '#B7791F',
                'border' => '#B7791F',
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
