<?php
/**
 * Cache base de donnees pour API externes.
 */

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ApiCacheRepository
{
    private ?string $table = null;

    /**
     * @return array<string, mixed>|null
     */
    public function lire(string $service, string $cle): ?array
    {
        $table = $this->resoudreTable();

        if ($table === null) {
            return null;
        }

        try {
            if ($table === 'api_cache') {
                $ligne = DB::table($table)
                    ->where('cache_key', $cle)
                    ->where('service_name', $service)
                    ->first();

                if ($ligne === null || strtotime((string) ($ligne->expires_at ?? '')) < time()) {
                    return null;
                }

                $payload = json_decode((string) ($ligne->payload_json ?? ''), true);

                return is_array($payload) ? $payload : null;
            }

            $ligne = DB::table($table)
                ->where('cle_cache', $cle)
                ->where('service_nom', $service)
                ->first();

            if ($ligne === null || strtotime((string) ($ligne->expire_le ?? '')) < time()) {
                return null;
            }

            $payload = json_decode((string) ($ligne->contenu ?? ''), true);

            return is_array($payload) ? $payload : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $metadata
     */
    public function ecrire(
        string $service,
        string $cle,
        array $payload,
        int $ttlSecondes,
        int $codeStatut = 200,
        ?array $metadata = null
    ): void {
        $table = $this->resoudreTable();

        if ($table === null) {
            return;
        }

        $expiration = date('Y-m-d H:i:s', time() + max(1, $ttlSecondes));

        try {
            if ($table === 'api_cache') {
                DB::table($table)->updateOrInsert(
                    [
                        'cache_key' => $cle,
                    ],
                    [
                        'service_name' => $service,
                        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                        'http_status' => $codeStatut,
                        'metadata_json' => $metadata !== null
                            ? (json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                            : null,
                        'expires_at' => $expiration,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

                return;
            }

            DB::table($table)->updateOrInsert(
                [
                    'cle_cache' => $cle,
                ],
                [
                    'service_nom' => $service,
                    'contenu' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                    'expire_le' => $expiration,
                    'mis_a_jour_le' => date('Y-m-d H:i:s'),
                ]
            );
        } catch (Throwable) {
            // Le cache ne doit jamais casser l'affichage public.
        }
    }

    public function purgerExpires(): void
    {
        $table = $this->resoudreTable();

        if ($table === null) {
            return;
        }

        try {
            if ($table === 'api_cache') {
                DB::table($table)->where('expires_at', '<', date('Y-m-d H:i:s'))->delete();

                return;
            }

            DB::table($table)->where('expire_le', '<', date('Y-m-d H:i:s'))->delete();
        } catch (Throwable) {
            // Ignore.
        }
    }

    private function resoudreTable(): ?string
    {
        if ($this->table !== null) {
            return $this->table;
        }

        try {
            if (Schema::hasTable('api_cache')) {
                return $this->table = 'api_cache';
            }

            if (Schema::hasTable('cache_api_externe')) {
                return $this->table = 'cache_api_externe';
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
