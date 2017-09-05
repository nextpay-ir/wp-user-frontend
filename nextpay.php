<?php
if ( !defined('ABSPATH') ) exit;

if ( !class_exists('WPUF_Nextpay') ) {

    class WPUF_Nextpay
    {

        private $payment_status;
        private $payment_message;


        public function __construct()
        {

            $this->payment_status = '';
            $this->payment_message = '';

            add_filter('wpuf_payment_gateways', array($this, 'WPUF_Nextpay_Setup_Sht'));
            add_action('wpuf_options_payment', array($this, 'WPUF_Nextpay_Options_Sht'));
            add_action('wpuf_gateway_Nextpay', array($this, 'WPUF_Nextpay_Request_Sht'));
            add_action('init', array($this, 'WPUF_Nextpay_Callback_Sht'));
            add_filter('gettext', array($this, 'WPUF_Nextpay_Text_Sht'));

            if ( !function_exists('WPUF_Iranian_Currencies_Sht') ) {
                add_action('wpuf_options_payment', array($this, 'WPUF_Iranian_Currencies_Sht'));
            }
        }

        public function WPUF_Iranian_Currencies_Sht( $settings )
        {

            foreach ( (array)$settings as $setting ) {

                if ( in_array('currency', $setting) ) {

                    $setting['default'] = 'IRT';
                    $iran_currencies = array(
                        'IRR' => __('ریال ایران', 'wpuf'),
                        'IRT' => __('تومان ایران', 'wpuf'),
                    );
                    $setting['options'] = array_unique(array_merge($iran_currencies, $setting['options']));
                }

                if ( in_array('currency_symbol', $setting) ) {
                    $setting['default'] = __('تومان', 'wpuf');
                }

                $settings[] = $setting;
            }

            return $settings;
        }


        public function WPUF_Nextpay_Setup_Sht( $gateways )
        {

            $gateways['Nextpay'] = array(
                'admin_label' => function_exists('wpuf_get_option') ? ( wpuf_get_option('Nextpay_name', 'wpuf_payment') ? wpuf_get_option('Nextpay_name', 'wpuf_payment') : __('نکست پی', 'wpuf') ) : '',
                'checkout_label' => function_exists('wpuf_get_option') ? ( wpuf_get_option('Nextpay_name', 'wpuf_payment') ? wpuf_get_option('Nextpay_name', 'wpuf_payment') : __('نکست پی', 'wpuf') ) : '',
                'icon' => apply_filters('wpuf_Nextpay_checkout_icon', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/logo.png')
            );

            return $gateways;
        }



        public function WPUF_Nextpay_Options_Sht( $options )
        {

            $options[] = array(
                'name' => 'Nextpay_header',
                'label' => __('پیکر بندی درگاه نکست پی', 'wpuf'),
                'type' => 'html',
                'desc' => '<hr/>'
            );

            $options[] = array(
                'name' => 'Nextpay_apikey',
                'label' => __('کلید مجوزدهی API KEY', 'wpuf')
            );

            $options[] = array(
                'name' => 'Nextpay_name',
                'label' => __('نام نمایشی درگاه', 'wpuf'),
                'default' => __('نکست پی', 'wpuf'),
            );

            $options[] = array(
                'name' => 'Nextpay_query',
                'label' => __('نام لاتین درگاه', 'wpuf'),
                'default' => 'Nextpay',
                'desc' => __('<br/>این نام در هنگام بازگشت از بانک در آدرس بازگشت از بانک نمایان خواهد شد . از به کاربردن حروف زائد و فاصله جدا خودداری نمایید .', 'wpuf')
            );

            $options[] = array(
                'name' => 'Nextpay_transaction',
                'label' => __('ثبت تراکنش ها', 'wpuf'),
                'desc' => sprintf(__('ثبت تراکنش های ناموفق علاوه بر تراکنش های موفق در %s', 'wpuf'), '<a href="' . admin_url('admin.php?page=wpuf_transaction') . '" target="_blank">لیست تراکنش ها</a>'),
                'type' => 'checkbox',
                'default' => 'on'
            );

            $options[] = array(
                'name' => 'gate_instruct_Nextpay',
                'label' => __('توضیحات درگاه نکست پی', 'wpuf'),
                'type' => 'textarea',
                'default' => __('پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه نکست پی', 'wpuf'),
                'desc' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'wpuf'),
            );

            $options[] = array(
                'name' => 'Nextpay_success',
                'label' => __('متن تراکنش پرداخت موفق', 'wpuf'),
                'type' => 'textarea',
                'desc' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید. همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (شماره ارجاع) نکست پی استفاده نمایید.', 'wpuf'),
                'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد.', 'wpuf'),
            );

            $options[] = array(
                'name' => 'Nextpay_failed',
                'label' => __('متن تراکنش پرداخت ناموفق', 'wpuf'),
                'type' => 'textarea',
                'desc' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید. همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید. این دلیل خطا از سایت نکست پی ارسال میگردد.', 'wpuf'),
                'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.', 'wpuf'),
            );

            $options[] = array(
                'name' => 'Nextpay_footer',
                'label' => '<hr/>',
                'type' => 'html',
                'desc' => '<hr/>'
            );

            return apply_filters('wpuf_Nextpay_options', $options);
        }


        public function WPUF_Nextpay_Request_Sht( $data )
        {
            ob_start();

            //item detail
            $item_number = !empty($data['item_number']) ? $data['item_number'] : 0;
            $item_name = !empty($data['custom']['post_title']) ? $data['custom']['post_title'] : $data['item_name'];

            $post_id = $data['type'] == 'post' ? $item_number : 0;
            $pack_id = $data['type'] == 'pack' ? $item_number : 0;

            $user_id = 0;
            $form_id = 0;
            if ( isset($data['post_data']) && isset($data['post_data']['post_id']) ) {
                $_post_id = $data['post_data']['post_id'];
                $post_object = get_post($_post_id);
                $user_id = $post_object->post_author;
                $form_id = get_post_meta($_post_id, '_wpuf_form_id', true);
            }

            global $current_user;
            if ( is_object($current_user) && !empty($current_user->ID) && $current_user->ID != 0 ) {
                $user_id = $current_user->ID;
            }

            $user_name = __('مهمان', 'wpuf');
            $user_email = '';
            if ( $user_id && $user_data = get_userdata($user_id) ) {
                $user_name = $user_data->user_login;
                $user_email = $user_data->user_email;
            }

            $user_id = !empty($data['user_info']['id']) ? $data['user_info']['id'] : $user_id;
            $user_email = !empty($data['user_info']['email']) ? $data['user_info']['email'] : $user_email;
            $first_name = !empty($data['user_info']['first_name']) ? $data['user_info']['first_name'] : '';
            $last_name = !empty($data['user_info']['last_name']) ? $data['user_info']['last_name'] : '';
            $full_user = $first_name . ' ' . $last_name;
            $full_user = strlen($full_user) > 5 ? $full_user : $user_name;

            $query = 'Nextpay';
            $currency = '';
            $redirect_page_id = 0;
            $subscription_page_id = 0;
            $ApiKey = $UserName = $PassWord = '';
            $set_transaction = false;
            $payment_gateway = __('نکست پی', 'wpuf');

            if ( function_exists('wpuf_get_option') ) {

                $currency = wpuf_get_option('currency', 'wpuf_payment');
                $query = wpuf_get_option('Nextpay_query', 'wpuf_payment');
                $set_transaction = wpuf_get_option('Nextpay_transaction', 'wpuf_payment');
                $payment_gateway = wpuf_get_option('Nextpay_name', 'wpuf_payment');
                $ApiKey = wpuf_get_option('Nextpay_apikey', 'wpuf_payment');
                $redirect_page_id = wpuf_get_option('payment_success', 'wpuf_payment');
                $subscription_page_id = wpuf_get_option('subscription_page', 'wpuf_payment');
            }

            if ( $redirect_page_id ) {
                $Return_url = add_query_arg('pay_method', $query, get_permalink($redirect_page_id));
            } else {
                $Return_url = add_query_arg('pay_method', $query, get_permalink($subscription_page_id));
            }

            //currency
            $currency = !empty($data['currency']) ? $data['currency'] : $currency;

            //price
            $price = empty($data['price']) ? 0 : intval(str_replace(',', '', $data['price']));

            //coupon
            $coupon_id = '';
            if ( !empty($_POST['coupon_id']) ) {
                $coupon_id = $_POST['coupon_id'];
                if ( get_post_meta($coupon_id, '_coupon_used', true) >= get_post_meta($coupon_id, '_usage_limit', true) ) {
                    wp_die(__('تعداد دفعات استفاده از کد تخفیف به اتمام رسیده است .', 'wpuf'));
                    return false;
                }
                $price = WPUF_Coupons::init()->discount($price, $_POST['coupon_id'], $data['item_number']);
            }
            $price = intval($price);


            //store
            $fg_data = array();

            if ( !empty($data['type']) )
                $fg_data = array_merge($fg_data, array('type' => $data['type']));

            if ( !empty($price) )
                $fg_data = array_merge($fg_data, array('price' => $price));

            if ( !empty($currency) )
                $fg_data = array_merge($fg_data, array('currency' => $currency));

            if ( !empty($item_number) )
                $fg_data = array_merge($fg_data, array('item_number' => $item_number));

            if ( !empty($user_id) )
                $fg_data = array_merge($fg_data, array('user_id' => $user_id));

            if ( !empty($user_email) )
                $fg_data = array_merge($fg_data, array('user_email' => $user_email));

            if ( !empty($full_user) )
                $fg_data = array_merge($fg_data, array('full_user' => $full_user));

            if ( !empty($coupon_id) )
                $fg_data = array_merge($fg_data, array('coupon_id' => $coupon_id));

            if ( !empty($first_name) )
                $fg_data = array_merge($fg_data, array('first_name' => $first_name));

            if ( !empty($last_name) )
                $fg_data = array_merge($fg_data, array('last_name' => $last_name));

            if ( !empty($form_id) )
                $fg_data = array_merge($fg_data, array('form_id' => $form_id));

            if ( !empty($_post_id) )
                $fg_data = array_merge($fg_data, array('_post_id' => $_post_id));


            $payment_data = $this->json_encode($fg_data);
            $this->WPUF_Nextpay_Data_Sht('set', $payment_data);

            if ( $set_transaction == 'on' ) {

                $data = array(
                    'user_id' => $user_id,
                    'status' => __('pending', 'wpuf'),
                    'cost' => $price,
                    'post_id' => $post_id,
                    'pack_id' => $pack_id,
                    'payer_first_name' => !empty($first_name) ? $first_name : $full_user,
                    'payer_last_name' => !empty($last_name) ? $last_name : null,
                    'payer_email' => $user_email,
                    'payment_type' => $payment_gateway . ( $price <= 0 ? __(' - رایگان', 'wpuf') : '' ),
                    'payer_address' => null,
                    'transaction_id' => $item_number,
                    'created' => current_time('mysql'),
                    'profile_id' => $user_id,
                );

                $this->insert_payment($data, $item_number, false);
            }

            do_action('WPUF_Nextpay_before_Proccessing_to_Sending', $data);

             if ( $price == 0 ) {
                WPUF_Subscription::init()->new_subscription($user_id, $item_number, $profile_id = null, false, 'free');
                wp_redirect(add_query_arg('pay', 'no', $Return_url));
                exit();
            }

            $Amount = intval($price);
            if ( strtolower($currency) == 'irr' )
                $Amount = $Amount/10 ;

            $Description = sprintf(__('پرداخت برای آیتم به شماره %s برای کاربر %s | نام آیتم : %s', 'wpuf'), $item_number, $full_user, $item_name);

            $Description = apply_filters('WPUF_Nextpay_Description', $Description, $fg_data);
            $Email = apply_filters('WPUF_Nextpay_Email', $user_email, $fg_data);
            $Mobile = apply_filters('WPUF_Nextpay_Mobile', '', $fg_data);
            do_action('WPUF_Nextpay_Gateway_Payment', $fg_data, $Description, $Email, $Mobile);
            do_action('WPUF_Gateway_Payment', $fg_data);

            try {

                $orderId = date('ymdHis');
                $additionalData = '';

                $client = new SoapClient('https://api.nextpay.org/gateway/token.wsdl', array('encoding' => 'UTF-8'));

                $localDate = date("Ymd");
                $localTime = date("His");

                $result = $client->TokenGenerator([
                    'api_key'  => $ApiKey,
                    'amount'      => $Amount,
                    'order_id' => $orderId,
                    'callback_uri' => $Return_url
                ]);

                $result = $result->TokenGeneratorResult;

                if ($result->code == -1) {
                        header("Location: https://api.nextpay.org/gateway/payment/". $result->trans_id);
                        exit();
                    } else {
                      wp_die(sprintf(__('متاسفانه پرداخت به دلیل خطای زیر امکان پذیر نمی باشد : <br/><br/><b> %s </b>', 'wpuf'), $result->code));
                        return;
                    }
                }

             catch (Exception $ex) {
                $Message = $ex->getMessage();
            }

        }

        public function WPUF_Nextpay_Callback_Sht()
        {

            $query = function_exists('wpuf_get_option') ? ( wpuf_get_option('Nextpay_query', 'wpuf_payment') ? wpuf_get_option('Nextpay_query', 'wpuf_payment') : 'Nextpay' ) : '';
            if ( !empty($_GET['pay_method']) && $_GET['pay_method'] == $query ) {

                //from store
                $Nextpay_data = $this->WPUF_Nextpay_Data_Sht('call', '');
                $payment_data = json_decode(stripcslashes($Nextpay_data));
                $new_payment = !empty($Nextpay_data) && $Nextpay_data != '' ? true : false;

                if ( isset($_GET['fault']) ) {

                    $this->payment_status = 'failed';

                    if ( function_exists('wpuf_get_option') )
                        $message = wpautop(wptexturize(wpuf_get_option('Nextpay_failed', 'wpuf_payment')));

                    $transaction_id = !empty($_POST['transaction_id']) ? $_GET['transaction_id'] : '';
                    $message = str_replace(array('{transaction_id}', '{fault}'), array($transaction_id, $this->WPUF_Nextpay_Fault($_GET['fault'])), $message);
                    $this->payment_message = $message;

                    add_filter('the_content', array($this, 'WPUF_Nextpay_Content_After_Return_Sht'));
                    return false;

                } else if ( !$new_payment ) {
                    $this->payment_status = 'failed';
                    $this->payment_message = __('وضعیت پرداخت قبلا مشخص شده است .', 'wpuf');
                    add_filter('the_content', array($this, 'WPUF_Nextpay_Content_After_Return_Sht'));
                    return false;
                }


                //price
                $price = !empty($payment_data->price) ? $payment_data->price : $this->POST('price');
                $price = !empty($price) ? intval($price) : 0;

                $currency = !empty($payment_data->currency) ? $payment_data->currency : ( function_exists('wpuf_get_option') ? wpuf_get_option('currency', 'wpuf_payment') : $this->POST('currency') );
                $currency = !empty($currency) ? $currency : '';

                $coupon_id = !empty($payment_data->coupon_id) ? $payment_data->coupon_id : $this->POST('coupon_id');
                $coupon_id = !empty($coupon_id) ? $coupon_id : false;

                $form_id = !empty($payment_data->form_id) ? $payment_data->form_id : $this->POST('form_id');
                $form_id = !empty($form_id) ? $form_id : false;

                $_post_id = !empty($payment_data->_post_id) ? $payment_data->_post_id : $this->POST('_post_id');
                $_post_id = !empty($_post_id) ? $_post_id : false;


                $user_id = !empty($payment_data->user_id) ? (int)$payment_data->user_id : '';
                $user_id = !empty($user_id) ? $user_id : (int)$this->POST('user_id');
                if ( empty($user_id) || !$user_id )
                    $user_id = 0;

                global $current_user;
                if ( !$user_id && is_object($current_user) && !empty($current_user->ID) && $current_user->ID != 0 ) {
                    $user_id = $current_user->ID;
                }

                $user_name = __('مهمان', 'wpuf');
                if ( $user_id && $user_data = get_userdata($user_id) ) {
                    $user_name = $user_data->user_login;
                }

                $user_email = !empty($payment_data->user_email) ? $payment_data->user_email : $user_email;
                $user_email = !empty($user_email) ? $user_email : $this->POST('user_email');

                $first_name = !empty($payment_data->first_name) ? $payment_data->first_name : '';
                $first_name = !empty($first_name) ? $first_name : $this->POST('first_name');

                $last_name = !empty($payment_data->last_name) ? $payment_data->last_name : '';
                $last_name = !empty($last_name) ? $last_name : $this->POST('last_name');

                $full_user = $first_name . ' ' . $last_name;
                $full_user = strlen($full_user) > 5 ? $full_user : $user_name;

                //item
                $item_number = !empty($payment_data->item_number) ? $payment_data->item_number : $this->POST('item_number');

                $pay_type = !empty($payment_data->type) ? $payment_data->type : $this->POST('type');

                if ( !empty($pay_type) ) {

                    switch ( $pay_type ) {
                        case 'post':
                            $post_id = $item_number;
                            $pack_id = 0;
                            break;
                        case 'pack':
                            $post_id = 0;
                            $pack_id = $item_number;
                            break;
                    }
                } else {
                    $post_id = 0;
                    $pack_id = 0;
                }


                $has_gateway = ( !empty($_GET['pay']) && $_GET['pay'] == 'no' ) ? 'no' : 'yes';

                if ( $has_gateway == 'yes' ) {

                    if ( function_exists('wpuf_get_option') ) {
                        $redirect_page_id = wpuf_get_option('payment_page', 'wpuf_payment');
                        $subscription_page_id = wpuf_get_option('subscription_page', 'wpuf_payment');
                    }
                    if ( $redirect_page_id ) {
                        $Failed_url = add_query_arg('pay_method', $query, get_permalink($redirect_page_id));
                    } else {
                        $Failed_url = add_query_arg('pay_method', $query, get_permalink($subscription_page_id));
                    }


                    $ApiKey = wpuf_get_option('Nextpay_apikey', 'wpuf_payment');
                    $Trans_ID = $_POST['trans_id'];
                    $Order_ID = $_POST['order_id'];

                    $Amount = intval($price);
                    if ( strtolower($currency) == 'irr' )
                        $Amount = $Amount / 10;

                    $client = new SoapClient('https://api.nextpay.org/gateway/verify.wsdl', array('encoding' => 'UTF-8'));
                    $result = $client->PaymentVerification(
                      [
                        'api_key'  => $ApiKey,
                        'amount'   => $Amount,
                        'trans_id' => $Trans_ID,
                        'order_id' => $Order_ID,
                      ]
                    );

                    $result = $result->PaymentVerificationResult;
                    if ($result->code == 0) {
                        $Status = 'completed';
                        $fault = 0;
                     }else {
                        if ( $result->code == -2 || $result->code == '-2' ) {
                            $Status = 'cancelled';
                            $fault = 22;
                        } else {
                            $Status = 'failed';
                            $fault = $result->code ;
                        }
                    }


                    $status = $Status;
                } else {
                    $Status = 'completed';
                    $fault = 0;
                }

                $data = array(
                    'user_id' => $user_id,
                    'profile_id' => $user_id,
                    'status' => $status,
                    'cost' => $price,
                    'post_id' => $post_id,
                    'pack_id' => $pack_id,
                    'payer_first_name' => !empty($first_name) ? $first_name : $full_user,
                    'payer_last_name' => !empty($last_name) ? $last_name : '',
                    'payer_email' => $user_email,
                    'payment_type' => ( function_exists('wpuf_get_option') ? ( wpuf_get_option('Nextpay_name', 'wpuf_payment') ? wpuf_get_option('Nextpay_name', 'wpuf_payment') : __('نکست پی', 'wpuf') ) : '' ) . ( $price <= 0 ? __(' - رایگان', 'wpuf') : '' ) . ( !empty($fault) && $fault ? sprintf(__(' - خطا : %s', 'wpuf'), $this->WPUF_Nextpay_Fault($fault)) : '' ),
                    'payer_address' => null,
                    'transaction_id' => $Trans_ID,
                    'created' => current_time('mysql'),
                );

                do_action('WPUF_Return_from_Gateway_' . $status, $data, $transaction_id);

                //WP_User_Frontend::log( $transaction_id, $status );
                $message = '';
                $this->payment_status = $status;

                if ( $status == 'completed' ) {

                    $this->insert_payment($data, $item_number, false);

                    do_action('wpuf_payment_received', $data, false);

                    if ( $coupon_id ) {
                        $pre_usage = get_post_meta($coupon_id, '_coupon_used', true);
                        $new_use = $pre_usage + 1;
                        update_post_meta($coupon_id, '_coupon_used', $new_use);
                    }

                    delete_user_meta($user_id, '_wpuf_user_active');
                    delete_user_meta($user_id, '_wpuf_activation_key');

                    do_action('WPUF_Nextpay_Return_from_Gateway_Success', $data, $transaction_id);
                    if ( function_exists('wpuf_get_option') ) {
                        $message = wpautop(wptexturize(wpuf_get_option('Nextpay_success', 'wpuf_payment')));
                    }
                    $message = str_replace('{transaction_id}', $transaction_id, $message);

                    if ( $form_id && $pay_type == 'post' ) {

                        $form_settings = wpuf_get_form_settings($form_id);
                        $redirect_to = isset($form_settings['redirect_to']) ? $form_settings['redirect_to'] : 'post';

                        if ( $redirect_to == 'page' ) {
                            $redirect_to = isset($form_settings['page_id']) ? get_permalink($form_settings['page_id']) : false;
                        } elseif ( $redirect_to == 'url' ) {
                            $redirect_to = isset($form_settings['url']) ? $form_settings['url'] : false;
                        } elseif ( $redirect_to == 'same' ) {
                            $redirect_to = false;

                            if ( !empty($form_settings['message']) ) {
                                $message .= $form_settings['message'];
                            }


                        } else {
                            $redirect_to = $_post_id ? get_permalink($_post_id) : false;
                        }

                        if ( $redirect_to != false && $redirect_to !== false ) {
                            wp_redirect($redirect_to);
                            exit();
                        }
                    }

                    $this->payment_message = $message;
                } else {
                    if ( function_exists('wpuf_get_option') && wpuf_get_option('Nextpay_transaction', 'wpuf_payment') == 'on' ) {
                        $this->insert_payment($data, $item_number, false);
                    }
                    do_action('WPUF_Nextpay_Return_from_Gateway_Failed', $data, $transaction_id, $fault);
                    wp_redirect(add_query_arg(array('fault' => $fault, 'transaction_id' => $transaction_id), $Failed_url));
                    exit();
                }
                add_filter('the_content', array($this, 'WPUF_Nextpay_Content_After_Return_Sht'));
            }
        }

        public function insert_payment( $data, $transaction_id = 0, $recurring = false )
        {
            global $wpdb;

            $sql = "SELECT transaction_id, status
					FROM " . $wpdb->prefix . "wpuf_transaction
					WHERE transaction_id = '" . esc_sql($transaction_id) . "' LIMIT 1";
            $result = $wpdb->get_row($sql);
            if ( $recurring != false ) {
                $profile_id = $data['profile_id'];
            }
            if ( isset($data['profile_id']) || empty($data['profile_id']) ) {
                unset($data['profile_id']);
            }

            if ( !empty($result->transaction_id) && ( empty($result->status) || $result->status == __('pending', 'wpuf') ) ) {
                $wpdb->update($wpdb->prefix . 'wpuf_transaction', $data, array('transaction_id' => $transaction_id));
            } else {
                $wpdb->insert($wpdb->prefix . 'wpuf_transaction', $data);
            }
            if ( isset($profile_id) ) {
                $data['profile_id'] = $profile_id;
            }
        }


        public function WPUF_Nextpay_Content_After_Return_Sht( $content )
        {
            return $this->payment_status == 'completed' ? ( $content . $this->payment_message ) : $this->payment_message;
        }


        public function POST( $name )
        {
            if ( isset($_POST[$name]) )
                return stripslashes_deep($_POST[$name]);
            return '';
        }


        public function WPUF_Nextpay_Text_Sht( $translated )
        {

            if ( is_admin() && get_post_type() == 'wpuf_subscription' ) {
                $translated = str_replace(
                    array('فعال کرن پرداخت دوره ای'),
                    array('فعال کرن پرداخت دوره ای (مخصوص پی پال)'),
                    $translated
                );
            }

            return $translated;
        }


        public function WPUF_Nextpay_Data_Sht( $action, $payment_data = '' )
        {

            if ( !class_exists('Sht_Store') )
                require_once( 'data.php' );
            $data = Sht_Store::get_instance();

            @session_start();
            if ( $action == 'set' ) {
                unset($data['wpuf_Nextpay']);
                unset($_SESSION['wpuf_Nextpay']);
                $data['wpuf_Nextpay'] = $_SESSION["wpuf_Nextpay"] = $payment_data;
            }

            if ( $action == 'call' ) {
                $payment_data = '';
                if ( !empty($data['wpuf_Nextpay']) ) {
                    $payment_data = $data['wpuf_Nextpay'];
                } else if ( !empty($_SESSION["wpuf_Nextpay"]) ) {
                    $payment_data = $_SESSION["wpuf_Nextpay"];
                }
                unset($data['wpuf_Nextpay']);
                unset($_SESSION['wpuf_Nextpay']);
                return $payment_data;
            }
        }


        public function json_encode( $json )
        {

            if ( defined('JSON_UNESCAPED_UNICODE') )
                return json_encode($json, JSON_UNESCAPED_UNICODE);

            $encoded = json_encode($json);
            $unescaped = preg_replace_callback('/\\\\u(\w{4})/', function ( $matches ) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
            }, $encoded);
            return $unescaped;
        }


        private function WPUF_Nextpay_Fault( $Result )
        {
            $message =  $Result;
            return $message;
        }


    }
}


add_action('plugins_loaded', 'wpuf_Nextpay_plugin');
function wpuf_Nextpay_plugin()
{
    global $wpuf_Nextpay;
    $wpuf_Nextpay = new WPUF_Nextpay();
}
