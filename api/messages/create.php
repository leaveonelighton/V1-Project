<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (lol_wants_json()) {
        lol_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    lol_render_shell('Send a Message', '<h1>Send a Message</h1><p>Please return to the <a href="/communicate/">message form</a>.</p>');
}

try {
    $pdo = lol_db();
    lol_enforce_rate_limit($pdo, 'create', 6, 3600);

    // Honeypot: humans should never fill this field.
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        throw new RuntimeException('Unable to submit this message.');
    }

    $startedAt = (int)($_POST['started_at'] ?? 0);
    if ($startedAt > 0 && (time() - $startedAt) < 2) {
        throw new RuntimeException('Please take a moment to review your message before sending.');
    }

    $allowedTopics = [
        'general' => 'General question',
        'correction' => 'Correction or source question',
        'partnership' => 'Partnership idea',
        'resource' => 'Resource question',
        'story' => 'Share something',
        'other' => 'Other',
    ];
    $allowedMethods = ['private', 'email', 'text', 'phone', 'none'];

    $topic = lol_text($_POST['topic'] ?? 'general', 40);
    if (!array_key_exists($topic, $allowedTopics)) {
        $topic = 'other';
    }

    $responseMethod = lol_text($_POST['response_method'] ?? 'private', 20);
    if (!in_array($responseMethod, $allowedMethods, true)) {
        $responseMethod = 'private';
    }

    $nickname = lol_text($_POST['nickname'] ?? '', 120);
    $message = lol_text($_POST['message'] ?? '', 4000);
    $contact = lol_text($_POST['contact'] ?? '', 240);

    if (strlen($message) < 10) {
        throw new RuntimeException('Please include a little more detail in your message.');
    }

    if ($responseMethod === 'email') {
        if (!filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address or choose another response method.');
        }
    } elseif (in_array($responseMethod, ['text', 'phone'], true)) {
        $digits = preg_replace('/\D+/', '', $contact);
        if (strlen((string)$digits) < 7 || strlen((string)$digits) > 15) {
            throw new RuntimeException('Please enter a valid phone number or choose another response method.');
        }
    } else {
        // Private website replies and no-reply messages do not require contact information.
        $contact = '';
    }

    $privateCode = lol_new_private_code();
    $secretHash = lol_code_hash($privateCode);
    $publicReference = lol_new_public_reference();
    $encryptedContact = lol_encrypt_contact($contact !== '' ? $contact : null);

    $pdo->beginTransaction();
    $insertConversation = $pdo->prepare(
        'INSERT INTO conversations
        (secret_hash, public_reference, topic, nickname, response_method, contact_ciphertext, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insertConversation->execute([
        $secretHash,
        $publicReference,
        $topic,
        $nickname !== '' ? $nickname : null,
        $responseMethod,
        $encryptedContact,
        'open',
    ]);
    $conversationId = (int)$pdo->lastInsertId();

    $insertMessage = $pdo->prepare(
        'INSERT INTO messages (conversation_id, sender, message_body) VALUES (?, ?, ?)'
    );
    $insertMessage->execute([$conversationId, 'visitor', $message]);
    lol_audit($pdo, $conversationId, 'conversation_created');
    $pdo->commit();

    $payload = [
        'ok' => true,
        'reference' => $publicReference,
        'private_code' => $privateCode,
        'response_method' => $responseMethod,
        'message' => $responseMethod === 'none'
            ? 'Your message was received. No response was requested.'
            : 'Your message was received. Save your private code so you can return to this conversation.',
    ];

    if (lol_wants_json()) {
        lol_json($payload, 201);
    }

    $codeBlock = $responseMethod === 'none'
        ? ''
        : '<div class="private-code-box"><p><strong>Your private response code</strong></p><p><code>'
            . lol_html_escape($privateCode)
            . '</code></p><p>Save this code. If you did not provide another contact method, it cannot be recovered if lost.</p>'
            . '<p><a class="button" href="/communicate/check-response.html">Check My Response</a></p></div>';

    lol_render_shell(
        'Message Received',
        '<p class="section-label">Message received</p><h1>Thank you for reaching out.</h1>'
        . '<p>Reference: <strong>' . lol_html_escape($publicReference) . '</strong></p>'
        . '<p>' . lol_html_escape($payload['message']) . '</p>'
        . $codeBlock
        . '<p><a href="/contact.html">Return to Contact</a></p>'
    );
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'We could not submit the message right now. Please try again later.';

    if (lol_wants_json()) {
        lol_json(['ok' => false, 'error' => $message], 400);
    }

    lol_render_shell(
        'Message Not Sent',
        '<h1>We could not send that message.</h1><p>' . lol_html_escape($message) . '</p>'
        . '<p><a class="button" href="/communicate/">Return to the form</a></p>'
    );
}
