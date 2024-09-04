<?php
// exit if WordPress isn't loaded
!defined('ABSPATH') && exit;

$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_members = 'https://'. $maap_api . '/api/members';

?>

<link rel='stylesheet' id='jquery-datatables-css-css'  href='//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css?ver=6.0' media='all' />
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
 <script src='https://code.jquery.com/jquery-3.5.1.js'></script>
 <script src='//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js?ver=6.0' id='jquery-datatables-js-js'></script>
 <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
 
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
	<th>Role</th>
	<th>Status</th>
        <th>Created</th>
    </thead>
    <tbody></tbody>
</table>

<script>

var pgt = '<?php echo $pgt ;?>';

jQuery(document).ready(function($){

if(pgt) {
	var dt = null;
    var selected = '';

    $('#maapusers').show();

    	dt = $('#maapusers').DataTable({    
        ajax: {
            url: "/wp-admin/admin-ajax.php?action=users_endpoint",
            cache:false,
        },
        columns: [
			{
				render: function (data, type, row) {
					return '<a class="text-primary org-name-select" style="cursor: pointer">' + row.first_name + ' ' + row.last_name + '</a>'
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
					return '<a class="memberrole" style="cursor: pointer">' + row.role_name + '</a>';
				}
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

	$('#maapusers tbody').on('click', '.membersts', function () {
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

	// Delete a record
	$('#maapusers').on('click', '.org-name-select', function (e) {
		e.preventDefault();
		var data = dt.row($(this).parents('tr')).data();

		$("#role").val(data.role_id);
		$("#customSwitch1").prop('checked', data.status == 'active');

		selected = data.username;

		$('#roleModalHeaderLabel').text(selected);
		$('#roleModal').modal('show');
	} );

	//Update a role
	$("#roleModal").on("click",".btn-primary", function(){
		
		$.ajax({
			url: '<?php echo $maap_api_members ;?>/' + selected,
			type: 'put',
			crossDomain: true,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify({ "role_id":  $('#role').val()}),
			headers: { 
				'cpticket': '<?php echo $pgt ;?>' 
			},
				success: function (data) {
				$('#maapusers').DataTable().ajax.reload();
				$('#roleModal').modal('hide');
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				console.log('error', XMLHttpRequest, textStatus, errorThrown);
			}	  
		});
	});

}
});

</script>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="roleModal" role="dialog">
  <div class="modal-dialog">
  
	<!-- Modal content-->
	<div class="modal-content">
	  <div class="modal-header">
        <h5 class="modal-title" id="roleModalHeaderLabel">Edit User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
	  <div class="modal-body">
	  <div class="form-group form-check">
			<input type="checkbox" class="form-check-input" id="customSwitch1" style="margin-top: 4px;margin-left: -24px;z-index: 999;">
			<label class="custom-control-label" for="customSwitch1">Active</label> 
			<br>
			<small class="text-muted">Only active users can access the MAAP ADE.</small>
		</div>
		<div class="form-group">
			<label for="roleModalInput">Member role:</label>
			<select name="roles" id="role" class="form-control">
				<option value="1">Guest</option>
				<option value="2">Member</option>
				<option value="3">Admin</option>
			</select>
			<small class="text-muted">A guest role has limited access to MAAP resources. Members have general access and can be assigned to organizations for fine-grained permissions over job queues.</small>
		</div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		   <button type="button" class="btn btn-primary">OK</button>
	  </div>
	</div>
	
  </div>
</div>