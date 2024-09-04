<?php
// exit if WordPress isn't loaded
!defined('ABSPATH') && exit;

$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_preapproved = 'https://'. $maap_api . '/api/admin/pre-approved';

?>


 <link rel='stylesheet' id='jquery-datatables-css-css'  href='//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css?ver=6.0' media='all' />
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
 <script src='https://code.jquery.com/jquery-3.5.1.js'></script>
 <script src='//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js?ver=6.0' id='jquery-datatables-js-js'></script>
 <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
 <style>
select[name="maapusers_length"] {
  width: 55px;
}
</style>

 <div class="wrap" style="width: 800px">
    <h1>MAAP Pre-Approved Emails</h1>
	<a class="editor-create" style="cursor: pointer">+ Add new email</a>
 <table id="maapusers" style="display: none;">
    <thead>
		<th>Email</th>
        <th>Created</th>
		<th></th>
    </thead>
    <tbody></tbody>
</table>

<script>

var pgt = '<?php echo $pgt ;?>';
var selected = '';

jQuery(document).ready(function($){

if(pgt) {

    $('#maapusers').show();

    var dt = $('#maapusers').DataTable({    
        ajax: {
            url: "/wp-admin/admin-ajax.php?action=preapproved_endpoint",
            cache:false,
        },
        columns: [                 
			{ 
				data: 'email' 
			},
			{ 
				data: 'creation_date', render: function (data) {
					var date = new Date(data);
					var month = date.getMonth() + 1;
					return date.getFullYear() + "-" + (month.toString().length > 1 ? month : "0" + month) + "-" + date.getDate() ;
			    }
			},
            {
                data: null,
                className: "dt-center editor-delete",
                defaultContent: '<span class="dashicons dashicons-trash"></span>',
                orderable: false
            }
        ],
        pageLength: 25
    }); 

	// New record
	$('a.editor-create').on('click', function (e) {
        e.preventDefault();
		$('#email1').val('');
		$('#myAddModal').modal('show');
    } );
 
    // Delete a record
    $('#maapusers').on('click', 'td.editor-delete', function (e) {
        e.preventDefault();

		selected = $(this).closest('tr')[0].firstChild.innerText;

		$('#modalText').text("Are you sure you want to delete " + selected + "?");
		$('#myModal').modal('show');
    } );

	$("#myModal").on("click",".btn-primary", function(){

		$.ajax({
			url: '<?php echo $maap_api_preapproved ;?>/' + selected,
		type: 'delete',
		crossDomain: true,
		dataType: 'json',
		contentType: 'application/json',
		headers: { 
			'cpticket': '<?php echo $pgt ;?>' 
		},
		success: function (data) {
			$('#maapusers').DataTable().ajax.reload();
			$('#myModal').modal('hide');
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log('error', XMLHttpRequest, textStatus, errorThrown);
		}	  
		});
	});

	$("#myAddModal").on("click",".btn-primary", function(){
		
		if(! $('#email1').val()) {
			alert('An email or wildcard is required.');
			return;
		}
		$.ajax({
			url: '<?php echo $maap_api_preapproved ;?>',
			type: 'post',
			crossDomain: true,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify({ "email":  $('#email1').val()}),
			headers: { 
				'cpticket': '<?php echo $pgt ;?>' 
			},
				success: function (data) {
				$('#maapusers').DataTable().ajax.reload();
				$('#myAddModal').modal('hide');
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

<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
  <div class="modal-dialog">
  
	<!-- Modal content-->
	<div class="modal-content">
	  <div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&times;</button>
		<h4 class="modal-title">Delete Email</h4>
	  </div>
	  <div class="modal-body">
		<p id="modalText"></p>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		   <button type="button" class="btn btn-primary">OK</button>
	  </div>
	</div>
	
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="myAddModal" role="dialog">
  <div class="modal-dialog">
  
	<!-- Modal content-->
	<div class="modal-content">
	  <div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&times;</button>
		<h4 class="modal-title">Add Email</h4>
	  </div>
	  <div class="modal-body">
		<p>
			Emails and wildcards are supported for starting email characters. Examples: *@maap-project.org, jane.doe@maap-project.org
		</p>
		<div class="form-group">
            <label for="email1">Email</label>
            <input type="email" class="form-control" id="email1" aria-describedby="emailHelp" placeholder="Enter email">
          </div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		   <button type="button" class="btn btn-primary">OK</button>
	  </div>
	</div>
	
  </div>
</div>
