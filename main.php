<?php
/*
Plugin Name: TarazGroup Financial Plugin
Plugin URI: https://tarazgroup.com/
Description:Fetch Data to FarazGroup application
Version: 1.0
Author:Aryan Mostafavi
License:
*/


if (!defined('ABSPATH')) {
    exit;
}

class Taraz
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_taraz_save_settings', array($this, 'save_settings'));
        add_action('woocommerce_thankyou', array($this, 'post_customer_data'), 10, 1);
//        add_action('woocommerce_thankyou', array($this, 'send_order_data'), 10, 1);
        add_action('init', array($this, 'update_stock_data'));
        add_action('init', array($this, 'get_token'));

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'taraz_settings_link'));

    }

    public function taraz_settings_link($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=taraz-settings')) . '">تنظیمات</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function admin_menu()
    {
        add_options_page(
            'TarazPlugin Settings',
            'Taraz Settings',
            'manage_options',
            'taraz-settings',
            array($this, 'settings_page')
        );
    }


    public function settings_page()
    {
        $taraz_url = get_option('taraz_url');
        $user = get_option('taraz_user');
        $password = get_option('taraz_password');
        $db_prefix = get_option('taraz_db_prefix');
        $voucherTypeID = get_option('taraz_voucherTypeID');
        $storeID = get_option('taraz_storeID');
        $goodsGroupID = get_option('taraz_goodsGroupID');

        ?>
        <div>
            <h1>تنظیمات پلاگین تراز سامانه</h1>
            <ul class="tab-navigation">
                <li class="tab-link active" data-tab="tab1">اطلاعات سرور</li>
                <li class="tab-link" data-tab="tab2">اطلاعات کاربری</li>
                <li class="tab-link" data-tab="tab3">اطلاعات سند</li>

            </ul>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="tab-form">
                <input type="hidden" name="action" value="taraz_save_settings">
                <?php wp_nonce_field('taraz-settings-save', 'taraz-settings-nonce'); ?>


                <div class="tab-content active" id="tab1">
                    <table class="form-table">
                        <tr style="padding:20px">
                            <th scope="row"><label for="taraz_url">آدرس سرور را وارد کنید</label></th>
                            <td><input name="taraz_url" type="text" id="taraz_url"
                                       value="<?php echo esc_attr($taraz_url); ?>"
                                       class="regular-text"></td>
                        </tr>
                    </table>
                    <?php submit_button('بازگشت', 'secendery', 'submit', false); ?>
                    <?php submit_button('ذخیره اطلاعات', 'primary', 'pre-submit', false); ?>

                </div>


                <div class="tab-content " id="tab2">
                    <table class="form-table">
                        <tr style="padding:20px">
                            <th scope="row"><label for="taraz_user">نام کاربری</label></th>
                            <td><input name="taraz_user" type="text" id="taraz_user"
                                       value="<?php echo esc_attr($user); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_password">گذرواژه</label></th>
                            <td><input name="taraz_password" type="password" id="taraz_password"
                                       value="<?php echo esc_attr($password); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <?php submit_button('بازگشت', 'secendery', 'submit', false); ?>
                    <?php submit_button('ذخیره اطلاعات', 'primary', 'pre-submit', false); ?>

                </div>

                <div class="tab-content" id="tab3">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="taraz_db_prefix">پسوند دیتابیس</label></th>
                            <td>
                                <input name="taraz_db_prefix" type="text" id="taraz_db_prefix"
                                       value="<?php echo esc_attr(!empty($db_prefix) ? $db_prefix : 'wp_'); ?>"
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_voucherTypeID">نوع سند</label></th>
                            <td>
                                <select name="taraz_voucherTypeID" id="taraz_voucherTypeID" class="regular-text">
                                    <?php echo $this->get_voucher_types_options($voucherTypeID); ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_storeID">انبار</label></th>
                            <td>
                                <?php echo $this->get_stores_combo_box($storeID); ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="taraz_goodsGroupID">گروه کالا</label></th>
                            <td>
                                <select name="taraz_goodsGroupID" id="taraz_goodsGroupID" class="regular-text">
                                    <?php echo $this->get_goods_groups_options($goodsGroupID); ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('بازگشت', 'secendery', 'submit', false); ?>
                    <?php submit_button('ذخیره اطلاعات', 'primary', 'pre-submit', false); ?>

                </div>
            </form>
            <style>
                .tab-navigation {
                    list-style: none;
                    display: flex;
                    padding: 0;
                    margin: 0;
                }

                .tab-link {
                    cursor: pointer;
                    padding: 10px 20px;
                    border: 1px solid #ccc;
                    border-radius: 4px 4px 0 0;
                }

                .tab-link.active {
                    background-color: #f1f1f1;
                }

                .tab-content {
                    display: none;
                    padding: 20px;
                    border: 1px solid #ccc;
                    border-top: none;
                }

                .tab-content.active {
                    display: block;
                }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelector('#submit').addEventListener('click', function (event) {
                        event.preventDefault();
                        window.location.href = '<?php echo esc_url(admin_url('plugins.php')); ?>';
                    });
                });

                document.addEventListener('DOMContentLoaded', function () {
                    const tabLinks = document.querySelectorAll('.tab-link');
                    const tabContents = document.querySelectorAll('.tab-content');

                    tabLinks.forEach(link => {
                        link.addEventListener('click', function () {
                            const tabId = this.getAttribute('data-tab');
                            showTab(tabId);
                        });
                    });

                    function showTab(tabId) {
                        tabLinks.forEach(link => link.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));

                        const selectedTabLink = document.querySelector(`[data-tab="${tabId}"]`);
                        const selectedTabContent = document.getElementById(tabId);

                        selectedTabLink.classList.add('active');
                        selectedTabContent.classList.add('active');
                    }
                });
            </script>
        </div>
        <?php
    }

    private function get_voucher_types_options($selectedType)
    {
        $server_url = get_option('taraz_url');

        $token = $this->get_token();
        if (!$token) {
            return '<option value="">Cannot fetch voucher types</option>';
        }

        $url = $server_url . '/tws/pub/vouchertypes?systemID=6';

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => 'Bearer ' . $token,
        );

        $response = wp_remote_get($url, array('headers' => $headers));

        if (is_wp_error($response)) {
            return '<option value="">Cannot fetch voucher types</option>';
        }

        $voucher_types = json_decode(wp_remote_retrieve_body($response), true);

        $options = '<option value="">انتخاب کنید</option>';

        if (is_array($voucher_types) && !empty($voucher_types)) {
            foreach ($voucher_types as $type) {
                $option_value = esc_attr($type['voucherTypeID']);
                $option_label = esc_html($type['voucherTypeDesc']);
                $selected = $selectedType == $type['voucherTypeID'] ? 'selected' : '';
                $options .= "<option value='$option_value' $selected>$option_label</option>";
            }
        }

        return $options;
    }

    public function save_settings()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'taraz_save_settings') {
            check_admin_referer('taraz-settings-save', 'taraz-settings-nonce');

            $taraz_user = isset($_POST['taraz_user']) ? sanitize_text_field($_POST['taraz_user']) : '';
            $taraz_password = isset($_POST['taraz_password']) ? sanitize_text_field($_POST['taraz_password']) : '';
            $taraz_db_prefix = isset($_POST['taraz_db_prefix']) ? sanitize_text_field($_POST['taraz_db_prefix']) : '';
            $taraz_voucherTypeID = isset($_POST['taraz_voucherTypeID']) ? sanitize_text_field($_POST['taraz_voucherTypeID']) : '';
            $taraz_storeID = isset($_POST['taraz_storeID']) ? sanitize_text_field($_POST['taraz_storeID']) : '';
            $taraz_goodsGroupID = isset($_POST['taraz_goodsGroupID']) ? sanitize_text_field($_POST['taraz_goodsGroupID']) : '';
            $taraz_url = isset($_POST['taraz_url']) ? sanitize_text_field($_POST['taraz_url']) : '';


            update_option('taraz_user', $taraz_user);
            update_option('taraz_password', $taraz_password);
            update_option('taraz_db_prefix', $taraz_db_prefix);
            update_option('taraz_voucherTypeID', $taraz_voucherTypeID);
            update_option('taraz_storeID', $taraz_storeID);
            update_option('taraz_goodsGroupID', $taraz_goodsGroupID);
            update_option('taraz_url', $taraz_url);


            $redirect = add_query_arg('settings-updated', 'true', wp_get_referer());
            wp_safe_redirect($redirect);
            exit;
        }
    }


    public function get_token()
    {
        $server_url = get_option('taraz_url');

        $user = get_option('taraz_user');
        $password = get_option('taraz_password');
        $url = $server_url . '/tws/authenticate';

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                'body' => json_encode(array('username' => $user, 'password' => $password))
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        $token = isset($body['token']) ? $body['token'] : false;

        if ($token) {
            update_option('taraz_token', $token);
            $server_url = get_option('taraz_url');
            $url = $server_url . '/tws/applicationinfo/default';

            $response = wp_remote_get(
                $url,
                array(
                    'headers' => array(
                        'Content-Type' => 'application/json; charset=utf-8',
                        'Authorization' => 'Bearer ' . $token,
                    ),
                )
            );

            if (is_wp_error($response)) {
                return false;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $userID = isset($body['perComID']) ? $body['perComID'] : false;
            update_option('taraz_userID', $userID);

        }

        return $token;


    }

    public function get_goods_groups_options($selectedGroup)
    {

        $token = $this->get_token();
        if (!$token) {
            return '<option value="">Cannot fetch goods groups</option>';
        }
        $server_url = get_option('taraz_url');

        $url = $server_url . '/tws/inv/goodsgroups/web';

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => 'Bearer ' . $token,
        );

        $response = wp_remote_get($url, array('headers' => $headers));

        if (is_wp_error($response)) {
            return '<option value="">Cannot fetch goods groups</option>';
        }

        $goods_groups = json_decode(wp_remote_retrieve_body($response), true);

        $options = '<option value="">انتخاب کنید</option>';

        if (is_array($goods_groups) && !empty($goods_groups)) {
            foreach ($goods_groups as $group) {
                $option_value = esc_attr($group['groupID']);
                $option_label = esc_html($group['groupDesc']);
                $selected = $selectedGroup == $group['groupID'] ? 'selected' : '';
                $options .= "<option value='$option_value' $selected>$option_label</option>";
            }
        }

        return $options;
    }

    private function get_stores_combo_box($selectedStoreID)
    {

        $token = $this->get_token();
        if (!$token) {
            return '<option value="">Cannot fetch goods groups</option>';
        }

        $userID = get_option('taraz_userID');

        $server_url = get_option('taraz_url');

//        $url = 'http://127.0.0.1:8080/tws/inv/getstoreuserwebs?userID=10000000';
        $url = $server_url . '/tws/inv/getstoreuserwebs?userID=' . urlencode($userID);

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => 'Bearer ' . $token,
        );

        $response = wp_remote_get($url, array('headers' => $headers));

        if (is_wp_error($response)) {
            return '<select name="taraz_storeID" id="taraz_storeID" class="regular-text">
                        <option value="">Cannot fetch stores</option>
                    </select>';
        }

        $stores_data = json_decode(wp_remote_retrieve_body($response), true);

        $options = '<select name="taraz_storeID" id="taraz_storeID" class="regular-text">';
        $options .= '<option value="">انتخاب کنید</option>';

        if (is_array($stores_data) && !empty($stores_data)) {
            foreach ($stores_data as $store) {
                $option_value = esc_attr($store['storeID']);
                $option_label = esc_html($store['storeName']);
                $selected = $selectedStoreID == $store['storeID'] ? 'selected' : '';
                $options .= "<option value='$option_value' $selected>$option_label</option>";
            }
        }

        $options .= '</select>';

        return $options;
    }

    public function update_stock_data()
    {
        $voucherID = get_option('taraz_voucherTypeID');
        $storeID = get_option('taraz_storeID');
        $goodsGroupID = get_option('taraz_goodsGroupID');


        $token = $this->get_token();

        if (!$token) {
            return;
        }

        $currentDate = date('Y-m-d');
        $persianDate = $this->convertToPersianDate($currentDate);
//        $url = 'http://127.0.0.1:8080/tws/sale/goods?voucherDate=' . urlencode($persianDate) . '&voucherTypeID=60001&storeID=10000001&groupID=10000007&isWithImage=false';
        $server_url = get_option('taraz_url');

        $url = $server_url . '/tws/sale/goods?voucherDate=' . urlencode($persianDate) . '&voucherTypeID=' . urlencode($voucherID) . '&storeID=' . urlencode($storeID) . '&groupID=' . urlencode($goodsGroupID) . '&isWithImage=false';
        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $token,
                ),
            )
        );

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
            $product_id = (int)$item['goodsID'];
            $remain = (int)$item['remain'];

            $wpdb->update(
                $table_name,
                array('stock_quantity' => $remain),
                array('sku' => $product_id),
                array('%d'),
                array('%d')
            );
            $this->update_meta_inv_data($product_id, $remain);
        }
    }

    private function update_meta_inv_data($id, $remain)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_product_meta_lookup';

        $product_id = $wpdb->get_results("SELECT product_id FROM $table_name 
            where sku=$id", OBJECT);


        global $wpdb;
        $table_name = $wpdb->prefix . 'postmeta';

        $stock = $wpdb->get_results("UPDATE $table_name
        SET meta_value = $remain 
        WHERE post_id = " . $product_id[0]->product_id . "
        AND meta_key = '_stock'", OBJECT);

    }

    private function convertToPersianDate($date)
    {
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


    public function send_order_data($order_id, $customer_id)
    {
        $voucherID = get_option('taraz_voucherTypeID');
        $storeID = get_option('taraz_storeID');
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $token = $this->get_token();
        $order_items = $order->get_items();
        $other = (object)array();


        foreach ($order_items as $item_id => $item) {
            $product = $item->get_product();
            $sku = $product->get_sku();
            $quantity = $item->get_quantity();
            $fe = $item->get_total() - $item->get_subtotal();
            $fee = $fe / $quantity;
            $price = $item->get_total();


            $product_details = array(
                'goodsID' => $sku,
                'secUnitID' => null,
                'quantity' => $quantity,
                'fee' => $fee
            );
            $details[] = $product_details;

        }
        $orders_data = [
            'header' => [
                'voucherTypeID' => $voucherID,
                'customerID' => $customer_id,
                'storeID' => $storeID
            ],
            'other' => $other,
            'details' => $details,
            'promotions' => [],
            'elements' => []
        ];
        if (!$token) {
            return;

        }

        $server_url = get_option('taraz_url');

        $url = $server_url . '/tws/sale/vouchers';

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $token
                ),
                'body' => json_encode($orders_data)

            )

//
//        echo " <script language='javascript'>
//        console.log($quantity);
//        </script>";
//        echo " <script language='javascript'>
//        console.log(" . json_encode($quantity) . ");
//        </script>";
        );
        echo " <script language='javascript'>
        console.log($response);
        </script>";
        echo " <script language='javascript'>
        console.log(" . json_encode($response) . ");
        </script>";
    }


    public function post_customer_data($order_id)
    {

        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $billing_address = $order->get_address('billing');
        $order_customer_Id = $order->get_customer_id();


        $customer_data = array(
            'perComFName' => $billing_address['first_name'],
            'perComLName' => $billing_address['last_name'],
            'userLoginName' => $billing_address['email'],
            'organizationID' => 10000001,
            'isOrganizationOwner' => true,
            'userMobileNumber' => $billing_address['phone'],
            'priorityID' => 10000001,
            'organizationalRank' => ''
        );

        //        $customer_id = $this->get_customer_id($billing_address['email']);

        $customer_email = $customer_data['userLoginName'];
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_customer_lookup';
        $customer_id = $wpdb->get_var($wpdb->prepare("
        SELECT customer_id 
        FROM $table_name 
        WHERE email = %s
    ", $customer_email));

        if ($customer_id <= 10000000) {
            $token = $this->get_token();

            if (!$token) {
                return;
            }
            $server_url = get_option('taraz_url');

            $url = $server_url . '/tws/tkt/customers';

            $response = wp_remote_post(
                $url,
                array(
                    'headers' => array(
                        'Content-Type' => 'application/json; charset=utf-8',
                        'Authorization' => 'Bearer ' . $token
                    ),
                    'body' => json_encode($customer_data)
                )
            );


            if (is_wp_error($response)) {
                return;
            }


            $body = json_decode(wp_remote_retrieve_body($response), true);

            $customer_id = isset($body['customerID']) ? $body['customerID'] : false;


            if ($customer_id) {
                global $wpdb;
                $db_prefix = get_option('taraz_db_prefix');

                $wpdb->update(
                    $wpdb->prefix . $db_prefix . 'wc_customer_lookup',
                    array('customer_id' => $customer_id),
                    array('customer_id' => $order_customer_Id),
                    array('%d'),
                    array('%d')
                );
            }
        }

        if ($customer_id) {
            $this->send_order_data($order_id, $customer_id);
        }
    }

    private function get_customer_id($email)
    {
        $customer = get_user_by('email', $email);
        return $customer ? $customer->ID : false;
    }

}

new Taraz();