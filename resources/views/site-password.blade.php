<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $site->name }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #f4f4f5; color: #18181b; }
        form { background: #fff; padding: 2rem; border-radius: .75rem; box-shadow: 0 1px 3px rgb(0 0 0 / .1); width: min(90vw, 20rem); }
        h1 { font-size: 1.1rem; margin: 0 0 1rem; }
        input, button { width: 100%; box-sizing: border-box; font: inherit; padding: .6rem .75rem; border-radius: .5rem; border: 1px solid #d4d4d8; }
        button { margin-top: .75rem; background: #18181b; color: #fff; border-color: #18181b; cursor: pointer; }
        .error { color: #b91c1c; font-size: .9rem; margin: .5rem 0 0; }
    </style>
</head>
<body>
<form method="post" action="{{ route('site.unlock', $site->slug) }}">
    @csrf
    <h1>{{ $site->name }}</h1>
    <input type="password" name="password" placeholder="Passwort" autofocus required>
    @error('password') <p class="error">{{ $message }}</p> @enderror
    <button type="submit">Öffnen</button>
</form>
</body>
</html>
