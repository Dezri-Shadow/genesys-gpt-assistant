<?php
error_log('[GGA] api-handler.php loaded');
add_action('rest_api_init', function() {
    error_log('[GGA] Registering /chat/ route');

    register_rest_route('gga/v1', '/chat/', [
        'methods' => 'POST',
        'callback' => 'gga_handle_chat_request',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('gga/v1', '/debug/', [
        'methods' => 'GET',
        'callback' => 'gga_debug_endpoint',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ]);

    register_rest_route('gga/v1', '/save', [
        'methods' => 'POST',
        'callback' => 'gga_save_npc_handler',
        'permission_callback' => function () {
            return current_user_can('read');
        }
    ]);

    register_rest_route('gga/v1', '/npcs', [
        'methods' => 'GET',
        'callback' => 'gga_get_saved_npcs',
        'permission_callback' => function () {
            return current_user_can('read');
        }
    ]);

    register_rest_route('gga/v1', '/delete', [
        'methods' => 'POST',
        'callback' => 'gga_delete_npc_handler',
        'permission_callback' => function () {
            return current_user_can('read');
        }
    ]);

});

function gga_get_saved_npcs() {
    $user = wp_get_current_user();
    if (!$user || !$user->ID) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }

    $npcs = get_user_meta($user->ID, 'gga_saved_npcs', true) ?: [];
    return new WP_REST_Response(['npcs' => $npcs], 200);
}

function gga_save_npc_handler(WP_REST_Request $request) {
    $user = wp_get_current_user();
    if (!$user || !$user->ID) {
        return new WP_REST_Response(['message' => 'Unauthorized'], 401);
    }

    $npc = $request->get_param('npc');
    if (!$npc || !is_array($npc) || empty($npc['name'])) {
        return new WP_REST_Response(['message' => 'Invalid NPC data'], 400);
    }

    $npcs = get_user_meta($user->ID, 'gga_saved_npcs', true) ?: [];
    $npcs[] = [
        'name' => sanitize_text_field($npc['name']),
        'data' => $npc,
        'saved_at' => current_time('mysql')
    ];
    update_user_meta($user->ID, 'gga_saved_npcs', $npcs);

    return new WP_REST_Response(['message' => 'NPC saved successfully.'], 200);
}

function gga_handle_chat_request(WP_REST_Request $request) {
    $body = $request->get_json_params();

    // Extract & sanitize inputs
    $prompt_raw = sanitize_text_field($body['prompt'] ?? '');

    // Reject if empty
    if (empty($prompt_raw)) {
        return new WP_REST_Response(['error' => 'Prompt is empty or invalid.'], 400);
    }

    // Filter prompt for potential prompt injection
    $prompt_sanitized = gga_defend_prompt($prompt_raw);

    $api_key = get_option('gga_openai_api_key');
    if (!$api_key) {
        return new WP_REST_Response(['error' => 'API key is not configured.'], 403);
    }

    $system_msg = <<<EOT
    You are a GM assistant trained in the Genesys RPG system by Fantasy Flight Games. Generate detailed, lore-consistent NPC stat blocks strictly following Genesys mechanics and narrative tone.

    Output only strict JSON—no markdown, prose, or explanation. All content must be in English.

    Top-level JSON keys: name, type, characteristics, skills, talents, gear, combat_stats, tactics, quirks, complications.

    **Characteristics:**
    - Use standard Genesys attributes (e.g., Brawn, Agility, Intellect).

    **Skills:**
    - Do not calculate dice pools.
    - For each skill, include:
    {
        "name": "Skill Name",
        "rank": 0–5,
        "characteristic": "Associated Characteristic"
    }

    **Talents:**
    - Only use talents from the Genesys Core Rulebook.
    - Do not invent new talents or tiers.
    - Include the correct tier (1–5) for each talent.
    - Talent structure must follow strict tier logic:
    - Each higher-tier talent requires more lower-tier support.
    - You may include a Tier N talent only if there are at least N Tier N–1 or lower talents.
    - Each tier must contain strictly fewer talents than the tier below.
    - Examples:
        - 2 Tier 2 → requires ≥3 Tier 1
        - 1 Tier 3 → requires ≥2 Tier 2 and ≥3 Tier 1
    - Talent format:
    {
        "name": "Talent Name",
        "tier": 1–5,
        "description": "Short summary of effect"
    }

    **Gear:**
    - Use Core Rulebook gear unless specified.
    - Format: name and brief description.

    **Combat Stats:**
    - Use: soak, wounds, strain, defense { melee, ranged }

    **Narrative Fields:**
    - tactics, quirks, complications: short descriptive sentences.

    **System Output Template:**
    {
    "name": "NPC Name",
    "type": "Minion | Rival | Nemesis",
    "characteristics": { "Brawn": 2, ... },
    "skills": [{ "name": "Discipline", "rank": 3, "characteristic": "Willpower" }],
    "talents": [{ "name": "Toughened", "tier": 1, "description": "Gain +2 Wounds" }],
    "gear": [{ "name": "Blaster", "description": "Ranged (Light); 6 Damage; Crit 3" }],
    "combat_stats": { "soak": 4, "wounds": 12, "strain": 10, "defense": { "melee": 1, "ranged": 0 } },
    "tactics": "Aggressive flanker using cover",
    "quirks": "Compulsively polishes gear",
    "complications": "Weapon jams on first Crit roll"
    }
    EOT;

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 20, // increase from default (5) to 20 seconds
        'body' => json_encode([
            'model' => 'gpt-4.1-nano',
            'messages' => [
                ['role' => 'system', 'content' => $system_msg],
                ['role' => 'user', 'content' => $prompt_sanitized],
            ],
        ]),
    ]);      

    if (is_wp_error($response)) {
        error_log('OpenAI API request failed: ' . $response->get_error_message());
        return new WP_REST_Response([
            'error' => 'Failed to contact OpenAI API.',
            'details' => $response->get_error_message()
        ], 500);
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['error'])) {
        error_log('OpenAI API returned an error: ' . print_r($data['error'], true));
        return new WP_REST_Response([
            'error' => 'OpenAI API returned an error.',
            'details' => $data['error']['message'] ?? 'Unknown error'
        ], 502);
    }
    
    if (!isset($data['choices'][0]['message']['content'])) {
        error_log('OpenAI response did not contain expected content. Raw body: ' . $body);
        return new WP_REST_Response([
            'error' => 'Unexpected response from OpenAI API.',
            'details' => 'Missing choices[0].message.content'
        ], 500);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? null;
    $parsed_npc = json_decode($content, true);

    // Optional: Validate parsed JSON before saving
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed_npc) && !empty($parsed_npc['name'])) {
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            $existing_npcs = get_user_meta($current_user->ID, 'gga_saved_npcs', true) ?: [];

            $new_npc = [
                'name' => sanitize_text_field($parsed_npc['name']),
                'data' => $parsed_npc,
                'saved_at' => current_time('mysql')
            ];

            $existing_npcs[] = $new_npc;
            update_user_meta($current_user->ID, 'gga_saved_npcs', $existing_npcs);
        }
    }

    return new WP_REST_Response([
        'choices' => $body['choices']
    ], 200);


}

function gga_defend_prompt($input) {
    // Normalize line endings and spaces
    $input = preg_replace('/\s+/', ' ', trim($input));

    // Remove common prompt injection attempts
    $blacklist = [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/you\s+are\s+now\s+.*?/i',
        '/(please\s+)?respond\s+with/i',
        '/```.*?```/s',
        '/`{1,3}/',
    ];
    foreach ($blacklist as $pattern) {
        $input = preg_replace($pattern, '[REDACTED]', $input);
    }

    // Strip control characters
    $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);

    return $input;
}

function gga_debug_endpoint() {
    $api_key = get_option('gga_openai_api_key');
    $key_preview = $api_key ? substr($api_key, 0, 6) . '...' : 'Not set';

    $model = 'gpt-4.1-nano'; // update if you allow admin override in future

    $system_msg = <<<EOT
You are a GM assistant trained in the Genesys RPG system by Fantasy Flight Games...
EOT;

    return [
        'plugin_status' => 'Active',
        'route_check' => 'This debug route is active and working.',
        'api_key_present' => $api_key ? true : false,
        'api_key_preview' => $key_preview,
        'model' => $model,
        'system_message_snippet' => substr($system_msg, 0, 100) . '...'
    ];
}

function gga_delete_npc_handler($request) {
    $user_id = get_current_user_id();
    $npcs = get_user_meta($user_id, 'gga_saved_npcs', true) ?: [];
    $params = $request->get_json_params();
    $index = intval($params['index'] ?? -1);

    if ($index < 0 || !isset($npcs[$index])) {
        return new WP_REST_Response(['error' => 'Invalid NPC index'], 400);
    }

    array_splice($npcs, $index, 1);
    update_user_meta($user_id, 'gga_saved_npcs', $npcs);

    return new WP_REST_Response(['message' => 'NPC deleted.'], 200);
}
