
<?php if(current_url()==base_url('dashboard') && check_module_access($module_id=82,true)):?>
  <?php  $unread_message_count = $unread_count_data->total_unseen ?? 0;?>
  <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown" style="margin-top: 5px" class="nav-link notification-toggle nav-link-lg mr-2 <?php if($unread_message_count>0) echo 'beep'; ?>"><i class="far fa-comment-alt"></i> <span class="badge badge-primary p-1" style="font-size:8px;position:absolute;margin-left: -6px;margin-top:15px;"><?php echo $unread_message_count;?></span></a>
    <?php if(isset($whatsapp_unread_data) && count($whatsapp_unread_data)>0):?>
      <div class="dropdown-menu dropdown-list dropdown-menu-right">
        <div class="dropdown-header"><?php echo $this->lang->line('Livechat Notifications'); ?>
          
          <?php 
          if(count($whatsapp_unread_data)==0)  echo '<div class="float-right">'.$this->lang->line("Nothing new").'</div>'; 
          else echo '<div class="float-right">'.count($whatsapp_unread_data)." ".$this->lang->line("New").'</div>';
          ?>     
        
        </div>
        <div class="dropdown-list-content dropdown-list-icons">

          <?php 
          foreach($whatsapp_unread_data as $row) 
          { ?>
            <a href="<?php echo base_url('livechat/load_livechat').'?subscriber_id='.$row->subscribe_id;;?>" class="dropdown-item">
              <div class="dropdown-item-icon <?php echo "bg-primary"; ?> text-white">
                <i class="<?php echo ($row->social_media === 'fb') ? 'fab fa-facebook' : 'fab fa-instagram'; ?>"></i>
              </div>
              <div class="dropdown-item-desc">
                <?php 
                  if(strlen($row->last_conversation_message)>50)
                  echo format_preview_message($row->last_conversation_message,115);
                  else echo $row->last_conversation_message;
                ?>
                <div class="time"><?php echo date_time_calculator($row->last_subscriber_interaction_time,true);?></div>
              </div>
            </a>
          <?php 
          } ?> 
        </div>
      </div>
    <?php endif ?>
  </li>
<?php endif ?>

<?php if(isset($annoucement_data) && count($annoucement_data)==0){ ?>
<a href="<?php echo site_url().'announcement/full_list';?>"  style="margin-top: 5px" class="nav-link nav-link-lg mr-2 <?php if(count($annoucement_data)>0) echo 'beep'; ?>"><i class="far fa-bell text-dark"></i> <span class="badge badge-secondary p-1" style="font-size:8px;position:absolute;margin-left: -6px;margin-top:15px;"><?php echo isset($annoucement_data) && !empty($annoucement_data) ? count($annoucement_data) : 0;?></span></a>
<?php } else{ ?>
  <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown" style="margin-top: 5px" class="nav-link notification-toggle nav-link-lg mr-2 <?php if(count($annoucement_data)>0) echo 'beep'; ?>"><i class="far fa-bell text-dark"></i> <span class="badge badge-secondary p-1" style="font-size:8px;position:absolute;margin-left: -6px;margin-top:15px;"><?php echo isset($annoucement_data) && !empty($annoucement_data) ? count($annoucement_data) : 0;?></span></a>
    <?php if(isset($annoucement_data) && count($annoucement_data)>0):?>
    <div class="dropdown-menu dropdown-list dropdown-menu-right">
      <div class="dropdown-header"><?php echo $this->lang->line('Notifications'); ?>
        
        <?php 
        if(count($annoucement_data)==0)  echo '<div class="float-right">'.$this->lang->line("Nothing new").'</div>'; 
        else echo '<div class="float-right">'.count($annoucement_data)." ".$this->lang->line("New").'</div>';
        ?>     
      
      </div>
      <div class="dropdown-list-content dropdown-list-icons">

        <?php 
        foreach($annoucement_data as $row) 
        { ?>

          <a href="<?php echo site_url().'announcement/details/'.$row['id'];?>" class="dropdown-item">
            <div class="dropdown-item-icon <?php echo "bg-".$row['color_class']; ?> text-white">
              <i class="<?php echo $row['icon']; ?>"></i>
            </div>
            <div class="dropdown-item-desc">
              <?php 
                if(strlen($row['title'])>50)
                echo substr($row['title'], 0, 50)."...";
                else echo $row['title'];
              ?>
              <div class="time"><?php echo date_time_calculator($row['created_at'],true);?></div>
            </div>
          </a>
        <?php 
        } ?> 
      </div>
      <div class="dropdown-footer text-center">
        <a href="<?php echo site_url().'announcement/full_list';?>"><?php echo $this->lang->line('View all');?> <i class="fas fa-chevron-right"></i></a>
      </div>
    </div>
  <?php endif;?>
  </li>
<?php } ?>

