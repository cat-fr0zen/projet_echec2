@php
    $errorCode = (string) ($code ?? '500');
    $errorLabel = (string) ($label ?? ('Erreur '.$errorCode));
    $errorTitle = (string) ($title ?? 'Vous etes en echec & mat.');
    $errorMessage = (string) ($message ?? "Une erreur est survenue pendant l'affichage de cette page.");
    $errorHint = (string) ($hint ?? "Reviens a l'accueil ou contacte le club si le probleme persiste.");
    $errorPrimaryUrl = (string) ($primaryUrl ?? url('/'));
    $errorPrimaryLabel = (string) ($primaryLabel ?? "Revenir a l'accueil");
    $errorSecondaryUrl = (string) ($secondaryUrl ?? url('/contact'));
    $errorSecondaryLabel = (string) ($secondaryLabel ?? 'Contacter le club');
    $logoPath = public_path('assets/media/divers/Logo_LCH2025.png');
    $logoUrl = url('assets/media/divers/Logo_LCH2025.png').'?v='.(string) @filemtime($logoPath);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="{{ $errorMessage }}">
    <title>{{ $errorLabel }} | Cavaliers d'Hérouville</title>
    <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap"
    >
    <link rel="stylesheet" href="{{ url('assets/styles/style.css') }}?v={{ (string) @filemtime(public_path('assets/styles/style.css')) }}">
</head>
<body data-theme="light">
    <div class="page-noise" aria-hidden="true"></div>
    <main class="page-shell error-shell">
        <section class="page-banner page-banner--error">
            <div class="error-page">
                <div class="error-page__visual" aria-hidden="true">
                    <img
                        class="error-page__logo"
                        src="{{ $logoUrl }}"
                        alt=""
                        width="394"
                        height="401"
                    >
                    <span class="error-page__badge">{{ $errorCode }}</span>
                </div>

                <div class="error-page__content">
                    <p class="eyebrow">{{ $errorLabel }}</p>
                    <h1>{!! $errorTitle !!}</h1>
                    <p class="error-page__lead">{{ $errorMessage }}</p>
                    <p class="error-page__hint">{{ $errorHint }}</p>

                    <div class="button-row">
                        <a class="button button-primary" href="{{ $errorPrimaryUrl }}">{{ $errorPrimaryLabel }}</a>
                        <a class="button button-secondary" href="{{ $errorSecondaryUrl }}">{{ $errorSecondaryLabel }}</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
