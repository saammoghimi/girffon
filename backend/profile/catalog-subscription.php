<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/communication-common.php';

function girffonProfileCatalogSubscriptionDebugLog(array $context): void
{
    $logDirectory = dirname(__DIR__) . '/logs';
    if (!is_dir($logDirectory)) {
        @mkdir($logDirectory, 0777, true);
    }

    $line = json_encode([
        'timestamp' => date('c'),
        'email' => (string) ($context['email'] ?? ''),
        'user_id' => isset($context['user_id']) ? (int) $context['user_id'] : 0,
        'rows_affected' => isset($context['rows_affected']) ? (int) $context['rows_affected'] : 0,
        'subscriber_id' => isset($context['subscriber_id']) ? (int) $context['subscriber_id'] : 0,
        'error' => (string) ($context['error'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($line === false) {
        $line = '{"timestamp":"' . date('c') . '","error":"Unable to encode catalog subscription debug context."}';
    }

    @file_put_contents($logDirectory . '/catalog-subscription-debug.log', $line . PHP_EOL, FILE_APPEND);
}

function girffonProfileFetchCatalogSubscriptionId(PDO $pdo, string $email): int
{
    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '') {
        return 0;
    }

    try {
        $statement = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute([':email' => $normalizedEmail]);
        return (int) ($statement->fetchColumn() ?: 0);
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonProfileSaveCatalogSubscription(PDO $pdo, int $userId, string $email): array
{
    if (!girffonEnsureNewsletterSubscribersTable($pdo)) {
        return [
            'success' => false,
            'saved_to_newsletter_subscribers' => false,
            'subscriber_id' => 0,
            'email' => strtolower(trim($email)),
            'status' => '',
            'rows_affected' => 0,
            'error' => 'Unable to ensure newsletter_subscribers table.',
        ];
    }

    $columns = girffonProfileTableColumns($pdo, 'newsletter_subscribers');
    if ($columns === [] || !isset($columns['email'])) {
        return [
            'success' => false,
            'saved_to_newsletter_subscribers' => false,
            'subscriber_id' => 0,
            'email' => strtolower(trim($email)),
            'status' => '',
            'rows_affected' => 0,
            'error' => 'newsletter_subscribers is missing the required email column.',
        ];
    }

    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '') {
        return [
            'success' => false,
            'saved_to_newsletter_subscribers' => false,
            'subscriber_id' => 0,
            'email' => $normalizedEmail,
            'status' => '',
            'rows_affected' => 0,
            'error' => 'A valid email address is required.',
        ];
    }

    try {
        $existingId = girffonProfileFetchCatalogSubscriptionId($pdo, $normalizedEmail);

        if ($existingId > 0) {
            $assignments = [];
            $params = [
                ':id' => $existingId,
                ':email' => $normalizedEmail,
            ];

            if (isset($columns['user_id'])) {
                $assignments[] = 'user_id = :user_id';
                $params[':user_id'] = $userId > 0 ? $userId : null;
            }
            if (isset($columns['email'])) {
                $assignments[] = 'email = :email';
            }
            if (isset($columns['status'])) {
                $assignments[] = 'status = :status';
                $params[':status'] = 'subscribed';
            }
            if (isset($columns['source'])) {
                $assignments[] = 'source = :source';
                $params[':source'] = 'profile';
            }
            if (isset($columns['subscribed_at'])) {
                $assignments[] = 'subscribed_at = CURRENT_TIMESTAMP';
            }
            if (isset($columns['updated_at'])) {
                $assignments[] = 'updated_at = CURRENT_TIMESTAMP';
            }

            if ($assignments === []) {
                return [
                    'success' => false,
                    'saved_to_newsletter_subscribers' => false,
                    'subscriber_id' => $existingId,
                    'email' => $normalizedEmail,
                    'status' => '',
                    'rows_affected' => 0,
                    'error' => 'No writable newsletter_subscribers columns were found.',
                ];
            }

            $statement = $pdo->prepare('UPDATE newsletter_subscribers SET ' . implode(', ', $assignments) . ' WHERE id = :id');
            if (!$statement->execute($params)) {
                return [
                    'success' => false,
                    'saved_to_newsletter_subscribers' => false,
                    'subscriber_id' => $existingId,
                    'email' => $normalizedEmail,
                    'status' => '',
                    'rows_affected' => 0,
                    'error' => 'Unable to update the existing newsletter subscriber row.',
                ];
            }

            $savedSubscriberId = girffonProfileFetchCatalogSubscriptionId($pdo, $normalizedEmail);
            return [
                'success' => $savedSubscriberId > 0,
                'saved_to_newsletter_subscribers' => $savedSubscriberId > 0,
                'subscriber_id' => $savedSubscriberId,
                'email' => $normalizedEmail,
                'status' => 'subscribed',
                'rows_affected' => (int) $statement->rowCount(),
                'error' => $savedSubscriberId > 0 ? '' : 'Subscriber row could not be reloaded after update.',
            ];
        }

        $insertColumns = [];
        $insertValues = [];
        $params = [];

        if (isset($columns['user_id'])) {
            $insertColumns[] = 'user_id';
            $insertValues[] = ':user_id';
            $params[':user_id'] = $userId > 0 ? $userId : null;
        }

        $insertColumns[] = 'email';
        $insertValues[] = ':email';
        $params[':email'] = $normalizedEmail;

        if (isset($columns['status'])) {
            $insertColumns[] = 'status';
            $insertValues[] = ':status';
            $params[':status'] = 'subscribed';
        }

        if (isset($columns['source'])) {
            $insertColumns[] = 'source';
            $insertValues[] = ':source';
            $params[':source'] = 'profile';
        }

        if (isset($columns['subscribed_at'])) {
            $insertColumns[] = 'subscribed_at';
            $insertValues[] = 'CURRENT_TIMESTAMP';
        }

        if (isset($columns['updated_at'])) {
            $insertColumns[] = 'updated_at';
            $insertValues[] = 'CURRENT_TIMESTAMP';
        }

        $statement = $pdo->prepare(
            'INSERT INTO newsletter_subscribers (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')'
        );

        if (!$statement->execute($params)) {
            return [
                'success' => false,
                'saved_to_newsletter_subscribers' => false,
                'subscriber_id' => 0,
                'email' => $normalizedEmail,
                'status' => '',
                'rows_affected' => 0,
                'error' => 'Unable to insert the newsletter subscriber row.',
            ];
        }

        $savedSubscriberId = girffonProfileFetchCatalogSubscriptionId($pdo, $normalizedEmail);
        return [
            'success' => $savedSubscriberId > 0,
            'saved_to_newsletter_subscribers' => $savedSubscriberId > 0,
            'subscriber_id' => $savedSubscriberId,
            'email' => $normalizedEmail,
            'status' => 'subscribed',
            'rows_affected' => (int) $statement->rowCount(),
            'error' => $savedSubscriberId > 0 ? '' : 'Subscriber row could not be reloaded after insert.',
        ];
    } catch (PDOException $exception) {
        return [
            'success' => false,
            'saved_to_newsletter_subscribers' => false,
            'subscriber_id' => 0,
            'email' => $normalizedEmail,
            'status' => '',
            'rows_affected' => 0,
            'error' => $exception->getMessage(),
        ];
    }
}

$userId = girffonProfileCurrentUserId();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

$payload = girffonProfileRequestData();
$email = strtolower(trim((string) ($payload['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonProfileCatalogSubscriptionDebugLog([
        'email' => $email,
        'user_id' => $userId,
        'rows_affected' => 0,
        'subscriber_id' => 0,
        'error' => 'A valid email address is required.',
    ]);
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'A valid email address is required.',
    ]);
}

$user = $userId > 0 ? (girffonProfileFetchUserById($pdo, $userId) ?: []) : [];
$name = trim((string) ($user['name'] ?? trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')))));
if ($name === '') {
    $name = trim((string) ($user['username'] ?? 'GirffoN Member'));
}

$subscriptionResult = girffonProfileSaveCatalogSubscription($pdo, $userId, $email);

girffonProfileCatalogSubscriptionDebugLog([
    'email' => $email,
    'user_id' => $userId,
    'rows_affected' => (int) ($subscriptionResult['rows_affected'] ?? 0),
    'subscriber_id' => (int) ($subscriptionResult['subscriber_id'] ?? 0),
    'error' => (string) ($subscriptionResult['error'] ?? ''),
]);

if (empty($subscriptionResult['success'])) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to save the catalog subscription right now.',
        'saved_to_newsletter_subscribers' => false,
        'subscriber_id' => (int) ($subscriptionResult['subscriber_id'] ?? 0),
        'error' => (string) ($subscriptionResult['error'] ?? 'Unknown newsletter subscriber save error.'),
    ]);
}

girffonCommunicationLogAdminMessage(
    $pdo,
    $name,
    $email,
    'Newsletter Subscription',
    'Catalog subscription enabled from Profile. Email: ' . $email,
    'unread'
);

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => 'Catalog subscription saved successfully.',
    'saved_to_newsletter_subscribers' => !empty($subscriptionResult['saved_to_newsletter_subscribers']),
    'subscriber_id' => (int) ($subscriptionResult['subscriber_id'] ?? 0),
    'email' => (string) ($subscriptionResult['email'] ?? $email),
    'status' => (string) ($subscriptionResult['status'] ?? 'subscribed'),
    'subscriber' => girffonCommunicationFetchNewsletterSubscriber($pdo, $email),
]);
