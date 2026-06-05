<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\NewsletterRepository;
use Illuminate\Http\RedirectResponse;

final class NewsletterController extends Controller
{
    public function unsubscribe(string $jeton): RedirectResponse
    {
        $depotNewsletter = new NewsletterRepository();
        $abonnement = $depotNewsletter->trouverParJetonDesabonnement($jeton);

        if ($abonnement === null) {
            ajouter_message_flash('error', 'Le lien de desabonnement est invalide ou a expire.');

            return redirect('/#footer-newsletter-title');
        }

        if (($abonnement['statut'] ?? '') === NewsletterRepository::STATUT_DESABONNE) {
            ajouter_message_flash('success', 'Cette adresse etait deja desabonnee de la newsletter.');

            return redirect('/#footer-newsletter-title');
        }

        $depotNewsletter->desabonner($jeton);
        ajouter_message_flash('success', 'Votre desabonnement a bien ete pris en compte.');

        return redirect('/#footer-newsletter-title');
    }
}
