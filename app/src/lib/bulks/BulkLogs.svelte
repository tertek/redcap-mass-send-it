<script lang="ts">
    let {num_scheduled, num_sent, bulk_id} = $props()
</script>
<div class="card mt-3">
    <div class="card-body p-2">
        <div class="clearfix">
            <div class="float-start boldish" style="color:#6320ac;width:90px;">
                <i class="fs14 fas fa-tachometer-alt"></i> Activity:
            </div>
            <div class="float-start"> 
                {#if num_scheduled == 0}
                <div class="text-secondary">
                    <i class="far fa-clock"></i> There are no notifications currently scheduled.
                    {#if num_scheduled == 0 && num_sent == 0}
                    <button style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .25rem; --bs-btn-font-size: .6rem;" class="schedule-now-btn btn btn-rcgreen" type="button" data-bulk-id="{bulk_id}">Schedule Now</button>
                    {/if}
                </div>  
                {:else}
                <div class="">
                    <i class="far fa-clock"></i> {num_scheduled} notifications are currently scheduled.
                </div>                
                {/if}

                {#if num_sent == 0}
                <div class="text-secondary">
                    <i class="far fa-envelope-open"></i> No notifcations were sent yet.
                </div>
                {:else}
                <div class="">
                    <i class="far fa-envelope-open"></i> {num_sent} notifcations were sent.
                </div>
                {/if}
            </div>
        </div>
    </div>
</div>