<?php
    // $has_livechat_advanced_access = check_module_access(000,true);

    $has_livechat_advanced_access = true;
    $backgroundStyle = ($media_type === 'fb') 
    ? 'background-image: linear-gradient(#3d94fd, #6cbdfc);' 
    : 'background-image: linear-gradient(#c9a0f9, #f599bb);';
?>
<style>
  .main-content {
    padding-top: 0px !important;
  }
  .card-header:first-child {
    border-radius: 0;
  }
  .nav{
    padding-right: 30px !important;
  }
</style>

    <div class="p-0 m-0">
        <section id="basic-horizontal-layouts" class="">
            <div class="row main_row">
              <div class="col-12 col-sm-12 col-md-5 col-lg-5 col-xl-3 pe-xl-0 p-0 border-left" id="col-subscriber-list">
                <div class="card card-success no_radius mb-1 border-0">
                  <!-- <div class="card-header p-0 bg-whatsapp d-flex flex-column no_radius" style="background-color: #6cbdfc;"> -->
                  <div class="card-header p-0 bg-whatsapp d-flex flex-column no_radius" style="<?php echo $backgroundStyle; ?>">
                    <h6 class="w-100 clearfix d-flex flex-row p-2 pt-3 pb-0 mb-0">
                        <input type="text" class="border-0 form-control float-start search_list mr-auto" autofocus onkeyup="search_in_subscriber_ul(this,'put_content')" placeholder="<?php echo $this->lang->line("Search...") ?>">
                        <form class="form-inline mr-auto">   
                          <?php 
                              $current_account = isset($fb_rx_account_switching_info[$this->session->userdata("facebook_rx_fb_user_info")]['name']) ? $fb_rx_account_switching_info[$this->session->userdata("facebook_rx_fb_user_info")]['name'] : $this->lang->line("No Account");
                              $fb_img = base_url('assets/img/avatar/avatar-1.png');
                              if(isset($fb_rx_account_switching_info[$this->session->userdata("facebook_rx_fb_user_info")]['access_token']))
                              $fb_img = 'https://graph.facebook.com/me/picture?access_token='.$fb_rx_account_switching_info[$this->session->userdata("facebook_rx_fb_user_info")]['access_token'].'&width=18&height=18';
                          ?>
                          <ul class="navbar-nav navbar-right d-block ml-2 mr-1 facebook">
                              <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user d-inline-block pb-0 ml-0 pt-0 text-truncate" style="max-width: 150px;">
                              <img src="<?php echo $fb_img; ?>" height="18px" width="18px" class="rounded-circle mr-1">
                              <div class="d-inline text-white"><small><?php echo $current_account; ?></small></div></a>
                              <div class="dropdown-menu dropdown-menu-right acount-switch-lists">
                                  <div class="dropdown-title"><?php echo $this->lang->line("Interact as"); ?></div>               
                                  <?php 
                                  foreach ($fb_rx_account_switching_info as $key => $value) 
                                  {              
                                    echo '<a href="" data-id="'.$key.'" class="dropdown-item account_switch"><i class="fas fa-check-circle text-primary"></i> '.$value['name'].'</a>';
                                  } 
                                  ?>
                              </div>
                              </li>
                          </ul>
                        </form>   

                    </h6>                   

                    <ul class="nav nav-pills d-flex justify-content-between">
                      <li class="nav-item"><a class="text-white nav-link <?php if($message_type == 'all') echo 'active';?>" href="?message=all&media_type=<?php echo $media_type;?>" value="all"><?php echo $this->lang->line('All')?></a></li>
                      <li class="nav-item"><a class="text-white nav-link <?php if($message_type=='mine') echo 'active';?>" href="?message=mine&media_type=<?php echo $media_type;?>" value="mine"><?php echo $this->lang->line('Mine')?></a></li>
                      <li class="nav-item"><a class="text-white nav-link <?php if($message_type=='unread') echo 'active';?>" href="?message=unread&media_type=<?php echo $media_type;?>" value="unread"><?php echo $this->lang->line('Unread')?></a></li>
                      <li class="nav-item"><a class="text-white nav-link <?php if($message_type=='archived') echo 'active';?>" href="?message=archived&media_type=<?php echo $media_type;?>" value="archived"><?php echo $this->lang->line('Archived')?></a></li>
                    </ul>  
                  </div>

                  <div class="bg-light">
                      <select name="whatsapp_bot_id" id="whatsapp_bot_id" class="form-control select2 w-100" autocomplete="off">
                        <option value=""><?php echo $this->lang->line('Select'); ?></option>
                        <?php foreach($bot_info as $bot) : ?>
                        <!-- <option data-wa-business-id="<?php echo $bot->id ;?>" value="<?php echo $bot->id?>" <?php if($bot->id == $first_bot_id) echo 'selected'; ?>><?php echo $this->lang->line('Using').' : '.$bot->page_name.' ('.str_replace(['-',' '],'',$bot->username).')'; ?></option> -->
                        <option 
                            data-wa-business-id="<?php echo $bot->id; ?>" 
                            value="<?php echo $bot->id; ?>" 
                            <?php if ($bot->id == $first_bot_id) echo 'selected'; ?>>
                            <?php
                            if ($media_type === 'fb') {
                                echo $this->lang->line('Using') . ' : ' . $bot->page_name . ' (' . str_replace(['-', ' '], '', $bot->username) . ')';
                            } elseif ($media_type === 'ig') {
                                echo $this->lang->line('Using') . ' : ' . $bot->insta_username;
                            }
                            ?>
                        </option>

                        <?php endforeach; ?>
                      </select>
                  </div>

                  <div class="card-body p-0">
                    <ul class="list-unstyled list-unstyled-border" id="put_content">
                    </ul>
                  </div>
                </div>
              </div>
              <div class="col-12 col-sm-12 col-md-7 col-lg-7 col-xl-6 ps-xl-0 pe-xl-0 p-0" id="col-chat-content">
                <div class="card chat-box card-success no-radius mb-1 border-0" style="" id="mychatbox2">
                   <!-- <div class="card-header pt-0 pb-2 bg-whatsapp no-radius d-flex flex-column" style="height: 100px; background-color: #6cbdfc;">   -->
                   <div class="card-header pt-0 pb-2 bg-whatsapp no-radius d-flex flex-column" style="height: 106px; <?php echo $backgroundStyle; ?>">  
                        <div class="w-100 d-flex flex-row pt-3">
                            <input type="text" class="border-0 form-control search_list search_list2" onkeyup="search_in_div(this,'conversation_modal_body')" placeholder="<?php echo $this->lang->line("Search...") ?>">
                            <a class="btn btn-outline-light btn-sm text-muted float-start mt-0 mx-1 action-item ml-2"     title="<?php echo $this->lang->line("Reload") ?>" name="refresh_data" id="refresh_data"   whatsapp_bot_id="<?php echo $first_bot_id ?>"> <i class="fas fa-sync"></i>
                            </a>
                            <?php $notification_action = $browser_notification_enabled=='0' ? $this->lang->line('Enable Notification') : $this->lang->line('Disable Notification');?>
                            <a class="btn btn-outline-light btn-sm text-muted float-start me-2 mt-0 action-item" data-state="<?php echo $browser_notification_enabled; ?>" title="<?php echo $notification_action; ?>" name="notification_action" id="notification_action">
                            <?php
                                if($browser_notification_enabled == '0'){
                                  echo  '<i class="fas fa-bell-slash"></i>';
                                } 
                                else echo '<i class="fas fa-bell"></i>';
                            ?>
                            </a>
                            <a class="btn btn-outline-light btn-sm text-muted float-start mt-0 me-2 mx-1 action-item" href="<?php echo base_url('dashboard');?>" title="<?php echo $this->lang->line("Back to Dashbaord"); ?>" id=""> <i class="fas fa-less-than"></i></a>
                        </div>
                        <div class="w-100 d-flex flex-row mt-1">
                            <h6 class="w-100 pe-0 mt-2 mt-lg-2-5 mb-2">
                            <!-- <div class=""> -->
                                <a class="btn btn-outline-light btn-sm text-muted me-1 action-item" id="back-to-list" href="" data-toggle="tooltip" title="<?php echo $this->lang->line("Back to List") ?>"> <i class="fas fa-less-than"></i></a>
                                <span class="text-white" id="chat_with"></span>
                                <span class="text-white" id="phone_number"></span>
                                </span>
                            </h6>
                            <select name="mark_as_action" id="mark_as_action" class="form-control pb-2 border-white text-white ml-auto mt-2">
                              <option class="text-dark" value=""> <?php echo $this->lang->line('Mark as');?></option>
                              <option class="text-dark" value="read"> <?php echo $this->lang->line('Read');?></option>
                              <option class="text-dark" value="unread"> <?php echo $this->lang->line('Unread');?></option>
                              <option class="text-dark" value="archived"> <?php echo $this->lang->line('Archived');?></option>
                              <option class="text-dark" value="unarchived"> <?php echo $this->lang->line('Unarchived');?></option>
                            </select> 
                        </div>
                        
                    </div>
                   <div class="card-body chat-content2  bg-light-danger gradient w-100" style="overflow-y: auto;" id="conversation_modal_body"></div>
                   <div class="card-footer chat-form">
                      <form id="chat-form2">

                        <div class="row">
                            <div class="col-12 ps-0 pe-0">
                                <div class="input-group">
                                    <div class="input-group-append">
                                      <button type="button" title="<?php echo $this->lang->line('Send Flow or Message Template');?>" class="btn btn-outline-primary" id="postback_reply_button" data-toggle="modal" data-target="#postbackModal" data-wa-business-id="{{$whatsapp_business_id}}">
                                        <i class="fas fa-robot"></i>
                                      </button>
                                    </div>
                                    <?php if($has_livechat_advanced_access) : ?>
                                        <div class="input-group-append">
                                            <button type="button" title="<?php echo $this->lang->line("Canned Response");?>"class="btn btn-outline-dark" id="canned_response" data-toggle="modal" data-target="#canned_responseModal" data-wa-business-id="<?php echo$whatsapp_business_id;?>">
                                                <i class="fab fa-autoprefixer"></i>
                                            </button>
                                        </div>                                                                       
                                        <div class="input-group-append">
                                            <button type="button" title="<?php echo $this->lang->line("Attachment");?>"class="btn btn-outline-warning" id="send_file">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" title="<?php echo $this->lang->line("Audio Record");?>"class="btn btn-outline-success" id="record_audio_message">
                                                <i class="fas fa-microphone"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                        <div class="col-2 col-md-2 no_padding_col_right mt-0 p-0 pl-1">
                                            <?php echo form_dropdown('message_tag', $tag_list, 'HUMAN_AGENT','class="form-control select2" id="message_tag" style="width: 100% !important;height:50px !important;"'); ?>
                                        </div>
                                   <textarea type="text" id="reply_message" class="form-control border ms-1" placeholder="<?php echo $this->lang->line('Type a message..');?>"></textarea>

                                  <div class="input-group-append">
                                    <button class="btn btn-success" id="final_reply_button">
                                      <i class="far fa-paper-plane"></i>
                                    </button>
                                  </div>
                                </div>
                            </div>
                       </div>
                      </form>
                   </div>
                </div>
              </div>
              <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-3 ps-xl-0 p-0" id="col-subscriber-action">
                 <!-- <div class="card card-primary mb-1 no_radius" style="min-height: 752px">  -->
                <div class="card card-primary mb-1 no_radius border-0">
                  <div class="card-body p-0">
                    <div id="subscriber_action">
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </section>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="postbackModal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title full_width" id="postbackModalLabel">
                        <i class="fas fa-paper-plane"></i> <?php echo $this->lang->line('Send Flow or Message Template')?>
                        <input type="text" class="form-control d-inline float-end me-2" autofocus style="width: 130px;" autofocus="" onkeyup="search_in_class(this,'item-searchable')" placeholder="<?php echo $this->lang->line('Search');?>">
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body" style="height:550px;overflow:auto">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 text-dark" id="pills-bot-flow-tab" data-toggle="pill" data-target="#pills-bot-flow" type="button" role="tab" aria-controls="pills-bot-flow" aria-selected="true"><?php echo $this->lang->line("Bot Flow");?></button>
                        </li>
                        <!-- <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 text-dark" id="pills-message-template-tab" data-toggle="pill" data-target="#pills-message-template" type="button" role="tab" aria-controls="pills-message-template" aria-selected="false"><?php echo $this->lang->line('Message Template');?></button>
                        </li> -->
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show" id="pills-bot-flow" role="tabpanel" aria-labelledby="pills-bot-flow-tab">
                            <div id="bot-postback-list"></div>
                        </div>

                        <!-- <div class="tab-pane fade" id="pills-message-template" role="tabpanel" aria-labelledby="pills-message-template-tab">
                            <div id="bot-template-list"></div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="template-modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form class="template-modal-form" action="#" method="post">

                    <div class="modal-header">
                        <h5 class="modal-title full_width" id="template-modal-label">
                            <i class="fas fa-th-large"></i> <?php echo $this->lang->line('Template'); ?> : <span></span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>

                    <div class="modal-body" style="height:550px;overflow:auto">
                        <div id="bot-template-quickreply-button-wrapper"></div>
                        <div id="bot-template-dynamic-variable-wrapper"></div>
                        <div id="bot-template-header-media-wrapper" class="form-group text-left mt-2 d-none">
                            <div class="header-media">
                                <div class="header-media-url pb-2">
                                    <label for="bot-template-header-media-url-input" class="pb-1"><?php echo $this->lang->line('Media URL'); ?></label>
                                    <input type="text" class="form-control" id="bot-template-header-media-url-input" placeholder="<?php echo $this->lang->line('Put media url here or click the upload box'); ?>">
                                    <input type="hidden" name="bot-template-file-name" id="bot-template-file-name">
                                    <input type="hidden" name="media_type" id="media_type" value="<?php echo $media_type;?>">
                                    <input type="hidden" name="message_type" id="message_type" value="<?php echo $message_type;?>">
                                </div>

                                <div class="header-media-dropzone">
                                    <label for="" class="pb-1"><?php echo $this->lang->line('Upload media'); ?></label>
                                    <div id="file-upload-dropzone" class="dropzone d-flex justify-content-center align-items-center mb-1">
                                        <div class="dz-default dz-message">
                                            <input type="file" class="d-none">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                        </div>
                                    <span class="text-small text-muted"><p>Image:5MB, Audio/Video:16MB, File:100MB</p></span>
                                    </div>
                                </div>
                            </div><!-- #bot-template-header-media-wrapper -->
                    </div>

                    <div class="modal-footer d-flex align-items-center justify-content-between">
                        <div classs="d-flex align-items-center">
                            <span class="error-message text-danger"></span>
                        </div>
                        <button type="submit" id="send-message-template-action" class="btn btn-primary"><?php echo $this->lang->line('Submit'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Modal -->
<?php if($has_livechat_advanced_access) : ?>
    <div class="modal fade" id="canned_responseModal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title full_width" id="canned_responseModalLabel">
                        <i class="fas fa-paper-plane"></i> <?php echo $this->lang->line("Canned Response");?>&nbsp;&nbsp;
                        <button id="btnAddNew" type="button" class="btn btn-outline-primary " aria-expanded="false">
                        <i class="fas fa-plus-circle"></i> <?php echo $this->lang->line("Add New");?></button>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>

                <div class="modal-body" style="height:550px;overflow:auto">
                    <div id="add_new_canned_response">
                         <div class="form-group">
                           <label for="name"><?php echo $this->lang->line("Name");?></label>
                           <input type="text" class="form-control" id="name">
                         </div>
                         <div class="form-group">
                            <label for="message"><?php echo $this->lang->line("Message"); ?> 
                                <span class="">
                                    <a class="btn btn-sm border-dashed text-primary m-2" data-toggle="dropdown" data-placement="top"  title="The custom variable will be replaced by actual value before sending it.">
                                        <i class="fas fa-cogs"></i>
                                        <?php echo $this->lang->line("custom");?>
                                    </a>
                                    <div class="dropdown-menu" id="variables_dropdown" data-animation="fadeIn">
                                        <?php foreach($custom_variables as $value) :?>
                                            <span class="dropdown-item" value="custom_<?php echo $value->id?>"><?php echo $value->name ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </span>
                                <span class="btn btn-sm border-dashed text-primary my-2" id="first_name_variable" data-toggle="tooltip" title="You can include #LEAD_USER_FIRST_NAME# variable inside your message. The variable will be replaced by real names when we will send it." value="#LEAD_USER_FIRST_NAME# ">
                                    <i class="fas fa-user"></i>
                                    <?php echo $this->lang->line("F. Name");?>
                                </span>
                            </label>
                           <textarea class="form-control" id="message" rows="3"></textarea>
                         </div>
                         <div>
                             <input type="hidden" id="bot_id" whatsapp_bot_id = "<?php echo $first_bot_id ;?>" >
                         </div>
                         <div>
                             <input type="hidden" id="user_id" user_id = "<?php echo $this->user_id ;?>" >
                         </div>
                         <div class="d-flex justify-content-between">
                             <button type="submit" id="add_canned_response" data-mode="add" class="btn btn-primary"><?php $this->lang->line("Add");?></button>
                             <button type="button" id="close_canned_response" data-mode="close" class="btn btn-warning"><?php echo $this->lang->line("Close");?></button>
                         </div>
                    </div>
                   <div class="card no-shadow">
                       <div class="card-body data-card p-0 mt-3">
                           <div class="table-responsive">
                               <table class="table table-hover table-bordered table-sm w-100" id="mytable" >
                                   <thead>
                                   <tr class="table-light">
                                       <th>#</th>
                                       <th>
                                           <div class="form-check form-switch d-flex justify-content-center"><input class="form-check-input" type="checkbox"  id="datatableSelectAllRows"></div>
                                       </th>
                                       <th><?php echo $this->lang->line("Name") ;?></th>
                                       <th><?php echo $this->lang->line("Message") ;?></th>
                                       <th><?php echo $this->lang->line("Actions") ;?></th>
                                   </tr>
                                   </thead>
                                   <tbody></tbody>
                               </table>
                           </div>
                       </div>
                   </div>
                </div>
                </div>
            </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="send_fileModal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title full_width" id="send_fileModalLabel">
                        <i class="fas fa-paperclip"></i> <?php echo $this->lang->line("Send Attachment")?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body" style="height:550px;overflow:auto">
                    <div id="livechat-dropzone" class="dropzone mb-1">
                        <div class="dz-default dz-message">
                            <input class="form-control" name="thumbnail" id="uploaded-file" type="hidden">
                            <span style="font-size: 20px;"><i class="fas fa-cloud-upload-alt" style="font-size: 35px;color: #6777ef;"></i> <?php echo $this->lang->line("Upload") ;?></span>
                        </div>
                    </div>
                    <input type="text" class="form-control" placeholder="<?php echo $this->lang->line("Instead of uploading, you can paste the attachment URL")?>" id="livechat-current-item-url">
                    <textarea class="d-none" id="livechat-current-item-name"></textarea>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<script>
    var messageType = '<?php echo $message_type;?>';
</script>

<?php include(FCPATH.'application/views/livechat/livechat_load_livechat_css.php');?>

<?php include(FCPATH.'application/views/livechat/livechat_load_livechat_js.php');?>

<?php include(FCPATH.'application/views/messenger_tools/subscriber_actions_common_js.php');?>

<script>
        document.addEventListener('DOMContentLoaded', () => {
          let stream;
          let mediaRecorder;
          let recordedChunks = [];
          let isRecording = false;
          let intervalId;
        
          const startRecording = () => {
            navigator.mediaDevices.getUserMedia({ audio: true })
              .then((audioStream) => {
                stream = audioStream;
                mediaRecorder = new MediaRecorder(audioStream);
        
                mediaRecorder.ondataavailable = (e) => {
                  recordedChunks.push(e.data);
                };
        
                mediaRecorder.onstop = () => {
                  const audioBlob = new Blob(recordedChunks, { type: 'audio/mp3'});
                  const formData = new FormData();
                  formData.append('media_file', audioBlob, 'recorded_audio.mp3');
        
                  $.ajax({
                    url:"<?php echo site_url();?>livechat/livechat_upload_file",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        var response = JSON.parse(response);
                      if (response.status === false) {
                        swal('Error', response.message, 'error');
                        return;
                      }
                      if (response.file) {
                        $('#livechat-current-item-name').val(response.file_name);
                        $('#livechat-current-item-url').val(response.file).trigger('keyup');
                      }
                    }
                  });
                };
        
                mediaRecorder.start();
                isRecording = true;
                document.getElementById('record_audio_message').innerHTML = '<i class="fas fa-stop"></i>';
                startProgressBar();
              })
              .catch((err) => {
                console.error('Error accessing the microphone:', err);
              });
          };
        
          const stopRecording = () => {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
              mediaRecorder.stop();
              stream.getTracks().forEach((track) => track.stop());
              isRecording = false;
            }
          };
        
          const startProgressBar = () => {
            let width = 0;
            intervalId = setInterval(() => {
              width += 1;
            //   document.getElementById('progress_bar').style.width = `${width}%`;
              if (width >= 100) {
                clearInterval(intervalId);
              }
            }, 100); // Change the interval duration for smoother or faster progress
          };
        
          const toggleRecording = () => {
            if (isRecording) {
              stopRecording();
              $('#record_audio_message').html('<i class="fas fa-microphone"></i>');
              // Hide or change UI indicating recording has stopped
            } else {
              startRecording();
              $('#record_audio_message').html('<i class="fas fa-stop"></i>');
              // Display any UI indicating recording is in progress
            }
          };
        
          const audioSendButton = document.getElementById('record_audio_message');
          audioSendButton.addEventListener('click', toggleRecording);
        });
</script>

<script>
    var perscroll;
    var table ='';

    $(document).ready(function() {
        var table_name = 'mytable';
        $('#add_new_canned_response').hide();
        var whatsapp_bot_id = $('#bot_id').attr('whatsapp_bot_id');
        var media_type = $('#media_type').val();
        setTimeout(function(){
            if(table==''){
                table = $("#"+table_name).DataTable({
                    fixedHeader: false,
                    colReorder: true,
                    serverSide: true,
                    processing:true,
                    bFilter: true,
                    pageLength: 5,
                    lengthMenu: [5, 10, 20, 50, 100],
                    ajax:
                        {
                            "url": "<?php echo site_url();?>livechat/canned_response_list",
                            "type": 'POST',
                            data: {whatsapp_bot_id,media_type},

                        },
                    language:
                        {
                            url: ''
                        },
                    dom: '<"top"f>rt<"bottom"lip><"clear">',
                    columnDefs: [
                        {
                            targets: [1],
                            visible: false
                        },
                        {
                            targets: [2],
                            sortable: false
                        }
                    ],
                    fnInitComplete:function(){  // when initialization is completed then apply scroll plugin
                        if(areWeUsingScroll)
                        {
                            if (perscroll) perscroll.destroy();
                            perscroll = new PerfectScrollbar('#'+table_name+'_wrapper .dataTables_scrollBody');
                        }
                        var $searchInput = $('div.dataTables_filter input');
                        $searchInput.unbind();
                        $searchInput.bind('keyup', function(e) {
                            if(this.value.length > 2 || this.value.length==0) {
                                table.search( this.value ).draw();
                            }
                        });
                    },
                    scrollX: 'auto',
                    fnDrawCallback: function( oSettings ) { //on paginition page 2,3.. often scroll shown, so reset it and assign it again
                        if(areWeUsingScroll)
                        {
                            if (perscroll) perscroll.destroy();
                            perscroll = new PerfectScrollbar('#'+table_name+'_wrapper .dataTables_scrollBody');
                        }
                    }
                });
            }
            else table.draw();
        }, 700);


        $(document).on('click','#btnAddNew',function(e){
        $('#add_new_canned_response').show();
        $('#message').val('');
        $('#name').val('');
        $('#add_canned_response').text('Add').attr('data-mode', 'add');
        });

        $(document).on('click','.delete_canned_response',function(e){
        e.preventDefault();
        var id = $(this).attr('data-id');
        swal({
            title: '<?php echo $this->lang->line("Are you sure"); ?>',
				text: '',
				icon: 'warning',
				buttons: true,
				dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    context:this,
                    url:"<?php echo site_url();?>livechat/delete_canned_response",
                    type:'POST',
                    data:{id},
                    dataType:'JSON',
                    success:function(response){
                    if(response.status== '1'){
                        swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success');
                        table.draw();
                    }
                    else{
                        swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error')
                    }
                    }
                });
            }
        });

    });

        $(document).on('click','#canned_response',function(e){
            table.draw();
        });

        // modal hide event
        $('#canned_responseModal').on("show.bs.modal", function (e) {
            setTimeout(function(){table.draw();},1000);
        });


        $(document).on('click','#add_canned_response',function(e){
        var mode = $(this).attr('data-mode');
        if(mode == 'edit'){
            var update_id = $(this).attr('data-update_id');
        }
        else{
            var update_id = 0;
        }
        var  name = $('#name').val();
        var  media_type = $('#media_type').val();
        var  message = $('#message').val();
        var whatsapp_bot_id = $('#bot_id').attr('whatsapp_bot_id');
        var user_id = $('#user_id').attr('user_id');
        $.ajax({
            url: "<?php echo site_url();?>livechat/add_canned_response",
            method: "POST",
            data: {user_id,whatsapp_bot_id,name,message,update_id},
            success:function(response)
            {
                var response = JSON.parse(response);
                if(response.status=='0') Swal.fire({title: global_lang_error, text: response.message,icon: 'error',confirmButtonText: global_lang_ok});
                else {
                    swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success')
                    .then(function () {
                    $('#message').val('');
                    $('#name').val('');
                    $("#add_new_canned_response").hide();
                    $('#add_canned_response').attr('data-mode', 'add').attr('data-update_id', 0);
                    table.draw();
                    });
                }
            },
            error: function (xhr, statusText) {
                const msg = handleAjaxError(xhr, statusText);
                Swal.fire({icon: 'error',title: global_lang_error,html: msg});
                return false;
            }

        });

        });

        $(document).on('click','.update_canned_response',function(e){
            e.preventDefault();
            var id = $(this).attr('data-id');
            var name = $(this).attr('data-name');
            var message = $(this).attr('data-message');
            $('#message').val(message);
            $('#name').val(name);
            $("#add_new_canned_response").show();
            $('#add_canned_response').text('Edit').attr('data-mode', 'edit').attr('data-update_id', id);
            $('.modal-body').scrollTop(0);

        });
        $(document).on('click','.delete_canned_response',function(e){
        $('#message').val('');
        $('#name').val('');
        $("#add_new_canned_response").hide();

        });
        $(document).on('click','.send_canned_response',function(e){
            e.preventDefault();
            var message = $(this).attr('data-message');
            $('#reply_message').val(message);
            $('#canned_responseModal').modal('hide');

        });

        $(document).on('click','#close_canned_response',function(e){
            $('#message').val('');
            $('#name').val('');
            $("#add_new_canned_response").hide();
        });

    
    });


</script>




