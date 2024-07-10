<?php

declare(strict_types=1);

/**
 * These functions are to handle third-party WordPress code not covered by available extensions.
 *
 * See README.md for more information about the extensions.
 */

/**
 * @param array<string, string> $atts
 */
function gdlr_core_esc_style(array $atts, bool $wrap = true): string
{
    return '';
}

function infinite_get_option(string $option, ?string $key = null, ?string $default = null): string
{
    return '';
}

function infinite_get_post_option(int|false $post_id, string $key = 'gdlr-core-page-option'): mixed
{
    return '';
}

function infinite_is_top_search(): bool
{
    return (bool) mt_rand(0, 1);
}
