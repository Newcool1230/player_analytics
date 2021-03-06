<?php 

if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
{
    header("Location: ../index.php?error=".urlencode("Direct access not allowed."));
    die();
}

//Database Info
include 'config.php';

// Include database class
include 'database.class.php';

if (!isset($_GET['id'])) {
	$_GET['id'] = date("Y-m-d", strtotime('-7 day')).",".date("Y-m-d");
}

$date = explode(",", $_GET['id']);
$c_total = 0;
$m_total = 0;
$p_total = 0;

// Instantiate database.
$database = new Database();

$database->query('SELECT `country` AS label, COUNT(`country`) AS value FROM `player_analytics` WHERE `connect_date` BETWEEN  :start AND :end GROUP BY `country`');
$database->bind(':start', $date[0]);
$database->bind(':end', $date[1]);
$country = $database->resultset();

$database->query('SELECT `connect_method` AS label, COUNT(`connect_method`) AS value FROM `player_analytics` WHERE `connect_date` BETWEEN  :start AND :end GROUP BY `connect_method`');
$database->bind(':start', $date[0]);
$database->bind(':end', $date[1]);
$method = $database->resultset();

$database->query('SELECT `premium` AS label, COUNT(`premium`) AS value FROM `player_analytics` WHERE `connect_date` BETWEEN  :start AND :end GROUP BY `premium`');
$database->bind(':start', $date[0]);
$database->bind(':end', $date[1]);
$premium = $database->resultset();

foreach ($country as $key => $value) {
	$c_total += $value['value'];
}

foreach ($method as $key => $value) {
	$m_total += $value['value'];
}

foreach ($premium as $key => $value) {
	$p_total += $value['value'];
}

foreach ($country as $key => $value) {
	$country[$key]['value'] = number_format($value['value']/$c_total*100,2);
}

foreach ($method as $key => $value) {
	if (preg_match("/quickplay/", $value['label'])) {
		if (!isset($id)) {
			$id = $key;
			$methods[$id]['label'] = 'quickplay';
			$methods[$id]['value'] = $value['value'];
		}
		else {
			$methods[$id]['label'] = 'quickplay';
			$methods[$id]['value'] += $value['value'];
		}
	}
	else {
		$methods[$key]['label'] = $value['label'];
		$methods[$key]['value'] = $value['value'];	
	}
}

foreach ($methods as $key => $value) {
	$methods[$key]['label'] = ConnMethod($value['label']);
	$methods[$key]['value'] = number_format($value['value']/$m_total*100,2);
}

foreach ($premium as $key => $value) {
	if ($value['label'] == '1') {
		$value['label'] = 'Premium';
	}
	else {
		$value['label'] = 'F2P';
	}
	$premium[$key]['label'] = $value['label'];
	if(!$p_total == 0)
		$premium[$key]['value'] = number_format($value['value']/$p_total*100,2);
	else
		$premium[$key]['value'] = 0;
}

$country = json_encode($country);
$methods = json_encode(array_values($methods));
$premium = json_encode($premium);
?>
					<div class="col-lg-4">
						<div class="panel panel-default">
							<div class="panel-heading">
								<i class="fa fa-bar-chart-o fa-fw"></i> Countries
							</div>
							<div class="panel-body">
								<div id="country"></div>
								<a class="btn btn-default btn-block"><input type="hidden" value="getlocation"/>View Details</a>

							</div><!-- /.panel-body -->
						</div><!-- /.panel -->
					</div><!-- /.col-lg-4 -->
					<div class="col-lg-4">
						<div class="panel panel-default">
							<div class="panel-heading">
								<i class="fa fa-bar-chart-o fa-fw"></i> Connection Method
							</div>
							<div class="panel-body">
								<div id="method"></div>
								<a class="btn btn-default btn-block"><input type="hidden" value="getconnections"/>View Details</a>
							</div><!-- /.panel-body -->
						</div><!-- /.panel -->
					</div><!-- /.col-lg-4 -->
					<div class="col-lg-4">
						<div class="panel panel-default">
							<div class="panel-heading">
								<i class="fa fa-bar-chart-o fa-fw"></i> Premium
							</div>
							<div class="panel-body">
								<div id="premium"></div>
								<a class="btn btn-default btn-block"><input type="hidden" value="getplayers"/>View Details</a>
							</div><!-- /.panel-body -->
						</div><!-- /.panel -->
					</div><!-- /.col-lg-4 -->
<script type="text/javascript">
	Morris.Donut({
	  element: 'country',
	  data: <?php echo $country; ?>,
	  formatter: function (y) { return y + "%" ;}
	});
</script>
<script type="text/javascript">
	Morris.Donut({
	  element: 'method',
	  data: <?php echo $methods; ?>,
	  formatter: function (y) { return y + "%" ;}
	});
</script>
<script type="text/javascript">
	Morris.Donut({
	  element: 'premium',
	  data: <?php echo $premium; ?>,
	  formatter: function (y) { return y + "%" ;}
	});
</script>
<script type="text/javascript">
	$(document).on("click",".btn-block",function(){
		$.ajax({
			type: "GET",
			url: "inc/"+ $(this).find("input").val()+".php",
			beforeSend: function(){
				$('#overlay').fadeIn("fast");
				$('#content').empty();
				$('.daterangepicker').detach();
			},
			success: function(msg){
				$('#content').delay(400).fadeIn("slow").html(msg);
				$('#overlay').delay(400).fadeOut( "slow" );
			}
		});
	});
</script>
