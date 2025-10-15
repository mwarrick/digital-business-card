<?php
/**
 * Card Theme Definitions
 * Visual themes for business cards with color schemes and typography
 */

function getThemes() {
    return [
        'professional-blue' => [
            'name' => 'Professional Blue',
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2',
            'accent_color' => '#667eea',
            'text_color' => '#333333',
            'text_light' => '#666666',
            'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ],
        'minimalist-gray' => [
            'name' => 'Minimalist Gray',
            'primary_color' => '#2d3748',
            'secondary_color' => '#4a5568',
            'accent_color' => '#2d3748',
            'text_color' => '#1a202c',
            'text_light' => '#718096',
            'font_family' => 'Georgia, "Times New Roman", serif'
        ],
        'creative-sunset' => [
            'name' => 'Creative Sunset',
            'primary_color' => '#f093fb',
            'secondary_color' => '#f5576c',
            'accent_color' => '#f5576c',
            'text_color' => '#2d3748',
            'text_light' => '#4a5568',
            'font_family' => '"Helvetica Neue", Arial, sans-serif'
        ],
        'corporate-green' => [
            'name' => 'Corporate Green',
            'primary_color' => '#11998e',
            'secondary_color' => '#38ef7d',
            'accent_color' => '#11998e',
            'text_color' => '#2d3748',
            'text_light' => '#4a5568',
            'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ],
        'tech-purple' => [
            'name' => 'Tech Purple',
            'primary_color' => '#4776e6',
            'secondary_color' => '#8e54e9',
            'accent_color' => '#4776e6',
            'text_color' => '#1a202c',
            'text_light' => '#4a5568',
            'font_family' => '"SF Pro Display", -apple-system, sans-serif'
        ]
    ];
}

function getTheme($themeName) {
    $themes = getThemes();
    return $themes[$themeName] ?? $themes['professional-blue'];
}

function generateThemeCSS($themeName) {
    $theme = getTheme($themeName);
    
    return "
        :root {
            --primary-color: {$theme['primary_color']};
            --secondary-color: {$theme['secondary_color']};
            --accent-color: {$theme['accent_color']};
            --text-color: {$theme['text_color']};
            --text-light: {$theme['text_light']};
            --font-family: {$theme['font_family']};
            --gradient: linear-gradient(135deg, {$theme['primary_color']} 0%, {$theme['secondary_color']} 100%);
        }
    ";
}

