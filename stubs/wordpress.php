<?php

declare(strict_types=1);

/**
 * First install phpstan-wordpress. These functions are to handle other third-party WordPress code.
 *
 * @see https://github.com/szepeviktor/phpstan-wordpress
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

function wc_get_product_id_by_sku(string $sku): ?int
{
    $value = mt_rand(0, 10);
    return $value < 5 ? $value : null;
}

class WC_Product_Simple
{
    private readonly string $name;

    private readonly string $slug;

    public function __construct(
        private readonly int $id
    ) {
        $this->name = '';
        $this->slug = '';
    }

    public function get_attribute(string $attribute): string
    {
        return '';
    }

    public function get_id(): int
    {
        return $this->id;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_sku(): string
    {
        return '';
    }

    public function get_slug(): string
    {
        return $this->slug;
    }
}
