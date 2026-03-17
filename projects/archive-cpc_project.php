<?php
/**
 * Plugin archive template for cpc_project overview.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div id="primary" class="content-area cpc_projects_archive_page">
    <main id="main" class="site-main" role="main">
        <?php echo cpc_projects_directory_shortcode(array()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </main>
</div>
<?php
get_sidebar();
get_footer();
