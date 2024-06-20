<?php

declare(strict_types=1);

/**
 * Mimics the WordPress add_action function.
 *
 * @param string $tag the name of the action to hook the $callback to
 * @param callable $callback the callback to be run when the action is triggered
 * @param int $priority Optional. Used to specify the order in which the functions associated with a
 * particular action are executed. Default 10.
 * @param int $accepted_args Optional. The number of arguments the function accepts. Default 1.
 */
function add_action(
    string $tag,
    callable $callback,
    int $priority = 10,
    int $accepted_args = 1
): void {
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress add_filter function.
 *
 * @param string $tag the name of the filter to hook the $callback to
 * @param callable $callback the callback to be run when the filter is applied
 * @param int $priority Optional. Used to specify the order in which the functions associated with a
 * particular action are executed. Default 10.
 * @param int $accepted_args Optional. The number of arguments the function accepts. Default 1.
 */
function add_filter(
    string $tag,
    callable $callback,
    int $priority = 10,
    int $accepted_args = 1
): void {
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress add_shortcode function.
 *
 * @param string $tag the shortcode tag to be searched in post content
 * @param callable $callback the callback function to run when the shortcode is found
 */
function add_shortcode(string $tag, callable $callback): void
{
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress get_file_data function.
 *
 * @param string $file path to the file
 * @param array<string, string> $default_headers list of headers, in the format array('HeaderKey' =>
 * 'Header Name')
 * @param string $context Optional. If specified adds filter hook 'extra_$context_headers'.
 * @return array<string, string> array of file header values keyed by header name
 */
function get_file_data(string $file, array $default_headers, string $context = ''): array
{
    // Since this is an empty mimic function, return an empty array or any other logic needed.
    return [];
}

/**
 * Mimics the WordPress get_permalink function.
 *
 * @param WP_Post|int|null $post Optional. Post ID or post object. Default is global $post.
 * @return string the permalink URL
 */
function get_permalink($post = null): string
{
    // Since this is an empty mimic function, return an empty string or any other logic needed.
    return '';
}

/**
 * Mimics the WordPress get_post_meta function.
 *
 * @param int $post_id post ID
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Optional. Whether to return a single value. Default false.
 * @return array<string, mixed>|string Will be an array if $single is false. Will be value of meta
 * data field if $single is true.
 */
function get_post_meta(int $post_id, string $key = '', bool $single = false): array|string
{
    // Since this is an empty mimic function, return an empty array or any other logic needed.
    return $single ? '' : [];
}

/**
 * Mimics the WordPress get_the_modified_author function.
 *
 * @return string the name of the author who last modified the current post
 */
function get_the_modified_author(): string
{
    // Since this is an empty mimic function, return an empty string or any other logic needed.
    return '';
}

/**
 * Mimics the WordPress get_the_title function.
 *
 * @param WP_Post|int|null $post Optional. Post ID or post object. Default is global $post.
 * @return string the post title
 */
function get_the_title($post = null): string
{
    // Since this is an empty mimic function, return an empty string or any other logic needed.
    return '';
}

/**
 * Mimics the WordPress plugin_dir_path function.
 *
 * @param string $file the file path
 * @return string the directory path
 */
function plugin_dir_path(string $file): string
{
    // Since this is an empty mimic function, return an empty string or any other logic needed.
    return '';
}

/**
 * Mimics the WordPress plugin_dir_url function.
 *
 * @param string $file the file path
 * @return string the URL to the directory
 */
function plugin_dir_url(string $file): string
{
    // Since this is an empty mimic function, return an empty string or any other logic needed.
    return '';
}

/**
 * Mimics the WordPress plugins_url function.
 *
 * @param string $path Optional. Path relative to the plugins URL. Default empty.
 * @param string $plugin Optional. The plugin file path to be relative to. Default empty.
 * @return string the URL to the plugins directory or file
 */
function plugins_url(string $path = '', string $plugin = ''): string
{
    // Since this is an empty mimic function, return an empty string or any other logic needed.
    return '';
}

/**
 * Mimics the WordPress register_deactivation_hook function.
 *
 * @param string $file the file that contains the deactivation hook
 * @param callable $callback the function to be called when the plugin is deactivated
 */
function register_deactivation_hook(string $file, callable $callback): void
{
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress register_taxonomy_for_object_type function.
 *
 * @param string $taxonomy the taxonomy name
 * @param string $object_type the object type
 *
 * @return bool true on success, false on failure
 */
function register_taxonomy_for_object_type(string $taxonomy, string $object_type): bool
{
    // Since this is an empty mimic function, return true or any other logic needed.
    return true;
}

/**
 * Mimics the WordPress shortcode_atts function.
 *
 * @param array<string, ?string> $pairs Default values for the shortcode attributes
 * @param array<string, ?string> $atts User defined attributes in the shortcode tag
 * @param string $shortcode Optional. The name of the shortcode. Default null.
 * @return array<string, ?string> Combined and filtered attribute list
 */
function shortcode_atts(array $pairs, array $atts, ?string $shortcode = null): array
{
    // Since this is an empty mimic function, return an empty array or any other logic needed.
    return [];
}

/**
 * Mimics the WooCommerce wc_get_product_id_by_sku function.
 *
 * @param string $sku the SKU for which to find the product ID
 * @return int|null the ID of the product or null if no product is found
 */
function wc_get_product_id_by_sku(string $sku): ?int
{
    // Since this is an empty mimic function, return null or any other logic needed.
    $value = mt_rand(0, 10);
    return $value < 5 ? $value : null;
}

/**
 * Mimics the WordPress wp_add_inline_script function.
 *
 * @param string $handle name of the script to add the inline script to
 * @param string $data string containing the JavaScript to be added
 * @param string $position Optional. Whether to add the inline script before the handle or after.
 * Default 'after'.
 * @return bool true on success, false on failure
 */
function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool
{
    // Since this is an empty mimic function, return true or any other logic needed.
    return true;
}

/**
 * Mimics the WordPress wp_enqueue_script function.
 *
 * @param string $handle name of the script
 * @param string $src Optional. Full URL of the script, or path of the script relative to the
 * WordPress root directory. Default empty string.
 * @param array<string> $deps Optional. An array of registered script handles this script depends
 * on.  Default empty array.
 * @param string $ver Optional. String specifying the script version number. Default empty string.
 * @param bool $in_footer Optional. Whether to enqueue the script before </body> instead of in the
 * <head>. Default false.
 */
function wp_enqueue_script(
    string $handle,
    string $src = '',
    array $deps = [],
    string $ver = '',
    bool $in_footer = false
): void {
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress wp_enqueue_style function.
 *
 * @param string $handle name of the stylesheet
 * @param string $src Optional. Full URL of the stylesheet, or path of the stylesheet relative to
 * the WordPress root directory. Default empty string.
 * @param array<string> $deps Optional. An array of registered stylesheet handles this stylesheet
 * depends on. Default empty array.
 * @param string $ver Optional. String specifying the stylesheet version number. Default empty
 * string.
 * @param string $media Optional. The media for which this stylesheet has been defined. Default
 * 'all'.
 */
function wp_enqueue_style(
    string $handle,
    string $src = '',
    array $deps = [],
    string $ver = '',
    string $media = 'all'
): void {
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress wp_is_post_revision function.
 *
 * @param WP_Post|int|null $post Optional. Post ID or post object. Default is global $post.
 * @return int|false the ID of the revision's parent on success, false if not a revision
 */
function wp_is_post_revision($post = null): int|false
{
    // Since this is an empty mimic function, return false or any other logic needed.
    $value = mt_rand(0, 10);
    return $value < 5 ? $value : false;
}

/**
 * Mimics the WordPress wp_mail function.
 *
 * @param array<string>|string $to array or comma-separated list of email addresses to send message
 * @param string $subject email subject
 * @param string $message message contents
 * @param array<string>|string $headers Optional. Additional headers.
 * @param array<string> $attachments Optional. Files to attach.
 * @return bool whether the email contents were sent successfully
 */
function wp_mail(
    string|array $to,
    string $subject,
    string $message,
    string|array $headers = '',
    array $attachments = []
): bool {
    // Since this is an empty mimic function, return true or any other logic needed.
    return true;
}

/**
 * Mimics the WordPress wp_register_style function.
 *
 * @param string $handle name of the stylesheet
 * @param string $src full URL of the stylesheet, or path of the stylesheet relative to the
 * WordPress root directory
 * @param array<string> $deps Optional. An array of registered stylesheet handles this stylesheet
 * depends on. Default empty array.
 * @param string $ver Optional. String specifying the stylesheet version number. Default empty
 * string.
 * @param string $media Optional. The media for which this stylesheet has been defined. Default
 * 'all'.
 */
function wp_register_style(
    string $handle,
    string $src,
    array $deps = [],
    string $ver = '',
    string $media = 'all'
): void {
    // Since this is an empty mimic function, no logic is needed.
}

/**
 * Mimics the WordPress wp_remote_fopen function.
 *
 * @param string $url the URL to fetch
 * @return string|false the content of the URL or false on failure
 */
function wp_remote_fopen(string $url): string|false
{
    // Since this is an empty mimic function, return false or any other logic needed.
    $value = mt_rand(0, 10);
    return $value < 5 ? (string) $value : false;
}

/**
 * Mimics the WordPress wp_remote_get function.
 *
 * @param string $url the URL to fetch
 * @param array<string, mixed> $args Optional. Additional arguments for the request.
 * @return WP_Error|array<string, mixed> the response or WP_Error on failure
 */
function wp_remote_get(string $url, array $args = []): WP_Error|array
{
    // Since this is an empty mimic function, return an empty array or any other logic needed.
    $value = mt_rand(0, 10);
    return $value < 5 ? new WP_Error() : [];
}

class WP_Error {}

class WP_Post {}

/**
 * Mimics the WooCommerce WC_Product_Simple class.
 */
class WC_Product_Simple
{
    private readonly string $name;

    private readonly string $slug;

    /**
     * Constructs a new instance of WC_Product_Simple.
     */
    public function __construct()
    {
        $this->name = '';
        // Default value, assume it could be set elsewhere
        $this->slug = '';
        // Default value, assume it could be set elsewhere
    }

    /**
     * Returns the value of a specified attribute.
     *
     * @param string $attribute the name of the attribute
     * @return string the value of the attribute
     */
    public function get_attribute(string $attribute): string
    {
        // Since this is a stub, return an empty string or any other logic needed.
        return '';
    }

    /**
     * Returns the name of the product.
     *
     * @return string the name of the product
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Returns the SKU of the product.
     *
     * @return string the SKU of the product
     */
    public function get_sku(): string
    {
        // Since this is a stub, return an empty string or any other logic needed.
        return '';
    }

    /**
     * Returns the slug of the product.
     *
     * @return string the slug of the product
     */
    public function get_slug(): string
    {
        return $this->slug;
    }
}
