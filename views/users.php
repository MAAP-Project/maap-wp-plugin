<?php
// exit if WordPress isn't loaded
!defined('ABSPATH') && exit;

$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_members = 'https://'. $maap_api . '/api/members';

?>


 <link rel='stylesheet' id='jquery-datatables-css-css'  href='//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css?ver=6.0' media='all' />
 <script src='//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js?ver=6.0' id='jquery-datatables-js-js'></script>
 <style>
select[name="maapusers_length"] {
  width: 55px;
}
</style>

<div class="wrap">
    <h1>MAAP Users</h1>
    <table id="maapusers" style="display: none">
    <thead>
	<th>Name</th>
	<th>Username</th>
	<th>Email</th>
	<th>Status</th>
        <th>Created</th>
    </thead>
    <tbody></tbody>
</table>

<script>

var pgt = '<?php echo $pgt ;?>';

jQuery(document).ready(function($){

if(pgt) {

    $('#maapusers').show();

    	var dt = $('#maapusers').DataTable({    
        ajax: {
            url: "/wp-admin/admin-ajax.php?action=users_endpoint",
            cache:false,
        },
        columns: [
			{
				render: function (data, type, row) {
					return row.first_name + ' ' + row.last_name;
				}
			},
			{
				data: 'username'
			},
			{
				data: 'email'
			},
			{
				render: function (data, type, row) {
					return '<a class="membersts" style="cursor: pointer">' + row.status + '</a>';
				}
			},
			{
				data: 'creation_date',
				render: function (data) {
					var date = new Date(data);
					var month = date.getMonth() + 1;
					var day = date.getDate();
					return date.getFullYear() + "-" + (month.toString().length > 1 ? month : "0" + month) + "-" + (day.toString().length > 1 ? day : "0" + day);
				}
			},
		],
		pageLength: 25
	}); 

    $('#maapusers tbody').on('click', 'a', function () {
	    var data = dt.row($(this).parents('tr')).data();
	    var new_status = (data.status == 'active' ? 'suspended' : 'active');
	    if (confirm("Change " + data.username + "'s status to " + new_status + "?")) {

		var statusInfo = {status}
	   	$.ajax({
    		  url: '<?php echo $maap_api_members ;?>/' + data.username + '/status',
		  type: 'post',
		  crossDomain: true,
		  dataType: 'json',
		  contentType: 'application/json',
    		  data: JSON.stringify({ "status": new_status}),
		  headers: { 
			'cpticket': '<?php echo $pgt ;?>' 
		  },
    		  success: function (data) {
			  console.info(data);
			  $('#maapusers').DataTable().ajax.reload();
		  },
		  error: function(XMLHttpRequest, textStatus, errorThrown) {
			 console.log('error', XMLHttpRequest, textStatus, errorThrown);
		  }	  
		});
	    }
    });
}
});

</script>
</div>