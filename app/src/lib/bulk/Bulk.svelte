<script lang="ts">
        import BulkHead from "./BulkHead.svelte";
        import BulkMeta from "./BulkMeta.svelte";
        import BulkRepo from "./BulkRepo.svelte";
        import BulkLogs from "./BulkLogs.svelte";
        import BulkMail from "./BulkMail.svelte";
        let {bulk, index} = $props()

</script>
<tr id="bulk_{bulk.bulk_id}" class="{(index+1) % 2 == 0 ? 'even' : 'odd'}">
    <td class="pt-0 pb-4"  data-order="1">
        <BulkHead bulk={bulk} />
        <BulkMeta bulk={bulk} />
        <BulkRepo bulk={bulk} />
        <BulkLogs bulk={bulk} />
    </td>
    <td class="pt-3 pb-4" style="width:350px;">
        <BulkMail bulk={bulk} />
    </td>
</tr>
