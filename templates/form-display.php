<div id="gga-loading" class="text-center my-3" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<form id="gga-form" class="p-3 border rounded bg-light needs-validation" novalidate>
    <div class="mb-3">
        <label class="form-label" for="gga-setting">Genesys Setting:</label>
        <select id="gga-setting" class="form-select" required>
            <option value="Fantasy">Fantasy</option>
            <option value="Steampunk">Steampunk</option>
            <option value="Wierd War">Wierd War</option>
            <option value="Modern Day">Modern Day</option>
            <option value="Science Fiction">Science Fiction</option>
            <option value="Space Opera">Space Opera</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="gga-level">NPC Level:</label>
        <select id="gga-level" class="form-select" required>
            <option value="Minion">Minion</option>
            <option value="Rival">Rival</option>
            <option value="Nemesis">Nemesis</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="gga-tier">Player Experience Tier:</label>
        <select id="gga-tier" class="form-select" required>
            <option value="Novice (0-99 XP)">Novice (0-99 XP)</option>
            <option value="Apprentice (100-149 XP)">Apprentice (100-149 XP)</option>
            <option value="Journeyman (150-199 XP)">Journeyman (150-199 XP)</option>
            <option value="Veteran (200-249 XP)">Veteran (200-249 XP)</option>
            <option value="Expert (250-299 XP)">Expert (250-299 XP)</option>
            <option value="Master (300+ XP)">Master (300+ XP)</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="gga-players">Number of Players:</label>
        <input type="number" id="gga-players" class="form-control" min="1" max="10" required>
    </div>

    <div class="mb-3">
        <label class="form-label" for="gga-world">World Background:</label>
        <textarea id="gga-world" class="form-control" rows="4" maxlength="800" required></textarea>
        <div class="form-text"><span id="world-count">0</span>/800 characters used</div>
        <div class="invalid-feedback">Please provide world background details.</div>
    </div>

    <div class="form-check mb-2">
        <input type="checkbox" id="gga-quirk" class="form-check-input">
        <label class="form-check-label" for="gga-quirk">Add a special quirk to the NPC</label>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" id="gga-prompts" class="form-check-input">
        <label class="form-check-label" for="gga-prompts">Include roleplay prompts for this NPC</label>
    </div>

    <button id="gga-submit" type="submit" class="btn btn-primary">Generate NPC</button>
</form>

