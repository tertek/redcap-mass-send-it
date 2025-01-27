<script module>
    declare const JSO_STPH_MASS_SEND_IT: any
    declare const DTO_STPH_MASS_SEND_IT: any
</script>
<script lang="ts">
    import {onMount} from  'svelte'
    import Bulks from './Bulks.svelte'
    import Notifications from './Notifications.svelte'
    import Alert from './Util/Alert.svelte'
    import Modal from './Modal/index.svelte'

    const urlParams = new URLSearchParams(window.location.search)
    const isLog = urlParams.has("log")
    let BulkModal: any


    console.log(BulkModal)

    //@ts-ignore
    //const myModal = new bootstrap.Modal(BulkModal)

    onMount(()=>{

    //  @ts-ignore
      //const myModal = new bootstrap.Modal('#external-modules-configure-modal-1')
      //const myModal = new bootstrap.Modal(BulkModal)
    })


    //  @ts-ignore
    //const myModal = new bootstrap.Modal(BulkModal)

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
        return JSON.parse(json)
    }

    //
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
    <Modal bind:modalRef={BulkModal}/>
    
  {/if}
{/await}
{:else}
<Notifications />
{/if}