<?php include 'db_connect.php' ?>
<?php session_start(); ?>
<?php 
// extract($_POST);
if(isset($id)){
	$qry = $conn->query("SELECT * FROM payments where id=".$_POST['id']);
	foreach($qry->fetch_array() as $k => $val){
		$$k = $val;
	}
}
$loan = $conn->query("SELECT l.*,concat(b.firstname,' ',b.lastname)as name, b.contact_no, b.address from loan_list l inner join borrowers b on b.id = l.borrower_id where l.id = ".$_POST['loan_id']);
foreach($loan->fetch_array() as $k => $v){
	$meta[$k] = $v;
}
$type_arr = $conn->query("SELECT * FROM loan_types where id = '".$meta['loan_type_id']."' ")->fetch_array();

$plan_arr = $conn->query("SELECT *,concat(days,' day/s [ ',interest_percentage,'%, ',penalty_rate,' ]') as plan FROM loan_plan where id  = '".$meta['plan_id']."' ")->fetch_array();
$total = $meta['amount'] + ($meta['amount'] * ($plan_arr['interest_percentage']/100));	//added
$total_daily = ($meta['amount'] + ($meta['amount'] * ($plan_arr['interest_percentage']/100))) / $plan_arr['days'];
$daily = $conn->query("SELECT * from payments where date(date_created)=date(CURRENT_DATE) and loan_id =".$_POST['loan_id']);
$daily_paid = 0;
while($d = $daily->fetch_assoc()){
	$daily_paid += ($d['amount'] - $d['penalty_amount']);
}
$remaining_daily = $total_daily - $daily_paid;
$penalty = $total_daily * ($plan_arr['penalty_rate']/100);
$payments = $conn->query("SELECT * from payments where loan_id =".$_POST['loan_id']);
$paid = $payments->num_rows;
$offset = $paid > 0 ? " offset $paid ": "";
	$next = $conn->query("SELECT * FROM loan_schedules where loan_id = '".$_POST['loan_id']."'  order by date(date_due) asc limit 1 $offset ")->fetch_assoc()['date_due'];
$sum_paid = 0;
while($p = $payments->fetch_assoc()){
	$sum_paid += ($p['amount'] - $p['penalty_amount']);
}
$remaining = $total-$sum_paid;
?>
<div class="col-lg-12">
<hr>
<div class="row">
	<div class="col-md-5">
		<div class="form-group">
			<label for="">Payee</label>
			<input name="payee" class="form-control" required="" value="<?php echo isset($payee) ? $payee : (isset($meta['name']) ? $meta['name'] : '') ?>">
		</div>
	</div>
	
</div>
<hr>
<div class="row">
	<div class="col-md-5">
		<p><small>Total Remaining Amount: <b><?php echo number_format($remaining,2) ?></b></small></p>
		<p><small>Daily Amount: <b><?php echo number_format($total_daily,2) ?></b></small></p>
		<p><small>Daily Remaining Amount: <b><?php echo number_format($remaining_daily,2) ?></b></small></p>
		<p><small>Penalty: <b><?php echo $add = (date('Ymd',strtotime($next)) < date("Ymd") ) ?  $penalty : 0; ?></b></small></p>
		<p><small>Payable Amount: <b><?php echo number_format($remaining_daily + $add,2) ?></b></small></p>
	</div>
	<div class="col-md-5">
		<div class="form-group">
			<label for="">Amount</label>
			<input type="number" name="amount" step="any" min="0" max="<?php echo number_format($remaining_daily + $add,2) ?>" class="form-control text-right" required="" value="<?php echo isset($amount) ? $amount : '' ?>">
			<input type="hidden" name="penalty_amount" value="<?php echo $add ?>">
			<input type="hidden" name="loan_id" value="<?php echo $_POST['loan_id'] ?>">
			<input type="hidden" name="overdue" value="<?php echo $add > 0 ? 1 : 0 ?>">
			<input type="hidden" name="collect_by" value="<?php echo $_SESSION['login_name'] ?>">
		</div>
	</div>
</div>
</div>