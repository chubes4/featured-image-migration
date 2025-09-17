<?php
/**
 * Plugin Name: Featured Image Migration Tool
 * Plugin URI: https://chubes.net/
 * Description: Migrates manually added first content images to proper featured image display. Eliminates duplicate image processing and improves performance.
 * Version: 1.0.0
 * Author: Chris Huber
 * Author URI: https://chubes.net/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: featured-image-migration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FIM_VERSION', '1.0.0');
define('FIM_PLUGIN_FILE', __FILE__);
define('FIM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FIM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FIM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class for Featured Image Migration Tool
 *
 * Automatically migrates manually added first content images to proper
 * featured image display by removing duplicate image blocks from post content
 * when they match the current featured image.
 *
 * @since 1.0.0
 */
class FeaturedImageMigration {

    /**
     * Initialize the plugin hooks and activation/deactivation handlers
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin functionality after WordPress loads
     *
     * Sets up text domain and admin functionality for migration tool.
     *
     * @since 1.0.0
     */
    public function init() {
        load_plugin_textdomain('featured-image-migration', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if (is_admin()) {
            $this->init_admin();
        }
    }

    /**
     * Initialize admin-specific functionality
     *
     * Registers admin notices and AJAX handlers for the migration process.
     *
     * @since 1.0.0
     */
    private function init_admin() {
        add_action('admin_notices', array($this, 'show_migration_notice'));
        add_action('wp_ajax_fim_count_migration_posts', array($this, 'count_migration_posts'));
        add_action('wp_ajax_fim_migrate_images_batch', array($this, 'migrate_images_batch'));
    }

    /**
     * Plugin activation handler
     *
     * Sets the flag to display migration notice to administrators.
     *
     * @since 1.0.0
     */
    public function activate() {
        update_option('fim_show_migration_notice', true);
    }

    /**
     * Plugin deactivation handler
     *
     * Cleans up plugin options to prevent orphaned data.
     *
     * @since 1.0.0
     */
    public function deactivate() {
        delete_option('fim_show_migration_notice');
        delete_option('fim_migration_complete');
    }

    /**
     * Display migration admin notice with interactive controls
     *
     * Shows migration interface only to administrators when migration
     * is available and not yet completed. Includes JavaScript for
     * batch processing and progress tracking.
     *
     * @since 1.0.0
     */
    public function show_migration_notice() {
        // Don't show if migration is complete or notice is dismissed
        if (get_option('fim_migration_complete') || !get_option('fim_show_migration_notice')) {
            return;
        }

        // Security: Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }

        // Don't show on migration-related pages to avoid conflicts
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'migration') !== false) {
            return;
        }

        ?>
        <div class="notice notice-warning" id="fim-migration-notice">
            <p>
                <strong><?php _e('Featured Image Migration Available', 'featured-image-migration'); ?>:</strong>
                <?php _e('Convert manually added first images to proper featured images for better performance and SEO.', 'featured-image-migration'); ?>
            </p>
            <p>
                <button id="fim-migrate-images-btn" class="button button-primary">
                    <?php _e('Migrate All Images', 'featured-image-migration'); ?>
                </button>
                <button id="fim-dismiss-notice-btn" class="button button-secondary" style="margin-left: 10px;">
                    <?php _e('Dismiss Notice', 'featured-image-migration'); ?>
                </button>
                <span id="fim-migration-progress" style="margin-left: 15px; font-weight: bold;"></span>
            </p>
            <div id="fim-migration-details" style="margin-top: 10px; display: none;">
                <div style="background: #f0f0f1; padding: 10px; border-radius: 4px;">
                    <div id="fim-migration-status"></div>
                    <div id="fim-migration-log" style="max-height: 200px; overflow-y: auto; margin-top: 5px; font-family: monospace; font-size: 12px;"></div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let migrationInProgress = false;

            // Dismiss notice handler
            $('#fim-dismiss-notice-btn').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fim_dismiss_notice',
                        nonce: '<?php echo wp_create_nonce('fim_dismiss_nonce'); ?>'
                    },
                    success: function() {
                        $('#fim-migration-notice').fadeOut();
                    }
                });
            });

            // Migration handler
            $('#fim-migrate-images-btn').on('click', function() {
                if (migrationInProgress) return;

                if (!confirm('<?php _e('This will migrate all posts with manually added first images. Continue?', 'featured-image-migration'); ?>')) {
                    return;
                }

                migrationInProgress = true;
                $(this).prop('disabled', true).text('<?php _e('Starting Migration...', 'featured-image-migration'); ?>');
                $('#fim-migration-details').show();
                $('#fim-migration-progress').text('<?php _e('Initializing...', 'featured-image-migration'); ?>');

                startMigration();
            });

            function startMigration() {
                // Get total posts count first
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fim_count_migration_posts',
                        nonce: '<?php echo wp_create_nonce('fim_migration_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            processBatch(0, response.data.total);
                        } else {
                            showError('Failed to count posts: ' + response.data);
                        }
                    },
                    error: function() {
                        showError('Failed to connect to server');
                    }
                });
            }

            function processBatch(offset, total) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fim_migrate_images_batch',
                        nonce: '<?php echo wp_create_nonce('fim_migration_nonce'); ?>',
                        offset: offset,
                        limit: 20
                    },
                    success: function(response) {
                        if (response.success) {
                            const processed = offset + response.data.processed;
                            const percentage = Math.round((processed / total) * 100);

                            $('#fim-migration-progress').text('Progress: ' + processed + '/' + total + ' (' + percentage + '%)');
                            $('#fim-migration-status').text('Processed batch: ' + response.data.processed + ' posts');

                            // Add log entries
                            if (response.data.log && response.data.log.length > 0) {
                                response.data.log.forEach(function(entry) {
                                    $('#fim-migration-log').append('<div>' + entry + '</div>');
                                });
                                $('#fim-migration-log').scrollTop($('#fim-migration-log')[0].scrollHeight);
                            }

                            if (processed < total) {
                                // Continue with next batch
                                setTimeout(function() {
                                    processBatch(processed, total);
                                }, 500);
                            } else {
                                // Migration complete
                                completeMigration(response.data.stats);
                            }
                        } else {
                            showError('Migration failed: ' + response.data);
                        }
                    },
                    error: function() {
                        showError('Connection failed during migration');
                    }
                });
            }

            function completeMigration(stats) {
                $('#fim-migrate-images-btn').text('<?php _e('Migration Complete!', 'featured-image-migration'); ?>').removeClass('button-primary').addClass('button-disabled');
                $('#fim-migration-progress').html('✓ <?php _e('Migration Complete!', 'featured-image-migration'); ?>').css('color', 'green');
                $('#fim-migration-status').html(
                    '<?php _e('Successfully migrated', 'featured-image-migration'); ?> ' + stats.migrated + ' <?php _e('posts', 'featured-image-migration'); ?>. ' +
                    stats.skipped + ' <?php _e('posts skipped (no matching images)', 'featured-image-migration'); ?>.'
                );

                // Hide notice after 3 seconds
                setTimeout(function() {
                    $('#fim-migration-notice').fadeOut();
                }, 3000);
            }

            function showError(message) {
                migrationInProgress = false;
                $('#fim-migrate-images-btn').prop('disabled', false).text('<?php _e('Migrate All Images', 'featured-image-migration'); ?>');
                $('#fim-migration-progress').html('❌ ' + message).css('color', 'red');
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to count posts available for migration
     *
     * Counts published posts (post and recipe types) that have featured images
     * set. Used to calculate total progress for batch processing.
     *
     * @since 1.0.0
     */
    public function count_migration_posts() {
        // Security: Verify nonce to prevent CSRF attacks
        if (!wp_verify_nonce($_POST['nonce'], 'fim_migration_nonce')) {
            wp_die('Security check failed');
        }

        // Security: Only allow administrators to access migration data
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $posts = get_posts(array(
            'post_type' => array('post', 'recipe'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            )
        ));

        wp_send_json_success(array('total' => count($posts)));
    }

    /**
     * AJAX handler for batch migration processing
     *
     * Processes a batch of posts for image migration. Each batch processes
     * a limited number of posts to prevent timeout issues and provide
     * real-time progress feedback.
     *
     * @since 1.0.0
     */
    public function migrate_images_batch() {
        // Security: Verify nonce to prevent CSRF attacks
        if (!wp_verify_nonce($_POST['nonce'], 'fim_migration_nonce')) {
            wp_die('Security check failed');
        }

        // Security: Only allow administrators to perform migrations
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Sanitize input parameters for batch processing
        $offset = intval($_POST['offset']);
        $limit = intval($_POST['limit']);
        $log = array();
        $migrated = 0;
        $skipped = 0;

        $posts = get_posts(array(
            'post_type' => array('post', 'recipe'),
            'post_status' => 'publish',
            'numberposts' => $limit,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            )
        ));

        foreach ($posts as $post) {
            $result = $this->migrate_post_image($post->ID);

            if ($result['migrated']) {
                $migrated++;
                $log[] = "✓ Migrated post #{$post->ID}: {$post->post_title}";
            } else {
                $skipped++;
                $log[] = "- Skipped post #{$post->ID}: {$result['reason']}";
            }
        }

        if (count($posts) < $limit) {
            update_option('fim_migration_complete', true);
            update_option('fim_show_migration_notice', false);
        }

        wp_send_json_success(array(
            'processed' => count($posts),
            'log' => $log,
            'stats' => array(
                'migrated' => $migrated,
                'skipped' => $skipped
            )
        ));
    }

    /**
     * Migrate a single post's first image block to featured image display
     *
     * Removes the first image block from post content if it matches the
     * current featured image. This prevents duplicate image display while
     * maintaining the featured image for theme display and SEO.
     *
     * @since 1.0.0
     * @param int $post_id Post ID to migrate
     * @return array {
     *     Migration result with status and details
     *
     *     @type bool   $migrated Whether migration was successful
     *     @type string $reason   Details about migration result
     * }
     */
    private function migrate_post_image($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array('migrated' => false, 'reason' => 'Post not found');
        }

        $featured_image_id = get_post_thumbnail_id($post_id);
        if (!$featured_image_id) {
            return array('migrated' => false, 'reason' => 'No featured image');
        }

        $content = $post->post_content;

        // Only process Gutenberg block content (not classic editor)
        if (!has_blocks($content)) {
            return array('migrated' => false, 'reason' => 'No Gutenberg blocks');
        }

        // Parse Gutenberg blocks for processing
        $blocks = parse_blocks($content);
        $first_image_block = $this->find_first_image_block($blocks);

        if (!$first_image_block) {
            return array('migrated' => false, 'reason' => 'No image blocks found');
        }

        // Verify first content image matches featured image before removal
        $first_image_id = $first_image_block['attrs']['id'] ?? 0;
        if ($first_image_id != $featured_image_id) {
            return array('migrated' => false, 'reason' => 'First image does not match featured image');
        }

        // Remove the duplicate first image block and serialize back to content
        $updated_blocks = $this->remove_first_image_block($blocks);
        $new_content = serialize_blocks($updated_blocks);

        // Update post content
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content
        ));

        if (is_wp_error($result)) {
            return array('migrated' => false, 'reason' => 'Failed to update post: ' . $result->get_error_message());
        }

        return array('migrated' => true, 'reason' => 'Successfully migrated');
    }

    /**
     * Find the first image block in parsed blocks array
     *
     * Recursively searches through Gutenberg blocks to find the first
     * core/image block with an ID attribute. Handles nested blocks like
     * columns, groups, and other container blocks.
     *
     * @since 1.0.0
     * @param array $blocks Parsed Gutenberg blocks array
     * @return array|null First image block data or null if not found
     */
    private function find_first_image_block($blocks) {
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/image' && isset($block['attrs']['id'])) {
                return $block;
            }

            // Recursively search nested blocks (columns, groups, etc.)
            if (!empty($block['innerBlocks'])) {
                $inner_result = $this->find_first_image_block($block['innerBlocks']);
                if ($inner_result) {
                    return $inner_result;
                }
            }
        }

        return null;
    }

    /**
     * Remove the first image block from parsed blocks array
     *
     * Recursively searches and removes the first core/image block found.
     * Maintains proper array indexing and handles nested block structures.
     * Only removes the first matching image block to preserve other images.
     *
     * @since 1.0.0
     * @param array $blocks Parsed Gutenberg blocks array
     * @return array Updated blocks array with first image removed
     */
    private function remove_first_image_block($blocks) {
        foreach ($blocks as $index => $block) {
            if ($block['blockName'] === 'core/image' && isset($block['attrs']['id'])) {
                // Remove this block and re-index array to prevent gaps
                unset($blocks[$index]);
                return array_values($blocks);
            }

            // Recursively process nested blocks for image removal
            if (!empty($block['innerBlocks'])) {
                $updated_inner = $this->remove_first_image_block($block['innerBlocks']);
                // Only update if changes were made to preserve original structure
                if ($updated_inner !== $block['innerBlocks']) {
                    $blocks[$index]['innerBlocks'] = $updated_inner;
                    return $blocks;
                }
            }
        }

        return $blocks;
    }

}

// Initialize the plugin
new FeaturedImageMigration();

/**
 * AJAX handler for dismissing migration notice
 *
 * Allows administrators to permanently dismiss the migration notice
 * without performing the migration.
 *
 * @since 1.0.0
 */
add_action('wp_ajax_fim_dismiss_notice', function() {
    // Security: Verify nonce for notice dismissal
    if (!wp_verify_nonce($_POST['nonce'], 'fim_dismiss_nonce')) {
        wp_die('Security check failed');
    }

    // Security: Only administrators can dismiss notices
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    update_option('fim_show_migration_notice', false);
    wp_send_json_success();
});