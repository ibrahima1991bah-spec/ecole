<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

/**
 * Envoie un message WhatsApp via l'API Cloud officielle de Meta
 * (WhatsApp Business Platform).
 *
 * PRÉREQUIS avant que cette fonction puisse fonctionner réellement :
 *   1. Un compte Meta Business Manager vérifié.
 *   2. Le produit "WhatsApp" activé dans ce Business Manager.
 *   3. Un numéro de téléphone dédié à l'API — ce numéro ne peut plus
 *      servir de compte WhatsApp "normal" dans l'appli mobile en
 *      parallèle. Un simple numéro WhatsApp personnel ne suffit donc
 *      pas tel quel.
 *   4. Des "message templates" créés et validés par Meta pour chaque
 *      type de notification (ex : nouvelle_note, alerte_absence) — il
 *      est impossible d'envoyer du texte libre à un contact qui ne
 *      vous a pas écrit dans les 24h précédentes.
 *   5. WHATSAPP_ACCESS_TOKEN et WHATSAPP_PHONE_NUMBER_ID renseignés
 *      dans config/config.php.
 *   6. Le destinataire doit avoir donné son accord (opt-in) pour
 *      recevoir ces messages, conformément aux règles de Meta.
 *
 * Voir README.md pour la procédure complète, étape par étape.
 */
function send_whatsapp_template(string $to_e164, string $template_name, array $params = []): array
{
    if (WHATSAPP_ACCESS_TOKEN === '' || WHATSAPP_PHONE_NUMBER_ID === '') {
        return ['ok' => false, 'error' => 'WhatsApp API non configurée (voir config.php et README.md).'];
    }

    $url = 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';

    $components = [];
    if (!empty($params)) {
        $components[] = [
            'type'       => 'body',
            'parameters' => array_map(static fn($p) => ['type' => 'text', 'text' => (string)$p], $params),
        ];
    }

    $body = [
        'messaging_product' => 'whatsapp',
        'to'                => $to_e164,
        'type'              => 'template',
        'template'          => [
            'name'       => $template_name,
            'language'   => ['code' => 'fr'],
            'components' => $components,
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WHATSAPP_ACCESS_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_TIMEOUT    => 10,
    ]);
    $response   = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'ok'         => ($http_code >= 200 && $http_code < 300),
        'http_code'  => $http_code,
        'response'   => $response,
        'curl_error' => $curl_error,
    ];
}

function log_whatsapp(int $recipient_user_id, string $type, array $result): void
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO whatsapp_log (recipient_user_id, message_type, payload, status) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $recipient_user_id,
        $type,
        json_encode($result),
        $result['ok'] ? 'envoye' : 'echec',
    ]);
}

/** Notifie l'élève d'une nouvelle note. */
function notifier_nouvelle_note(array $student_user, string $matiere, string $valeur): void
{
    if (empty($student_user['phone_whatsapp'])) {
        return;
    }
    $result = send_whatsapp_template($student_user['phone_whatsapp'], 'nouvelle_note', [$matiere, $valeur]);
    log_whatsapp((int)$student_user['id'], 'nouvelle_note', $result);
}

/** Notifie un parent d'une absence ou d'un retard. */
function notifier_absence(array $parent_user, string $student_name, string $date, string $statut): void
{
    if (empty($parent_user['phone_whatsapp'])) {
        return;
    }
    $result = send_whatsapp_template($parent_user['phone_whatsapp'], 'alerte_absence', [$student_name, $date, $statut]);
    log_whatsapp((int)$parent_user['id'], 'alerte_absence', $result);
}
