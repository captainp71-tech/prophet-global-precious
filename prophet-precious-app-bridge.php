<?php
/**
 * Plugin Name: Prophet Precious Global App Bridge
 * Description: App homepage feed and Android push notification administration bridge.
 * Version: 1.0.0
 * Author: Prophet Precious Global
 */
if (!defined('ABSPATH')) exit;

class PPG_Android_App_Bridge {
    public function __construct() {
        add_shortcode('ppg_app_home', [$this, 'render_home']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', function () {
            register_rest_route('ppg-app/v1', '/feed', [
                'methods' => 'GET', 'callback' => [$this, 'feed'], 'permission_callback' => '__return_true'
            ]);
        });
        add_action('wp_footer', [$this, 'native_login_bridge']);
    }

    public function render_home() {
        $posts = get_posts(['post_type'=>'post','post_status'=>'publish','category_name'=>'intervention','numberposts'=>6]);
        ob_start(); ?>
        <div class="ppg-app-home" style="font-family:Arial,sans-serif;color:#21152f">
          <section style="background:#51258e;color:#fff;padding:28px;border-radius:16px;margin-bottom:20px">
            <h1 style="color:#fff;margin:0 0 8px">Prophet Precious Global</h1>
            <p style="margin:0">Understanding the Hidden. Changing the Outcome.</p>
          </section>
          <h2>Latest Stories</h2><div style="display:grid;gap:14px">
          <?php foreach ($posts as $post): setup_postdata($post); ?>
            <article style="border:1px solid #eee;border-radius:12px;padding:14px">
              <?php if (has_post_thumbnail($post)) echo get_the_post_thumbnail($post, 'large', ['style'=>'width:100%;height:auto;border-radius:8px']); ?>
              <h3><a style="color:#51258e" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
              <p><?php echo esc_html(wp_trim_words(get_the_excerpt($post), 28)); ?></p>
              <a href="<?php echo esc_url(get_permalink($post)); ?>">Read story</a>
            </article>
          <?php endforeach; wp_reset_postdata(); ?>
          </div>
          <nav style="display:grid;gap:10px;margin-top:24px">
            <a href="/general-diagnosis">Request Spiritual Diagnosis</a>
            <a href="/register-to-the-prayer-altar/">Financial Breakthrough Altar</a>
            <a href="/partner-dashboard/">My Account</a>
          </nav>
        </div>
        <?php return ob_get_clean();
    }

    public function feed() {
        $posts = get_posts(['post_type'=>'post','post_status'=>'publish','category_name'=>'intervention','numberposts'=>10]);
        return rest_ensure_response(array_map(function($p) { return [
            'id'=>$p->ID, 'title'=>get_the_title($p), 'url'=>get_permalink($p),
            'excerpt'=>wp_strip_all_tags(get_the_excerpt($p)), 'image'=>get_the_post_thumbnail_url($p, 'large'),
            'date'=>get_the_date('c', $p)
        ]; }, $posts));
    }

    public function admin_menu() {
        add_menu_page('PPG Android App','PPG Android App','manage_options','ppg-android-app',[$this,'settings_page'],'dashicons-smartphone');
        add_submenu_page('ppg-android-app','Push Notifications','Push Notifications','manage_options','ppg-android-push',[$this,'push_page']);
    }
    public function register_settings() {
        register_setting('ppg_app_settings','ppg_onesignal_app_id');
        register_setting('ppg_app_settings','ppg_onesignal_rest_key');
        register_setting('ppg_app_settings','ppg_android_apk_url');
    }
    public function settings_page() { ?>
      <div class="wrap"><h1>PPG Android App Settings</h1><form method="post" action="options.php">
      <?php settings_fields('ppg_app_settings'); ?>
      <p>OneSignal App ID<br><input class="regular-text" name="ppg_onesignal_app_id" value="<?php echo esc_attr(get_option('ppg_onesignal_app_id')); ?>"></p>
      <p>OneSignal REST API Key<br><input class="regular-text" type="password" name="ppg_onesignal_rest_key" value="<?php echo esc_attr(get_option('ppg_onesignal_rest_key')); ?>"></p>
      <p>Android APK URL<br><input class="regular-text" name="ppg_android_apk_url" value="<?php echo esc_attr(get_option('ppg_android_apk_url')); ?>"></p>
      <?php submit_button(); ?></form><p>Add shortcode <code>[ppg_app_home]</code> to the WordPress page you use for the app homepage.</p></div>
    <?php }
    public function push_page() {
        if (!empty($_POST['ppg_send_push']) && check_admin_referer('ppg_send_push_action')) {
            $app = get_option('ppg_onesignal_app_id'); $key = get_option('ppg_onesignal_rest_key');
            $title = sanitize_text_field($_POST['title'] ?? ''); $message = sanitize_textarea_field($_POST['message'] ?? '');
            $url = esc_url_raw($_POST['url'] ?? '');
            if ($app && $key && $title && $message) {
                $payload = ['app_id'=>$app,'included_segments'=>['Subscribed Users'],'filters'=>[['field'=>'tag','key'=>'platform','relation'=>'=','value'=>'android']],'headings'=>['en'=>$title],'contents'=>['en'=>$message]];
                if ($url) $payload['url'] = $url;
                $response = wp_remote_post('https://api.onesignal.com/notifications', ['headers'=>['Authorization'=>'Key '.$key,'Content-Type'=>'application/json'],'body'=>wp_json_encode($payload),'timeout'=>20]);
                echo '<div class="notice notice-info"><p>'.esc_html(is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response)).'</p></div>';
            } else echo '<div class="notice notice-error"><p>Complete OneSignal settings, title and message first.</p></div>';
        } ?>
        <div class="wrap"><h1>Send Android Notification</h1><form method="post"><?php wp_nonce_field('ppg_send_push_action'); ?>
        <p>Title<br><input name="title" class="regular-text" required></p><p>Message<br><textarea name="message" class="large-text" required></textarea></p><p>Optional destination URL<br><input name="url" class="regular-text"></p>
        <p><button class="button button-primary" name="ppg_send_push" value="1">Send to Android subscribers</button></p></form></div>
    <?php }
    public function native_login_bridge() {
        if (is_user_logged_in()) { $uid = get_current_user_id(); ?>
        <script>window.addEventListener('load',function(){if(window.PPGNative && window.PPGNative.login){window.PPGNative.login(<?php echo (int)$uid; ?>);}});</script>
        <?php }
    }
}
new PPG_Android_App_Bridge();
