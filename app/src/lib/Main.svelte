<script module>
    declare const JSO_STPH_MASS_SEND_IT: any
    declare const DTO_STPH_MASS_SEND_IT: any
</script>

<script lang="ts">
    
    import Bulks from './Bulks.svelte'
    import Notifications from './Notifications.svelte'
    import Alert from './Util/Alert.svelte'
    import Modal from './Modal/index.svelte'

    const urlParams = new URLSearchParams(window.location.search)
    const isLog = urlParams.has("log")

    //  Reads all bulks with count information on schedules and notifications
    async function fetchBulks() {
        const payload = {
            task: 'read',
            data: {
            all: true,
            withCount: true
            }
        }
        const json = await JSO_STPH_MASS_SEND_IT.ajax("bulk", payload)
        const response = JSON.parse(json)
        return response
    }    

</script>

{#if !isLog}
{#await fetchBulks() then response }
  {#if response.error}
    <Alert 
      error={true} 
      message={response.message} 
    />
  {:else}
    <Bulks bulks={response.data.bulks} />
    <Modal />
  {/if}
{/await}
{:else}
<Notifications />
{/if}