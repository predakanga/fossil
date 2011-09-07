<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>{block name=assignations}{/block}
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Fossil - {$title}</title>
<link href="static/css/reset.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/grid-16.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/text.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/menu.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/table.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/form.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/grey-box.css" media="all" rel="stylesheet" type="text/css" />
<link href="static/css/test.css" media="all" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="static/js/test.js"></script>
<script type="text/javascript">
google.load("jquery", "1.6.2");
</script>
</head>
<body>
	
<div class="grid-centered main_col cf">
	<div class="box-16-expand">
		<div class="box no-margin"><h1>{$title_verbose|default:{$title|capitalize}}</h1></div>
        {block name=content_layout}
		<div class="box-16-expand">
			<ul class="menu-horizontal no-margin">
				<li class="current"><a>Home</a></li>
				<li><a>Users</a></li>
				<li><a>Settings</a></li>
				<li><a>About</a></li>
			</ul>
			<div class="box-16-contract">
				<div class="box">
					Focus item
				</div>
			</div>
		</div>
		<div class="box-14-expand push-1">
			<div class="box-9">
				<div class="box cf">{block name=content}Main content goes here{/block}</div>
			</div>
			<div class="box-5">
				<div class="box">Poll or similar</div>
			</div>
		</div>
        {/block}
        {include file='errors.tpl'}
	</div>
</div>

</body>
</html>