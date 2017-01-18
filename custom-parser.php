<?php
if (!defined('DB_NAME'))
    die('Error: Plugin "wp-noexternallinks" does not support standalone calls, damned hacker.');

#include base parser
if (!defined('WP_PLUGIN_DIR'))
    include_once(ABSPATH . 'wp-content/plugins/wp-noexternallinks/wp-noexternallinks-parser.php');
else
    include_once(WP_PLUGIN_DIR . '/wp-noexternallinks/wp-noexternallinks-parser.php');

class custom_parser extends wp_noexternallinks_parser
{
    function set_filters()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
      ############# ADDED HERE A CHECK FOR BOTS
      if(stripos($_SERVER['HTTP_USER_AGENT'],'AhrefsBot')!==FALSE)
      {
        $this->debug_info("Masking is disabled for bots with this useragent");
        return;
      }
      #########################
        if ($this->options['debug'])
            add_action('wp_footer', array($this, 'output_debug'), 99);

        if ($this->options['noforauth']) {
            $this->debug_info("Masking is enabled only for non logged in users");
            if (!function_exists('is_user_logged_in')) {
                $this->debug_info("'is_user_logged_in' function not found! Trying to include its file");
                $path = constant('ABSPATH') . 'wp-includes/pluggable.php';
                if (file_exists($path))
                    require_once($path);
                else
                    $this->debug_info("pluggable file not found! Not gonna include.");
            }
            if (is_user_logged_in()) {
                $this->debug_info("User is authorised, we're not doing anything");
                return;
            }
        }
        if ($this->options['fullmask']) {
            $this->debug_info("Setting fullmask filters");
            $this->fullmask_begin();
        } else {
            $this->debug_info("Setting per element filters");
            if ($this->options['mask_mine']) {
                add_filter('the_content', array($this, 'chk_post'), 99);
                add_filter('the_excerpt', array($this, 'chk_post'), 99);
            }
            if ($this->options['mask_comment']) {
                add_filter('comment_text', array($this, 'filter'), 99);
                add_filter('comment_url', array($this, 'filter'), 99);
            }
            if ($this->options['mask_rss']) {
                add_filter('the_content_feed', array($this, 'filter'), 99);
                add_filter('the_content_rss', array($this, 'filter'), 99);
            }
            if ($this->options['mask_rss_comment'])
                add_filter('comment_text_rss', array($this, 'filter'), 99);
            if ($this->options['mask_author']) {
                add_filter('get_comment_author_url_link', array($this, 'filter'), 99);
                add_filter('get_comment_author_link', array($this, 'filter'), 99);
                add_filter('get_comment_author_url', array($this, 'filter'), 99);
            }
            #add custom filter for user usage
            add_filter('wp_noexternallinks', array($this, 'filter'), 99);
        }
    }
}

?>
