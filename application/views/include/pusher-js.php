<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<?php $this->config->load('pusher');
  $media_text = '';
  $mediaType =$this->using_media_type ?? 'fb';
  if($mediaType == 'fb'){
    $media_text = 'Facebook';
  }
  else{
    $media_text = 'Instagram';
  }
  $get_real_user_id = $this->session->userdata("real_user_id");
?>
<script>
  var get_real_user_id = "<?php echo $get_real_user_id;?>";
  // Enable pusher logging - don't include this in production
  Pusher.logToConsole = false;
  var pusher = new Pusher('<?php echo $this->config->item("pusher_app_key");?>', {
    cluster: '<?php echo $this->config->item("pusher_cluster") ?? '';?>'
  });
  var isNotitificationEnabled = '<?php echo $browser_notification_enabled ?? '0';?>';
  var timezone = '<?php echo !empty($this->config->item("time_zone")) ? $this->config->item("time_zone") : 'Europe/Dublin';?>';
  var messageType = '<?php echo $message_type ?? 'all';?>';
  if(messageType=='') messageType = 'all';
  var route_name = '<?php echo current_url();?>';

  var whatsAppPushChannel = pusher.subscribe('livechat_channel');
    whatsAppPushChannel.bind('livechat_event', function(responseData) {
      var subscribe_id = responseData[2];
      subscriberName = '';
      if(messageType != 'archived'){
          if(responseData[5]=='user') {
            $('#chatNotificationAudio')[0].play();
          }

          if(route_name == '<?php echo base_url('dashboard');?>' && responseData[5]=='user'){ // notification are shown in dashboard only and for received message

            if(subscriberName=='') subscriberName = '';
            var notificationUrl = '<?php echo base_url('livechat/load_livechat');?>'+'?subscriber_id='+subscribe_id+"&media_type="+responseData[11];
            if(isNotitificationEnabled=='1'){
              Notification.requestPermission().then(perm=>{
                if(perm == 'granted'){

                  var last_conversation_message = responseData[9];
                  last_conversation_message = last_conversation_message.replace( /(<([^>]+)>)/ig, '');
                  const notification = new Notification(subscriberName+" <?php echo $this->lang->line('sent you a message on') .' '.$media_text;?>",{
                    body: last_conversation_message,
                    icon: "<?php echo site_url('assets/images/media/' . $media_text . '.png');?>"

                  });
                  notification.onclick = () => {
                      notification.close();
                      window.open(notificationUrl, '_blank');
                  }
                }
              });
            }
          }
        }


        var selectedSubscriber = $('.open_conversation.bg-light').attr('from_user_id');

        var matchingSubscriberElement= $('.open_conversation[from_user_id='+subscribe_id+']');
        if(matchingSubscriberElement.length==0) {
          $("#refresh_data").trigger('click');
          return false;
        }

        var matchingSubscriberPreviewElement= $('.open_conversation[from_user_id='+subscribe_id+'] .put-message-preview');
        matchingSubscriberPreviewElement.html(responseData[9]).removeClass('text-muted').addClass('text-primary');
        var convertedTime = convertTZ(responseData[8],timezone);
        $('.open_conversation[from_user_id='+subscribe_id+'] .put-time').text(convertedTime);

        if(responseData[5]=='user'){
          var matchingSubscriberCountElement= $('.open_conversation[from_user_id='+subscribe_id+'] .rounded-pill');
          var count  = matchingSubscriberCountElement.html();

          if(count=='' || count == null) count = 0;
          count = parseInt(count);
          count++;

          matchingSubscriberCountElement.html(count).removeClass('d-none');
        }

        setTimeout(function(){
          var list_html = matchingSubscriberElement.prop('outerHTML');
          $(matchingSubscriberElement).remove();
          $('#put_content').prepend(list_html);
        },200);


        if(subscribe_id == selectedSubscriber){
           $("#conversation_modal_body").append(responseData[7]);

           setTimeout(function(){
            var timeContent = $("#conversation_modal_body .chat-time-js:last").text();
            if(timeContent=='') $("#conversation_modal_body .chat-time-js:last").text(convertedTime);
           }, 200); 

           setTimeout(function(){ 
              var element = document.getElementById("conversation_modal_body");
              element.scrollTop = element.scrollHeight; 
           }, 300);              
        }
  });

  var assignedAgentPushChannel = pusher.subscribe('livechat_assigned_agent_channel');
  assignedAgentPushChannel.bind('livechat_assigned_agent_event', function(responseData) {
    var assignedAgentId = responseData[1];
    var social_media = responseData[6];
    var from_type = social_media=='ig' ? 'Instagram' : 'Messenger';
    $('#agentAssignNotificationAudio')[0].play();    
      if(route_name == '<?php echo base_url('dashboard');?>' && assignedAgentId == responseData[0]){ // notification are shown in dashboard only and for assigned agent
        var notificationUrl = 'url('+from_type+'/livechat?message=mine)'
        var notificationUrl = '<?php echo base_url('livechat/load_livechat');?>'+'?subscriber_id='+subscribe_id+"&media_type="+responseData[6];
        if(isNotitificationEnabled=='1'){
          Notification.requestPermission().then(perm=>{
            if(perm == 'granted'){
              const notification = new Notification(responseData[2],{
                body: responseData[3],
                icon: "<i class='fas fa-ticket-alt'></i>"
              });
              notification.onclick = () => {
                  notification.close();
                  window.open(notificationUrl, '_blank');
              }
            }
          });
        }
      }
  });

  $(document).on('click', '#notification_action', function(event) {
    event.preventDefault();
    var state = $(this).data('state');
    if(state=='0'){// enable notification
        Notification.requestPermission().then(perm=>{
        if(Notification.permission == "granted") notification_action('1');
        else if(Notification.permission !== "denied"){
          Notification.requestPermission().then((perm) => {
            if(perm === "granted") notification_action('1');
            else iziToast.warning({title: 'Warning',message:'<?php echo $this->lang->line('Please allow browser notification permission to get inbox notification.');?>',position: 'bottomRight'});
          });
        }
        else iziToast.warning({title: 'Warning',message:'<?php echo $this->lang->line('Notification permission is blocked by your browser. Please allow notification permission for this site manually to get inbox notification.');?>',position: 'bottomRight'});
      });
    }
    else notification_action('0');
  });

  function notification_action(action)
  {
    $.ajax({
        url:"<?php echo site_url();?>livechat/update_browser_permission",
        type:'POST',
        data:{action},
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-CSRF-TOKEN', <?php echo $this->security->get_csrf_hash();?>);
        },
        success:function(response){
          location.reload();
        }
    });
  }

  function convertTZ(date, tzString) {
      date = date.replace( /[-]/g, '/')+' +0000';
      date = new Date((typeof date === "string" ? new Date(date) : date).toLocaleString("en-US", {timeZone: tzString}));
      var converted = date.toLocaleTimeString("en-US", {
          hour: "2-digit",
          minute: "2-digit",
          hour12: true,
      });
    return converted;
  }
</script>