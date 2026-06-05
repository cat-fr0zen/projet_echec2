<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouveau mot de passe</title>
    <link rel="stylesheet" href="{{ asset('assets/styles/style.css') }}">
</head>
<body class="theme-light">
    <main class="section-block" style="max-width: 44rem; margin: 3rem auto;">
        <div class="section-head">
            <p class="eyebrow">Sécurité</p>
            <h1>Choisir un nouveau mot de passe</h1>
            <p>Utilise un mot de passe d'au moins 8 caractères.</p>
        </div>

        @if ($errors->any())
            <div class="auth-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label class="form-group">
                <span>Adresse email</span>
                <input type="email" name="courriel" value="{{ old('courriel', $courriel) }}" required autocomplete="email">
            </label>

            <label class="form-group">
                <span>Nouveau mot de passe</span>
                <input type="password" name="mot_de_passe" required minlength="8" autocomplete="new-password">
            </label>

            <label class="form-group">
                <span>Confirmer le mot de passe</span>
                <input type="password" name="mot_de_passe_confirmation" required minlength="8" autocomplete="new-password">
            </label>

            <div class="button-row">
                <button type="submit" class="button button-primary">Mettre à jour le mot de passe</button>
                <a href="{{ url('/') }}" class="button button-secondary">Retour au site</a>
            </div>
        </form>
    </main>
</body>
</html>
