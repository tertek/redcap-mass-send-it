<script lang="ts">
    let {bulk} = $props()
</script>
<div class="card mt-3">
    <div class="card-body p-2">
        <div id="trigger-descrip0" class="mb-1 trigger-descrip" style="overflow: hidden; text-overflow: ellipsis; -webkit-box-orient: vertical; display: -webkit-box; -webkit-line-clamp: 3;">
            {#if bulk.bulk_type == 'list'}
                <b class="fs14"><i class="fas fa-hand-point-right"></i></b> Based on <b>record list</b><span class="text-secondary ms-1 fs12">($recipient_count recipients)</span>
            {:else if bulk.bulk_type == 'logic'}
                Based on <b>filter logic:</b> <span class="code" style="font-size:85%;">{bulk.bulk_recipients_logic}</span><span class="text-secondary ms-1 fs12">( $recipient_count recipients)</span>
            {/if}
        </div>
        <div class="mt-1" style="color:green;">
            <i class="far fa-clock"></i> Schedule to send on {bulk.bulk_schedule}
        </div>
        <div class="mt-1">
            {#if bulk.use_second_email == false}
            <b class="code box-1x">1x</b> Send one email <span class="text-secondary ms-1 fs12"></span>
            {:else}
            <b class="code box-1x">2x</b> Send two emails <span class="text-secondary ms-1 fs12"></span>
            {/if}
        </div>
        <div class="mt-1">
            {#if bulk.use_random_pass}                                
                <i class="fa-solid fa-lock"></i> Using <b>random password</b><span class="text-secondary ms-1 fs12">(generated for each record)</span>
            {:else}
                <i class="fa-solid fa-lock"></i> Using <b>custom password</b><span class="text-secondary ms-1 fs12">(with custom password field: <span class="code" style="font-size:85%;">{bulk.custom_pass_field}</span>)</span>
            {/if}                                
        </div>
    </div>
</div>