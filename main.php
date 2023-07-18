<?php
/*
Plugin Name: taraz group plgugin
Plugin URI: https://tarazgroup.com/
Description: 
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
        add_action('woocommerce_thankyou', array($this, 'send_order_data'), 10, 1);
        add_action('init', array($this, 'update_stock_data'));
        add_action('init', array($this, 'get_token'));
        // add_action('woocommerce_new_customer', 'call_post_customer_data', 10, 1);


    }

    public function admin_menu()
    {
        add_options_page(
            'Taraz Settings',
            'Taraz Settings',
            'manage_options',
            'taraz-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page()
    {
        $user = get_option('taraz_user');
        $password = get_option('taraz_password');
        $db_prefix = get_option('taraz_db_prefix');
        $voucherTypeID = get_option('taraz_voucherTypeID');
        $storeID = get_option('taraz_storeID');
        $secUnitID = get_option('taraz_secUnitID');


        // http://127.0.0.1:8080/tws/pub/vouchertypes?systemID=6
        ?>
        <div>
            <h1>تنظیمات سامانه تراز سامانه</h1>
            <ul class="tab-navigation">
                <li class="tab-link active" data-tab="tab1">اطلاعات کاربری</li>
                <li class="tab-link" data-tab="tab2">اطلاعات سند</li>
            </ul>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="tab-form">
                <input type="hidden" name="action" value="taraz_save_settings">
                <?php wp_nonce_field('taraz-settings-save', 'taraz-settings-nonce'); ?>

                <!-- First Tab: Username and Password -->
                <div class="tab-content active" id="tab1">
                    <table class="form-table">
                        <tr style="padding=20px">
                            <th scope="row"><label for="taraz_user">نام کاربری</label></th>
                            <td><input name="taraz_user" type="text" id="taraz_user" value="<?php echo esc_attr($user); ?>"
                                    class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_password">گذرواژه</label></th>
                            <td><input name="taraz_password" type="password" id="taraz_password"
                                    value="<?php echo esc_attr($password); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <?php submit_button('ذخیره اطلاعات', 'primary', 'pre-submit', false); ?>

                </div>

                <div class="tab-content" id="tab2">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="taraz_db_prefix">پسوند دیتابیس</label></th>
                            <td>
                                <input name="taraz_db_prefix" type="text" id="taraz_db_prefix" data="fuck"
                                    value="<?php echo esc_attr(!empty($db_prefix) ? $db_prefix : 'wp_'); ?>"
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_voucherTypeID">نوع سند</label></th>
                            <td>
                                <select name="taraz_voucherTypeID" id="taraz_voucherTypeID" class="regular-text">
                                    <option value="">انتخاب کنید</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_storeID">storeID</label></th>
                            <td>
                                <input name="taraz_storeID" type="text" id="taraz_storeID"
                                    value="<?php echo esc_attr($storeID); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="taraz_secUnitID">secUnitID</label></th>
                            <td>
                                <input name="taraz_secUnitID" type="text" id="taraz_secUnitID"
                                    value="<?php echo esc_attr($secUnitID); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('ذخیره اطلاعات', 'primary', 'submit', false); ?>


                </div>

            </form>

            <script>
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
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelector('#submit').addEventListener('click', function (event) {
                        event.preventDefault();
                        window.location.href = '<?php echo esc_url(admin_url('plugins.php')); ?>';
                    });
                });

                document.addEventListener('DOMContentLoaded', function () {
                    function populateVoucherTypes() {
                        const token = '<?php echo $this->get_token(); ?>';
                        const headers = new Headers();
                        headers.append('Authorization', `Bearer ${token}`);

                        fetch('http://127.0.0.1:8080/tws/pub/vouchertypes?systemID=6', {
                            method: 'GET',
                            headers: headers,
                        }).then(response => response.json())
                            .then(data => {
                                const voucherTypeIDSelect = document.getElementById('taraz_voucherTypeID');

                                voucherTypeIDSelect.innerHTML = '<option value="">انتخاب کنید</option>';

                                data.forEach(item => {
                                    const option = document.createElement('option');
                                    option.value = item.voucherTypeID;
                                    option.textContent = item.voucherTypeDesc;
                                    voucherTypeIDSelect.appendChild(option);
                                });

                                const voucherTypeIDInput = document.getElementById('taraz_voucherTypeID');
                                voucherTypeIDSelect.value = voucherTypeIDInput.value;
                            })
                            .catch(error => console.error('Error fetching data:', error));
                    }

                    populateVoucherTypes();

                    document.querySelector('#submit').addEventListener('click', function (event) {
                        event.preventDefault();
                        populateVoucherTypes();
                        window.location.href = '<?php echo esc_url(admin_url('plugins.php')); ?>';
                    });
                });

                document.addEventListener('DOMContentLoaded', function () {
                    function populateVoucherTypes() {
                        const token = '<?php echo $this->get_token(); ?>';
                        const userID = '<?php echo get_option('taraz_userID'); ?>';
                        const headers = new Headers();
                        headers.append('Authorization', `Bearer ${token}`);
                        fetch('http://127.0.0.1:8080/tws/pub/vouchertypes?systemID=6', {
                            method: 'GET',
                            headers: headers,
                        }).then(response => response.json())
                            .then(data => {
                                const voucherTypeIDSelect = document.getElementById('taraz_voucherTypeID');

                                voucherTypeIDSelect.innerHTML = '<option value="">انتخاب کنید</option>';

                                data.forEach(item => {
                                    const option = document.createElement('option');
                                    option.value = item.voucherTypeID;
                                    option.textContent = item.voucherTypeDesc;
                                    voucherTypeIDSelect.appendChild(option);
                                });

                                const voucherTypeIDInput = document.getElementById('taraz_voucherTypeID');
                                console.log(voucherTypeIDInput.value)

                                voucherTypeIDSelect.value = voucherTypeIDInput.value;
                                // console.log(voucherTypeIDSelect.value)
                            })
                            .catch(error => console.error('Error fetching data:', error));
                    }

                    populateVoucherTypes();

                    document.querySelector('#submit').addEventListener('click', function (event) {
                        event.preventDefault();
                        populateVoucherTypes();
                        window.location.href = '<?php echo esc_url(admin_url('plugins.php')); ?>';
                    });
                });
            </script>

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
        </div>

        <?php
    }


    public function save_settings()
    {
        check_admin_referer('taraz-settings-save', 'taraz-settings-nonce');
        update_option('taraz_user', sanitize_text_field($_POST['taraz_user']));
        update_option('taraz_password', sanitize_text_field($_POST['taraz_password']));
        update_option('taraz_db_prefix', sanitize_text_field($_POST['taraz_db_prefix']));
        update_option('taraz_voucherTypeID', sanitize_text_field($_POST['taraz_voucherTypeID']));
        update_option('taraz_storeID', sanitize_text_field($_POST['taraz_storeID']));
        update_option('taraz_secUnitID', sanitize_text_field($_POST['taraz_secUnitID']));
        $redirect = add_query_arg('settings-updated', 'true', wp_get_referer());
        wp_safe_redirect($redirect);
        exit;
    }

    public function get_token()
    {
        $user = get_option('taraz_user');
        $password = get_option('taraz_password');
        $url = 'http://127.0.0.1:8080/tws/authenticate';

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

            $url = 'http://127.0.0.1:8080/tws/applicationinfo/default';

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

    public function update_stock_data()
    {
        $token = $this->get_token();

        if (!$token) {
            return;
        }

        $currentDate = date('Y-m-d');
        $persianDate = $this->convertToPersianDate($currentDate);
        $url = 'http://127.0.0.1:8080/tws/sale/goods?voucherDate=' . urlencode($persianDate) . '&voucherTypeID=60001&storeID=10000001&groupID=10000007&isWithImage=false';
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
        //    echo " <script language='javascript'>
        //     console.log(" . json_encode($goods_data) . ");
        //     </script>";
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



    public function send_order_data($order_id)
    {



        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $token = $this->get_token();
        $order_items = $order->get_items();
        $customer_Id = $order->get_customer_id();
        $other = (object) array(
        );
        echo " <script language='javascript'>
        console.log($customer_Id);
        </script>";
        echo " <script language='javascript'>
        console.log(" . json_encode($customer_Id) . ");
        </script>";

        foreach ($order_items as $item_id => $item) {
            $product = $item->get_product();
            $sku = $product->get_sku();
            $quantity = $item->get_quantity();
            $fee = $item->get_total() - $item->get_subtotal();
            $price = $item->get_total();

        }
        ;

        $orders_data = [
            'header' => [
                'voucherTypeID' => 6001,
                'customerID' => 20000003,
                'storeID' => 10000001
            ],
            'other' => $other,
            'details' => [
                [
                    'goodsID' => 10000001,
                    'secUnitID' => 10000001,
                    'quantity' => 1,
                    'fee' => 0
                ]
            ],
            'promotions' => [],
            'elements' => []
        ];
        if (!$token) {
            return;

        }


        $url = 'http://127.0.0.1:8080/tws/sale/vouchers';

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $token
                ),
                'body' => json_encode($orders_data)

            )
        );

    }




    public function post_customer_data($order_id)
    {

        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $billing_address = $order->get_address('billing');
        $order_customer_Id = $order->get_customer_id();

        echo " <script language='javascript'>
        console.log($order);
        </script>";
        echo " <script language='javascript'>
        console.log(" . json_encode($order) . ");
        </script>";
        $customer_data = array(
            'perComFName' => $billing_address['first_name'],
            'perComLName' => $billing_address['last_name'],
            'userLoginName' => $billing_address['email'] . rand(),
            'organizationID' => 10000001,
            'isOrganizationOwner' => true,
            'userMobileNumber' => $billing_address['phone'],
            'priorityID' => 10000001,
            'organizationalRank' => ''
        );


        $customer_id = $this->get_customer_id($billing_address['email']);



        // if (!$customer_id) {
        $token = $this->get_token();

        if (!$token) {
            return;
        }

        $url = 'http://127.0.0.1:8080/tws/tkt/customers';

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

        // }

        if ($customer_id) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'wp_wc_customer_lookup',
                array('customer_id' => $customer_id),
                array('customer_id' => $order_customer_Id),
                array('%d'),
                array('%d')
            );
            // global $wpdb;
            // $wpdb->update(
            //     $wpdb->prefix .  'wp_wc_customer_lookup',
            //     array('customer_id' => $customer_id),
            //     array('customer_id' => $order_customer_Id),
            //     array('%d'),
            //     array('%d')
            // );
        }
    }

    private function get_customer_id($email)
    {
        $customer = get_user_by('email', $email);
        return $customer ? $customer->ID : false;


    }

}

new Taraz();