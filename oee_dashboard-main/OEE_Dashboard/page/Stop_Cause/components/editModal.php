<div id="editStopModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editStopModal')">&times;</span>
        <h2 style="font-size: 2.5rem;">Edit Cause</h2>
        <form id="editStopForm">
            <input type="hidden" name="id" id="edit_id">

            <input type="date" name="log_date" id="edit_log_date" required>
            
            <input type="time" name="stop_begin" id="edit_stop_begin" step="1" required>
            <input type="time" name="stop_end" id="edit_stop_end" step="1" required>

            <input list="editLineList" name="line" id="edit_line" placeholder="Line" required>
            <datalist id="editLineList"></datalist>

            <input list="editMachineList" name="machine" id="edit_machine" placeholder="Machine / Station" required>
            <datalist id="editMachineList"></datalist>

            <input list="editCauseList" name="cause" id="edit_cause" placeholder="Cause" required>
            <datalist id="editCauseList"></datalist>
            
            <input list="editRecoveredByList" name="recovered_by" id="edit_recovered_by" placeholder="Recovered by" required>
            <datalist id="editRecoveredByList"></datalist>

            <input type="text" placeholder="Note" name="note" id="edit_note">

            <button type="submit" style="padding-block: 0;">Update</button>
        </form>
    </div>
</div>