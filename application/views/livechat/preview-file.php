<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="<?php echo $this->security->get_csrf_hash() ?>">
        <title><?php echo $this->config->item('product_name')." | Livechat preview";?></title>
        <link rel="shortcut icon" href="<?php echo base_url();?>assets/img/favicon.png" type="image/x-icon">
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/modules/bootstrap/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
          <div class="row">
            <div class="col-12 col-md-6 offset-md-3 pt-5 text-center">
            <?php if($media_type=="video"){ ?>
                <video width="100%" height="auto"  src="<?php echo $file_url;?>" controls autoplay></video>
            <?php }
            else if($media_type=="audio"){ ?>
                <audio width="100%" controls src="<?php echo $file_url;?>"></audio>
            <?php }
            else if($media_type=="image"){ ?>
                <img class="img-thumbnail img-fluid" src="<?php echo $file_url;?>">
            <?php } ?>

            </div>
          </div>
        </div>
    </body>

</html>
