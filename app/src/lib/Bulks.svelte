<script lang="ts">
    import BulkHead from "./bulks/BulkHead.svelte";
    import BulkMeta from "./bulks/BulkMeta.svelte";
    import BulkRepo from "./bulks/BulkRepo.svelte";
    import BulkLogs from "./bulks/BulkLogs.svelte";
    import BulkMail from "./bulks/BulkMail.svelte";
    let {bulks} = $props()
</script>
<div id="my-bulks-page" class="mt-3">
    <div class="mb-5 clearfix">
        <button id='addNewBulk' type="button" class="btn btn-sm btn-rcgreen float-start">
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
                <tr id="bulk_{bulk.bulk_id}" class="{(index+1) % 2 == 0 ? 'even' : 'odd'}">
                    <td class="pt-0 pb-4" style="border-right:0;" data-order="1">
                        <BulkHead bulk={bulk} />
                        <BulkMeta bulk={bulk}/>
                        <BulkRepo bulk={bulk} />
                        <BulkLogs num_scheduled={0} num_sent={0} bulk_id={bulk.bulk_id}/>
                    </td>
                    <td class="pt-3 pb-4" style="width:350px;border-left:0;">
                        <BulkMail bulk={bulk} />
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>                
</div>
<style>
    #my-bulks-page {
        width:950px;
        max-width:950px;
    }
</style>