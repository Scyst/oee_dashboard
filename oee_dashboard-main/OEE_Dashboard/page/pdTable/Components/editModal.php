<div id="editPartModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editPartModal')">&times;</span>
        <h2 style="font-size: 2.5rem;">Edit Part</h2>
        <form id="editPartForm">
            <input type="hidden" name="id" id="edit_id">

            <input type="date" name="log_date" id="edit_log_date" required><br>
            
            <input type="time" name="log_time" id="edit_log_time" step="1" required><br>

            <input list="editLineList" name="line" id="edit_line" placeholder="Line" required>
            <datalist id="editLineList"></datalist><br>

            <input list="editModelList" name="model" id="edit_model" placeholder="Model" required>
            <datalist id="editModelList"></datalist><br>

            <input list="editPartList" name="part_no" id="edit_part_no" placeholder="Part No." required>
            <datalist id="editPartList"></datalist><br>

            <input type="text" name="lot_no" id="edit_lot_no" placeholder="Lot No." readonly>

            <input type="number" name="count_value" id="edit_count_value" placeholder="Quantity" required><br>
            
            <select name="count_type" id="edit_count_type" required>
                <option value="">-- Select Type --</option>
                <option value="FG">FG</option>
                <option value="NG">NG</option>
                <option value="HOLD">HOLD</option>
                <option value="REWORK">REWORK</option>
                <option value="SCRAP">SCRAP</option>
                <option value="ETC.">ETC.</option>
            </select><br>

            <input type="text" placeholder="Note" name="note" id="edit_note"><br>

            <button type="submit">Update</button>
        </form>
    </div>
</div>