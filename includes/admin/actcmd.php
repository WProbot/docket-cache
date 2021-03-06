<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;
$ocdisabled = 2 === $this->info->status_code ? ' onclick="return false;" disabled' : '';
$opdisabled = 2 === $this->info->opcache_code || 0 === $this->info->opcache_code ? ' onclick="return false;" disabled' : '';
?>
<?php $this->tab_title('Actions', true); ?>
<div class="qact">
    <div class="cmd">
        <h4><?php esc_html_e('Object Cache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove all cache files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-occache'); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Cache', 'docket-cache'); ?></a>
        <hr>

        <h4><?php esc_html_e('Zend OPcache', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Reset OPcache usage.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-opcache'); ?>" class="button button-primary button-large btx-spinner" <?php echo $opdisabled; ?>><?php esc_html_e('Flush OPcache', 'docket-cache'); ?></a>

        <hr>
        <h4><?php esc_html_e('Object Cache Drop-In', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Enable / Disable Drop-In usage.', 'docket-cache'); ?>
        </p>
        <?php if ($this->is_dropin_validate() && $this->is_dropin_multinet()) : ?>
        <a href="<?php echo $this->pt->action_query('disable-occache'); ?>" class="button button-primary button-large btx-spinner" <?php echo $ocdisabled; ?>><?php esc_html_e('Disable Object Cache', 'docket-cache'); ?></a>
        <?php else : ?>
        <a href="<?php echo $this->pt->action_query('enable-occache'); ?>" class="button button-secondary button-large btx-spinner" <?php echo $ocdisabled; ?>><?php esc_html_e('Enable Object Cache', 'docket-cache'); ?></a>
        <?php endif; ?>
    </div>
</div>