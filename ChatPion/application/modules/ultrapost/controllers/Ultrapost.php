<?php
/*
Addon Name: Ultra Post
Unique Name: ultrapost
Modules:
{}
Project ID: 19
Addon URI: https://xerochat.com
Author: Xerone IT
Author URI: http://xeroneit.net
Version: 1.0
Description: 
*/

require_once("application/controllers/Home.php"); // loading home controller

class Ultrapost extends Home
{
    public $addon_data=array();
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('logged_in')!= 1) redirect('home/login', 'location');

        if($this->session->userdata('user_type') != 'Admin' && !in_array(100,$this->module_access))
        {
            redirect('home/login_page', 'location');
            exit();
        }

        $addon_path=APPPATH."modules/".strtolower($this->router->fetch_class())."/controllers/".ucfirst($this->router->fetch_class()).".php"; // path of addon controller
        $this->addon_data=$this->get_addon_data($addon_path);
        $this->member_validity();
        $this->user_id=$this->session->userdata('user_id'); // user_id of logged in user, we may need it

    }

    public function index()
    {
        $this->poster_menu_section();
    }

    public function poster_menu_section()
    {
        $has_multimedia_access = false;
        $has_cta_access = false;
        $has_carousel_access = false;
        $has_ig_access = false;
        $user_type = $this->session->userdata('user_type');

        if($user_type == 'Admin' || in_array(223,$this->module_access) || in_array(296,$this->module_access)) $has_multimedia_access = true;
        if($user_type == 'Admin' || in_array(220,$this->module_access)) $has_cta_access = true;
        if($user_type == 'Admin' || in_array(222,$this->module_access)) $has_carousel_access = true;

        if($user_type == 'Admin' || in_array(296,$this->module_access)) {
            if($this->config->item('instagram_reply_enable_disable') == '1') {
                $has_ig_access = true;               
            }
        }


        $data['ultrapost_tools'] = [
            '0' => [
                'title' => $this->lang->line('Multimedia Post'),
                'action_url' => base_url('instagram_poster'),
                'icon_color' =>'text-primary',
                'icon' =>'fas fa-paper-plane',
                'sub_contents' => $this->lang->line('Post Text, Link, Image, Video on Facebook & Instagram automatically.'),
                'has_access' => $has_multimedia_access,
            ],
            '1' => [
                'title' => $this->lang->line('CTA Post'),
                'action_url' => base_url('ultrapost/cta_post'),
                'icon_color' =>'text-secondary',
                'icon' =>'fas fa-hand-point-up',
                'sub_contents' => $this->lang->line('Post Call to Action on facebook automatically.'),
                'has_access' => $has_cta_access,
            ],
            '2' => [
                'title' => $this->lang->line('Carousel/Video Post'),
                'action_url' => base_url('ultrapost/carousel_slider_post'),
                'icon_color' =>'text-success',
                'icon' =>'fas fa-film',
                'sub_contents' => $this->lang->line('Post Carousel/Video on Facebook automatically.'),
                'has_access' => $has_carousel_access,
            ]
        ];

        $data['comboposter_tools'] = '';
        $has_text_access = false;
        $has_image_access = false;
        $has_video_access = false;
        $has_link_access = false;
        $has_html_access = false;
        $has_autopost_access = false;
        $has_bulk_post_access = false;
        $has_wp_feed_access = false;
        $has_youtube_feed_access = false;
       
        if($user_type == 'Admin' || in_array(256,$this->module_access)) {
            $has_autopost_access = true;

            if($this->basic->is_exist("add_ons",array("unique_name"=>"auto_feed_post"))) {

                if($user_type == 'Admin' || in_array(269, $this->module_access)) {
                    $has_wp_feed_access = true;
                }
                
                if($user_type == 'Admin' || in_array(276, $this->module_access)) {
                    $has_youtube_feed_access = true;
                }
            }

        }

        if($user_type == 'Admin' || in_array(223,$this->module_access)) {
            $has_bulk_post_access = true;
        }
        
        $data['comboposter_tools'] = [
            '2' => [
                'title' => $this->lang->line('Auto Post'),
                'action_url' => '',
                'icon_color' => 'text-danger',
                'icon' => 'fas fa-rss',
                'sub_contents' => $this->lang->line('Post RSS feed posts automatically on multiple social media.'),
                'has_access' => true,
                'sub_menus' => [
                    '0'=> [
                        'title' => $this->lang->line('RSS Feed'),
                        'action_url' => base_url('autoposting/settings'),
                        'icon' => 'fas fa-rss',
                        'has_access' => $has_autopost_access,
                    ],
                    '1'=> [
                        'title' => $this->lang->line('WP Feed'),
                        'action_url' => base_url('auto_feed_post/wordpress_settings'),
                        'icon' => 'fab fa-wordpress',
                        'has_access' => $has_wp_feed_access
                    ],
                    '2'=> [
                        'title' => $this->lang->line('Youtube video'),
                        'action_url' => base_url('auto_feed_post/youtube_settings'),
                        'icon' => 'fab fa-youtube',
                        'has_access' => $has_youtube_feed_access
                    ],
                ]
            ],

        ];

        $data['body'] = 'poster_menu_block';
        $data['page_title'] = $this->lang->line('Facebook Poster');
        $this->_viewcontroller($data);
    }

    public function get_fb_rx_config($fb_user_id=0)
    {
        if($fb_user_id==0) return 0;

        $getdata= $this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("id"=>$fb_user_id)),array("facebook_rx_config_id"));
        $return_val = isset($getdata[0]["facebook_rx_config_id"]) ? $getdata[0]["facebook_rx_config_id"] : 0;

        return $return_val;
    }

    public function text_image_link_video_meta_info_grabber()
    {
        if($_POST)
        {
            $link= $this->input->post("link");
            $this->load->library("fb_rx_login");
            $response=$this->fb_rx_login->get_meta_tag_fb($link);
            echo json_encode($response);
        }
    }


    public function cta_post()
    {
        check_module_access($module_id=220);

        $data['page_title'] = $this->lang->line("CTA (Call to Action) Poster");
        $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),'bot_enabled'=>'1'));
        if(!empty($this->team_allowed_pages)){
            $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
        }
        $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        
        $data['body'] = 'cta_post/cta_post_list';
        $this->_viewcontroller($data);
    }


    public function cta_poster()
    {
        check_module_access($module_id=220);

        $data['page_title'] = $this->lang->line("Add CTA Post");
        $data['body'] = 'cta_post/add_cta_post';
        $data["time_interval"] = $this->get_periodic_time();
        $data["time_zone"]= $this->_time_zone_list();
        
        $user_infos = $this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("user_id"=>$this->user_id,"id"=>$this->session->userdata("facebook_rx_fb_user_info"))));

        if ( count( $user_infos ) == 0 ) 
            return redirect( base_url( 'facebook_rx_account_import/index' ), 'location' );

        $data["fb_user_info"] = $user_infos;
        if($this->config->item('facebook_poster_botenabled_pages') == '1'){
            $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),'bot_enabled'=>'1'));
            if(!empty($this->team_allowed_pages)){
                $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
            }
            $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        }
        else{
            $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info")));
            if(!empty($this->team_allowed_pages)){
                $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
            }
            $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"))));
        }

        $data["fb_group_info"]=$this->basic->get_data("facebook_rx_fb_group_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"))));
        $data["app_info"]=$this->basic->get_data("facebook_rx_config",array("where"=>array("id"=>$this->session->userdata("fb_rx_login_database_id"))));  
        $data['auto_reply_template'] = $this->basic->get_data('ultrapost_auto_reply',array("where"=>array('user_id'=>$this->user_id)),array('id','ultrapost_campaign_name'));
        
        $only_message_button = 0;

        if($only_message_button==1) // only show message cta button, used in fb exciter
        $cta_dropdown = array("MESSAGE_PAGE"=>"MESSAGE_PAGE");
        else
        $cta_dropdown = array("BOOK_TRAVEL"=>"BOOK_TRAVEL","BUY_NOW"=>"BUY_NOW","CALL_NOW"=>"CALL_NOW","DOWNLOAD"=>"DOWNLOAD","GET_DIRECTIONS"=>"GET_DIRECTIONS","GET_QUOTE"=>"GET_QUOTE","INSTALL_APP"=>"INSTALL_APP","INSTALL_MOBILE_APP"=>"INSTALL_MOBILE_APP","LEARN_MORE"=>"LEARN_MORE","LIKE_PAGE"=>"LIKE_PAGE","LISTEN_MUSIC"=>"LISTEN_MUSIC","MESSAGE_PAGE"=>"MESSAGE_PAGE","NO_BUTTON"=>"NO_BUTTON","OPEN_LINK"=>"OPEN_LINK","PLAY_GAME"=>"PLAY_GAME","SHOP_NOW"=>"SHOP_NOW","SIGN_UP"=>"SIGN_UP","SUBSCRIBE"=>"SUBSCRIBE","USE_APP"=>"USE_APP","USE_MOBILE_APP"=>"USE_MOBILE_APP","WATCH_MORE"=>"WATCH_MORE","WATCH_VIDEO"=>"WATCH_VIDEO");
        foreach ($cta_dropdown as $key => $value) 
        {
           $value = str_replace('_', " ", $value);
           $value = ucwords(strtolower($value));
           $data["cta_dropdown"][$key] = $value;
        }

        $this->_viewcontroller($data);
    }


    public function cta_post_list_data($only_message_button=0)
    {
        $this->ajax_check();

        $pageId          = trim($this->input->post("page_id",true));
        $post_type       = trim($this->input->post("post_type",true));
        $campaign_name   = trim($this->input->post("campaign_name",true));
        $post_date_range = $this->input->post("post_date_range",true);
        $display_columns = array("#",'id','campaign_name','campaign_type','page_name','cta_button','actions','status','schedule_time','error_mesage');
        $search_columns = array('campaign_name','schedule_time');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_simple=array();

        if($post_date_range!="")
        {
            $exp = explode('|', $post_date_range);
            $from_date = isset($exp[0])?$exp[0]:"";
            $to_date   = isset($exp[1])?$exp[1]:"";

            if($from_date!="Invalid date" && $to_date!="Invalid date")
            {
                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date   = date('Y-m-d', strtotime($to_date));
                $where_simple["Date_Format(last_updated_at,'%Y-%m-%d') >="] = $from_date;
                $where_simple["Date_Format(last_updated_at,'%Y-%m-%d') <="] = $to_date;
            }
        }

        if($pageId !="") $where_simple['facebook_rx_cta_post.page_group_user_id like'] = $pageId;
        if($campaign_name !="") $where_simple['facebook_rx_cta_post.campaign_name like'] = "%".$campaign_name."%";


        $where_simple['facebook_rx_cta_post.user_id'] = $this->user_id;
        $where_simple['facebook_rx_cta_post.facebook_rx_fb_user_info_id'] = $this->session->userdata("facebook_rx_fb_user_info");

        $this->db->where("(is_child='0' or posting_status='2')");
        $where  = array('where'=>$where_simple);
        $select = array("facebook_rx_cta_post.*","facebook_rx_fb_page_info.page_name","facebook_rx_fb_page_info.page_id AS pageid");
        $join   = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=facebook_rx_cta_post.page_group_user_id,left");

        $table = "facebook_rx_cta_post";
        $info = $this->basic->get_data($table,$where,$select,$join,$limit,$start,$order_by);

        $this->db->where("(is_child='0' or posting_status='2')");
        $total_rows_array = $this->basic->count_row($table,$where,$count=$table.".id",$join);
        $total_result = $total_rows_array[0]['total_rows'];

        for($i=0;$i<count($info);$i++) 
        {
            $action_count = 4;
            $posting_status = $info[$i]['posting_status'];
            $full_complete =  $info[$i]['full_complete'];
            $schedule_type = $info[$i]['schedule_type'];
            $parent_id = $info[$i]['parent_campaign_id'];

            $allschedulepost_check = $this->basic->get_data("facebook_rx_cta_post", array('where'=>array('parent_campaign_id'=>$info[$i]['id'])));
            
            if(count($allschedulepost_check)>0)
            {
                $completed_child_count=0;
                foreach ($allschedulepost_check as $key => $value12) 
                {                        
                    if ($value12['posting_status'] == '2' ) $completed_child_count++;
                }
                if(count($allschedulepost_check) == $completed_child_count) $is_all_posted='1';
                else $is_all_posted='0';
            }
            else
            {
                if($posting_status=='2') $is_all_posted='1';
                else $is_all_posted='0';
            }

            $info[$i]['page_name'] = "<div style='min-width:120px;'><a class='ash' data-toggle='tooltip' title='".$this->lang->line("Visit Page")."' target='_BLANK' href='https://facebook.com/".$info[$i]['pageid']."'>".$info[$i]['page_name']."</a></div>";

            if(($posting_status=='0' || $posting_status == '2')  && ($schedule_type == 'later' && $parent_id == '0' && $is_all_posted==0))
                $info[$i]['status'] = '<div style="min-width:120px !important;" class="text-muted"><i class="fas fa-exclamation-circle"></i> '.$this->lang->line("not all completed").'</div>';
            else if( $posting_status == '2') 
                $info[$i]['status'] = '<div style="min-width:120px !important;" class="text-success"><i class="fas fa-check-circle"></i> '.$this->lang->line("Completed").'</div>';
            else if( $posting_status == '1') 
                $info[$i]['status'] = '<div style="min-width:120px !important;" class="text-warning"><i class="fas fa-spinner"></i> '.$this->lang->line("Processing").'</div>';
            else 
                $info[$i]['status'] = '<div style="min-width:120px !important;" class="text-danger"><i class="far fa-times-circle red"></i> '.$this->lang->line("Pending").'</div>';

            if($info[$i]['schedule_time'] != "0000-00-00 00:00:00")
                $info[$i]['schedule_time'] = "<div style='min-width:120px !important'>".date("M j, y H:i",strtotime($info[$i]['schedule_time']))."</div>";
            else 
                $info[$i]['schedule_time'] = '<div style="min-width:120px !important" data-toggle="tooltip" title="'.$this->lang->line("Instantly posted").'"><i class="fas fa-exclamation-circle"></i> '.$this->lang->line('Not Scheduled').'</div>';

            $insight="";
            if($this->session->userdata('user_type') == 'Admin'|| in_array(72,$this->module_access))
            {
                $post_id = $info[$i]['post_id'];
                $page_id = $info[$i]['page_group_user_id'];
                
                if($posting_status=='2')
                    $insight = "<a class='btn btn-sm btn-primary' target='_BLANK' href='".base_url("facebook_rx_insight/post_analytics_display/{$post_id}/{$page_id}")."'><i class='fa fa-line-chart'></i> Post Insight</a>";
                else $insight = '<i class="fa fa-remove red" title="'.$this->lang->line("This post is not published yet.").'"></i>';                
            }

            $info[$i]['insight'] =  $insight;

            $cta_type= $info[$i]['cta_type'];
            $cta_type = str_replace('_', " ", $cta_type);
            $cta_type = ucwords(strtolower($cta_type));

            if($info[$i]['cta_type']=="LIKE_PAGE" || $info[$i]['cta_type'] =="MESSAGE_PAGE")
                $cta_button = "<a class='btn btn-default btn-sm' href='#'>".$cta_type."</a>";
            else  
                $cta_button = "<a class='btn btn-default btn-sm' target='_BLANK' href='".$info[$i]['cta_value']."'>".$cta_type."</a>";
                    
            $info[$i]['cta_button'] =  $cta_button;

            if($schedule_type == 'later' && $parent_id == '0')
                $info[$i]['campaign_type'] = '<div style="min-width:100px !important">'.$this->lang->line("main campaign").'</div>';
            else if($schedule_type == 'now')
                $info[$i]['campaign_type'] = '<div style="min-width:100px !important">'.$this->lang->line("single campaign").'</div>';
            else
                $info[$i]['campaign_type'] = '<div style="min-width:100px !important">'.$this->lang->line("sub campaign").'</div>';

            // visit post action
            if($posting_status=='2')
                $post_url = "<a class='btn btn-circle btn-outline-info' target='_BLANK' data-toggle='tooltip' title='".$this->lang->line("Visit Post")."' href='".$info[$i]['post_url']."'><i class='fas fa-hand-point-right'></i></a>";
            else 
                $post_url = "<a class='btn btn-circle btn-light text-muted' data-toggle='tooltip' title='".$this->lang->line("not published yet.")."'><i class='fas fa-hand-point-right'></i></a>";

            // edit campaign action
            if(($posting_status=='0' || $posting_status == '2')  && ($schedule_type == 'later' && $parent_id == '0' && $is_all_posted==0) )
                $editUrlCtaPost ="<a class='btn btn-circle btn-outline-warning' data-toggle='tooltip' title='".$this->lang->line("Edit Campaign")."'  href='".base_url()."ultrapost/edit_cta_post/".$info[$i]['id']."'><i class='fas fa-edit'></i></a>";  
            else 
                $editUrlCtaPost ="<a class='btn btn-circle btn-light pointer text-muted' data-toggle='tooltip' title='".$this->lang->line("Only pending and scheduled campaigns are editable")."'><i class='fas fa-edit'></i></a>";  

            // view post report action
            if ($schedule_type == 'later' && $parent_id == '0') 
                $report_url = "<a href='#' class='btn btn-circle btn-outline-primary view_report' table_id='".$info[$i]['id']."' data-toggle='tooltip' title='".$this->lang->line("Campaign Report")."'><i class='fas fa-eye'></i></a>";  
            else 
                $report_url ="<a class='btn btn-circle btn-light pointer text-muted' data-toggle='tooltip' title='".$this->lang->line("Only parent campaign has shown report")."'><i class='fas fa-eye'></i></a>";

            $info[$i]['delete'] =  "<a href='#' title='".$this->lang->line("Delete")."' id='".$info[$i]['id']."' class='delete dropdown-item red'><i class='fas fa-trash-alt'></i></a>";

            // delete campaign action
            if($schedule_type == 'later' && $parent_id == '0')
                $deleteUrl =  "<a href='#' data-toggle='tooltip' title='".$this->lang->line("Delete Campaign")."' id='".$info[$i]['id']."' class='delete_p btn btn-circle btn-outline-danger'><i class='fas fa-trash-alt'></i></a>";
            else
                $deleteUrl =  "<a href='#' data-toggle='tooltip' title='".$this->lang->line("Delete Campaign")."' id='".$info[$i]['id']."' class='delete btn btn-circle btn-outline-danger'><i class='fas fa-trash-alt'></i></a>";

            // Action section started from here
            $action_width = ($action_count*47)+20;
            $info[$i]['actions'] = '<div class="dropdown d-inline dropright">
            <button class="btn btn-outline-primary dropdown-toggle no_caret" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-briefcase"></i></button>
            <div class="dropdown-menu mini_dropdown text-center" style="width:'.$action_width.'px !important">';
            $info[$i]['actions'] .= $post_url;
            $info[$i]['actions'] .= $report_url;
            $info[$i]['actions'] .= $editUrlCtaPost;
            $info[$i]['actions'] .= $deleteUrl;
            $info[$i]['actions'] .= "</div></div><script>$('[data-toggle=\"tooltip\"]').tooltip();</script>";
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);
    }

    public function ajax_cta_report()
    {
        $this->ajax_check();

        $table_id = $this->input->post('table_id');
        $searching = $this->input->post('searching1',true);
        
        $display_columns = array("#",'id','page_or_group_or_user','post_type','post_id','posting_status','schedule_time','error_mesage');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where = array();
        $where_simple=array();

        if($searching == '')
        {
            $where_simple['facebook_rx_cta_post.parent_campaign_id'] = $table_id;
            $or_where['facebook_rx_cta_post.id'] = $table_id;
            $where  = array('where'=>$where_simple,'or_where'=>$or_where);
        }

        $sql = '';
        if ($searching != '') 
        {
            $sql = "(facebook_rx_cta_post.schedule_time LIKE  '%".$searching."%' OR facebook_rx_cta_post.post_id LIKE '%".$searching."%') AND (`facebook_rx_cta_post.parent_campaign_id` = '$table_id' OR `facebook_rx_cta_post.id` = '$table_id')";
        }
        if($sql != '') $this->db->where($sql);

        $select = array("facebook_rx_cta_post.*","facebook_rx_fb_page_info.page_name");
        $join   =  array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=facebook_rx_cta_post.page_group_user_id ,left");
        
        $table = "facebook_rx_cta_post";
        $info = $this->basic->get_data('facebook_rx_cta_post',$where,$select,$join,$limit,$start,$order_by,$group_by='');

        $total_rows_array=$this->basic->count_row($table,$where,$count="facebook_rx_cta_post.id",$join,$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];

        for($i=0;$i<count($info);$i++)
        {   
            $posting_status = $info[$i]['posting_status'];
            $post_id = $info[$i]['post_id'];

            if($post_id != '')
                $info[$i]['post_id'] = "<a target='_BLANK' href='https://facebook.com/".$post_id."'>".$post_id."</a>";
            
            // status section started
            if($posting_status=='2')
                $posting_status='<span class="text-success"><i class="fas fa-check-circle"></i> '.$this->lang->line("completed").'</span>';
            if($posting_status == '0')
                $posting_status='<span class="text-danger"><i class="far fa-times-circle"></i> '.$this->lang->line("pending").'</span>';
            if($posting_status == '1')
                $posting_status='<span class="text-warning"><i class="fas fa-spinner"></i> '.$this->lang->line("processing").'</span>';

            $info[$i]['posting_status'] = $posting_status;
            $info[$i]['post_type'] = '<span>'.$this->lang->line("CTA").'</span>';
            // end of status section




            // publisher started 
            if($info[$i]['page_or_group_or_user'] == "page")
                $info[$i]['page_or_group_or_user'] = ucfirst($info[$i]['page_or_group_or_user'])." : ".$info[$i]['page_name'];
            else if($info[$i]['page_or_group_or_user'] == "group")
                $info[$i]['page_or_group_or_user'] = ucfirst($info[$i]['page_or_group_or_user'])." : ".$info[$i]['page_name'];
            else
                $info[$i]['page_or_group_or_user'] = ucfirst($info[$i]['page_or_group_or_user'])." : ".$info[$i]['page_name'];
            // publisher ended

            if($info[$i]['schedule_time'] != "0000-00-00 00:00:00")
                $info[$i]['schedule_time'] = date("jS M y H:i",strtotime($info[$i]['schedule_time']));
            else 
                $info[$i]['schedule_time'] ='<i class="far fa-times" title="'.$this->lang->line("Instantly posted").'"></i>';

        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);
    }    


    public function add_cta_post_action()
    {
        check_module_action_access($module_id=220,$actions=1);

        if(!$_POST)
        exit();
      
        $this->load->library("fb_rx_login");

        $post=$_POST;
        foreach ($post as $key => $value) 
        {
           if(!is_array($value))
               $temp = strip_tags($value);
           else
               $temp = $value;

           $$key=$this->security->xss_clean($temp);

           if(!is_array($value)){
            $value = strip_tags($value);
            $value=$this->security->xss_clean($value);
            if($key == "auto_reply_template")
                $insert_data['ultrapost_auto_reply_table_id'] = $value;
            else
                $insert_data[$key]=$value;
           }
           
        }

        $schedule_type = $this->input->post('schedule_type',true);

        if($schedule_type == '')
            $insert_data["schedule_type"] = 'later';
        else
            $insert_data["schedule_type"] = 'now';

        //************************************************/
        $times = 0;
        $times = $repeat_times;
        $interval= $time_interval;
        $request_count = count($post_to_pages);


        $status=$this->_check_usage($module_id=220,$request=$request_count);
        if($status=="2") 
        {
            $error_msg = $this->lang->line("sorry, your bulk limit is exceeded for this module.")."<a href='".site_url('payment/usage_history')."'>".$this->lang->line("click here to see usage log")."</a>";
            $return_val=array("status"=>"0","message"=>$error_msg);
            echo json_encode($return_val);
            exit();
        }
        else if($status=="3") 
        {
            $error_msg = $this->lang->line("sorry, your monthly limit is exceeded for this module.")."<a href='".site_url('payment/usage_history')."'>".$this->lang->line("click here to see usage log")."</a>";
            $return_val=array("status"=>"0","message"=>$error_msg);
            echo json_encode($return_val);
            exit();
        }
        //************************************************//

        $insert_data["auto_share_to_profile"]= "0";
        

        $insert_data["user_id"] = $this->user_id;        
        $insert_data["facebook_rx_fb_user_info_id"] = $this->session->userdata("facebook_rx_fb_user_info");       

        if(!isset($auto_share_this_post_by_pages) || !is_array($auto_share_this_post_by_pages)) $auto_share_this_post_by_pages=array();
        if(!isset($post_to_pages) || !is_array($post_to_pages)) $post_to_pages=array();
        $auto_share_this_post_by_pages_new = array_diff($auto_share_this_post_by_pages,$post_to_pages);  
        $page_ids_string = implode(',', $post_to_pages);
        $insert_data["page_ids"] = $page_ids_string;      
        $insert_data["auto_share_this_post_by_pages"] = json_encode($auto_share_this_post_by_pages_new);

        $insert_data["auto_private_reply_status"]= "0";
        $insert_data["auto_private_reply_count"]= 0;
        $insert_data["auto_private_reply_done_ids"]= json_encode(array());
        $insert_data["post_auto_comment_cron_jon_status"] = "0";
        $insert_data["post_auto_like_cron_jon_status"] = "0";
        $insert_data["post_auto_share_cron_jon_status"] = "0";
        $insert_data["repeat_times"] = $times;
        $insert_data["time_interval"] = $interval;
        

        if($schedule_type=="now")
        {
            $insert_data["posting_status"] ='2';
            $insert_data["full_complete"]  ='1';
        }
        else
        {
            $insert_data["posting_status"] ='0';
        }


        $insert_data_batch=array();
        $user_id_array=array($this->user_id);  
        $account_switching_id = $this->session->userdata("facebook_rx_fb_user_info"); // table > facebook_rx_fb_user_info.id
        $count=0;
        
        $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$account_switching_id));
        if(!empty($this->team_allowed_pages)){
            $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
        }  
        $page_info = $this->basic->get_data("facebook_rx_fb_page_info",$where);
     
        foreach ($page_info as $key => $value) 
        {
            if(!in_array($value["id"], $post_to_pages)) continue;

            
            $page_access_token =  isset($value["page_access_token"]) ? $value["page_access_token"] : ""; 
            $fb_page_id =  isset($value["page_id"]) ? $value["page_id"] : "";

            $insert_data_batch[$count]=$insert_data;
            $page_auto_id =  isset($value["id"]) ? $value["id"] : ""; 
            $insert_data_batch[$count]["page_group_user_id"]=$page_auto_id;
            $insert_data_batch[$count]["page_or_group_or_user"]="page";
            $insert_data_batch[$count]["post_id"] = "";
            $insert_data_batch[$count]["post_url"] = "";   
            
            if($schedule_type=="now")
            {
                if($this->is_demo == '1')
                    if($this->user_id == 1)
                        continue;
                try
                {

                    $response = $this->fb_rx_login->cta_post($message, $link,"","",$cta_type,$cta_value,"","",$page_access_token,$fb_page_id);

                }
                catch(Exception $e) 
                {
                  $error_msg = $e->getMessage();
                  $return_val=array("status"=>"0","message"=>$error_msg);
                  echo json_encode($return_val);
                  exit();
                }

                $object_id=$response["id"];
                $share_access_token = $page_access_token;

                $insert_data_batch[$count]["post_id"]= $object_id;
                $temp_data=$this->fb_rx_login->get_post_permalink($object_id,$page_access_token);
                $insert_data_batch[$count]["post_url"]= isset($temp_data["permalink_url"]) ? $temp_data["permalink_url"] : ""; 
                $insert_data_batch[$count]["last_updated_at"]= date("Y-m-d H:i:s");

                $this->basic->insert_data("facebook_rx_cta_post",$insert_data_batch[$count]);  
                //insert data to useges log table
                $this->_insert_usage_log($module_id=220,$request=$request_count);


                if(isset($insert_data['ultrapost_auto_reply_table_id']) && $insert_data['ultrapost_auto_reply_table_id'] != '0')
                {

                    //************************************************//
                    $status=$this->_check_usage($module_id=204,$request=1);
                    if($status!="2" && $status!="3") 
                    {


                        $auto_reply_table_info = $this->basic->get_data('ultrapost_auto_reply',['where'=>['id' => $insert_data['ultrapost_auto_reply_table_id'] ]]);

                        $auto_reply_table_data = [];

                        foreach ($auto_reply_table_info as $single_auto_reply_table_info) {

                            foreach ($single_auto_reply_table_info as $auto_key => $auto_value) {
                                
                                if($auto_key == 'id')
                                    continue;

                                if($auto_key == 'page_ids')
                                    continue;

                                if($auto_key == 'ultrapost_campaign_name')
                                    $auto_reply_table_data['auto_reply_campaign_name'] = $auto_value;
                                else
                                    $auto_reply_table_data[$auto_key] = $auto_value;
                            }
                        }



                        $auto_reply_table_data['facebook_rx_fb_user_info_id'] = $value['facebook_rx_fb_user_info_id'];
                        $auto_reply_table_data['page_info_table_id'] = $value['id'];
                        $auto_reply_table_data['page_name'] = $value['page_name'];
                        $auto_reply_table_data['post_id'] = $object_id;
                        $auto_reply_table_data['post_created_at'] = date("Y-m-d h:i:s");
                        $auto_reply_table_data['post_description'] = $message;
                        $auto_reply_table_data['auto_private_reply_status'] = '0';
                        $auto_reply_table_data['auto_private_reply_count'] = 0;
                        $auto_reply_table_data['last_updated_at'] = date("Y-m-d h:i:s");
                        $auto_reply_table_data['last_reply_time'] = '';
                        $auto_reply_table_data['error_message'] = '';
                        $auto_reply_table_data['hidden_comment_count'] = 0;
                        $auto_reply_table_data['deleted_comment_count'] = 0;
                        $auto_reply_table_data['auto_comment_reply_count'] = 0;


                        $this->basic->insert_data('facebook_ex_autoreply', $auto_reply_table_data);

                     
                         $this->_insert_usage_log($module_id=204,$request=1);                        
                     }
                    //************************************************//
                }


            }            
            $count++;
        }

        $profile_info = $this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("id"=> $account_switching_id,"user_id"=>$this->user_id)));  
        $user_access_token =  isset($profile_info[0]["access_token"]) ? $profile_info[0]["access_token"] : ""; 
        $user_fb_id =  isset($profile_info[0]["fb_id"]) ? $profile_info[0]["fb_id"] : ""; 
    

       if($schedule_type=="now") $return_val=array("status"=>"1","message"=>$this->lang->line("Facebook CTA post has been performed successfully."));
       else
       {       

            $page_info_arr = array();
            $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$account_switching_id)));

            foreach ($page_info as $key => $value) {
               if(!empty($this->team_allowed_pages) && !in_array($value['id'], $this->team_allowed_pages)) continue;
               if(!in_array($value["id"], $post_to_pages)) continue;
               $page_info_arr[$value["id"]] = $value['page_name'];
            }


            $parent_id='';
            for ($insert_counter=0; $insert_counter < $request_count; $insert_counter++) { 

               $insert_data['page_or_group_or_user'] = 'page';
               $insert_data['page_group_user_id'] = $post['post_to_pages'][$insert_counter];
               $x=$post['time_interval'];

               if($x=="" || $x==0){
                $x=rand(15,100);
               }

               for ($i=0; $i <=$times ; $i++) { 
                   
                    if($i == 0)
                    {
                        $insert_data['schedule_time']= $post['schedule_time'];
                        $this->basic->insert_data('facebook_rx_cta_post',$insert_data);
                        $insert_id = $this->db->insert_id();
                       
                        if($i == 0 && $insert_counter == 0)
                            $parent_id = $insert_id;
                            
                       

                    }
                    if ($times == 0) {
                          $insert_data['is_child'] = '1';
                          $insert_data['parent_campaign_id'] = $parent_id;
                    
                    }
                    if($i >= 1)
                    {


                        $insert_data['is_child'] = '1';
                        $current_schedule_time = $post['schedule_time'];
                        $dateTime = new DateTime($current_schedule_time);
                        $p = $i*$x;
                        $dateTime->modify("+{$p} minutes");
                        $insert_data['parent_campaign_id'] = $parent_id;
                        $change_time= $dateTime->format('Y-m-d H:i:s');
                        $insert_data['schedule_time']= $change_time;
                         unset($insert_data['page_ids']);
                         
                        $this->basic->insert_data('facebook_rx_cta_post',$insert_data);
                    }
               }

            }


            if($insert_counter > 0)
            {
                $number_request = count($insert_data_batch);
                $this->_insert_usage_log($module_id=220,$request=$number_request);
                $return_val=array("status"=>"1","message"=>$this->lang->line("Facebook CTA post campaign has been created successfully."));
            }
            else $return_val=array("status"=>"0","message"=>$this->lang->line("something went wrong, please try again."));
       }
       echo json_encode($return_val);        
    }


    public function edit_cta_post($cta_post_id)
    {
        check_module_access($module_id=220);

        $table2 = "facebook_rx_cta_post";
        $where5656  = array('where'=>array('id'=>$cta_post_id));

        $allschedulepost_check = $this->basic->get_data($table2,$where5656);

        foreach ($allschedulepost_check as $key => $value12) {
            if ($value12['posting_status'] == '2')
            {
                 $data['is_all_posted'] = 1; 
            }
            else
            {
                $data['is_all_posted'] =0;
            }
          
        }$data['body'] = 'cta_post/edit_cta_post';
        $data['page_title'] = $this->lang->line('Edit CTA Poster');
        $data["time_zone"]= $this->_time_zone_list();
        $data["time_interval"] = $this->get_periodic_time();
        $data["fb_user_info"]=$this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("user_id"=>$this->user_id,"id"=>$this->session->userdata("facebook_rx_fb_user_info"))));
        $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info")));
        if(!empty($this->team_allowed_pages)){
            $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
        }
        $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        $data["fb_group_info"]=$this->basic->get_data("facebook_rx_fb_group_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"))));
        $data["app_info"]=$this->basic->get_data("facebook_rx_config",array("where"=>array("id"=>$this->session->userdata("fb_rx_login_database_id"))));

        $data["all_data"] = $this->basic->get_data("facebook_rx_cta_post",array("where"=>array("id"=>$cta_post_id)));
        $data['auto_reply_template'] = $this->basic->get_data('ultrapost_auto_reply',array("where"=>array('user_id'=>$this->user_id)),array('id','ultrapost_campaign_name'));

        $only_message_button=0;
        if($only_message_button==1) // only show message cta button, used in fb exciter
        $cta_dropdown = array("MESSAGE_PAGE"=>"MESSAGE_PAGE");
        else
        $cta_dropdown = array("BOOK_TRAVEL"=>"BOOK_TRAVEL","BUY_NOW"=>"BUY_NOW","CALL_NOW"=>"CALL_NOW","DOWNLOAD"=>"DOWNLOAD","GET_DIRECTIONS"=>"GET_DIRECTIONS","GET_QUOTE"=>"GET_QUOTE","INSTALL_APP"=>"INSTALL_APP","INSTALL_MOBILE_APP"=>"INSTALL_MOBILE_APP","LEARN_MORE"=>"LEARN_MORE","LIKE_PAGE"=>"LIKE_PAGE","LISTEN_MUSIC"=>"LISTEN_MUSIC","MESSAGE_PAGE"=>"MESSAGE_PAGE","NO_BUTTON"=>"NO_BUTTON","OPEN_LINK"=>"OPEN_LINK","PLAY_GAME"=>"PLAY_GAME","SHOP_NOW"=>"SHOP_NOW","SIGN_UP"=>"SIGN_UP","SUBSCRIBE"=>"SUBSCRIBE","USE_APP"=>"USE_APP","USE_MOBILE_APP"=>"USE_MOBILE_APP","WATCH_MORE"=>"WATCH_MORE","WATCH_VIDEO"=>"WATCH_VIDEO");
        foreach ($cta_dropdown as $key => $value) 
        {
           $value = str_replace('_', " ", $value);
           $value = ucwords(strtolower($value));
           $data["cta_dropdown"][$key] = $value;
        }

        $this->_viewcontroller($data);
    }

    public function edit_cta_post_action()
    {
        check_module_action_access($module_id=220,$actions=2);

        if ($_SERVER['REQUEST_METHOD'] === 'GET'){
            redirect('home/access_forbidden', 'location');
        }
        if ($_POST)
        {
            $this->form_validation->set_rules('id',                             '<b>id</b>',                            'trim|required');
            $this->form_validation->set_rules('user_id',                        '<b>user_id</b>',                       'trim|required');
            $this->form_validation->set_rules('facebook_rx_fb_user_info_id',    '<b>facebook_rx_fb_user_info_id</b>',   'trim|required');
            $this->form_validation->set_rules('campaign_name',                  '<b>Campaign Name</b>',                 'trim');
            $this->form_validation->set_rules('message',                        '<b>Message</b>',                       'trim');
            $this->form_validation->set_rules('link',                           '<b>Paste link</b>',                    'trim');
            $this->form_validation->set_rules('link_preview_image',             '<b>Preview image URL</b>',             'trim');
            $this->form_validation->set_rules('link_caption',                   '<b>Link caption</b>',                  'trim');
            $this->form_validation->set_rules('link_description',               '<b>Link description</b>',              'trim');
            $this->form_validation->set_rules('cta_type',                       '<b>Cta Type</b>',                      'trim');
            $this->form_validation->set_rules('cta_value',                      '<b>Cta Talue</b>',                     'trim');
            $this->form_validation->set_rules('auto_share_post',                '<b>Auto Share Post</b>',               'trim');
            $this->form_validation->set_rules('auto_share_to_profile',          '<b>Auto Share To Profile</b>',         'trim');
            $this->form_validation->set_rules('auto_like_post',                 '<b>Auto Like Post</b>',                'trim');
            $this->form_validation->set_rules('auto_private_reply',             '<b>Auto Private Reply</b>',            'trim');
            $this->form_validation->set_rules('auto_private_reply_text',        '<b>Auto Private Reply Text</b>',       'trim');
            $this->form_validation->set_rules('auto_comment',                   '<b>auto comment</b>',                  'trim');
            $this->form_validation->set_rules('auto_comment_text',              '<b>auto comment text</b>',             'trim');
            $this->form_validation->set_rules('schedule_type',                  '<b>schedule type</b>',                 'trim');
            $this->form_validation->set_rules('schedule_time',                  '<b>schedule time</b>',                 'trim');
            $this->form_validation->set_rules('time_zone',                      '<b>time zone</b>',                     'trim');
            $this->form_validation->set_rules('submit_post_hidden',             '<b>submit post hidden</b>',            'trim');

            if($this->form_validation->run() == false)
            {
                return $this->edit_cta_post($_POST['id']);
            }
            $times = 0;
            $id                         = $this->input->post('id', true);
            $user_id                    = $this->input->post('user_id', true);
            $facebook_rx_fb_user_info_id= $this->input->post('facebook_rx_fb_user_info_id', true);
            $campaign_name              = strip_tags($this->input->post('campaign_name', true));
            $message                    = $this->input->post('message', true);
            $link                       = $this->input->post('link', true);
            $link_preview_image         = $this->input->post('link_preview_image', true);
            $link_caption               = $this->input->post('link_caption', true);
            $link_description           = $this->input->post('link_description', true);
            $cta_type                   = $this->input->post('cta_type', true);
            $cta_value                  = $this->input->post('cta_value', true);
            $auto_share_post            = $this->input->post('auto_share_post', true);
            $auto_share_to_profile      = $this->input->post('auto_share_to_profile', true);
            $auto_like_post             = $this->input->post('auto_like_post', true);
            $auto_private_reply         = $this->input->post('auto_private_reply', true);
            $auto_private_reply_text    = $this->input->post('auto_private_reply_text', true);
            $auto_comment               = $this->input->post('auto_comment', true);
            $auto_comment_text          = $this->input->post('auto_comment_text', true);
            $schedule_type              = $this->input->post('schedule_type', true);
            $schedule_time              = $this->input->post('schedule_time', true);
            $time_zone                  = $this->input->post('time_zone', true);
            $submit_post_hidden         = $this->input->post('submit_post_hidden', true);
            $ultrapost_auto_reply_table_id   = $this->input->post('auto_reply_template', true);
            $times                      = $this->input->post('repeat_times', true);
            $interval                   = $this->input->post('time_interval', true);

            $post_to_pages = array();
            if($this->input->post('post_to_pages', true) !== null)
                $post_to_pages = $this->input->post('post_to_pages', true);
            else
                $post_to_pages   = 0;
            

            $data = array(
                'user_id'                       => $user_id,
                'facebook_rx_fb_user_info_id'   => $facebook_rx_fb_user_info_id,
                'campaign_name'                 => $campaign_name,
                'message'                       => $message,
                'link'                          => $link,
                'link_preview_image'            => $link_preview_image,
                'link_caption'                  => $link_caption,
                'link_description'              => $link_description,
                'cta_type'                      => $cta_type,
                'cta_value'                     => $cta_value,
                'auto_share_post'               => '0',
                'auto_share_to_profile'         => '0',
                'auto_like_post'                => '0',
                'auto_private_reply'            => '0',
                'auto_private_reply_text'       => '0',
                'auto_comment'                  => '0',
                'auto_comment_text'             => '0',
                'schedule_time'                 => $schedule_time,
                'time_zone'                     => $time_zone,
                'page_group_user_id'            => $post_to_pages,
                // 'ultrapost_auto_reply_table_id' => $ultrapost_auto_reply_table_id,
                'auto_share_this_post_by_pages' => "",
                'repeat_times'                  => $times,
                'time_interval'                 => $interval,
                'schedule_type'                 => $schedule_type
            );

            $request_count = count($post_to_pages);
            if(isset($ultrapost_auto_reply_table_id))
                $data['ultrapost_auto_reply_table_id'] = $ultrapost_auto_reply_table_id;
    
            // $this->basic->delete_data('facebook_rx_cta_post',array('id'=>$id));
            // $this->basic->delete_data('facebook_rx_cta_post',array('parent_campaign_id'=>$id));
           // $this->basic->delete_data('facebook_rx_cta_post',array('full_complete'=>'0'));

            $this->basic->delete_data('facebook_rx_cta_post',array('id'=>$id,'user_id'=>$this->user_id));
            $this->basic->delete_data('facebook_rx_cta_post',array('parent_campaign_id'=>$id,'full_complete'=>'0' ,'user_id'=>$this->user_id));
            $account_switching_id = $this->session->userdata("facebook_rx_fb_user_info");
            // $page_counter = 0;
            $user_id_array=array($this->user_id);

            $page_ids_string = implode(',', $post_to_pages);
            $data["page_ids"] = $page_ids_string;
            $page_info_arr = array();
            $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$account_switching_id)));

            foreach ($page_info as $key => $value) {
               if(!empty($this->team_allowed_pages) && !in_array($value['id'], $this->team_allowed_pages)) continue;
               if(!in_array($value["id"], $post_to_pages)) continue;
               $page_info_arr[$value["id"]] = $value['page_name'];
            }

            $parent_id='';
            for ($insert_counter=0; $insert_counter < $request_count; $insert_counter++) { 

               $data['page_or_group_or_user'] = 'page';
               $data['page_group_user_id'] = $post_to_pages[$insert_counter];
               //$insert_data['page_or_group_or_user'] = $page_info_arr[$insert_data['page_group_user_id']];

               $x=$interval;
               
               if($x=="" || $x==0){
                    $x=rand(15,100);
               }

               for ($i=0; $i <=$times ; $i++) { 
                   
                    if($i == 0)
                    {
                        $data['schedule_time']= $schedule_time;
                        $this->basic->insert_data('facebook_rx_cta_post',$data);
                        $insert_id = $this->db->insert_id();
                        if($i == 0 && $insert_counter == 0)
                            $parent_id = $insert_id;
                            
                       

                    }
                    if ($times == 0) {
                          $data['is_child'] = '1';
                          $data['parent_campaign_id'] = $parent_id;
                    
                    }
                    if($i >= 1)
                    {


                        $data['is_child'] = '1';
                        $current_schedule_time = $schedule_time;
                        $dateTime = new DateTime($current_schedule_time);
                        $p = $i*$x;
                        $dateTime->modify("+{$p} minutes");
                        $data['parent_campaign_id'] = $parent_id;
                        $change_time= $dateTime->format('Y-m-d H:i:s');
                        $data['schedule_time']= $change_time;
                        $data['page_ids'];
                        $this->basic->insert_data('facebook_rx_cta_post',$data);
                    }
               }

            }

      

            
            if($insert_counter > 0)
            $return_val=array("status"=>"1","message"=>$this->lang->line('Facebook post information has been updated successfully.')); 
            else $return_val=array("status"=>"0","message"=>$this->lang->line("something went wrong, please try again."));
            echo json_encode($return_val);


        }
    }


    public function cta_post_meta_info_grabber()
    {
        if($_POST)
        {
            $link= $this->input->post("link");
            $this->load->library("fb_rx_login");
            $response=$this->fb_rx_login->get_meta_tag_fb($link);
            echo json_encode($response);
        }
    } 


    public function cta_post_upload_link_preview()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();

        $ret=array();
        $output_dir = FCPATH."upload_caster/ctapost";
        if (isset($_FILES["myfile"])) {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="imagethumb_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            $ret[]= $filename;
            echo json_encode($filename);
        }
    }


    public function cta_post_delete_uploaded_file() // deletes the uploaded video to upload another one
    {
        if(!$_POST) exit();

        $output_dir = FCPATH."upload_caster/ctapost/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }
  

    public function delete_post()
    {
      if(!check_module_action_access($module_id=220,$actions=3,'check')){
        echo '0';
        exit();
      }

      if(!$_POST) exit();
      $id=$this->input->post("id");

      $post_info = $this->basic->get_data('facebook_rx_cta_post',array('where'=>array('id'=>$id)));
      if($post_info[0]['posting_status'] != '2')
      {
          //******************************//
          // delete data to useges log table
          $this->_delete_usage_log($module_id=220,$request=1);   
          //******************************//
      }

      // if($this->basic->delete_data("facebook_rx_cta_post",array("id"=>$id,"user_id"=>$this->user_id)))
      // echo "1";
      // else echo "0";

      if($this->basic->delete_data("facebook_rx_cta_post",array("id"=>$id,"user_id"=>$this->user_id)))
      {
          $this->basic->delete_data("facebook_rx_cta_post",array("parent_campaign_id"=>$id,"user_id"=>$this->user_id));
          echo "1";
      }
      else echo "0";
    }



    public function carousel_slider_post()
    {
        check_module_access($module_id=222);
        $data['page_title'] = $this->lang->line("Carousel/Slider Poster");
        $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info")));
        if(!empty($this->team_allowed_pages)){
            $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
        }
        $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);

        $data['body'] = 'carousel_slider_post/slider_post_list';
        $this->_viewcontroller($data);
    }


    public function carousel_slider_poster()
    {
        check_module_access($module_id=222);
        $data['body'] = 'carousel_slider_post/video_slider_poster';
        $data["time_interval"] = $this->get_periodic_time();
        $data['page_title'] = $this->lang->line('Video/Carousel Poster');
        $data["time_zone"]= $this->_time_zone_list();
       
        $user_infos = $this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("user_id"=>$this->user_id,"id"=>$this->session->userdata("facebook_rx_fb_user_info"))));

        if ( count( $user_infos ) == 0 ) 
            return redirect( base_url( 'facebook_rx_account_import/index' ), 'location' );

        $data["fb_user_info"] = $user_infos;
        if($this->config->item('facebook_poster_botenabled_pages') == '1'){
            $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),'bot_enabled'=>'1'));
            if(!empty($this->team_allowed_pages)){
                $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
            }
            $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        }
        else{
            $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info")));
            if(!empty($this->team_allowed_pages)){
                $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
            }
            $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        }
        $data["fb_group_info"]=$this->basic->get_data("facebook_rx_fb_group_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"))));

        $data["app_info"]=$this->basic->get_data("facebook_rx_config",array("where"=>array("id"=>$this->session->userdata("fb_rx_login_database_id"))));    
        $data['auto_reply_template'] = $this->basic->get_data('ultrapost_auto_reply',array("where"=>array('user_id'=>$this->user_id)),array('id','ultrapost_campaign_name'));

        $this->_viewcontroller($data);
    }


    public function carousel_slider_post_list_data()
    {
        $this->ajax_check();

        $pageID        = trim($this->input->post("page_id",true));
        $campaign_name   = trim($this->input->post("campaign_name",true));
        $post_date_range = $this->input->post("post_date_range",true);
        $display_columns = array("#",'id','campaign_name','campaign_type','page_or_group_or_user_name','post_type','actions','status','schedule_time','error_mesage');
        $search_columns = array('campaign_name','schedule_time');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where_simple = array();

        if($post_date_range!="")
        {
            $exp = explode('|', $post_date_range);
            $from_date = isset($exp[0])?$exp[0]:"";
            $to_date   = isset($exp[1])?$exp[1]:"";

            if($from_date!="Invalid date" && $to_date!="Invalid date")
            {
                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date   = date('Y-m-d', strtotime($to_date));
                $where_simple["Date_Format(last_updated_at,'%Y-%m-%d') >="] = $from_date;
                $where_simple["Date_Format(last_updated_at,'%Y-%m-%d') <="] = $to_date;
            }
        }

        if($pageID !="") $where_simple['facebook_rx_slider_post.page_group_user_id'] = $pageID;
        if($campaign_name !="") $where_simple['facebook_rx_slider_post.campaign_name like'] = "%".$campaign_name."%";

        $where_simple['facebook_rx_slider_post.user_id'] = $this->user_id;
        $where_simple['facebook_rx_slider_post.facebook_rx_fb_user_info_id'] = $this->session->userdata("facebook_rx_fb_user_info");

        $this->db->where("(is_child='0' or posting_status='2')");
        $where  = array('where'=>$where_simple);
        $select = array("facebook_rx_slider_post.*","facebook_rx_fb_page_info.page_id AS pageid");
        $join   = array('facebook_rx_fb_page_info'=>"facebook_rx_fb_page_info.id=facebook_rx_slider_post.page_group_user_id,left");

        $table = "facebook_rx_slider_post";
        $info = $this->basic->get_data($table,$where,$select,$join,$limit,$start,$order_by,$group_by='');

        $this->db->where("(is_child='0' or posting_status='2')");
        $total_rows_array = $this->basic->count_row($table,$where,$count=$table.".id",$join,$group_by='');
        $total_result = $total_rows_array[0]['total_rows'];

        for($i=0;$i<count($info);$i++) 
        {
            $action_count = 5;
            $posting_status = $info[$i]['posting_status'];
            $full_complete =  $info[$i]['full_complete'];
            $schedule_type = $info[$i]['schedule_type'];
            $parent_id = $info[$i]['parent_campaign_id'];

            $allschedulepost_check=$this->basic->get_data("facebook_rx_slider_post", array('where'=>array('parent_campaign_id'=>$info[$i]['id'])));
            
            if(count($allschedulepost_check)>0)
            {
                $completed_child_count=0;
                foreach ($allschedulepost_check as $key => $value12) 
                {                        
                    if ($value12['posting_status'] == '2' ) $completed_child_count++;
                }
                if(count($allschedulepost_check) == $completed_child_count) $is_all_posted='1';
                else $is_all_posted='0';
            }
            else
            {
                if($posting_status=='2') $is_all_posted='1';
                else $is_all_posted='0';
            }

            if(($posting_status=='0' || $posting_status == '2')  && ($schedule_type == 'later' && $parent_id == '0' && $is_all_posted==0))
                $info[$i]['status'] = '<div class="text-muted" style="min-width:120px;"><i class="fas fa-exclamation-circle"></i> '.$this->lang->line("not all completed").'</div>';
            else if( $posting_status == '2') 
                $info[$i]['status'] = '<div class="text-success" style="min-width:120px;"><i class="fas fa-check-circle"></i> '.$this->lang->line("Completed").'</div>';
            else if( $posting_status == '1') 
                $info[$i]['status'] = '<div class="text-warning" style="min-width:120px;"><i class="fas fa-spinner"></i> '.$this->lang->line("Processing").'</div>';
            else 
                $info[$i]['status'] = '<div class="text-danger" style="min-width:120px;"><i class="far fa-times-circle"></i> '.$this->lang->line("Pending").'</div>';

            $post_type = $info[$i]['post_type'];
            $post_type = ucfirst(str_replace("_post","",$post_type));
            if($post_type == "Carousel") $info[$i]['post_type'] = '<div style="min-width:90px;"><i class="fas fa-clone"></i> '.ucfirst(str_replace("_post","",$post_type)).'</div>';
            if($post_type == "Slider") $info[$i]['post_type'] = '<div style="min-width:90px;"><i class="fab fa-slideshare"></i> '.ucfirst(str_replace("_post","",$post_type)).'</div>';

            // publisher started 
            if($info[$i]['page_or_group_or_user'] == "page")
                $info[$i]['page_or_group_or_user_name'] = ucfirst($info[$i]['page_or_group_or_user'])." : "."<a target='_BLANK' data-toggle='tooltip' title='".$this->lang->line("Visit Page")."' href='https://facebook.com/".$info[$i]['pageid']."'>".$info[$i]['page_or_group_or_user_name']."</a>";
            else if($info[$i]['page_or_group_or_user'] == "group")
                $info[$i]['page_or_group_or_user_name'] = ucfirst($info[$i]['page_or_group_or_user'])." : "."<a target='_BLANK' data-toggle='tooltip' title='".$this->lang->line("Visit Page")."' href='https://facebook.com/".$info[$i]['pageid']."'>".$info[$i]['page_or_group_or_user_name']."</a>";
            else
                $info[$i]['page_or_group_or_user_name'] = ucfirst($info[$i]['page_or_group_or_user'])." : "."<a target='_BLANK' data-toggle='tooltip' title='".$this->lang->line("Visit Page")."' href='https://facebook.com/".$info[$i]['pageid']."'>".$info[$i]['page_or_group_or_user_name']."</a>";

            // $info[$i]['publisher'] =  $publisher;
            // publisher ended


            if($info[$i]['schedule_time'] != "0000-00-00 00:00:00")
                $info[$i]['schedule_time'] = '<div style="min-width:120px !important;">'.date("M j, y H:i",strtotime($info[$i]['schedule_time'])).'</div>';
            else 
                $info[$i]['schedule_time'] = '<div style="min-width:120px !important;" class="text-muted"><i class="fas fa-exclamation-circle"></i> '.$this->lang->line('Not Scheduled').'</div>';

                // $info[$i]['scheduled_at'] =  $scheduled_at;

            if($schedule_type == 'later' && $parent_id == '0')
                $info[$i]['campaign_type'] = "<div style='min-width:100px !important;'>".$this->lang->line("main campaign")."</div>";
            else if($schedule_type == 'now')
                $info[$i]['campaign_type'] = "<div style='min-width:100px !important;'>".$this->lang->line("single campaign")."</div>";
            else
                $info[$i]['campaign_type'] = "<div style='min-width:100px !important;'>".$this->lang->line("sub campaign")."</div>";


            // Action section started from here
            if($posting_status=='2')
                $post_url = "<a class='btn btn-circle btn-outline-info' data-toggle='tooltip' title='".$this->lang->line("Visit Post")."' target='_BLANK' href='".$info[$i]['post_url']."'><i class='fas fa-hand-point-right'></i></a>";
            else 
                $post_url = "<a class='btn btn-circle btn-light pointer text-muted' data-toggle='tooltip' title='".$this->lang->line("This post is not published yet.")."'><i class='fas fa-hand-point-right'></i></a>";

            if($post_type=="Slider" && $posting_status=='2')
                $embedUrl =  "<a href='#' data-toggle='tooltip' title='".$this->lang->line("Embed Code")."' id='".$info[$i]['id']."' class='btn btn-circle btn-outline-dark embed_code'><i class='fas fa-code'></i></a>";
            else
                $embedUrl =  "<a data-toggle='tooltip' title='".$this->lang->line("Embed code is only available for published video posts.")."' class='btn btn-circle btn-light pointer text-muted'><i class='fas fa-code'></i></a>";


            if(($posting_status=='0' || $posting_status == '2')  && ($schedule_type == 'later' && $parent_id == '0' && $is_all_posted==0))
                $editUrlVideoPost = "<a class='btn btn-circle btn-outline-warning' data-toggle='tooltip' title='".$this->lang->line("Edit")."' href='".base_url()."ultrapost/edit_carousel_slider/".$info[$i]['id']."'><i class='fas fa-edit'></i></a>";  
            else 
                $editUrlVideoPost = "<a class='btn btn-circle btn-light pointer text-muted' data-toggle='tooltip' title='".$this->lang->line("Only pending and scheduled campaigns are editable")."'><i class='fas fa-edit'></i></a>";  

            $info[$i]['delete'] = "<a data-toggle='tooltip' title='".$this->lang->line("Delete")."' id='".$info[$i]['id']."' class='delete btn btn-circle btn-outline-danger'><i class='fas fa-trash-alt'></i></a>"; 

            if($schedule_type == 'later' && $parent_id == '0') 
                $deleteUrl = "<a href='#' data-toggle='tooltip' title='".$this->lang->line("Delete")."' id='".$info[$i]['id']."' class='btn btn-circle btn-outline-danger delete_p'><i class='fas fa-trash-alt'></i></a>";
            else
                $deleteUrl = "<a href='#' data-toggle='tooltip' title='".$this->lang->line("Delete")."' id='".$info[$i]['id']."' class='btn btn-circle btn-outline-danger delete'><i class='fas fa-trash-alt'></i></a>";
    

            if ($schedule_type == 'later' && $parent_id == '0')
                 $report_url = "<a href='#' class='btn btn-circle btn-outline-primary view_report' table_id='".$info[$i]['id']."' data-toggle='tooltip' title='".$this->lang->line("report")."'><i class='fas fa-eye'></i></a>";  
            else 
                $report_url ="<a class='btn btn-circle btn-light pointer text-muted' data-toggle='tooltip' data-toggle='tooltip' title='".$this->lang->line("Only parent campaign has shown  report")."'><i class='fas fa-eye'></i></a>";

            $action_width = ($action_count*47)+20;
            $info[$i]['actions'] = '
            <div class="dropdown d-inline dropright">
              <button class="btn btn-outline-primary dropdown-toggle no_caret" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-briefcase"></i>
              </button>
              <div class="dropdown-menu mini_dropdown text-center" style="width:'.$action_width.'px !important">';
            $info[$i]['actions'] .= $post_url;
            $info[$i]['actions'] .= $report_url;
            $info[$i]['actions'] .= $embedUrl;
            $info[$i]['actions'] .= $editUrlVideoPost;
            $info[$i]['actions'] .= $deleteUrl;
            $info[$i]['actions'] .= "</div></div><script>$('[data-toggle=\"tooltip\"]').tooltip();</script>";
        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);

    }


    public function ajax_carousel_slide_report()
    {
        $this->ajax_check();

        $table_id = $this->input->post('table_id');
        $searching = $this->input->post('searching1',true);
        
        $display_columns = array("#",'id','page_or_group_or_user','post_type','post_id','posting_status','schedule_time','error_mesage');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sort_index = isset($_POST['order'][0]['column']) ? strval($_POST['order'][0]['column']) : 2;
        $sort = isset($display_columns[$sort_index]) ? $display_columns[$sort_index] : 'id';
        $order = isset($_POST['order'][0]['dir']) ? strval($_POST['order'][0]['dir']) : 'desc';
        $order_by=$sort." ".$order;

        $where = array();
        $where_simple=array();

        if($searching == '')
        {
            $where_simple['parent_campaign_id'] = $table_id;
            $or_where['id'] = $table_id;
            $where  = array('where'=>$where_simple,'or_where'=>$or_where);
        }

        $sql = '';
        if ($searching != '') 
        {
            $sql = "(schedule_time LIKE  '%".$searching."%' OR post_id LIKE '%".$searching."%' OR post_type LIKE '%".$searching."%') AND (`parent_campaign_id` = '$table_id' OR `id` = '$table_id')";
        }
        if($sql != '') $this->db->where($sql);

        $table = "facebook_rx_slider_post";
        $info = $this->basic->get_data($table,$where,$select='',$join='',$limit,$start,$order_by,$group_by='');

        $total_rows_array=$this->basic->count_row($table,$where,$count="id",$join='',$group_by='');
        $total_result=$total_rows_array[0]['total_rows'];

        for($i=0;$i<count($info);$i++)
        {   
            $posting_status = $info[$i]['posting_status'];
            $post_type = $info[$i]['post_type'];
            $post_id = $info[$i]['post_id'];

            if($post_id != '')
                $info[$i]['post_id'] = "<a target='_BLANK' href='https://facebook.com/".$post_id."'>".$post_id."</a>";
            
            // status section started
            if($posting_status=='2')
                $posting_status='<span class="text-success"><i class="fas fa-check-circle"></i> '.$this->lang->line("completed").'</span>';
            if($posting_status == '0')
                $posting_status='<span class="text-danger"><i class="far fa-times-circle"></i> '.$this->lang->line("pending").'</span>';
            if($posting_status == '1')
                $posting_status='<span class="text-warning"><i class="fas fa-spinner"></i> '.$this->lang->line("processing").'</span>';

            $info[$i]['posting_status'] = $posting_status;
            // end of status section

            $post_type = ucfirst(str_replace("_post","",$post_type));
            if($post_type == "Carousel") $info[$i]['post_type'] = ucfirst(str_replace("_post","",$post_type));
            if($post_type == "Slider") $info[$i]['post_type'] = ucfirst(str_replace("_post","",$post_type));


            // publisher started 
            if($info[$i]['page_or_group_or_user'] == "page")
                $info[$i]['page_or_group_or_user'] = ucfirst($info[$i]['page_or_group_or_user'])." : ".$info[$i]['page_or_group_or_user_name'];
            else if($info[$i]['page_or_group_or_user'] == "group")
                $info[$i]['page_or_group_or_user'] = ucfirst($info[$i]['page_or_group_or_user'])." : ".$info[$i]['page_or_group_or_user_name'];
            else
                $info[$i]['page_or_group_or_user'] = ucfirst($info[$i]['page_or_group_or_user'])." : ".$info[$i]['page_or_group_or_user_name'];
            // publisher ended

            if($info[$i]['schedule_time'] != "0000-00-00 00:00:00")
                $info[$i]['schedule_time'] = date("jS M y H:i",strtotime($info[$i]['schedule_time']));
            else 
                $info[$i]['schedule_time'] ='<i class="fas fa-exclamation-circle" title="'.$this->lang->line("Instantly posted").'"></i>'.$this->lang->line('Not Scheduled');

        }

        $data['draw'] = (int)$_POST['draw'] + 1;
        $data['recordsTotal'] = $total_result;
        $data['recordsFiltered'] = $total_result;
        $data['data'] = convertDataTableResult($info, $display_columns ,$start,$primary_key="id");

        echo json_encode($data);    
    }


    public function carousel_slider_add_post_action()
    {       
        check_module_action_access($module_id=222,$actions=1);
        $this->load->library("fb_rx_login");

         // ********************** slider = carousel | video = slider **********************
         if($_POST)
         {
             $post=$_POST;
             foreach ($post as $key => $value) 
             {
                 if(!is_array($value))
                     $temp = strip_tags($value);
                 else
                     $temp = $value;

                 $$key=$this->security->xss_clean($temp);
             }
         }

         $schedule_type = $this->input->post("schedule_type",true);
         if($schedule_type == "") $schedule_type = "later";

         $message = "";

         if($content_type == 'slider_submit')
         {
             $slider_post_content = array();      

             for($i=1;$i<=$content_counter;$i++)
             {
                 $temp_name = 'post_title_'.$i;
                 $temp_title = trim($this->input->post($temp_name));     

                 $temp_link = 'post_link_'.$i;
                 $temp_post_link = trim($this->input->post($temp_link));               
                 
                 $temp_desc = 'post_description_'.$i;
                 $temp_post_desc = trim($this->input->post($temp_desc));                
                 
                 $temp_image_link = 'post_image_link_'.$i;
                 $temp_post_image_link = trim($this->input->post($temp_image_link));                
                 
                 if($temp_post_image_link != '')
                 {
                     $slider_post_content[$i-1]['link'] = $temp_post_link;
                     $slider_post_content[$i-1]['name'] = $temp_title;
                     $slider_post_content[$i-1]['picture'] = $temp_post_image_link;
                     $slider_post_content[$i-1]['description'] = $temp_post_desc;
                 }
             }

             $data['carousel_content'] = json_encode($slider_post_content);
             $data['message'] = $slider_message;
             $data['post_type'] = 'carousel_post';
             $data['carousel_link'] = $slider_link;
             $message = $slider_message;

         } // end of if content type
         else
         {
             $video_post_images = array();

             for($i=1;$i<=$video_content_counter;$i++)
             {
                 $temp_image_link = 'video_image_link_'.$i;
                 $temp_video_image_link = trim($this->input->post($temp_image_link));                
                 
                 if($temp_video_image_link != '')
                 {
                     array_push($video_post_images, $temp_video_image_link);
                 }
             }

             $data['message'] = $video_message;
             $data['post_type'] = 'slider_post';
             $data['slider_images'] = json_encode($video_post_images);
             $data['slider_image_duration'] = $video_image_duration*1000;
             $data['slider_transition_duration'] = $video_image_transition_duration*1000;
             $message = $video_message;
         } //end fo else 
         $data['schedule_type'] = $schedule_type;
         $data['campaign_name'] = $campaign_name;
         $times = $repeat_times;
         if($times == '') $times = 0;
         $interval= $time_interval;
         $request_count = count($post_to_pages);
         $data['repeat_times'] = $times;
         $data['time_interval'] = $interval;
         if($schedule_type=="now")
         {
             $data["posting_status"] ='2';
             $data["full_complete"]  ='1';
         }
         else
         {
             $data["posting_status"] ='0';
         }


         if(!isset($post_to_pages) || !is_array($post_to_pages)) $post_to_pages=array();
         $page_ids_string = implode(',', $post_to_pages);
         $data["page_ids"] = $page_ids_string;
         if(!empty($post_to_pages))
         {
             $get_fb_userinfo_id = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("id"=>$post_to_pages[0],"user_id"=>$this->user_id)));
             $data['facebook_rx_fb_user_info_id'] = $get_fb_userinfo_id[0]['facebook_rx_fb_user_info_id'];
         }

         //************************************************//
         $status=$this->_check_usage($module_id=222,$request=count($post_to_pages));
        
         if($status=="2") 
         {
             $error_msg = $this->lang->line("sorry, your bulk limit is exceeded for this module.")."<a href='".site_url('payment/usage_history')."'>".$this->lang->line("click here to see usage log")."</a>";
             $return_val=array("status"=>"2","message"=>$error_msg);
             echo json_encode($return_val);
             exit();
         }

         if($status=="3") 
         {
             $error_msg = $this->lang->line("sorry, your monthly limit is exceeded for this module.")."<a href='".site_url('payment/usage_history')."'>".$this->lang->line("click here to see usage log")."</a>";
             $return_val=array("status"=>"2","message"=>$error_msg);
             echo json_encode($return_val);
             exit();
         }


         //************************************************//

         if(isset($auto_reply_template))
             $data["ultrapost_auto_reply_table_id"] = $auto_reply_template;

         if($schedule_type=="now")
         {
             $data["posting_status"] ='2';
             $data['time_zone'] = '';
             $data['schedule_time'] = "0000-00-00 00:00:00";
             $post_to_profile="No";

             if($post_to_profile!="No" && $content_type != 'slider_submit')
             {
                 $data['post_auto_comment_cron_jon_status'] = "0";
                 $data['post_auto_like_cron_jon_status'] = "0";
                 $data['post_auto_share_cron_jon_status'] = "0";
             }
             else
             {
                 $data['post_auto_comment_cron_jon_status'] = "1";
                 $data['post_auto_like_cron_jon_status'] = "1";
                 $data['post_auto_share_cron_jon_status'] = "1";
             }
         }
         else
         {
             $data["posting_status"] ='0';
             $data['time_zone'] = $time_zone;
             $data['schedule_time'] = $schedule_time;  
             $data['post_auto_comment_cron_jon_status'] = "0";
             $data['post_auto_like_cron_jon_status'] = "0";
             $data['post_auto_share_cron_jon_status'] = "0";        
         }

         $data['user_id'] = $this->user_id;        
         
         

         $insert_data_batch=array();

         $count=0;
         
        
         $user_id_array=array($this->user_id);
         $account_switching_id = $this->session->userdata("facebook_rx_fb_user_info"); // table > facebook_rx_fb_user_info.id
         $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$account_switching_id));
         if(!empty($this->team_allowed_pages)){
             $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
         }
         $page_info = $this->basic->get_data("facebook_rx_fb_page_info",$where);

        
         foreach ($page_info as $key => $value) 
         {
             if(!in_array($value["id"], $post_to_pages)) continue;

             $page_access_token =  isset($value["page_access_token"]) ? $value["page_access_token"] : ""; 
             $fb_page_id =  isset($value["page_id"]) ? $value["page_id"] : "";
             $page_table_id = $value['id'];

             $insert_data_batch[$count]=$data;
             $page_auto_id =  isset($value["id"]) ? $value["id"] : ""; 
             $insert_data_batch[$count]["page_group_user_id"]=$page_auto_id;
             $insert_data_batch[$count]["page_or_group_or_user"]="page";
             $insert_data_batch[$count]["page_or_group_or_user_name"]=isset($value["page_name"]) ? $value["page_name"] : ""; 

             $insert_data_batch[$count]["post_id"] = "";
             $insert_data_batch[$count]["post_url"] = ""; 

             if($schedule_type=="now")
             {
                if($this->is_demo == '1')
                    if($this->user_id == 1)
                        continue;

                 if($content_type == 'slider_submit') //carousel post
                 {
                     try
                     {
                         $response = $this->fb_rx_login->carousel_post($message=$slider_message,$link=$slider_link,$child_attachments=$slider_post_content,$scheduled_publish_time="",$post_access_token=$page_access_token,$page_id=$fb_page_id);                    
                         
                     }
                     catch(Exception $e) 
                     {
                       $error_msg = $e->getMessage();
                       $return_val=array("status"=>"0","message"=>$error_msg);
                       echo json_encode($return_val);
                       exit();
                     }
                 }
                 else // slider post
                 {
                     $v_i_duration = $video_image_duration*1000;
                     $v_i_transition = $video_image_transition_duration*1000;
                     try
                     {
                        $response = $this->fb_rx_login->post_image_video($description=$video_message,$image_urls=$video_post_images,$v_i_duration,$v_i_transition,$scheduled_publish_time="",$page_access_token,$fb_page_id);
                         
                        
                     }
                     catch(Exception $e) 
                     {
                       $error_msg = $e->getMessage();
                       $return_val=array("status"=>"0","message"=>$error_msg);
                       echo json_encode($return_val);
                       exit();
                     }

                 }   


                 $object_id=$response["id"];
                 $share_access_token = $page_access_token;

                 $insert_data_batch[$count]["last_updated_at"]= date("Y-m-d H:i:s");
                 $insert_data_batch[$count]["post_id"]= $object_id;
                 $temp_data=$this->fb_rx_login->get_post_permalink($object_id,$page_access_token);
                 $insert_data_batch[$count]["post_url"]= isset($temp_data["permalink_url"]) ? $temp_data["permalink_url"] : ""; 

                 $this->basic->insert_data('facebook_rx_slider_post',$insert_data_batch[$count]);
                 $this->_insert_usage_log($module_id=222,$request=count($post_to_pages));



                 if(isset($auto_reply_template) && $auto_reply_template != '0')
                 {

                    //************************************************//
                    $status=$this->_check_usage($module_id=204,$request=1);
                    if($status!="2" && $status!="3") 
                    {


                         $auto_reply_table_info = $this->basic->get_data('ultrapost_auto_reply',['where'=>['id' => $auto_reply_template ]]);

                         $auto_reply_table_data = [];

                         foreach ($auto_reply_table_info as $single_auto_reply_table_info) {

                             foreach ($single_auto_reply_table_info as $auto_key => $auto_value) {
                                 
                                 if($auto_key == 'id')
                                     continue;

                                 if($auto_key == 'page_ids')
                                     continue;

                                 if($auto_key == 'ultrapost_campaign_name')
                                     $auto_reply_table_data['auto_reply_campaign_name'] = $auto_value;
                                 else
                                     $auto_reply_table_data[$auto_key] = $auto_value;
                             }
                         }



                         $auto_reply_table_data['facebook_rx_fb_user_info_id'] = $value['facebook_rx_fb_user_info_id'];
                         $auto_reply_table_data['page_info_table_id'] = $value['id'];
                         $auto_reply_table_data['page_name'] = $value['page_name'];

                         if($content_type=="slider_post")
                             $auto_reply_table_data['post_id'] = $value['page_id'].'_'.$object_id;
                         else
                             $auto_reply_table_data['post_id'] = $object_id;

                         $auto_reply_table_data['post_created_at'] = date("Y-m-d h:i:s");
                         $auto_reply_table_data['post_description'] = $message;
                         $auto_reply_table_data['auto_private_reply_status'] = '0';

                         $auto_reply_table_data['auto_private_reply_count'] = 0;
                         $auto_reply_table_data['last_updated_at'] = date("Y-m-d h:i:s");
                         $auto_reply_table_data['last_reply_time'] = '';
                         $auto_reply_table_data['error_message'] = '';
                         $auto_reply_table_data['hidden_comment_count'] = 0;
                         $auto_reply_table_data['deleted_comment_count'] = 0;
                         $auto_reply_table_data['auto_comment_reply_count'] = 0;


                         $this->basic->insert_data('facebook_ex_autoreply', $auto_reply_table_data);

                     
                          $this->_insert_usage_log($module_id=204,$request=1);                        
                      }
                     //************************************************//
                 }
                
             }

             $count++;

         } 


        if($schedule_type=="now") 
            $return_val=array("status"=>"1","message"=>$this->lang->line("Facebook post has been performed successfully."));
        else
        {            

             $page_info_arr = array();
             $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$account_switching_id));
             if(!empty($this->team_allowed_pages)){
                 $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
             }
             $page_info = $this->basic->get_data("facebook_rx_fb_page_info",$where);

             foreach ($page_info as $key => $value) {
                if(!in_array($value["id"], $post_to_pages)) continue;
                $page_info_arr[$value["id"]] = $value['page_name'];
             }


             $parent_id='';
             for ($insert_counter=0; $insert_counter < $request_count; $insert_counter++) { 

                $data['page_or_group_or_user'] = 'page';
                $data['page_group_user_id'] = $post['post_to_pages'][$insert_counter];
                $data['page_or_group_or_user_name'] = $page_info_arr[$data['page_group_user_id']];

                $x=$interval;

               if($x=="" || $x==0){
                $x=rand(15,100);
               }


                for ($i=0; $i <= $times ; $i++) { 

                     if($i == 0)
                     {
                         $data['schedule_time']= $post['schedule_time'];
                         $this->basic->insert_data('facebook_rx_slider_post',$data);
                         $insert_id = $this->db->insert_id();
                        
                         if($i == 0 && $insert_counter == 0)
                             $parent_id = $insert_id;
                                     
                        

                     }
                     if ($times == 0) {
                           $data['is_child'] = '1';
                           $data['parent_campaign_id'] = $parent_id;
                                         
                     
                     }
                     if($i >= 1)
                     {


                         $data['is_child'] = '1';
                         $current_schedule_time = $post['schedule_time'];
                         $dateTime = new DateTime($current_schedule_time);
                         $p = $i*$x;
                         $dateTime->modify("+{$p} minutes");
                         $data['parent_campaign_id'] = $parent_id;
                         $change_time= $dateTime->format('Y-m-d H:i:s');
                         $data['schedule_time']= $change_time;
                          unset($data['page_ids']);
                          
                         $this->basic->insert_data('facebook_rx_slider_post',$data);
                     }
                }

             }


             if($insert_counter > 0)
             {
                 $number_request = count($insert_data_batch);
                 $this->_insert_usage_log($module_id=222,$request=$number_request);
                 $return_val=array("status"=>"1","message"=>$this->lang->line("Facebook post campaign has been created successfully."));
             }
             else $return_val=array("status"=>"0","message"=>$this->lang->line("something went wrong, please try again."));
        }

        echo json_encode($return_val);


    }


    public function edit_carousel_slider($video_post_id)
    {
        check_module_access($module_id=222);

        $table2 = "facebook_rx_slider_post";
        $where5656  = array('where'=>array('id'=>$video_post_id));

        $allschedulepost_check = $this->basic->get_data($table2,$where5656);

        foreach ($allschedulepost_check as $key => $value12) {
            if ($value12['posting_status'] == '2')
            {
                 $data['is_all_posted'] = 1; 
            }
            else
            {
                $data['is_all_posted'] =0;
            }
          
        }
        $data['body'] = 'carousel_slider_post/edit_video_slider_poster';
        $data['page_title'] = $this->lang->line('Edit Video/Carousel Poster');
        $data["time_zone"]= $this->_time_zone_list();
        $data["time_interval"] = $this->get_periodic_time();
        $data["fb_user_info"]=$this->basic->get_data("facebook_rx_fb_user_info",array("where"=>array("user_id"=>$this->user_id,"id"=>$this->session->userdata("facebook_rx_fb_user_info"))));
        
        if($this->config->item('facebook_poster_botenabled_pages') == '1'){
            $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"),'bot_enabled'=>'1'));
            if(!empty($this->team_allowed_pages)){
                $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
            }
            $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        }
        else{
            $where = array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info")));
            if(!empty($this->team_allowed_pages)){
                $where['where_in'] = array("facebook_rx_fb_page_info.id"=>$this->team_allowed_pages);
            }
            $data["fb_page_info"]=$this->basic->get_data("facebook_rx_fb_page_info",$where);
        }
        
        $data["fb_group_info"]=$this->basic->get_data("facebook_rx_fb_group_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$this->session->userdata("facebook_rx_fb_user_info"))));
        $data["app_info"]=$this->basic->get_data("facebook_rx_config",array("where"=>array("id"=>$this->session->userdata("fb_rx_login_database_id"))));
        $data['auto_reply_template'] = $this->basic->get_data('ultrapost_auto_reply',array("where"=>array('user_id'=>$this->user_id)),array('id','ultrapost_campaign_name'));


        $data["all_data"] = $this->basic->get_data("facebook_rx_slider_post",array("where"=>array("id"=>$video_post_id)));
        $this->_viewcontroller($data);
    }



    public function edit_carousel_slider_action()
    {
        check_module_action_access($module_id=222,$actions=2);

        if ($_SERVER['REQUEST_METHOD'] === 'GET')
        {
            redirect('home/access_forbidden', 'location');
        }

        if($_POST)
        {
            $post=$_POST;
            foreach ($post as $key => $value) 
            {
                if(!is_array($value))
                    $temp = strip_tags($value);
                else
                    $temp = $value;

                $$key=$this->security->xss_clean($temp);
            }
            $facebook_rx_fb_user_info_id= $this->input->post('facebook_rx_fb_user_info_id', true);
        }


        if($content_type == 'slider_submit')
        {
            $slider_post_content = array();      

            for($i=1;$i<=$content_counter;$i++)
            {
                $temp_name = 'post_title_'.$i;
                $temp_title = trim($this->input->post($temp_name));     

                $temp_link = 'post_link_'.$i;
                $temp_post_link = trim($this->input->post($temp_link));               
                
                $temp_desc = 'post_description_'.$i;
                $temp_post_desc = trim($this->input->post($temp_desc));                
                
                $temp_image_link = 'post_image_link_'.$i;
                $temp_post_image_link = trim($this->input->post($temp_image_link));                
                
                if($temp_post_image_link != '')
                {
                    $slider_post_content[$i-1]['link'] = $temp_post_link;
                    $slider_post_content[$i-1]['name'] = $temp_title;
                    $slider_post_content[$i-1]['picture'] = $temp_post_image_link;
                    $slider_post_content[$i-1]['description'] = $temp_post_desc;
                }
            }

            $data['carousel_content'] = json_encode($slider_post_content);
            $data['message'] = $slider_message;
            $data['post_type'] = 'carousel_post';
            $data['carousel_link'] = $slider_link;
        } // end of if content type
        else
        {
            $video_post_images = array();
            for($i=1;$i<=$video_content_counter;$i++)
            {
                $temp_image_link = 'video_image_link_'.$i;
                $temp_video_image_link = trim($this->input->post($temp_image_link));           
                if($temp_video_image_link != '')
                {
                    array_push($video_post_images, $temp_video_image_link);
                }
            }

            $data['message'] = $video_message;
            $data['post_type'] = 'slider_post';
            $data['slider_images'] = json_encode($video_post_images);
            $data['slider_image_duration'] = $video_image_duration*1000;
            $data['slider_transition_duration'] = $video_image_transition_duration*1000;
        } //end fo else 
        $times = 0;
        $times = $repeat_times;
        $data['campaign_name'] = $campaign_name;
        $data['auto_share_post'] = 0;
        $data["auto_share_to_profile"]= "0";
        $data['repeat_times'] = $repeat_times;
        $data['time_interval'] = $time_interval;
        
        $request_count = count($post_to_pages);
        if(!isset($auto_share_this_post_by_pages) || !is_array($auto_share_this_post_by_pages)) $auto_share_this_post_by_pages = array();
        $post_to_pages = array();
        if($this->input->post('post_to_pages', true) !== null)
            $post_to_pages = $this->input->post('post_to_pages', true);
        else
            $post_to_pages   = 0;   

        $data["auto_share_this_post_by_pages"] = json_encode($auto_share_this_post_by_pages);
        $data['auto_like_post'] = 0;
        $data['auto_private_reply'] = 0;
        $data['auto_private_reply_text'] = 0;
        $data['auto_comment'] = 0;
        $data['auto_comment_text'] = 0;
        $data['facebook_rx_fb_user_info_id'] =$facebook_rx_fb_user_info_id;
        if(isset($auto_reply_template))
            $data['ultrapost_auto_reply_table_id'] = $auto_reply_template;
        $page_ids_string = implode(',', $post_to_pages);
        $data["page_ids"] = $page_ids_string;
        $data['time_zone'] = $time_zone;
        $data['schedule_time'] = $schedule_time;
        $data['schedule_type'] = $schedule_type;
        $data['user_id'] = $this->user_id;


        // $this->basic->delete_data('facebook_rx_slider_post',array('id'=>$id));
        // $this->basic->delete_data('facebook_rx_slider_post',array('parent_campaign_id'=>$id));
        //$this->basic->delete_data('facebook_rx_slider_post',array('full_complete'=>'0'));
        $this->basic->delete_data('facebook_rx_slider_post',array('id'=>$id,'user_id'=>$this->user_id));
        $this->basic->delete_data('facebook_rx_slider_post',array('parent_campaign_id'=>$id,'full_complete'=>'0' ,'user_id'=>$this->user_id));
        $account_switching_id = $this->session->userdata("facebook_rx_fb_user_info");
        // $page_counter = 0;
        $user_id_array=array($this->user_id);

            $page_info_arr = array();
            $page_info = $this->basic->get_data("facebook_rx_fb_page_info",array("where"=>array("user_id"=>$this->user_id,"facebook_rx_fb_user_info_id"=>$account_switching_id)));

            foreach ($page_info as $key => $value) {
               if(!empty($this->team_allowed_pages) && !in_array($value['id'], $this->team_allowed_pages)) continue;
               if(!in_array($value["id"], $post_to_pages)) continue;
               $page_info_arr[$value["id"]] = $value['page_name'];
            }

            $parent_id='';
            for ($insert_counter=0; $insert_counter < $request_count; $insert_counter++) { 

               $data['page_or_group_or_user'] = 'page';
               $data['page_group_user_id'] = $post['post_to_pages'][$insert_counter];
               $data['page_or_group_or_user_name'] = $page_info_arr[$data['page_group_user_id']];

               $x=$time_interval;

               if($x=="" || $x==0){
                $x=rand(15,100);
               }

               for ($i=0; $i <=$times ; $i++) { 
                   
                    if($i == 0)
                    {
                        $data['schedule_time']= $post['schedule_time'];
                        $this->basic->insert_data('facebook_rx_slider_post',$data);
                        $insert_id = $this->db->insert_id();
                        if($i == 0 && $insert_counter == 0)
                            $parent_id = $insert_id;
                            
                       

                    }
                    if ($times == 0) {
                          $data['is_child'] = '1';
                          $data['parent_campaign_id'] = $parent_id;
                                        
                    
                    }
                    if($i >= 1)
                    {


                        $data['is_child'] = '1';
                        $current_schedule_time = $post['schedule_time'];
                        $dateTime = new DateTime($current_schedule_time);
                        $p = $i*$x;
                        $dateTime->modify("+{$p} minutes");
                        $data['parent_campaign_id'] = $parent_id;
                        $change_time= $dateTime->format('Y-m-d H:i:s');
                        $data['schedule_time']= $change_time;
                        $data['page_ids'];
                         
                        $this->basic->insert_data('facebook_rx_slider_post',$data);
                    }
               }

            }




        
        if($insert_counter > 0)
        $return_val=array("status"=>"1","message"=>$this->lang->line('Facebook post information has been updated successfully.')); 
        else $return_val=array("status"=>"0","message"=>$this->lang->line("something went wrong, please try again."));
        echo json_encode($return_val);

    }




    public function carousel_slider_delete_post()
    {
        check_module_action_access($module_id=222,$actions=3);
        if(!$_POST) exit();
        $id=$this->input->post("id");

        $post_info = $this->basic->get_data('facebook_rx_slider_post',array('where'=>array('id'=>$id)));
        if($post_info[0]['posting_status'] != '2')
        {
            //******************************//
            // delete data to useges log table
            $this->_delete_usage_log($module_id=222,$request=1);   
            //******************************//
        }
        if($this->basic->delete_data("facebook_rx_slider_post",array("id"=>$id,"user_id"=>$this->user_id)))
        {
            $this->basic->delete_data("facebook_rx_slider_post",array("parent_campaign_id"=>$id,"user_id"=>$this->user_id));
            echo "1";
        }
        else echo "0";
        // if($this->basic->delete_data("facebook_rx_slider_post",array("id"=>$id,"user_id"=>$this->user_id)))
        // echo "1";
        // else echo "0";
    }


    public function carousel_slider_get_embed_code()
    {
        if(!$_POST) exit();
        $id=$this->input->post("id");

        $video_data = $this->basic->get_data("facebook_rx_slider_post",array("where"=>array("id"=>$id,"user_id"=>$this->user_id)));

        $post_url= isset($video_data[0]['post_url']) ? $video_data[0]['post_url']:"";
       
       $embed_code = '&lt;iframe src="https://www.facebook.com/plugins/video.php?href='.$post_url.'&show_text=0&width=600" width="600" height="600" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"&gt;&lt;/iframe&gt;';

       $preview = '<iframe src="https://www.facebook.com/plugins/video.php?href='.$post_url.'&show_text=0&width=600" width="600" height="600" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>';

       $embed_html1 = '
       <div class="card">
         <div class="card-header">
           <h4>'.$this->lang->line('Copy this embed code').'</h4>
         </div>
         <div class="card-body">
           <pre class="language-javascript"><code class="dlanguage-javascript copy_code">'.$embed_code.'</code></pre><br>
           <center>'.$preview.'</center>
         </div>
       </div>';

        $embed_html1 .= '
        <script>
            $(document).ready(function() {
                Prism.highlightAll();
                $(".toolbar-item").find("a").addClass("copy");

                $(document).on("click", ".copy", function(event) {
                    event.preventDefault();

                    $(this).html("'.$this->lang->line('Copied!').'");
                    var that = $(this);
                    
                    var text = $(this).prev("code").text();
                    var temp = $("<input>");
                    $("body").append(temp);
                    temp.val(text).select();
                    document.execCommand("copy");
                    temp.remove();

                    setTimeout(function(){
                      $(that).html("'.$this->lang->line('Copy').'");
                    }, 2000); 

                });
            });
        </script>';

        echo $embed_html1;

    }






    public function carousel_slider_upload_image_only()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') exit();

        $ret=array();
        $output_dir = FCPATH."upload_caster/carousel_slider";
        if (isset($_FILES["myfile"])) {
            $error =$_FILES["myfile"]["error"];
            $post_fileName =$_FILES["myfile"]["name"];
            $post_fileName_array=explode(".", $post_fileName);
            $ext=array_pop($post_fileName_array);
            $filename=implode('.', $post_fileName_array);
            $filename="image_".$this->user_id."_".time().substr(uniqid(mt_rand(), true), 0, 6).".".$ext;

            $allow=".jpg,.jpeg,.png,.gif";
            $allow=str_replace('.', '', $allow);
            $allow=explode(',', $allow);
            if(!in_array(strtolower($ext), $allow)) 
            {
                echo json_encode("Are you kidding???");
                exit();
            }

            
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir.'/'.$filename);
            $ret[]= $filename;
            echo json_encode($filename);
        }
    }



    public function carousel_slider_delete_uploaded_file() // deletes the uploaded image to upload another one
    {

        $output_dir = FCPATH."upload_caster/carousel_slider/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
             $fileName =$_POST['name'];
             $fileName=str_replace("..",".",$fileName); //required. if somebody is trying parent folder files 
             $filePath = $output_dir. $fileName;
             if (file_exists($filePath)) 
             {
                unlink($filePath);
             }
        }
    }
       /* for Ultrapost */

    


    protected function get_emotion_list()
    {
        $dirTree=$this->scanAll(FCPATH."assets/images/emotions-fb");
        $map = array
        (
            "angel" => "o:)",
            "colonthree" => ":3",
            "confused" => "o.O",
            "cry" => ":'(",
            "devil" => "3:)",
            "frown" => ":(",
            "gasp" => ":O",
            "glasses" => "8)",
            "grin" => ":D",
            "grumpy" => ">:(",
            "heart" => "<3",
            "kiki" => "^_^",
            "kiss" => ":*",
            "pacman" => ":v",
            "smile" => ":)",
            "squint" => "-_-",
            "sunglasses" => "8|",
            "tongue" => ":p",
            "upset" => ">:O",
            "wink" => ";)"
            );
        $str = "";
        foreach ($dirTree as $value) 
        {
            $temp = array();
            $value['file'] = str_replace('\\','/', $value['file']);
            $temp =explode('/', $value["file"]);
            $filename = array_pop($temp);
            if(!strpos($filename,'.gif')) continue;
            $title = str_replace('.gif',"",$filename);
            $eval = $map[$title];
            $src= base_url('assets/images/emotions-fb/'.$filename);
            $str.= '&nbsp;&nbsp;<img eval="'.$eval.'" title="'.$title.'" style="cursor:pointer;"  class="emotion inline" src="'.$src.'"/>&nbsp;&nbsp;';
        }
        return $str;
    }

    protected function scanAll($myDir)
    {
        $dirTree = array();
        $di = new RecursiveDirectoryIterator($myDir,RecursiveDirectoryIterator::SKIP_DOTS);
        $i=0;
        foreach (new RecursiveIteratorIterator($di) as $filename) {
            $dir = str_replace($myDir, '', dirname($filename));
            $dir = str_replace('/', '>', substr($dir,1));
            $org_dir=str_replace("\\", "/", $dir);
            if($org_dir)
                $file_path = $org_dir. "/". basename($filename);
            else
                $file_path = basename($filename);
            $file_full_path=$myDir."/".$file_path;
            $file_size= filesize($file_full_path);
            $file_modification_time=filemtime($file_full_path);
            $dirTree[$i]['file'] = $file_full_path;
            $dirTree[$i]['size'] = $file_size;
            $dirTree[$i]['time'] =date("Y-m-d H:i:s",$file_modification_time);
            $i++;
        }
        return $dirTree;
    }


    public function get_periodic_time()
    {

        $all_periodic_time= array(
        
        
        '60' =>'every 1 hours',
        '120'=>'every 2 hours',
        '300'=>'every 5 hours',
        '1440'=>'every 1 days',
        '2880'=>'every 2 days',
        '4320'=>'every 3 days',
        '7200'=>'every 5 days',
        '8640'=>'every 6 days',
        '10080'=>'every 7 days',
        '43200'=>'every 1 months',
        '86400'=>'every 2 months',
        '259200'=>'every 6 months',
       );
        return $all_periodic_time;
    }


}