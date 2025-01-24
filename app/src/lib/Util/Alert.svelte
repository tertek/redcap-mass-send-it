<script lang="ts">
    let {error, message} = $props()
</script>

<div class="alert alert-{error ? 'error': 'success'} has-max-width" role="alert">
    <h4 class="alert-heading">{error ? 'Error' : 'Success'}</h4>
    <p><b>{message}</b></p>
</div>
<style>
    .has-max-width {
        width:950px;
        max-width:950px;
    }
  </style>