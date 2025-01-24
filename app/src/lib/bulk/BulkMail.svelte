<script lang="ts">
    let {bulk} = $props()
</script>
<div class="card">
    <div class="card-header bg-light py-1 px-3 clearfix" style="color:#004085;background-color:#d5e3f3 !important;">
        <div class="float-start"><i class="fas fa-envelope"></i> Email (1)</div>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
                <li class="list-group-item py-1 px-3 text-truncate fs12">
            <span class="me-1 boldish">From:</span> 
            <a href="#email_from" class="fs12">{bulk.email_from}</a>
            </li><li class="list-group-item py-1 px-3 text-truncate fs12">
            <span class="me-1 boldish">To:</span> <a href="#email_to" class="fs12">{bulk.email_to}</a>
            </li><li class="list-group-item py-1 px-3 text-truncate fs12">
            <span class="me-1 boldish">Subject:</span>{bulk.email_first_subject}
            </li><li class="list-group-item py-1 px-3 text-truncate fs12">{@html bulk.email_first_message}
            </li>       
        </ul>                                                                  
    </div>
</div>
{#if bulk.use_second_email}
<div class="card mt-2">
    <div class="card-header bg-light py-1 px-3 clearfix" style="color:#004085;background-color:#d5e3f3 !important;">
        <div class="float-start"><i class="fas fa-envelope"></i> Email (2)</div>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <li class="list-group-item py-1 px-3 text-truncate fs12">
                <span class="me-1 boldish">Subject:</span>{bulk.email_second_subject}
            </li>
            <li class="list-group-item py-1 px-3 text-truncate fs12">
                {@html bulk.email_second_message}
            </li>       
        </ul>                                                                  
    </div>
</div>
{/if}