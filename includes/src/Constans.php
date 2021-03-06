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

final class Constans
{
    public function __construct()
    {
        $this->register_default();
    }

    private function px($name)
    {
        return nwdcx_constfx($name);
    }

    public function is_false($name)
    {
        return !\defined($name) || !\constant($name);
    }

    public function is_true($name)
    {
        return \defined($name) && \constant($name);
    }

    public function value($name)
    {
        $value = '';
        if (\defined($name)) {
            $value = \constant($name);
        }

        return $value;
    }

    public function is_array($name)
    {
        $value = $this->value($name);

        return !empty($value) && \is_array($value);
    }

    public function is_int($name)
    {
        $value = $this->value($name);

        return !empty($value) && \is_int($value);
    }

    public function is($name, $value)
    {
        return \defined($name) && $value === \constant($name);
    }

    public function is_dctrue($name)
    {
        $key = $this->px($name);

        return $this->is_true($key);
    }

    public function is_dcfalse($name)
    {
        $key = $this->px($name);

        return $this->is_false($key);
    }

    public function is_dcarray($name, &$value = '')
    {
        $key = $this->px($name);
        $value = '';
        if ($this->is_array($key)) {
            $value = $this->value($key);

            return true;
        }

        return false;
    }

    public function is_dcint($name, &$value = '')
    {
        $key = $this->px($name);
        $value = '';
        if ($this->is_int($key)) {
            $value = $this->value($key);

            return true;
        }

        return false;
    }

    public function dcvalue($name)
    {
        $key = $this->px($name);

        return $this->value($key);
    }

    public function maybe_define($name, $value, $user_config = true)
    {
        if (!\defined($name)) {
            if ($user_config && class_exists('Nawawi\\DocketCache\\Canopt')) {
                $nv = Canopt::init()->get($name);
                if (!empty($nv) && 'default' !== $nv) {
                    switch ($nv) {
                        case 'enable':
                            $nv = true;
                            break;
                        case 'disable':
                            $nv = false;
                            break;
                    }

                    $value = $nv;
                }
            }

            return @\define($name, $value);
        }

        return false;
    }

    public function register_default()
    {
        // compat
        $this->maybe_define('WP_CONTENT_DIR', ABSPATH.'wp-content', false);
        $this->maybe_define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins', false);

        // data dir
        $this->maybe_define($this->px('DATA_PATH'), WP_CONTENT_DIR.'/docket-cache-data/', false);

        // cache dir
        $this->maybe_define($this->px('PATH'), WP_CONTENT_DIR.'/cache/docket-cache/', false);

        // cache file max size: 3MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        // Only numbers between 1000000 and 10485760 are accepted
        $this->maybe_define($this->px('MAXSIZE'), 3145728);

        // cache file max size total: 500MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        // minimum 100MB
        $this->maybe_define($this->px('MAXSIZE_DISK'), 524288000);

        // cache file max accelerated files: Only numbers between 200 and 200000 are accepted
        $this->maybe_define($this->px('MAXFILE'), 50000);

        // cache maxttl: cache lifespan.  Only seconds between 86400 and 2419200 are accepted
        $this->maybe_define($this->px('MAXTTL'), 345600);

        // log on/off
        $this->maybe_define($this->px('LOG'), false);

        // private: log on/off
        $this->maybe_define($this->px('LOG_ALL'), (\defined('WP_DEBUG') ? WP_DEBUG : false));

        // log file
        $this->maybe_define($this->px('LOG_FILE'), WP_CONTENT_DIR.'/.object-cache.log');

        // empty file when cache flushed
        $this->maybe_define($this->px('LOG_FLUSH'), true);

        // log time format: utc, local
        $this->maybe_define($this->px('LOG_TIME'), 'utc');

        // log file max size: 10MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        $this->maybe_define($this->px('LOG_SIZE'), 10485760);

        // truncate or delete cache file
        $this->maybe_define($this->px('FLUSH_DELETE'), false);

        // optimize db
        $this->maybe_define($this->px('CRONOPTMZDB'), 'never');

        // option autoload
        $this->maybe_define($this->px('WPOPTALOAD'), false);

        // global cache group
        $this->maybe_define(
            $this->px('GLOBAL_GROUPS'),
            [
                'blog-details',
                'blog-id-cache',
                'blog-lookup',
                'global-posts',
                'networks',
                'rss',
                'sites',
                'site-details',
                'site-lookup',
                'site-options',
                'site-transient',
                'users',
                'useremail',
                'userlogins',
                'usermeta',
                'user_meta',
                'userslugs',
            ]
        );

        // cache ignored groups
        $this->maybe_define(
            $this->px('IGNORED_GROUPS'),
            [
                'themes',
                'counts',
                'plugins',
            ]
        );

        // @private
        // cache ignored keys
        $this->maybe_define($this->px('IGNORED_KEYS'), []);

        // @private
        // this option private for right now
        $this->maybe_define(
            $this->px('FILTERED_GROUPS'),
            [
                'counts' => [
                    'posts-page',
                    'posts-post',
                ],
            ]
        );

        // @private
        // cache ignored group:key
        $this->maybe_define($this->px('IGNORED_GROUPKEY'), []);

        // @private
        // cache ignored precache
        $this->maybe_define(
            $this->px('IGNORED_PRECACHE'),
            [
                'freemius:fs_accounts',
                'site-transient:update_themes',
                'site-transient:update_plugins',
                'site-transient:update_core',
            ]
        );

        // misc tweaks
        $this->maybe_define($this->px('MISC_TWEAKS'), true);

        // woocommerce tweaks
        $this->maybe_define($this->px('WOOTWEAKS'), true);

        // post missed schedule
        $this->maybe_define($this->px('POSTMISSEDSCHEDULE'), false);

        // advanced post cache
        $this->maybe_define($this->px('ADVCPOST'), true);

        // optimize term count
        $this->maybe_define($this->px('OPTERMCOUNT'), true);

        // translation mo file cache
        $this->maybe_define($this->px('MOCACHE'), false);

        // @private
        // wp-cli
        $this->maybe_define($this->px('WPCLI'), (\defined('WP_CLI') && WP_CLI));

        // banner
        $this->maybe_define($this->px('SIGNATURE'), true);

        // preload
        $this->maybe_define($this->px('PRELOAD'), false);

        // precache
        $this->maybe_define($this->px('PRECACHE'), true);

        // page loader
        $this->maybe_define($this->px('PAGELOADER'), true);

        // docket cronbot
        $this->maybe_define($this->px('CRONBOT'), true);

        // docket cronbot
        $this->maybe_define($this->px('CRONBOT_MAX'), 10);

        // cache stats
        $this->maybe_define($this->px('STATS'), true);

        // check version
        $this->maybe_define($this->px('CHECKVERSION'), true);

        // auto update
        $this->maybe_define($this->px('AUTOUPDATE'), true);

        // optimize post query
        $this->maybe_define($this->px('OPTWPQUERY'), true);

        // xmlrpc pingbacks
        $this->maybe_define($this->px('PINGBACK'), true);

        // header junk
        $this->maybe_define($this->px('HEADERJUNK'), true);

        // wp emoji
        $this->maybe_define($this->px('WPEMOJI'), false);

        // wp embed
        $this->maybe_define($this->px('WPEMBED'), false);

        // wp feed
        $this->maybe_define($this->px('WPFEED'), false);
    }
}
