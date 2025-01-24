<script lang="ts">
    let {bulk} = $props()
</script>

<div class="card mt-3">
    <div class="card-body p-2">
        <div class="clearfix">
            <div class="float-start boldish" style="color:#a920ac;width:90px;">
                <i class="fs14 fas fa-folder-open"></i> File Repository:
            </div>
            <div class="float-start">
                <div>
                    <i class="fas fa-folder"></i> Folder Id: {bulk.file_repo_folder_id}
                </div>
                <div>
                    <i class="fas fa-file"></i> File Extension: {bulk.file_repo_extension}
                </div>
                <div>
                    <i class="fas fa-link"></i> Reference Field: {bulk.file_repo_reference}
                </div>
            </div>
        </div>
    </div>
</div>