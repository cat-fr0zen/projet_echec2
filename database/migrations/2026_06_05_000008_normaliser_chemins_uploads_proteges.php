<?php

declare(strict_types=1);

use App\Models\Article;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('media_publication')
            ->select(['identifiant', 'nom_fichier_stocke', 'chemin_public'])
            ->get() as $media) {
            $nomFichier = trim((string) ($media->nom_fichier_stocke ?? ''));

            if ($nomFichier === '') {
                continue;
            }

            $cheminProtege = 'fichiers/medias/'.rawurlencode($nomFichier);

            if ((string) ($media->chemin_public ?? '') === $cheminProtege) {
                continue;
            }

            DB::table('media_publication')
                ->where('identifiant', (string) $media->identifiant)
                ->update(['chemin_public' => $cheminProtege]);
        }

        foreach (DB::table('article_bloc')
            ->select(['identifiant_bloc', 'code_type', 'chemin_public'])
            ->whereIn('code_type', [Article::TYPE_BLOC_IMAGE, Article::TYPE_BLOC_VIDEO])
            ->get() as $bloc) {
            $nomFichier = basename((string) ($bloc->chemin_public ?? ''));

            if ($nomFichier === '') {
                continue;
            }

            $cheminProtege = 'fichiers/articles/'.rawurlencode($nomFichier);

            if ((string) ($bloc->chemin_public ?? '') === $cheminProtege) {
                continue;
            }

            DB::table('article_bloc')
                ->where('identifiant_bloc', (string) $bloc->identifiant_bloc)
                ->update(['chemin_public' => $cheminProtege]);
        }
    }

    public function down(): void
    {
        // Cette migration normalise des chemins legacy et n'a pas de rollback utile fiable.
    }
};
