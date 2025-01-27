<script lang="ts">
        let {bulk} = $props()
        function showModal() {
            alert("show modal for bulk_id: " + bulk.bulk_id)
        }
</script>
<div class="clearfix" style="margin-left: -11px;">
    <div style="max-width:340px;" class="card-header alert-num-box  float-start text-truncate"><i class="fas fa-mail-bulk fs13" style="margin-right:5px;"></i>Bulk #{bulk.bulk_id + " "  + (bulk.bulk_title ?? "")}</div>
    <div class="btn-group nowrap float-start mb-1 ms-2" role="group">
        <button type="button" class="btn btn-link fs13 py-1 ps-1 pe-2 bulk-edit-btn" onclick={showModal} data-bulk-id="{bulk.bulk_id}">
            <i class="fas fa-pencil-alt"></i> Edit
        </button>
        <div class="btn-group dropdown" role="group">

            <button id="btnGroupDrop1" type="button" class="btn btn-link fs13 py-1 ps-2 pe-0 dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-toggle="dropdown">
                <i class="fas fa-cog"></i> Options
            </button>

            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                <a href="#reschedule"  class="dropdown-item bulk-schedule-btn {bulk.num_scheduled == 0 && bulk.num_sent == 0 ? "disabled" : ""}" data-bulk-id="{bulk.bulk_id}"><i class="fas fa-calendar-check"></i> Reschedule</a>

                <a class="dropdown-item bulk-delete-btn" href="delete-bulk" data-bulk-id="{bulk.bulk_id}"><i class="fas fa-trash"></i> Delete bulk</a>
            </div>
        </div>
    </div>
    <div style="padding:4px 4px 4px 5px; float: right;">
        <span class="fs11"><span class="text-secondary">Unique Bulk ID:</span> <span style="color:#A00000;">B-{bulk.bulk_id}</span></span>
    </div>
</div>