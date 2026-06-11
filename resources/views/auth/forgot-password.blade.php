<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublie</title>
    <link rel="stylesheet" href="{{ asset('assets/styles/style.css') }}">
</head>
<body class="theme-light">
    <main class="section-block" style="max-width: 44rem; margin: 3rem auto;">
        <div class="section-head">
            <p class="eyebrow">Espace membre</p>
            <h1>Reinitialiser le mot de passe</h1>
            <p>Saisis ton email ou ton numero de licence pour recevoir un lien de reinitialisation.</p>
        </div>

        @if (session('status'))
            <div class="auth-errors" role="status">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <label class="form-group">
                <span>Email ou numero de licence</span>
                <input type="text" name="identifiant_reinitialisation" value="{{ old('identifiant_reinitialisation') }}" required autocomplete="username">
                <small class="form-helper">Si plusieurs comptes partagent le meme email, la reinitialisation automatique n'est pas disponible.</small>
            </label>

            <div class="button-row">
                <button type="submit" class="button button-primary">Envoyer le lien</button>
                <a href="{{ url('/') }}" class="button button-secondary">Retour au site</a>
            </div>
        </form>
    </main>
</body>
</html>
