jQuery(document).ready(function($) {
    const form = document.getElementById('gga-form');

    let knownTalents = {};

    fetch('/wp-content/plugins/genesys-gpt-assistant/assets/data/talents.json')
        .then(response => response.json())
        .then(data => { knownTalents = data; })
        .catch(err => console.error('Failed to load talent data:', err));


    form.addEventListener('submit', function(e) {
        // Perform Bootstrap validation
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        e.preventDefault();

        // Collect sanitized values
        const setting = $('#gga-setting').val();
        const level = $('#gga-level').val();
        const tier = $('#gga-tier').val();
        const players = parseInt($('#gga-players').val(), 10);
        const world = $('#gga-world').val().trim();
        const addQuirk = $('#gga-quirk').is(':checked');
        const addPrompts = $('#gga-prompts').is(':checked');

        // Extra content validation
        if (world.length < 10 ) {
            alert('Please provide more detailed descriptions (at least 10 characters).');
            return;
        }

        if (world.length > 800 ) {
            alert('Text inputs must not exceed 800 characters.');
            return;
        }        

        const prompt = `
            Generate a ${level}-level NPC for the Genesys RPG system.
            Setting: ${setting}
            Player Experience Tier: ${tier}
            Number of Players: ${players}
            World Background: ${world}
            ${addQuirk ? 'Include a unique quirk for this NPC.' : ''}
            ${addPrompts ? 'Add a few roleplay prompts or lines this NPC might say.' : ''}
            Return the result in detailed JSON format, including stats, background, and roleplay elements.
            Ensure that all skill dice pools are based on valid Genesys logic, using the NPC's characteristics and skill ranks. Show only the final calculated dice pool.
            `.trim();

        $('#gga-loading').show();
        $('#gga-submit').prop('disabled', true);
        // API request
        const apiUrl = `${window.location.origin}/wp-json/gga/v1/chat/`;
        $.ajax({
            url: apiUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ prompt }),
            success: function(res) {
                const content = res.choices?.[0]?.message?.content || 'No response';

                try {
                    const parsed = JSON.parse(content);
                
                    if (!isValidNPCStructure(parsed)) {
                        $('#gga-json-display').html('<div class="text-danger"><strong>Invalid NPC format received.</strong></div>');
                    } else {
                        const html = renderJSONToHTML(parsed);
                        $('#gga-json-display').html(html);
                    }

                } catch (e) {
                    $('#gga-json-display').html('<div class="text-danger"><strong>Response was not valid JSON.</strong></div>');
                }

                const modal = new bootstrap.Modal(document.getElementById('gga-modal'));
                $('#gga-loading').hide();
                $('#gga-submit').prop('disabled', false);
                modal.show();
            },
            
            error: function(jqXHR, textStatus, errorThrown) {
                $('#gga-loading').hide();
                $('#gga-submit').prop('disabled', false);
            
                let message = 'An error occurred while contacting the GPT API.';
            
                if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
                    message += `\n\nDetails: ${jqXHR.responseJSON.error}`;
                } else if (textStatus === 'timeout') {
                    message += '\n\nThe request timed out. Please try again.';
                } else if (textStatus === 'parsererror') {
                    message += '\n\nReceived an unexpected response format.';
                }
            
                $('#gga-modal-content').text(message);
                $('#gga-json-display').html('');
            
                const modal = new bootstrap.Modal(document.getElementById('gga-modal'));
                modal.show();
            
                console.warn('GPT API error:', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
            } 
                     
        });
    });

    $('#gga-json-display').on('click', '.gga-toggle', function() {
        const $toggle = $(this);
        const $target = $toggle.nextAll('.gga-collapsible').first();
        $target.toggle();
        $toggle.text($target.is(':visible') ? '▼' : '▶');
    });

    function bindCharCounter(id, counterId, limit) {
        $(`#${id}`).on('input', function() {
            const len = $(this).val().length;
            $(`#${counterId}`).text(len);
            if (len > limit) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    }
    
    bindCharCounter('gga-world', 'world-count', 800);
    bindCharCounter('gga-events', 'events-count', 800);
});

function renderDicePool(rank, charVal) {
    const base = Math.max(rank, charVal);
    const upgrades = Math.min(rank, charVal);
    const dice = [];

    for (let i = 0; i < upgrades; i++) {
        dice.push('<img src="/wp-content/plugins/genesys-gpt-assistant/assets/images/dice/die-proficiency.png" alt="Proficiency Die" class="die-icon">');
    }
    for (let i = 0; i < base - upgrades; i++) {
        dice.push('<img src="/wp-content/plugins/genesys-gpt-assistant/assets/images/dice/die-ability.png" alt="Ability Die" class="die-icon">');
    }

    return `<span class="die-stack">${dice.join('')}</span>`;
}

// For future symbol rendering if needed
function renderSymbol(type) {
    return `<img src="/wp-content/plugins/genesys-gpt-assistant/assets/images/symbols/symbol-${type}.png" alt="${type}" class="symbol-icon">`;
}

function renderJSONToHTML(npc) {
    const capitalize = str => str.charAt(0).toUpperCase() + str.slice(1);

    const html = ['<div class="npc-render p-3 rounded shadow-sm">'];

    // Header
    html.push(`<h3 class="mb-2">${npc.name} <small class="text-muted">(${npc.type})</small></h3>`);

    // Characteristics
    html.push('<h5>Characteristics</h5><ul class="list-inline npc-section">');
    for (const [key, value] of Object.entries(npc.characteristics)) {
        html.push(`<li class="list-inline-item"><strong>${capitalize(key)}:</strong> ${value}</li>`);
    }
    html.push('</ul>');

    // Combat Stats
    html.push('<h5>Combat Stats</h5><ul class="list-inline npc-section">');
    for (const [key, value] of Object.entries(npc.combat_stats)) {
        if (typeof value === 'object') {
            for (const [subKey, subVal] of Object.entries(value)) {
                html.push(`<li class="list-inline-item"><strong>${capitalize(subKey)} Defense:</strong> ${subVal}</li>`);
            }
        } else {
            html.push(`<li class="list-inline-item"><strong>${capitalize(key)}:</strong> ${value}</li>`);
        }
    }
    html.push('</ul>');

    // Skills with dice pool rendering
    html.push('<h5>Skills</h5><ul class="npc-section">');
    npc.skills.forEach(skill => {
        const charVal = npc.characteristics[skill.characteristic];
        const pool = renderDicePool(skill.rank, charVal);
        html.push(`<li><strong>${skill.name}</strong> (${skill.characteristic} ${charVal}, Rank ${skill.rank}): ${pool}</li>`);
    });
    html.push('</ul>');

    // Talents
    html.push('<h5>Talents</h5><ul class="npc-section">');
    npc.talents.forEach(talent => {
        const expectedTier = knownTalents[talent.name];
        const correct = expectedTier === talent.tier;
        const warning = expectedTier && !correct
        ? `<span class="text-danger" title="Expected Tier ${expectedTier}">⚠️</span>`
        : '';
        html.push(`<li><strong>${talent.name}</strong> (Tier ${talent.tier}): ${talent.description} ${warning}</li>`);
    });
    html.push('</ul>');


    // Gear
    html.push('<h5>Gear</h5><ul class="npc-section">');
    npc.gear.forEach(item => {
        html.push(`<li><strong>${item.name}</strong>: ${item.description}</li>`);
    });
    html.push('</ul>');

    // Narrative Sections
    ['tactics', 'quirks', 'complications'].forEach(field => {
        html.push(`<h5>${capitalize(field)}</h5><p class="npc-section">${npc[field]}</p>`);
    });

    html.push('</div>');
    return html.join('');
}




/* Commented out alternate version of isValidNPCStructure()
function isValidNPCStructure(npc) {
    return (
        npc &&
        typeof npc.name === 'string' &&
        ['Minion', 'Rival', 'Nemesis'].includes(npc.type) &&
        npc.characteristics?.Brawn &&
        Array.isArray(npc.skills) &&
        Array.isArray(npc.talents) &&
        Array.isArray(npc.gear) &&
        typeof npc.combat_stats?.soak === 'number'
    );
}
*/

function isValidNPCStructure(npc) {
    if (!npc || typeof npc !== 'object') return false;

    const requiredKeys = ['name', 'type', 'characteristics', 'skills', 'talents', 'gear', 'combat_stats'];
    const hasKeys = requiredKeys.every(key => npc.hasOwnProperty(key));

    const validType = ['Minion', 'Rival', 'Nemesis'].includes(npc.type);
    const validChar = npc.characteristics && typeof npc.characteristics.Brawn === 'number';

    return hasKeys && validType && validChar;
}

