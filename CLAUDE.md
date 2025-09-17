# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with the Featured Image Migration Tool WordPress plugin.

## Plugin Overview

The Featured Image Migration Tool is a single-file WordPress plugin that eliminates duplicate image processing by migrating manually added first content images to proper featured image display. It provides a comprehensive solution for WordPress sites that need to optimize image handling and improve performance.

## Architectural Principles

### Security-First Design
- **Nonce Verification**: All AJAX requests use WordPress nonces for CSRF protection
- **Capability Checks**: Administrative functions require `manage_options` capability
- **Input Sanitization**: All user inputs are sanitized using `intval()` for numeric values
- **Direct Access Prevention**: Plugin file includes `ABSPATH` check to prevent direct execution

### Single Responsibility Implementation
- **Core Migration Logic**: Focused solely on featured image migration functionality
- **Gutenberg Block Processing**: Specialized handling of WordPress block editor content
- **AJAX Batch Processing**: Dedicated system for handling large-scale migrations
- **Admin Notice Management**: Centralized notice display and dismissal system

### WordPress Standards Compliance
- **Hook-Based Architecture**: Uses proper WordPress actions and filters
- **Plugin Lifecycle Management**: Implements activation and deactivation hooks
- **Internationalization**: Full text domain support with translation functions
- **Database Integration**: Uses WordPress post meta and options APIs

## Code Organization

### Main Plugin Class: `FeaturedImageMigration`
- **Initialization**: Constructor sets up WordPress hooks and plugin lifecycle
- **Admin Interface**: Conditional admin functionality loading
- **AJAX Handlers**: Secure endpoints for migration operations
- **Block Processing**: Gutenberg block parsing and manipulation methods

### Key Methods and Functionality

#### Migration Core Logic
- `migrate_post_image($post_id)`: Main migration logic for individual posts
- `find_first_image_block($blocks)`: Recursive block traversal to locate first image
- `remove_first_image_block($blocks)`: Safe removal of first matching image block

#### AJAX Security Implementation
- `count_migration_posts()`: Counts posts requiring migration with security checks
- `migrate_images_batch()`: Processes migration batches with progress tracking
- Notice dismissal handler with dedicated nonce verification

#### Admin Interface Components
- Dynamic admin notice with JavaScript-powered migration interface
- Real-time progress tracking with detailed logging
- Batch processing with configurable limits (20 posts per batch)

## Technical Implementation Details

### Gutenberg Block Processing
- **Block Parsing**: Uses WordPress `parse_blocks()` and `serialize_blocks()` functions
- **Recursive Traversal**: Handles nested blocks and complex block structures
- **Image Block Detection**: Identifies `core/image` blocks with attachment IDs
- **Content Modification**: Safe block removal with array re-indexing

### Batch Processing System
- **Memory Management**: Processes posts in configurable batches (default: 20)
- **Progress Tracking**: Real-time progress updates with percentage calculations
- **Error Handling**: Comprehensive error reporting and recovery
- **Performance Optimization**: 500ms delays between batches to prevent server overload

### Post Type Support
- **Multi-Post-Type**: Supports 'post' and 'recipe' post types
- **Featured Image Validation**: Requires existing featured image (`_thumbnail_id` meta)
- **Published Content**: Only processes published posts for safety
- **Content Matching**: Verifies first content image matches featured image before migration

## Security Implementation Patterns

### AJAX Endpoint Security
```php
// Nonce verification pattern used throughout
if (!wp_verify_nonce($_POST['nonce'], 'fim_migration_nonce')) {
    wp_die('Security check failed');
}

// Capability check pattern
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}
```

### Input Sanitization
```php
// All numeric inputs sanitized
$offset = intval($_POST['offset']);
$limit = intval($_POST['limit']);
```

### Safe Database Operations
```php
// WordPress post update with error handling
$result = wp_update_post(array(
    'ID' => $post_id,
    'post_content' => $new_content
));

if (is_wp_error($result)) {
    return array('migrated' => false, 'reason' => 'Failed to update post: ' . $result->get_error_message());
}
```

## Development Workflow

### Plugin Lifecycle Management
- **Activation**: Sets migration notice flag for new installations
- **Deactivation**: Cleans up plugin-specific options
- **Initialization**: Loads text domain and admin functionality conditionally

### Option Management
- `fim_show_migration_notice`: Controls admin notice visibility
- `fim_migration_complete`: Tracks migration completion status
- Clean option removal on plugin deactivation

### Testing and Validation
- **Post Validation**: Checks for post existence and featured image presence
- **Block Validation**: Verifies Gutenberg block structure before processing
- **Content Validation**: Ensures first image matches featured image before migration
- **Error Reporting**: Detailed logging for troubleshooting and validation

## Build and Distribution

### WordPress Plugin Standards
- **Plugin Header**: Complete plugin metadata for WordPress repository
- **Version Management**: Consistent versioning across constants and headers
- **Text Domain**: Proper internationalization setup
- **Compatibility**: WordPress 5.0+ and PHP 7.4+ requirements

### File Structure
- **Single File Plugin**: Complete functionality in `featured-image-migration.php`
- **No External Dependencies**: Pure WordPress implementation
- **Minimal Footprint**: Lightweight design for optimal performance

## Performance Considerations

### Efficient Processing
- **Batch Limiting**: Configurable batch sizes prevent memory exhaustion
- **Selective Processing**: Only processes posts with featured images
- **Block Caching**: Minimal block parsing with efficient traversal
- **AJAX Throttling**: Built-in delays prevent server overload

### Database Optimization
- **Meta Queries**: Efficient post selection using meta_query for featured images
- **Single Updates**: Atomic post updates per migration
- **Option Cleanup**: Automatic cleanup of migration-related options

## Code Quality Standards

### WordPress Coding Standards
- **Naming Conventions**: Consistent prefix (`fim_`) for all functions and options
- **Hook Naming**: Standard WordPress action and filter naming
- **Class Organization**: Logical method grouping with clear responsibilities
- **Error Handling**: Comprehensive error checking and user feedback

### Documentation Standards
- **Inline Documentation**: DocBlocks for all public methods
- **Parameter Documentation**: Clear parameter types and descriptions
- **Return Value Documentation**: Detailed return value specifications
- **Code Comments**: Strategic comments for complex logic only

## Extensibility Patterns

### WordPress Hook Integration
- Plugin designed to work seamlessly with existing WordPress themes and plugins
- No conflicts with standard WordPress functionality
- Compatible with custom post types through configurable post type array

### Future Enhancement Points
- Additional post type support through filter hooks
- Customizable batch sizes via options
- Enhanced logging and reporting capabilities
- Integration with WordPress CLI for command-line migrations

This plugin exemplifies WordPress development best practices with security-first design, efficient batch processing, and comprehensive Gutenberg block handling for featured image migration workflows.