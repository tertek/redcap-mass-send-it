<script lang="ts">
    import Head from "./head.svelte";
    import Meta from "./meta.svelte";
    import Repo from "./repo.svelte";
    import Logs from "./logs.svelte";
    import Mail from "./mail.svelte";
    let {bulk, index} = $props()

</script>
<tr id="bulk_{bulk.bulk_id}" class="{(index+1) % 2 == 0 ? 'even' : 'odd'}">
    <td class="pt-0 pb-4"  data-order="1">
        <Head bulk={bulk} />
        <Meta bulk={bulk} />
        <Repo bulk={bulk} />
        <Logs bulk={bulk} />
    </td>
    <td class="pt-3 pb-4" style="width:350px;">
        <Mail bulk={bulk} />
    </td>
</tr>
