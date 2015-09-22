<?php if ( ! defined('BASEPATH') ) exit('No direct script access allowed');

class Init extends CI_Controller {
         
    protected $_general_list;
    protected $_constructor_list;

    /**
     *  *************************************************************************
     *  __construct
     *  *************************************************************************
     */
     
    public function __construct() {
        parent::__construct();

        $this->template->set(get_info('site_path'));
        $this->lang->load('init', $this->config->item('language'));

        $this->_general_list = array(
            'url' => 'home',
            'name' => 'Главная',
            'submenu' => array(
                array(
                    'url' => 'tasks',
                    'name' => 'Задачи',
                    'icon' => 'calendar'
                ),
                array(
                    'url' => 'clients/' . value($this->session->userdata('client_status'), 2),
                    'name' => $this->lang->line('clients'),
                    'icon' => 'users'
                ),
                array(
                    'url' => 'constructor',
                    'name' => $this->lang->line('constructor'),
                    'icon' => 'book'
                ),
                array(
                    'url' => 'suppliers',
                    'name' => $this->lang->line('suppliers'),
                    'icon' => 'user-md'
                ),
            )
        );
        
        $this->_constructor_list = array(
            array(
                'url' => 'products',
                'name' => 'Продукты',
                'icon' => 'shopping-cart'
            ),
            array(
                'url' => 'dishes',
                'name' => 'Блюда',
                'icon' => 'cutlery'
            ),
            array(
                'url' => 'exercises',
                'name' => 'Упражнения',
                'icon' => 'male'
            ),
            array(
                'url' => 'komplexes',
                'name' => 'Комплексы',
                'icon' => 'heartbeat'
            ),
            array(
                'url' => 'preparats',
                'name' => 'Препараты',
                'icon' => 'flask'
            ),
        );

        $urlArray = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', $urlArray);
        $numSegments = count($segments); 
        $currentSegment = $segments[$numSegments - 1];
        
        if ($currentSegment == 'login') {
            //include_scripts(array('messages'));
        } else {
            //include_styles(array('template'));
        }
        $notifies = $this->session->userdata('notifies');
        if (isset($notifies)) {
            //include_scripts(array('messages'));
        }
        
        $denied_pages = array('login', 'logout', 'login_action', 'forgot_password', 'forgot_password_action', 'load_styles.php', 'load_scripts.php');

        if (! in_array($currentSegment, $denied_pages)) {
            $token = $this->session->userdata('token');

            if (! isset($token) || empty($token)) {
                $this->logout();
            }
            
            $token = $this->session->userdata('token');
            $post_data = array(
                't' => $token,
            );
            $session_check = server_data($this->config->item('api_url') . 'session/check.php', $post_data);

            $data['privileges'] = $session_check['response']['expert_info']['privileges'];
            $this->template->set_data('privileges', $data);
            
            if (! isset($session_check['result']['success']) || $session_check['result']['success'] != true) {
                $this->session->set_userdata('errors', $session_check['result']['errors']);
                $this->logout();
            }
            
            $data['expert_id'] = $this->session->userdata('id');
            $data['expert_name'] = $this->session->userdata('name');
            $data['new_notifies_count'] = $this->session->userdata('new_notifies_count');
            $this->template->set_data('expert_bar', $data);
            
            $notifies = $this->session->userdata('notifies');
            if (isset($notifies)) {
                $data_messages['notifies'] = $notifies;
            }
            $this->template->set_data('redirect_messages', $data_messages);
            $this->session->unset_userdata('notifies');
        }
    }

    

    

    


    function planner_event_delete_clear_action() {
        if (isset($_POST['submit_event_clear'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
                'events_range' => '[' . $_POST['event_id'] . ']',
            );
            $events_range_clear = server_data($this->config->item('api_url') . 'planner/events_range_clear.php', $post_data);
            $notifies['success'] = $events_range_clear['result']['notifies'];

            if (is_not($events_range_clear['result']['errors'])) {
                if ($events_range_clear['result']['success'] == true) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        }
        if (isset($_POST['submit_event_delete'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
                'events_range' => '[' . $_POST['event_id'] . ']',
            );
            $events_range_del = server_data($this->config->item('api_url') . 'planner/events_range_del.php', $post_data);
            $notifies['success'] = $events_range_del['result']['notifies'];

            if (is_not($events_range_del['result']['errors'])) {
                if ($events_range_del['result']['success'] == true) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        }
    }
    
    /**
     *  *************************************************************************
     *  planner_export
     *  *************************************************************************
     */
    
    function planner_week_export() {
        if ($this->input->is_ajax_request()) {
            $data_privileges = $this->template->get_data('privileges');
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
            );
            $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
            $notifies[] = $clients_details['result'];
            $data['client_info'] = $clients_details['response']['client_info'];
    
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
                'date_start' => week_range($_POST['date_export_start'], 'start_day'),
                'date_end' => week_range($_POST['date_export_end'], 'end_day'),
            );
    
            $export = server_data($this->config->item('api_url') . 'planner/export2.php', $post_data);
            $data['client_events'] = $export['response']['data'];
            $notifies['success'] = $export['result']['notifies'];

            $this->template->set_data('planner_week_export', $data);
            $this->template->load_file('clients/planner/planner_week_export');
            
            if (is_not($export['result']['errors'])) {
                if ($export['result']['success'] == true) {
                    $file = $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name'] . ' ' . week_range($_POST['date_export_start'], 'start_day') . ' - ' . week_range($_POST['date_export_end'], 'end_day');
                    echo json_encode(get_info('site_dir') . '/tmp/' . transliteration($file, FALSE) . '.docx');
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function planner_missed_events_export() {
        if ($this->input->is_ajax_request()) {
            $data_privileges = $this->template->get_data('privileges');
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
            );
            $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
            $notifies[] = $clients_details['result'];
            $data['client_info'] = $clients_details['response']['client_info'];
    
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
                'date_start' => week_range($_POST['date_export_start'], 'start_day'),
                'date_end' => week_range($_POST['date_export_end'], 'end_day'),
            );
    
            $export = server_data($this->config->item('api_url') . 'planner/missed_events.php', $post_data);
            $data['client_events'] = $export['response']['data'];
            $notifies['success'] = $export['result']['notifies'];

            $this->template->set_data('planner_missed_events_export', $data);
            $this->template->load_file('clients/planner/planner_missed_events_export');
            
            $get_cookie_date = get_cookie('date');
            if (! empty($get_cookie_date)) {
                $event_date = get_cookie('date');
            } else {
                $event_date = date('d.m.Y');
            }

            if (is_not($export['result']['errors'])) {
                if ($export['result']['success'] == true) {
                    $file = 'Missed Events - ' . $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name'] . ' ' . week_range($_POST['date_export_start'], 'start_day') . ' - ' . week_range($_POST['date_export_end'], 'end_day');
                    echo json_encode(get_info('site_dir') . '/tmp/' . transliteration($file, FALSE) . '.docx');
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    
    /**
     *  *************************************************************************
     *  planner_add
     *  *************************************************************************
     */
    function planner_add($id) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data_messages[] = $clients_details['result'];
        $data['client_info'] = $clients_details['response']['client_info'];
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $event_names_list = server_data($this->config->item('api_url') . 'planner/event_names_list.php', $post_data);
        $data_messages[] = $event_names_list['result'];
        $data['event_names_list'] = $event_names_list['response']['data'];
        
        if (isset($clients_details['response']['client_info']['id'])) {
            if (in_array('client_planner_view', $data_privileges['privileges'])) {
                $name = 'Добавить событие';
                        
                set_title($name, '|', true);
                set_pagename($name, 'bar-chart-o');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'url' => 'planner/' . $clients_details['response']['client_info']['id'],
                            'name' => 'Планировщик'
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );
                $this->template->set_data('notifies', $data_messages);
                $this->template->set_data('planner_add', $data);
                $this->template->load_file('clients/planner/planner_add');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404(); 
        }
    }
    
    function planner_add_action() {
        if (isset($_POST['submit'])) {
            $token = $this->session->userdata('token');
    
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
            );
            $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
            $data_messages[] = $clients_details['result'];
            $data['client_info'] = $clients_details['response']['client_info'];
    
            $post_data = array(
                't' => $token,
                'client_id' => $_POST['id'],
                'datetime' => $_POST['datetime'],
                'event_class' => $_POST['event_class'],
                'event_name_id' => $_POST['event_names_id'],
                'event_items_list' => json_encode($_POST['event_items_list']),
            );
            $event_add = server_data($this->config->item('api_url') . 'planner/events_add2.php', $post_data);
            $data_messages[] = $event_add['result'];
            $this->template->set_data('client_event_add', $data);
    
            $selected_date = week_range(date('d.m.Y', strtotime($_POST['datetime'])), 'start_day');
            $get_cookie_date = get_cookie('date');
            if (isset($get_cookie_date)) {
                $get_cookie_date = array('name' => 'date', 'value' => '', 'expire' => '0');
                delete_cookie($get_cookie_date);
                
                $get_cookie_date_new = array('name' => 'date', 'value' => $selected_date, 'expire' => 86400);
                set_cookie($get_cookie_date_new);
            } else {
                $get_cookie_date = array('name' => 'date', 'value' => $selected_date, 'expire' => 86400);
                set_cookie($get_cookie_date);
            }
    
            if (! isset($event_add['result']['errors']) || empty($event_add['result']['errors'])) {
                if ($event_add['result']['success'] == 1) {
                    $this->session->set_userdata('notifies', $event_add['result']['notifies']);
                }
                
                echo json_encode(get_info('site_url') . '/planner/' . $_POST['id']);
            } else {
                $this->template->set_data('notifies', $data_messages);
                $this->template->load_file('notifies');
            }
        } else {
            $this->error_404();
        }
    }
    
    
    /**
     *  *************************************************************************
     *  combolist_loader
     *  *************************************************************************
     */
    function combolist_loader() {
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'item_types' => '[' . $_POST['item_types'] . ']',
            'filter_string' => $_POST['filter_string'],
            'filter' => $_POST['filter'],
            'order' => $_POST['order'],
        );
        
        if (! isset($_POST['without_filters'])) {
            $session = array(
                'filter' => $_POST['filter'], 
                'order' => $_POST['order']
            );
            $this->session->set_userdata($session, 86400);
        }
        
        if (isset($_POST['filter_string'])) {
            $get_cookie_search_query = array('name' => 'search_query_' . $_POST['section'], 'value' => $_POST['filter_string'], 'expire' => 7200);
            $this->input->set_cookie($get_cookie_search_query);
        }

        $combolist_event_items = server_data($this->config->item('api_url') . 'constructor/combolist_event_items.php', $post_data);
        $data_messages[] = $combolist_event_items['result'];
        $data['combolist_event_items'] = $combolist_event_items['response']['data'];
        $data['search'] = $this->session->userdata('search');
        
        if (! isset($combolist_event_items['result']['errors']) || empty($combolist_event_items['result']['errors'])) {
            $this->template->set_data('combolist_loader', $data);
            $this->template->load_file('combolist_loader');
        } else {
            $this->template->set_data('notifies', $data_messages);
            $this->template->load_file('notifies');
        }
    }
    
    /**
     *  *************************************************************************
     *  combolist_favorite
     *  *************************************************************************
     */
    function combolist_favorite() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'item_type' => $this->input->post('item_type'),
                'item_id' => $this->input->post('item_id'),
            );
            $reverse_favorite_item = server_data($this->config->item('api_url') . 'constructor/reverse_favorite_item.php', $post_data);
            $data['reverse_favorite_item'] = $reverse_favorite_item['response']['data'];
            $notifies[] = $reverse_favorite_item['result'];
            $notifies['success'] = $reverse_favorite_item['result']['notifies'];
               
            if (is_not($reverse_favorite_item['result']['errors'])) {
                if ($reverse_favorite_item['result']['success'] == TRUE) {
                    $this->template->set_data('combolist_loader', $data);
                    $this->template->set_data('notifies', $notifies);

                    ob_start();
                    $this->template->load_file('notifies');
                    $output = ob_get_clean();
                    
                    $response = array(
                        'data' => TRUE,
                        'html' => $output
                    );

                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function combolist_excluded() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('client_id'),
                'item_id' => $this->input->post('item_id'),
            );
            $reverse_excluded_product = server_data($this->config->item('api_url') . 'clients/reverse_excluded_product.php', $post_data);
            $data['reverse_favorite_item'] = $reverse_excluded_product['response']['data'];
            $notifies[] = $reverse_excluded_product['result'];
            $notifies['success'] = $reverse_excluded_product['result']['notifies'];

            if (is_not($reverse_excluded_product['result']['errors'])) {
                if ($reverse_excluded_product['result']['success'] == TRUE) {
                    $this->template->set_data('combolist_loader', $data);
                    $this->template->set_data('notifies', $notifies);

                    ob_start();
                    $this->template->load_file('notifies');
                    $output = ob_get_clean();
                    
                    $response = array(
                        'data' => TRUE,
                        'html' => $output
                    );

                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    
    /**
     *  *************************************************************************
     *  planner_edit
     *  *************************************************************************
     */
    function planner_edit($id, $event_id) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data_messages[] = $clients_details['result'];
        $data['client_info'] = $clients_details['response']['client_info'];
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
            'event_id' => $event_id,
        );
        $event_details = server_data($this->config->item('api_url') . 'planner/event_details2.php', $post_data);
        $data_messages[] = $event_details['result'];
        $data['event_details'] = $event_details['response']['data'];

        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $event_names_list = server_data($this->config->item('api_url') . 'planner/event_names_list.php', $post_data);
        $data_messages[] = $event_names_list['result'];
        $data['event_names_list'] = $event_names_list['response']['data'];
        
        if (isset($clients_details['response']['client_info']['id'])) {
            if (in_array('client_planner_view', $data_privileges['privileges'])) {
                $name = 'Редактировать событие';
                        
                set_title($name, '|', true);
                set_pagename($name, 'bar-chart-o');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'url' => 'planner/' . $clients_details['response']['client_info']['id'],
                            'name' => 'Планировщик'
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );
                $this->template->set_data('notifies', $data_messages);
                $this->template->set_data('planner_edit', $data);
                $this->template->load_file('clients/planner/planner_edit');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404();
        }
    }

    function planner_edit_action() {
        $token = $this->session->userdata('token');

        $post_data = array(
            't' => $token,
            'client_id' => $_POST['id'],
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data_messages[] = $clients_details['result'];
        $data['client_info'] = $clients_details['response']['client_info'];

        $post_data = array(
            't' => $token,
            'client_id' => $_POST['id'],
            'event_id' => $_POST['event_id'],
            'datetime' => $_POST['datetime'],
            'event_class' => $_POST['event_class'],
            'event_name_id' => $_POST['event_names_id'],
            'event_items_list' => json_encode($_POST['event_items_list']),
        );
        $events_edit = server_data($this->config->item('api_url') . 'planner/event_edit2.php', $post_data);
        $data_messages[] = $events_edit['result'];
        $this->template->set_data('client_event_edit', $data);

        if (! isset($events_edit['result']['errors']) || empty($events_edit['result']['errors'])) {
            if ($events_edit['result']['success'] == 1) {
                $this->session->set_userdata('notifies', $events_edit['result']['notifies']);
            }
            
            echo json_encode(get_info('site_url') . '/planner/' . $_POST['id']);
        } else {
            $this->template->set_data('notifies', $data_messages);
            $this->template->load_file('notifies');
        }
    }
    

    
    







    



    
    

    

    
    
    


    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
//////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     *  *************************************************************************
     *  errors
     *  *************************************************************************
     */
    function error_403() {
        set_title($this->lang->line('error_403'), '|', TRUE);

        $this->output->set_status_header('403'); 
        $this->template->load_file('status_codes');
    }
    
    function error_404() {
        set_title($this->lang->line('error_404'), '|', TRUE);

        $this->output->set_status_header('404'); 
        $this->template->load_file('status_codes');
    }


    /**
     *  *************************************************************************
     *  login
     *  *************************************************************************
     */
    function login() {
        set_title($this->lang->line('login_title'), '|', TRUE);

        $token = $this->session->userdata('token');

        if (is($token)) {
            $this->logout();
        }

        $this->template->load_file('login');
    }

    function login_action() {
        if (isset($_POST['login_submit'])) {
            $this->load->library('user_agent');
            
            $post_data = array(
                'login' => $this->input->post('login'),
                'pass' => MD5($this->input->post('password')),
                'expert_user_agent' => $this->input->user_agent(),
                'expert_os' => $this->agent->platform(),
                't' => 'free'
            );
            $login = server_data($this->config->item('api_url') . 'experts/login.php', $post_data);
            $notifies[] = $login['result'];

            if (is_not($login['result']['errors'])) {
                if ($login['result']['success'] == true) {
                    $this->session->set_flashdata('success', $login['result']['notifies']);
                }
    
                $session = array(
                    'id' => $login['response']['expert_info']['id'], 
                    'name' => $login['response']['expert_info']['name'],
                    'token' => $login['response']['expert_info']['token'],
                );
                $this->session->set_userdata($session, 86400);

                $response = array(
                    'redirect' => get_info('site_url')
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function logout() {
        $token = $this->session->userdata('token');

        $post_data = array(
            't' => $token
        );
        
        server_data($this->config->item('api_url') . 'experts/logout.php', $post_data);
        $this->session->sess_destroy();
        
        redirect(get_info('site_url') . '/login', 'refresh');
    }

    function forgot_password() {
        set_title('Восстановление пароля', '|', TRUE);

        $token = $this->session->userdata('token');
        
        $this->template->load_file('forgot_password');
    }

    function forgot_password_action() {
        if (isset($_POST['forgot_password_submit'])) {
            $post_data = array(
                'login' => $this->input->post('login'),
                'phone' => $this->input->post('phone'),
                't' => 'free'
            );
            $forgot_password = server_data($this->config->item('api_url') . 'experts/forgot_password.php', $post_data);
            $notifies[] = $forgot_password['result'];

            if (is_not($forgot_password['result']['errors'])) {
                if ($forgot_password['result']['success'] == true) {
                    $this->session->set_flashdata('success', $forgot_password['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/login'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function profile_modals() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token
            );
            
            $experts_details = server_data($this->config->item('api_url') . 'experts/details.php', $post_data);
            $notifies[] = $experts_details['result'];
            $data['profile'] = $experts_details['response']['data'];

            if (is_not($experts_details['result']['errors'])) {
                $this->template->set_data('profile_modals', $data);
                $this->template->load_file('profile_modals');
            } else {
                $this->template->set_data('notifies', $notifies);
                $this->template->load_file('notifies');
            }
        } else {
            $this->error_404();
        }
    }
    
    function profile_edit_action() {
        if (isset($_POST['profile_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'surname' => $this->input->post('surname'),
                'name' => $this->input->post('name'),
                'patronymic' => $this->input->post('patronymic'),
                'phone' =>$this->input->post('phone'),
                'email' => $this->input->post('email'),
                'skype' => $this->input->post('skype'),
                'sex_id' => $this->input->post('sex_id'),
            );
            $experts_edit = server_data($this->config->item('api_url') . 'experts/edit.php', $post_data);
            $notifies[] = $experts_edit['result'];

            if (is_not($experts_edit['result']['errors'])) {
                if ($experts_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $experts_edit['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => $this->input->post('redirect')
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function change_password_action() {
        if (isset($_POST['change_password_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'old_pass' => $this->input->post('old_pass'),
                'new_pass' => $this->input->post('new_pass'),
                'new_pass2' => $this->input->post('new_pass2'),
            );
            $experts_change_password = server_data($this->config->item('api_url') . 'experts/change_password.php', $post_data);
            $notifies[] = $experts_change_password['result'];

            if (is_not($experts_change_password['result']['errors'])) {
                if ($experts_change_password['result']['success'] == true) {
                    $this->session->set_flashdata('success', $experts_change_password['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ?  'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    

    
    
    /**
     *  *************************************************************************
     *  index
     *  *************************************************************************
     */
    function index() {
        set_title($this->lang->line('personal_cabinet_title'), '|', true);
        set_pagename($this->lang->line('home'), 'home');
        
        $this->template->load();
    }



























    /**
     *  *************************************************************************
     *  clients
     *  *************************************************************************
     */
    function clients($id = FALSE) {
        $privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'status_id' => $id
        );
        $clients_list = server_data($this->config->item('api_url') . 'clients/list.php', $post_data);
        $data['clients_list'] = $clients_list['response']['data'];
        $notifies[] = $clients_list['result'];
        
        $statuses_list = server_data($this->config->item('api_url') . 'clients/statuses_list.php', $post_data);
        $data['statuses_list'] = $statuses_list['response']['data'];
        $data['statuses_id'] = $id;
        $notifies[] = $statuses_list['result'];

        if (isset($_POST['change_status'])) {
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('client_id'),
                'old_client_status' => $this->input->post('client_old_status'),
                'new_client_status' => $this->input->post('client_status')
            );
            $change_status = server_data($this->config->item('api_url') . 'clients/change_status.php', $post_data);
            $notifies[] = $change_status['result'];
            
            $this->session->set_userdata('client_status_id',$id, 86400);

            if (is_not($change_status['result']['errors'])) {
                if ($change_status['result']['success'] == true) {
                    $this->session->set_flashdata('success', $change_status['result']['notifies']);
                }
                redirect(get_info('site_url') . '/clients/' . $id, 'refresh');
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }
            }
        }

        if (array_key_exists($id, $statuses_list['response']['data']['clients_statuses'])) {
            if (in_array('clients_view', $privileges['privileges'])) {
                $name = $this->lang->line('clients');
                set_title($name, '|', true);
                set_pagename($name, 'users');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'name' => $name,
                        )
                    )
                );

                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }
                
                $this->template->set_data('clients', $data);
                $this->template->load_file('clients/clients');
                
                $this->session->set_userdata('client_status', $id, 86400);
            } else {
                $this->error_403(); 
            }
        } else {
            $this->error_404(); 
        }
    }
    
    function client_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'view') {            
                $token = $this->session->userdata('token');
                $data_privileges = $this->template->get_data('privileges');
                
                if ($this->input->post('action') == 'add') {                
                    $post_data = array(
                        't' => $token,
                    );
                    $suppliers_list = server_data($this->config->item('api_url') . 'clients/suppliers_list.php', $post_data);
                    $data['suppliers_list'] = $suppliers_list['response']['data'];
                    $notifies[] = $suppliers_list['result'];
                }
                
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'client_id' => $this->input->post('id'),
                        'mode' => 1
                    );
                    $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
                    $data['client_info'] = $clients_details['response']['client_info'];
                    $notifies[] = $clients_details['result'];  
            
                    $suppliers_list = server_data($this->config->item('api_url') . 'clients/suppliers_list.php', $post_data);
                    $data['suppliers_list'] = $suppliers_list['response']['data'];
                    $notifies[] = $suppliers_list['result'];
                    
                    $statuses_list = server_data($this->config->item('api_url') . 'clients/statuses_list.php', $post_data);
                    $data['statuses_list'] = $statuses_list['response']['data'];
                    $notifies[] = $statuses_list['result'];
                }
                
                if ($this->input->post('action') == 'view') {
                    $post_data = array(
                        't' => $token,
                        'client_id' => $this->input->post('id'),
                        'mode' => 1
                    );
                    $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
                    $data['client_info'] = $clients_details['response']['client_info'];
                    $notifies[] = $clients_details['result'];  
            
                    $clients_credentials = server_data($this->config->item('api_url') . 'clients/credentials.php', $post_data);
                    $data['credentials'] = $clients_credentials['response']['data'];
                    $notifies[] = $clients_credentials['result']; 
                    
                    $suppliers_list = server_data($this->config->item('api_url') . 'clients/suppliers_list.php', $post_data);
                    $data['suppliers_list'] = $suppliers_list['response']['data'];
                    $notifies[] = $suppliers_list['result'];
                }         
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('client_modals', $data);
                $this->template->load_file('clients/client/client_modals');
            }                
        } else {
            $this->error_404(); 
        }
    }
    
    function client_add_action() {
        if (isset($_POST['client_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'surname' => $this->input->post('surname'),
                'name' => $this->input->post('name'),
                'patronymic' => $this->input->post('patronymic'),
                'phone' => $this->input->post('phone'),
                'phone_model' => $this->input->post('phone_model'),
                'email' => $this->input->post('email'),
                'sex_id' => $this->input->post('sex_id'),
                'supplier_id' => $this->input->post('supplier_id'),
                'waist_size' => $this->input->post('waist_size'),
                'chest_size' => $this->input->post('chest_size'),
                'hips_size' => $this->input->post('hips_size'),
                'hand_size' => $this->input->post('hand_size'),
                'leg_size' => $this->input->post('leg_size'),
                'eating_habits' => $this->input->post('eating_habits'),
                'cooking_equipment' => $this->input->post('cooking_equipment'),
                'growth_size' => $this->input->post('growth_size'),
                'weight_size' => $this->input->post('weight_size'),
                'born_year' => $this->input->post('born_year'),
                'lifestyle' => $this->input->post('lifestyle'),
                'physical_activity' => $this->input->post('physical_activity'),
                'diseases' => $this->input->post('diseases'),
                'diseases_family' => $this->input->post('diseases_family'),
                'current_policy' => $this->input->post('current_policy'),
                'future_results' => $this->input->post('future_results'),
                'skype' => $this->input->post('skype'),
                'comment' => $this->input->post('comment'),
                'body_fat' => $this->input->post('body_fat'),
                'body_water' => $this->input->post('body_water'),
                'visceral_fat' => $this->input->post('visceral_fat'),
                'metabolic_rate' => $this->input->post('metabolic_rate'),
                'biological_age' => $this->input->post('biological_age'),
                'muscle_mass' => $this->input->post('muscle_mass'),
                'physically_rating' => $this->input->post('physically_rating'),
                'bone_mass' => $this->input->post('bone_mass')
            );
            $clients_add = server_data($this->config->item('api_url') . 'clients/add.php', $post_data);
            $notifies[] = $clients_add['result'];

            if (is_not($clients_add['result']['errors'])) {
                if ($clients_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $clients_add['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/clients/1'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_edit_action() {
        if (isset($_POST['client_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'surname' => $this->input->post('surname'),
                'name' => $this->input->post('name'),
                'patronymic' => $this->input->post('patronymic'),
                'phone' => $this->input->post('phone'),
                'phone_model' => $this->input->post('phone_model'),
                'email' => $this->input->post('email'),
                'sex_id' => $this->input->post('sex_id'),
                'supplier_id' => $this->input->post('supplier_id'),
                'waist_size' => $this->input->post('waist_size'),
                'chest_size' => $this->input->post('chest_size'),
                'hips_size' => $this->input->post('hips_size'),
                'hand_size' => $this->input->post('hand_size'),
                'leg_size' => $this->input->post('leg_size'),
                'eating_habits' => $this->input->post('eating_habits'),
                'cooking_equipment' => $this->input->post('cooking_equipment'),
                'growth_size' => $this->input->post('growth_size'),
                'weight_size' => $this->input->post('weight_size'),
                'born_year' => $this->input->post('born_year'),
                'lifestyle' => $this->input->post('lifestyle'),
                'physical_activity' => $this->input->post('physical_activity'),
                'diseases' => $this->input->post('diseases'),
                'diseases_family' => $this->input->post('diseases_family'),
                'current_policy' => $this->input->post('current_policy'),
                'future_results' => $this->input->post('future_results'),
                'status_id' => $this->input->post('status_id'),
                'skype' => $this->input->post('skype'),
                'comment' => $this->input->post('comment'),
                'body_fat' => $this->input->post('body_fat'),
                'body_water' => $this->input->post('body_water'),
                'visceral_fat' => $this->input->post('visceral_fat'),
                'metabolic_rate' => $this->input->post('metabolic_rate'),
                'biological_age' => $this->input->post('biological_age'),
                'muscle_mass' => $this->input->post('muscle_mass'),
                'physically_rating' => $this->input->post('physically_rating'),
                'bone_mass' => $this->input->post('bone_mass')
            );
            $clients_edit = server_data($this->config->item('api_url') . 'clients/edit.php', $post_data);
            $this->template->set_data('client_edit', $clients_edit);
            $notifies[] = $clients_edit['result'];

            if (is_not($clients_edit['result']['errors'])) {
                if ($clients_edit['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_edit['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client($id) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array (
            't' => $token,
            'date_format' => 'for_russians',
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];

        $clients_dashboard = server_data($this->config->item('api_url') . 'clients/dashboard.php', $post_data);
        $data['client_dashboard'] = $clients_dashboard['response']['data'];
        $notifies[] = $clients_dashboard['result'];

        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_view', $data_privileges['privileges'])) {
                $name = $data['client_info']['surname'] . ' ' . $data['client_info']['name'] . ' ' . $data['client_info']['patronymic'];
                
                set_title($this->lang->line('client') . ' - ' . $name, '|', true);
                set_pagename($name, 'user');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name'],
                        )
                    )
                );
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }
                
                $this->template->set_data('client', $data);
                $this->template->load_file('clients/client/client');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_remove_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id')
            );

            $safe_delete = server_data($this->config->item('api_url') . 'clients/safe_delete.php', $post_data);
            $notifies[] = $safe_delete['result'];  

            if (is_not($safe_delete['result']['errors'])) {
                if ($safe_delete['result']['success'] == 1) {
                    $this->session->set_flashdata('success', $safe_delete['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/clients/5'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

//*******************************************
    function client_ei($id = FALSE) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');


//        echo '<pre>';
//        var_dump($this->session->all_userdata());exit;

        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];

        $clients_ei_list = server_data($this->config->item('api_url') . 'clients/ei_list.php', $post_data);
        $data['list'] = $clients_ei_list['response']['data'];
        $notifies[] = $clients_ei_list['result'];

        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_history_view', $data_privileges['privileges'])) {
                $name = $this->lang->line('client_ei');

                set_title($name, '|', true);
                set_pagename($name, 'file-text-o');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );

                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }

                $this->template->set_data('client_history', $data);

                $this->template->load_file('clients/client/client_ei');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404();
        }
    }
//****************************************


    function client_history($id = FALSE) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');

        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];
        
        $clients_history_list = server_data($this->config->item('api_url') . 'clients/history_list.php', $post_data);
        $data['list'] = $clients_history_list['response']['data'];
        $notifies[] = $clients_history_list['result'];

        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_history_view', $data_privileges['privileges'])) {
                $name = $this->lang->line('client_history');
                
                set_title($name, '|', true);
                set_pagename($name, 'file-text-o');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }

                $this->template->set_data('client_history', $data);

                $this->template->load_file('clients/client/client_history');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404(); 
        }
    }  
    
    function client_history_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'item_id' => $this->input->post('item_id'),
                        'client_id' => $this->input->post('id')
                    );                
                    $history_details = server_data($this->config->item('api_url') . 'clients/history_details.php', $post_data);
                    $data['history_details'] = $history_details['response']['data'];
                    $notifies[] = $history_details['result'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('client_history_modals', $data);
                $this->template->load_file('clients/client/client_history_modals');
            }
        } else {
            $this->error_404();
        }      
    }
    
    function client_history_add_action() {
        if (isset($_POST['client_history_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'date' => $this->input->post('date'),
                'text' => $this->input->post('text')
            );
            $clients_history_add = server_data($this->config->item('api_url') . 'clients/history_add.php', $post_data);
            $notifies[] = $clients_history_add['result'];

            if (is_not($clients_history_add['result']['errors'])) {
                if ($clients_history_add['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_history_add['result']['notifies']);
                }
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_history/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_history_edit_action() {
        if (isset($_POST['client_history_edit_submit'])) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'item_id' => $this->input->post('item_id'),
                'date' => $this->input->post('date'),
                'text' => $this->input->post('text')
            );
            $clients_history_edit = server_data($this->config->item('api_url') . 'clients/history_edit.php', $post_data);
            $notifies[] = $clients_history_edit['result'];

            if (is_not($clients_history_edit['result']['errors'])) {
                if ($clients_history_edit['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_history_edit['result']['notifies']);
                }
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_history/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_history_remove_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'item_id' => $this->input->post('item_id')
            );

            $clients_history_del = server_data($this->config->item('api_url') . 'clients/history_del.php', $post_data);
            $notifies[] = $clients_history_del['result'];  

            if (is_not($clients_history_del['result']['errors'])) {
                if ($clients_history_del['result']['success'] == 1) {
                    $this->session->set_flashdata('success', $clients_history_del['result']['notifies']);
                }
                
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_history/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_anthropometry($id = FALSE) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];
        
        $clients_anthropometry_list = server_data($this->config->item('api_url') . 'clients/anthropometry_list.php', $post_data);
        $data['list'] = $clients_anthropometry_list['response']['data'];
        $notifies[] = $clients_anthropometry_list['result'];

        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_anthropometry_view', $data_privileges['privileges'])) {
                $name = $this->lang->line('client_anthropometry');
                
                set_title($name, '|', true);
                set_pagename($name, 'stethoscope');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }
                
                $this->template->set_data('client_anthropometry', $data);
                $this->template->load_file('clients/client/client_anthropometry');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404(); 
        }
    }
    
    function client_anthropometry_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'item_id' => $this->input->post('item_id'),
                        'client_id' => $this->input->post('id')
                    );                
                    $clients_anthropometry_details = server_data($this->config->item('api_url') . 'clients/anthropometry_details.php', $post_data);
                    $data['anthropometry_details'] = $clients_anthropometry_details['response']['data'];
                    $notifies[] = $clients_anthropometry_details['result'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('client_anthropometry_modals', $data);
                $this->template->load_file('clients/client/client_anthropometry_modals');
            }
        } else {
            $this->error_404();
        }      
    }
    
    function client_anthropometry_add_action() {
        if (isset($_POST['client_anthropometry_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'weight_size' => $this->input->post('weight'),
                'waist_size' => $this->input->post('waist_size'),
                'chest_size' => $this->input->post('chest_size'),
                'hips_size' => $this->input->post('hips_size'),
                'hand_size' => $this->input->post('hand_size'),
                'leg_size' => $this->input->post('leg_size'),
                'date' => $this->input->post('date'),
                'comment' => $this->input->post('comment'),
                'body_fat' => $this->input->post('body_fat'),
                'body_water' => $this->input->post('body_water'),
                'visceral_fat' => $this->input->post('visceral_fat'),
                'metabolic_rate' => $this->input->post('metabolic_rate'),
                'biological_age' => $this->input->post('biological_age'),
                'muscle_mass' => $this->input->post('muscle_mass'),
                'physically_rating' => $this->input->post('physically_rating'),
                'bone_mass' => $this->input->post('bone_mass')
            );
            $clients_anthropometry_add = server_data($this->config->item('api_url') . 'clients/anthropometry_add.php', $post_data);
            $notifies[] = $clients_anthropometry_add['result'];

            if (is_not($clients_anthropometry_add['result']['errors'])) {
                if ($clients_anthropometry_add['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_anthropometry_add['result']['notifies']);
                }

                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_anthropometry/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_anthropometry_edit_action() {
        if (isset($_POST['client_anthropometry_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'item_id' => $this->input->post('item_id'),
                'weight_size' => $this->input->post('weight'),
                'waist_size' => $this->input->post('waist_size'),
                'chest_size' => $this->input->post('chest_size'),
                'hips_size' => $this->input->post('hips_size'),
                'hand_size' => $this->input->post('hand_size'),
                'leg_size' => $this->input->post('leg_size'),
                'date' => $this->input->post('date'),
                'comment' => $this->input->post('comment'),
                'body_fat' => $this->input->post('body_fat'),
                'body_water' => $this->input->post('body_water'),
                'visceral_fat' => $this->input->post('visceral_fat'),
                'metabolic_rate' => $this->input->post('metabolic_rate'),
                'biological_age' => $this->input->post('biological_age'),
                'muscle_mass' => $this->input->post('muscle_mass'),
                'physically_rating' => $this->input->post('physically_rating'),
                'bone_mass' => $this->input->post('bone_mass')
            );
            $clients_anthropometry_edit = server_data($this->config->item('api_url') . 'clients/anthropometry_edit.php', $post_data);
            $notifies[] = $clients_anthropometry_edit['result'];

            if (is_not($clients_anthropometry_edit['result']['errors'])) {
                if ($clients_anthropometry_edit['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_anthropometry_edit['result']['notifies']);
                }

                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_anthropometry/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_anthropometry_remove_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'item_id' => $this->input->post('item_id')
            );

            $clients_anthropometry_del = server_data($this->config->item('api_url') . 'clients/anthropometry_del.php', $post_data);
            $notifies[] = $clients_anthropometry_del['result'];  

            if (is_not($clients_anthropometry_del['result']['errors'])) {
                if ($clients_anthropometry_del['result']['success'] == 1) {
                    $this->session->set_flashdata('success', $clients_anthropometry_del['result']['notifies']);
                }
                
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_anthropometry/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_payments($id = FALSE) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];
        
        $clients_payments_list = server_data($this->config->item('api_url') . 'clients/payments_list.php', $post_data);
        $data['list'] = $clients_payments_list['response']['data'];
        $notifies[] = $clients_payments_list['result'];

        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_payments_view', $data_privileges['privileges'])) {
                $name = $this->lang->line('client_payments');
                
                set_title($name, '|', true);
                set_pagename($name, 'usd');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }
                
                $this->template->set_data('client_payments', $data);
                $this->template->load_file('clients/client/client_payments');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404();
        }
    }

    function client_payments_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'item_id' => $this->input->post('item_id'),
                        'client_id' => $this->input->post('id')
                    );                
                    $payments_details = server_data($this->config->item('api_url') . 'clients/payments_details.php', $post_data);
                    $data['payments_details'] = $payments_details['response']['data'];
                    $notifies[] = $payments_details['result'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('client_payments_modals', $data);
                $this->template->load_file('clients/client/client_payments_modals');
            }
        } else {
            $this->error_404();
        }      
    }
    
    function client_payments_add_action() {
        if (isset($_POST['client_payments_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'price' => $this->input->post('price'),
                'date' => $this->input->post('date'),
                'comment' => $this->input->post('comment')
            );
            $clients_payments_add = server_data($this->config->item('api_url') . 'clients/payments_add.php', $post_data);
            $notifies[] = $clients_payments_add['result'];

            if (is_not($clients_payments_add['result']['errors'])) {
                if ($clients_payments_add['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_payments_add['result']['notifies']);
                }
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_payments/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_payments_edit_action() {
        if (isset($_POST['client_payments_edit_submit'])) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'item_id' => $this->input->post('item_id'),
                'price' => $this->input->post('price'),
                'date' => $this->input->post('date'),
                'comment' => $this->input->post('comment')
            );
            $clients_payments_edit = server_data($this->config->item('api_url') . 'clients/payments_edit.php', $post_data);
            $notifies[] = $clients_payments_edit['result'];

            if (is_not($clients_payments_edit['result']['errors'])) {
                if ($clients_payments_edit['result']['success'] == TRUE) {
                    $this->session->set_flashdata('success', $clients_payments_edit['result']['notifies']);
                }
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_payments/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_payments_remove_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'item_id' => $this->input->post('item_id')
            );

            $clients_payments_del = server_data($this->config->item('api_url') . 'clients/payments_del.php', $post_data);
            $notifies[] = $clients_payments_del['result'];  

            if (is_not($clients_payments_del['result']['errors'])) {
                if ($clients_payments_del['result']['success'] == 1) {
                    $this->session->set_flashdata('success', $clients_payments_del['result']['notifies']);
                }
                
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_payments/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function client_notifies($id = FALSE) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array(
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data); 
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];

        $clients_notifies_list = server_data($this->config->item('api_url') . 'clients/notifies_list.php', $post_data);
        $data['list'] = $clients_notifies_list['response']['data'];
        $notifies[] = $clients_notifies_list['result'];
        
        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_notifies_view', $data_privileges['privileges'])) {
                $name = $this->lang->line('messages');
                
                set_title($name, '|', true);
                set_pagename($name, 'info-circle');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );

                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }

                $this->template->set_data('client_notifies', $data);
                $this->template->load_file('clients/client/client_notifies');
            } else {
                $this->error_403();
            }
        } else {
            $this->error_404();
        }
    }

    function client_notifies_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add') {
  
                $this->template->load_file('clients/client/client_notifies_modals');
            }
        } else {
            $this->error_404();
        }      
    }

    function client_notifies_add_action() {
        if (isset($_POST['client_notifies_add_submit'])) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'caption' => $this->input->post('caption'),
                'text' => $this->input->post('text'),
                'datetime' => $this->input->post('datetime'),
                'send_sms' => $this->input->post('send_sms')
            );
            $clients_notifies_add = server_data($this->config->item('api_url') . 'clients/notifies_add.php', $post_data);
            $notifies[] = $clients_notifies_add['result'];

            if (is_not($clients_notifies_add['result']['errors'])) {
                if (is($this->input->post('without_redirect'))) {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                } else {
                    $response = array(
                        'redirect' => get_info('site_url') . '/client_notifies/' . $this->input->post('id')
                    );
                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    




























    
    








    /**
     *  *************************************************************************
     *  planner
     *  *************************************************************************
     */
    function planner($id = FALSE, $event_id = FALSE) {
        $data_privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');
        
        $post_data = array (
            't' => $token,
            'client_id' => $id,
        );
        $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
        $data['client_info'] = $clients_details['response']['client_info'];
        $notifies[] = $clients_details['result'];

        $events_classes_list = server_data($this->config->item('api_url') . 'planner/events_classes_list.php', $post_data);
        $data['events_classes_list'] = $events_classes_list['response']['data'];
        $notifies[] = $events_classes_list['result'];

        if (isset($_POST['submit_events_range_copy'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'destination_client_id' => $this->input->post('copy_to_client'),
                'destination_date' => $this->input->post('events_range_copy_date'),
                'events_range' => '[' . $this->input->post('events_ids') . ']',
                'replace_events' => $this->input->post('replace_events'),
                'delete_sources' => $this->input->post('delete_sources')
            );

            $events_range_copy = server_data($this->config->item('api_url') . 'planner/events_range_copy.php', $post_data);
            $notifies[] = $events_range_copy['result'];

            $get_cookie_date = get_cookie('date');
            if (is($get_cookie_date)) {
                $get_cookie_date = array('name' => 'date', 'value' => '', 'expire' => '0');
                delete_cookie($get_cookie_date);
                
                $get_cookie_date_new = array('name' => 'date', 'value' => week_range($this->input->post('events_range_copy_date'), 'start_day'), 'expire' => 86400);
                set_cookie($get_cookie_date_new);
            } else {
                $get_cookie_date = array('name' => 'date', 'value' => week_range($this->input->post('events_range_copy_date'), 'start_day'), 'expire' => 86400);
                set_cookie($get_cookie_date);
            }
            
            $get_scroll_value = array('name' => 'scroll_value', 'value' => '', 'expire' => '0');
            delete_cookie($get_scroll_value);

            if (is_not($events_range_copy['result']['errors'])) {
                if ($events_range_copy['result']['success'] == true) {
                    $this->session->set_flashdata('success', $events_range_copy['result']['notifies']);
                }
                redirect(get_info('site_url') . '/planner/' . $_POST['copy_to_client'], 'refresh');
            }
        }

        if (isset($_POST['submit_event_copy'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $id,
                'event_id' => $event_id,
                'copies_count' => $this->input->post('copies_count'),
            );
            $event_fast_copy = server_data($this->config->item('api_url') . 'planner/event_fast_copy.php', $post_data);
            $notifies[] = $event_fast_copy['result'];

            if (is_not($event_fast_copy['result']['errors'])) {
                if ($event_fast_copy['result']['success'] == true) {
                    $this->session->set_flashdata('success', $event_fast_copy['result']['notifies']);
                }
                redirect(get_info('site_url') . '/planner/' . $clients_details['response']['client_info']['id'], 'refresh');
            }
        }
        
        if (isset($_POST['submit_redefinition_week'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $id,
                'events_vs_new_time' => json_encode($this->input->post('redefinition_week')),
            );
            $events_redefinition_time_action = server_data($this->config->item('api_url') . 'planner/events_redefinition_time_action.php', $post_data);
            $notifies[] = $events_redefinition_time_action['result'];

            if (is_not($events_redefinition_time_action['result']['errors'])) {
                if ($events_redefinition_time_action['result']['success'] == true) {
                    $this->session->set_flashdata('success', $events_redefinition_time_action['result']['notifies']);
                }
                redirect(get_info('site_url') . '/planner/' . $clients_details['response']['client_info']['id'], 'refresh');
            }
        }
        
        
        if (isset($_POST['submit_week_copy'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'client_id' => $id,
                'start_week' => $this->input->post('copy_from_week'),
                'destination_client_id' => $this->input->post('copy_to_client'),
                'destination_start_week' => week_range($this->input->post('copy_to_week'), 'start_day'),
                'events_classes' => '[1, 2, 3]',
                'only_structure' => $this->input->post('copy_only_structure'),
            );
            $events_copy_week = server_data($this->config->item('api_url') . 'planner/events_copy_week.php', $post_data);
            $notifies[] = $events_copy_week['result'];

            $get_cookie_date = get_cookie('date');
            if (is($get_cookie_date)) {
                $get_cookie_date = array('name' => 'date', 'value' => '', 'expire' => '0');
                delete_cookie($get_cookie_date);
                
                $get_cookie_date_new = array('name' => 'date', 'value' => week_range($this->input->post('copy_to_week'), 'start_day'), 'expire' => 86400);
                set_cookie($get_cookie_date_new);
            } else {
                $get_cookie_date = array('name' => 'date', 'value' => week_range($this->input->post('copy_to_week'), 'start_day'), 'expire' => 86400);
                set_cookie($get_cookie_date);
            }
            
            $get_scroll_value = array('name' => 'scroll_value', 'value' => '', 'expire' => '0');
            delete_cookie($get_scroll_value);

            if (is_not($events_copy_week['result']['errors'])) {
                if ($events_copy_week['result']['success'] == true) {
                    $this->session->set_flashdata('success', $events_copy_week['result']['notifies']);
                }
                redirect(get_info('site_url') . '/planner/' . $this->input->post('copy_to_client'), 'refresh');
            }
        }

        if (is($clients_details['response']['client_info']['id'])) {
            if (in_array('client_view', $data_privileges['privileges'])) {
                $name = $this->lang->line('planner');
                
                set_title($name, '|', true);
                set_pagename($name, 'bar-chart-o');
                set_breadcrumbs(
                    array(
                        $this->_general_list,
                        array(
                            'url' => 'clients/' . $this->session->userdata['client_status'],
                            'name' => $this->lang->line('clients')
                        ),
                        array(
                            'url' => 'client/' . $clients_details['response']['client_info']['id'],
                            'name' => $clients_details['response']['client_info']['surname'] . ' ' . $clients_details['response']['client_info']['name']
                        ),
                        array(
                            'name' => $name,
                        )
                    )
                );
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                }
                
                $this->template->set_data('planner', $data);
                $this->template->load_file('clients/planner/planner');
            } else {
                $this->error_403(); 
            }
        } else {
            $this->error_404(); 
        }
    } 

    function planner_loader() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
    
            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id')
            );
    
            $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
            $data['client_info'] = $clients_details['response']['client_info'];
            $notifies[] = $clients_details['result'];
    
            if (isset($_POST['submit_change_week'])) {
                $selected_date = $this->input->post('event_date');
            }
            if (isset($_POST['submit_prev_week'])) {
                $selected_date = strtotime(week_range($this->input->post('event_date'), 'start_day'));
                $selected_date = strtotime('-7 day', $selected_date);
                $selected_date = date('d.m.Y', $selected_date);
            }
            if (isset($_POST['submit_current_week'])) {
                $selected_date = week_range(date('d.m.Y'), 'start_day');
            }
            if (isset($_POST['submit_next_week'])) {
                $selected_date = strtotime(week_range($this->input->post('event_date'), 'start_day'));
                $selected_date = strtotime('+7 day', $selected_date);
                $selected_date = date('d.m.Y', $selected_date); 
            }
            if (isset($_POST['submit_change_week']) || isset($_POST['submit_prev_week']) || isset($_POST['submit_current_week']) || isset($_POST['submit_next_week'])) {
                $get_cookie_date = get_cookie('date');
                if (is($get_cookie_date)) {
                    $get_cookie_date = array('name' => 'date', 'value' => '', 'expire' => '0');
                    delete_cookie($get_cookie_date);
                    
                    $get_cookie_date_new = array('name' => 'date', 'value' => $selected_date, 'expire' => 86400);
                    set_cookie($get_cookie_date_new);
                } else {
                    $get_cookie_date = array('name' => 'date', 'value' => $selected_date, 'expire' => 86400);
                    set_cookie($get_cookie_date);
                }
    
                $get_scroll_value = array('name' => 'scroll_value', 'value' => '', 'expire' => '0');
                delete_cookie($get_scroll_value);
                
                if (isset($_POST['date'])) {
                    $start_day = week_range($this->input->post('date'), 'start_day');
                    $end_day = week_range($this->input->post('date'), 'end_day');
                } else {        
                    $get_cookie_date = get_cookie('date');
                    if (is($get_cookie_date)) {
                        $start_day = week_range($selected_date, 'start_day');
                        $end_day = week_range($selected_date, 'end_day');
                    } else {
                        $start_day = date('d.m.Y', strtotime('Last Monday', time()));
                        $end_day = date('d.m.Y', strtotime('Sunday', time()));
                    }
                }
            } else {
                if (isset($_POST['date'])) {
                    $start_day = week_range($this->input->post('date'), 'start_day');
                    $end_day = week_range($this->input->post('date'), 'end_day');
                    
                    $get_cookie_date = get_cookie('date');
                    if (is($get_cookie_date)) {
                        $get_cookie_date = array('name' => 'date', 'value' => '', 'expire' => '0');
                        delete_cookie($get_cookie_date);
                        
                        $get_cookie_date_new = array('name' => 'date', 'value' => $start_day, 'expire' => 86400);
                        set_cookie($get_cookie_date_new);
                    } else {
                        $get_cookie_date = array('name' => 'date', 'value' => $start_day, 'expire' => 86400);
                        set_cookie($get_cookie_date);
                    }
                } else {
                    $get_cookie_date = get_cookie('date');
                    if (is($get_cookie_date)) {
                        $start_day = week_range(get_cookie('date'), 'start_day');
                        $end_day = week_range(get_cookie('date'), 'end_day');
                    } else {
                        $start_day = strtotime('Last Monday', time());
                        $start_day = strtotime('+7 day', $start_day);
                        $start_day = date('d.m.Y', $start_day);
                        
                        $end_day = strtotime('Sunday', time());
                        $end_day = strtotime('+7 day', $end_day);
                        $end_day = date('d.m.Y', $end_day);
                    }
                }
            }

            $post_data = array(
                't' => $token,
                'client_id' => $this->input->post('id'),
                'date_start' => $start_day,
                'date_end' => $end_day
            );
    
            $events_list = server_data($this->config->item('api_url') . 'planner/events_list2.php', $post_data);
            $data['events_list'] = $events_list['response']['data'];
            $data['selected_date'] = $selected_date;
    
            $notifies[] = $events_list['result'];
            
            if (is($notifies)) {
                $this->template->set_data('notifies', $notifies);
                $this->template->load_file('notifies');
            }
            
            $this->template->set_data('planner_loader', $data);
            $this->template->load_file('clients/planner/planner_loader');
        } else {
            $this->error_404(); 
        }
    }

    function planner_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('modal') == 'week_export' || $this->input->post('modal') == 'missed_events_export' || $this->input->post('modal') == 'events_range_copy' || $this->input->post('modal') == 'redefinition_week' || $this->input->post('modal') == 'week_copy' || $this->input->post('modal') == 'copy_event') {
                $token = $this->session->userdata('token');
                
                if ($this->input->post('modal') == 'week_export' || $this->input->post('modal') == 'missed_events_export') {
                    $post_data = array(
                        't' => $token,
                        'client_id' => $this->input->post('id'),
                    );
            
                    $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
                    $notifies[] = $clients_details['result'];
                    $data['client_info'] = $clients_details['response']['client_info'];
                }
                
                if ($this->input->post('modal') == 'events_range_copy') {
                    $post_data = array(
                        't' => $token,
                    );
                    $active_clients_list = server_data($this->config->item('api_url') . 'clients/active_clients_list.php', $post_data);
                    $notifies[] = $active_clients_list['result'];
                    $data['active_clients_list'] = $active_clients_list['response']['data'];
                }

                if ($this->input->post('modal') == 'redefinition_week') {
                    $get_cookie_date = get_cookie('date');
                    if (is($get_cookie_date)) {
                        $start_day = week_range(get_cookie('date'), 'start_day');
                        $end_day = week_range(get_cookie('date'), 'end_day');
                    } else {
                        $start_day = date('d.m.Y', strtotime('Last Monday', time()));
                        $end_day = date('d.m.Y', strtotime('Sunday', time()));
                    }
                    $post_data = array(
                        't' => $token,
                        'start_week' => $start_day,
                        'client_id' => $this->input->post('id')
                    );
                    $event_groups_for_week = server_data($this->config->item('api_url') . 'planner/event_groups_for_week.php', $post_data);
                    $notifies[] = $event_groups_for_week['result'];
                    $data['events_redefinition_time'] = $event_groups_for_week['response']['data'];
                }

                if ($this->input->post('modal') == 'week_copy') {
                    $post_data = array(
                        't' => $token,
                        'client_id' => $this->input->post('id'),
                    );
            
                    $clients_details = server_data($this->config->item('api_url') . 'clients/details.php', $post_data);
                    $notifies[] = $clients_details['result'];
                    $data['client_info'] = $clients_details['response']['client_info'];
                    
                    $active_clients_list = server_data($this->config->item('api_url') . 'clients/active_clients_list.php', $post_data);
                    $notifies[] = $active_clients_list['result'];
                    $data['active_clients_list'] = $active_clients_list['response']['data'];
                }

                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('planner_modals', $data);
                $this->template->load_file('clients/planner/planner_modals');
            }
        } else {
            $this->error_404();
        }
    }








    /**
     *  *************************************************************************
     *  bugreport
     *  *************************************************************************
     */
    function bugreports() {
        $token = $this->session->userdata('token');

        $post_data = array(
            't' => $token,
        );
        $bugreport_list = server_data($this->config->item('api_url') . 'experts/bugreport_list.php', $post_data);
        $data['bugreport_list'] = $bugreport_list['response']['data'];
        $notifies[] = $bugreport_list['result'];
        
        $name = 'Сообщения об ошибке';

        set_title($name, '|', true);
        set_pagename($name, 'exclamation-triangle');
        set_breadcrumbs(
            array(
                $this->_general_list,
                array(
                    'name' => $name,
                )
            )
        );
        
        $this->template->set_data('notifies', $notifies);
        
        $this->template->set_data('bugreports', $data);
        $this->template->load_file('bugreports');
    }
     
    function bugreport_add() {
        if (isset($_POST['bugreport_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'page' => $this->input->post('page'),
                'priority' => $this->input->post('priority'),
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr')
            );
            $bugreport_add = server_data($this->config->item('api_url') . 'experts/bugreport_add.php', $post_data);
            $notifies[] = $bugreport_add['result'];

            if (is_not($bugreport_add['result']['errors'])) {
                if ($bugreport_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $bugreport_add['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => $this->input->post('page')
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function bugreport_check() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'item_id' => $this->input->post('id'),
            );
            $bugreport_fix = server_data($this->config->item('api_url') . 'experts/bugreport_fix.php', $post_data);
            $notifies[] = $bugreport_fix['result'];

            if (is_not($bugreport_fix['result']['errors'])) {
                if ($bugreport_fix['result']['success'] == true) {
                    $this->session->set_flashdata('success', $bugreport_fix['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/bugreports'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function bugreport_remove() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'item_id' => $this->input->post('id'),
            );
            $bugreport_del = server_data($this->config->item('api_url') . 'experts/bugreport_del.php', $post_data);
            $notifies[] = $bugreport_del['result'];

            if (is_not($bugreport_del['result']['errors'])) {
                if ($bugreport_del['result']['success'] == true) {
                    $this->session->set_flashdata('success', $bugreport_del['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/bugreports'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
















    /**
     *  *************************************************************************
     *  tasks
     *  *************************************************************************
     */
    function tasks() {
        $privileges = $this->template->get_data('privileges');
        $token = $this->session->userdata('token');

        $post_data = array(
            't' => $token,
        );
        
        $performers_list = server_data($this->config->item('api_url') . 'tasks/performers_list.php', $post_data);
        $data['performers_list'] = $performers_list['response']['data'];
        $notifies[] = $performers_list['result'];
        
        $types_list = server_data($this->config->item('api_url') . 'tasks/types_list.php', $post_data);
        $data['types_list'] = $types_list['response']['data'];
        $notifies[] = $types_list['result'];

        if (in_array('constructor_view', $privileges['privileges'])) {
            $name = 'Задачи';
            
            set_title($name, '|', true);
            set_pagename($name, 'calendar');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'name' => $name,
                    )
                )
            );
            
            if (is($notifies)) {
                $this->template->set_data('notifies', $notifies);
            }
            
            $this->template->set_data('tasks', $data);
            $this->template->load_file('tasks/tasks');
        } else {
            $this->error_403();
        }
    }

    function tasks_loader() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            if (isset($_POST['change_week_submit']) || isset($_POST['prev_week_submit']) || isset($_POST['current_week_submit']) || isset($_POST['next_week_submit'])) {
                if (isset($_POST['change_week_submit'])) {
                    $selected_date = $this->input->post('selected_date');
                }
                if (isset($_POST['prev_week_submit'])) {
                    $selected_date = strtotime(week_range($this->input->post('selected_date'), 'start_day'));
                    $selected_date = strtotime('-7 day', $selected_date);
                    $selected_date = date('d.m.Y', $selected_date);
                }
                if (isset($_POST['current_week_submit'])) {
                    $selected_date = date('d.m.Y');
                }
                if (isset($_POST['next_week_submit'])) {
                    $selected_date = strtotime(week_range($this->input->post('selected_date'), 'start_day'));
                    $selected_date = strtotime('+7 day', $selected_date);
                    $selected_date = date('d.m.Y', $selected_date); 
                }
                
                $selected_task_week_cookie = $this->input->cookie('selected_task_week');
                if (is($selected_date)) {
                    $start_day = week_range($selected_date, 'start_day');
                    $end_day = week_range($selected_date, 'end_day');
                } else if (is($selected_task_week_cookie)) {
                    $start_day = week_range($selected_task_week_cookie, 'start_day');
                    $end_day = week_range($selected_task_week_cookie, 'end_day');
                } else {
                    $start_day = week_range(date('d.m.Y'), 'start_day');
                    $end_day = week_range(date('d.m.Y'), 'end_day');
                }
                
                $selected_task_week_cookie = array('name' => 'selected_task_week', 'value' => $selected_date, 'expire' => 86400);
                $this->input->set_cookie($selected_task_week_cookie);
            } else {
                $selected_task_week_cookie = $this->input->cookie('selected_task_week');
                if (is($selected_task_week_cookie)) {
                   $start_day = week_range($selected_task_week_cookie, 'start_day');
                    $end_day = week_range($selected_task_week_cookie, 'end_day');
                } else {
                    $start_day = week_range(date('d.m.Y'), 'start_day');
                    $end_day = week_range(date('d.m.Y'), 'end_day');
                }  
            }


            $post_data = array(
                't' => $token,
                'date_start' => $start_day,
                'date_end' => $end_day,
                'filter_task_types' => NULL,
            );
            
            if (isset($_POST['filter_performers'])) {
                $post_data['filter_performers'] = json_encode($this->input->post('filter_performers'));
            } else {
                $post_data['filter_performers'] = NULL;
            }

            $tasks_list = server_data($this->config->item('api_url') . 'tasks/list.php', $post_data);
            $data['tasks_list'] = $tasks_list['response']['data'];
            $data['selected_date'] = $selected_date;
            $data['time_start'] = '06:00';
            $data['time_end'] = '23:00';
            $data['time_step'] = '00:30';

            $notifies[] = $tasks_list['result'];
            
            if (is($notifies)) {
                $this->template->set_data('notifies', $notifies);
                $this->template->load_file('notifies');
            }
            
            $this->template->set_data('tasks_loader', $data);
            $this->template->load_file('tasks/tasks_loader');
        } else {
            $this->error_404(); 
        }
    }

    function tasks_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit') {
                $token = $this->session->userdata('token');
                
                if ($this->input->post('action') == 'add') {
                    $token = $this->session->userdata('token');
            
                    $post_data = array(
                        't' => $token,
                    );
                    $types_list = server_data($this->config->item('api_url') . 'tasks/types_list.php', $post_data);
                    $data['types_list'] = $types_list['response']['data'];
                    $notifies[] = $types_list['result'];
                    
                    $performers_list = server_data($this->config->item('api_url') . 'tasks/performers_list.php', $post_data);
                    $data['performers_list'] = $performers_list['response']['data'];
                    $notifies[] = $performers_list['result'];
                    
                    $clients_list = server_data($this->config->item('api_url') . 'tasks/clients_list.php', $post_data);
                    $data['clients_list'] = $clients_list['response']['data'];
                    $notifies[] = $clients_list['result'];
                    
                    $statuses_list = server_data($this->config->item('api_url') . 'tasks/statuses_list.php', $post_data);
                    $data['statuses_list'] = $statuses_list['response']['data'];
                    $notifies[] = $statuses_list['result'];
                    
                    $data['time_end'] = '23:00';
                    $data['time_step'] = '00:30';
                }
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'task_id' => $this->input->post('id')
                    );
                    $tasks_details = server_data($this->config->item('api_url') . 'tasks/details.php', $post_data);
                    $data['tasks_details'] = $tasks_details['response']['data'];
                    $notifies[] = $tasks_details['result'];
                    
                    $types_list = server_data($this->config->item('api_url') . 'tasks/types_list.php', $post_data);
                    $data['types_list'] = $types_list['response']['data'];
                    $notifies[] = $types_list['result'];

                    $performers_list = server_data($this->config->item('api_url') . 'tasks/performers_list.php', $post_data);
                    $data['performers_list'] = $performers_list['response']['data'];
                    $notifies[] = $performers_list['result'];
                    
                    $clients_list = server_data($this->config->item('api_url') . 'tasks/clients_list.php', $post_data);
                    $data['clients_list'] = $clients_list['response']['data'];
                    $notifies[] = $clients_list['result'];
                    
                    $statuses_list = server_data($this->config->item('api_url') . 'tasks/statuses_list.php', $post_data);
                    $data['statuses_list'] = $statuses_list['response']['data'];
                    $notifies[] = $statuses_list['result']; 
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('tasks_modals', $data);
                $this->template->load_file('tasks/tasks_modals');
            }
        } else {
            $this->error_404();
        }
    }

    function tasks_add_action() {
        if (isset($_POST['tasks_add_submit'])) {
            $token = $this->session->userdata('token');
            
            if (is($this->input->post('performers_list'))) {
                $performers_list = json_encode($this->input->post('performers_list'));
            } else {
                $performers_list = FALSE;
            }
            
            if (is($this->input->post('client_id'))) {
                $client_id = $this->input->post('client_id');
            } else {
                $client_id = FALSE;
            }
            
            $post_data = array(
                't' => $token,
                'datetime' => $this->input->post('datetime'),
                'status_id' => 1,
                'type_id' => $this->input->post('type_id'),
                'caption' =>$this->input->post('caption'),
                'client_id' => $client_id,
                'comment' => $this->input->post('comment'),
                'performers_list' => $performers_list,
            );
            $tasks_add = server_data($this->config->item('api_url') . 'tasks/add.php', $post_data);
            $notifies[] = $tasks_add['result'];

            if (is_not($tasks_add['result']['errors'])) {
                if ($tasks_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $tasks_add['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => get_info('site_url') . '/tasks'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function tasks_edit_action() {
        if (isset($_POST['tasks_edit_submit'])) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'task_id' => $this->input->post('id'),
                'datetime' => $this->input->post('datetime'),
                'status_id' => $this->input->post('status_id'),
                'type_id' => $this->input->post('type_id'),
                'caption' =>$this->input->post('caption'),
                'client_id' => is($this->input->post('client_id')),
                'comment' => is($this->input->post('comment')),
                'performers_list' => json_encode($this->input->post('performers_list')),
            );
            $tasks_edit = server_data($this->config->item('api_url') . 'tasks/edit.php', $post_data);
            $notifies[] = $tasks_edit['result'];

            if (is_not($tasks_edit['result']['errors'])) {
                if ($tasks_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $tasks_edit['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => get_info('site_url') . '/tasks'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function tasks_remove_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'task_id' => $this->input->post('id')
            );

            $tasks_delete = server_data($this->config->item('api_url') . 'tasks/delete.php', $post_data);
            $notifies[] = $tasks_delete['result'];  

            if (is_not($tasks_delete['result']['errors'])) {
                if ($tasks_delete['result']['success'] == 1) {
                    $this->session->set_flashdata('success', $tasks_delete['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/tasks'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function tasks_change_status_action() {
        if (isset($_POST['tasks_change_status_submit'])) {
            $token = $this->session->userdata('token');

            
            $post_data = array(
                't' => $token,
                'status_id' => 10,
                'item_id' => $this->input->post('id'),
            );
            $tasks_change_status = server_data($this->config->item('api_url') . 'tasks/change_status.php', $post_data);
            $notifies[] = $tasks_change_status['result'];

            if (is_not($tasks_change_status['result']['errors'])) {
                if ($tasks_change_status['result']['success'] == true) {
                    $this->session->set_flashdata('success', $tasks_change_status['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => get_info('site_url') . '/tasks'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function tasks_move_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'datetime' => $this->input->post('datetime'),
                'item_id' => $this->input->post('id'),
            );
            $tasks_move = server_data($this->config->item('api_url') . 'tasks/move.php', $post_data);
            $notifies[] = $tasks_move['result'];
            $notifies['success'] = $tasks_move['result']['notifies'];
               
            if (is_not($tasks_move['result']['errors'])) {
                if ($tasks_move['result']['success'] == TRUE) {
                    $this->template->set_data('notifies', $notifies);

                    ob_start();
                    $this->template->load_file('notifies');
                    $output = ob_get_clean();
                    
                    $response = array(
                        'html' => $output
                    );

                    $this->output->append_output(json_encode($response));
                }
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }



    /**
     *  *************************************************************************
     *  constructor
     *  *************************************************************************
     */
    function constructor() {
        $privileges = $this->template->get_data('privileges');
        
        $name = 'Конструктор';

        set_title($name, '|', true);
        set_pagename($name, 'user-md');
        set_breadcrumbs(
            array(
                $this->_general_list,
                array(
                    'name' => $name,
                )
            )
        );

        if (in_array('constructor_view', $privileges['privileges'])) {
            $this->template->load_file('constructor/constructor');
        } else {
            $this->error_403();
        }
    }

    function products() {
        $privileges = $this->template->get_data('privileges');
        
        if (in_array('products_view', $privileges['privileges'])) {
            $token = $this->session->userdata('token');
            $data['section'] = 'products';

            $data['search'] = array(
                'search_query' => $this->input->cookie('search_query_products'),
                'filter' => $this->session->userdata('filter'),
                'order' => $this->session->userdata('order'),
            );

            $name = 'Продукты';
            
            set_title($name, '|', true);
            set_pagename($name, 'shopping-cart');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'url' => 'constructor',
                        'name' => 'Конструктор',
                        'submenu' => $this->_constructor_list
                    ),
                    array(
                        'name' => $name,
                    )
                )
            );
            $this->template->set_data('constructor_loader', $data);
            $this->template->load_file('constructor/constructor_loader');
        } else {
            $this->error_403();
        };
    }

    function products_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'view') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'add') {
                    $post_data = array(
                        't' => $token
                    );
                    $products_categs_list = server_data($this->config->item('api_url') . 'constructor/products_categs_list.php', $post_data);
                    $data['products_categs'] = $products_categs_list['response']['data'];
                    $notifies[] = $products_categs_list['result'];
                }
                
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'product_id' => $this->input->post('id')
                    );
                    $products_categs_list = server_data($this->config->item('api_url') . 'constructor/products_categs_list.php', $post_data);
                    $data['products_categs'] = $products_categs_list['response']['data'];
                    $notifies[] = $products_categs_list['result'];
                    
                    $products_details = server_data($this->config->item('api_url') . 'constructor/products_details.php', $post_data);
                    $data['products_list'] = $products_details['response']['data'];
                    $notifies[] = $products_details['result'];
                }
                
                if ($this->input->post('action') == 'view') {
                    $post_data = array(
                        't' => $token,
                        'product_id' => $this->input->post('id')
                    );
                    $products_details = server_data($this->config->item('api_url') . 'constructor/products_details.php', $post_data);
                    $notifies[] = $products_details['result'];
                    $data['products_list'] = $products_details['response']['data'];
                    
                    $products_categs_list = server_data($this->config->item('api_url') . 'constructor/products_categs_list.php', $post_data);
                    $notifies[] = $products_categs_list['result']; 
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('products_modals', $data);
                $this->template->load_file('constructor/products_modals');
            }
        } else {
            $this->error_404();
        }
    }
    
    function products_add_action() {
        if (isset($_POST['products_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'categ_id' => $this->input->post('categ_id'),
                'caption' => $this->input->post('caption'),
                'proteins_count' =>$this->input->post('proteins_count'),
                'fats_count' => $this->input->post('fats_count'),
                'cellulose_count' => $this->input->post('cellulose_count'),
                'carbs_count' => $this->input->post('carbs_count'),
                'kkal_count' => $this->input->post('kkal_count'),
                'descr' => $this->input->post('descr')
            );
            $products_add = server_data($this->config->item('api_url') . 'constructor/products_add.php', $post_data);
            $notifies[] = $products_add['result'];

            if (is_not($products_add['result']['errors'])) {
                if ($products_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $products_add['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/products'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function products_edit_action() {
        if (isset($_POST['products_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'product_id' => $this->input->post('id'),
                'categ_id' => $this->input->post('categ_id'),
                'caption' => $this->input->post('caption'),
                'proteins_count' => $this->input->post('proteins_count'),
                'fats_count' => $this->input->post('fats_count'),
                'carbs_count' => $this->input->post('carbs_count'),
                'cellulose_count' => $this->input->post('cellulose_count'),
                'kkal_count' => $this->input->post('kkal_count'),
                'descr' => $this->input->post('descr')
            );
            $products_edit = server_data($this->config->item('api_url') . 'constructor/products_edit.php', $post_data);
            $notifies[] = $products_edit['result'];

            if (is_not($products_edit['result']['errors'])) {
                if ($products_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $products_edit['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/products'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }

    function dishes() {
        $privileges = $this->template->get_data('privileges');
        
        if (in_array('products_view', $privileges['privileges'])) {
            $data['section'] = 'dishes';

            $data['search'] = array(
                'search_query' => $this->input->cookie('search_query_dishes'),
                'filter' => $this->session->userdata('filter'),
                'order' => $this->session->userdata('order'),
            );

            $name = 'Блюда';
            
            set_title($name, '|', true);
            set_pagename($name, 'cutlery');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'url' => 'constructor',
                        'name' => 'Конструктор',
                        'submenu' => $this->_constructor_list
                    ),
                    array(
                        'name' => $name,
                    )
                )
            );
            $this->template->set_data('constructor_loader', $data);
            $this->template->load_file('constructor/constructor_loader');
        } else {
            $this->error_403();
        };
    }
    
    function dishes_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'view') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'add') {
                    $post_data = array(
                        't' => $token
                    );                    
                    $dishes_categs_list = server_data($this->config->item('api_url') . 'constructor/dishes_categs_list.php', $post_data);
                    $data['dishes_categs'] = $dishes_categs_list['response']['data'];
                    $notifies[] = $dishes_categs_list['result'];
                    
                    $dishes_destinations_list = server_data($this->config->item('api_url') . 'constructor/dishes_destinations_list.php', $post_data);
                    $data['dishes_destinations'] = $dishes_destinations_list['response']['data'];
                    $notifies[] = $dishes_destinations_list['result'];
            
                    $dishes_prepare_methods_list = server_data($this->config->item('api_url') . 'constructor/dishes_prepare_methods_list.php', $post_data);
                    $data['dishes_prepare_methods'] = $dishes_prepare_methods_list['response']['data'];
                    $notifies[] = $dishes_prepare_methods_list['result'];
                    
                    $dishes_prepare_times_list = server_data($this->config->item('api_url') . 'constructor/dishes_prepare_times_list.php', $post_data);
                    $data['dishes_prepare_times'] = $dishes_prepare_times_list['response']['data'];
                    $notifies[] = $dishes_prepare_times_list['result'];
                }
                
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'dish_id' => $this->input->post('id')
                    );                    
                    $dishes_categs_list = server_data($this->config->item('api_url') . 'constructor/dishes_categs_list.php', $post_data);
                    $data['dishes_categs'] = $dishes_categs_list['response']['data'];
                    $notifies[] = $dishes_categs_list['result'];
                    
                    $dishes_destinations_list = server_data($this->config->item('api_url') . 'constructor/dishes_destinations_list.php', $post_data);
                    $data['dishes_destinations'] = $dishes_destinations_list['response']['data'];
                    $notifies[] = $dishes_destinations_list['result'];
            
                    $dishes_prepare_methods_list = server_data($this->config->item('api_url') . 'constructor/dishes_prepare_methods_list.php', $post_data);
                    $data['dishes_prepare_methods'] = $dishes_prepare_methods_list['response']['data'];
                    $notifies[] = $dishes_prepare_methods_list['result'];
                    
                    $dishes_prepare_times_list = server_data($this->config->item('api_url') . 'constructor/dishes_prepare_times_list.php', $post_data);
                    $data['dishes_prepare_times'] = $dishes_prepare_times_list['response']['data'];
                    $notifies[] = $dishes_prepare_times_list['result'];
                    
                    $dishes_details = server_data($this->config->item('api_url') . 'constructor/dishes_details.php', $post_data);
                    $data['dishes_details'] = $dishes_details['response']['data'];
                    $notifies[] = $dishes_details['result'];
                }
                
                if ($this->input->post('action') == 'view') {
                    $post_data = array(
                        't' => $token,
                        'dish_id' => $this->input->post('id')
                    );
                    $dishes_details = server_data($this->config->item('api_url') . 'constructor/dishes_details.php', $post_data);
                    $data['dishes_details'] = $dishes_details['response']['data'];
                    $notifies[] = $dishes_details['result'];
                    
                    $dishes_destinations_list = server_data($this->config->item('api_url') . 'constructor/dishes_destinations_list.php', $post_data);
                    $data['dishes_destinations'] = $dishes_destinations_list['response']['data'];
                    $notifies[] = $dishes_destinations_list['result'];
            
                    $dishes_prepare_methods_list = server_data($this->config->item('api_url') . 'constructor/dishes_prepare_methods_list.php', $post_data);
                    $data['dishes_prepare_methods'] = $dishes_prepare_methods_list['response']['data'];
                    $notifies[] = $dishes_prepare_methods_list['result'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                
                $this->template->set_data('dishes_modals', $data);
                $this->template->load_file('constructor/dishes_modals');
            }
        } else {
            $this->error_404();
        }
    }
    
    function dishes_add_action() {
        if (isset($_POST['dishes_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'expert_id' => $this->session->userdata('id'),
                'categ_id' => $this->input->post('categ_id'),
                'caption' => $this->input->post('caption'),
                'prepare_time_id' => $this->input->post('prepare_time_id'),
                'proteins_count' =>$this->input->post('proteins_count'),
                'fats_count' => $this->input->post('fats_count'),
                'carbs_count' => $this->input->post('carbs_count'),
                'cellulose_count' => $this->input->post('cellulose_count'),
                'kkal_count' => $this->input->post('kkal_count'),
                'product_list' => json_encode($this->input->post('product_list')),
                'destinations_list' => json_encode($this->input->post('destinations_list')),
                'prepare_methods_list' => json_encode($this->input->post('prepare_methods_list')),
                'recipe' => $this->input->post('recipe')
            );
            $dishes_add = server_data($this->config->item('api_url') . 'constructor/dishes_add.php', $post_data);
            $notifies[] = $dishes_add['result'];

            if (is_not($dishes_add['result']['errors'])) {
                if ($dishes_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $dishes_add['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/dishes'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }
    
    function dishes_edit_action() {
        if (isset($_POST['dishes_edit_submit'])) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'dish_id' => $this->input->post('id'),
                'categ_id' => $this->input->post('categ_id'),
                'caption' => $this->input->post('caption'),
                'prepare_time_id' => $this->input->post('prepare_time_id'),
                'proteins_count' => $this->input->post('proteins_count'),
                'fats_count' => $this->input->post('fats_count'),
                'carbs_count' => $this->input->post('carbs_count'),
                'cellulose_count' => $this->input->post('cellulose_count'),
                'kkal_count' => $this->input->post('kkal_count'),
                'product_list' => json_encode($this->input->post('product_list')),
                'destinations_list' => json_encode($this->input->post('destinations_list')),
                'prepare_methods_list' => json_encode($this->input->post('prepare_methods_list')),
                'recipe' => $this->input->post('recipe')
            );
            $dishes_edit = server_data($this->config->item('api_url') . 'constructor/dishes_edit.php', $post_data);
            $notifies[] = $dishes_edit['result'];

            if (is_not($dishes_edit['result']['errors'])) {
                if ($dishes_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $dishes_edit['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/dishes'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }

    function preparats() {
        $privileges = $this->template->get_data('privileges');
        
        if (in_array('products_view', $privileges['privileges'])) {
            $data['section'] = 'preparats';
            
            $data['search'] = array(
                'search_query' => $this->input->cookie('search_query_preparats'),
                'filter' => $this->session->userdata('filter'),
                'order' => $this->session->userdata('order'),
            );

            $name = 'Препараты';
            
            set_title($name, '|', true);
            set_pagename($name, 'flask');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'url' => 'constructor',
                        'name' => 'Конструктор',
                        'submenu' => $this->_constructor_list
                    ),
                    array(
                        'name' => $name,
                    )
                )
            );
            $this->template->set_data('constructor_loader', $data);
            $this->template->load_file('constructor/constructor_loader');
        } else {
            $this->error_403();
        };
    }

    function preparats_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'view') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'add') {
                    $post_data = array(
                        't' => $token
                    );
                    
                    $preparats_categs_list = server_data($this->config->item('api_url') . 'constructor/preparats_categs_list.php', $post_data);
                    $data['preparats_categs_list'] = $preparats_categs_list['response']['data'];
                    $notifies[] = $preparats_categs_list['result'];
                }
                
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'item_id' => $_POST['id'],
                    );
                    $preparats_details = server_data($this->config->item('api_url') . 'constructor/preparats_details.php', $post_data);
                    $data['preparats_details'] = $preparats_details['response']['data'];
                    $notifies[] = $preparats_details['result'];
                    
                    $preparats_categs_list = server_data($this->config->item('api_url') . 'constructor/preparats_categs_list.php', $post_data);
                    $data['preparats_categs_list'] = $preparats_categs_list['response']['data'];
                    $notifies[] = $preparats_categs_list['result'];
                }
                
                if ($this->input->post('action') == 'view') {
                    $post_data = array(
                        't' => $token,
                        'item_id' => $_POST['id'],
                    );
                    $preparats_details = server_data($this->config->item('api_url') . 'constructor/preparats_details.php', $post_data);
                    $data['preparats_details'] = $preparats_details['response']['data'];
                    $notifies[] = $preparats_details['result'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('preparats_modals', $data);
                $this->template->load_file('constructor/preparats_modals');
            }
        } else {
            $this->error_404();
        }
    }
    
    function preparats_add_action() {
        if (isset($_POST['preparats_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'categ_id' => $this->input->post('categ_id'),
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr'),
            );
            
            $preparats_add = server_data($this->config->item('api_url') . 'constructor/preparats_add.php', $post_data);
            $notifies[] = $preparats_add['result'];
            
            if (is_not($preparats_add['result']['errors'])) {
                if ($preparats_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $preparats_add['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/preparats'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function preparats_edit_action() {
        if (isset($_POST['preparats_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'item_id' => $this->input->post('id'),
                'categ_id' => $this->input->post('categ_id'),
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr'),
            );
            $preparats_edit = server_data($this->config->item('api_url') . 'constructor/preparats_edit.php', $post_data);
            $notifies[] = $preparats_edit['result'];;

            if (is_not($preparats_edit['result']['errors'])) {
                if ($preparats_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $preparats_edit['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/preparats'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }

    function exercises() {
        $privileges = $this->template->get_data('privileges');
        
        if (in_array('products_view', $privileges['privileges'])) {
            $data['section'] = 'exercises';
            
            $data['search'] = array(
                'search_query' => $this->input->cookie('search_query_exercises'),
                'filter' => $this->session->userdata('filter'),
                'order' => $this->session->userdata('order'),
            );

            $name = 'Упражнения';
            
            set_title($name, '|', true);
            set_pagename($name, 'male');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'url' => 'constructor',
                        'name' => 'Конструктор',
                        'submenu' => $this->_constructor_list
                    ),
                    array(
                        'name' => $name,
                    )
                )
            );
            $this->template->set_data('constructor_loader', $data);
            $this->template->load_file('constructor/constructor_loader');
        } else {
            $this->error_403();
        };
    }

    function exercises_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'view') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'add') {
                    $post_data = array(
                        't' => $token
                    );
                    
                    $exercises_categs_list = server_data($this->config->item('api_url') . 'constructor/exercises_categs_list.php', $post_data);
                    $data['exercises_categs_list'] = $exercises_categs_list['response']['data'];
                    $notifies[] = $exercises_categs_list['result'];
        
                    $exercises_places_list = server_data($this->config->item('api_url') . 'constructor/exercises_places_list.php', $post_data);
                    $data['exercises_places_list'] = $exercises_places_list['response']['data'];
                    $notifies[] = $exercises_places_list['result'];
                }
                
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'exercise_id' => $this->input->post('id')
                    );
                    
                    $exercises_details = server_data($this->config->item('api_url') . 'constructor/exercises_details.php', $post_data);
                    $data['exercises_details'] = $exercises_details['response']['data'];
                    $notifies[] = $exercises_details['result'];
                    
                    $exercises_categs_list = server_data($this->config->item('api_url') . 'constructor/exercises_categs_list.php', $post_data);
                    $data['exercises_categs_list'] = $exercises_categs_list['response']['data'];
                    $notifies[] = $exercises_categs_list['result'];
        
                    $exercises_places_list = server_data($this->config->item('api_url') . 'constructor/exercises_places_list.php', $post_data);
                    $data['exercises_places_list'] = $exercises_places_list['response']['data'];
                    $notifies[] = $exercises_places_list['result'];
                }
                
                if ($this->input->post('action') == 'view') {
                    $post_data = array(
                        't' => $token,
                        'exercise_id' => $this->input->post('id')
                    );
                    $exercises_details = server_data($this->config->item('api_url') . 'constructor/exercises_details.php', $post_data);
                    $notifies[] = $exercises_details['result'];
                    $data['exercises_details'] = $exercises_details['response']['data'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('exercises_modals', $data);
                $this->template->load_file('constructor/exercises_modals');
            }
        } else {
            $this->error_404();
        }
    }
    
    function exercises_add_action() {
        if (isset($_POST['exercises_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'exercise_id' => $this->input->post('id'),
                'categ_id' => $this->input->post('categ_id'),
                'place_id' => $this->input->post('place_id'),
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr'),
                'youtube' => $this->input->post('youtube'),
            );
            
            $exercises_add = server_data($this->config->item('api_url') . 'constructor/exercises_add.php', $post_data);
            $notifies[] = $exercises_add['result'];
            
            if (is_not($exercises_add['result']['errors'])) {
                if ($exercises_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $exercises_add['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/exercises'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }

    function exercises_edit_action() {
        if (isset($_POST['exercises_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'exercise_id' => $this->input->post('id'),
                'categ_id' => $this->input->post('categ_id'),
                'place_id' => $this->input->post('place_id'),
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr'),
                'youtube' => $this->input->post('youtube'),
            );
            $exercises_edit = server_data($this->config->item('api_url') . 'constructor/exercises_edit.php', $post_data);
            $notifies[] = $exercises_edit['result'];;

            if (is_not($exercises_edit['result']['errors'])) {
                if ($exercises_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $exercises_edit['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/exercises'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }

    function komplexes() {
        $privileges = $this->template->get_data('privileges');
        
        if (in_array('products_view', $privileges['privileges'])) {
            $data['section'] = 'komplexes';
            
            $data['search'] = array(
                'search_query' => $this->input->cookie('search_query_komplexes'),
                'filter' => $this->session->userdata('filter'),
                'order' => $this->session->userdata('order'),
            );

            $name = 'Комплексы';
            
            set_title($name, '|', true);
            set_pagename($name, 'heartbeat');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'url' => 'constructor',
                        'name' => 'Конструктор',
                        'submenu' => $this->_constructor_list
                    ),
                    array(
                        'name' => $name,
                    )
                )
            );
            $this->template->set_data('constructor_loader', $data);
            $this->template->load_file('constructor/constructor_loader');
        } else {
            $this->error_403();
        };
    }
    
    function komplexes_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'view') {
                $token = $this->session->userdata('token');

                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'komplex_id' => $this->input->post('id')
                    );                    
                    $komplexes_details = server_data($this->config->item('api_url') . 'constructor/komplexes_details.php', $post_data);
                    $data_messages[] = $komplexes_details['result'];
                    $data['komplexes_details'] = $komplexes_details['response']['data'];
                }
                
                if ($this->input->post('action') == 'view') {
                    $post_data = array(
                        't' => $token,
                        'komplex_id' => $_POST['id'],
                    );
                    $komplexes_details = server_data($this->config->item('api_url') . 'constructor/komplexes_details.php', $post_data);
                    $data_messages[] = $komplexes_details['result'];
                    $data['komplexes_details'] = $komplexes_details['response']['data'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('komplexes_modals', $data);
                $this->template->load_file('constructor/komplexes_modals');
            }
        } else {
            $this->error_404();
        }
    }
    
    function komplexes_add_action() {
        if (isset($_POST['komplexes_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr'),
                'youtube' => $this->input->post('youtube'),
                'exercises_list' => json_encode($this->input->post('exercises_list')),
            );
            $komplexes_add = server_data($this->config->item('api_url') . 'constructor/komplexes_add.php', $post_data);
            $notifies[] = $komplexes_add['result'];

            if (is_not($komplexes_add['result']['errors'])) {
                if ($komplexes_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $komplexes_add['result']['notifies']);
                }

                $response = array(
                    'redirect' => get_info('site_url') . '/komplexes'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }
    
    function komplexes_edit_action() {
        if (isset($_POST['komplexes_edit_submit'])) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'komplex_id' => $this->input->post('id'),
                'caption' => $this->input->post('caption'),
                'descr' => $this->input->post('descr'),
                'youtube' => $this->input->post('youtube'),
                'exercises_list' => json_encode($this->input->post('exercises_list')),
            );
            $komplexes_edit = server_data($this->config->item('api_url') . 'constructor/komplexes_edit.php', $post_data);
            $notifies[] = $komplexes_edit['result'];

            if (is_not($komplexes_edit['result']['errors'])) {
                if ($komplexes_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $komplexes_edit['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/komplexes'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }  
    }

    function constructor_replacement() {
        if ($this->input->is_ajax_request()) {
            $this->template->load_file('constructor/constructor_replacement');
        } else {
            $this->error_404();
        }
    }
    
    function constructor_remove_check() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('section') == 'products') {
                $token = $this->session->userdata('token');
                
                $post_data = array(
                    't' => $token,
                    'product_id' => $this->input->post('id')
                );
                $products_details = server_data($this->config->item('api_url') . 'constructor/products_details.php', $post_data);
                $notifies[] = $products_details['result'];
                $data['products_list'] = $products_details['response']['data'];
                
                if (is_not($data['products_list']['related_dishes']) && is_not($data['products_list']['related_events'])) {
                    echo json_encode(TRUE);
                } else {
                    echo json_encode(FALSE);
                }
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            } else if ($this->input->post('section') == 'dishes') {
                $token = $this->session->userdata('token');
                
                $post_data = array(
                    't' => $token,
                    'dish_id' => $this->input->post('id')
                );
                $dishes_details = server_data($this->config->item('api_url') . 'constructor/dishes_details.php', $post_data);
                $notifies[] = $dishes_details['result'];
                $data['dishes_details'] = $dishes_details['response']['data'];
                
                if (is_not($data['dishes_details']['related_events'])) {
                    echo json_encode(TRUE);
                } else {
                    echo json_encode(FALSE);
                }
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            } else if ($this->input->post('section') == 'preparats') {
                $token = $this->session->userdata('token');
                
                $post_data = array(
                    't' => $token,
                    'item_id' => $this->input->post('id')
                );
                $preparats_details = server_data($this->config->item('api_url') . 'constructor/preparats_details.php', $post_data);
                $notifies[] = $preparats_details['result'];
                $data['preparats_details'] = $preparats_details['response']['data'];
                
                if (is_not($data['preparats_details']['related_events'])) {
                    echo json_encode(TRUE);
                } else {
                    echo json_encode(FALSE);
                }
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            } else if ($this->input->post('section') == 'exercises') {
                $token = $this->session->userdata('token');
                
                $post_data = array(
                    't' => $token,
                    'exercise_id' => $this->input->post('id')
                );
                $exercises_details = server_data($this->config->item('api_url') . 'constructor/exercises_details.php', $post_data);
                $notifies[] = $exercises_details['result'];
                $data['exercises_details'] = $exercises_details['response']['data'];

                if (is_not($data['exercises_details']['related_komplexes']) && is_not($data['exercises_details']['related_events'])) {
                    echo json_encode(TRUE);
                } else {
                    echo json_encode(FALSE);
                }
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            } else if ($this->input->post('section') == 'komplexes') {
                $token = $this->session->userdata('token');
                
                $post_data = array(
                    't' => $token,
                    'komplex_id' => $this->input->post('id')
                );
                $komplexes_details = server_data($this->config->item('api_url') . 'constructor/komplexes_details.php', $post_data);
                $notifies[] = $komplexes_details['result'];
                $data['komplexes_details'] = $komplexes_details['response']['data'];
                
                if (is_not($data['komplexes_details']['related_events'])) {
                    echo json_encode(TRUE);
                } else {
                    echo json_encode(FALSE);
                }
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function constructor_remove() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            if ($this->input->post('section') == 'products') {
                $post_data = array(
                    't' => $token,
                    'product_id' => $this->input->post('id')
                );
                if (isset($_POST['replacement_id'])) {
                    $post_data['replacement_id'] = $this->input->post('replacement_id');
                }
                $products_del = server_data($this->config->item('api_url') . 'constructor/products_del.php', $post_data);
                $notifies[] = $products_del['result'];  

                if (is_not($products_del['result']['errors'])) {
                    if ($products_del['result']['success'] == 1) {
                        $this->session->set_flashdata('success', $products_del['result']['notifies']);
                    }
                    echo json_encode(get_info('site_url') . '/products');
                } else {
                    if (is($notifies)) {
                        $this->template->set_data('notifies', $notifies);
                        $this->template->load_file('notifies');
                    }
                }  
            } else if ($this->input->post('section') == 'dishes') {
                $post_data = array(
                    't' => $token,
                    'dish_id' => $this->input->post('id')
                );
                if (isset($_POST['replacement_id'])) {
                    $post_data['replacement_id'] = $this->input->post('replacement_id');
                }
                $dishes_del = server_data($this->config->item('api_url') . 'constructor/dishes_del.php', $post_data);
                $notifies[] = $dishes_del['result'];  

                if (is_not($dishes_del['result']['errors'])) {
                    if ($dishes_del['result']['success'] == 1) {
                        $this->session->set_flashdata('success', $dishes_del['result']['notifies']);
                    }
                    echo json_encode(get_info('site_url') . '/dishes');
                } else {
                    if (is($notifies)) {
                        $this->template->set_data('notifies', $notifies);
                        $this->template->load_file('notifies');
                    }
                }  
            } else if ($this->input->post('section') == 'preparats') {
                $post_data = array(
                    't' => $token,
                    'item_id' => $this->input->post('id')
                );
                if (isset($_POST['replacement_id'])) {
                    $post_data['replacement_id'] = $this->input->post('replacement_id');
                }
                $preparats_del = server_data($this->config->item('api_url') . 'constructor/preparats_del.php', $post_data);
                $notifies[] = $preparats_del['result'];  

                if (is_not($preparats_del['result']['errors'])) {
                    if ($preparats_del['result']['success'] == 1) {
                        $this->session->set_flashdata('success', $preparats_del['result']['notifies']);
                    }
                    echo json_encode(get_info('site_url') . '/preparats');
                } else {
                    if (is($notifies)) {
                        $this->template->set_data('notifies', $notifies);
                        $this->template->load_file('notifies');
                    }
                }  
            } else if ($this->input->post('section') == 'exercises') {
                $post_data = array(
                    't' => $token,
                    'exercise_id' => $this->input->post('id')
                );
                if (isset($_POST['replacement_id'])) {
                    $post_data['replacement_id'] = $this->input->post('replacement_id');
                }
                $exercises_del = server_data($this->config->item('api_url') . 'constructor/exercises_del.php', $post_data);
                $notifies[] = $exercises_del['result'];  

                if (is_not($exercises_del['result']['errors'])) {
                    if ($exercises_del['result']['success'] == 1) {
                        $this->session->set_flashdata('success', $exercises_del['result']['notifies']);
                    }
                    echo json_encode(get_info('site_url') . '/exercises');
                } else {
                    if (is($notifies)) {
                        $this->template->set_data('notifies', $notifies);
                        $this->template->load_file('notifies');
                    }
                }  
            } else if ($this->input->post('section') == 'komplexes') {
                $post_data = array(
                    't' => $token,
                    'komplex_id' => $this->input->post('id')
                );
                if (isset($_POST['replacement_id'])) {
                    $post_data['replacement_id'] = $this->input->post('replacement_id');
                }
                $komplexes_del = server_data($this->config->item('api_url') . 'constructor/komplexes_del.php', $post_data);
                $notifies[] = $komplexes_del['result'];  

                if (is_not($komplexes_del['result']['errors'])) {
                    if ($komplexes_del['result']['success'] == 1) {
                        $this->session->set_flashdata('success', $komplexes_del['result']['notifies']);
                    }
                    echo json_encode(get_info('site_url') . '/komplexes');
                } else {
                    if (is($notifies)) {
                        $this->template->set_data('notifies', $notifies);
                        $this->template->load_file('notifies');
                    }
                }  
            }
        } else {
            $this->error_404();
        }
    }











    
    
    
    
    
    
    
    
    
    
    

    
    


    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     *  *************************************************************************
     *  suppliers
     *  *************************************************************************
     */
    function suppliers() {
        $privileges = $this->template->get_data('privileges');
        
        if (in_array('suppliers_view', $privileges['privileges'])) {
            $name = 'Поставщики';
                    
            set_title($name, '|', true);
            set_pagename($name, 'user-md');
            set_breadcrumbs(
                array(
                    $this->_general_list,
                    array(
                        'name' => $name,
                    )
                )
            );
            
            $this->template->load_file('suppliers/suppliers');
        } else {
            $this->error_403();
        }
    }
    
    function suppliers_loader() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'filter_string' => $this->input->post('filter_string'),
            );
            
            $suppliers_list = server_data($this->config->item('api_url') . 'suppliers/list.php', $post_data);
            $notifies[] = $suppliers_list['result'];
            $data['suppliers_list'] = $suppliers_list['response']['data'];

            if (is_not($suppliers_list['result']['errors'])) {
                $this->template->set_data('suppliers_loader', $data);
                $this->template->load_file('suppliers/suppliers_loader');
            } else {
                $this->template->set_data('notifies', $notifies);
                $this->template->load_file('notifies');
            }
        } else {
            $this->error_404();
        }
    }
    
    function suppliers_modals() {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post('action') == 'add' || $this->input->post('action') == 'edit' || $this->input->post('action') == 'remove') {
                $token = $this->session->userdata('token');
                
                if ($this->input->post('action') == 'edit') {
                    $post_data = array(
                        't' => $token,
                        'supplier_id' => $this->input->post('id')
                    );
                    $suppliers_details = server_data($this->config->item('api_url') . 'suppliers/details.php', $post_data);
                    $data['supplier'] = $suppliers_details['response']['data'];
                    $notifies[] = $suppliers_details['result'];
                }
                if ($this->input->post('action') == 'remove') {
                    $post_data = array(
                        't' => $token,
                        'supplier_id' => $this->input->post('id')
                    );
                    $suppliers_details = server_data($this->config->item('api_url') . 'suppliers/details.php', $post_data);
                    $data['supplier'] = $suppliers_details['response']['data'];
                    $notifies[] = $suppliers_details['result'];
                }
                
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
                $this->template->set_data('suppliers_modals', $data);
                $this->template->load_file('suppliers/suppliers_modals');
            }
        } else {
            $this->error_404();
        }
    }
    
    function suppliers_add_action() {
        if (isset($_POST['suppliers_add_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'surname' => $this->input->post('surname'),
                'name' => $this->input->post('name'),
                'patronymic' => $this->input->post('patronymic'),
                'phone' =>$this->input->post('phone'),
                'email' => $this->input->post('email'),
                'skype' => $this->input->post('skype'),
                'sex_id' => $this->input->post('sex_id'),
                'comment' => $this->input->post('comment')
            );
            $suppliers_add = server_data($this->config->item('api_url') . 'suppliers/add.php', $post_data);
            $notifies[] = $suppliers_add['result'];

            if (is_not($suppliers_add['result']['errors'])) {
                if ($suppliers_add['result']['success'] == true) {
                    $this->session->set_flashdata('success', $suppliers_add['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => get_info('site_url') . '/suppliers'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function suppliers_edit_action() {
        if (isset($_POST['suppliers_edit_submit'])) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'supplier_id' => $this->input->post('id'),
                'surname' => $this->input->post('surname'),
                'name' => $this->input->post('name'),
                'patronymic' => $this->input->post('patronymic'),
                'phone' =>$this->input->post('phone'),
                'email' => $this->input->post('email'),
                'skype' => $this->input->post('skype'),
                'sex_id' => $this->input->post('sex_id'),
                'comment' => $this->input->post('comment')
            );
            $suppliers_edit = server_data($this->config->item('api_url') . 'suppliers/edit.php', $post_data);
            $notifies[] = $suppliers_edit['result'];

            if (is_not($suppliers_edit['result']['errors'])) {
                if ($suppliers_edit['result']['success'] == true) {
                    $this->session->set_flashdata('success', $suppliers_edit['result']['notifies']);
                }
                                
                $response = array(
                    'redirect' => get_info('site_url') . '/suppliers'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    function suppliers_remove_check() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');
            
            $post_data = array(
                't' => $token,
                'supplier_id' => $this->input->post('id')
            );
            $suppliers_details = server_data($this->config->item('api_url') . 'suppliers/details.php', $post_data);
            $data['supplier'] = $suppliers_details['response']['data'];
            $notifies[] = $suppliers_details['result'];
            
            if (is_not($data['supplier']['clients'])) {
                $this->output->append_output(json_encode(TRUE));
            } else {
                $this->output->append_output(json_encode(FALSE));
            }
            if (is($notifies)) {
                $this->template->set_data('notifies', $notifies);
                $this->template->load_file('notifies');
            }
        } else {
            $this->error_404();
        }
    }
    
    function suppliers_remove_action() {
        if ($this->input->is_ajax_request()) {
            $token = $this->session->userdata('token');

            $post_data = array(
                't' => $token,
                'supplier_id' => $this->input->post('id')
            );

            $suppliers_delete = server_data($this->config->item('api_url') . 'suppliers/delete.php', $post_data);
            $notifies[] = $suppliers_delete['result'];  

            if (is_not($suppliers_delete['result']['errors'])) {
                if ($suppliers_delete['result']['success'] == 1) {
                    $this->session->set_flashdata('success', $suppliers_delete['result']['notifies']);
                }
                
                $response = array(
                    'redirect' => get_info('site_url') . '/suppliers'
                );
                $this->output->append_output(json_encode($response));
            } else {
                if (is($notifies)) {
                    $this->template->set_data('notifies', $notifies);
                    $this->template->load_file('notifies');
                }
            }
        } else {
            $this->error_404();
        }
    }
    
    
}