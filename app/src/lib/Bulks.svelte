<script lang="ts">
    import Bulk from './Bulk/index.svelte'
    let {bulks} = $props()

    function showModal() {
        console.log()
        //BulkModal.show()
        // //  data-bs-toggle="modal" data-bs-target="#external-modules-configure-modal-1"
    }

</script>
<div class="mt-3 has-max-width">
    <div class="mb-5 clearfix">
        <button 
            onclick={showModal}
            id='addNewBulk' 
            type="button" 
            class="btn btn-sm btn-rcgreen float-start" >
            <i class="fas fa-plus"></i> Add New Bulk
        </button>        
    </div>

    <table class="table table-bordered table-hover email_preview_forms_table dataTable " id="customizedAlertsPreview" style="width:100%;table-layout: fixed;">
        <thead>
            <tr class="table_header d-none">
                <th>Bulks</th>
                <th style="width:350px;"><span class="fas fa-envelope"></span> Bulk</th>
                <th style="display:none;">Active</th>
                <th style="display:none;">Deleted</th>
            </tr>
        </thead>
        <tbody>
            {#each bulks as bulk, index}
                <Bulk {bulk} {index}/>
            {/each}
        </tbody>
    </table>

</div>
<style>
    .has-max-width {
        width:950px;
        max-width:950px;
    }
  </style>