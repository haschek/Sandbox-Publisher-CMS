<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php $this->show('TITLE'); ?></title>
</head>
<body>

<div class="header">
</div> <!-- /header -->

<div class="breadcrumb">
</div> <!-- /breadcrumb -->

<?php if ($this->content->teaser) { ?>
<div class="teaser">
</div>
<?php } ?>

<div class="content">

    <?php echo $this->show('content'); ?>

</div> <!-- /content -->

<div class="footer">
</div>

</body>
</html>
