<?php
/**
 * Plugin archive template for cpc_doc overview.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div id="primary" class="content-area cpc_docs_archive_page">
    <main id="main" class="site-main" role="main">
        <?php echo cpc_docs_directory_shortcode(array()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </main>
</div>
<?php
get_sidebar();
get_footer();
