<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_api_externe', function (Blueprint $table): void {
            $table->string('cle_cache', 180)->primary();
            $table->string('service_nom', 60);
            $table->longText('contenu');
            $table->timestamp('expire_le');
            $table->timestamp('mis_a_jour_le')->useCurrent();
            $table->index(['service_nom', 'expire_le'], 'ix_cache_api_service_expire');
        });

        Schema::create('newsletter_abonnement', function (Blueprint $table): void {
            $table->string('identifiant_abonnement', 50)->primary();
            $table->string('courriel', 254);
            $table->string('courriel_normalise', 254)->unique();
            $table->string('statut', 20)->default('actif');
            $table->string('jeton_desabonnement', 80)->unique();
            $table->string('consentement_version', 40);
            $table->string('adresse_ip_hachee', 128)->nullable();
            $table->string('agent_utilisateur', 255)->nullable();
            $table->string('source_inscription', 80)->default('footer');
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamp('confirme_le')->useCurrent();
            $table->timestamp('desabonne_le')->nullable();
            $table->index(['statut', 'cree_le'], 'ix_newsletter_statut');
        });

        Schema::create('newsletter_envoi', function (Blueprint $table): void {
            $table->string('identifiant_envoi', 60)->primary();
            $table->string('identifiant_abonnement', 50);
            $table->string('type_evenement', 30);
            $table->string('titre_evenement', 220);
            $table->string('url_evenement', 500)->nullable();
            $table->string('sujet', 220);
            $table->string('statut_envoi', 20);
            $table->string('erreur_envoi', 1000)->nullable();
            $table->timestamp('envoye_le')->useCurrent();

            $table->foreign('identifiant_abonnement')->references('identifiant_abonnement')->on('newsletter_abonnement');
            $table->index(['type_evenement', 'envoye_le'], 'ix_newsletter_envoi_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_envoi');
        Schema::dropIfExists('newsletter_abonnement');
        Schema::dropIfExists('cache_api_externe');
    }
};
