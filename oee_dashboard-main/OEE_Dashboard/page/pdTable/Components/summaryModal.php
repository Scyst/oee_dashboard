<div id="summaryModal" class="modal">
    <div class="modal-content" style="max-height: 80vh; min-width: fit-content; max-width: 80%; overflow-y: auto;">
        <span class="close" onclick="closeModal('summaryModal')">&times;</span>
        
        <div class="modal-header">
            <h2>Detailed Summary</h2>
            <button class="btn btn-info btn-sm" onclick="exportSummaryToExcel()">Export Summary</button>
        </div>

        <div id="summaryGrandTotalContainer" class="summary-grand-total">
            </div>

        <div id="summaryTableContainer">
            </div>
    </div>
</div>