<script lang="ts" module>
    declare const JSO_STPH_MASS_SEND_IT: any
</script>

<div class="col-md-12">
    <form class="form-horizontal" method="post" id="saveBulkForm" novalidate>
        <div class="modal fade" id="external-modules-configure-modal-1" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="true" data-bs-focus="false">
            <div class="modal-dialog" role="document" style="max-width: 950px !important;">
                <div class="modal-content">
                    
                    <div class="modal-header py-2">
                        <button type="button" class="py-2 close closeCustomModal btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <h4 id="add-edit-title-text" class="modal-title form-control-custom">Create new bulk</h4>
                    </div>

                    <div class="modal-body pt-2">
                        <div class="mb-2">
                            {JSO_STPH_MASS_SEND_IT.tt("modal_description")}
                        </div>
                        <table class="code_modal_table" id="code_modal_table_update">
                            <tbody>
                            <!-- Title -->
                            <tr class="form-control-custom">
                                <td colspan="2" class="align-text-top pt-1">
                                    <label for="bulk_title" class="fs14 boldish">Title of this bulk:</label>
                                    <input type="text" name="bulk_title" placeholder="add bulk title" class="d-inline ms-3" style="font-size:15px;width:500px;" maxlength="100">
                                </td>
                            </tr>
                            </tbody>
                        </table>                        
                    </div>

                    <div class="modal-footer">
                        <button id="saveBulkBtn" type="submit" class="btn btn-rcgreen">Save</button>
                        <button type="button" class="btn btn-defaultrc" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    
                </div>
            </div>
        </div>
    </form>
</div>