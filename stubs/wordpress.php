<?php

declare(strict_types=1);

function add_action(
    string $tag,
    callable $callback,
    int $priority = 10,
    int $accepted_args = 1
): void {}

function add_filter(
    string $tag,
    callable $callback,
    int $priority = 10,
    int $accepted_args = 1
): void {}

function add_shortcode(string $tag, callable $callback): void {}

function apply_filters(string $hook_name, mixed $value, mixed ...$args): mixed
{
    return '';
}

function bloginfo(string $show): void {}

/**
 * @param list<string>|string $class
 */
function body_class($class = ''): void {}

function do_action(string $tag, mixed ...$args): void {}

function esc_attr(string $text): string
{
    return '';
}

function esc_attr__(string $text, string $domain = 'default'): string
{
    return '';
}

function esc_html__(string $text, string $domain = 'default'): string
{
    return '';
}

/**
 * @param list<string> $protocols
 */
function esc_url(string $url, array $protocols = null, string $_context = 'display'): string
{
    return '';
}

function gdlr_core_esc_style(string $style): void {}

/**
 * @param array<string, string> $default_headers
 * @return array<string, string>
 */
function get_file_data(string $file, array $default_headers, string $context = ''): array
{
    return [];
}

function get_footer(string $name = ''): void {}

function get_header(string $name = ''): void {}

function get_permalink(WP_Post|int|null $post = null): string
{
    return '';
}

/**
 * @return array<string, mixed>|string
 */
function get_post_meta(int $post_id, string $key = '', bool $single = false): array|string
{
    return $single ? '' : [];
}

function get_search_query(bool $escaped = true): string
{
    return '';
}

function get_template_part(string $slug, string $name = null): void {}

function get_the_ID(): void {}

function get_theme_file_uri(string $file = ''): string
{
    return '';
}

function get_the_modified_author(): string
{
    return '';
}

function get_the_title(WP_Post|int|null $post = null): string
{
    return '';
}

function home_url(string $path = '', ?string $scheme = null): string
{
    return '';
}

function infinite_get_option(string $name, mixed $default = null): void {}

function infinite_get_post_option(string $name, mixed $default = null, int $post_id = null): void {}

function infinite_is_top_search(): bool
{
    return (bool) mt_rand(0, 1);
}

function language_attributes(string $doctype = 'html'): void {}

function plugin_dir_path(string $file): string
{
    return '';
}

function plugin_dir_url(string $file): string
{
    return '';
}

function plugins_url(string $path = '', string $plugin = ''): string
{
    return '';
}

function register_deactivation_hook(string $file, callable $callback): void {}

function register_taxonomy_for_object_type(string $taxonomy, string $object_type): bool
{
    return true;
}

/**
 * @param array<string, ?string> $pairs
 * @param array<string, ?string> $atts
 * @return array<string, ?string>
 */
function shortcode_atts(array $pairs, array $atts, ?string $shortcode = null): array
{
    return [];
}

function wc_get_product_id_by_sku(string $sku): ?int
{
    $value = mt_rand(0, 10);
    return $value < 5 ? $value : null;
}

function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool
{
    return true;
}

/**
 * @param array<string> $deps
 */
function wp_enqueue_script(
    string $handle,
    string $src = '',
    array $deps = [],
    string $ver = '',
    bool $in_footer = false
): void {}

/**
 * @param array<string> $deps
 */
function wp_enqueue_style(
    string $handle,
    string $src = '',
    array $deps = [],
    string $ver = '',
    string $media = 'all'
): void {}

function wp_head(): void {}

function wp_is_post_revision(WP_Post|int|null $post = null): int|false
{
    $value = mt_rand(0, 10);
    return $value < 5 ? $value : false;
}

/**
 * @param array<string>|string $to
 * @param array<string>|string $headers
 * @param array<string> $attachments
 */
function wp_mail(
    string|array $to,
    string $subject,
    string $message,
    string|array $headers = '',
    array $attachments = []
): bool {
    return true;
}

/**
 * @param array<string> $deps
 */
function wp_register_style(
    string $handle,
    string $src,
    array $deps = [],
    string $ver = '',
    string $media = 'all'
): void {}

function wp_remote_fopen(string $url): string|false
{
    $value = mt_rand(0, 10);
    return $value < 5 ? (string) $value : false;
}

/**
 * @param array<string, mixed> $args
 * @return WP_Error|array<string, mixed>
 */
function wp_remote_get(string $url, array $args = []): WP_Error|array
{
    $value = mt_rand(0, 10);
    return $value < 5 ? new WP_Error() : [];
}

class WP_Error {}

class WP_Post {}

class WC_Product_Simple
{
    private readonly string $name;

    private readonly string $slug;

    public function __construct()
    {
        $this->name = '';
        $this->slug = '';
    }

    public function get_attribute(string $attribute): string
    {
        return '';
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
