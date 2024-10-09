<?php
// exit if WordPress isn't loaded
!defined('ABSPATH') && exit;

$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_orgs = 'https://'. $maap_api . '/api/organizations';

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
    <div style="padding-bottom: 70px;">
        <div style="float: left"> <h1>MAAP Organizations</h1></div>

        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#orgModal" style="float:right; margin-top: 10px">
            Add Organization
        </button>
    </div>

    <table id="maapusers" style="display: none">
        <thead>
        <th>Name</th>
        <th>Job Queues</th>
        <th>Default Job Limit</th>
        <th>Members</th>
        <th>Created</th>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>

var pgt = '<?php echo $pgt ;?>';

jQuery(document).ready(function($){

$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})

if(pgt) {

    $('#maapusers').show();

        $('body').tooltip({
            selector: '.createdDiv'
        });

        var org_tree = [];
        var users = [];

        

        //MODAL BEGIN

        var edit_mode = false;
        var selected_item_edit;

        $("body").on('change',".item_search_cls", function(){
            if($("#item_search").val()) {
                $('.add-item-options').show();
            }
        });

        $('body').on('click', 'a.member-pill', function() {
            $(this).remove();
        });

        $("#btnAddMember").click(function(){
            if ($('#selected_item_options_display:contains("' + $("#item_search option:selected").text() + '")').length == 0)  {
                for(var i = 0; i < $("#item_search").val().length; i++) {
                    var sel_user = users.find(({id}) => id == $("#item_search").val()[i]);
                    add_item_option(sel_user.first_name + ' ' + sel_user.last_name + ' (' + sel_user.username + ')', sel_user.id, $("#maintainerCheck").is(':checked'));
                }
            }

            clearForm();
        }); 

        function add_item_option(_name, _id, _is_maintainer) {
            var nameBadge = _name;

            if(_is_maintainer)
                nameBadge += ' <span class="badge badge-light">OM</span>';

            nameBadge +='<button type="button" class="close" aria-label="Close" style="color: white; font-size: 12px; margin-left: 5px;">';
            nameBadge +='<span aria-hidden="true">×</span>';
            nameBadge +='</button>';

            $("#selected_item_options_display").append('<a href=# class="badge badge-secondary member-pill" data-memberid="' + _id + '" data-orgmaintainer="' + _is_maintainer + '">' + nameBadge + '</a>');
        }

        var member_dropdown='<select id="item_search" name="item_search" class="form-control selectpicker item_search_cls" data-live-search="true" data-width="100%" title="Search by name or username" multiple><option value="" disabled></option></select>';

        //MODAL END



        $("#org_membership").after(member_dropdown);

        $.ajax({url: "/wp-admin/admin-ajax.php?action=users_endpoint", success: function(result){
            dd = $("#item_search");
            users = result.data;
            var member_options = result.data.filter(m => m.role_id > 1 && m.status == 'active').reduce((member_options, item) => 
                member_options += `<option value="${item.id}">${item.first_name + ' ' + item.last_name + ' (' + item.username + ')'}</option>`, '');

            dd.append(member_options);
            $('.selectpicker').selectpicker();
            $('.selectpicker').selectpicker('refresh');
        }});    

        $.ajax({url: "/wp-admin/admin-ajax.php?action=orgs_endpoint", success: function(result){
            org_tree = result.data;
            var org_dd = $("#parentOrg")

            if(org_tree) {
                org_dd.show();
                var org_options = result.data.reduce((org_options, item) => 
                    org_options += `<option value="${item.id}">${get_tabbed_org_name(item.depth, item.name)}</option>`, '');
                org_dd.append(org_options);
            }
            else
                $("#parent_org_container").hide();
        }});    

        function get_tabbed_org_name(depth, org_name) {
            var result = '';

            for(var i = 2; i <= depth; i++) {
                result += '&nbsp; &nbsp; &nbsp;';
            }
            return result += org_name;
        }

    	var dt = $('#maapusers').DataTable({    
        order: [],
        initComplete: function (settings, json) {
            console.log('init complete', json);
        },
        drawCallback: function (settings) {
            var api = this.api();
    
            // Output the data for the visible rows to the browser's console
            console.log('yy', api.rows().data());
        },
        ajax: {
            url: "/wp-admin/admin-ajax.php?action=orgs_endpoint",
            cache:false,
        },
        columns: [
			{
                data: 'name',
				render: function (data, type, row) {
					return get_tabbed_org_name(row.depth, '<a class="text-primary org-name-select" style="cursor: pointer">' + row.name + '</a>');
				}
			},
			{
				render: function (data, type, row) {
                    var result = '';

                    for(var i = 0; i < Math.min(row.job_queues.length, 3); i++) {
                        if(result)
                            result += ", "

                        result += row.job_queues[i].queue_name.replace("maap-dps-", "");
                    }

                    if(row.job_queues.length > 3) {
                        var remaining = row.job_queues.length - 3;
                        var tooltip = row.job_queues.reduce((tooltip, item) => 
                        tooltip += `${item.queue_name}<br />`, '');
                        result += ' and <b><a href=# class="createdDiv" data-container="body" data-toggle="tooltip" data-placement="top" title="' + tooltip + '" data-html="true">' + remaining + ' more</a></b>';
                    }

                    return result;
				},
                width: '30%',
                className: 'detail-font'
			},
			{
				render: function (data, type, row) {
                    var result = 'Unlimited';

                    if(row.default_job_limit_count && row.default_job_limit_hours) {
                        result = row.default_job_limit_count + ' jobs per ' + 
                            (row.default_job_limit_hours == 1 ? 'hour' : row.default_job_limit_hours + ' hours');
                    }

					return result;
				}
			},
			{				
				render: function (data, type, row) {
                    var result = '';

                    for(var i = 0; i < Math.min(row.members.length, 3); i++) {

                        var nameBadge = row.members[i].first_name + ' ' + row.members[i].last_name
                        var memberDetails = nameBadge + 
                            "<br>Username: " + row.members[i].username + 
                            "<br><nobr>Email: " + row.members[i].email + 
                            "</nobr><br>Maintainer? " + (row.members[i].maintainer ? "Yes" : "No");

                        // if(row.members[i].maintainer)
                        //     nameBadge += ' <b>(OM)</b>';

                        if(result)
                            result += ", "

                        result += '<a href=# class="createdDiv" data-container="body" data-toggle="tooltip" data-placement="top" title="' + memberDetails + '" data-html="true">' + nameBadge + '</a>';
                    }

                    if(row.members.length > 3) {
                        var remaining = row.members.length - 3;
                        var tooltip = row.members.reduce((tooltip, item) => 
                        tooltip += `${item.first_name} ${item.last_name} (${item.username})<br />`, '');
                        result += ' and <b><a href=# class="createdDiv" data-container="body" data-toggle="tooltip" data-placement="top" title="' + tooltip + '" data-html="true">' + remaining + (remaining == 1 ? ' other' : ' others</a></b>')
                    }

                    return result;
				},
                width: '30%',
                className: 'detail-font'
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

    $('#orgModal').on('shown.bs.modal', function (e) {
        clearForm(true);

        if(edit_mode) {
            $('#btnDeleteOrg').show();
            $('#roleModalHeaderLabel').text(selected_item_edit.name);
            $("#btnSaveOrg").text('Save Changes');

            if(selected_item_edit.parent_org_id)
                $('#parentOrg').val(selected_item_edit.parent_org_id);

            $('#org_name_tb').val(selected_item_edit.name);

            if(selected_item_edit.default_job_limit_count)
                $('#defaultJobLimitCount').val(selected_item_edit.default_job_limit_count);

            for(var i = 0; i < selected_item_edit.members.length; i++) {
                var _mem = selected_item_edit.members[i];
                var _mem_name_display = _mem.first_name + ' ' + _mem.last_name + ' (' + _mem.username + ')';
                add_item_option(_mem_name_display, _mem.id, _mem.maintainer);
            }
        } else {
            $('#btnDeleteOrg').hide();
            $('#roleModalHeaderLabel').text('Add organization');
            $("#btnSaveOrg").text('Create organization');
        }
    })

    $('#orgModal').on('hidden.bs.modal', function (e) {
        clearForm(true);
        edit_mode = false;
    })

    function clearForm(all_fields) {
        $('#item_search').prop('selectedIndex',0);
        $("#item_search").selectpicker("refresh");
        $('.add-item-options').hide();
        $('#maintainerCheck').prop("checked", false);

        if(all_fields) {
            $('#parentOrg').prop('selectedIndex',0);
            $('#org_name_tb').val('');
            $('#defaultJobLimitCount').val('');
            $('#selected_item_options_display').empty();
        }
    }

//Save org changes
$("#orgModal").on("click",".btn-primary", function(){

    var defaultJobLimitCount = $('#defaultJobLimitCount').val();
    var defaultJobLimitHours = $('#defaultJobLimitHours').val();

    var membersToAdd = [];
    $('#selected_item_options_display').children('a').each(function () {
        membersToAdd.push({
            member_id: $(this).data("memberid"), 
            maintainer: $(this).data("orgmaintainer")
        });
    });

    var parent_org_id = $('#parentOrg').val();

    //Persist the original parent id if editing a root node
    if(edit_mode && !parent_org_id)
        parent_org_id = selected_item_edit.parent_org_id;

    $.ajax({
        url: '<?php echo $maap_api_orgs ;?>' + (edit_mode ? '/' + selected_item_edit.id : ''),
        type: edit_mode ? 'put' : 'post',
        crossDomain: true,
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({ 
            "name":  $('#org_name_tb').val(), 
            "parent_org_id": parent_org_id,
            "default_job_limit_count":  defaultJobLimitCount ? defaultJobLimitCount : 0,
            "default_job_limit_hours":  1,
            "members": membersToAdd
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
    if (confirm("Delete org " + selected_item_edit.name + "?")) {
        $.ajax({
        url: '<?php echo $maap_api_orgs ;?>' + '/' + selected_item_edit.id,
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

    // Edit an org
    $('#maapusers').on('click', '.org-name-select', function (e) {
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
                <h5 class="modal-title" id="roleModalHeaderLabel">Add Organization</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group" id="parent_org_container">
                    <label for="parentOrg">Parent org:</label>
                    <select class="form-control" id="parentOrg">
                        <option value="" selected disabled>Select org</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="defaultJobLimitCount">Organization name:</label> 
                    <input type="text" class="form-control" id="org_name_tb" required> 
                </div>                
                <div class="form-group">
                    <label for="defaultJobLimitCount">Default job limit:</label>
                    <div class="input-group mb-3">
                    <input type="number" class="form-control" placeholder="Leave blank for unlimited jobs" aria-label="Leave blank for unlimited jobs" aria-describedby="basic-addon2" id="defaultJobLimitCount">
                    <div class="input-group-append">
                        <span class="input-group-text" id="basic-addon2">jobs per hour</span>
                    </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="item_search" id="org_membership">Members:</label> 
                    <div class="form-group" style="margin-top: 20px;" id="selected_org_members">
                    </div>
                </div>
                <div>
                    <div class="form-group form-check add-item-options" style="float: left">
                        <input type="checkbox" class="form-check-input" id="maintainerCheck" name="maintainerCheck" style="margin-top: 6px;">
                        <label class="form-check-label" for="maintainerCheck" style="margin-left: 30px;">Org maintainer</label>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm add-item-options" style="float: right" id="btnAddMember">Add Member(s)</button>
                </div>
                <div class="clear">
                    <div class="form-group" style="margin-top: 20px;" id="selected_item_options_display">
                    </div>
                </div>
            </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger mr-auto" id="btnDeleteOrg">Delete</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="btnSaveOrg">Create organization</button>
        </div>
        </div>
    </div>
    </div>