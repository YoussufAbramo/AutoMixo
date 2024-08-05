<style>
    .subscriber_details{ padding-right: 20px !important;}
.card.chat-box{border-radius: 0;}
.form-control.search_list{width: 40%;border-radius: 4px !important;}
/*#put_content{height: 584px;overflow-y:auto;overflow-x:hidden;}*/
#put_content{overflow-y:auto;overflow-x:hidden;}
#refresh_interval,#mark_as_action{width: 90px;height: 30px;padding: 0 5px !important;text-align: center !important;background: transparent;margin-top:5px}
.form-control#reply_message{
  /* border-radius: 30px 0 0 30px; */
  font-size: 12px;
  padding-left: 20px;
  padding-right: 20px;
  line-height: 1.3 !important;}
#final_reply_button{border-radius: 0 30px 30px 0;}
#chat-form2{margin-block-end: 0;}
.chat-box .chat-form .btn{position: relative;transform: none; border-radius: 0;height: 42px;top: 0;right: 0;}
.chat-box .chat-form .form-control{height: 42px;}
.select2-container--default .select2-selection--single{border-radius: 0 !important;}
.no_radius{border-radius: 0 !important;}
.open_conversation.bg-primary .text-primary,.open_conversation.bg-primary .put-time{color: #777 !important;}
p.spaced{margin-bottom: 4px;}
.markup{min-width: 300px !important;font-weight: bold;box-shadow: 0 4px 8px rgba(0, 0, 0, 0.03);}
#profile_edited_name{border-radius: 4px;}
@media (min-width: 992px){  
  .mt-lg-2-5 {
      margin-top: 0.8rem!important;
  }
}

/* .bg-whatsapp {
    background: #128c7e !important;
} */

.chat-box .chat-content2 {
/*  height: 588px;*/
  /*overflo: hidden;*/
  width: 100%;
  padding-top: 5px !important;
/*  background: #e7efdd url("/assets/images/background/telegram.png") repeat;*/
  background: url("/assets/images/media/telegram.png") repeat;
}
.chat-box .chat-content2 .chat-item .chat-details {
  display: inline-block;
  width: 100%;
  margin-bottom: 20px; 
}

.chat-box .chat-content2 .chat-item .chat-details img {
  text-align: left;
  max-width: 300px !important;
  border-radius: 7px !important;
  /*display: block !important;*/
}
.chat-box .chat-content2 .chat-item.chat-right .chat-details img { text-align: right; }

.chat-box .chat-content2 .card-title,.chat-box .chat-content2 .card-text {
  min-width: 300px;
  max-width: 330px;
}

.chat-box .chat-content2 .chat-item .chat-details .chat-text {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.03);
  background-color: #fff;
  padding: 10px 15px;
  border-radius: 7px;
  max-width: 330px;
  display: inline-block;
  font-size: 13px;
}

.chat-box .chat-content2 .chat-item .chat-details .chat-text i{
  font-size: 30px;
  float: left;
}

.chat-box .chat-content2 .chat-item .chat-details .chat-text .file-name{
  float: left;
  padding-left: 15px;
  padding-top: 12px;
}

.chat-box .chat-content2 .chat-item .chat-details .chat-text a{
  color:black;
}

.chat-box .chat-content2 .chat-item.chat-right .chat-details .chat-text a.fw-bold{
  color:#555;
  font-weight: normal !important;
  text-decoration: underline;
}

.chat-box .chat-content2 .chat-item.chat-left .chat-details .chat-text a.fw-bold{
  color:#555;
  font-weight: normal !important;
  text-decoration: underline;
}

.chat-box .chat-content2 .chat-item .chat-details .chat-time {
  margin-top: 5px;
  font-size: 9px;
  font-weight: 400;
  opacity: .6; 
}
.chat-box .chat-content2 .chat-item .chat-details .chat-time .message_status .fas{
  font-size: 9px !important;
}

.chat-box .chat-content2 .chat-item.chat-right .chat-details {
  margin-left: 0;
  text-align: right;
}




#middle_column .card,  .collef .card{box-shadow: none !important;}
.chat-box .chat-form{padding: 2px;border-top: 1px solid #eee}
.card .card-header, .card .card-body, .card .card-footer {
  padding: 10px 15px; 
}
.list-unstyled-border li{border-bottom: 0 !important;}
#postback_reply_button,#canned_response,#send_file,#record_audio_message{width: 30px;padding: 0; text-align: center;height: 30px;margin-top:5px;border-radius: 30px;margin-right: 3px;}
section .section-title:after{display: none}
#main .main-content{padding:0px !important;}

/*#back-to-list{
  position:absolute;top:0;right:0;border-radius: 0 .267rem 0 .267rem;font-size: 14px;
}*/
#notification-navbar,footer{display: none}
html,body{overflow-x: hidden;}

.list-group-item.active, .nav-pills .nav-link.active, .nav-pills .show>.nav-link{
  background: transparent !important;
  border-radius: .25rem .25rem 0 0;
/*  color:#fff !important;*/
  padding-top:6.5px;
  padding-bottom:6.5px;
  border-bottom: 3px solid #fff;
  font-weight: bold;
}

.action-item {
    width: 26px;
    height: 26px;
    border-radius: 26px;
    padding: 0 !important;
    line-height: 2;
    color: #000 !important;
    background: #fff !important;
    border-color:#fff;
}
#back-to-chat.action-item{
  color: #000 !important;
  border-color:#000;
}

.sidebar-wrapper{box-shadow: none !important}

#col-subscriber-list .card-body{
  border-right: 1px solid #eee !important;
}

.navbar-brand #profile_name_edit{
  visibility: hidden;
}

.system_message_text{
  max-width:450px !important;
  border:1px dashed #ccc;
  background-color: rgba(222,227,230,.4196078431372549);
  color: #0d8bf1;
  text-align: center;
  padding: 5px 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.03);
  width: auto;
  font-size: 11px;
  margin-bottom: 10px !important;
}
.system_message_date{
  font-size: 10px !important;
}

#reply_message{
  height: 42px !important;
  overflow:hidden;
  padding: 0 ;
  padding-top: 11px;
}


/* width */
::-webkit-scrollbar {
  width: 5px;
  border;
}

/* Track */
::-webkit-scrollbar-track {
  background: #f1f1f1;
}

/* Handle */
::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 5px;
}

/* Handle on hover */
::-webkit-scrollbar-thumb:hover {
  background: #555;
}

body.sidebar-mini .main-sidebar {
  overflow: scroll !important;
}

</style>

<?php if($media_type === 'fb'): ?>
  <style>
      .chat-box .chat-content2 .chat-item.chat-right .chat-details .chat-text {
          background-color: #dfedf5;
          /* background-color: #c7f6dc; */
          color: #000;
          text-align: left;
      }

      .bg-light-primary {
          background: #dfedf5 !important;
      }
  </style>
<?php else: ?>
  <style>
      .chat-box .chat-content2 .chat-item.chat-right .chat-details .chat-text {
          background-color: #fddeea;
          /* background-color: #c7f6dc; */
          color: #000;
          text-align: left;
      }

      .bg-light-primary {
          background: #fddeea !important;
      }
  </style>
<?php endif; ?>





