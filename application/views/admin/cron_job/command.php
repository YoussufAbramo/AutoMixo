<section class="section">
  <div class="section-header">
    <h1><i class="fas fa-tasks"></i> <?php echo $page_title; ?></h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><?php echo $this->lang->line("System"); ?></div>      
      <div class="breadcrumb-item"><?php echo $page_title; ?></div>
    </div>
  </div>

  <?php $this->load->view('admin/theme/message'); ?>

  <div class="section-body">
  	<div class="row">
      <div class="col-12">			
		<div class="card">
          <div class="card-header">
            <h4><i class="fas fa-circle"></i> <?php echo $this->lang->line("Membership Expiration Alert & Delete Junk Data");?> <code><?php echo $this->lang->line("Once/Day"); ?></code></h4>
          </div>
          <div class="card-body">
            <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/membership_alert_delete_junk_data")." >/dev/null 2>&1"; ?></span></code></pre>
          </div>
        </div>
  
        <?php if($this->basic->is_exist("add_ons",array("project_id"=>30)) || $this->basic->is_exist("add_ons",array("project_id"=>40))) :?>
        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-circle"></i> 
              <?php echo $this->lang->line("Sequecne Message Broadcasting");?>
              <code><?php echo $this->lang->line("Once/5 Minutes"); ?></code>
              <p class="mb-0">
                <b class="text-danger">*** Do not set this cron if you have already set <u><?php echo "curl ".site_url("cron_job/background_scanning_update_subscriber_info");?></u></b>
              </p>
            </h4>
          </div>
          <div class="card-body">
            <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/sequence_message")." >/dev/null 2>&1"; ?></span></code></pre>
          </div>
        </div>
      <?php endif;?>

        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-circle"></i> 
              <?php echo $this->lang->line("Broadcasting");?>
              <code><?php echo $this->lang->line("Once/Minute"); ?></code></h4>
          </div>
          <div class="card-body">
            <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/braodcast_message")." >/dev/null 2>&1"; ?></span></code></pre>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h4>
              <i class="fas fa-circle"></i> 
              <?php echo $this->lang->line("Auto Comment");?>
              <?php if($this->basic->is_exist("add_ons",array("project_id"=>29))) : ?>
                <?php echo ' & '.$this->lang->line("Auto Share");?>
                <?php echo " & ".$this->lang->line("Cart Reminder"); ?>

              <?php endif; ?>
              <code><?php echo $this->lang->line("Once/5 Minutes"); ?></code></h4>
          </div>
          <div class="card-body">
            <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/auto_comment_on_post")." >/dev/null 2>&1"; ?></span></code></pre>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h4><i class="fas fa-circle"></i> <?php echo $this->lang->line("Social Posting");?> <code><?php echo $this->lang->line("Once/5 Minutes"); ?></code></h4>
          </div>
          <div class="card-body">
            <pre class="language-javascript"><code class="dlanguage-javascript"><span class="token keyword"><?php echo "curl ".site_url("cron_job/publish_post")." >/dev/null 2>&1"; ?></span></code></pre>
          </div>
        </div>
	  </div>
  </div>
</section>