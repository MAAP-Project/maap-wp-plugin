<?php
// exit if WordPress isn't loaded
!defined('ABSPATH') && exit;

$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_s3access = 'https://'. $maap_api . '/api/admin/s3-access';

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
        <div style="float: left"> <h1>MAAP S3 Access</h1></div>

        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#s3Modal" style="float:right; margin-top: 10px">
            Add S3 Access
        </button>
    </div>

    <table id="maapusers" style="display: none">
        <thead>
        <th>Organization</th>
        <th>Bucket Name</th>
        <th>Bucket Prefix</th>
        <th>Read Only?</th>
        <th>Created</th>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>

var pgt = '<?php echo $pgt ;?>';

jQuery(document).ready(function($){

if(pgt) {

    $('#maapusers').show();

        var org_tree = [];
        var edit_mode = false;
        var selected_item_edit;

        $.ajax({url: "/wp-admin/admin-ajax.php?action=orgs_endpoint", success: function(result){
            org_tree = result.data;
            var org_dd = $("#s3_org_id");
            var org_options = result.data.reduce((org_options, item) =>
                org_options += `<option value="${item.id}">${item.name}</option>`, '');
            org_dd.append(org_options);

            render_dt();
        }});

        function render_dt() {
            var dt = $('#maapusers').DataTable({
            ajax: {
                url: "/wp-admin/admin-ajax.php?action=s3access_endpoint",
                cache:false,
            },
            columns: [
                {
                    render: function (data, type, row) {
                        var org_name = row.org_name || '';
                        if(!org_name && row.org_id) {
                            var org = org_tree.find(({id}) => id == row.org_id);
                            if(org) org_name = org.name;
                        }
                        return '<a class="text-primary s3-entry-select" style="cursor: pointer">' + org_name + '</a>';
                    }
                },
                {
                    data: 'bucket_name'
                },
                {
                    data: 'bucket_prefix',
                    render: function (data) {
                        return data ? data : '';
                    }
                },
                {
                    data: 'readonly',
                    render: function (data) {
                        return data ? 'Yes' : 'No';
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

        // Edit an entry
        $('#maapusers').on('click', '.s3-entry-select', function (e) {
            e.preventDefault();
            var data = dt.row($(this).parents('tr')).data();

            selected_item_edit = data;
            edit_mode = true;
            $('#s3Modal').modal('show');
        });
        }

    $('#s3Modal').on('shown.bs.modal', function (e) {
        clearForm();

        if(edit_mode) {
            $('#btnDeleteS3').show();
            $('#s3ModalHeaderLabel').text('Edit S3 Access');
            $("#btnSaveS3").text('Save Changes');

            if(selected_item_edit.org_id)
                $('#s3_org_id').val(selected_item_edit.org_id);

            $('#s3_bucket_name').val(selected_item_edit.bucket_name);
            $('#s3_bucket_prefix').val(selected_item_edit.bucket_prefix || '');
            $('#s3_readonly').prop("checked", selected_item_edit.readonly);
        } else {
            $('#btnDeleteS3').hide();
            $('#s3ModalHeaderLabel').text('Add S3 Access');
            $("#btnSaveS3").text('Create S3 Access');
        }
    })

    $('#s3Modal').on('hidden.bs.modal', function (e) {
        clearForm();
        edit_mode = false;
    })

    function clearForm() {
        $('#s3_org_id').prop('selectedIndex', 0);
        $('#s3_bucket_name').val('');
        $('#s3_bucket_prefix').val('');
        $('#s3_readonly').prop("checked", false);
    }

    // Save s3 access entry
    $("#s3Modal").on("click",".btn-primary", function(){

        if(!$('#s3_org_id').val()) {
            alert('An organization is required.');
            return;
        }
        if(!$('#s3_bucket_name').val()) {
            alert('A bucket name is required.');
            return;
        }

        $.ajax({
            url: '<?php echo $maap_api_s3access ;?>' + (edit_mode ? '/' + selected_item_edit.id : ''),
            type: edit_mode ? 'put' : 'post',
            crossDomain: true,
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                "org_id": parseInt($('#s3_org_id').val()),
                "bucket_name": $('#s3_bucket_name').val(),
                "bucket_prefix": $('#s3_bucket_prefix').val(),
                "readonly": $('#s3_readonly').prop("checked")
            }),
            headers: {
                'cpticket': '<?php echo $pgt ;?>'
            },
                success: function (data) {
                $('#maapusers').DataTable().ajax.reload();
                $('#s3Modal').modal('hide');
                clearForm();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log('error', XMLHttpRequest, textStatus, errorThrown);
            }
        });
    });

    // Delete s3 access entry
    $("#s3Modal").on("click",".btn-danger", function(){
        if (confirm("Delete this S3 access entry?")) {
            $.ajax({
            url: '<?php echo $maap_api_s3access ;?>' + '/' + selected_item_edit.id,
            type: 'delete',
            crossDomain: true,
            dataType: 'json',
            contentType: 'application/json',
            headers: {
                'cpticket': '<?php echo $pgt ;?>'
            },
                success: function (data) {
                $('#maapusers').DataTable().ajax.reload();
                $('#s3Modal').modal('hide');
                clearForm();
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

    <!-- Modal -->
    <div class="modal fade" id="s3Modal" tabindex="-1" role="dialog" aria-labelledby="s3ModalLabel" style="margin-top: 30px">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="s3ModalHeaderLabel">Add S3 Access</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="s3_org_id">Organization:</label>
                    <select class="form-control" id="s3_org_id">
                        <option value="" selected disabled>Select organization</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="s3_bucket_name">Bucket name:</label>
                    <input type="text" class="form-control" id="s3_bucket_name" placeholder="e.g. my-bucket" required>
                </div>
                <div class="form-group">
                    <label for="s3_bucket_prefix">Bucket prefix:</label>
                    <input type="text" class="form-control" id="s3_bucket_prefix" placeholder="e.g. optional/prefix">
                    <small class="text-muted">Optional path prefix within the bucket.</small>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="s3_readonly" style="margin-top: 4px;margin-left: -24px;z-index: 999;">
                    <label class="custom-control-label" for="s3_readonly">Read only?</label>
                    <small class="text-muted">When checked, the organization will have read-only access to this bucket.</small>
                </div>
            </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger mr-auto" id="btnDeleteS3">Delete</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="btnSaveS3">Create S3 Access</button>
        </div>
        </div>
    </div>
    </div>
