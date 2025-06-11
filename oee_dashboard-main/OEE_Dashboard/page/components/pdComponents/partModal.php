<div id="partModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('partModal')">&times;</span>
        <h2>Add Part</h2>
        <form id="addPartForm">
            <input type="date" name="log_date" required value="<?= date('Y-m-d') ?>"><br>
            <input type="time" name="log_time" step="1" required value="<?= date('H:i:s') ?>"><br>

            <!-- Line input with datalist -->
            <input list="lineList" name="line" placeholder="Line" required>
            <datalist id="lineList">
                <?php include '../api/pdTable/get_lines.php'; ?>
            </datalist><br>

            <!-- Model input with datalist -->
            <input list="modelList" name="model" placeholder="Model" required>
            <datalist id="modelList">
                <?php include '../api/pdTable/get_models.php'; ?>
            </datalist><br>

            <!-- Part No. input with datalist -->
            <input list="partList" name="part_no" placeholder="Part No." required>
            <datalist id="partList">
                <?php include '../api/pdTable/get_part_nos.php'; ?>
            </datalist><br>

            <input list="LotList" name="lot_no" placeholder="Lot No." required>
            <datalist id="LotList">
                <?php include '../api/pdTable/get_lot_numbers.php'; ?>
            </datalist><br>

            <input type="number" name="count_value" placeholder="Enter value" required><br>
            
            <select name="count_type" required>
                <option value="FG">FG</option>
                <option value="NG">NG</option>
                <option value="HOLD">HOLD</option>
                <option value="REWORK">REWORK</option>
                <option value="SCRAP">SCRAP</option>
                <option value="ETC.">ETC.</option>
            </select><br>

            <input type="text" placeholder="Note" name="note"><br>

            <button type="submit">Submit Part</button>
        </form>

    </div>
</div>
