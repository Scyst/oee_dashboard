<div class="modal" id="addStopModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Add Cause</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addStopForm">
                    <div class="mb-3">
                        <label for="addStopLogDate" class="form-label">Log Date</label>
                        <input type="date" id="addStopLogDate" name="log_date" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="addStopBegin" class="form-label">Stop Begin</label>
                            <input type="time" id="addStopBegin" name="stop_begin" step="1" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="addStopEnd" class="form-label">Stop End</label>
                            <input type="time" id="addStopEnd" name="stop_end" step="1" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="addLine" class="form-label">Line</label>
                        <input list="addLineList" id="addLine" name="line" class="form-control" placeholder="Select or type Line" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
                        <datalist id="addLineList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="addMachine" class="form-label">Machine / Station</label>
                        <input list="addMachineList" id="addMachine" name="machine" class="form-control" placeholder="Select or type Machine" required>
                        <datalist id="addMachineList"></datalist>
                    </div>
                    
                    <div class="mb-3">
                        <label for="addCause" class="form-label">Cause</label>
                        <input list="addCauseList" id="addCause" name="cause" class="form-control" placeholder="Select or type Cause" required>
                        <datalist id="addCauseList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="addRecoveredBy" class="form-label">Recovered by</label>
                        <input list="addRecoveredByList" id="addRecoveredBy" name="recovered_by" class="form-control" placeholder="Select or type name" required>
                        <datalist id="addRecoveredByList"></datalist>
                    </div>

                    <div class="mb-3">
                        <label for="addNote" class="form-label">Note</label>
                        <input type="text" id="addNote" name="note" class="form-control" placeholder="Optional note">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="addStopForm" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>