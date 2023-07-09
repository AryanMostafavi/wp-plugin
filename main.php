<?php
/*
Plugin Name: taraz group plgugins
Plugin URI: https://tarazgroup.com/
Description: 
Version: 1.0
Author:Aryan Mostafavi
Author URI: https://your-website.com/
License: 
*/


if (!defined('ABSPATH')) {
    exit;
}

class Taraz {
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_taraz_save_settings', array($this, 'save_settings'));
        add_action('woocommerce_thankyou', array($this, 'send_order_data'), 10, 1);
        add_action('init', array($this, 'update_stock_data'));
    }

    public function admin_menu() {
        add_options_page(
            'Taraz Settings',
            'Taraz Settings',
            'manage_options',
            'taraz-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $user = get_option('taraz_user');
        $password = get_option('taraz_password');
        ?>
        <div class="wrap">
            <h1>Taraz Settings</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="taraz_save_settings">
                <?php wp_nonce_field('taraz-settings-save', 'taraz-settings-nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="taraz_user">User</label></th>
                        <td><input name="taraz_user" type="text" id="taraz_user" value="<?php echo esc_attr($user); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="taraz_password">Password</label></th>
                        <td><input name="taraz_password" type="password" id="taraz_password" value="<?php echo esc_attr($password); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        check_admin_referer('taraz-settings-save', 'taraz-settings-nonce');
        update_option('taraz_user', sanitize_text_field($_POST['taraz_user']));
        update_option('taraz_password', sanitize_text_field($_POST['taraz_password']));

        // Redirect back to settings page
        $redirect = add_query_arg('settings-updated', 'true', wp_get_referer());
        wp_safe_redirect($redirect);
        exit;
    }

    private function get_token() {
        $user = get_option('taraz_user');
        $password = get_option('taraz_password');
        $url = 'http://127.0.0.1:8080/tws/authenticate';
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode(array('username' => $user, 'password' => $password))
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        $token = isset($body['token']) ? $body['token'] : false;
        
        if ($token) {
            // Save the token value to be used later
            update_option('taraz_token', $token);
        }
        
        return $token;
    }

    public function update_stock_data() {
        $token = $this->get_token();
    
        if (!$token) {
            return;
        }
    
        $currentDate = date('Y-m-d');
        $persianDate = $this->convertToPersianDate($currentDate);
    
        $url = 'http://127.0.0.1:8080/tws/sale/goods?voucherDate=' . urlencode($persianDate) . '&voucherTypeID=60001&groupID=10000007&isWithImage=false';
    
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $token,
            ),
        ));
    
        if (is_wp_error($response)) {
            return;
        }
    
        $goods_data = json_decode(wp_remote_retrieve_body($response), true);
    
        if (empty($goods_data)) {
            return;
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_product_meta_lookup';
    
        foreach ($goods_data as $item) {
            $product_id = (int) $item['goodsID'];
            $remain = (int) $item['remain'];
    
            $wpdb->update(
                $table_name,
                array('stock_quantity' => $remain),
                array('product_id' => $product_id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    private function convertToPersianDate($date) {
        $intlCalendar = IntlCalendar::fromDateTime($date);
        $intlCalendar->setTimeZone('Asia/Tehran');
        $persianDateFormatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL
        );
        $persianDateFormatter->setPattern('yyyy/MM/dd');
        return $persianDateFormatter->format($intlCalendar);
    }


    
    public function send_order_data($order_id) {
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $token = $this->get_token();

        if (!$token) {
            return;
        }

        $url = 'http://127.0.0.1:8080/tws/sale/sale/vouchers';

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $token
            ),
            'body' => json_encode($order_data)
        ));

        if (is_wp_error($response)) {
            // Handle error
        }
    }
}

new Taraz();