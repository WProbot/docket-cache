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

$log = $this->parse_log_query();
?>

<div class="section<?php echo !$log->output_empty ? ' log' : ''; ?>">
    <div class="flex-container">
        <div class="row">
            <?php $this->tab_title(!$this->has_vcache() ? esc_html__('Cache Log', 'docket-cache') : esc_html__('Cache View', 'docket-cache')); ?>
            <table class="form-table noborder-b">
                <?php if (!$this->has_vcache()) : ?>
                <tr class="form-table-selection">
                    <th><?php esc_html_e('Timestamp', 'docket-cache'); ?></th>
                    <td>
                        <?php
                        echo $this->config_select_set(
                            'log_time',
                            [
                                'default' => __('Default', 'docket-cache'),
                                'utc' => __('UTC', 'docket-cache'),
                                'local' => __('Local time', 'docket-cache'),
                                'wp' => __('Site Format', 'docket-cache'),
                            ],
                            'dcdefault',
                            [
                                'idx' => 'log',
                                'quiet' => 1,
                            ]
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Log File', 'docket-cache'); ?></th>
                    <td>
                        <?php if ($log->output_empty) : ?>
                        <?php echo $this->info->log_file; ?>
                        <?php else : ?>
                        <a class="btxo" title="<?php esc_html_e('Download', 'docket-cache'); ?>" href="<?php echo $this->tab_query('log', ['dl' => '0'.time()]); ?>" rel="noopener" target="new"><?php echo $this->info->log_file; ?><span class="dashicons dashicons-external"></span></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else : ?>
                <tr>
                    <th><?php esc_html_e('Cache Index', 'docket-cache'); ?></th>
                    <td><?php echo $this->idx_vcache(); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($log->output_empty) : ?>
                <tr>
                    <td colspan="2"><?php esc_html_e('Data Not available', 'docket-cache'); ?></td>
                </tr>
                <?php else : ?>
                <tr>
                    <th class="border-b"><?php echo  $this->has_vcache() ? esc_html__('Cache Size', 'docket-cache') : esc_html__('Log Size', 'docket-cache'); ?></th>
                    <td>
                        <?php
                        echo $log->log_size.' / '.($this->has_vcache() ? $this->cache_max_size : $this->log_max_size);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php if ($this->has_vcache()) : ?>
                        <a href="
							<?php
                            echo $this->pt->action_query(
                                'flush-ocfile',
                                [
                                    'idx' => 'log',
                                    'idxv' => $this->idx_vcache(),
                                ]
                            );
                            ?>
                    " class="button button-primary button-small bt-fx"><?php esc_html_e('Flush', 'docket-cache'); ?></a>
                        <a href="<?php echo $this->tab_query('log'); ?>" class="button button-primary button-small bt-fx"><?php esc_html_e('Close', 'docket-cache'); ?></a>
                        <?php else : ?>
                        <span class="vcache">Select row to view cache content.</span>
                        <a href="<?php echo $this->tab_query('log'); ?>" class="button button-primary button-small bt-fx button-vcache hide"><?php esc_html_e('View', 'docket-cache'); ?></a>
                        <a href="<?php echo $this->tab_query('log'); ?>" class="button button-primary button-small bt-fx button-vcache-c hide"><?php esc_html_e('Close', 'docket-cache'); ?></a>
                        <?php endif; ?>
                        <textarea id="log" class="code" readonly="readonly" rows="<?php echo $log->row_size; ?>" wrap="off"><?php echo $log->output; ?></textarea>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <p class="submit">
                <?php if (!$log->output_empty && !$this->has_vcache()) : ?>
                <select id="order">
                    <?php
                    foreach ([
                        'first' => __('FIRST', 'docket-cache'),
                        'last' => __('LAST', 'docket-cache'),
                    ] as $order => $text) {
                        $selected = ($order === $log->default_order ? ' selected' : '');
                        echo '<option value="'.$order.'"'.$selected.'>'.esc_html($text).'</option>';
                    }
                    ?>
                </select>
                <select id="line">
                    <?php
                    foreach (['10', '50', '100', '300', '500'] as $line) {
                        $selected = ((int) $line === $log->default_line ? ' selected' : '');
                        echo '<option value="'.$line.'"'.$selected.'>'.$line.'</option>';
                    }
                    ?>
                </select>
                <select id="sort">
                    <?php
                    foreach ([
                        'asc' => __('ASCENDING', 'docket-cache'),
                        'desc' => __('DESCENDING', 'docket-cache'),
                    ] as $sort => $text) {
                        $selected = ($sort === $log->default_sort ? ' selected' : '');
                        $text = esc_html($text);
                        if (\in_array($text, ['ASCENDING', 'DESCENDING'])) {
                            $text = 'desc' === $sort ? substr($text, 0, 4) : substr($text, 0, 3);
                        }
                        echo '<option value="'.$sort.'"'.$selected.'>'.$text.'</option>';
                    }
                    ?>
                </select>
                <br>
                <a href="<?php echo $this->pt->action_query('flush-oclog', ['idx' => 'log']); ?>" class="button button-primary button-large"><?php esc_html_e('Flush Log', 'docket-cache'); ?></a>
                <?php endif; ?>

                <?php if (($this->info->log_enable || !$log->output_empty) && !$this->has_vcache()) : ?>
                <a href="<?php echo $this->tab_query('log'); ?>" class="button button-secondary button-large" id="refresh"><?php echo esc_html_e('Refresh', 'docket-cache'); ?></a>
                <?php endif; ?>

                <?php if ($this->info->log_enable && $this->has_vcache() && $log->output_empty) : ?>
                <a href="<?php echo $this->tab_query('log'); ?>" class="button button-secondary button-large" id="refresh"><?php echo esc_html_e('Refresh', 'docket-cache'); ?></a>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>