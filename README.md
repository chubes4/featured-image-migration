# Featured Image Migration Tool

A WordPress plugin that automatically migrates manually added first content images to proper featured image display, eliminating duplicate image processing and improving site performance.

## Problem Solved

Many WordPress users set a featured image AND manually add the same image to their post content, creating:
- Duplicate image display (when themes show both)
- SEO issues with duplicate images in sitemaps
- Cluttered content structure
- Poor editorial workflow

This plugin automatically removes the first content image block when it matches the featured image, streamlining your content while preserving the featured image for SEO and social media purposes.

## Features

- **Automated Migration**: Converts manually added first images to featured image display
- **Performance Optimization**: Eliminates duplicate image processing and improves page load times
- **Batch Processing**: AJAX-powered migration with real-time progress tracking
- **Security First**: Comprehensive nonces, capability checks, and input sanitization
- **Gutenberg Integration**: Advanced block parsing to safely identify and remove image blocks
- **Multi-Post-Type Support**: Works with 'post' and 'recipe' post types
- **Smart Detection**: Only migrates when first content image matches the featured image
- **Detailed Logging**: Real-time migration progress with detailed success/skip reporting

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Block Editor**: Gutenberg block editor (Classic Editor not supported)
- **User Permissions**: Administrator role required for migration operations

## Installation

### Manual Installation

1. Download the plugin files
2. Upload `featured-image-migration.php` to `/wp-content/plugins/featured-image-migration/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to any admin page to see the migration notice

### WordPress Repository (Future)

This plugin is designed for WordPress repository standards and can be packaged for distribution.

## Usage

### Running the Migration

1. **Activate the Plugin**: The migration notice appears automatically for administrators
2. **Review Posts**: Click "Migrate All Images" to start the process
3. **Monitor Progress**: Watch real-time progress with detailed logging
4. **Complete Migration**: Process completes automatically with summary statistics

### Migration Process

The plugin performs these steps for each post:

1. **Validates Post**: Checks for published status and featured image presence
2. **Parses Blocks**: Analyzes Gutenberg block structure for image blocks
3. **Matches Images**: Compares first content image with featured image
4. **Removes Duplicate**: Safely removes first image block if it matches featured image
5. **Updates Content**: Saves modified content while preserving all other blocks

### What Gets Migrated

- ✅ Posts with featured images set
- ✅ Posts containing Gutenberg image blocks
- ✅ First image block matches featured image ID
- ✅ Published posts only

### What Gets Skipped

- ❌ Posts without featured images
- ❌ Posts without image blocks
- ❌ Posts where first image doesn't match featured image
- ❌ Draft or private posts
- ❌ Classic Editor content

## Technical Details

### Supported Post Types

- `post` - Standard WordPress posts
- `recipe` - Recipe post type (if available)

### Block Processing

The plugin uses WordPress core functions for safe block manipulation:

- `parse_blocks()` - Parses post content into block structure
- `serialize_blocks()` - Converts blocks back to content
- Recursive traversal for nested blocks (columns, groups, etc.)
- Safe array manipulation with proper re-indexing

### Security Features

- **Nonce Verification**: All AJAX requests protected with WordPress nonces
- **Capability Checks**: Requires `manage_options` capability
- **Input Sanitization**: All user inputs properly sanitized
- **Error Handling**: Comprehensive error reporting and recovery

### Performance Optimization

- **Batch Processing**: Configurable batch sizes (default: 20 posts)
- **Memory Management**: Efficient processing prevents server overload
- **Progress Throttling**: 500ms delays between batches
- **Selective Processing**: Only processes relevant posts

## Development

### File Structure

```
featured-image-migration/
├── featured-image-migration.php    # Main plugin file (466 lines)
├── CLAUDE.md                       # AI development context
└── README.md                       # This documentation
```

### Key Functions

- `migrate_post_image($post_id)` - Core migration logic
- `find_first_image_block($blocks)` - Recursive block finder
- `remove_first_image_block($blocks)` - Safe block removal
- `count_migration_posts()` - AJAX post counter
- `migrate_images_batch()` - AJAX batch processor

### WordPress Standards

- PSR-4 autoloading ready
- WordPress Coding Standards compliant
- Internationalization support (`featured-image-migration` text domain)
- Proper plugin header with all metadata

## Migration Statistics

After migration completion, you'll see:

- **Total Posts Processed**: Number of posts examined
- **Successfully Migrated**: Posts with duplicate images removed
- **Skipped Posts**: Posts that didn't meet migration criteria
- **Detailed Log**: Real-time processing information

## Troubleshooting

### Common Issues

**Migration Notice Doesn't Appear**
- Ensure user has Administrator role
- Check that posts have featured images set
- Verify WordPress is using Gutenberg editor

**No Posts Being Migrated**
- Confirm posts contain image blocks in content
- Verify first image matches featured image
- Check posts are published (not draft/private)

**Migration Stops or Fails**
- Check server error logs for PHP errors
- Ensure adequate server memory and execution time
- Verify WordPress and PHP version requirements

### Debug Information

Enable WordPress debug logging to troubleshoot issues:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Contributing

### Development Setup

1. Clone or download the plugin
2. Place in WordPress plugins directory
3. Activate in WordPress admin
4. Test with posts containing featured images and image blocks

### Code Standards

- Follow WordPress Coding Standards
- Use WordPress core functions for all operations
- Maintain security-first approach
- Document all public methods
- Test with various block structures

### Security Guidelines

- Always verify nonces for AJAX requests
- Check user capabilities before operations
- Sanitize all user inputs
- Use WordPress post update functions
- Handle errors gracefully

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

**Chris Huber**
- Website: [chubes.net](https://chubes.net/)
- GitHub: [@chubes4](https://github.com/chubes4)

## Support

For issues or questions:

1. Check the troubleshooting section above
2. Review WordPress error logs
3. Ensure requirements are met
4. Test with minimal plugin configuration

---

*This plugin is designed to be a one-time migration tool. Once migration is complete, the plugin can be safely deactivated and removed if no longer needed.*