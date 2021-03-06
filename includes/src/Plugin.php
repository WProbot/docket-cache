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

final class Plugin extends Bepart
{
    /**
     * Plugin file.
     *
     * @var string
     */
    public $file;

    /**
     * Plugin slug.
     *
     * @var string
     */
    public $slug;

    /**
     * Plugin hook.
     *
     * @var string
     */
    public $hook;

    /**
     * Plugin path.
     *
     * @var string
     */
    public $path;

    /**
     * Plugin valid page uri.
     *
     * @var string
     */
    public $page;

    /**
     * Plugin action token.
     *
     * @var string
     */
    public $token;

    /**
     * Plugin screen name.
     *
     * @var string
     */
    public $screen;

    /**
     * The cache path.
     *
     * @var string
     */
    public $cache_path;

    /**
     * API Endpoint.
     *
     * @var string
     */
    public $api_endpoint;

    /**
     * Cronbot Endpoint.
     *
     * @var string
     */
    public $cronbot_endpoint;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->slug = 'docket-cache';
        $this->file = nwdcx_constval('FILE');
        $this->hook = plugin_basename($this->file);
        $this->path = realpath(plugin_dir_path($this->file));
        $this->register_init();
    }

    /**
     * Dropino().
     */
    public function cx()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Dropino($this->path);
        }

        return $inst;
    }

    /**
     * Canopt().
     */
    public function co()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Canopt();
        }

        return $inst;
    }

    /**
     * Constans().
     */
    public function cf()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Constans();
        }

        return $inst;
    }

    /**
     * get_version.
     */
    public function version()
    {
        static $version = false;

        if (!empty($version)) {
            return $version;
        }

        $version = $this->plugin_meta($this->file)['Version'];

        return $version;
    }

    /**
     * get_info.
     */
    public function get_info()
    {
        $status_code = [
             0 => __('Disabled', 'docket-cache'),
             1 => __('Enabled', 'docket-cache'),
             2 => __('Not Available', 'docket-cache'),
             3 => __('Unknown', 'docket-cache'),
         ];

        $yesno = [
             0 => esc_html__('No', 'docket-cache'),
             1 => esc_html__('Yes', 'docket-cache'),
         ];

        $force_stats = $this->cf()->is_dctrue('WPCLI');
        $cache_stats = $this->get_cache_stats($force_stats);

        $status = $this->get_status();
        $status_text_stats = '';
        $status_text = '';

        switch ($status) {
             case 1:
                 if ($this->cf()->is_dctrue('STATS')) {
                     /* translators: %1$s = size, %2$s number of file */
                     $status_text_stats = sprintf(esc_html__(_n('%1$s size of %2$s file', '%1$s size of %2$s files', $cache_stats->files < 1 ? 1 : $cache_stats->files, 'docket-cache')), $this->normalize_size($cache_stats->size), $cache_stats->files);
                 }
                 $status_text = $status_code[1];
                 break;
             case 2:
                 $status_text = esc_html__('Disabled at runtime.', 'docket-cache');
                 break;
             default:
                 $status_text = $status_code[$status];
         }

        $opcache = $this->get_opcache_status();
        $opcache_text_stats = '';
        $opcache_status = 2;

        $opcache_dc_stats = '';
        $opcache_wp_stats = '';
        if (1 === $opcache->status) {
            /* translators: %1$s = size, %2$s number of file */
            $opcache_text_stats = sprintf(esc_html__(_n('%1$s memory of %2$s file', '%1$s memory of %2$s files', $opcache->files, 'docket-cache')), $this->normalize_size($opcache->size), $opcache->files);
            $opcache_status = 1;

            if ($opcache->dcfiles > 1) {
                /* translators: %1$s = size, %2$s number of file */
                $opcache_dc_stats = sprintf(esc_html__(_n('%1$s memory of %2$s file', '%1$s memory of %2$s files', $opcache->dcfiles, 'docket-cache')), $this->normalize_size($opcache->dcsize), $opcache->dcfiles);

                /* translators: %1$s = size, %2$s number of file */
                $opcache_wp_stats = sprintf(esc_html__(_n('%1$s memory of %2$s file', '%1$s memory of %2$s files', $opcache->wpfiles, 'docket-cache')), $this->normalize_size($opcache->wpsize), $opcache->wpfiles);
            }
        }

        $log_enable = $this->cf()->is_dctrue('LOG') ? 1 : 0;
        $log_file = $this->cf()->dcvalue('LOG_FILE');

        if (is_multisite()) {
            $log_file = nwdcx_network_filepath($log_file);
        }

        $multisite_text = $status_code[3];
        $multinets_lock = '';

        if (is_multisite()) {
            $netcount = get_networks(['count' => 1]);
            $this->get_network_sites($sitecount, true);

            /* translators: %s = sites */
            $multisite_text = sprintf(esc_html__(_n('%s Site', '%s Sites', $sitecount, 'docket-cache')), $sitecount);

            if ($netcount > 1) {
                /* translators: %s = networks */
                $multisite_text = $multisite_text.' '.sprintf(esc_html__(_n('of %s Network', 'of %s Networks', $netcount, 'docket-cache')), $netcount);

                $multinets_lock = $this->sanitize_rootpath($this->cx()->multinet_tag());
            }
        }

        $file_dropin = WP_CONTENT_DIR.'/object-cache.php';
        if (@is_file($file_dropin)) {
            $write_dropin = @is_writable($file_dropin);
        } else {
            $write_dropin = @is_writable(WP_CONTENT_DIR.'/');
        }

        return [
             'status_code' => $status,
             'status_text' => $status_text,
             'status_text_stats' => $status_text_stats,
             'opcache_code' => $opcache->status,
             'opcache_text' => $status_code[$opcache_status],
             'opcache_text_stats' => $opcache_text_stats,
             'opcache_dc_stats' => $opcache_dc_stats,
             'opcache_wp_stats' => $opcache_wp_stats,
             'php_memory_limit' => $this->normalize_size(@ini_get('memory_limit')),
             'wp_memory_limit' => $this->normalize_size(WP_MEMORY_LIMIT),
             'wp_max_memory_limit' => $this->normalize_size(WP_MAX_MEMORY_LIMIT),
             'write_dropin' => $yesno[$write_dropin],
             'dropin_path' => $this->sanitize_rootpath($file_dropin),
             'write_cache' => $yesno[is_writable($this->cache_path)],
             'cache_size' => $this->normalize_size($cache_stats->size),
             'cache_path_real' => $this->cache_path,
             'cache_path' => $this->sanitize_rootpath($this->cache_path),
             'cache_maxfile' => $this->get_cache_maxfile(),
             'cache_maxsize_disk' => $this->normalize_size($this->get_cache_maxsize_disk()),
             'log_file_real' => $log_file,
             'log_file' => $this->sanitize_rootpath($log_file),
             'log_enable' => $log_enable,
             'log_enable_text' => $status_code[$log_enable],
             'config_path' => $this->sanitize_rootpath($this->co()->path),
             'write_config' => $yesno[$this->co()->is_options_writable()],
             'wp_multisite' => $multisite_text,
             'wp_multinetlock' => $multinets_lock,
             'wp_multinetmain' => $yesno[is_main_network()],
         ];
    }

    /**
     * sanitize_rootpath.
     */
    public function sanitize_rootpath($path)
    {
        return rtrim(str_replace([WP_CONTENT_DIR, ABSPATH], ['/'.basename(WP_CONTENT_DIR), '/'], $path), '/');
    }

    /**
     * get_status.
     */
    public function get_status()
    {
        if ($this->cf()->is_dctrue('DISABLED')) {
            return 2;
        }

        if (!$this->cx()->exists()) {
            return 0;
        }

        if ($this->cx()->validate() && $this->cx()->multinet_me()) {
            return 1;
        }

        if (!$this->cx()->multinet_me()) {
            return 0;
        }

        return 3;
    }

    /**
     * get_logsize.
     */
    public function get_logsize()
    {
        if ($this->has_log($logfile)) {
            return $this->normalize_size($this->filesize($logfile));
        }

        return 0;
    }

    /**
     * get_opcache_status.
     */
    public function get_opcache_status($is_raw = false)
    {
        $total_bytes = 0;
        $total_files = 0;
        $status = 0;

        $dcfiles = 0;
        $dcbytes = 0;

        $wpfiles = 0;
        $wpbytes = 0;

        $raw = [];
        if ($this->is_opcache_enable() && \function_exists('opcache_get_status')) {
            $data = @opcache_get_status();
            if (!empty($data) && \is_array($data) && (!empty($data['opcache_enabled']) || !empty($data['file_cache_only']))) {
                $status = 1;

                if (!empty($data['memory_usage']['used_memory'])) {
                    $total_bytes = $data['memory_usage']['used_memory'];
                }
                if (!empty($data['opcache_statistics']['num_cached_scripts'])) {
                    $total_files = $data['opcache_statistics']['num_cached_scripts'];
                }

                if (!empty($data['scripts']) && \is_array($data['scripts'])) {
                    foreach ($data['scripts'] as $script => $arr) {
                        if ($this->is_docketcachedir(\dirname($script)) && preg_match('@^([a-z0-9]+)\-([a-z0-9]+).*\.php$@', basename($script))) {
                            ++$dcfiles;
                            if (isset($arr['memory_consumption'])) {
                                $dcbytes += $arr['memory_consumption'];
                            }
                        } else {
                            ++$wpfiles;
                            if (isset($arr['memory_consumption'])) {
                                $wpbytes += $arr['memory_consumption'];
                            }
                        }
                    }
                }

                $raw = $data;
            }
        }

        $arr = [
            'status' => (int) $status,
            'size' => $total_bytes,
            'files' => (int) $total_files,
            'wpfiles' => (int) $wpfiles,
            'wpsize' => (int) $wpbytes,
            'dcfiles' => (int) $dcfiles,
            'dcsize' => (int) $dcbytes,
        ];

        if ($is_raw) {
            $arr['data'] = $raw;
        }

        return (object) $arr;
    }

    /**
     * get_cache_maxfile.
     */
    public function get_cache_maxfile()
    {
        $maxfile = $this->cf()->dcvalue('MAXFILE');
        if (empty($maxfile) || !\is_int($maxfile)) {
            $maxfile = 5000;
        }

        if ($maxfile < 200) {
            $maxfile = 5000;
        } elseif ($maxfile > 200000) {
            $maxfile = 200000;
        }

        return $maxfile;
    }

    /**
     * get_cache_maxttl.
     */
    public function get_cache_maxttl()
    {
        $maxttl = $this->cf()->dcvalue('MAXTTL');
        $maxttl = $this->sanitize_second($maxttl);

        // 86400 = 1d
        // 172800 = 2d
        // 345600 = 4d
        // 2419200 = 28d
        if ($maxttl < 86400) {
            $maxttl = 345600;
        } elseif ($maxttl > 2419200) {
            $maxttl = 2419200;
        }

        return $maxttl;
    }

    /**
     * get_cache_maxsize_disk.
     */
    public function get_cache_maxsize_disk()
    {
        $maxsizedisk = $this->cf()->dcvalue('MAXSIZE_DISK');
        if (empty($maxsizedisk) || !\is_int($maxsizedisk)) {
            $maxsizedisk = 524288000; // 500MB
        }

        if ($maxsizedisk < 104857600) {
            $maxsizedisk = 104857600;
        }

        return $maxsizedisk;
    }

    /**
     * get_cache_stats.
     */
    public function get_cache_stats($force = false)
    {
        $cache_stats = false;

        if ($this->cf()->is_dctrue('STATS')) {
            if ($force) {
                $cache_stats = $this->cache_size($this->cache_path);
                $this->co()->save_part($cache_stats, 'cachestats');
            } else {
                $cache_stats = $this->co()->get_part('cachestats');
            }
        }

        if (empty($cache_stats) || !\is_array($cache_stats)) {
            $cache_stats = [
                'size' => 0,
                'filesize' => 0,
                'files' => 0,
            ];
        }

        return (object) $cache_stats;
    }

    /**
     * normalize_size.
     */
    public function normalize_size($size)
    {
        $size = wp_convert_hr_to_bytes($size);
        $size = str_replace([',', ' ', 'B'], '', size_format($size));
        if (is_numeric($size)) {
            $size = $size.'B';
        }

        return $size;
    }

    public function site_url_scheme($site_url)
    {
        if ('https://' !== substr($site_url, 0, 8) && $this->is_ssl()) {
            $site_url = nwdcx_fixscheme($site_url, 'https://');
        } elseif (!@preg_match('@^(https?:)?//@', $site_url)) {
            $site_url = nwdcx_fixscheme($site_url, 'http://');
        }

        return rtrim($site_url, '/\\');
    }

    public function site_url($current = false)
    {
        $site_url = get_option('siteurl');
        if (!$current && is_multisite()) {
            $blog_id = get_main_site_id();
            switch_to_blog($blog_id);
            $site_url = get_option('siteurl');
            restore_current_blog();
        }

        return $this->site_url_scheme($site_url);
    }

    public function site_meta($short = false)
    {
        $m = '0,0';
        if (is_multisite()) {
            $n = get_networks(['count' => 1]);
            $s = get_sites(['count' => 1]);
            $m = $n.','.$s;
        }

        if ($short) {
            $meta = $m.','.$this->version();
            $meta = str_replace('0,0,', '', $meta);
            $meta = str_replace('0', '', $meta);
        } else {
            $meta = $m.','.$this->version().','.$GLOBALS['wp_version'];
        }

        return str_replace('.', '', $meta);
    }

    /**
     * flush_cache.
     */
    public function flush_cache($cleanup = false)
    {
        $this->co()->clear_part('cachestats');
        $this->cx()->delay();

        delete_expired_transients(true);

        $ret = $this->cachedir_flush($this->cache_path, $cleanup);
        if (false === $ret) {
            $this->cx()->undelay();

            return false;
        }

        return true;
    }

    /**
     * flush_fcache.
     */
    public function flush_fcache()
    {
        if (!empty($_GET['idxv'])) {
            $fx = sanitize_text_field($_GET['idxv']);
            $file = $this->cache_path.$fx.'.php';
            if (@is_file($file)) {
                $this->co()->clear_part('cachestats');

                return $this->unlink($file, true);
            }
        }

        return true;
    }

    /**
     * flush_opcache.
     */
    public function flush_opcache()
    {
        return $this->opcache_reset($this->cache_path);
    }

    /**
     * has_log.
     */
    public function has_log(&$logfile = '')
    {
        $logfile = $this->cf()->dcvalue('LOG_FILE');

        if (is_multisite()) {
            $logfile = nwdcx_network_filepath($logfile);
        }

        return @is_file($logfile) && is_readable($logfile);
    }

    /**
     * flush_log.
     */
    public function flush_log()
    {
        if ($this->has_log($logfile)) {
            return @unlink($logfile);
        }

        return false;
    }

    /**
     * suspend_wp_options_autoload.
     */
    public function suspend_wp_options_autoload($status = null)
    {
        if (version_compare($this->version(), '20.09.05', '>')) {
            return false;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        // 20201020: always false for compat. now handle by Filesystem::optimize_alloptions()
        $status = false;

        $suspend_value = 'docketcache-no';
        $options_tbl = $wpdb->options;

        // check
        $query = $wpdb->prepare("SELECT autoload FROM `{$options_tbl}` WHERE autoload=%s ORDER BY option_id ASC LIMIT 1", $suspend_value);
        $check = $wpdb->query($query);
        if ($check < 1) {
            return false;
        }

        $query = $wpdb->prepare("UPDATE `{$options_tbl}` SET autoload='yes' WHERE autoload=%s ORDER BY option_id ASC", $suspend_value);

        $suppress = $wpdb->suppress_errors(true);
        $result = $wpdb->query($query);
        $wpdb->suppress_errors($suppress);

        wp_cache_delete('alloptions', 'options');

        return $result;
    }

    public function switch_cron_site()
    {
        if (is_multisite()) {
            $cronbot_siteid = $this->get_cron_siteid();
            if (!empty($cronbot_siteid) && (int) $cronbot_siteid > 0) {
                switch_to_blog($cronbot_siteid);

                return true;
            }
        }

        return false;
    }

    public function delete_cron_siteid($userid = false)
    {
        if (empty($userid)) {
            $userid = get_current_user_id();
        }

        $key = 'cronbot-siteid-'.get_current_user_id();

        return $this->co()->lookup_delete($key);
    }

    public function set_cron_siteid($id)
    {
        $key = 'cronbot-siteid-'.get_current_user_id();

        return $this->co()->lookup_set($key, $id);
    }

    public function get_cron_siteid()
    {
        $key = 'cronbot-siteid-'.get_current_user_id();
        $siteid = $this->co()->lookup_get($key);
        if (empty($siteid)) {
            $siteid = is_multisite() ? get_main_site_id() : get_current_blog_id();
        }

        return $siteid;
    }

    /**
     * pushup.
     */
    public function wearechampion()
    {
        add_action(
            'shutdown',
            function () {
                $active_plugins = (array) get_option('active_plugins', []);
                if (!empty($active_plugins) && \is_array($active_plugins) && isset($active_plugins[0]) && \in_array($this->hook, $active_plugins) && $this->hook !== $active_plugins[0]) {
                    unset($active_plugins[array_search($this->hook, $active_plugins)]);
                    array_unshift($active_plugins, $this->hook);
                    update_option('active_plugins', $active_plugins);
                }
            },
            PHP_INT_MAX
        );
    }

    /**
     * cleanup.
     */
    private function cleanup($is_uninstall = false)
    {
        if ($this->cx()->validate()) {
            $this->cx()->uninstall();
        }

        $this->cx()->undelay();
        $this->cachedir_flush($this->cache_path, true);
        $this->flush_log();
        $this->suspend_wp_options_autoload(false);

        // clear all network if available
        if ($is_uninstall && is_multisite()) {
            $this->cx()->multinet_clear($this->cache_path, $this->cf()->dcvalue('LOG_FILE'));
        }
    }

    /**
     * uninstall.
     */
    public static function uninstall()
    {
        ( new self() )->cleanup(true);
    }

    /**
     * deactivate.
     */
    public function deactivate()
    {
        $this->cleanup();
        $this->unregister_cronjob();
    }

    /**
     * activate.
     */
    public function activate()
    {
        $this->flush_cache();
        $this->suspend_wp_options_autoload(null);
        $this->cx()->install(true);
        $this->unregister_cronjob();
        $this->wearechampion();
    }

    /**
     * register_init.
     */
    private function register_init()
    {
        $this->page = 'admin.php?page='.$this->slug;
        $this->screen = 'toplevel_page_docket-cache';
        $this->cache_path = $this->define_cache_path($this->cf()->dcvalue('PATH'));

        if (is_multisite()) {
            $this->cache_path = nnwdcx_network_dirpath($this->cache_path);
        }

        $this->api_endpoint = 'https://api.docketcache.com';
        $this->cronbot_endpoint = 'https://cronbot.docketcache.com';

        // use Constans() to trigger default
        if ($this->cf()->is_dctrue('DEV')) {
            $this->api_endpoint = 'http://api.docketcache.local';
            $this->cronbot_endpoint = 'http://cronbot.docketcache.local';
        }
    }

    /**
     * plugin_upgrade.
     */
    private function plugin_upgrade()
    {
        add_action(
            'shutdown',
            function () {
                $this->cx()->install(true);
                if (is_multisite()) {
                    $this->cx()->multinet_install($this->hook);
                }

                // put last
                $this->critical_version();
            },
            PHP_INT_MAX
        );
    }

    // @TODO: flush cache for checkversion
    private function critical_version()
    {
        return true;
    }

    /**
     * register_plugin_hooks.
     */
    private function register_plugin_hooks()
    {
        add_action(
            'plugins_loaded',
            function () {
                load_plugin_textdomain(
                    'docket-cache',
                    false,
                    $this->path.'/languages/'
                );
            },
            0
        );

        add_action(
            'upgrader_process_complete',
            function ($wp_upgrader, $options) {
                if ('update' !== $options['action']) {
                    return;
                }

                if ('plugin' === $options['type'] && !empty($options['plugins'])) {
                    if (!\is_array($options['plugins'])) {
                        return;
                    }

                    foreach ($options['plugins'] as $plugin) {
                        if ($plugin === $this->hook) {
                            $this->plugin_upgrade();
                            break;
                        }
                    }
                }
            },
            PHP_INT_MAX,
            2
        );

        // wp 5.5 >=
        if (\function_exists('wp_is_maintenance_mode')) {
            add_action(
                'upgrader_overwrote_package',
                function ($package, $package_data, $package_type = 'plugin') {
                    if (!empty($package_data) && \is_array($package_data) && !empty($package_data['TextDomain']) && $this->slug === $package_data['TextDomain']) {
                        $this->plugin_upgrade();
                    }
                },
                PHP_INT_MAX,
                3
            );
        }

        add_action(
            'admin_footer',
            function () {
                $output = $this->cx()->after_delay();
                if (!empty($output)) {
                    echo $output;
                }
            },
            PHP_INT_MAX
        );

        if ($this->cf()->is_dctrue('SIGNATURE')) {
            add_action(
                'send_headers',
                function () {
                    $status = $this->cx()->validate() ? 'on' : 'off';
                    header('x-'.$this->slug.': '.$status.'; '.$this->site_meta(true));
                },
                PHP_INT_MAX
            );
        }

        add_action(
            'plugins_loaded',
            function () {
                if (!headers_sent() && $this->cf()->is_dctrue('LOG')
                    && !empty($_SERVER['REQUEST_URI']) && false !== strpos($_SERVER['REQUEST_URI'], '?page=docket-cache&idx=log&dl=0')
                    && preg_match('@log\&dl=\d+$@', $_SERVER['REQUEST_URI'])) {
                    $file = $this->cf()->dcvalue('LOG_FILE');

                    if (is_multisite()) {
                        $file = nwdcx_network_filepath($file);
                    }

                    @header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                    @header('Content-Type: text/plain; charset=UTF-8');
                    if (@is_file($file)) {
                        @readfile($file);
                        exit;
                    }
                    echo 'No data available';
                    exit;
                }
            },
            0
        );

        add_action(
            'plugin_loaded',
            function () {
                if (nwdcx_wpdb($wpdb)) {
                    static $done = false;

                    if (!$done) {
                        $done = true;

                        $suppress = $wpdb->suppress_errors(true);

                        $ok = $wpdb->get_var('SELECT @@SESSION.SQL_BIG_SELECTS');
                        if (empty($ok)) {
                            $wpdb->query('SET SESSION SQL_BIG_SELECTS=1');
                        }

                        $wpdb->suppress_errors($suppress);
                    }
                }
            },
            -PHP_INT_MAX
        );

        if ($this->cf()->is_dctrue('AUTOUPDATE')) {
            add_filter(
                'auto_update_plugin',
                function ($update, $item) {
                    if ('docket-cache' === $item->slug) {
                        return true;
                    }

                    return $update;
                },
                PHP_INT_MAX,
                2
            );
        }

        if (class_exists('Nawawi\\DocketCache\\CronAgent')) {
            ( new CronAgent($this) )->register();
        }

        register_activation_hook($this->hook, [$this, 'activate']);
        register_deactivation_hook($this->hook, [$this, 'deactivate']);
        register_uninstall_hook($this->hook, [__CLASS__, 'uninstall']);
    }

    /**
     * action_query.
     */
    public function action_query($key, $args_extra = [])
    {
        $key = str_replace('docket-', '', $key);
        $key = 'docket-'.$key;

        $args = array_merge(
            [
                'action' => $key,
            ],
            $args_extra
        );

        $query = add_query_arg($args, $this->page);

        return wp_nonce_url(network_admin_url($query), $key);
    }

    public function our_screen()
    {
        $current_screen = get_current_screen()->id;
        if (substr($current_screen, 0, \strlen($this->screen)) === $this->screen) {
            return true;
        }

        $subsplug = $this->slug.'_page_'.$this->slug.'-';
        if (substr($current_screen, 0, \strlen($subsplug)) === $subsplug) {
            return true;
        }

        return false;
    }

    /**
     * register_admin_hooks.
     */
    private function register_admin_hooks()
    {
        $action_name = is_multisite() ? 'network_admin_menu' : 'admin_menu';
        add_action(
            $action_name,
            function () {
                $cap = is_multisite() ? 'manage_network_options' : 'manage_options';
                $order = is_multisite() ? '25.1' : '80.1';
                $icon = 'PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJ';
                $icon .= 'QyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8y';
                $icon .= 'MDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4';
                $icon .= 'bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iMzAwLjAwMDAwMHB0IiBo';
                $icon .= 'ZWlnaHQ9IjMwMC4wMDAwMDBwdCIgdmlld0JveD0iMCAwIDMwMC4wMDAwMDAgMzAwLjAwMDAwMCIK';
                $icon .= 'IHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIG1lZXQiPgoKPGcgdHJhbnNmb3JtPSJ0cmFu';
                $icon .= 'c2xhdGUoMC4wMDAwMDAsMzAwLjAwMDAwMCkgc2NhbGUoMC4xMDAwMDAsLTAuMTAwMDAwKSIKZmls';
                $icon .= 'bD0iI2JhYmFiYSIgc3Ryb2tlPSJub25lIj4KPHBhdGggZD0iTTEyODUgMjk5NCBjLTcwIC0xMSAt';
                $icon .= 'MjUwIC01NyAtMjkwIC03NCAtMTEgLTUgLTQzIC0xOCAtNzEgLTI5IC0yOAotMTEgLTc1IC0zMyAt';
                $icon .= 'MTA2IC00OSAtMzEgLTE3IC01OSAtMjggLTYyIC0yNSAtMyA0IC02IDEgLTYgLTUgMCAtNyAtNiAt';
                $icon .= 'MTIKLTE0IC0xMiAtOCAwIC0xNiAtMyAtMTggLTcgLTIgLTUgLTMwIC0yNiAtNjMgLTQ4IC04NiAt';
                $icon .= 'NTggLTk5IC02OCAtMjE1IC0xODUKLTExNyAtMTE2IC0xMjcgLTEyOSAtMTg1IC0yMTUgLTIyIC0z';
                $icon .= 'MyAtNDMgLTYxIC00NyAtNjMgLTUgLTIgLTggLTEwIC04IC0xOCAwCi04IC01IC0xNCAtMTIgLTE0';
                $icon .= 'IC02IDAgLTkgLTMgLTUgLTYgMyAtMyAtOCAtMzEgLTI1IC02MiAtMTYgLTMxIC0zOCAtNzggLTQ5';
                $icon .= 'Ci0xMDYgLTExIC0yOCAtMjQgLTYwIC0yOSAtNzEgLTEwIC0yMyAtMzEgLTk2IC01NyAtMjAwIC0y';
                $icon .= 'NiAtMTAyIC0yNiAtNTA4IDAKLTYxMCAyNSAtMTAxIDQ3IC0xNzcgNTcgLTIwMCA1IC0xMSAyNSAt';
                $icon .= 'NTggNDUgLTEwNSAxOSAtNDcgNDcgLTEwMiA2MCAtMTIyIDE0Ci0yMSAyNSAtNDQgMjUgLTUyIDAg';
                $icon .= 'LTggMyAtMTYgOCAtMTggMTAgLTUgNjQgLTc4IDU3IC03OCAtNCAwIDAgLTYgNyAtMTQgNyAtNwoz';
                $icon .= 'MyAtMzcgNTggLTY3IDYxIC03MyAxMDcgLTEyMCAxNTggLTE2MyAyNCAtMjAgNDAgLTM2IDM1IC0z';
                $icon .= 'NiAtNCAwIDIgLTYgMTQKLTEzIDEyIC02IDY2IC00MiAxMjAgLTc4IDU0IC0zNyAxMzAgLTgyIDE2';
                $icon .= 'OCAtMTAwIDM5IC0xOCA3MyAtMzcgNzYgLTQyIDMgLTUKMTAgLTggMTUgLTggNSAxIDQzIC0xMCA4';
                $icon .= 'NCAtMjMgMTg1IC02MCAyOTIgLTc2IDQ5NSAtNzUgMTk4IDAgMzIxIDIwIDQ4NSA3NQozMjMgMTEw';
                $icon .= 'IDU5NCAzMjUgNzg1IDYyMiA3NiAxMTkgMTUwIDI5MCAxODUgNDMyIDI2IDEwMCAzOSAyMjMgMzkg';
                $icon .= 'MzY1IDEgMjAzCi0xNyAzMjIgLTc0IDQ5MCAtMTMgMzkgLTI1IDc3IC0yNyA4NSAtMiA4IC02IDE3';
                $icon .= 'IC05IDIwIC0zIDMgLTIwIDM3IC0zOCA3NQotMTggMzkgLTYzIDExNCAtMTAwIDE2OCAtMzYgNTQg';
                $icon .= 'LTcyIDEwOCAtNzggMTIwIC03IDEyIC0xMyAxOCAtMTMgMTQgMCAtNSAtMTYKMTEgLTM2IDM1IC00';
                $icon .= 'MyA1MSAtOTAgOTcgLTE2MyAxNTggLTMwIDI1IC02MCA1MSAtNjcgNTggLTggNyAtMTQgMTEgLTE0';
                $icon .= 'IDcgMAotNyAtNzMgNDcgLTc4IDU4IC0yIDQgLTEwIDcgLTE4IDcgLTggMCAtMzEgMTEgLTUyIDI1';
                $icon .= 'IC0yMCAxMyAtNzUgNDEgLTEyMiA2MAotNDcgMjAgLTk0IDQwIC0xMDUgNDUgLTExIDUgLTQ1IDE1';
                $icon .= 'IC03NSAyNCAtMTY4IDQ3IC0xODIgNDkgLTQwMCA1MiAtMTE4IDEKLTIyOCAwIC0yNDUgLTJ6IG0t';
                $icon .= 'MzkwIC01NDMgYzcwIC01NyAxMjIgLTk5IDIxNSAtMTc3IDI4OSAtMjQyIDY5MyAtNjMxIDgwOAot';
                $icon .= 'Nzc2IDE0OCAtMTg4IDE4NSAtMjcwIDE4NiAtNDAzIDAgLTc3IC00IC05OCAtMjggLTE0OSAtMzIg';
                $icon .= 'LTcwIC0xMTggLTE1OAotMTkyIC0xOTggLTk4IC01MyAtMjgzIC04MSAtMzc5IC01OCAtMzUgOCAt';
                $icon .= 'NDYgNCAxMTYgNDAgMTY1IDM2IDMxNCAxMzIgMzczCjIzOSAzNiA2NyAzOSAxNDYgOSAyMDYgLTg3';
                $icon .= 'IDE2OCAtNDkwIDM2OSAtMTE2MyA1NzggLTE4MiA1NyAtMjAwIDU4IC0xNDMgMTMKNTggLTQ0IDI3';
                $icon .= 'MyAtMjI0IDM4MyAtMzIwIDI0NiAtMjEzIDM5MiAtMzU4IDM3NiAtMzczIC03IC03IC0xMTkgNjYg';
                $icon .= 'LTIzMSAxNTEKLTg3IDY2IC0zNjcgMjg3IC0zNzUgMjk2IC0zIDMgLTU0IDQ2IC0xMTUgOTUgLTYw';
                $icon .= 'IDQ5IC0xMjEgOTkgLTEzNSAxMTEgLTI0IDIxCi0yOTAgMjQ0IC0yOTkgMjUxIC00OCAzNyA0NDcg';
                $icon .= 'LTkyIDc1OCAtMTk4IDYzIC0yMSAxMTYgLTM4IDExNyAtMzYgMSAxIC04NQoxNzUgLTE5MSAzODcg';
                $icon .= 'LTEwNyAyMTIgLTE5NCAzODkgLTE5NSAzOTMgMCAxMiAxNSAxIDEwNSAtNzJ6IG0xNTA3IC0xMTgx';
                $icon .= 'IGM4OAotMTc4IDE4NSAtMzg1IDE4MiAtMzg5IC0zIC00IC03OSAyNiAtMTgwIDcwIGwtNDIgMTgg';
                $icon .= 'LTkzIC00MCBjLTUyIC0yMiAtMTE0Ci00OSAtMTM4IC02MCAtMjQgLTExIC00NSAtMTggLTQ4IC0x';
                $icon .= 'NiAtNiA2IDI2MyAxODcgMjc3IDE4NyA2IDAgMjkgLTEwIDUwIC0yMgoyMSAtMTMgNDEgLTIwIDQ1';
                $icon .= 'IC0xNiA1IDQgLTEwNCAyNDQgLTEyMSAyNjYgLTEgMiAtNDEgLTExIC05MCAtMjggLTQ4IC0xNyAt';
                $icon .= 'OTIKLTI5IC05NyAtMjcgLTUgMSAxOSAyMCA1NCA0MiAzNSAyMSA4MiA1MiAxMDQgNjcgMjIgMTUg';
                $icon .= 'NDUgMjYgNTAgMjUgNiAtMiAyNwotMzYgNDcgLTc3eiIvPgo8cGF0aCBkPSJNMTAzMCAyMjI5IGMw';
                $icon .= 'IC0yIDYwIC0xMjMgMTM0IC0yNjkgODIgLTE2MSAxNDEgLTI2NyAxNTIgLTI3MiAxOTIKLTc2IDQ0';
                $icon .= 'NiAtMTk3IDUwNyAtMjQwIDE1IC0xMSAyNyAtMTcgMjcgLTE0IDAgMTUgLTE3MCAyMDcgLTI5MCAz';
                $icon .= 'MjYgLTE2NCAxNjMKLTUzMSA0ODggLTUzMCA0Njl6Ii8+CjwvZz4KPC9zdmc+Cg==';

                add_menu_page(
                    'Docket Cache',
                    'Docket Cache',
                    $cap,
                    $this->slug,
                    function () {
                        ( new View($this) )->index();
                    },
                    'data:image/svg+xml;base64,'.$icon,
                    $order
                );

                /*add_submenu_page(
                    $this->slug,
                    'Settings',
                    'Settings',
                    $cap,
                    $this->slug,
                    function () {
                        ( new View($this) )->index();
                    }
                );

                add_submenu_page(
                    $this->slug,
                    'Docket Cache',
                    'Advanced',
                    $cap,
                    $this->slug.'-advanced',
                    function () {
                        ( new View($this) )->index();
                    }
                );*/
            }
        );

        add_action(
            'admin_bar_menu',
            function ($admin_bar) {
                if (!is_multisite() || !current_user_can('manage_network_options')) {
                    return;
                }

                $admin_bar->add_menu(
                    [
                        'id' => 'network-admin-docketcache',
                        'parent' => 'network-admin',
                        'group' => null,
                        'title' => 'Docket Cache',
                        'href' => network_admin_url($this->page),
                        'meta' => [
                            'title' => 'Docket Cache',
                        ],
                    ]
                );

                if (nwdcx_network_multi()) {
                    $networks = get_networks();
                    if (!empty($networks) && \is_array($networks)) {
                        foreach ($networks as $network) {
                            $id = $network->id;
                            $url = $this->site_url_scheme('http://'.$network->domain.$network->path);
                            $admin_bar->add_menu(
                                [
                                    'id' => 'network-admin-docketcache-'.$id,
                                    'parent' => 'network-admin-'.$id,
                                    'group' => null,
                                    'title' => 'Docket Cache',
                                    'href' => $url.'/wp-admin/network/'.$this->page,
                                    'meta' => [
                                        'title' => 'Docket Cache',
                                    ],
                                ]
                            );
                        }
                    }
                }
            },
            PHP_INT_MAX
        );

        add_action(
            'all_admin_notices',
            function () {
                if (!current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
                    return;
                }

                if ($this->cx()->exists()) {
                    $url = $this->action_query('update-dropino');

                    if ($this->cx()->validate()) {
                        if ($this->cx()->is_outdated() && !$this->cx()->install(true)) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> The object-cache.php Drop-In is outdated. Please click "Re-Install" to update it now.<p style="padding:0;"><a href="%s" class="button button-primary">Re-Install</a>', 'docket-cache'), $url);
                        }
                    } else {
                        /* translators: %s: url */
                        $message = sprintf(__('<strong>Docket Cache:</strong> An unknown object-cache.php Drop-In was found. Please click "Install" to use Docket Cache.<p style="margin-bottom:0;"><a href="%s" class="button button-primary">Install</a></p>', 'docket-cache'), $url);
                    }
                }

                if (2 === $this->get_status() && $this->our_screen()) {
                    $message = esc_html__('The object-cache.php Drop-In has been disabled at runtime.', 'docket-cache');
                }

                if (isset($message)) {
                    echo '<div id="docket-notice" class="notice notice-warning">';
                    echo '<p>'.$message.'</p>';
                    echo '</div>';
                }
            }
        );

        add_action(
            'admin_enqueue_scripts',
            function ($hook) {
                $is_debug = $this->cf()->is_true('WP_DEBUG');
                $plugin_url = plugin_dir_url($this->file);
                $version = str_replace('.', '', $this->version()).'xc'.($is_debug ? date('his') : date('d'));
                wp_enqueue_script($this->slug.'-worker', $plugin_url.'includes/admin/worker.js', ['jquery'], $version, false);
                wp_localize_script(
                    $this->slug.'-worker',
                    'docket_cache_config',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'token' => wp_create_nonce('docketcache-token-nonce'),
                        'slug' => $this->slug,
                        'debug' => $is_debug ? 'true' : 'false',
                    ]
                );
                if ($hook === $this->screen || $this->our_screen()) {
                    wp_enqueue_style($this->slug.'-core', $plugin_url.'includes/admin/docket.css', null, $version);
                    wp_enqueue_script($this->slug.'-core', $plugin_url.'includes/admin/docket.js', ['jquery'], $version, true);
                }

                if ($this->cf()->is_dctrue('PAGELOADER')) {
                    wp_enqueue_style($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.css', null, $version);
                    wp_enqueue_script($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.js', ['jquery'], $version, true);
                }
            }
        );

        // refresh user_meta: before login
        add_action(
            'set_logged_in_cookie',
            function ($logged_in_cookie, $expire, $expiration, $user_id, $scheme, $token) {
                if ($this->cx()->validate()) {
                    wp_cache_delete($user_id, 'user_meta');
                }
                $this->delete_cron_siteid($user_id);
            },
            PHP_INT_MAX,
            6
        );

        // refresh user_meta: after logout
        add_action(
            'wp_logout',
            function () {
                $user = wp_get_current_user();
                if (\is_object($user) && isset($user->ID)) {
                    if ($this->cx()->validate()) {
                        wp_cache_delete($user->ID, 'user_meta');
                    }
                    $this->delete_cron_siteid($user->ID);
                }
            },
            PHP_INT_MAX
        );

        add_action(
            'wp_ajax_docket_worker',
            function () {
                if (!check_ajax_referer('docketcache-token-nonce', 'token', false) && !isset($_POST['type'])) {
                    wp_send_json_error('Invalid security token sent.');
                    exit;
                }

                $type = sanitize_text_field($_POST['type']);

                if ($this->cx()->validate()) {
                    if ('preload' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        $this->cx()->undelay();
                        do_action('docketcache/preload');
                        exit;
                    }

                    if ('fetch' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        @Crawler::fetch_home();
                        exit;
                    }

                    if ('countcachesize' === $type) {
                        do_action('docketcache/countcachesize');
                        $info = (object) $this->get_info();

                        $cache_stats = 1 === $info->status_code && !empty($info->status_text_stats) ? $info->status_text_stats : $info->status_text;
                        $opcache_stats = 1 === $info->opcache_code && !empty($info->opcache_text_stats) ? $info->opcache_text_stats : $info->opcache_text;
                        $opcache_dc_stats = $info->opcache_dc_stats;
                        $opcache_wp_stats = $info->opcache_wp_stats;

                        $response = [];
                        $response = ['success' => true];
                        $response['data'] = $this->slug.':worker: pong '.$type;

                        $stats = [];
                        $stats['obc'] = $cache_stats;
                        $stats['opc'] = $opcache_stats;
                        $stats['obcs'] = $info->status_text_stats;
                        $stats['opcs'] = $info->opcache_text_stats;
                        $stats['opcdc'] = $opcache_dc_stats;
                        $stats['opcwp'] = $opcache_wp_stats;

                        $response['cachestats'] = $stats;
                        wp_send_json($response);
                        exit;
                    }
                }

                if ('flush' === $type) {
                    $this->send_json_continue($this->slug.':worker: pong '.$type);
                    if (\function_exists('delete_expired_transients')) {
                        delete_expired_transients(true);
                    }
                    exit;
                }

                wp_send_json_error($this->slug.':worker: "'.$type.'" not available');
                exit;
            }
        );

        add_filter(
            'admin_footer_text',
            function ($text) {
                if ($this->our_screen()) {
                    $meta = $this->plugin_meta($this->file);
                    /* translators: %s: version */
                    $text = $meta['Name'].' '.sprintf(__('Version %s', 'docket-cache'), $meta['Version']);
                }

                return $text;
            },
            PHP_INT_MAX
        );

        foreach (['update_footer', 'core_update_footer'] as $fn) {
            add_filter(
                $fn,
                function ($text) {
                    if ($this->our_screen()) {
                        /* translators: %s: version */
                        $text = 'WordPress '.' '.sprintf(__('Version %s', 'docket-cache'), $GLOBALS['wp_version']);
                    }

                    return $text;
                },
                PHP_INT_MAX
            );
        }

        $filter_name = sprintf('%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', $this->hook);
        add_filter(
            $filter_name,
            function ($links) {
                array_unshift(
                    $links,
                    sprintf(
                        '<a href="%s">%s</a>',
                        network_admin_url($this->page),
                        __('Settings', 'docket-cache')
                    )
                );

                switch ($this->get_status()) {
                    case 0:
                        $text = esc_html__('Enable Object Cache', 'docket-cache');
                        $action = 'enable-occache';
                        break;
                    case 1:
                        $text = esc_html__('Disable Object Cache', 'docket-cache');
                        $action = 'disable-occache';
                        break;
                    default:
                        $text = esc_html__('Install Drop-In', 'docket-cache');
                        $action = 'update-dropino';
                }

                $links[] = sprintf('<a href="%s">%s</a>', $this->action_query($action), $text);

                return $links;
            }
        );

        add_action(
            'docketcache/save-option',
            function ($name, $value, $status = true) {
                switch ($name) {
                    case 'log':
                        if (true === $status) {
                            $this->flush_log();
                        }
                        if ('enable' === $value) {
                            @Crawler::fetch_home();
                        }
                        break;
                    case 'wpoptaload':
                        $opt = 'enable' === $value ? true : false;
                        $this->suspend_wp_options_autoload($opt);

                        add_action(
                            'shutdown',
                            function () {
                                wp_cache_delete('alloptions', 'options');
                                if (\function_exists('wp_cache_flush_group')) {
                                    wp_cache_flush_group('options');
                                    wp_cache_flush_group('docketcache-precache');
                                }
                            },
                            PHP_INT_MAX
                        );

                        break;
                    case 'cronoptmzdb':
                        $this->unregister_cronjob();
                        break;
                    case 'cronbot':
                        $action = 'enable' === $value ? true : false;
                        add_action(
                            'shutdown',
                            function () use ($action) {
                                if (!$action) {
                                    apply_filters('docketcache/cronbot-active', $action);
                                }
                            },
                            PHP_INT_MAX
                        );
                        break;
                }
            },
            -1,
            3
        );

        add_action(
            'docketcache/preload',
            function () {
                if ($this->cf()->is_dctrue('WPCLI')) {
                    return;
                }

                // warmup: see after_delay
                if ($this->cf()->is_dcfalse('PRELOAD')) {
                    add_action(
                        'shutdown',
                        function () {
                            wp_load_alloptions();
                            wp_count_comments(0);
                            wp_count_posts();
                            @Crawler::fetch_home();
                        },
                        PHP_INT_MAX
                    );

                    return;
                }

                // preload
                add_action(
                    'shutdown',
                    function () {
                        if ($this->co()->lockproc('preload', time() + 3600)) {
                            return false;
                        }

                        wp_load_alloptions();
                        wp_count_comments(0);
                        wp_count_posts();

                        @Crawler::fetch_home();

                        $preload_admin = [
                            'index.php',
                            'options-general.php',
                            'options-writing.php',
                            'options-reading.php',
                            'options-discussion.php',
                            'options-media.php',
                            'options-permalink.php',
                            'edit-comments.php',
                            'profile.php',
                            'users.php',
                            'upload.php',
                            'plugins.php',
                            'edit.php',
                            'edit-tags.php?taxonomy=category',
                            'edit-tags.php?taxonomy=post_tag',
                            'edit.php?post_type=page',
                            'post-new.php?post_type=page',
                            'themes.php',
                            'widgets.php',
                            'nav-menus.php',
                            'tools.php',
                            'import.php',
                            'export.php',
                            'site-health.php',
                            'update-core.php',
                        ];

                        $preload_network = [
                            'index.php',
                            'update-core.php',
                            'sites.php',
                            'users.php',
                            'themes.php',
                            'plugins.php',
                            'settings.php',
                        ];

                        if ($this->cf()->is_dcarray('PRELOAD_ADMIN')) {
                            $preload_admin = $this->cf()->dcvalue('PRELOAD_ADMIN');
                        }

                        if ($this->cf()->is_dcarray('PRELOAD_NETWORK')) {
                            $preload_network = $this->cf()->dcvalue('PRELOAD_NETWORK');
                        }

                        if (\is_array($preload_admin) && !empty($preload_admin)) {
                            foreach ($preload_admin as $path) {
                                $url = admin_url('/'.$path);
                                @Crawler::fetch_admin($url);
                                usleep(500000);
                            }
                        }

                        if (is_multisite() && \is_array($preload_network) && !empty($preload_network)) {
                            foreach ($preload_network as $path) {
                                $url = network_admin_url('/'.$path);
                                @Crawler::fetch_admin($url);
                                usleep(500000);
                            }
                        }

                        $this->co()->lockreset('preload');
                    },
                    PHP_INT_MAX
                );
            }
        );

        add_action(
            'docketcache/countcachesize',
            function () {
                $cache_stats = $this->co()->save_part('cachestats');
                if (!empty($cache_stats)) {
                    return;
                }

                if ($this->co()->lockproc('doing_countcachesize', time() + 60)) {
                    return;
                }

                $cache_stats = $this->cache_size($this->cache_path);
                $this->co()->save_part($cache_stats, 'cachestats');

                $this->co()->lockreset('doing_countcachesize');
            },
            PHP_INT_MAX
        );

        add_action(
            'docketcache/suspend_wp_options_autoload',
            function () {
                if ($this->co()->lockproc('doing_suspend_wp_options_autoload', time() + 3600)) {
                    return;
                }

                $this->suspend_wp_options_autoload(null);
                $this->co()->lockreset('doing_suspend_wp_options_autoload');
            },
            PHP_INT_MAX
        );

        // page action
        if (class_exists('Nawawi\\DocketCache\\ReqAction')) {
            ( new ReqAction($this) )->register();
        }
    }

    /**
     * register_tweaks.
     */
    private function register_tweaks()
    {
        $this->wearechampion();

        if (class_exists('Nawawi\\DocketCache\\Tweaks')) {
            $tweaks = new Tweaks();

            if ($this->cf()->is_dctrue('OPTWPQUERY')) {
                $tweaks->wpquery();
            }

            if ($this->cf()->is_dctrue('WOOTWEAKS')) {
                $tweaks->woocommerce();
            }

            if ($this->cf()->is_dctrue('MISC_TWEAKS')) {
                $tweaks->misc();
            }

            if ($this->cf()->is_dctrue('HEADERJUNK')) {
                $tweaks->headerjunk();
            }

            if ($this->cf()->is_dctrue('PINGBACK')) {
                $tweaks->pingback();
            }

            if ($this->cf()->is_dctrue('WPEMOJI')) {
                $tweaks->wpemoji();
            }

            if ($this->cf()->is_dctrue('WPFEED')) {
                $tweaks->wpfeed();
            }

            if ($this->cf()->is_dctrue('WPEMBED')) {
                $tweaks->wpembed();
            }

            if ($this->cf()->is_dctrue('POSTMISSEDSCHEDULE')) {
                foreach (['wp_footer', 'admin_footer'] as $hx) {
                    add_action(
                        $hx,
                        function () use ($tweaks) {
                            $tweaks->post_missed_schedule();
                        },
                        PHP_INT_MAX
                    );
                }
            }
        }

        // only if our dropin
        if ($this->cx()->validate()) {
            // wp_cache: advanced cache post
            if ($this->cf()->is_dctrue('ADVCPOST') && class_exists('Nawawi\\DocketCache\\PostCache')) {
                ( new PostCache() )->register();
            }

            // wp_cache: translation mo file cache
            if ($this->cf()->is_dctrue('MOCACHE') && class_exists('Nawawi\\DocketCache\\MoCache')) {
                add_filter(
                    'override_load_textdomain',
                    function ($plugin_override, $domain, $mofile) {
                        if (!@is_file($mofile) || !@is_readable($mofile) || !isset($GLOBALS['l10n'])) {
                            return false;
                        }

                        $l10n = $GLOBALS['l10n'];
                        $upstream = empty($l10n[$domain]) ? null : $l10n[$domain];
                        $mo = new MoCache($mofile, $domain, $upstream);
                        $l10n[$domain] = $mo;

                        $GLOBALS['l10n'] = $l10n;

                        return true;
                    },
                    PHP_INT_MAX,
                    3
                );
            }
        }

        // optimize term count
        if ($this->cf()->is_dctrue('OPTERMCOUNT') && class_exists('Nawawi\\DocketCache\\TermCount')) {
            ( new TermCount() )->register();
        }
    }

    /**
     * register_cronjob.
     */
    private function register_cronjob()
    {
        ( new Event($this) )->register();
    }

    /**
     * unregister_cronjob.
     */
    private function unregister_cronjob()
    {
        ( new Event($this) )->unregister();
    }

    private function register_cli()
    {
        if ($this->cf()->is_dctrue('WPCLI') && $this->cf()->is_false('DocketCache_CLI')) {
            \define('DocketCache_CLI', true);
            $cli = new Command($this);
            \WP_CLI::add_command('cache update', [$cli, 'update_dropino']);
            \WP_CLI::add_command('cache enable', [$cli, 'enable']);
            \WP_CLI::add_command('cache disable', [$cli, 'disable']);
            \WP_CLI::add_command('cache status', [$cli, 'status']);
            \WP_CLI::add_command('cache type', [$cli, 'type']);
            \WP_CLI::add_command('cache flush', [$cli, 'flush_cache']);
            \WP_CLI::add_command('cache gc', [$cli, 'rungc']);
            \WP_CLI::add_command('cache unlock', [$cli, 'clearlock']);
        }
    }

    /**
     * register.
     */
    public function register()
    {
        $this->register_plugin_hooks();
        $this->register_admin_hooks();
        $this->register_tweaks();
        $this->register_cronjob();
        $this->register_cli();
    }
}
