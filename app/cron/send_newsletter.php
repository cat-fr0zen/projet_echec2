<?php
/**
 * Cron o2switch : envoi newsletter par petits lots.
 */

declare(strict_types=1);

use App\Mail\NewsletterActualiteMail;

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/mailer.php';

$pdo = o2switch_pdo();
$config = o2switch_app_config();
$batchSize = max(1, min(20, (int) ($config['newsletter_batch_size'] ?? 20)));
$maintenant = date('Y-m-d H:i:s');

$pdo->beginTransaction();

$selection = $pdo->prepare(
    "SELECT queue_id
     FROM newsletter_queue
     WHERE status = 'pending'
       AND available_at <= :maintenant
     ORDER BY created_at ASC
     LIMIT {$batchSize}
     FOR UPDATE"
);
$selection->execute([':maintenant' => $maintenant]);
$ids = array_map(
    static fn (array $ligne): string => (string) ($ligne['queue_id'] ?? ''),
    $selection->fetchAll()
);
$ids = array_values(array_filter($ids));

if ($ids === []) {
    $pdo->commit();
    exit(0);
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$claim = $pdo->prepare(
    "UPDATE newsletter_queue
     SET status = 'processing', locked_at = ?, updated_at = ?
     WHERE queue_id IN ({$placeholders})"
);
$claim->execute([...[$maintenant, $maintenant], ...$ids]);
$pdo->commit();

$lecture = $pdo->prepare(
    "SELECT *
     FROM newsletter_queue
     WHERE queue_id IN ({$placeholders})
     ORDER BY created_at ASC"
);
$lecture->execute($ids);
$lignes = $lecture->fetchAll();

foreach ($lignes as $ligne) {
    $queueId = (string) ($ligne['queue_id'] ?? '');
    $abonnementId = (string) ($ligne['newsletter_abonnement_id'] ?? '');
    $destinataire = trim((string) ($ligne['recipient_email'] ?? ''));
    $jeton = trim((string) ($ligne['unsubscribe_token'] ?? ''));
    $sujet = (string) ($ligne['subject'] ?? '');
    $titre = (string) ($ligne['title'] ?? '');
    $message = (string) ($ligne['message_text'] ?? '');
    $urlEvenement = (string) ($ligne['event_url'] ?? '');
    $tentatives = (int) ($ligne['attempt_count'] ?? 0);

    if ($queueId === '' || ! filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
        continue;
    }

    if ($abonnementId !== '') {
        $verifAbonne = $pdo->prepare('SELECT statut FROM newsletter_abonnement WHERE identifiant_abonnement = ? LIMIT 1');
        $verifAbonne->execute([$abonnementId]);
        $statutAbonne = (string) ($verifAbonne->fetchColumn() ?: '');

        if ($statutAbonne !== 'actif') {
            $annuler = $pdo->prepare("UPDATE newsletter_queue SET status = 'cancelled', last_error = 'abonne_inactif', updated_at = ? WHERE queue_id = ?");
            $annuler->execute([$maintenant, $queueId]);
            continue;
        }
    }

    $lienDesabonnement = rtrim((string) ($config['newsletter_public_base_url'] ?? $config['app_url'] ?? ''), '/')
        . '/newsletter/desabonnement/' . rawurlencode($jeton);

    try {
        o2switch_send_laravel_mailable(
            $destinataire,
            new NewsletterActualiteMail(
                $sujet,
                $titre,
                trim($message),
                $urlEvenement,
                $lienDesabonnement
            )
        );

        $miseAJour = $pdo->prepare("UPDATE newsletter_queue SET status = 'sent', sent_at = ?, last_error = NULL, attempt_count = ?, updated_at = ? WHERE queue_id = ?");
        $miseAJour->execute([$maintenant, $tentatives + 1, $maintenant, $queueId]);

        if ($abonnementId !== '') {
            $journal = $pdo->prepare(
                'INSERT INTO newsletter_envoi
                (identifiant_envoi, identifiant_abonnement, type_evenement, titre_evenement, url_evenement, sujet, statut_envoi, erreur_envoi, envoye_le)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $journal->execute([
                'newsletter_envoi_' . bin2hex(random_bytes(8)),
                $abonnementId,
                (string) ($ligne['event_type'] ?? 'newsletter'),
                $titre,
                $urlEvenement,
                $sujet,
                'envoye',
                null,
                $maintenant,
            ]);
        }
    } catch (Throwable $exception) {
        $prochaineDisponibilite = date('Y-m-d H:i:s', time() + 900);
        $statut = $tentatives + 1 >= 3 ? 'failed' : 'pending';
        $miseAJour = $pdo->prepare(
            "UPDATE newsletter_queue
             SET status = ?, attempt_count = ?, last_error = ?, available_at = ?, updated_at = ?
             WHERE queue_id = ?"
        );
        $miseAJour->execute([
            $statut,
            $tentatives + 1,
            substr($exception->getMessage(), 0, 1000),
            $prochaineDisponibilite,
            $maintenant,
            $queueId,
        ]);
    }
}
