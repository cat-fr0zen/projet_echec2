/*
    Migration 07 - Horaires administrables
*/

MERGE INTO horaire_club cible
USING (
    SELECT
        'club_schedule' schedule_id,
        'Horaires 2025/2026 - Club d''Echecs' season_label,
        'Les horaires peuvent etre adaptes les jours feries. Consultez l''emploi du temps complet avant de vous deplacer.' holiday_notice
    FROM dual
) source
ON (cible.schedule_id = source.schedule_id)
WHEN MATCHED THEN UPDATE SET
    cible.season_label = source.season_label,
    cible.holiday_notice = source.holiday_notice
WHEN NOT MATCHED THEN INSERT (
    schedule_id, season_label, holiday_notice
) VALUES (
    source.schedule_id, source.season_label, source.holiday_notice
);

MERGE INTO schema_migration cible
USING (SELECT '10g.0.7' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.7',
    'club_schedule',
    'schedule',
    'Horaires consultables et modifiables par l''administrateur.'
);

COMMIT;
