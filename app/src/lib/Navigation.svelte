<script lang="ts">
    let {isLog, url} = $props()
</script>
<div class="clearfix">
    <div id="sub-nav" class="d-none d-sm-block">
        <ul>
            <li class={isLog ? '' : 'active' } >
                <a class="sub-nav-link" href="{url}">
                    <i class="fas fa-mail-bulk me-1"></i>My Bulks
                </a>
            </li>
            <li class={ isLog ? 'active' : '' }>
                <a class="sub-nav-link" href="{url}&log=1">
                    <i class="fas fa-table me-1"></i>Notification Log
                </a>
            </li>
        </ul>
    </div>
</div>
<style>
    #sub-nav {
        margin:5px 0 15px;
        width:950px;
    }

    .sub-nav-link {
        font-size:13px;
        color:#393733 !important;
        padding:6px 12px 7px 13px;
    }
</style>