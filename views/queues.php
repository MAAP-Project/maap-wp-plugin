<?php
// exit if WordPress isn't loaded
!defined('ABSPATH') && exit;

$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_members = 'https://'. $maap_api . '/api/members';
$maap_api_orgs = 'https://'. $maap_api . '/api/organizations';
$maap_api_queues = 'https://'. $maap_api . '/api/admin/job-queues';

?>

 <link rel='stylesheet' id='jquery-datatables-css-css'  href='//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css?ver=6.0' media='all' />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
<script src='https://code.jquery.com/jquery-3.5.1.js'></script>
<script src='//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js?ver=6.0' id='jquery-datatables-js-js'></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
 
 <style>
select[name="maapusers_length"] {
  width: 55px;
}
.member-pill {
    margin-right: 5px;
}
.tooltip-inner {
    max-width: 100% !important;
}
.detail-font{
  font-size: 15px;
}
</style>

<div class="wrap">
    <h1>MAAP Job Queues</h1>

    <table id="maapusers" style="display: none">
    <thead>
	<th>Name</th>
	<th>Description</th>
	<th>Orgs</th>
	<th>Default?</th>
	<th>Public?</th>
	<th>Max Minutes</th>
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
    var org_tree = [];

    $('#maapusers').show();

        $('body').tooltip({
            selector: '.createdDiv'
        });


      

        $.ajax({url: "/wp-admin/admin-ajax.php?action=orgs_endpoint", success: function(result){
            org_tree = result.data;
            var org_dd = $("#selected_queue_org")
            var org_options = result.data.reduce((org_options, item) => 
                org_options += `<option value="${item.id}">${get_tabbed_org_name(item.depth, item.name)}</option>`, '');
            org_dd.append(org_options);   
            
            render_dt();
        }}); 

        function render_dt() {
            dt = $('#maapusers').DataTable({    
            ajax: {
                url: "/wp-admin/admin-ajax.php?action=queues_endpoint",
                cache:false,
            },
            columns: [
                {
                    render: function (data, type, row) {
                        return '<a class="text-primary queue-name-select" style="cursor: pointer">' + row.queue_name + '</a>'
                    }
                },
                {
                    data: 'queue_description'
                },
                {		
                    render: function (data, type, row) {
                        var result = '';

                        for(var i = 0; i < Math.min(row.orgs.length, 3); i++) {

                            var nameBadge = row.orgs[i].org_name;
                            var job_limit = 'Unlimited';
                            var members = ''

                            // if(row.orgs[i].default_job_limit_count && row.orgs[i].default_job_limit_hours) {
                            //     job_limit = row.orgs[i].default_job_limit_count + ' jobs per ' + 
                            //         (row.orgs[i].default_job_limit_hours == 1 ? 'hour' : row.orgs[i].default_job_limit_hours + ' hours');
                            // }

                            var orgDetails = nameBadge;// + 
                              //  "<br><nobr>Default job limit: " + job_limit;

                            var row_org = org_tree.find(({id}) => id == row.orgs[i].id)
                            if(row_org.members) {         
                                //orgDetails += "<br>Members";                   
                                members = row_org.members.reduce((members, item) => 
                                    members += `${item.first_name} ${item.last_name} (${item.username}<br />`, '');
                                
                                orgDetails += "<br>" + members;
                            }

                            if(result)
                                result += ", "

                            result += '<a href=# class="createdDiv" data-container="body" data-toggle="tooltip" data-placement="top" title="' + orgDetails + '" data-html="true">' + nameBadge + '</a>';
                        }

                        if(row.orgs.length > 3) {
                            var remaining = row.orgs.length - 3;
                            var tooltip = row.orgs.reduce((tooltip, item) => 
                                tooltip += `${item.name}<br />`, '');
                            result += ' and <b><a href=# class="createdDiv" data-container="body" data-toggle="tooltip" data-placement="top" title="' + tooltip + '" data-html="true">' + remaining + (remaining == 1 ? ' other' : ' others</a></b>');
                        }

                        return result;
                    },
                    width: '30%',
                    className: 'detail-font'
                },
                {
                    data: 'is_default',
                    render: function (data) {
                        return data ? 'Yes' : 'No'
                    }
                },
                {
                    data: 'guest_tier',
                    render: function (data) {
                        return data ? 'Yes' : 'No'
                    }
                },
                {
                    data: 'time_limit_minutes',
                    render: function (data) {
                        return data ? data : 'Unlimited'
                    }
                },
                {
                    render: function (data, type, row) {

                        var bs_class = 'primary';
                        if (row.status == 'Unassigned')
                            bs_class = 'warning';
                        else if (row.status == 'Online')
                            bs_class = 'success';
                        else if (row.status == 'Offline')
                            bs_class = 'daner';					
                        
                        return '<a href=# class="badge badge-' + bs_class + ' member-pill">' + row.status + '</a>'
                    }
                },
                {
                    data: 'creation_date',
                    render: function (data) {
                        if(data == null)
                            return '';
                        else {
                            var date = new Date(data);
                            var month = date.getMonth() + 1;
                            var day = date.getDate();
                            return date.getFullYear() + "-" + (month.toString().length > 1 ? month : "0" + month) + "-" + (day.toString().length > 1 ? day : "0" + day);
                        }
                    }
                }
            ],
            pageLength: 25
        }); 
        }
    
    $('#selected_queue_org').on('change', function() {
        add_item_option($("#selected_queue_org option:selected").text().trim(), this.value);
        clearForm();
    });

    function add_item_option(_name, _id) {

        var nameBadge = _name;

        nameBadge +='<button type="button" class="close" aria-label="Close" style="color: white; font-size: 12px; margin-left: 5px;">';
        nameBadge +='<span aria-hidden="true">×</span>';
        nameBadge +='</button>';

        $("#selected_item_options_display").append('<a href=# class="badge badge-secondary member-pill" data-orgid="' + _id + '">' + nameBadge + '</a>');
    }

    function get_tabbed_org_name(depth, org_name) {
        var result = '';

        for(var i = 2; i <= depth; i++) {
            result += '&nbsp; &nbsp; &nbsp;';
        }
        return result += org_name;
    }   

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



    $('#orgModal').on('shown.bs.modal', function (e) {
        clearForm(true);

        if(edit_mode) {
            $('#roleModalHeaderLabel').text(selected_item_edit.queue_name);
            $("#btnSaveOrg").text('Save Changes');

            if(selected_item_edit.status == 'Unassigned') {
                $('#btnDeleteOrg').hide();
                $('#roleModalHeaderLabel').text(selected_item_edit.queue_name);
                $("#btnSaveOrg").text('Assign Queue');
            }
            else {
                $('#btnDeleteOrg').show();
                $('#roleModalHeaderLabel').text(selected_item_edit.queue_name);
                $("#btnSaveOrg").text('Save Changes');
            }

            if(selected_item_edit.time_limit_minutes)
                $('#jobTimeLimit').val(selected_item_edit.time_limit_minutes);

            $('#queue_description_tb').val(selected_item_edit.queue_description);
            $('#customSwitch1').prop("checked", selected_item_edit.guest_tier);
            $('#defaultQueue').prop("checked", selected_item_edit.is_default);

            for(var i = 0; i < selected_item_edit.orgs.length; i++) {
                var _mem = selected_item_edit.orgs[i];
                add_item_option(_mem.org_name, _mem.id);
            }
        } else {
            $('#btnDeleteOrg').hide();
            $('#roleModalHeaderLabel').text('Assign Job Queue');
            $("#btnSaveOrg").text('Save Job Queue');
        }
    })

    $('body').on('click', 'a.member-pill', function() {
        $(this).remove();
    });

    $('#orgModal').on('hidden.bs.modal', function (e) {
        clearForm(true);
        edit_mode = false;
    })

    function clearForm(all_fields) {
        $('#selected_queue_org').prop('selectedIndex',0);
        $('.add-item-options').hide();

        if(all_fields) {
            $('#queue_description_tb').val('');
            $('#customSwitch1').prop("checked", false);
            $('#defaultQueue').prop("checked", false);
            $('#selected_item_options_display').empty();
            $('#jobTimeLimit').val('');
        }
    }  

    //Save org changes
    $("#orgModal").on("click",".btn-primary", function(){

        var isPublic =  $('#customSwitch1').prop("checked");
        var defaultQueue =  $('#defaultQueue').prop("checked");
        var jobTimeLimit = $('#jobTimeLimit').val();

        var orgsToAdd = [];
        $('#selected_item_options_display').children('a').each(function () {
            orgsToAdd.push({
                org_id: $(this).data("orgid")
            });
        });

        var new_assignment = selected_item_edit.status == 'Unassigned';

        $.ajax({
            url: '<?php echo $maap_api_queues ;?>' + (new_assignment ? '' : '/' + selected_item_edit.id),
            type: new_assignment ? 'post' : 'put',
            crossDomain: true,
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({ 
                "queue_name":  selected_item_edit.queue_name, 
                "queue_description": $('#queue_description_tb').val(),
                "guest_tier":  isPublic,
                "is_default": defaultQueue,
                "time_limit_minutes":  jobTimeLimit ? jobTimeLimit : 0,
                "orgs": orgsToAdd
            }),
            headers: { 
                'cpticket': '<?php echo $pgt ;?>' 
            },
                success: function (data) {
                $('#maapusers').DataTable().ajax.reload();
                $('#orgModal').modal('hide');
                clearForm();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log('error', XMLHttpRequest, textStatus, errorThrown);
            }	  
        });
    });

    //Delete org
    $("#orgModal").on("click",".btn-danger", function(){
        if (confirm("Unassign " + selected_item_edit.queue_name + "?")) {
            $.ajax({
            url: '<?php echo $maap_api_queues ;?>' + '/' + selected_item_edit.id,
            type: 'delete',
            crossDomain: true,
            dataType: 'json',
            contentType: 'application/json',
            headers: { 
                'cpticket': '<?php echo $pgt ;?>' 
            },
                success: function (data) {
                $('#maapusers').DataTable().ajax.reload();
                $('#orgModal').modal('hide');
                clearForm();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log('error', XMLHttpRequest, textStatus, errorThrown);
            }	  
        });
        }
    });

    // Edit a queue
    $('#maapusers').on('click', '.queue-name-select', function (e) {
        e.preventDefault();
        var data = dt.row($(this).parents('tr')).data();

        selected_item_edit = data;
        edit_mode = true;
        $('#orgModal').modal('show');
    } );

}
});

</script>
</div>

    <!-- Modal -->
    <div class="modal fade" id="orgModal" tabindex="-1" role="dialog" aria-labelledby="orgModalLabel" style="margin-top: 30px">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalHeaderLabel">Edit Job Queue</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="queue_description_tb">Description:</label> 
                    <input type="text" class="form-control" id="queue_description_tb" required> 
                </div>   
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="defaultQueue" style="margin-top: 4px;margin-left: -24px;z-index: 999;">
                    <label class="custom-control-label" for="defaultQueue">Default queue?</label>            
                    <small class="text-muted">The queue to use as a default when no queues are specified.</small> 
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="customSwitch1" style="margin-top: 4px;margin-left: -24px;z-index: 999;">
                    <label class="custom-control-label" for="customSwitch1">Public queue?</label>            
                    <small class="text-muted">Public queues are accessible to guest users who do not belong to organizations.</small> 
                </div>              
                <div class="form-group">
                    <label for="jobTimeLimit">Job time limit (in minutes):</label>
                    <div class="input-group mb-3">
                    <input type="number" class="form-control" placeholder="Leave blank for unlimited time" aria-label="Leave blank for unlimited time" aria-describedby="basic-addon2" id="jobTimeLimit">
                    <div class="input-group-append">
                        <span class="input-group-text" id="basic-addon2">minutes</span>
                    </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="selected_queue_org" id="queue_orgs">Organizations:</label> 
                    <select class="form-control" id="selected_queue_org">
                        <option value="" selected disabled>Select org</option>
                    </select>
                </div>
                <div class="clear">
                    <div class="form-group" style="margin-top: 20px;" id="selected_item_options_display">
                    </div>
                </div>
            </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger mr-auto" id="btnDeleteOrg">Unassign</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="btnSaveOrg">Assign Queue</button>
        </div>
        </div>
    </div>
    </div>