<h3>System Requirements Check</h3>
<p>Please ensure your system meets all the requirements before proceeding with the installation.</p>

<div id="requirements-loading">
    <div class="progress">
        <div class="progress-bar progress-bar-striped active" style="width: 100%"></div>
    </div>
    <p>Checking system requirements...</p>
</div>

<div id="requirements-results" style="display: none;">
    <div id="requirements-list"></div>
    
    <div class="btn-navigation">
        <button type="button" class="btn btn-default" onclick="checkRequirements()">
            <i class="icon-refresh"></i> Refresh Check
        </button>
        <button id="btn-next-step" type="button" class="btn btn-primary pull-right" disabled onclick="proceedToNextStep()">
            Next Step <i class="icon-arrow-right"></i>
        </button>
        <div id="ignore-requirements" style="display: none; margin-top: 10px;">
            <label>
                <input type="checkbox" id="ignore-check" onchange="toggleIgnoreRequirements()">
                Ignore requirements check (not recommended)
            </label>
        </div>
    </div>
</div>