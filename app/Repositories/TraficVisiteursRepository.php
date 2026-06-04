<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class TraficVisiteursRepository
{
    public function enregistrerVisitePublique(string $page): void
    {
        $pageNormalisee = trim($page);

        if ($pageNormalisee === '') {
            return;
        }

        $sessionId = trim((string) session()->getId());
        $adresseIp = trim((string) request()->ip());
        $agentUtilisateur = $this->nettoyerAgentUtilisateur((string) request()->userAgent());
        $hoteReferent = $this->extraireHoteReferent((string) request()->headers->get('referer', ''));

        try {
            DB::table('journal_visite_visiteur')->insert([
                'identifiant_visite' => 'visite_' . bin2hex(random_bytes(8)),
                'page' => $pageNormalisee,
                'hachage_session' => $this->hacherValeur($sessionId),
                'hachage_ip' => $this->hacherValeur($adresseIp),
                'hote_referent' => $hoteReferent,
                'agent_utilisateur' => $agentUtilisateur,
                'visite_le' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenirResumeAdmin(?DateTimeImmutable $reference = null): array
    {
        try {
            $maintenant = $reference ?? new DateTimeImmutable('now');
            $debutJour = $maintenant->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $debutSemaineGlissante = $maintenant->modify('-6 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $debutMoisGlissant = $maintenant->modify('-29 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s');

            $visitesAujourdhui = (int) DB::table('journal_visite_visiteur')
                ->where('visite_le', '>=', $debutJour)
                ->count();

            $visitesSeptJours = (int) DB::table('journal_visite_visiteur')
                ->where('visite_le', '>=', $debutSemaineGlissante)
                ->count();

            $visiteursUniquesSeptJours = (int) (
                DB::table('journal_visite_visiteur')
                    ->where('visite_le', '>=', $debutSemaineGlissante)
                    ->selectRaw('COUNT(DISTINCT hachage_session) as total')
                    ->value('total') ?? 0
            );

            $pagesPopulaires = array_map(
                static fn (object $row): array => [
                    'page' => (string) ($row->page ?? ''),
                    'total' => (int) ($row->total ?? 0),
                ],
                DB::table('journal_visite_visiteur')
                    ->selectRaw('page, COUNT(*) as total')
                    ->where('visite_le', '>=', $debutMoisGlissant)
                    ->groupBy('page')
                    ->orderByDesc('total')
                    ->orderBy('page')
                    ->limit(5)
                    ->get()
                    ->all()
            );

            $dernieresVisites = array_map(
                static fn (object $row): array => [
                    'page' => (string) ($row->page ?? ''),
                    'hote_referent' => (string) ($row->hote_referent ?? ''),
                    'visite_le' => (string) ($row->visite_le ?? ''),
                ],
                DB::table('journal_visite_visiteur')
                    ->select(['page', 'hote_referent', 'visite_le'])
                    ->orderByDesc('visite_le')
                    ->limit(8)
                    ->get()
                    ->all()
            );

            return [
                'visites_aujourdhui' => $visitesAujourdhui,
                'visites_7_jours' => $visitesSeptJours,
                'visiteurs_uniques_7_jours' => $visiteursUniquesSeptJours,
                'pages_populaires' => $pagesPopulaires,
                'dernieres_visites' => $dernieresVisites,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return $this->resumeVide();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resumeVide(): array
    {
        return [
            'visites_aujourdhui' => 0,
            'visites_7_jours' => 0,
            'visiteurs_uniques_7_jours' => 0,
            'pages_populaires' => [],
            'dernieres_visites' => [],
        ];
    }

    private function hacherValeur(string $valeur): string
    {
        if ($valeur === '') {
            return '';
        }

        $sel = trim((string) (env('VISITOR_TRAFFIC_SALT', env('APP_KEY', 'visiteur-local'))));

        return hash('sha256', $sel . '|' . $valeur);
    }

    private function extraireHoteReferent(string $referent): ?string
    {
        $hote = parse_url(trim($referent), PHP_URL_HOST);

        if (!is_string($hote) || trim($hote) === '') {
            return null;
        }

        return substr(trim($hote), 0, 120);
    }

    private function nettoyerAgentUtilisateur(string $agentUtilisateur): string
    {
        $agentNettoye = trim(strip_tags($agentUtilisateur));

        return function_exists('mb_substr')
            ? mb_substr($agentNettoye, 0, 255)
            : substr($agentNettoye, 0, 255);
    }
}
