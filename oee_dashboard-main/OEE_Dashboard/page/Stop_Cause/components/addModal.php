<div id="addStopModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addStopModal')">&times;</span>
        <h2 style="font-size: 2.5rem;">Add Cause</h2>
        <form id="addStopForm">
            <input type="date" name="log_date" required>
            
            <input type="time" name="stop_begin" step="1" required>

            <input type="time" name="stop_end" step="1" required>

            <input list="addLineList" name="line" placeholder="Line" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
            <datalist id="addLineList"></datalist>

            <input list="addMachineList" name="machine" placeholder="Machine / Station" required>
            <datalist id="addMachineList"></datalist>

            <input list="addCauseList" name="cause" placeholder="Cause" required>
            <datalist id="addCauseList"></datalist>
            
            <input list="addRecoveredByList" name="recovered_by" placeholder="Recovered by" required>
            <datalist id="addRecoveredByList"></datalist>

            <input type="text" placeholder="Note" name="note">

            <button type="submit" style="padding-block: 0;">Submit</button>
        </form>
    </div>
</div>