<div class="modal" id="editStopModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Edit Cause</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStopForm">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label for="edit_log_date" class="form-label">Log Date</label>
                        <input type="date" name="log_date" id="edit_log_date" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_stop_begin" class="form-label">Stop Begin</label>
                            <input type="time" name="stop_begin" id="edit_stop_begin" step="1" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_stop_end" class="form-label">Stop End</label>
                            <input type="time" name="stop_end" id="edit_stop_end" step="1" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_line" class="form-label">Line</label>
                        <input list="editLineList" name="line" id="edit_line" class="form-control" placeholder="Select or type Line" required>
                        <datalist id="editLineList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="edit_machine" class="form-label">Machine / Station</label>
                        <input list="editMachineList" name="machine" id="edit_machine" class="form-control" placeholder="Select or type Machine" required>
                        <datalist id="editMachineList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="edit_cause" class="form-label">Cause</label>
                        <input list="editCauseList" name="cause" id="edit_cause" class="form-control" placeholder="Select or type Cause" required>
                        <datalist id="editCauseList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="edit_recovered_by" class="form-label">Recovered by</label>
                        <input list="editRecoveredByList" name="recovered_by" id="edit_recovered_by" class="form-control" placeholder="Select or type name" required>
                        <datalist id="editRecoveredByList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="edit_note" class="form-label">Note</label>
                        <input type="text" placeholder="Optional note" name="note" id="edit_note" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editStopForm" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>