<script context="module">
    declare const JSO_STPH_MASS_SEND_IT: any
    declare const DTO_STPH_MASS_SEND_IT: any
</script>
<script lang="ts">
  import Header from './lib/Header.svelte'
  import Navigation from './lib/Navigation.svelte';
  import Bulks from './lib/Bulks.svelte'
  import Notifications from './lib/Notifications.svelte'

  const urlParams = new URLSearchParams(window.location.search)
  const isLog = urlParams.has("log")

  const project_page_url  = JSO_STPH_MASS_SEND_IT.getUrl(DTO_STPH_MASS_SEND_IT.basename)

</script>

<main>
  <Header />
  <Navigation isLog={isLog} url={project_page_url} />

  {#if !isLog}
    <Bulks bulks= {DTO_STPH_MASS_SEND_IT.bulks}/>
  {:else}
    <Notifications />
  {/if}

</main>
