<script module>
    declare const JSO_STPH_MASS_SEND_IT: any
    declare const DTO_STPH_MASS_SEND_IT: any
</script>
<script lang="ts">
    const urlParams = new URLSearchParams(window.location.search)
    const isLog = urlParams.has("log")
    const url  = JSO_STPH_MASS_SEND_IT.getUrl(DTO_STPH_MASS_SEND_IT.basename)
</script>
<div class="clearfix">
    <div id="sub-nav" class="d-none d-sm-block">
        <ul>
            <li class={isLog ? '' : 'active' } >
                <a class="sub-nav-link" href="{url}">
                    <i class="fas fa-mail-bulk me-1"></i>{JSO_STPH_MASS_SEND_IT.tt("nav_title_my_bulks")}
                </a>
            </li>
            <li class={ isLog ? 'active' : '' }>
                <a class="sub-nav-link" href="{url}&log=1">
                    <i class="fas fa-table me-1"></i>{JSO_STPH_MASS_SEND_IT.tt("nav_title_notification_log")}
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