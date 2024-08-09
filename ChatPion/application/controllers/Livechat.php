<?php

require_once("Home.php"); // loading home controller

class livechat extends Home
{

    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in') != 1)
        redirect('home/login_page', 'location');   
        if($this->session->userdata('user_type') != 'Admin' && !in_array(82,$this->module_access))
        redirect('home/login_page', 'location'); 

        if($this->session->userdata("facebook_rx_fb_user_info")==0)
        redirect('social_accounts/index','refresh');
    
        $this->load->library("fb_rx_login");
        $this->load->helper("bot_helper");
        $this->important_feature();
        $this->member_validity();        
    }

    public function load_livechat()
    {
        $this->config->load('pusher');
        $pusher_status = !empty( $this->config->item("pusher_app_key")) ? '1' : '0';
        $media_type = $this->input->get('media_type') ?? 'fb';
        $message_type = $this->input->get('message') ?? 'all';
        if(empty($message_type)) $message_type = 'all';

        $this->db->from('facebook_rx_fb_page_info');
        $this->db->where('facebook_rx_fb_page_info.user_id', $this->user_id);
        $this->db->where('facebook_rx_fb_page_info.bot_enabled', '1');
        $this->db->where('facebook_rx_fb_user_info_id', $this->session->userdata('facebook_rx_fb_user_info'));
        if(!empty($this->team_allowed_pages)){
            $this->db->where_in('facebook_rx_fb_page_info.id', $this->team_allowed_pages);
        }
        if($media_type == 'ig'){
            $this->db->where('has_instagram', '1');
        }

        $bot_info = $this->db->get()->result();
        $first_bot_id = '';
        if($media_type == 'fb'){
            if($this->session->userdata('selected_global_page_table_id_fb')){
                $first_bot_id = $this->session->userdata('selected_global_page_table_id_fb');
            }
            if(empty($first_bot_id)) $first_bot_id = $bot_info[0]->id ?? '';
            if(empty($first_bot_id)) {
                $first_bot_id = $bot_info[0]->id ?? '';
                $this->session->set_userdata('selected_global_page_table_id_fb',$first_bot_id);
                $this->session->set_userdata('bot_manager_get_bot_details_tab_menu_id_messanger','v-pills-bot-settings-tab');
            }
        }
        else{
            if($this->session->userdata('selected_global_page_table_id_ig')){
                $first_bot_id = $this->session->userdata('selected_global_page_table_id_ig');
            }
            if(empty($first_bot_id)) $first_bot_id = $bot_info[0]->id ?? '';
            if(empty($first_bot_id)) {
                $first_bot_id = $bot_info[0]->id ?? '';
                $this->session->set_userdata('selected_global_page_table_id_ig',$first_bot_id);
                $this->session->set_userdata('bot_manager_get_bot_details_tab_menu_id_messanger','v-pills-bot-settings-tab');
            }
        }

        $data['bot_info'] = $bot_info;

        $first_bot_name = '';
        foreach ($bot_info as $key=>$value){
            if($value->facebook_rx_fb_user_info_id == $first_bot_id){
                if($media_type == 'ig'){
                    $first_bot_name = $value->insta_username;
                    break;
                }
                else{
                $first_bot_name = $value->page_name;
                break;
                }
            }
        }
        $where = array();
        $select = array('id','name');
        $where['where'] = array('user_id' => $this->user_id);
        $table_name = "user_input_custom_fields";

        $custom_variables = $this->basic->get_data($table_name,$where,$select);
        if(!empty($custom_variables)) $custom_variables = json_decode(json_encode($custom_variables));
        $data['custom_variables'] = $custom_variables ?? [];
        $data['first_bot_name'] = $first_bot_name;
        $where2['where'] = array('id' => $this->user_id);
        $select2 = array('browser_notification_enabled');
        $browser_notification = $this->basic->get_data('users',$where2,$select2);
        $data['browser_notification_enabled'] = isset($browser_notification[0]['browser_notification_enabled'])?$browser_notification[0]['browser_notification_enabled'] : '0';
        $data['first_bot_id'] = $first_bot_id;
        $data['message_type'] = $message_type;
        $data['media_type'] = $media_type;
        $data['tag_list'] = $this->get_broadcast_tags($media_type);
        $data['body'] = 'livechat/livechat';
        $data['page_title'] = $media_type.' - '.$this->lang->line('Live Chat');
        //$data['postback_list'] = $first_bot_id>0 ? $this->get_dropdown_postback($first_bot_id) : '';
        $data['whatsapp_business_id'] = $first_bot_id > 0 ? $this->session->userdata('facebook_rx_fb_user_info')  : '';
        $data['load_datatable']=true;
        $data['pusher_status']=$pusher_status;
        return $this->_viewcontroller($data);
    }

    public function get_conversation_list()
    {
        $this->ajax_check();
        $media_type = $this->input->post('media_type');
        $page_id = $this->input->post('whatsapp_bot_id');
        $message_type = $this->input->post('message_type') ?? 'all';
        $start = $this->input->post('start') ?? 0;
        if($media_type == 'fb'){
            $this->session->set_userdata('selected_global_page_table_id_fb',$page_id);
            $this->session->set_userdata(['bot_manager_get_bot_details_tab_menu_id_messanger'=>'v-pills-bot-settings-tab']);
        }
        else{
            $this->session->set_userdata('selected_global_page_table_id_ig',$page_id);
            $this->session->set_userdata(['bot_manager_get_bot_details_tab_menu_id_messanger'=>'v-pills-bot-settings-tab']);
        }
        $response= $this->get_subscriber_list($page_id,$message_type,$start,$media_type);

        if(isset($response['error']))
        {
            echo '<br><div class="alert alert-danger text-center w-100"><b class="m-0">'.$response['error_message'].'</b></div>';
            exit();
        }
        else echo $response;
    }

    protected function get_subscriber_list($page_id=0,$message_type='all',$start=0,$media_type='fb'){
        if($message_type=='') $message_type = 'all';
        $media_type = $media_type;
        
        $where['where'] = array(
            'user_id' => $this->user_id,
            'facebook_rx_fb_user_info_id' => $this->session->userdata('facebook_rx_fb_user_info'),
            'bot_enabled' => '1',
            'id' => $page_id
            );
        $select = array('id','page_name','page_profile','page_id as fb_page_id');
        $page_list = $this->basic->get_data('facebook_rx_fb_page_info',$where,$select,'','','', $order_by='page_name asc');
        if(empty($page_list))
        {
            echo '<br><div class="alert alert-danger text-center w-100"><b class="m-0">'.$this->lang->line("You do not have any bot enabled page").'</b></div>';
            exit();
        }
            
        $this->db->select('*');
        $this->db->from('messenger_bot_subscriber');
        $this->db->where(['page_table_id' => $page_id, 'user_id' => $this->user_id, 'social_media'=>$media_type]);

        if ($message_type == 'unread') {
            $this->db->where('unseen_count >', 0);
        } elseif ($message_type == 'archived') {
            $this->db->where('is_archived', '1');
        } elseif ($message_type == 'mine') {
            // $user_id = $this->is_manager ? $this->manager_id : $this->user_id;
            $this->db->where('assigned_used_id', $this->real_user_id);
        } else {
            $this->db->where('is_archived !=', '1');
        }

        $this->db->order_by('last_communicated_at', 'DESC');
        $this->db->limit(20, $start);

        $subscriber_list = $this->db->get()->result();

        $str = '';
        foreach($subscriber_list as $conversion_info){
            $str .= $this->display_subscriber_list($page_id,$conversion_info,$media_type);
        }
        return $str;
    }

    protected function display_subscriber_list($whatsapp_bot_id=null,$conversion_info=null,$media_type=''){
        $first_name = $conversion_info->first_name ?? '';
        $last_name = $conversion_info->last_name ?? '';
        $username = $conversion_info->client_thread_id;
        $from_user ='';
        $media_type = $media_type;
        if($media_type == 'fb'){
            $from_user =trim($first_name.' '.$last_name) ;
        }
        elseif($media_type == 'ig'){
            $from_user = $conversion_info->full_name ?? '';
        }

        $from_user_id = $conversion_info->subscribe_id  ?? null;
        $thread_id = $conversion_info->page_id ?? '';

        $message_preview = format_preview_message($conversion_info->last_conversation_message,40);
        $rand = rand(1,6);
        // $default = base_url('assets/img/avatar/avatar-6.png');
        $default = base_url('assets/img/avatar/avatar-'.$rand.'.png');
        $profile_pic =$conversion_info->profile_pic ?? $default;
        $subscriber_image = '<img src="'.$profile_pic.'" class="rounded-circle me-2 border" width="45" height="45">';
        //$online_icon_color = $this->is_subscriber_online($conversion_info->last_interacted_at) ? 'text-success' : 'text-warning';
        $unseen_count = $conversion_info->unseen_count ?? 0;
        $unseen_class = 'd-none';
        $unseen_message_class = 'text-muted';
        if($unseen_count>0){
            $unseen_class = '';
            $unseen_message_class = 'text-primary';
            //$online_icon_color = 'text-primary';
        }
        $display_name = !empty($from_user) ? $from_user : $username;
        $display_name2 = !empty($from_user) ? $from_user : "+".$username;

        $str ='
            <li class="media py-2 px-1 my-0 ps-3 pe-2 open_conversation d-flex pl-3" thread_id="'.$thread_id.'" from_user="'.htmlspecialchars($display_name2).'" from_user_id="'.$from_user_id.'" whatsapp_bot_id="'.$whatsapp_bot_id.'" data_id="'.$conversion_info->id.'" style="cursor:pointer">
                '.$subscriber_image.'
                <div class="media-body w-100 pl-2">
                  <div class="mt-0 pt-1 ">'.$display_name.'<span class="put-time text-sm-right text-wrap float-right"><small>'.display_message_time($conversion_info->last_communicated_at).'</small></span></div>
                  <div class="text-sm font-600-bold"><span class="put-message-preview '.$unseen_message_class.' "><small>'.$message_preview.'</small></span> <span class="badge bg-primary rounded-pill me-2 float-end '.$unseen_class.'">'.$unseen_count.'</span></div>
                </div>
            </li>';
        return $str;
    }


    public function search_subscriber(){
        $this->ajax_check();
        $page_id = $this->input->post('whatsapp_bot_id');
        $search_value = $this->input->post('filter');
        $search_columns = array('first_name','subscriber_id');

        $select = '*';
        $where = ['facebook_rx_fb_user_info_id' => $page_id, 'user_id' => $this->user_id];

        $this->db->select($select);
        $this->db->from('messenger_bot_subscriber');
        $this->db->where($where);

        if ($search_value != '') {
            $this->db->group_start();
            foreach ($search_columns as $key => $value) {
                $this->db->or_like($value, $search_value);
            }
            $this->db->group_end();
        }

        $this->db->order_by('last_communicated_at', 'DESC');
        $subscriber_list = $this->db->get()->result();


        $str = '';
        foreach($subscriber_list as $conversion_info){
            $str .= $this->display_subscriber_list($page_id,$conversion_info);
        }
        echo $str;
    }

    public function get_conversation_single()
    {
        $this->ajax_check();
        $from_user_id = $this->input->post('from_user_id'); // subscriber_id
        $thread_id = $this->input->post('thread_id');
        $messageStart = $this->input->post('messageStart') ?? 0;
        $page_id = $this->input->post('whatsapp_bot_id');
        $last_message_id = $this->input->post('last_message_id');
        $conversations = null;
        // dd($messageStart);
        $this->db->select('*');
        $this->db->from('livechat_messages');
        $this->db->where('subscriber_id ', $from_user_id);

        if (!empty($last_message_id)) {
            $this->db->where('id >', $last_message_id);
        }

        $this->db->order_by('id', 'desc');
        // $this->db->limit(10 ,$messageStart);
        $this->db->limit(100);
        $conversation = $this->db->get()->result();
        $conversation = array_reverse($conversation);

        if(!check_module_action_access($module_id=82,$actions=2)){
            echo '<br><div class="alert alert-danger text-center w-100"><b class="m-0">'.$this->lang->line("You do not have any access").'</b></div>';
            exit();
        }

        $this->basic->update_data('messenger_bot_subscriber',array("subscribe_id"=>$from_user_id),array("unseen_count"=>'0'));
        $str = '';
        foreach($conversation as $key=>$value)
        {
            $conversation_time = $value->conversation_time;
            $formattedDate = display_message_time($conversation_time);

            $agent_name_str = $value->agent_name!='' ? '-'.$value->agent_name : '-Bot';

            if($value->sender == 'bot'){
                $str .= display_sent_message($page_id,$value->fb_page_id,$value->message_content,$value->id,$formattedDate,$value->agent_name,true,$value->message_status,$value->delivery_status_updated_at);
            }
            else if($value->sender == 'system'){
                $str .= '<div class="chat-item chat-center text-center" >
                            <div class="chat-details mr-0 ml-0 system_message_text" message_id="'.$page_id.'">
                                '.$value->message_content.' <sub class="text-muted" id="system_message_date">'.$formattedDate.$agent_name_str.'</sub>
                            </div>
                        </div>';
            }
            else {
                $str .= display_received_message($page_id,$value->fb_page_id,$value->message_content,$value->id,$formattedDate,true,$value->message_status,$value->delivery_status_updated_at);
            }

        }
        echo $str;
    }

    public function reply_to_conversation()
    {
        $this->ajax_check();
        $insert_data = [];
        $media_type = $this->input->post('media_type');
        $message_tag = $this->input->post('message_tag') ?? 'HUMAN_AGENT';
        $api_call = $this->input->post('thread_id') ? false : true;
        $page_id = $this->input->post('thread_id') ? $this->input->post('thread_id') : null;
        $thread_id = $this->input->post('thread_id') ? $this->input->post('thread_id') : urldecode($this->input->post('sendToPhoneNumber')); // called from livechat or api
        $select_subscriber = ['id','last_subscriber_interaction_time','full_name'];
        if ($this->input->post('whatsapp_bot_id')) {
            $whatsapp_bot_id = $this->input->post('whatsapp_bot_id'); // called from livechat
        } else {
            // called from API
            $phone_number_id = urldecode($this->input->post('phoneNumberID'));
            $bot_data = $this->get_bot_whatsapp_by_phone_number_id($phone_number_id, ['id', 'access_token', 'phone_number_id'], $this->user_id);
            $whatsapp_bot_id = isset($bot_data->id) ? $bot_data->id : null;
        }
        

        if($this->input->post('from_user_id')) $from_user_id = $this->input->post('from_user_id'); // subscriber_id
        else {  // called from api
            $thread_id = preg_replace('/[^0-9]/i', '', $thread_id);
            $from_user_id = $thread_id.'-'.$whatsapp_bot_id;

            $this->db->select($select_subscriber);
            $this->db->from('messenger_bot_subscriber');
            $this->db->where(['user_id' => $this->user_id, 'subscribe_id' => $from_user_id,'social_media'=>$media_type]);
            $subscriber_data = $this->db->get()->row();

            if(empty($subscriber_data)){
                // $limit_exceed = $this->check_subscriber_limit("1",$this->user_id);
                // if($limit_exceed) {
                //     $response = array(
                //         'status' => '0',
                //         'message' => $this->lang->line('Subscriber limit has been exceeded. You cannot have more subscribers.')
                //     );
                //    echo json_encode($response); 
                //    exit; 
                // }
                $curdate = date('Y-m-d H:i:s');
                $insert_data['facebook_rx_fb_user_info_id '] = $whatsapp_bot_id;
                $insert_data['user_id'] = $this->user_id;
                $insert_data['subscriber_id'] = $from_user_id;
                $insert_data['chat_id'] = $thread_id;
                $insert_data['last_estimaed_at'] = $curdate;
            }
        }

        if (empty($subscriber_data)) {
            $this->db->select($select_subscriber);
            $this->db->from('messenger_bot_subscriber');
            $this->db->where(['user_id' => $this->user_id, 'subscribe_id' => $from_user_id]);
            $subscriber_data = $this->db->get()->row();
        }

        $last_interacted_at = $subscriber_data->last_subscriber_interaction_time ?? null;
        $allowed_time_limit = !empty($last_interacted_at) ? date('Y-m-d H:i:s', strtotime($last_interacted_at. ' + 1430 minutes')) : null;
        $allowed_time_limit_human = !empty($last_interacted_at) ? date('Y-m-d H:i:s', strtotime($last_interacted_at. ' + 10050 minutes')) : null;
        if($message_tag !='ACCOUNT_UPDATE' && $message_tag !='CONFIRMED_EVENT_UPDATE' && $message_tag !='POST_PURCHASE_UPDATE'){
            if($message_tag =='NON_PROMOTIONAL'){
                if(empty($allowed_time_limit) || strtotime($allowed_time_limit) < strtotime(date('Y-m-d H:i:s'))){
                    $time_error = $this->lang->line('Sending message outside 24 hour window is not allowed #NON_PROMOTIONAL#');
                    if($api_call) {
                        $response = array(
                            'status' => '0',
                            'message' => $time_error
                        );
                    echo json_encode($response);
                    exit;
                    }
                    else {
                        echo "<div class='alert alert-danger text-center'>".$time_error."</div>";
                        exit;
                    }
                }
            }
            
            elseif($message_tag =='HUMAN_AGENT'){
                if(empty($allowed_time_limit_human) || strtotime($allowed_time_limit_human) < strtotime(date('Y-m-d H:i:s'))){
                    $time_error = $this->lang->line('Sending message outside 7 days window is not allowed for #HUMAN_AGENT#');
                    if($api_call) {
                        $response = array(
                            'status' => '0',
                            'message' => $time_error
                        );
                    echo json_encode($response);
                    exit;
                    }
                    else {
                        echo "<div class='alert alert-danger text-center'>".$time_error."</div>";
                        exit;
                    }
                }
            }
        }
        // called from livechat or api
        $reply_message = $this->input->post('reply_message') ? $this->input->post('reply_message') : urldecode($this->input->get('message'));
        $reply_message = str_replace(['#SUBSCRIBER_ID_REPLACE#','%23SUBSCRIBER_ID_REPLACE%23'], $from_user_id, $reply_message);
        $reply_message = str_replace(['#LEAD_USER_FIRST_NAME#','%23LEAD_USER_FIRST_NAME%23'], $subscriber_data->first_name ?? '', $reply_message);
        $reply_message = str_replace(['#LEAD_USER_LAST_NAME#','%23LEAD_USER_LAST_NAME%23'], $subscriber_data->username??'', $reply_message);

        //get custom fields & value & replace with the actual value.
        $where['where'] = array("subscriber_id "=>$from_user_id);
        // $where['where'] = array("subscriber_id "=>$from_user_id,'id' => $whatsapp_bot_id);
        $join = array('user_input_custom_fields'=>"user_input_custom_fields.id=user_input_custom_fields_assaign.custom_field_id ,left");   
        $custom_field_info = $this->basic->get_data('user_input_custom_fields_assaign',$where,array('user_input_custom_fields.name','user_input_custom_fields_assaign.custom_field_value'),$join);

        $custom_replace_search=array();
        $custom_replace_with=array();
        foreach($custom_field_info as $variable){
            $temp_name = $variable['name'] ?? '';
            $custom_replace_search[] = "#".$temp_name."#";
            $custom_replace_with[] = $variable['custom_field_value'] ?? '';
        }

        $reply_message = str_replace($custom_replace_search, $custom_replace_with, $reply_message);

        if(!isset($bot_data)) $bot_data = $this->get_bot($whatsapp_bot_id,['page_access_token'],$this->user_id,$media_type);

        $message = array
        (
            'recipient' =>array('id'=>$from_user_id),
            'message'=>array('text'=>$reply_message),
            'tag'=>$message_tag
        );
        $message = json_encode($message);
        $post_access_token = $bot_data->page_access_token;
        $response = $this->fb_rx_login->send_non_promotional_message_subscription($message,$post_access_token);

        if(isset($response['error']))
        {
            $error_msg = $this->get_api_error_message($response);
            if($api_call) {
                $response = array(
                    'status' => '0',
                    'message' => $error_msg
                );
               echo json_encode($response);
               exit;
            }
            else echo "<div class='alert alert-danger text-center'>".$error_msg."</div>";
        }
        else
        {
            // if(!empty($insert_data)) DB::table("whatsapp_bot_subscribers")->insert($insert_data);
            $message_id = $response['message_id'] ?? null;
            $insert_livechat_data = [
                'subscriber_id' => $from_user_id,
                'page_table_id' => $whatsapp_bot_id,
                'fb_page_id' => $page_id,
                'user_id' => $this->user_id,
                'sender' => 'bot',
                'platform' => $media_type,
                'message_content' => $message,
                'fb_message_id '=>$message_id
            ];
            $message_id = $this->insert_livechat_data($insert_livechat_data);

            if($api_call) {
                $response = array(
                    'status' => '1',
                    'message' => $this->lang->line('Message sent successfully.')
                );
               echo json_encode($response);
               exit;
            }
            //else echo "Message Sent";
        }

    }

    public function send_postback_reply(){
        $this->ajax_check();
        $page_id = $this->input->post('whatsapp_bot_id');
        $subscriber_id = $this->input->post('subscriber_id');
        $postback_id = $this->input->post('postback_id');

        $subscriber_info=$this->basic->get_data("messenger_bot_subscriber",$where=array("where"=>array("subscribe_id"=>$subscriber_id)),$select="messenger_bot_subscriber.*,access_token,",$join=array('facebook_rx_fb_user_info'=>"facebook_rx_fb_user_info.id=messenger_bot_subscriber.page_id,left"));
        $last_interacted_at = $subscriber_info[0]['last_subscriber_interaction_time'] ?? null;
        $allowed_time_limit = !empty($last_interacted_at) ? date('Y-m-d H:i:s', strtotime($last_interacted_at. ' + 1430 minutes')) : null;
        if(empty($allowed_time_limit) || strtotime($allowed_time_limit) < strtotime(date('Y-m-d H:i:s'))){
            $time_error = $this->lang->line('Sending message outside 24 hour window is not allowed. You can only send template message to this user.');
            $response = array(
                'status' => '0',
                'message' =>$time_error
            );
            echo json_encode($response);
        }
        
        // $get_postback=$this->basic->get_data("messenger_bot",array('where'=>array("postback_id"=>$postback_id)));
        $where['where'] = array("postback_id"=>$postback_id,'facebook_rx_fb_page_info.bot_enabled' => '1');
        $join = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=messenger_bot.page_id,left");   
        $get_postback = $this->basic->get_data('messenger_bot',$where,array("messenger_bot.*","facebook_rx_fb_page_info.page_access_token as page_access_token","facebook_rx_fb_page_info.enable_mark_seen as enable_mark_seen","facebook_rx_fb_page_info.enbale_type_on as enbale_type_on","facebook_rx_fb_page_info.reply_delay_time as reply_delay_time"),$join,'','','messenger_bot.id asc');
        $get_postback = $get_postback[0] ?? [];

        $post_data = ['value'=>json_encode($get_postback),'sender_id'=>$subscriber_id,'subscriber_info'=>json_encode($subscriber_info),'page_id'=>$page_id];
        $url = base_url('messenger_bot/send_message_bot_reply');
        $ch = curl_init();
        $headers = array("Content-type: application/json");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
        $st=curl_exec($ch);
        if(empty($st)) $st = json_encode([
            'status'=>  '0',
            'message'=>  $this->lang->line('Something went wrong.')
        ]);
        echo $st;
    }

    public function get_dropdown_postback($page_id=0,$return=true)
    {
        if($page_id==0) {
            $page_id = $this->input->post('whatsapp_bot_id');
            $return = false;
        }

        $this->db->select('*');
        $this->db->from('messenger_bot_postback');
        $this->db->where('page_id', $page_id);
        $this->db->where('is_template', '1');
        $this->db->where('template_for', 'reply_message');
        $this->db->order_by('postback_type', 'asc');
        $query = $this->db->get();

        $postback_data = $query->result();
        
        $push_postback = '<div class="list-group">';
        foreach ($postback_data as $key => $value)
        {
            $push_postback .= '
            <a href="#" data-id="'.$value->postback_id.'" class="list-group-item list-group-item-action flex-column align-items-start postback-item item-searchable">
                <div class="d-flex w-100 justify-content-between mt-1">
                  <h6 class="mb-1"><i class="fas fa-check-circle text-success"></i> '.$this->lang->line('Send').' : '.$value->template_name.'</h6>
                </div>
            </a>';
        }
        $push_postback .=' </div>';
        if($return) return $push_postback;
        else {
            echo json_encode(['content'=>$push_postback]);
        }
    }

    public function get_postback_list_json($page_id,$media_type)
    {
        $this->ajax_check();
        $where = [
            "page_id" => (int)$page_id,
            "is_template" => "1",
            "template_for" => "reply_message",
            "media_type" => $media_type
        ];
        
        $this->db->where($where);
        $this->db->order_by('postback_type', 'asc');
        $query = $this->db->get('messenger_bot_postback');
        $postback_data = $query->result();
        echo json_encode($postback_data);
    }

    public function livechat_send_file()
    {
        $this->ajax_check();
        // if(!has_module_access($this->module_id_live_chat_advanced,$this->module_ids,$this->is_admin,$this->is_manager)) {
        //     echo "<div class='alert alert-danger text-center'>".__("Access Denied")."</div>";
        //         exit;
        // }
        $insert_data = [];
        $media_type = $this->input->post('media_type') ?? 'fb';
        $message_tag = $this->input->post('message_tag') ?? 'HUMAN_AGENT';
        $api_call = $this->input->post('thread_id') ? false : true;
        $page_id = $this->input->post('thread_id') ? $this->input->post('thread_id') : null;
        $thread_id = $this->input->post('thread_id') ? $this->input->post('thread_id') : urldecode($this->input->post('sendToPhoneNumber')); // called from livechat or api

        $select_subscriber = ['id','last_subscriber_interaction_time','full_name'];

        if ($this->input->post('whatsapp_bot_id')) {
            $whatsapp_bot_id = $this->input->post('whatsapp_bot_id'); // called from livechat
        } 
        else{ // called from api
            $phone_number_id = urldecode($this->input->post('phoneNumberID'));
            $bot_data = $this->get_bot_whatsapp_by_phone_number_id($phone_number_id,['id','access_token','phone_number_id'],$this->user_id);
            $whatsapp_bot_id = $bot_data->id ?? null;
        }

        if($this->input->post('from_user_id')) $from_user_id = $this->input->post('from_user_id'); // subscriber_id
        else {  // called from api
            $thread_id = preg_replace('/[^0-9]/i', '', $thread_id);
            $from_user_id = $thread_id.'-'.$whatsapp_bot_id;
            $this->db->select($select_subscriber);
            $this->db->from('messenger_bot_subscriber');
            $this->db->where(['user_id' => $this->user_id, 'subscribe_id' => $from_user_id]);
            $subscriber_data = $this->db->get()->row();
            if(empty($subscriber_data)){
                // $limit_exceed = $this->check_subscriber_limit("1",$this->user_id);
                // if($limit_exceed) {
                //     $response = array(
                //         'status' => '0',
                //         'message' => $this->lang->line('Subscriber limit has been exceeded. You cannot have more subscribers.')
                //     );
                //    echo json_encode($response);  
                // }
                $curdate = date('Y-m-d H:i:s');
                $insert_data['facebook_rx_fb_user_info_id '] = $whatsapp_bot_id;
                $insert_data['user_id'] = $this->user_id;
                $insert_data['subscribe_id'] = $from_user_id;
                $insert_data['chat_id'] = $thread_id;
                $insert_data['last_estimaed_at'] = $curdate;
            }
        }

        if (empty($subscriber_data)) {
            $this->db->select($select_subscriber);
            $this->db->from('messenger_bot_subscriber');
            $this->db->where(['user_id' => $this->user_id, 'subscribe_id' => $from_user_id]);
            $subscriber_data = $this->db->get()->row();
        }

        $last_interacted_at = $subscriber_data->last_subscriber_interaction_time ?? null;
        $allowed_time_limit = !empty($last_interacted_at) ? date('Y-m-d H:i:s', strtotime($last_interacted_at. ' + 1430 minutes')) : null;
        $allowed_time_limit_human = !empty($last_interacted_at) ? date('Y-m-d H:i:s', strtotime($last_interacted_at. ' + 10050 minutes')) : null;
        if($message_tag !='ACCOUNT_UPDATE' && $message_tag !='CONFIRMED_EVENT_UPDATE' && $message_tag !='POST_PURCHASE_UPDATE'){
            if($message_tag =='NON_PROMOTIONAL'){
                if(empty($allowed_time_limit) || strtotime($allowed_time_limit) < strtotime(date('Y-m-d H:i:s'))){
                    $time_error = $this->lang->line('Sending message outside 24 hour window is not allowed #NON_PROMOTIONAL#');
                    if($api_call) {
                        $response = array(
                            'status' => '0',
                            'message' => $time_error
                        );
                    echo json_encode($response);
                    exit;
                    }
                    else {
                        echo "<div class='alert alert-danger text-center'>".$time_error."</div>";
                        exit;
                    }
                }
            }
            
            elseif($message_tag =='HUMAN_AGENT'){
                if(empty($allowed_time_limit_human) || strtotime($allowed_time_limit_human) < strtotime(date('Y-m-d H:i:s'))){
                    $time_error = $this->lang->line('Sending message outside 7 days window is not allowed for #HUMAN_AGENT#');
                    if($api_call) {
                        $response = array(
                            'status' => '0',
                            'message' => $time_error
                        );
                    echo json_encode($response);
                    exit;
                    }
                    else {
                        echo "<div class='alert alert-danger text-center'>".$time_error."</div>";
                        exit;
                    }
                }
            }
        }

        // called from livechat or api
        $media_url = $this->input->post('media_url') ?? null;
        $media_name = $this->input->post('media_name') ?? '';
        $media_path = parse_url($media_url, PHP_URL_PATH);
        $extension = pathinfo($media_path, PATHINFO_EXTENSION);
        $file_type = $this->find_file_type($extension);
        $file_type = !empty($extension) ? $this->find_file_type($extension) : 'file';
        if(!isset($bot_data)) $bot_data = $this->get_bot($whatsapp_bot_id,['page_access_token']);
        $message =array();
        if(isset($media_name) && !empty($media_name)){
            $message = array
            (
                'recipient' =>array('id'=>$from_user_id),
                'message'=>array('attachment'=>array('type'=>$file_type,'payload'=>array('url'=>$media_url,'is_reusable'=>false))),
                'tag'=>$message_tag
            );
        }
        $message = json_encode($message);
        $post_access_token = $bot_data->page_access_token;
        $response = $this->fb_rx_login->send_non_promotional_message_subscription($message,$post_access_token);

        if(isset($response['error']))
        {
            $error_msg = $this->get_api_error_message($response);
            if($api_call) {
                $response = array(
                    'status' => '0',
                    'message' => $error_msg
                );
               echo json_encode($response);
            }
            else echo "<div class='alert alert-danger text-center'>".$error_msg."</div>";
        }
        else
        {
            // if(!empty($insert_data)) DB::table("whatsapp_bot_subscribers")->insert($insert_data);
            $message_id = $response['message_id'] ?? null;
            $insert_livechat_data = [
                'subscriber_id' => $from_user_id,
                'page_table_id' => $whatsapp_bot_id,
                'fb_page_id' => $page_id,
                'user_id' => $this->user_id,
                'sender' => 'bot',
                'message_content' => $message,
                'fb_message_id'=>$message_id,
                'platform'=>$media_type
            ];
            $message_id = $this->insert_livechat_data($insert_livechat_data);
            if($api_call) {
                $response = array(
                    'status' => '1',
                    'message' => $this->lang->line('Message sent successfully.')
                );
               echo json_encode($response);
            }
            //else echo "Message Sent";
        }

    }

    protected function get_bot_whatsapp_by_phone_number_id($phone_number_id='',$select=null,$user_id=0)
    {
        if($user_id==0) $user_id = $this->user_id;
        if(empty($select)) $select = '*';
        $where = ['whatsapp_bots.phone_number_id' => $phone_number_id];
        if(!empty($user_id) && $user_id>0) $where['whatsapp_bots.user_id'] = $user_id;
        // $bot_data = DB::table("whatsapp_bots")
        //     ->select($select)
        //     ->where($where)->first();
        return [];
    }

    protected function get_bot($id=0,$select=null,$user_id=0,$media_type='')
    {
        $media_type =$media_type;
        if($user_id==0) $user_id = $this->user_id;
        if(empty($select)) $select = '*';
        $where = ['facebook_rx_fb_page_info.id' => $id];
        $this->db->select($select);
        $this->db->from('facebook_rx_fb_page_info');
        $this->db->where($where);
        if (!empty($user_id) && $user_id > 0) {
            $this->db->where(['facebook_rx_fb_page_info.user_id' => $user_id]);
        }
        if($media_type == 'ig'){
            $this->db->where(['facebook_rx_fb_page_info.has_instagram' => '1']);
        }
        $bot_data = $this->db->get()->row();
        return $bot_data;
    }

    public function get_api_error_message($response=[]){
        $error = $response['error']['error_data']['details'] ?? '';
        if(empty($error)) $error = isset($response['error']['error_data']) && !is_array($response['error']['error_data']) ? $response['error']['error_data'] : '';
        if(empty($error)) $error = $response['error']['error_user_msg'] ?? '';
        if(empty($error)) $error = $response['error']['message'] ?? '';
        return $error;
    }

    private function find_file_type($extension)
    {
        $allowed_image_extensions = [
            // jpeg or jpg images
            'jpeg',
            'jpg',
            // png images
            'png',
            // gif images
            //'gif',
        ];

        $allowed_video_extensions = [
            // Video extensions
            //'flv',
            // ogv or ogg videos
            //'ogg',
            // '.webm',
            // 3gp or mts videos
            '3gpp',
            'mp4',
            // '.mkv',
            // '.mpeg',
            // '.mov',
            // '.avi',
            //'wmv',
            // '.m4v',

        ];

        $allowed_audio_extensions = [
            // Audio extensions
            'amr',
            'mp3',
            //'wav',
        ];

        $allowed_file_extensions = [
            // File extensions
            'doc',
            'docx',
            'pdf',
            'txt',
            'ppt',
            'pptx',
            'xls',
            'xlsx',
        ];

        if(in_array(strtolower($extension), $allowed_image_extensions)) {
            return 'image';
        } else if (in_array(strtolower($extension), $allowed_audio_extensions)) {
            return 'audio';
        } else if (in_array(strtolower($extension), $allowed_video_extensions)) {
            return 'video';
        } else if (in_array(strtolower($extension), $allowed_file_extensions)) {
            return 'file';
        }
        return false;
    }

    public function display_message_file($file_url=null,$media_type=null){

        if(empty($file_url) || empty($media_type)){
            $error_message = $this->lang->line('Media could not be fetched.');
            dd($error_message);
        }
        $file_url = base64_decode($file_url);
        $data = array('file_url'=>$file_url,'media_type'=>$media_type);
        if(in_array($media_type,['image','audio','video'])){
         $this->load->view('livechat/preview-file', $data);
        }
        else {
            header("Location: $file_url");
            exit;
        }

    }

    public function update_browser_permission(){
        $this->ajax_check();
        $action = $this->input->post('action') ?? "0";
        $user_id = $this->user_id;
        $insert_data['browser_notification_enabled'] = $action;
        check_module_action_access($module_id=82,$actions=2);
        $this->basic->update_data('users',array("id"=>$user_id),$insert_data);
    }

    public function update_mark_as_action(){
        $this->ajax_check();
        $action = $this->input->post('action');
        $subscriber_id = $this->input->post('subscriber_id');
        if(!empty($action) && !empty($subscriber_id)){
            $update_data = [];
            if($action=='read') $update_data = ['unseen_count'=>0];
            else if($action=='unread') $update_data = ['unseen_count' => 1];
            else if($action=='archived') $update_data = ['is_archived'=>'1','unseen_count'=>0];
            else if($action=='unarchived') $update_data = ['is_archived'=>'0'];

            check_module_action_access($module_id=82,$actions=2);
            $this->basic->update_data('messenger_bot_subscriber',array("subscribe_id"=>$subscriber_id),$update_data);
            $response = array(
                'status' => '1',
                'message' => $this->lang->line('Mark as action has been completed successfully.')
            );
           echo json_encode($response);
           exit;

        }
        $response = array(
            'status' => '0',
            'message' => $this->lang->line('Something went wrong.')
        );
       echo json_encode($response);
       exit;
    }

    public function canned_response_list()
    {
        $this->ajax_check();
        $media_type = $this->input->post('media_type') ?? 'fb';
        $search_value = !is_null($this->input->post('search.value')) ? $this->input->post('search.value') : '';
        $display_columns = array("#","CHECKBOX",'name','message','action');
        $search_columns = array('name');
        $postback_id = $_POST['search']['value'];
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? 2 : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $page_id = $this->input->post('whatsapp_bot_id') ?? '';
        $where_simple = array();
        $where_simple['user_id'] = $this->user_id;
        $where_simple['page_id'] = $page_id;
        $where_simple['media_type'] = $media_type;

        if($postback_id != '') $where_simple['postback_id like'] = "%".$postback_id."%";

        $table ="canned_response";
        $where = array('where'=>$where_simple);
        $info=$this->basic->get_data($table,$where,$select='',$join='',$limit,$start,$order_by,$group_by='');
        $total_rows_array=$this->basic->count_row($table,$where,$count=$table.".id",$join='',$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];
        $datatable_name ='table';
        $delete_route = base_url('livechat/delete_canned_response');
        $edit_class ='update_canned_response';
        $icon =  '<i class="fa fa-edit"></i>';
        $send_icon = '<i class="fas fa-paper-plane"></i>';
        $send_class ='send_canned_response';
        $i=0;
        foreach ($info as $key => $value)
        {

            $str="";
            $str = $str."<a class='btn btn-sm btn-outline-primary py-1 me-4 mx-1 ".$send_class."' data-message='".htmlspecialchars($value['message'])."' href='#' title='".$this->lang->line('Use')."'>".$this->lang->line('Use')."</a>";
            $str = $str."<a class='btn btn-circle btn-outline-warning ".$edit_class."' data-id='".$value['id']."' data-name='".$value['name']."' data-message='".htmlspecialchars($value['message'])."' href='#' title='".$this->lang->line('Edit')."'>".$icon."</a>";
            $str = $str."&nbsp;<a href='#' data-id='".$value['id']."' data-table-name='".$datatable_name."' class='delete btn btn-circle btn-outline-danger delete_canned_response' title='".$this->lang->line('Delete')."'>".'<i class="fa fa-trash"></i>'."</a>";

            $action = "<div style='width:150px;display:inline;'>".$str."</div>";
            $info[$i]["action"] = $action;
            $i++;
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start);
        echo json_encode($data);
    }

    public function add_canned_response()
    {
        $this->ajax_check();
        // if(!has_module_access($this->module_id_live_chat_advanced,$this->module_ids,$this->is_admin,$this->is_manager)) abort(403);
        $user_id = $this->input->post('user_id') ?? '';
        $media_type = $this->input->post('media_type') ?? 'fb';
        $page_id = $this->input->post('whatsapp_bot_id') ?? '';
        $name = $this->input->post('name') ?? '';
        $message = $this->input->post('message') ?? '';
        $update_id = $this->input->post('update_id') ?? 0;
        if($name == '' || $message == ''){
            $response = array(
                'error' => true,
                'message' => $this->lang->line('Please fill the required fields.')
            );
            echo json_encode($response);
        }
        $insert_data['user_id'] = $user_id;
        $insert_data['page_id'] = $page_id;
        $insert_data['name'] = $name;
        $insert_data['message'] = $message;
        $insert_data['media_type'] = $media_type;

        $table = 'canned_response';
        if($update_id==0){
            check_module_action_access($module_id=82,$actions=1);
            $this->basic->insert_data($table,$insert_data);
        }
        else {
            check_module_action_access($module_id=82,$actions=2);
            $this->basic->update_data($table,array("id"=>$update_id),$insert_data);
        }
        $response = array(
            'status' => '1',
            'message' => $this->lang->line('Data has been saved successfully.')
        );
        echo json_encode($response);
        

    }

    // public function livechat_upload_file()
    // {
    //     $this->ajax_check();
    //     // if(!has_module_access($this->module_id_live_chat_advanced,$this->module_ids,$this->is_admin,$this->is_manager)){
    //     //     return response()->json([
    //     //         'status' => false,
    //     //         'message' => __('Access Denied'),
    //     //     ]);
    //     // }


    //     $this->load->library('upload');

    //     if(!$_FILES['media_file']){
    //         $response = array(
    //             'status' => false,
    //             'message' => $this->lang->line('Something went wrong.')
    //         );
    //        echo json_encode($response);
    //     }

    //     $upload_dir_subpath = date("Y").'/'.date("n").'/livechat';
    //     $file = $_FILES['media_file']['tmp_name'];
    //     $get_valid_mime_types = $this->get_valid_mime_types();
    //     $get_valid_mime_types = implode('|',$get_valid_mime_types);
    //     $extension = $_FILES['media_file']['name'];
    //     $extension = substr(strrchr($extension, '.'), 1); //extension
    //     $mime_type = $_FILES['media_file']['type'];//type  
    //     $file_name = $_FILES['media_file']['name'];//name  
    //     $file_type = $this->find_file_type($extension);
    //     $allowed_file_size = $this->find_allowed_file_size($file_type);
    //     $filename = "livechat-".$this->user_id.'-'.time().'.'. $extension;

    //     $base_path=realpath(APPPATH . '../upload');

    //     $this->load->library('upload');

    //     $config = array(
    //         // "allowed_types" => $get_valid_mime_types,
    //         "allowed_types" => 'jpeg|png|pdf|mp3|mp4|wav|AMR-WB|amr-wb+|mpeg|amr|msword|txt',
    //         "upload_path" => $base_path,
    //         "overwrite" => true,
    //         "file_name" => $filename,
    //         'max_size' => $allowed_file_size,
    //         );
    //     $this->upload->initialize($config);
    //     $this->load->library('upload', $config);
    //     if (!$this->upload->do_upload('media_file')) {
    //         $response = array(
    //             'status' => false,
    //             'message' => $this->upload->display_errors()
    //         );
    //         echo json_encode($response);
    //         exit;
    //     }
        
    //     $response = array(
    //         'status' => true,
    //         'mime_type' => $mime_type,
    //         'file_type' => $file_type,
    //         'file_name' => $file_name,
    //         'file' => base_url('upload').'/'.$filename
    //     );
    //    echo json_encode($response);

    // }

    public function livechat_upload_file()
    {
        $this->ajax_check();

        $output_dir = FCPATH."upload/livechat";

        $folder_path = FCPATH."upload/livechat";
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }

        if (isset($_FILES["media_file"])) {
            $error =$_FILES["media_file"]["error"];
            $post_fileName =$_FILES["media_file"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $ext=strtolower($ext);
            $filename=implode('.', $post_fileName_array);
            $filename="livechat".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;

            $allow=".jpg,.jpeg,.png,.flv,.mp4,.wmv,.WMV,.MP4,.FLV,.wav,.pdf,.amr,.mp3";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                $custom_error['jquery-upload-file-error']=$this->lang->line("File type not allowed.");
                $response = array(
                    'status' => false,
                    'message' => $custom_error
                );
                echo json_encode($response);
                exit;
            }

            move_uploaded_file($_FILES["media_file"]["tmp_name"], $output_dir.'/'.$filename);
            $response = array(
                'status' => true,
                'file_type' => $ext,
                'file_name' => $post_fileName,
                'file' => base_url('upload/livechat').'/'.$filename
            );
           echo json_encode($response);
           exit ;
        }

        $response = array(
            'status' => false,
            'message' => $this->lang->line("Something went wrong")
        );
        echo json_encode($response);
        exit;
    }

    public function delete_canned_response(){
        $this->ajax_check();
        $id = $this->input->post('id');
        $data=$this->basic->get_data("canned_response",array('where'=>array("id"=>$id)));
        if(!isset($data[0]) && !isset($data[0]['user_id'])){
            $response = array(
                'status' => '0',
                'message' => $this->lang->line('Something went wrong')
            );
            echo json_encode($response);
        }  
        $user_id = $data[0]['user_id'];
        $error_message = '';
         try {
             // DB::beginTransaction();
            check_module_action_access($module_id=82,$actions=3);
             $this->basic->delete_data('canned_response',array('id'=>$id));

             // DB::commit();
             $success = true;
         }
         catch (\Throwable $e){
             // DB::rollBack();
             $success = false;
             $error_message = $e->getMessage();
         }
         if($success)
         {
            $response = array(
                'status' => '1',
                'message' => $this->lang->line('Canned response has been deleted successfully.')
            );
            echo json_encode($response);
         }
         else{
            $response = array(
                'status' => '0',
                'message' => $this->lang->line('Database error : ').$error_message
            );
            echo json_encode($response);
         } 
    }
 
     private function get_valid_mime_types()   
    {

        $supported_mime_types = [
            // Image extensions
            'image/jpeg',
            'image/png',
            //'image/gif',
            // Video extensions
            //'video/x-flv',
            //'video/ogg',
            //'application/ogg',
            'mp4',
            'video/mp4',
            //'video/x-ms-wmv',
            // Audio extensions
            'audio/AMR',
            'audio/amr',
            'audio/AMR-WB',
            'audio/amr-wb+',
            'audio/mpeg',
            //'audio/wave',
            //'audio/wav',
            //'audio/x-wav',
            //'audio/x-pn-wav',
            // File extensions
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/pdf',
            'text/plain',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $supported_mime_types;
    }

    private function find_allowed_file_size($file_type)
    {
        $image_upload_limit = 5;
        $audio_upload_limit = 16;
        $video_upload_limit = 16;
        $file_upload_limit = 100;

        $return = 5; // 5MB
        if ('image' == $file_type) $return = $image_upload_limit;
        else if ('video' == $file_type) $return = $video_upload_limit;
        else if ('audio' == $file_type) $return = $audio_upload_limit;
        else if ('file' == $file_type)  $return = $file_upload_limit;

        return $return*1024; // KB
    }




}