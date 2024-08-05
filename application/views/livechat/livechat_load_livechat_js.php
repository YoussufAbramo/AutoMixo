<script>
    var livechatSubscriberIdUrl ='';
    var loading = '<br><div class="col-12 text-center waiting previewLoader"><i class="fas fa-spinner fa-spin blue text-center" style="font-size: 60px; margin-top: 100px; margin-bottom: 100px;"></i></div>';
    var refresh_interval = 10000;
    var auto_refresh_con = '';
    var postbackPayload = [];
    var botTemplatePayload = [];
    var livechatDropzone;
    var global_lang_loading = '<?php echo $this->lang->line('Loading') ?>';
    var whatsapp_bot_manager_lang_webhook_data_view_no_data = '<?php echo $this->lang->line('No data available to show') ?>';
    var pusher_status = '<?php echo $pusher_status;?>';

    function openTab(url) {
    var win = window.open(url, '_blank');
    win.focus();
    }

    function get_subscriber_action_content2(id,subscriber_id,whatsapp_bot_id)
    {
    $("#subscriber_action").html(loading);
    var media_type = $('#media_type').val();
    $.ajax({
        type:'POST' ,
        url: "<?php echo site_url();?>subscriber_manager/subscriber_actions_modal" ,
        data:{id:id,page_id:whatsapp_bot_id,subscribe_id:subscriber_id,media_type:media_type,call_from_conversation:'1'},
        success:function(response)
        {
        $("#subscriber_action").html(response);
        }
    });
    }

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('#postback_reply_button').tooltip();
        $('#canned_response').tooltip();
        $('#send_file').tooltip();
        $('#record_audio_message').tooltip();

        // refresh automatically if pusher not found
        if(pusher_status=='0'){
             setInterval(function () {$('.open_conversation.bg-light').click();}, refresh_interval);
        }
    });

    $("document").ready(function(){  
    var bodyHeight = document.querySelector("body").offsetHeight;
    if(areWeUsingScroll){
        $("#put_content").height(bodyHeight-70);
        $("#conversation_modal_body").height(bodyHeight-90);
        // $('#col-subscriber-action .card').css('min-height', bodyHeight-33);
        $('#col-subscriber-action .card').css({
            'height': bodyHeight+95,
            'overflow-y': 'auto',
            'overflow-x': 'hidden'
        });
    }
    else{
        $("#put_content").height(bodyHeight-300);
        $("#conversation_modal_body").height(bodyHeight-340);
        $('#col-subscriber-action .card').css('height', 'auto');
        $("#main").css('background-color','white');
    }
    
    if(!areWeUsingScroll){   
        $("#col-chat-content").hide();
        $("#col-subscriber-action").hide();
        $("#back-to-list").show();

        $("#col-subscriber-list").css('width','100%');
        $("#col-chat-content").css('width','100%');
        $("#col-subscriber-action").css('width','100%');
    }
    else{
        $("#col-chat-content").show();
        $("#col-subscriber-action").show();
        $("#back-to-list").hide();
    }

    setTimeout(function(){
        auto_refresh_con = $('.open_conversation.bg-light').click();

        if(livechatSubscriberIdUrl!=''){
            var splitSubscriber = livechatSubscriberIdUrl.split('-');
            var defaulBotId = typeof(splitSubscriber[1]) !== undefined ? splitSubscriber[1] : '';
            var timeout = 0;
            if($("#whatsapp_bot_id").val() != defaulBotId) {
                $("#whatsapp_bot_id").val(defaulBotId).trigger('change');
                timeout = 750;
            }
            setTimeout(function(){ 
                ajax_call(".open_conversation[from_user_id="+livechatSubscriberIdUrl+"]",false);
            }, timeout);
        }
        else ajax_call(".open_conversation:first",false,true);
    }, 750);

    $(document).on('click', '#go-to-action', function(event) {
        event.preventDefault();
        if(!areWeUsingScroll){        
            $("#col-subscriber-list").hide();
            $("#col-chat-content").hide();
            $("#col-subscriber-action").show();
        }
    });

    $(document).on('click', '#back-to-chat', function(event) {
        event.preventDefault();
        if(!areWeUsingScroll){        
            $("#col-subscriber-list").hide();
            $("#col-chat-content").show();
            $("#col-subscriber-action").hide();
        }
    });

    $(document).on('click', '#back-to-list', function(event) {
        event.preventDefault();
        if(!areWeUsingScroll){        
            $("#col-subscriber-list").show();
            $("#col-chat-content").hide();
            $("#col-subscriber-action").hide();
            $("#back-to-list").hide();
        }
    });

    $('#variables_dropdown .dropdown-item').click(function (e) { 
        e.preventDefault();
        var selectedItemText = $(this).text();
        var final_selectedItemText = "#"+selectedItemText+"# ";
        var currentContent = $("#message").val();
        var newContent = currentContent + final_selectedItemText;
        $("#message").val(newContent);
    });
    $('#first_name_variable').click(function (e) { 
        e.preventDefault();
        var first_name = $('#first_name_variable').attr("value");;
        var currentContent2 = $("#message").val();
        var newContent2 = currentContent2 + first_name;
        $("#message").val(newContent2);
    });

    $(document).on('change', '#whatsapp_bot_id', function(event) {
        event.preventDefault();
        var whatsapp_bot_id = $(this).val();
        $(".search_list").val('');
        postbackPayload = [];
        botTemplatePayload = [];
        $('#refresh_data').attr('whatsapp_bot_id',whatsapp_bot_id);
        $("#put_content").empty();
        ajax_call(".open_conversation:first",false);
    });

    // $('#conversation_modal_body').on('scroll', function (){
    //         var scrollableHeight = $('#conversation_modal_body')[0].scrollHeight - $('#conversation_modal_body').outerHeight();
    //         if ($('#conversation_modal_body').scrollTop() <= 200) {
    //             $(".open_conversation:first").trigger("click");
    //         }
    //     });

    $(document).on('click','.open_conversation',function(){
        if(!areWeUsingScroll){        
            $("#col-subscriber-list").hide();
            $("#col-chat-content").show();
            // $("#col-subscriber-action").show();
            $("#back-to-list").show();
            $('html, body').animate({
            scrollTop: $("#col-chat-content").offset().top-15
            });
        }
        var already_loaded = false;
        if($(this).hasClass('bg-light')) already_loaded = true;
        if(already_loaded) {
            var element = document.getElementById("conversation_modal_body");
            element.scrollTop = element.scrollHeight;
            // return false;
        }

        $('#name_change_icon').removeClass('fa fa-save').addClass('fa fa-edit');
        $('#profile_name_save').attr('data-bs-original-title', 'Edit');
        $('#profile_name_save').attr('id', 'profile_name_edit');


        $('.media').removeClass('bg-light');
        $(this).addClass('bg-light');
        var from_user_id = $(this).attr('from_user_id');
        var from_user = $(this).attr('from_user');
        var auto_id = $(this).attr('data_id');
        var subscribe_id = $(this).attr('from_user_id');
        var thread_id = $(this).attr('thread_id');
        var whatsapp_bot_id = $(this).attr('whatsapp_bot_id');
        var last_message_id  =  $(".card-body .chat-item:last .chat-details").attr('message_id');

        last_message_id = '';
        $("#chat_with").html(global_lang_loading+"...");
        $("#final_reply_button").attr('thread_id',thread_id);
        $("#final_reply_button").attr('whatsapp_bot_id',whatsapp_bot_id);
        $("#final_reply_button").attr('from_user_id',from_user_id);
        get_subscriber_action_content2(auto_id,from_user_id,whatsapp_bot_id);
        $("#conversation_modal_body").html('');

        $.ajax({
            context:this,
            url:"<?php echo site_url();?>livechat/get_conversation_single",
            type:'POST',
            data:{thread_id:thread_id,whatsapp_bot_id:whatsapp_bot_id,from_user_id:from_user_id,last_message_id:last_message_id},
            success:function(response){
            var from_user_id_split = from_user_id.split('-');
            var subcriber_real_id = '';
            if(typeof(from_user_id_split[0])!==undefined) subcriber_real_id = +from_user_id_split[0];
            if(subcriber_real_id!=from_user)  subcriber_real_id = " ("+subcriber_real_id+")";
            else subcriber_real_id = '';

            $("#chat_with").html(from_user);          
            // $("#phone_number").html(subcriber_real_id);          
            $("#conversation_modal_body").html(response);
            var element = document.getElementById("conversation_modal_body");
            element.scrollTop = element.scrollHeight;

            $('.open_conversation[from_user_id='+from_user_id+'] .rounded-pill').html('0').addClass('d-none');
            $('.open_conversation[from_user_id='+from_user_id+'] .put-message-preview').removeClass('text-primary').addClass('text-muted');
            }

        });
    });    

    $("#reply_message").on('keydown', function(event) {
        if (event.keyCode == 13 && event.shiftKey) { // if Enter key is pressed without Shift key
            $(this).append('\n'); // add line break        
        }
        else if (event.keyCode == 13 && !event.shiftKey){
            event.preventDefault();
            $("#final_reply_button").trigger('click'); // trigger click event on final_send_button
        }
    });


    $(document).on('click','#final_reply_button',function(e){
        e.preventDefault();
        var thread_id = $(this).attr('thread_id');
        var whatsapp_bot_id = $(this).attr('whatsapp_bot_id');
        var from_user_id = $(this).attr('from_user_id');
        var reply_message = $("#reply_message").val().trim();
        var media_type = $('#media_type').val();
        var message_tag = $('#message_tag').val();

        if(reply_message == '') return false;

        $("#reply_message").val('');
        $("#final_reply_button").addClass('disabled');
        $.ajax({
            url:"<?php echo site_url();?>livechat/reply_to_conversation",
            type:'POST',
            data:{whatsapp_bot_id:whatsapp_bot_id,reply_message:reply_message,from_user_id:from_user_id,thread_id:thread_id,media_type:media_type,message_tag:message_tag},
            
            success:function(response){
            $("#conversation_modal_body").append(response);
            $("#final_reply_button").removeClass('disabled');
            }

        });
    });

    $("#put_content").html(loading);
    $('#put_content').on('scroll', function (){
            var scrollableHeight = $('#put_content')[0].scrollHeight - $('#put_content').outerHeight();
            if ($('#put_content').scrollTop() >= scrollableHeight-100) {
                ajax_call(".open_conversation:first",true,true);
            }
        });

    function ajax_call(selected,already_loaded,dont_open)
    {
        var whatsapp_bot_id = $("#refresh_data").attr('whatsapp_bot_id');

        var ulElement = document.querySelector('#put_content');
        var listItems = ulElement.querySelectorAll('li');
        var start = listItems.length;
        var media_type = $('#media_type').val();
        if(typeof dont_open === undefined) dont_open = false;
        if(!already_loaded) $("#chat_with").html(global_lang_loading+"...");
        $.ajax({
            url:"<?php echo site_url();?>livechat/get_conversation_list",
            type:'POST',
            data:{whatsapp_bot_id:whatsapp_bot_id,message_type:messageType,start:start,media_type:media_type},
            async: false,
            success:function(response){
            if(start==0){
                $("#put_content").empty();
            }
            $("#put_content").append(response);

            if(dont_open && !areWeUsingScroll) return;

            setTimeout(function(){

                if($(selected).length==0){
                    $("#mark_as_action").hide();
                    $(".search_list2").hide();
                    $("#phone_number").empty();
                    $('#profile_name_edit').hide();
                    $("#chat_with").html(whatsapp_bot_manager_lang_webhook_data_view_no_data);
                    $("#conversation_modal_body").html('');
                }
                else{                
                    $("#mark_as_action").show();
                    $(".search_list2").show();
                    $('#profile_name_edit').show();
                }

                if(already_loaded) $(selected).addClass('bg-light');
                else $(selected).click();
            }, 1000);
            }
        });
    }

    $(document).on('click','#refresh_data',function(e){
        e.preventDefault();
        var from_user_id = $('.open_conversation.bg-light').attr('from_user_id');
        var selected = ".open_conversation[from_user_id="+from_user_id+"]";
        var trueFalse = messageType=='all' ? true : false;
        if(messageType!='all') selected = ".open_conversation:first";
        $("#put_content").empty();
        ajax_call(selected,trueFalse);
    });

    $(document).on('change','#mark_as_action',function(e){
        e.preventDefault();
        var from_user_id = $('.open_conversation.bg-light').attr('from_user_id');
        var action = $(this).val();
        $.ajax({
            url:"<?php echo site_url();?>livechat/update_mark_as_action",
            type:'POST',        
            dataType:'JSON',
            data:{subscriber_id:from_user_id,action},
            success:function(response){
                if(response.status=='1'){
                    iziToast.success({title: '',message: response.message,position: 'bottomRight'});
                    $("#refresh_data").click();    
                }
                else iziToast.error({title: '',message: response.message,position: 'bottomRight'});
            }
        });
    });


    $(document).on('click','.postback-item',function(e){
        e.preventDefault();
        var whatsapp_bot_id = $("#refresh_data").attr('whatsapp_bot_id');
        var subscriber_id = $('.open_conversation.bg-light').attr('from_user_id');
        var postback_id = $(this).attr('data-id');

        $('#postbackModal').modal('hide');
        $('#postback_reply_button').html('<i class="fas fa-spinner"></i>');

        $.ajax({
            context:this,
            url:"<?php echo site_url();?>livechat/send_postback_reply",
            type:'POST',
            data:{whatsapp_bot_id,subscriber_id,postback_id},
            dataType:'JSON',
            success:function(response){
            $('#postback_reply_button').html('<i class="fas fa-robot"></i>');
            if(response.status=='1'){
                swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success')
            }
            else{
                swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error')
            }
            }
        });
    });


    $(document).on('click', '.file_preview', function(event) {
        event.preventDefault();
        var file_url = $(this).attr('data_file_url');
        var media_type = $(this).attr('data_media_type');
        var livechat_file_preview_url_new = "<?php echo site_url();?>livechat/display_message_file/"+file_url+'/'+media_type;
        openTab(livechat_file_preview_url_new);
    });

    $(document).on('click',"#send_file",function(e){
        e.preventDefault();
        $('#livechat-current-item-url').val('');
        $('#livechat-current-item-name').val('');
        $('#livechat-dropzone .dz-preview').remove();
        $('#livechat-dropzone').removeClass('dz-started dz-max-files-reached');
        Dropzone.forElement('#livechat-dropzone').removeAllFiles(true);
        $("#send_fileModal").modal('show');
    });

    Dropzone.autoDiscover = false;
    $("#livechat-dropzone").dropzone({
        url: "<?php echo site_url();?>livechat/livechat_upload_file",
        // maxFilesize:2,
        uploadMultiple:false,
        paramName:"media_file",
        createImageThumbnails:true,
        acceptedFiles: ".png, .jpg, .jpeg, .webp, .JPEG, .JPG, .PNG, .WEBP, .aac, .amr, .mp3, .AAC, .AMR, .MP3, .mp4, .MP4, .3gpp, .3GPP, .doc, .docx, .pdf, .txt, .ppt, .pptx, .xls, .xlsx, .DOC, .DOCX, .PDF, .TXT, .PPT, .PPTX, .XLS, .XLSX, .wav, .WAV",
        maxFiles:1,
        addRemoveLinks:false,
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success:function(file, response) {
            response= JSON.parse(response);

            if (response.status===false) {
                swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error')
                return;
            }
            if (response.file) {
            $('#livechat-current-item-name').val(response.file_name);
            $('#livechat-current-item-url').val(response.file).trigger('keyup');
        }

        }
    });

    $(document).on('keyup','#livechat-current-item-url',function(e){
        $("#send_fileModal").modal('hide');
        var thread_id = $("#final_reply_button").attr('thread_id');
        var whatsapp_bot_id = $("#final_reply_button").attr('whatsapp_bot_id');
        var from_user_id = $("#final_reply_button").attr('from_user_id');
        var media_url = $('#livechat-current-item-url').val();
        var media_name = $('#livechat-current-item-name').val();
        var message_tag = $('#message_tag').val();
        var media_type = $('#media_type').val();
        if(media_url == '') return false;
        $("#final_reply_button").addClass('disabled');
        $.ajax({
            url:"<?php echo site_url();?>livechat/livechat_send_file",
            type:'POST',
            data:{whatsapp_bot_id:whatsapp_bot_id,media_url:media_url,media_name:media_name,from_user_id:from_user_id,thread_id:thread_id,message_tag:message_tag,media_type},
            
            success:function(response){
            $("#conversation_modal_body").append(response);
            $("#final_reply_button").removeClass('disabled');
            }

        });
    });


                

    });

    function search_in_subscriber_ul(obj,ul_id){  // obj = 'this' of jquery, ul_id = id of the ul
    var filter=$(obj).val().toUpperCase().trim();
    var count_li = 0;
    $('#'+ul_id+' li').each(function(){
        var content=$(this).text().trim();
        if (content.toUpperCase().indexOf(filter) > -1) {
        $(this).removeClass('d-none');
        $(this).addClass('d-flex');
        count_li++;
        }
        else {
        $(this).addClass('d-none');
        $(this).removeClass('d-flex');
        }
    });

    if(filter.length>=3 && count_li==0){
        var whatsapp_bot_id = $("#refresh_data").attr('whatsapp_bot_id');
        $.ajax({
            url:whatsapp_livechat_search_subscriber_db_url,
            type:'POST',
            data:{whatsapp_bot_id,filter},
            success:function(response){
            $("#put_content").append(response);
            }
        });
    }

    }
    var headerType = null;
    var buttonType = null;

    $(document).ready(function() {
        // livechatDropzone = uploadFileViaDropzone({
        //     csrf_token,
        //     file_upload_url: flow_builder_upload_media,
        //     file_delete_url: flow_builder_delete_media,
        //     elementForInsertingUrlOntoInput: $('#bot-template-header-media-url-input'),
        // })

        $('#pills-tab').on('show.bs.tab', function (event) {
            const target = $(event.target).data('target');
            if ('#pills-bot-flow' === target && postbackPayload.length === 0) {
                const whatsapp_bot_id = $('#whatsapp_bot_id').val();
                if (whatsapp_bot_id) {
                    fetchPostbackPayload(whatsapp_bot_id, function (response) {
                        $('#bot-postback-list').html('').html(
                            getBotPostbackHtmlList(response)
                        );
                    });
                }
            }

            if ('#pills-message-template' === target && botTemplatePayload.length === 0) {
                fetchBotTemplatePayload(function(response) {
                    $('#bot-template-list').html('').html(
                        getBotTemplateHtmlList(response)
                    );
                })
            }
        });

        $('#postbackModal').on('show.bs.modal', function (event) {
            $('#pills-bot-flow-tab').tab('show');
        });

        $('#template-modal').on('show.bs.modal', function (event) {
            const templateId = $(event.relatedTarget).data('id')
            const templateName = $(event.relatedTarget).data('template-name')
            headerType = $(event.relatedTarget).data('header-type');
            buttonType = $(event.relatedTarget).data('button-type');

            $('#template-modal-label').find('span').text(templateName)
            $('#send-message-template-action').attr('data-id',templateId)

            const buttonList = getButtonList(templateId);
            const dynamicVariableList = getDynamicVariableList(templateId);

            var buttonType = '';
            if (buttonList.length > 0 && "0" in buttonList && "type" in buttonList[0]){
                var buttonType = buttonList[0]['type'];
            }

            if (buttonType!='quick_reply' && false === dynamicVariableList.some(item => item.startsWith('#!')) && headerType != 'media') {
                $(event.relatedTarget).addClass('bg-light btn-progress');
                const whatsapp_bot_template_id = $('#send-message-template-action').attr('data-id');
                const whatsapp_bot_id = $("#refresh_data").attr('whatsapp_bot_id');
                const chat_id = $('.open_conversation.bg-light').attr('thread_id');

                $.ajax({
                    url: "<?php echo site_url();?>livechat/send_message_template",
                    method: 'POST',
                    dataType: 'JSON',
                    data: {
                        whatsapp_bot_template_id: whatsapp_bot_template_id,
                        whatsapp_bot_id: whatsapp_bot_id,
                        chat_id: chat_id,
                        botTemplateHeaderType: headerType,
                        botTemplateButtonType: buttonType,
                        botTemplateHeaderMediaUrl: null,
                        botTemplateQuickreplyButtonValues: [],
                        botTemplateDynamicVariableValues: [],
                    },
                    success: function (response) {
                        response= JSON.parse(response);
                        $(event.relatedTarget).removeClass('bg-light btn-progress');
                        $('#postback_reply_button').html('<i class="fas fa-robot"></i>');
                        if (response.status == '1') {
                            swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success')
                                .then((result) => {
                                    if (result.isConfirmed) {
                                        $("#template-modal").modal('hide');
                                        $("#postbackModal").modal('hide');
                                        //$('#refresh_data').click();
                                    }
                                });
                        } else {
                            swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error')
                        }
                    },
                });

                return false;
            }

            manageButtonFieldsWithPostbackList(buttonList);

            manageDynamicVariableFieldsWithValues(dynamicVariableList);

            const botTemplateQuickreplyButtonElement = $('#bot-template-quickreply-button-wrapper')
            const botTemplateHeaderMediaElement = $('#bot-template-header-media-wrapper')

            if ('quick_reply' === buttonType) {
                botTemplateQuickreplyButtonElement.removeClass('d-none').addClass('d-block')
            } else {
                botTemplateQuickreplyButtonElement.removeClass('d-block').addClass('d-none')
            }

            if ('media' === headerType) {
                const headerSubtype = $(event.relatedTarget).data('header-subtype')
                if (mimeTypesMap[headerSubtype]) {
                    const acceptedFiles = mimeTypesMap[headerSubtype].join(',')
                    $(livechatDropzone.element).find('[type="file"]').attr('accept', acceptedFiles)
                    livechatDropzone.options.acceptedFiles = acceptedFiles
                } else {
                    $(livechatDropzone.element).find('[type="file"]').attr('accept', 'unknown')
                    livechatDropzone.options.acceptedFiles = 'unknown'
                }

                botTemplateHeaderMediaElement.removeClass('d-none').addClass('d-block')
            } else {
                botTemplateHeaderMediaElement.removeClass('d-block').addClass('d-none')
                $(livechatDropzone.element).find('[type="file"]').attr('accept', 'unknown')
                livechatDropzone.options.acceptedFiles = 'unknown'
            }
        });

        submitForm({ headerType, buttonType });
    });

    function fetchBotTemplatePayload(callback) {
        var whatsapp_business_id = $('#whatsapp_bot_id option:selected').attr('data-wa-business-id');
        var media_type = $('#media_type').val();
        
        $.ajax({
            // url: whatsapp_livechat_get_template_list_url.replace(':whatsapp_business_id', whatsapp_business_id),
            url: "<?php echo site_url();?>livechat/get_postback_list_json/"+whatsapp_business_id,
            type: 'GET',
            dataType: 'JSON',
            async: false,
            
            success:function(response) {
                botTemplatePayload = response;
                callback(response);
            }
        });
    }

    function fetchPostbackPayload(whatsapp_bot_id, callback) {
        var whatsapp_business_id = $('#whatsapp_bot_id option:selected').attr('data-wa-business-id');
        var media_type = $('#media_type').val();
        $.ajax({
            url: "<?php echo site_url();?>livechat/get_postback_list_json/"+whatsapp_bot_id+"/"+media_type,
            type: 'GET',
            dataType: 'JSON',
            async: false,
            
            success:function(response) {
                postbackPayload = response;
                callback(response);
            }
        });
    }

    function getBotTemplateHtmlList(templates) {
        if (! Array.isArray(templates)) return;

        let html = '<div class="list-group">';

        for (const template of templates) {
            html += '<a href="#" data-toggle="modal" data-target="#template-modal" data-id="' + template.id + '" data-template-name="' + template.template_name + '" data-header-type="' + template.header_type + '" data-header-subtype="' + template.header_subtype + '" data-button-type="' + template.button_type + '" class="list-group-item flex-column align-items-start bot-template-list-item item-searchable">';
                html += '<div class="d-flex w-100 justify-content-between mt-1">';
                    html += '<h6 class="mb-1"><i class="fas fa-check-circle text-success"></i> ' + template.template_name + '</h6>';
                html += '</div>';
            html += '</a>';
        }

        html +=' </div>';

        return html;
    }

    function getBotPostbackHtmlList(postbacks) {
        if (! Array.isArray(postbacks)) return;

        let html = '<div class="list-group">';
        for (const postback of postbacks) {
            html += '<a href="#" data-id="' + postback.postback_id + '"  class="list-group-item flex-column align-items-start postback-item item-searchable">';
                html += '<div class="d-flex w-100 justify-content-between mt-1">';
                    html += '<h6 class="mb-1"><i class="fas fa-check-circle text-success"></i> ' + postback.template_name + '</h6>';
                html += '</div>';
            html += '</a>';
        }

        html +=' </div>';

        return html;
    }

    const getButtonList = (templateId) => {
        const template = botTemplatePayload.find(item => parseInt(item.id, 10) === parseInt(templateId, 10))

        if(template && template.button_content) {
            const content = JSON.parse(template.button_content || '{}')
            if (content && 'buttons' === content.type && Array.isArray(content.buttons)) {
                return content.buttons
            }
        }

        return []
    }

    const manageButtonFieldsWithPostbackList = async (buttons) => {
    const botTemplateQuickreplyButtonElement = $('#bot-template-quickreply-button-wrapper')
    botTemplateQuickreplyButtonElement.html('')
    let index = 0

    for (const button of buttons) {
        const randomInt = getRandomInt(1000, 9999)
        const html = '' +
            '<div class="form-group pb-3">' +
            '<label class="pb-1 control-label">' + button.text + '</label>' +
            '<select id="bot-template-quickreply-buttons-' + randomInt + '" class="form-control select2 bot-template-quickreply-button">' + preparePostbackSelectOptions(postbackPayload) + '</select>' +
            '</div>'

            
        botTemplateQuickreplyButtonElement.append(html)
        $("#bot-template-quickreply-buttons-' + randomInt + '").select2({width: "100%"})
        index++
    }
}


    function getRandomInt(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);

        return Math.floor(Math.random() * (max - min) + min);
    }

    const getDynamicVariableList = (templateId) => {
        const content = botTemplatePayload.find(item => parseInt(item.id, 10) === parseInt(templateId, 10))

        if(content && content.variable_map) {
            const parsedContent = JSON.parse(content.variable_map || '{}')
            let returnValue = []

            if (parsedContent && parsedContent.header && isPlainObject(parsedContent.header)) {
            returnValue = returnValue.concat(Object.values(parsedContent.header))
            }

            if (parsedContent && parsedContent.body && isPlainObject(parsedContent.body)) {
            returnValue = returnValue.concat(Object.values(parsedContent.body))
            }

            if (parsedContent && parsedContent.button && isPlainObject(parsedContent.button)) {
            returnValue = returnValue.concat(Object.values(parsedContent.button))
            }

            if (parsedContent && parsedContent.footer && isPlainObject(parsedContent.footer)) {
            returnValue = returnValue.concat(Object.values(parsedContent.footer))
            }

            return returnValue
        }

        return []
    }

    function isPlainObject(obj) {
        return obj ? obj.constructor === {}.constructor : false
    }

    const manageDynamicVariableFieldsWithValues = async (variables) => {
        const botTemplateDynamicVariableElement = $('#bot-template-dynamic-variable-wrapper')
        botTemplateDynamicVariableElement.html('')
        let position = 0

        for (const variable of variables) {
            position++

            if (! variable.startsWith('#!') && ! variable.endsWith('!#')) {
                continue
            }

            const html = '' +
                '<div class="form-group pb-3">' +
                '<label class="pb-1 control-label">' + variable.replace(/[#|!]/g, '') + '</label>' +
                '<input type="text" class="form-control bot-template-dynamic-variable" data-position="' + position + '">' +
                '</div>'

            botTemplateDynamicVariableElement.append(html)
        }
    }

    function submitForm(data, callback) {
        $('.template-modal-form').submit(function(event) {
            event.preventDefault();

            const buttonElement = $(event.target).find('[type="submit"]')[0];
            buttonElement.classList.add('btn-progress')

            const botTemplateQuickreplyButtonElement = $('.bot-template-quickreply-button')
            const botTemplateQuickreplyButtonInputLabels = []
            const botTemplateQuickreplyButtonValues = botTemplateQuickreplyButtonElement.map((index, element) => { // eslint-disable-line
                if (new Boolean(element.value).valueOf() === false) {
                    botTemplateQuickreplyButtonInputLabels.push(cleanXss($(element).prev().text()))
                }
                return element.value
            }).get()

            const botTemplateDynamicVariableElement = $('.bot-template-dynamic-variable')
            const botTemplateDynamicVariableValues = botTemplateDynamicVariableElement.map((index, element) => { // eslint-disable-line
                const label = cleanXss($(element).prev().text())
                const position = $(element).data('position')
                return { label, value: element.value, position }
            }).get()

            const botTemplateHeaderMediaUrl = $('#bot-template-header-media-url-input').val() || null

            let hasError = false;
            if (botTemplateQuickreplyButtonElement.length !== botTemplateQuickreplyButtonValues.filter(Boolean).length) {
                hasError = true;
            }

            const emptyDynamicVariableFields = botTemplateDynamicVariableValues
                .filter(item => new Boolean(item.value).valueOf() === false)

            if ((botTemplateDynamicVariableElement.length ===
                botTemplateDynamicVariableValues.length) &&
                emptyDynamicVariableFields.length > 0
            ) {
                hasError = true;
            }

            if ('media' === data.headerType) {
                if (! botTemplateHeaderMediaUrl) {
                    hasError = true;
                }
            }

            if (hasError) {
                buttonElement.classList.remove('btn-progress')
                $('.error-message').text(global_all_fields_are_required)
                return;
            }

            const whatsapp_bot_template_id = $('#send-message-template-action').attr('data-id');
            const whatsapp_bot_id = $("#refresh_data").attr('whatsapp_bot_id');
            const chat_id = $('.open_conversation.bg-light').attr('thread_id');
            var original_file_name = $("#bot-template-file-name").val();

            $.ajax({
                url: "<?php echo site_url();?>livechat/send_message_template",
                method: 'POST',
                dataType: 'JSON',
                data: {
                    whatsapp_bot_template_id: whatsapp_bot_template_id,
                    whatsapp_bot_id: whatsapp_bot_id,
                    chat_id: chat_id,
                    botTemplateHeaderMediaUrl,
                    botTemplateQuickreplyButtonValues,
                    botTemplateDynamicVariableValues,
                    original_file_name
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf_token);
                },
                success:function(response) {
                    buttonElement.classList.remove('btn-progress');
                    $('#postback_reply_button').html('<i class="fas fa-robot"></i>');
                    if(response.status=='1'){
                        response= JSON.parse(response);
                        swal('<?php echo $this->lang->line("Success"); ?>', response.message, 'success')
                        .then((result) => {
                            if (result.isConfirmed) {
                                $("#template-modal").modal('hide');
                                $("#postbackModal").modal('hide');
                                //$('#refresh_data').click();
                            }
                        });
                    }
                    else{
                        swal('<?php echo $this->lang->line("Error"); ?>', response.message, 'error')
                    }
                }
            });
        });
    }

    function preparePostbackSelectOptions (optionsArray, defaultValue = '') {
        let html = ''

        for (const option of optionsArray) {
            if ('object' === typeof option) {
                if (defaultValue === option.key) {
                    html += '<option value="' + option.postback_id + '" selected>' + option.template_name + '</option>'
                } else {
                    html += '<option value="' + option.postback_id + '">' + option.template_name + '</option>'
                }
            }
        }

        return html
    }

/**
 * Upload files via dropzone
 *
 * Expects the following html
 *
 *  <div id="file-upload-dropzone" class="dropzone d-flex justify-content-center align-items-center mb-1">
 *    <div class="dz-default dz-message">
 *      <input type="file" class="d-none">
 *      <i class="fas fa-cloud-upload-alt"></i>
 *    </div>
 *  </div>
 *
 * @param {*} data
 */
    const uploadFileViaDropzone = (data) => {
        const config = {
            elementId: data.elementId || '#file-upload-dropzone',
            elementForInsertingUrlOntoInput: data.elementForInsertingUrlOntoInput,
            maxFiles: data.maxFiles || 1,
            maxFilesize: data.maxFilesize || 20,
            acceptedFiles: data.acceptedFiles || ".png, .jpg, .jpeg, .webp, .JPEG, .JPG, .PNG, .WEBP, .aac, .amr, .mp3, .AAC, .AMR, .MP3, .mp4, .MP4, .3gpp, .3GPP, .doc, .docx, .pdf, .txt, .ppt, .pptx, .xls, .xlsx, .DOC, .DOCX, .PDF, .TXT, .PPT, .PPTX, .XLS, .XLSX",
            uploadMultiple: data.uploadMultiple || false,
            fileUploadUrl: data.file_upload_url,
            fileDeleteUrl: data.file_delete_url,
            csrfToken: data.csrf_token
        }

        let serverGeneratedFilename = ''
        window.flowBuilderUploadedFileData = {}

        const dropzone = new Dropzone(config.elementId, {
            url: config.fileUploadUrl,
            maxFilesize: config.maxFilesize,
            uploadMultiple: config.uploadMultiple,
            paramName: "media_file",
            createImageThumbnails: true,
            acceptedFiles: config.acceptedFiles,
            maxFiles: config.maxFiles,
            addRemoveLinks: true,
            headers:{
                'X-CSRF-TOKEN': config.csrfToken
            },

            // eslint-disable-next-line
            success: async function(file, response) {
                // Display message if error
                if (false === response.status) {
                    await Swal.fire({
                        icon: 'error',
                        text: response.message,
                        title: 'Error!',
                    });

                    return
                }

                if (response.status) {
                    serverGeneratedFilename = response.file
                    window.flowBuilderUploadedFileData = {
                        mime_type: response.file_type,
                        file: response.file,
                    }

                    $('#bot-template-file-name').val(response.original_file_name)

                    config.elementForInsertingUrlOntoInput
                    && config.elementForInsertingUrlOntoInput.val(response.file)
                }
            },

            // eslint-disable-next-line
            removedfile: function(file) {
                const fileData = window.flowBuilderUploadedFileData
                if (fileData.file && (fileData.file === serverGeneratedFilename)) {
                    // eslint-disable-next-line
                    const result = deleteUploadedFile(serverGeneratedFilename)
                        .then(response => {
                            if (response.status) {
                                window.flowBuilderUploadedFileData = {}
                                config.elementForInsertingUrlOntoInput
                                && config.elementForInsertingUrlOntoInput.val('')
                                $(config.elementId + ' .dz-preview').remove()
                                Dropzone.forElement(config.elementId).removeAllFiles(true)
                            }
                        }).catch(error => {
                            if (error.status !== '200' && error.statusText) {
                                const message = error.status + ' ' + error.statusText
                                alert(message)
                            } else {
                                console.log(error)
                            }
                        })
                }
            },

            // eslint-disable-next-line
            error: function(file, message, xhr) {
                file.previewElement.remove()
                $('.dropzone.dz-started .dz-message').show()
                iziToast.warning(title="",message, 'bottomRight');
            },
        })

        // Removes previous files if there is any
        Dropzone.forElement(config.elementId).removeAllFiles(true)

        const deleteUploadedFile = async (file) => {
            return await $.ajax({
                type: 'POST',
                url: config.fileDeleteUrl,
                dataType: 'JSON',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', config.csrfToken);
                },
                data: { file },
            })
        }

        return dropzone;
    }

    const mimeTypesMap = {
        image: ['.png', '.jpg', '.jpeg', '.webp', '.JPEG', '.JPG', '.PNG', '.WEBP'],
        audio: ['.aac', '.amr', '.mp3', '.mp4', '.opus', '.AAC', '.AMR', '.MP3', '.MP4', '.OPUS'],
        video: ['.mp4', '.3gp', '.3gpp', '.MP4', '.3GP', '.3GPP'],
        document: ['.doc', '.docx', '.pdf', '.txt', '.ppt', '.pptx', '.xls', '.xlsx', '.DOC', '.DOCX', '.PDF', '.TXT', '.PPT', '.PPTX', '.XLS', '.XLSX'],
    }

    function cleanXss(string) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;',
            "/": '&#x2F;',
        };
        const reg = /[&<>"'/]/ig;
        return string.replace(reg, (match)=>(map[match]));
    }


</script>