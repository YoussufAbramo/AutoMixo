<?php


function display_sent_message($whatsapp_bot_id=null,$fb_page_id=null,$message_content=null,$message_id=null,$conversation_time=null,$agent_name=null,$display_time=true,$message_status=null,$delivery_status_updated_at=null){
    $ci = &get_instance();
    if(empty($message_content)) return '';
    $message_content = json_decode($message_content,true);
    // echo '<pre>';
    // print_r($message_content);
    // echo '</pre>';
    $message_type = $message_content['message']['attachment']['type'] ?? null;
    $display_message = '';
    $chattime_style = '';
    if(isset($message_content['message']['text']) && !empty($message_content['message']['text'])){
        $text_content = $message_content['message']['text'];
        $is_html = preg_match("/<[^<]+>/",$text_content,$m) != 0 ? true : false;
        $display_message .= '<div class="chat-text">'.wrap_text($text_content,false,$is_html).'</div>';
    }
    else if($message_type=='location'){
       $display_message .= '<div class="chat-text p-0" style="background: transparent;">'.display_list($message_content['location'] ?? []).'</div>';
    }
    else if($message_type=='contacts'){
        $display_message .= '<div class="chat-text p-0" style="background: transparent;">'.display_list($message_content['contacts'] ?? []).'</div>';
    }
    else if($message_type=='interactive'){
        $display_message .= '<div class="chat-text p-0" style="background: transparent">';

        $display_message .= '<div class="card float-end mb-2 bg-light-primary" style="max-width: 330px;min-width: 300px;text-align: left;">';
        $display_message .= generate_interactive_content($message_content,'header');
        $display_message .= generate_interactive_content($message_content,'body');
        $display_message .= generate_interactive_content($message_content,'footer');
        $display_message .= '</div>';

        if(isset($message_content[$message_type]['action']['sections']))
        $display_message .= generate_reply_list($message_content[$message_type]['action']);

        if(isset($message_content[$message_type]['action']['buttons']))
        $display_message .= generate_reply_button($message_content[$message_type]['action']);

        $display_message .= '</div>';
        $chattime_style = 'text-align:right;';
    }
    else if($message_type=='template'){ //template message
        // dd($message_content);
        $display_message .= generate_template_content($message_content);
        $chattime_style = 'text-align:right;';
    }
    else{
        $media_url = $message_content['message']['attachment']['payload']['url'] ?? null;

        if($message_type=='image' ) // photo
        $display_message .= '<img src="'.$media_url.'" class="border mt-1 pointer file_preview" data_media_type="'.$message_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'">';

        else if($message_type=='video') // video
        $display_message .= '<img src="'.base_url('assets/images/media/video-player.png').'" class="border mt-1 pointer file_preview" data_media_type="'.$message_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'">';

        else{ // audio and docs
            $file_icon = '<i class="fas fa-file-pdf"></i>';
            if($message_type=='audio') $file_icon = '<i class="fas fa-microphone"></i>';
            $click_to_view  = $message_type=='audio' ? $ci->lang->line('Click to play audio') : $ci->lang->line('Click to view file');
            $display_message .= '<div class="chat-text bg-body text-primary fw-bold border pointer mt-1 file_preview" data_media_type="'.$message_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'" title="'.$click_to_view.'">'.$file_icon.' <span class="file-name">'.$click_to_view.'</span></div><br>';
        }
    }
    $conversation_time = !empty($conversation_time) ? display_message_time($conversation_time) : $ci->lang->line('Just now');
    $delivery_status_updated_at = !empty($delivery_status_updated_at) ? display_message_time($delivery_status_updated_at) : $ci->lang->line('Just now');
    $conversation_time_str = $display_time ? $conversation_time : '<span class="chat-time-js"></span>';
   
    $tick_class1 = 'text-muted d-inline';   
    $tick_class2 = 'text-muted d-none';   
    if($message_status=='read') {
        $tick_class1 = $tick_class2 = 'text-primary d-inline';
    }
    else if($message_status=='delivered') {
        $tick_class1 = $tick_class2 = 'text-muted d-inline';
    }
    else if($message_status=='failed') {
        $tick_class1 = 'text-danger d-inline';
        $tick_class2 = 'text-muted d-none';
    }   
    $message_tick1 = '<i class="fas fa-check tick tick1 '.$tick_class1.'"></i>';
    $message_tick2 = '<i class="fas fa-check tick tick2 '.$tick_class2.'"></i>';
    $message_tick = '<span class="message_status pointer ps-2" title="'.$message_status.' - '.$delivery_status_updated_at.'">'.$message_tick1.$message_tick2.'</span>';
    $agent_name_str = $agent_name!='' ? ' - '.$agent_name : ' - Bot';

    $response= '
    <div class="chat-item chat-right" style="">
         <div class="chat-details mr-0 ml-0" message_id="'.$message_id.'">
            '.$display_message.'
            <div class="chat-time" style="'.$chattime_style.'">'.$conversation_time_str.$agent_name_str.$message_tick.'</div>
         </div>
    </div>';

    //fix of breaking issue of message left/right, there was a missing closing div
    //if($message_type=='interactive') $response.='</div>';

    return $response;
}


function display_received_message($whatsapp_bot_id=null,$fb_page_id=null,$message_content=null,$message_id=null,$conversation_time=null,$display_time=true,$message_status=null,$delivery_status_updated_at=null){
    $ci = &get_instance();
    if(empty($message_content)) return '';

    $message_content = json_decode($message_content,true);
    // echo '<pre>';
    // print_r($message_content);
    // echo '</pre>';
    $display_message = '';
    $message_type   = $message_content['entry'][0]['messaging'][0]['message']['attachments'][0]['type'] ?? null;
    $rcn_message_type   = $message_content['entry'][0]['messaging'][0]['optin'] ?? null;
    $text_content = $message_content['entry'][0]['messaging'][0]['message']['text'] ?? null;
    $emoji_content  = $message_content['entry'][0]['messaging'][0]['message']['reaction']['emoji'] ?? null;
    $error_message  = $message_content['entry'][0]['messaging'][0]['message']['errors'][0]['details'] ?? null;
    $button_type = $message_content['entry'][0]['messaging'][0]['postback'] ?? null;
    $has_media = in_array($message_type,['image','video','audio','file','sticker']) ? true : false;

    if(!empty($error_message)){ //error
        $display_message .= '<div class="chat-text border text-muted fst-italic">'.wrap_text($error_message,false,false).'</div>';
    }
    if( !empty($text_content)){ //text
        $is_html = preg_match("/<[^<]+>/",$text_content,$m) != 0 ? true : false;
        $display_message .= '<div class="chat-text border">'.wrap_text(htmlspecialchars($text_content),false,$is_html).'</div>';
    }
    if($message_type=='reaction' && !empty($emoji_content)){ //emoji reaction
        $is_html = false;
        $display_message .= '<div class="chat-text border">'.wrap_text($emoji_content,false,$is_html).'</div>';
    }
    else if($message_type=='contacts'){ //contacts
        $contact_info =  $message_content['entry'][0]['changes'][0]['value']['messages'][0]['contacts'][0] ?? [];
        $display_message .= '<div class="chat-text p-0">'.display_list($contact_info).'</div>';
    }
    // else if($message_type=='interactive'){ //button click
    else if($button_type){ //button click
        $button_text =  $message_content['entry'][0]['messaging'][0]['postback']['title'] ?? null;
        if(empty($button_text)) $button_text =  $message_content['entry'][0]['messaging'][0]['postback']['title'] ?? null;
        if(!empty($button_text)) $display_message .= '<div class="chat-text border">'.wrap_text($button_text).'</div>';
    }
    else if($rcn_message_type){ //button click
        $button_text =  isset($message_content['entry'][0]['messaging'][0]['optin']['notification_messages_cta_text']) ?$message_content['entry'][0]['messaging'][0]['optin']['notification_messages_cta_text'] : 'GET_UPDATES';
        if(!empty($button_text)) $display_message .= '<div class="chat-text border">'.wrap_text($button_text).'</div>';
    }
    else if($message_type=='button'){ //payload button click
        $button_text =  $message_content['entry'][0]['changes'][0]['value']['messages'][0]['button']['text'] ?? null;
        if(!empty($button_text)) $display_message .= '<div class="chat-text border">'.wrap_text($button_text).'</div>';
    }
    else if($message_type=='order'){
        $order_items = $message_content['entry'][0]['changes'][0]['value']['messages'][0]['order']['product_items'] ?? [];
        $display_message = '<ul class="list-unstyled mb-0" style="width:300px;">';
        foreach ($order_items as $pro){
            $display_message.='<li class="media bg-white border">
            <!--<img class="mr-3" src="..." alt="Generic placeholder image">-->
            <div class="media-body p-3 py-2">
              <h6 class="mt-0 mb-1">'.$ci->lang->line("Product").' : '.$pro['product_retailer_id'].'</h6>
              '.$pro['currency'].' '.$pro['item_price'].' x '.$pro['quantity'].
                '</div>
          </li>';
        }
        $display_message .='</ul>';
        $display_message .='<div style="width: 300px"><a href="" onclick="return false;" class="btn btn-block btn-md bg-white text-primary markup mt-1">'.$ci->lang->line("View sent cart").'</a></div>';
    }
    else if($has_media)
    {
        $mime_type = $message_content['entry'][0]['messaging'][0]['message'][$message_type]['mime_type'] ?? null;
        $media_url = $message_content['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['url'] ?? null;
        $media_id = $message_content['entry'][0]['messaging'][0]['postback']['mid'] ?? null;
        if(in_array($message_type,['image','sticker'])){
            $display_message .= !empty($media_url)
                ? '<img src="'.$media_url.'" class="border mt-1 file_preview" data_media_type="'.$message_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'" alt="Image has not found">'
                : '<img src="'.base_url('assets/pre-loader/typing.gif').'" class="border mt-1" style="max-width:200px !important">';

        }
        else if($message_type=='video') {
            $display_message .= '<img src="'.base_url('assets/images/media/video-player.png').'" class="border mt-1 pointer file_preview" data_media_type="'.$message_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'">';
        }
        else { // document and audio
            $file_icon = $message_type=='audio' ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-file-pdf"></i>';
            $click_to_view  = $message_type=='audio' ? $ci->lang->line('Click to play audio') : $ci->lang->line('Click to view file');
            $display_message .= '<div class="chat-text bg-body text-primary fw-bold border pointer mt-1 file_preview" data_media_type="'.$message_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'" title="'.$click_to_view.'">'.$file_icon.' <span class="file-name">'.$click_to_view.'</span></div><br>';
        }

    }
    $conversation_time = !empty($conversation_time) ? display_message_time($conversation_time) : $ci->lang->line('Just now');
    $delivery_status_updated_at = !empty($delivery_status_updated_at) ? display_message_time($delivery_status_updated_at) : 'Just now';
    $conversation_time_str = $display_time ? $conversation_time : '<span class="chat-time-js"></span>';
    $tick_class1 = 'text-muted d-inline';   
    $tick_class2 = 'text-muted d-none';   
    if($message_status=='read') {
        $tick_class1 = $tick_class2 = 'text-primary d-inline';
    }
    else if($message_status=='delivered') {
        $tick_class1 = $tick_class2 = 'text-muted d-inline';
    }
    else if($message_status=='failed') {
        $tick_class1 = 'text-danger d-inline';
        $tick_class2 = 'text-muted d-none';
    }   
    $message_tick1 = '<i class="fas fa-check tick tick1 '.$tick_class1.'"></i>';
    $message_tick2 = '<i class="fas fa-check tick tick2 '.$tick_class2.'"></i>';
    $message_tick = '<span class="message_status pointer pe-2" title="'.ucwords($message_status).' - '.$delivery_status_updated_at.'">'.$message_tick1.$message_tick2.'</span>';
    $response = '
    <div class="chat-item chat-left" style="">
         <div class="chat-details mr-0 ml-0" message_id="'.$message_id.'">
            '.$display_message.'
            <div class="chat-time">'.$conversation_time_str.'</div>
         </div>
    </div>';

    return $response;
}


function display_list($list=[]){ // 3 level action
    $return = '<ul class="list-group list-group-numbered mb-2" style="max-width: 330px;min-width: 300px">';
    foreach ($list as $key=>$value){
        if(is_array($value)){
            foreach ($value as $key2=>$value2) {
                if(is_array($value2)){
                    foreach ($value2 as $key3=>$value3) {
                        if(!is_array($key3) && !is_array($value3))
                        $return.= make_element($key3,$value3);
                    }
                }
                else  $return.= make_element($key2,$value2);
            }
        }
        else $return.= make_element($key,$value);

    }
    $return .= '</ul>';
    return $return;
}


function make_element($item,$description){
    if($item=='wa_id') $item = 'WhatsApp ID';
    return '
        <li class="list-group-item d-flex justify-content-between align-items-start">
            <div class="ms-2 me-auto">
              <div class="fw-bold">'.ucwords(str_replace('_',' ',$item)).'</div>
              '.$description.'
            </div>
       </li>';
}


function generate_interactive_content($message_content=[],$content_type='header'){
    $ci = &get_instance();
    $display_message = '';
    $message_type = $message_content['type'] ?? null;
    if(isset($message_content[$message_type][$content_type])){
        $nested_message_type = $message_content[$message_type][$content_type]['type'] ?? 'text';
        if($nested_message_type=='text'){ //text
            $nested_message_content = $message_content[$message_type][$content_type]['text'] ?? '';
            $is_html = preg_match("/<[^<]+>/",$nested_message_content,$m) != 0 ? true : false;
            $display_message .= '<div class="card-body">';

            if($content_type=='header')
            $display_message .= '<h5 class="card-title text-dark">'.wrap_text($nested_message_content,false,$is_html).'</h5>';
            else $display_message .= '<p class="card-text text-dark">'.wrap_text($nested_message_content,false,$is_html).'</p>';

            $display_message .= '</div>';
        }
        else{ // media
            $nested_media_url = $message_content[$message_type][$content_type][$nested_message_type]['link'] ?? null;
            if($nested_message_type=='image' ) { // photo
                $display_message .= '<img src="' . $nested_media_url . '" class="mt-0 pointer file_preview" data_media_type="' . $nested_message_type . '" data_file_url="' . base64_encode($nested_media_url) . '" style="max-width:100% !important;width:100% !important;border-radius:7px 7px 0 0 !important;">';
            }
            else if($nested_message_type=='video') { // video
                $display_message .= '<img src="' . base_url('assets/images/media/video-player.png') . '" class="mt-0 pointer file_preview" data_media_type="' . $nested_message_type . '" data_file_url="' . base64_encode($nested_media_url) . '" style="max-width:100% !important;width:100% !important;border-radius:7px 7px 0 0 !important;">';
            }
            else{ // audio and docs
                $display_message .= '<div class="card-body">';
                $file_icon = '<i class="fas fa-file-pdf"></i>';
                if($nested_message_type=='audio') $file_icon = '<i class="fas fa-microphone"></i>';
                $click_to_view  = $nested_message_type=='audio' ? $ci->lang->line('Click to play audio') : $ci->lang->line('Click to view file');
                $display_message .= '<div class="w-100 px-2 text-primary fw-bold pointer mt-1 file_preview" data_media_type="'.$nested_message_type.'" data_file_url="'.base64_encode($nested_media_url).'" title="'.$click_to_view.'">'.$file_icon.' <span class="file-name">'.$click_to_view.'</span></div><br>';
                $display_message .= '</div>';
            }
        }
    }
    return $display_message;
}


function generate_reply_list($markup=null){
   if(empty($markup)) return '';

   $response = '<div>';
   if(isset($markup['button'])) $response .= '<a href="#" onclick="return false;" class="btn btn-md btn-block bg-white text-primary mt-1 mb-2">'.$markup['button'].'</a>';

   if(isset($markup['sections'])) {
       foreach ($markup['sections'] as $key => $value) {
           $value = (array) $value;
           $text = $value['title'] ?? 'Section';
           $response .= '
           <div class="card float-end mb-2" style="max-width: 330px;min-width: 300px;text-align: left;">
           <div class="card-body"><h5 class="card-title mb-0">'.$text.'</h5></div>';

           $rows = $value['rows'] ?? null;
           if(!empty($rows)){
               $response .= '<ul class="list-group list-group-flush">';
               foreach ($rows as $key2=>$value2){
                   $value2 = (array) $value2;
                   $tit = $value2['title'] ?? 'Title';
                   $des = isset($value2['description']) && !empty($value2['description']) ? '<p class="text-muted pt-1 mb-0">'.$value2['description'] .'</p>' : '';
                   $response .= '<li class="list-group-item"><a href="" class="text-primary" onclick="return false;">'.$tit.'</a>'.$des.'</li>';
               }
               $response .= '</ul>';
           }
           $response .= '</div>';
       }
   }
   $response .= '</div>';
   return $response;
}


function generate_reply_button($markup=null){
   if(empty($markup)) return '';

   $response = '<div>';
   if(isset($markup['buttons'])) {
       foreach ($markup['buttons'] as $key => $value) {
           $value = (array) $value;
           $tit = $value['reply']['title'] ?? 'Button';
           $response .= '<a href="" onclick="return false;" class="btn btn-block btn-md bg-white text-primary markup mt-1">'.$tit.'</a>';
       }
   }
   $response .= '</div>';
   return $response;
}

function generate_template_content($message_content=[]){
    $ci = &get_instance();
    $display_message = '';
    $display_message .= '<div class="chat-text p-0" style="background: transparent">';
    $display_message .= '<div class="card float-end mb-0 bg-light-primary" style="max-width: 330px;min-width: 300px;text-align: left;">';

    // $components = $message_content['components'] ?? [];
    $components = $message_content['message']['attachment']['payload'] ?? [];
    // dd($components);
    $header_exist = $body_exist = $footer_exist = $button_exist = $quick_reply_exist = $media_exist = $generic_messages_exist = $notification_messages_exist  = false;
    $header_type = $header_pos = $body_pos = $footer_pos = $button_pos = '';
    // foreach ($components as $val_com){
        // dd($val_com, $val_com->template_type);
        $temp_type = strtolower($components['template_type']) ?? '';
        // dd($temp_type);
        if($temp_type=='header'){
            $header_exist = true;
            // $header_pos = $key_com;
            $header_type = $components['format'] ?? 'text';
        }
        else if($temp_type=='body'){
            $body_exist = true;
            // $body_pos = $key_com;
        }
        else if($temp_type=='footer'){
            $footer_exist = true;
            // $footer_pos = $key_com;
        }
        else if($temp_type=='media'){
            $media_exist = true;
            // $footer_pos = $key_com;
        }
        else if($temp_type=='notification_messages'){
            $notification_messages_exist = true;
            // $footer_pos = $key_com;
        }
        else if($temp_type=='generic'){
            $generic_messages_exist = true;
            // $footer_pos = $key_com;
        }
        else if($temp_type=='button'){
            $button_exist = true;
            // $button_pos = $key_com;
            $quick_reply_exist =  isset($components['buttons'][0]['type']) && $components['buttons'][0]['type']=='quick_reply' ? true : false;
        }
    // }

    if($header_exist){
        if($header_type=='text'){
            $nested_message_content =$components['text'] ?? '';
            $is_html = preg_match("/<[^<]+>/",$nested_message_content,$m) != 0 ? true : false;
            $display_message .= '<div class="card-body">';
            $display_message .= '<h5 class="card-title text-dark">'.wrap_text($nested_message_content,false,$is_html).'</h5>';
            $display_message .= '</div>';
        }
        else{ // media
            $nested_media_url = $components['link'] ?? null;
            if($header_type=='image' ) { // photo
                $display_message .= '<img src="' . $nested_media_url . '" class="mt-0 pointer file_preview" data_media_type="' . $header_type . '" data_file_url="' . base64_encode($nested_media_url) . '" style="max-width:100% !important;width:100% !important;border-radius:7px 7px 0 0 !important;">';
            }
            else if($header_type=='video') { // video
                $display_message .= '<img src="' . base_url('assets/images/media/video-player.png') . '" class="mt-0 pointer file_preview" data_media_type="' . $header_type . '" data_file_url="' . base64_encode($nested_media_url) . '" style="max-width:100% !important;width:100% !important;border-radius:7px 7px 0 0 !important;">';
            }
            else{ // audio and docs
                $display_message .= '<div class="card-body">';
                $file_icon = '<i class="fas fa-file-pdf"></i>';
                if($header_type=='audio') $file_icon = '<i class="fas fa-microphone"></i>';
                $click_to_view  = $header_type=='audio' ? $ci->lang->line('Click to play audio') : $ci->lang->line('Click to view file');
                $display_message .= '<div class="w-100 px-2 text-primary fw-bold pointer mt-1 file_preview" data_media_type="'.$header_type.'" data_file_url="'.base64_encode($nested_media_url).'" title="'.$click_to_view.'">'.$file_icon.' <span class="file-name">'.$click_to_view.'</span></div><br>';
                $display_message .= '</div>';
            }
        }
    }

    $nested_message_content =$components['text'] ?? '';
    $is_html = preg_match("/<[^<]+>/",$nested_message_content,$m) != 0 ? true : false;
    $display_message .= '<div class="card-body">';
    $display_message .= '<p class="card-text text-dark">'.wrap_text($nested_message_content,false,$is_html).'</p>';
    $display_message .= '</div>';

    if($footer_exist){
        $nested_message_content =$components['text'] ?? '';
        $is_html = preg_match("/<[^<]+>/",$nested_message_content,$m) != 0 ? true : false;
        $display_message .= '<div class="card-body">';
        $display_message .= '<p class="card-text text-dark">'.wrap_text($nested_message_content,false,$is_html).'</p>';
        $display_message .= '</div>';
    }

    $display_message .= '</div>';

    if($media_exist){
        $media_type= $components['elements'][0]['media_type'] ?? '';
        $media_url = $components['elements'][0]['url'] ?? '';
        // $media_id = isset($message_content['recipient']['id']) ? $message_content['recipient']['id'] : '';
        // $response = $ci->fb_rx_login->instagram_get_media_url('',$media_id,'EAAPfrNSgjnkBOZBw7dm3OsVRIbW82VIffvEXG22sx4VyvhbpTgXY8aBGoHzjBAcAZBMgZCPnrSUUJ7tjn99pxDbxxyIaRkzZC2ZC2GvcZCYQ3dH5V2dH0byDWck16ZBCxrcpfZBWZCjwZAQP9TBIwTBBYqFM2Tk8ejHYJ8CscpCZAmjZC22RVp7upPw7tG4hjbvLVtxNpkRV1bnrjtSLNwoZD');
        // dd($response);

        $display_message = '<div class="card-body">';
        if($media_type=='video'){
            $display_message .= '<img src="'.base_url('assets/images/media/video-player.png').'" class="border mt-1 pointer file_preview" data_media_type="'.$media_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'">';
        }
        if($media_type == 'image'){
            $display_message .= !empty($media_url)
            ? '<img src="'.$media_url.'" class="border mt-1 file_preview" data_media_type="'.$media_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'" alt="Image has not found">'
            : '<img src="'.base_url('assets/pre-loader/typing.gif').'" class="border mt-1" style="max-width:200px !important">';
        }
    }

    if($notification_messages_exist){
        $media_type= 'image';
        $media_url = $components['image_url'] ?? '';
        $display_message = '<div class="card-body">';
        $display_message .= !empty($media_url)
        ? '<img src="'.$media_url.'" class="border mt-1 file_preview" data_media_type="'.$media_type.'" data_file_url="'.rtrim(base64_encode($media_url), '=').'" alt="Image has not found">'
        : '<img src="'.base_url('assets/pre-loader/typing.gif').'" class="border mt-1" style="max-width:200px !important">';
        $button_text = isset($components['notification_messages_cta_text']) ? $components['notification_messages_cta_text '] : 'GET_UPDATES';
        $button_header = $components['title'];

        $display_message .= '<div class="ml-auto" style="max-width: 330px;min-width: 300px;">';

        $display_message .= '<p class="mt-1 p-2 rounded" style="background-color:#e9f7ff; text-align:left">'.$button_header.'<br><small style="font-size:70%">Want to get additional message from us? You can opt out any time</small></p>';  
        $display_message .= '<a href="#" class="btn btn-md bg-white text-primary markup">'.$button_text.'</a>';  
        $display_message .= '</div>';

    }

    if($button_exist){
        $buttons = $components['buttons'] ?? [];

        $display_message .= '<div>';
        foreach ($buttons as $key => $value) {
            $tit = $value['title'] ?? 'Button';
            $typ = $value['type'] ?? '';
            $href = '';
            $onclick = 'onclick="return false;"';
            if($typ=='url'){
                $href = $value['url'] ?? '';
                $onclick = 'target="_BLANK"' ?? '';
            }
            $display_message .= '<a href="'.$href.'" '.$onclick.' class="btn btn-block btn-md bg-white text-primary markup mt-1">'.$tit.'</a>';
        }
        $display_message .= '</div>';
    }
    $display_message .= '</div>';

    return $display_message;
}


function generate_reply_markup($markup=null){
    if(empty($markup)) return '';
    if(!isset($markup->inline_keyboard[0]) && !isset($markup->keyboard[0])) return '';
    $items = $markup->inline_keyboard ?? $markup->keyboard;
    $response = '<div>';
    foreach ($items as $k=>$v){
        $keyboard = $markup->inline_keyboard[$k] ?? $markup->keyboard[$k];
        $keyboard = (array) $keyboard;
       foreach ($keyboard as $key=>$value){
           $value = (array) $value;
           $text = $value['text'] ?? 'Button';
           $url = $value['url'] ?? '';
           $response .= '<a href="'.$url.'" onclick="return false;" class="btn btn-md bg-white text-primary markup mt-1">'.$text.'</a>';
        }
    }
    $response .= '</div>';
    return $response;
}

if ( ! function_exists('make_message_preview'))
{
    function make_message_preview($message_content=null){
        $ci = &get_instance();
        $message_text = "";
        if(!empty($message_content)){
            $template_json = json_decode($message_content,true);
            if(isset($template_json['message']['attachment']['payload'])){ 
                $header_exist = $body_exist = $menual_sent= false;
                $header_type = $header_pos = $body_pos = '';
                // foreach ($template_json['message']['attachment']['payload'] as $key_com=>$val_com) {
                    $temp_type =isset($template_json['message']['attachment']['payload']['template_type']) ? strtolower($template_json['message']['attachment']['payload']['template_type']) : '';
                    $sent_type= isset($template_json['message']['attachment']['type']) ? true : false;
                    if ($temp_type == 'header' || $temp_type == 'button') {
                        $header_exist = true;
                        $header_type = $template_json['format'] ?? 'text';
                    } else if ($temp_type == 'body') {
                        $body_exist = true;
                    }
                    else if($sent_type){
                        $menual_sent = true;
                    }
                // }

                if($header_exist){
                    if($header_type=='text') $message_text = $template_json['message']['attachment']['payload']['text'] ?? '';
                    else if($header_type!='') $message_text = "#ATTACHMENT:".$header_type."#";
                }
                if($body_exist && $header_type!='text') $message_text .= $template_json['message']['attachment']['payload']['text'] ?? '';
                if($menual_sent){
                    $menual_sent_type =isset($template_json['message']['attachment']['type']) ?$template_json['message']['attachment']['type'] : '';
                    if($menual_sent_type=='audio') $message_text = '#ATTACHMENT:audio#';
                    else if($menual_sent_type=='video'){
                       $message_text = '#ATTACHMENT:video#';
                       }
                    else if($menual_sent_type=='image') $message_text = '#ATTACHMENT:image#';
                    else if($menual_sent_type=='file') $message_text = '#ATTACHMENT:file#';
                }
            }

            else if(isset($template_json['message']['text'])){ // message sent
                $message_text = $template_json['message']['text'] ?? '';
            }

            else if(isset($template_json['object'])){ // message received
                $message_type =  $template_json['entry'][0]['messaging'][0]['message']['attachments'][0]['type'] ?? 'text';
                if($message_type=='text'){
                    $message_text = $template_json['entry'][0]['messaging'][0]['message']['text'] ?? '';
                }
                else if($message_type=='reaction'){
                    // $emoji = $template_json['entry'][0]['changes'][0]['value']['messages'][0]['reaction']['emoji'] ?? '';
                    $emoji = ['entry'][0]['messaging'][0]['message']['reaction']['emoji'] ?? '';
                    $message_text = "#ATTACHMENT:".$message_type."#".$emoji;
                }
                else $message_text = "#ATTACHMENT:".$message_type."#";
            }
        }
        return $message_text;
    }
}

function format_preview_message($message_preview='',$wrap=40)
{
    $ci = &get_instance();
    $media_icon = '';
    if(!empty($message_preview)){
        if(str_starts_with($message_preview,'#ATTACHMENT:image#')){
            $message_preview = str_replace('#ATTACHMENT:image#','',$message_preview);
            $media_icon = '<i class="fas fa-image"></i> '.$ci->lang->line('Photo').' : ';
        }
        else if(str_starts_with($message_preview,'#ATTACHMENT:video#')){
            $message_preview = str_replace('#ATTACHMENT:video#','',$message_preview);
            $media_icon = '<i class="fas fa-play-circle"></i> '.$ci->lang->line('Video').' : ';
        }
        else if(str_starts_with($message_preview,'#ATTACHMENT:audio#')){
            $message_preview = str_replace('#ATTACHMENT:audio#','',$message_preview);
            $media_icon = '<i class="fas fa-microphone"></i> '.$ci->lang->line('Audio').' : ';
        }
        else if(str_starts_with($message_preview,'#ATTACHMENT:file#')){
            $message_preview = str_replace('#ATTACHMENT:file#','',$message_preview);
            $media_icon = '<i class="fas fa-file-image"></i> '.$ci->lang->line('File').' : ';
        }
        else if(str_starts_with($message_preview,'#ATTACHMENT:reaction#')){
            $message_preview = str_replace('#ATTACHMENT:reaction#','',$message_preview);
            $media_icon = '<i class="fas fa-smile"></i> '.$ci->lang->line('Reacted').' : ';
        }
    }
    $message_preview =  mb_substr($message_preview, 0, $wrap);
    $message_preview = htmlspecialchars($message_preview);
    if(!empty($media_icon)) $message_preview = $media_icon.' '.$message_preview;
    return $message_preview;
}

function wrap_text($message=null,$cut_long_text=false,$is_html=false){
    if(empty($message)) return '';
    //if(!$is_html) $message = wordwrap($message,70,'<br>',$cut_long_text);
    $message = preg_replace('"\b(https?://\S+)"', '<a class="fw-bold" target="_BLANK" href="$1">$1</a>', $message); // find and replace links with ancor tag
    $message = nl2br($message);
    return $message;
}

if ( ! function_exists('display_message_time'))
{
    function display_message_time($input_datetime=null){
        $ci = &get_instance();
        if(empty($input_datetime)) return $ci->lang->line('days ago');
        $curdate = date('Y-m-d');
        $date_format = 'n/j/y';
        if(date('Y-m-d',strtotime($input_datetime))==date('Y-m-d',strtotime($curdate))) $date_format = 'h:i A';
        $converted = convert_datetime_to_timezone($input_datetime,'',false,$date_format);
        return $converted;
    }
}
if ( ! function_exists('convert_datetime_to_timezone'))
{
  function convert_datetime_to_timezone($input_datetime='',$to_timezone='',$display_timezone=false,$date_format='',$from_timezone='UTC')
  {
    $ci = &get_instance();
      if(empty($input_datetime) || $input_datetime=='0000-00-00' || $input_datetime=='0000-00-00 00:00:00') return null;
      if(empty($to_timezone)) $to_timezone = $ci->config->item('time_zone');
      if(empty($to_timezone)) return $input_datetime;
      if(empty($date_format)) $date_format = 'jS M y H:i';
      $date = new DateTime($input_datetime,new DateTimeZone($from_timezone));
      $date->setTimezone(new DateTimeZone($to_timezone));
      $converted = $date->format($date_format);
      $get_timezone_list = get_timezone_list_new();
      if($display_timezone) {
          if(isset($get_timezone_list[$to_timezone])){
              $exp = explode(')',$get_timezone_list[$to_timezone]);
              $gmt_hour = isset($exp[0]) ? ltrim($exp[0],'(') : '';
              $converted.=' <i>'.str_replace('GMT','',$gmt_hour).'</i>';
          }
      }
      return $converted;
  }
}


if ( ! function_exists('get_timezone_list_new')){
    function get_timezone_list_new()
    {
        return $timezones =
        array(
            'America/Adak' => '(GMT-10:00) America/Adak (Hawaii-Aleutian Standard Time)',
            'America/Atka' => '(GMT-10:00) America/Atka (Hawaii-Aleutian Standard Time)',
            'America/Anchorage' => '(GMT-9:00) America/Anchorage (Alaska Standard Time)',
            'America/Juneau' => '(GMT-9:00) America/Juneau (Alaska Standard Time)',
            'America/Nome' => '(GMT-9:00) America/Nome (Alaska Standard Time)',
            'America/Yakutat' => '(GMT-9:00) America/Yakutat (Alaska Standard Time)',
            'America/Dawson' => '(GMT-8:00) America/Dawson (Pacific Standard Time)',
            'America/Ensenada' => '(GMT-8:00) America/Ensenada (Pacific Standard Time)',
            'America/Los_Angeles' => '(GMT-8:00) America/Los_Angeles (Pacific Standard Time)',
            'America/Tijuana' => '(GMT-8:00) America/Tijuana (Pacific Standard Time)',
            'America/Vancouver' => '(GMT-8:00) America/Vancouver (Pacific Standard Time)',
            'America/Whitehorse' => '(GMT-8:00) America/Whitehorse (Pacific Standard Time)',
            'Canada/Pacific' => '(GMT-8:00) Canada/Pacific (Pacific Standard Time)',
            'Canada/Yukon' => '(GMT-8:00) Canada/Yukon (Pacific Standard Time)',
            'Mexico/BajaNorte' => '(GMT-8:00) Mexico/BajaNorte (Pacific Standard Time)',
            'America/Boise' => '(GMT-7:00) America/Boise (Mountain Standard Time)',
            'America/Cambridge_Bay' => '(GMT-7:00) America/Cambridge_Bay (Mountain Standard Time)',
            'America/Chihuahua' => '(GMT-7:00) America/Chihuahua (Mountain Standard Time)',
            'America/Dawson_Creek' => '(GMT-7:00) America/Dawson_Creek (Mountain Standard Time)',
            'America/Denver' => '(GMT-7:00) America/Denver (Mountain Standard Time)',
            'America/Edmonton' => '(GMT-7:00) America/Edmonton (Mountain Standard Time)',
            'America/Hermosillo' => '(GMT-7:00) America/Hermosillo (Mountain Standard Time)',
            'America/Inuvik' => '(GMT-7:00) America/Inuvik (Mountain Standard Time)',
            'America/Mazatlan' => '(GMT-7:00) America/Mazatlan (Mountain Standard Time)',
            'America/Phoenix' => '(GMT-7:00) America/Phoenix (Mountain Standard Time)',
            'America/Shiprock' => '(GMT-7:00) America/Shiprock (Mountain Standard Time)',
            'America/Yellowknife' => '(GMT-7:00) America/Yellowknife (Mountain Standard Time)',
            'Canada/Mountain' => '(GMT-7:00) Canada/Mountain (Mountain Standard Time)',
            'Mexico/BajaSur' => '(GMT-7:00) Mexico/BajaSur (Mountain Standard Time)',
            'America/Belize' => '(GMT-6:00) America/Belize (Central Standard Time)',
            'America/Cancun' => '(GMT-6:00) America/Cancun (Central Standard Time)',
            'America/Chicago' => '(GMT-6:00) America/Chicago (Central Standard Time)',
            'America/Costa_Rica' => '(GMT-6:00) America/Costa_Rica (Central Standard Time)',
            'America/El_Salvador' => '(GMT-6:00) America/El_Salvador (Central Standard Time)',
            'America/Guatemala' => '(GMT-6:00) America/Guatemala (Central Standard Time)',
            'America/Knox_IN' => '(GMT-6:00) America/Knox_IN (Central Standard Time)',
            'America/Managua' => '(GMT-6:00) America/Managua (Central Standard Time)',
            'America/Menominee' => '(GMT-6:00) America/Menominee (Central Standard Time)',
            'America/Merida' => '(GMT-6:00) America/Merida (Central Standard Time)',
            'America/Mexico_City' => '(GMT-6:00) America/Mexico_City (Central Standard Time)',
            'America/Monterrey' => '(GMT-6:00) America/Monterrey (Central Standard Time)',
            'America/Rainy_River' => '(GMT-6:00) America/Rainy_River (Central Standard Time)',
            'America/Rankin_Inlet' => '(GMT-6:00) America/Rankin_Inlet (Central Standard Time)',
            'America/Regina' => '(GMT-6:00) America/Regina (Central Standard Time)',
            'America/Swift_Current' => '(GMT-6:00) America/Swift_Current (Central Standard Time)',
            'America/Tegucigalpa' => '(GMT-6:00) America/Tegucigalpa (Central Standard Time)',
            'America/Winnipeg' => '(GMT-6:00) America/Winnipeg (Central Standard Time)',
            'Canada/Central' => '(GMT-6:00) Canada/Central (Central Standard Time)',
            'Canada/East-Saskatchewan' => '(GMT-6:00) Canada/East-Saskatchewan (Central Standard Time)',
            'Canada/Saskatchewan' => '(GMT-6:00) Canada/Saskatchewan (Central Standard Time)',
            'Chile/EasterIsland' => '(GMT-6:00) Chile/EasterIsland (Easter Is. Time)',
            'Mexico/General' => '(GMT-6:00) Mexico/General (Central Standard Time)',
            'America/Atikokan' => '(GMT-5:00) America/Atikokan (Eastern Standard Time)',
            'America/Bogota' => '(GMT-5:00) America/Bogota (Colombia Time)',
            'America/Cayman' => '(GMT-5:00) America/Cayman (Eastern Standard Time)',
            'America/Coral_Harbour' => '(GMT-5:00) America/Coral_Harbour (Eastern Standard Time)',
            'America/Detroit' => '(GMT-5:00) America/Detroit (Eastern Standard Time)',
            'America/Fort_Wayne' => '(GMT-5:00) America/Fort_Wayne (Eastern Standard Time)',
            'America/Grand_Turk' => '(GMT-5:00) America/Grand_Turk (Eastern Standard Time)',
            'America/Guayaquil' => '(GMT-5:00) America/Guayaquil (Ecuador Time)',
            'America/Havana' => '(GMT-5:00) America/Havana (Cuba Standard Time)',
            'America/Indianapolis' => '(GMT-5:00) America/Indianapolis (Eastern Standard Time)',
            'America/Iqaluit' => '(GMT-5:00) America/Iqaluit (Eastern Standard Time)',
            'America/Jamaica' => '(GMT-5:00) America/Jamaica (Eastern Standard Time)',
            'America/Lima' => '(GMT-5:00) America/Lima (Peru Time)',
            'America/Louisville' => '(GMT-5:00) America/Louisville (Eastern Standard Time)',
            'America/Montreal' => '(GMT-5:00) America/Montreal (Eastern Standard Time)',
            'America/Nassau' => '(GMT-5:00) America/Nassau (Eastern Standard Time)',
            'America/New_York' => '(GMT-5:00) America/New_York (Eastern Standard Time)',
            'America/Nipigon' => '(GMT-5:00) America/Nipigon (Eastern Standard Time)',
            'America/Panama' => '(GMT-5:00) America/Panama (Eastern Standard Time)',
            'America/Pangnirtung' => '(GMT-5:00) America/Pangnirtung (Eastern Standard Time)',
            'America/Port-au-Prince' => '(GMT-5:00) America/Port-au-Prince (Eastern Standard Time)',
            'America/Resolute' => '(GMT-5:00) America/Resolute (Eastern Standard Time)',
            'America/Thunder_Bay' => '(GMT-5:00) America/Thunder_Bay (Eastern Standard Time)',
            'America/Toronto' => '(GMT-5:00) America/Toronto (Eastern Standard Time)',
            'Canada/Eastern' => '(GMT-5:00) Canada/Eastern (Eastern Standard Time)',
            'America/Caracas' => '(GMT-4:-30) America/Caracas (Venezuela Time)',
            'America/Anguilla' => '(GMT-4:00) America/Anguilla (Atlantic Standard Time)',
            'America/Antigua' => '(GMT-4:00) America/Antigua (Atlantic Standard Time)',
            'America/Aruba' => '(GMT-4:00) America/Aruba (Atlantic Standard Time)',
            'America/Asuncion' => '(GMT-4:00) America/Asuncion (Paraguay Time)',
            'America/Barbados' => '(GMT-4:00) America/Barbados (Atlantic Standard Time)',
            'America/Blanc-Sablon' => '(GMT-4:00) America/Blanc-Sablon (Atlantic Standard Time)',
            'America/Boa_Vista' => '(GMT-4:00) America/Boa_Vista (Amazon Time)',
            'America/Campo_Grande' => '(GMT-4:00) America/Campo_Grande (Amazon Time)',
            'America/Cuiaba' => '(GMT-4:00) America/Cuiaba (Amazon Time)',
            'America/Curacao' => '(GMT-4:00) America/Curacao (Atlantic Standard Time)',
            'America/Dominica' => '(GMT-4:00) America/Dominica (Atlantic Standard Time)',
            'America/Eirunepe' => '(GMT-4:00) America/Eirunepe (Amazon Time)',
            'America/Glace_Bay' => '(GMT-4:00) America/Glace_Bay (Atlantic Standard Time)',
            'America/Goose_Bay' => '(GMT-4:00) America/Goose_Bay (Atlantic Standard Time)',
            'America/Grenada' => '(GMT-4:00) America/Grenada (Atlantic Standard Time)',
            'America/Guadeloupe' => '(GMT-4:00) America/Guadeloupe (Atlantic Standard Time)',
            'America/Guyana' => '(GMT-4:00) America/Guyana (Guyana Time)',
            'America/Halifax' => '(GMT-4:00) America/Halifax (Atlantic Standard Time)',
            'America/La_Paz' => '(GMT-4:00) America/La_Paz (Bolivia Time)',
            'America/Manaus' => '(GMT-4:00) America/Manaus (Amazon Time)',
            'America/Marigot' => '(GMT-4:00) America/Marigot (Atlantic Standard Time)',
            'America/Martinique' => '(GMT-4:00) America/Martinique (Atlantic Standard Time)',
            'America/Moncton' => '(GMT-4:00) America/Moncton (Atlantic Standard Time)',
            'America/Montserrat' => '(GMT-4:00) America/Montserrat (Atlantic Standard Time)',
            'America/Port_of_Spain' => '(GMT-4:00) America/Port_of_Spain (Atlantic Standard Time)',
            'America/Porto_Acre' => '(GMT-4:00) America/Porto_Acre (Amazon Time)',
            'America/Porto_Velho' => '(GMT-4:00) America/Porto_Velho (Amazon Time)',
            'America/Puerto_Rico' => '(GMT-4:00) America/Puerto_Rico (Atlantic Standard Time)',
            'America/Rio_Branco' => '(GMT-4:00) America/Rio_Branco (Amazon Time)',
            'America/Santiago' => '(GMT-4:00) America/Santiago (Chile Time)',
            'America/Santo_Domingo' => '(GMT-4:00) America/Santo_Domingo (Atlantic Standard Time)',
            'America/St_Barthelemy' => '(GMT-4:00) America/St_Barthelemy (Atlantic Standard Time)',
            'America/St_Kitts' => '(GMT-4:00) America/St_Kitts (Atlantic Standard Time)',
            'America/St_Lucia' => '(GMT-4:00) America/St_Lucia (Atlantic Standard Time)',
            'America/St_Thomas' => '(GMT-4:00) America/St_Thomas (Atlantic Standard Time)',
            'America/St_Vincent' => '(GMT-4:00) America/St_Vincent (Atlantic Standard Time)',
            'America/Thule' => '(GMT-4:00) America/Thule (Atlantic Standard Time)',
            'America/Tortola' => '(GMT-4:00) America/Tortola (Atlantic Standard Time)',
            'America/Virgin' => '(GMT-4:00) America/Virgin (Atlantic Standard Time)',
            'Antarctica/Palmer' => '(GMT-4:00) Antarctica/Palmer (Chile Time)',
            'Atlantic/Bermuda' => '(GMT-4:00) Atlantic/Bermuda (Atlantic Standard Time)',
            'Atlantic/Stanley' => '(GMT-4:00) Atlantic/Stanley (Falkland Is. Time)',
            'Brazil/Acre' => '(GMT-4:00) Brazil/Acre (Amazon Time)',
            'Brazil/West' => '(GMT-4:00) Brazil/West (Amazon Time)',
            'Canada/Atlantic' => '(GMT-4:00) Canada/Atlantic (Atlantic Standard Time)',
            'Chile/Continental' => '(GMT-4:00) Chile/Continental (Chile Time)',
            'America/St_Johns' => '(GMT-3:-30) America/St_Johns (Newfoundland Standard Time)',
            'Canada/Newfoundland' => '(GMT-3:-30) Canada/Newfoundland (Newfoundland Standard Time)',
            'America/Araguaina' => '(GMT-3:00) America/Araguaina (Brasilia Time)',
            'America/Bahia' => '(GMT-3:00) America/Bahia (Brasilia Time)',
            'America/Belem' => '(GMT-3:00) America/Belem (Brasilia Time)',
            'America/Buenos_Aires' => '(GMT-3:00) America/Buenos_Aires (Argentine Time)',
            'America/Catamarca' => '(GMT-3:00) America/Catamarca (Argentine Time)',
            'America/Cayenne' => '(GMT-3:00) America/Cayenne (French Guiana Time)',
            'America/Cordoba' => '(GMT-3:00) America/Cordoba (Argentine Time)',
            'America/Fortaleza' => '(GMT-3:00) America/Fortaleza (Brasilia Time)',
            'America/Godthab' => '(GMT-3:00) America/Godthab (Western Greenland Time)',
            'America/Jujuy' => '(GMT-3:00) America/Jujuy (Argentine Time)',
            'America/Maceio' => '(GMT-3:00) America/Maceio (Brasilia Time)',
            'America/Mendoza' => '(GMT-3:00) America/Mendoza (Argentine Time)',
            'America/Miquelon' => '(GMT-3:00) America/Miquelon (Pierre & Miquelon Standard Time)',
            'America/Montevideo' => '(GMT-3:00) America/Montevideo (Uruguay Time)',
            'America/Paramaribo' => '(GMT-3:00) America/Paramaribo (Suriname Time)',
            'America/Recife' => '(GMT-3:00) America/Recife (Brasilia Time)',
            'America/Rosario' => '(GMT-3:00) America/Rosario (Argentine Time)',
            'America/Santarem' => '(GMT-3:00) America/Santarem (Brasilia Time)',
            'America/Sao_Paulo' => '(GMT-3:00) America/Sao_Paulo (Brasilia Time)',
            'Antarctica/Rothera' => '(GMT-3:00) Antarctica/Rothera (Rothera Time)',
            'Brazil/East' => '(GMT-3:00) Brazil/East (Brasilia Time)',
            'America/Noronha' => '(GMT-2:00) America/Noronha (Fernando de Noronha Time)',
            'Atlantic/South_Georgia' => '(GMT-2:00) Atlantic/South_Georgia (South Georgia Standard Time)',
            'Brazil/DeNoronha' => '(GMT-2:00) Brazil/DeNoronha (Fernando de Noronha Time)',
            'America/Scoresbysund' => '(GMT-1:00) America/Scoresbysund (Eastern Greenland Time)',
            'Atlantic/Azores' => '(GMT-1:00) Atlantic/Azores (Azores Time)',
            'Atlantic/Cape_Verde' => '(GMT-1:00) Atlantic/Cape_Verde (Cape Verde Time)',
            'Africa/Abidjan' => '(GMT+0:00) Africa/Abidjan (Greenwich Mean Time)',
            'Africa/Accra' => '(GMT+0:00) Africa/Accra (Ghana Mean Time)',
            'Africa/Bamako' => '(GMT+0:00) Africa/Bamako (Greenwich Mean Time)',
            'Africa/Banjul' => '(GMT+0:00) Africa/Banjul (Greenwich Mean Time)',
            'Africa/Bissau' => '(GMT+0:00) Africa/Bissau (Greenwich Mean Time)',
            'Africa/Casablanca' => '(GMT+0:00) Africa/Casablanca (Western European Time)',
            'Africa/Conakry' => '(GMT+0:00) Africa/Conakry (Greenwich Mean Time)',
            'Africa/Dakar' => '(GMT+0:00) Africa/Dakar (Greenwich Mean Time)',
            'Africa/El_Aaiun' => '(GMT+0:00) Africa/El_Aaiun (Western European Time)',
            'Africa/Freetown' => '(GMT+0:00) Africa/Freetown (Greenwich Mean Time)',
            'Africa/Lome' => '(GMT+0:00) Africa/Lome (Greenwich Mean Time)',
            'Africa/Monrovia' => '(GMT+0:00) Africa/Monrovia (Greenwich Mean Time)',
            'Africa/Nouakchott' => '(GMT+0:00) Africa/Nouakchott (Greenwich Mean Time)',
            'Africa/Ouagadougou' => '(GMT+0:00) Africa/Ouagadougou (Greenwich Mean Time)',
            'Africa/Sao_Tome' => '(GMT+0:00) Africa/Sao_Tome (Greenwich Mean Time)',
            'Africa/Timbuktu' => '(GMT+0:00) Africa/Timbuktu (Greenwich Mean Time)',
            'America/Danmarkshavn' => '(GMT+0:00) America/Danmarkshavn (Greenwich Mean Time)',
            'Atlantic/Canary' => '(GMT+0:00) Atlantic/Canary (Western European Time)',
            'Atlantic/Faeroe' => '(GMT+0:00) Atlantic/Faeroe (Western European Time)',
            'Atlantic/Faroe' => '(GMT+0:00) Atlantic/Faroe (Western European Time)',
            'Atlantic/Madeira' => '(GMT+0:00) Atlantic/Madeira (Western European Time)',
            'Atlantic/Reykjavik' => '(GMT+0:00) Atlantic/Reykjavik (Greenwich Mean Time)',
            'Atlantic/St_Helena' => '(GMT+0:00) Atlantic/St_Helena (Greenwich Mean Time)',
            'Europe/Belfast' => '(GMT+0:00) Europe/Belfast (Greenwich Mean Time)',
            'Europe/Dublin' => '(GMT+0:00) Europe/Dublin (Greenwich Mean Time)',
            'Europe/Guernsey' => '(GMT+0:00) Europe/Guernsey (Greenwich Mean Time)',
            'Europe/Isle_of_Man' => '(GMT+0:00) Europe/Isle_of_Man (Greenwich Mean Time)',
            'Europe/Jersey' => '(GMT+0:00) Europe/Jersey (Greenwich Mean Time)',
            'Europe/Lisbon' => '(GMT+0:00) Europe/Lisbon (Western European Time)',
            'Europe/London' => '(GMT+0:00) Europe/London (Greenwich Mean Time)',
            'Africa/Algiers' => '(GMT+1:00) Africa/Algiers (Central European Time)',
            'Africa/Bangui' => '(GMT+1:00) Africa/Bangui (Western African Time)',
            'Africa/Brazzaville' => '(GMT+1:00) Africa/Brazzaville (Western African Time)',
            'Africa/Ceuta' => '(GMT+1:00) Africa/Ceuta (Central European Time)',
            'Africa/Douala' => '(GMT+1:00) Africa/Douala (Western African Time)',
            'Africa/Kinshasa' => '(GMT+1:00) Africa/Kinshasa (Western African Time)',
            'Africa/Lagos' => '(GMT+1:00) Africa/Lagos (Western African Time)',
            'Africa/Libreville' => '(GMT+1:00) Africa/Libreville (Western African Time)',
            'Africa/Luanda' => '(GMT+1:00) Africa/Luanda (Western African Time)',
            'Africa/Malabo' => '(GMT+1:00) Africa/Malabo (Western African Time)',
            'Africa/Ndjamena' => '(GMT+1:00) Africa/Ndjamena (Western African Time)',
            'Africa/Niamey' => '(GMT+1:00) Africa/Niamey (Western African Time)',
            'Africa/Porto-Novo' => '(GMT+1:00) Africa/Porto-Novo (Western African Time)',
            'Africa/Tunis' => '(GMT+1:00) Africa/Tunis (Central European Time)',
            'Africa/Windhoek' => '(GMT+1:00) Africa/Windhoek (Western African Time)',
            'Arctic/Longyearbyen' => '(GMT+1:00) Arctic/Longyearbyen (Central European Time)',
            'Atlantic/Jan_Mayen' => '(GMT+1:00) Atlantic/Jan_Mayen (Central European Time)',
            'Europe/Amsterdam' => '(GMT+1:00) Europe/Amsterdam (Central European Time)',
            'Europe/Andorra' => '(GMT+1:00) Europe/Andorra (Central European Time)',
            'Europe/Belgrade' => '(GMT+1:00) Europe/Belgrade (Central European Time)',
            'Europe/Berlin' => '(GMT+1:00) Europe/Berlin (Central European Time)',
            'Europe/Bratislava' => '(GMT+1:00) Europe/Bratislava (Central European Time)',
            'Europe/Brussels' => '(GMT+1:00) Europe/Brussels (Central European Time)',
            'Europe/Budapest' => '(GMT+1:00) Europe/Budapest (Central European Time)',
            'Europe/Copenhagen' => '(GMT+1:00) Europe/Copenhagen (Central European Time)',
            'Europe/Gibraltar' => '(GMT+1:00) Europe/Gibraltar (Central European Time)',
            'Europe/Ljubljana' => '(GMT+1:00) Europe/Ljubljana (Central European Time)',
            'Europe/Luxembourg' => '(GMT+1:00) Europe/Luxembourg (Central European Time)',
            'Europe/Madrid' => '(GMT+1:00) Europe/Madrid (Central European Time)',
            'Europe/Malta' => '(GMT+1:00) Europe/Malta (Central European Time)',
            'Europe/Monaco' => '(GMT+1:00) Europe/Monaco (Central European Time)',
            'Europe/Oslo' => '(GMT+1:00) Europe/Oslo (Central European Time)',
            'Europe/Paris' => '(GMT+1:00) Europe/Paris (Central European Time)',
            'Europe/Podgorica' => '(GMT+1:00) Europe/Podgorica (Central European Time)',
            'Europe/Prague' => '(GMT+1:00) Europe/Prague (Central European Time)',
            'Europe/Rome' => '(GMT+1:00) Europe/Rome (Central European Time)',
            'Europe/San_Marino' => '(GMT+1:00) Europe/San_Marino (Central European Time)',
            'Europe/Sarajevo' => '(GMT+1:00) Europe/Sarajevo (Central European Time)',
            'Europe/Skopje' => '(GMT+1:00) Europe/Skopje (Central European Time)',
            'Europe/Stockholm' => '(GMT+1:00) Europe/Stockholm (Central European Time)',
            'Europe/Tirane' => '(GMT+1:00) Europe/Tirane (Central European Time)',
            'Europe/Vaduz' => '(GMT+1:00) Europe/Vaduz (Central European Time)',
            'Europe/Vatican' => '(GMT+1:00) Europe/Vatican (Central European Time)',
            'Europe/Vienna' => '(GMT+1:00) Europe/Vienna (Central European Time)',
            'Europe/Warsaw' => '(GMT+1:00) Europe/Warsaw (Central European Time)',
            'Europe/Zagreb' => '(GMT+1:00) Europe/Zagreb (Central European Time)',
            'Europe/Zurich' => '(GMT+1:00) Europe/Zurich (Central European Time)',
            'Africa/Blantyre' => '(GMT+2:00) Africa/Blantyre (Central African Time)',
            'Africa/Bujumbura' => '(GMT+2:00) Africa/Bujumbura (Central African Time)',
            'Africa/Cairo' => '(GMT+2:00) Africa/Cairo (Eastern European Time)',
            'Africa/Gaborone' => '(GMT+2:00) Africa/Gaborone (Central African Time)',
            'Africa/Harare' => '(GMT+2:00) Africa/Harare (Central African Time)',
            'Africa/Johannesburg' => '(GMT+2:00) Africa/Johannesburg (South Africa Standard Time)',
            'Africa/Kigali' => '(GMT+2:00) Africa/Kigali (Central African Time)',
            'Africa/Lubumbashi' => '(GMT+2:00) Africa/Lubumbashi (Central African Time)',
            'Africa/Lusaka' => '(GMT+2:00) Africa/Lusaka (Central African Time)',
            'Africa/Maputo' => '(GMT+2:00) Africa/Maputo (Central African Time)',
            'Africa/Maseru' => '(GMT+2:00) Africa/Maseru (South Africa Standard Time)',
            'Africa/Mbabane' => '(GMT+2:00) Africa/Mbabane (South Africa Standard Time)',
            'Africa/Tripoli' => '(GMT+2:00) Africa/Tripoli (Eastern European Time)',
            'Asia/Amman' => '(GMT+2:00) Asia/Amman (Eastern European Time)',
            'Asia/Beirut' => '(GMT+2:00) Asia/Beirut (Eastern European Time)',
            'Asia/Damascus' => '(GMT+2:00) Asia/Damascus (Eastern European Time)',
            'Asia/Gaza' => '(GMT+2:00) Asia/Gaza (Eastern European Time)',
            'Asia/Istanbul' => '(GMT+2:00) Asia/Istanbul (Eastern European Time)',
            'Asia/Jerusalem' => '(GMT+2:00) Asia/Jerusalem (Israel Standard Time)',
            'Asia/Nicosia' => '(GMT+2:00) Asia/Nicosia (Eastern European Time)',
            'Asia/Tel_Aviv' => '(GMT+2:00) Asia/Tel_Aviv (Israel Standard Time)',
            'Europe/Athens' => '(GMT+2:00) Europe/Athens (Eastern European Time)',
            'Europe/Bucharest' => '(GMT+2:00) Europe/Bucharest (Eastern European Time)',
            'Europe/Chisinau' => '(GMT+2:00) Europe/Chisinau (Eastern European Time)',
            'Europe/Helsinki' => '(GMT+2:00) Europe/Helsinki (Eastern European Time)',
            'Europe/Istanbul' => '(GMT+2:00) Europe/Istanbul (Eastern European Time)',
            'Europe/Kaliningrad' => '(GMT+2:00) Europe/Kaliningrad (Eastern European Time)',
            'Europe/Kiev' => '(GMT+2:00) Europe/Kiev (Eastern European Time)',
            'Europe/Mariehamn' => '(GMT+2:00) Europe/Mariehamn (Eastern European Time)',
            'Europe/Minsk' => '(GMT+2:00) Europe/Minsk (Eastern European Time)',
            'Europe/Nicosia' => '(GMT+2:00) Europe/Nicosia (Eastern European Time)',
            'Europe/Riga' => '(GMT+2:00) Europe/Riga (Eastern European Time)',
            'Europe/Simferopol' => '(GMT+2:00) Europe/Simferopol (Eastern European Time)',
            'Europe/Sofia' => '(GMT+2:00) Europe/Sofia (Eastern European Time)',
            'Europe/Tallinn' => '(GMT+2:00) Europe/Tallinn (Eastern European Time)',
            'Europe/Tiraspol' => '(GMT+2:00) Europe/Tiraspol (Eastern European Time)',
            'Europe/Uzhgorod' => '(GMT+2:00) Europe/Uzhgorod (Eastern European Time)',
            'Europe/Vilnius' => '(GMT+2:00) Europe/Vilnius (Eastern European Time)',
            'Europe/Zaporozhye' => '(GMT+2:00) Europe/Zaporozhye (Eastern European Time)',
            'Africa/Addis_Ababa' => '(GMT+3:00) Africa/Addis_Ababa (Eastern African Time)',
            'Africa/Asmara' => '(GMT+3:00) Africa/Asmara (Eastern African Time)',
            'Africa/Asmera' => '(GMT+3:00) Africa/Asmera (Eastern African Time)',
            'Africa/Dar_es_Salaam' => '(GMT+3:00) Africa/Dar_es_Salaam (Eastern African Time)',
            'Africa/Djibouti' => '(GMT+3:00) Africa/Djibouti (Eastern African Time)',
            'Africa/Kampala' => '(GMT+3:00) Africa/Kampala (Eastern African Time)',
            'Africa/Khartoum' => '(GMT+3:00) Africa/Khartoum (Eastern African Time)',
            'Africa/Mogadishu' => '(GMT+3:00) Africa/Mogadishu (Eastern African Time)',
            'Africa/Nairobi' => '(GMT+3:00) Africa/Nairobi (Eastern African Time)',
            'Antarctica/Syowa' => '(GMT+3:00) Antarctica/Syowa (Syowa Time)',
            'Asia/Aden' => '(GMT+3:00) Asia/Aden (Arabia Standard Time)',
            'Asia/Baghdad' => '(GMT+3:00) Asia/Baghdad (Arabia Standard Time)',
            'Asia/Bahrain' => '(GMT+3:00) Asia/Bahrain (Arabia Standard Time)',
            'Asia/Kuwait' => '(GMT+3:00) Asia/Kuwait (Arabia Standard Time)',
            'Asia/Qatar' => '(GMT+3:00) Asia/Qatar (Arabia Standard Time)',
            'Europe/Moscow' => '(GMT+3:00) Europe/Moscow (Moscow Standard Time)',
            'Europe/Volgograd' => '(GMT+3:00) Europe/Volgograd (Volgograd Time)',
            'Indian/Antananarivo' => '(GMT+3:00) Indian/Antananarivo (Eastern African Time)',
            'Indian/Comoro' => '(GMT+3:00) Indian/Comoro (Eastern African Time)',
            'Indian/Mayotte' => '(GMT+3:00) Indian/Mayotte (Eastern African Time)',
            'Asia/Tehran' => '(GMT+3:30) Asia/Tehran (Iran Standard Time)',
            'Asia/Baku' => '(GMT+4:00) Asia/Baku (Azerbaijan Time)',
            'Asia/Dubai' => '(GMT+4:00) Asia/Dubai (Gulf Standard Time)',
            'Asia/Muscat' => '(GMT+4:00) Asia/Muscat (Gulf Standard Time)',
            'Asia/Tbilisi' => '(GMT+4:00) Asia/Tbilisi (Georgia Time)',
            'Asia/Yerevan' => '(GMT+4:00) Asia/Yerevan (Armenia Time)',
            'Europe/Samara' => '(GMT+4:00) Europe/Samara (Samara Time)',
            'Indian/Mahe' => '(GMT+4:00) Indian/Mahe (Seychelles Time)',
            'Indian/Mauritius' => '(GMT+4:00) Indian/Mauritius (Mauritius Time)',
            'Indian/Reunion' => '(GMT+4:00) Indian/Reunion (Reunion Time)',
            'Asia/Kabul' => '(GMT+4:30) Asia/Kabul (Afghanistan Time)',
            'Asia/Aqtau' => '(GMT+5:00) Asia/Aqtau (Aqtau Time)',
            'Asia/Aqtobe' => '(GMT+5:00) Asia/Aqtobe (Aqtobe Time)',
            'Asia/Ashgabat' => '(GMT+5:00) Asia/Ashgabat (Turkmenistan Time)',
            'Asia/Ashkhabad' => '(GMT+5:00) Asia/Ashkhabad (Turkmenistan Time)',
            'Asia/Dushanbe' => '(GMT+5:00) Asia/Dushanbe (Tajikistan Time)',
            'Asia/Karachi' => '(GMT+5:00) Asia/Karachi (Pakistan Time)',
            'Asia/Oral' => '(GMT+5:00) Asia/Oral (Oral Time)',
            'Asia/Samarkand' => '(GMT+5:00) Asia/Samarkand (Uzbekistan Time)',
            'Asia/Tashkent' => '(GMT+5:00) Asia/Tashkent (Uzbekistan Time)',
            'Asia/Yekaterinburg' => '(GMT+5:00) Asia/Yekaterinburg (Yekaterinburg Time)',
            'Indian/Kerguelen' => '(GMT+5:00) Indian/Kerguelen (French Southern & Antarctic Lands Time)',
            'Indian/Maldives' => '(GMT+5:00) Indian/Maldives (Maldives Time)',
            'Asia/Calcutta' => '(GMT+5:30) Asia/Calcutta (India Standard Time)',
            'Asia/Colombo' => '(GMT+5:30) Asia/Colombo (India Standard Time)',
            'Asia/Kolkata' => '(GMT+5:30) Asia/Kolkata (India Standard Time)',
            'Asia/Katmandu' => '(GMT+5:45) Asia/Katmandu (Nepal Time)',
            'Antarctica/Mawson' => '(GMT+6:00) Antarctica/Mawson (Mawson Time)',
            'Antarctica/Vostok' => '(GMT+6:00) Antarctica/Vostok (Vostok Time)',
            'Asia/Almaty' => '(GMT+6:00) Asia/Almaty (Alma-Ata Time)',
            'Asia/Bishkek' => '(GMT+6:00) Asia/Bishkek (Kirgizstan Time)',
            'Asia/Dhaka' => '(GMT+6:00) Asia/Dhaka (Bangladesh Time)',
            'Asia/Novosibirsk' => '(GMT+6:00) Asia/Novosibirsk (Novosibirsk Time)',
            'Asia/Omsk' => '(GMT+6:00) Asia/Omsk (Omsk Time)',
            'Asia/Qyzylorda' => '(GMT+6:00) Asia/Qyzylorda (Qyzylorda Time)',
            'Asia/Thimbu' => '(GMT+6:00) Asia/Thimbu (Bhutan Time)',
            'Asia/Thimphu' => '(GMT+6:00) Asia/Thimphu (Bhutan Time)',
            'Indian/Chagos' => '(GMT+6:00) Indian/Chagos (Indian Ocean Territory Time)',
            'Asia/Rangoon' => '(GMT+6:30) Asia/Rangoon (Myanmar Time)',
            'Indian/Cocos' => '(GMT+6:30) Indian/Cocos (Cocos Islands Time)',
            'Antarctica/Davis' => '(GMT+7:00) Antarctica/Davis (Davis Time)',
            'Asia/Bangkok' => '(GMT+7:00) Asia/Bangkok (Indochina Time)',
            'Asia/Ho_Chi_Minh' => '(GMT+7:00) Asia/Ho_Chi_Minh (Indochina Time)',
            'Asia/Hovd' => '(GMT+7:00) Asia/Hovd (Hovd Time)',
            'Asia/Jakarta' => '(GMT+7:00) Asia/Jakarta (West Indonesia Time)',
            'Asia/Krasnoyarsk' => '(GMT+7:00) Asia/Krasnoyarsk (Krasnoyarsk Time)',
            'Asia/Phnom_Penh' => '(GMT+7:00) Asia/Phnom_Penh (Indochina Time)',
            'Asia/Pontianak' => '(GMT+7:00) Asia/Pontianak (West Indonesia Time)',
            'Asia/Saigon' => '(GMT+7:00) Asia/Saigon (Indochina Time)',
            'Asia/Vientiane' => '(GMT+7:00) Asia/Vientiane (Indochina Time)',
            'Indian/Christmas' => '(GMT+7:00) Indian/Christmas (Christmas Island Time)',
            'Antarctica/Casey' => '(GMT+8:00) Antarctica/Casey (Western Standard Time (Australia))',
            'Asia/Brunei' => '(GMT+8:00) Asia/Brunei (Brunei Time)',
            'Asia/Choibalsan' => '(GMT+8:00) Asia/Choibalsan (Choibalsan Time)',
            'Asia/Chongqing' => '(GMT+8:00) Asia/Chongqing (China Standard Time)',
            'Asia/Chungking' => '(GMT+8:00) Asia/Chungking (China Standard Time)',
            'Asia/Harbin' => '(GMT+8:00) Asia/Harbin (China Standard Time)',
            'Asia/Hong_Kong' => '(GMT+8:00) Asia/Hong_Kong (Hong Kong Time)',
            'Asia/Irkutsk' => '(GMT+8:00) Asia/Irkutsk (Irkutsk Time)',
            'Asia/Kashgar' => '(GMT+8:00) Asia/Kashgar (China Standard Time)',
            'Asia/Kuala_Lumpur' => '(GMT+8:00) Asia/Kuala_Lumpur (Malaysia Time)',
            'Asia/Kuching' => '(GMT+8:00) Asia/Kuching (Malaysia Time)',
            'Asia/Macao' => '(GMT+8:00) Asia/Macao (China Standard Time)',
            'Asia/Macau' => '(GMT+8:00) Asia/Macau (China Standard Time)',
            'Asia/Makassar' => '(GMT+8:00) Asia/Makassar (Central Indonesia Time)',
            'Asia/Manila' => '(GMT+8:00) Asia/Manila (Philippines Time)',
            'Asia/Shanghai' => '(GMT+8:00) Asia/Shanghai (China Standard Time)',
            'Asia/Singapore' => '(GMT+8:00) Asia/Singapore (Singapore Time)',
            'Asia/Taipei' => '(GMT+8:00) Asia/Taipei (China Standard Time)',
            'Asia/Ujung_Pandang' => '(GMT+8:00) Asia/Ujung_Pandang (Central Indonesia Time)',
            'Asia/Ulaanbaatar' => '(GMT+8:00) Asia/Ulaanbaatar (Ulaanbaatar Time)',
            'Asia/Ulan_Bator' => '(GMT+8:00) Asia/Ulan_Bator (Ulaanbaatar Time)',
            'Asia/Urumqi' => '(GMT+8:00) Asia/Urumqi (China Standard Time)',
            'Australia/Perth' => '(GMT+8:00) Australia/Perth (Western Standard Time (Australia))',
            'Australia/West' => '(GMT+8:00) Australia/West (Western Standard Time (Australia))',
            'Australia/Eucla' => '(GMT+8:45) Australia/Eucla (Central Western Standard Time (Australia))',
            'Asia/Dili' => '(GMT+9:00) Asia/Dili (Timor-Leste Time)',
            'Asia/Jayapura' => '(GMT+9:00) Asia/Jayapura (East Indonesia Time)',
            'Asia/Pyongyang' => '(GMT+9:00) Asia/Pyongyang (Korea Standard Time)',
            'Asia/Seoul' => '(GMT+9:00) Asia/Seoul (Korea Standard Time)',
            'Asia/Tokyo' => '(GMT+9:00) Asia/Tokyo (Japan Standard Time)',
            'Asia/Yakutsk' => '(GMT+9:00) Asia/Yakutsk (Yakutsk Time)',
            'Australia/Adelaide' => '(GMT+9:30) Australia/Adelaide (Central Standard Time (South Australia))',
            'Australia/Broken_Hill' => '(GMT+9:30) Australia/Broken_Hill (Central Standard Time (South Australia/New South Wales))',
            'Australia/Darwin' => '(GMT+9:30) Australia/Darwin (Central Standard Time (Northern Territory))',
            'Australia/North' => '(GMT+9:30) Australia/North (Central Standard Time (Northern Territory))',
            'Australia/South' => '(GMT+9:30) Australia/South (Central Standard Time (South Australia))',
            'Australia/Yancowinna' => '(GMT+9:30) Australia/Yancowinna (Central Standard Time (South Australia/New South Wales))',
            'Antarctica/DumontDUrville' => '(GMT+10:00) Antarctica/DumontDUrville (Dumont-d\'Urville Time)',
            'Asia/Sakhalin' => '(GMT+10:00) Asia/Sakhalin (Sakhalin Time)',
            'Asia/Vladivostok' => '(GMT+10:00) Asia/Vladivostok (Vladivostok Time)',
            'Australia/ACT' => '(GMT+10:00) Australia/ACT (Eastern Standard Time (New South Wales))',
            'Australia/Brisbane' => '(GMT+10:00) Australia/Brisbane (Eastern Standard Time (Queensland))',
            'Australia/Canberra' => '(GMT+10:00) Australia/Canberra (Eastern Standard Time (New South Wales))',
            'Australia/Currie' => '(GMT+10:00) Australia/Currie (Eastern Standard Time (New South Wales))',
            'Australia/Hobart' => '(GMT+10:00) Australia/Hobart (Eastern Standard Time (Tasmania))',
            'Australia/Lindeman' => '(GMT+10:00) Australia/Lindeman (Eastern Standard Time (Queensland))',
            'Australia/Melbourne' => '(GMT+10:00) Australia/Melbourne (Eastern Standard Time (Victoria))',
            'Australia/NSW' => '(GMT+10:00) Australia/NSW (Eastern Standard Time (New South Wales))',
            'Australia/Queensland' => '(GMT+10:00) Australia/Queensland (Eastern Standard Time (Queensland))',
            'Australia/Sydney' => '(GMT+10:00) Australia/Sydney (Eastern Standard Time (New South Wales))',
            'Australia/Tasmania' => '(GMT+10:00) Australia/Tasmania (Eastern Standard Time (Tasmania))',
            'Australia/Victoria' => '(GMT+10:00) Australia/Victoria (Eastern Standard Time (Victoria))',
            'Australia/LHI' => '(GMT+10:30) Australia/LHI (Lord Howe Standard Time)',
            'Australia/Lord_Howe' => '(GMT+10:30) Australia/Lord_Howe (Lord Howe Standard Time)',
            'Asia/Magadan' => '(GMT+11:00) Asia/Magadan (Magadan Time)',
            'Antarctica/McMurdo' => '(GMT+12:00) Antarctica/McMurdo (New Zealand Standard Time)',
            'Antarctica/South_Pole' => '(GMT+12:00) Antarctica/South_Pole (New Zealand Standard Time)',
            'Asia/Anadyr' => '(GMT+12:00) Asia/Anadyr (Anadyr Time)',
            'Asia/Kamchatka' => '(GMT+12:00) Asia/Kamchatka (Petropavlovsk-Kamchatski Time)'
        );
    }
}





