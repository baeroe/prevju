<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $site->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body>
<main class="grid min-h-dvh place-items-center px-10 py-16">
    <form method="post" action="{{ route('site.unlock', $site->slug) }}" class="crop w-full max-w-sm border bg-sheet p-8">
        <span class="crop-mark" aria-hidden="true"></span>
        <span class="crop-mark" aria-hidden="true"></span>
        <span class="crop-mark" aria-hidden="true"></span>
        <span class="crop-mark" aria-hidden="true"></span>
        @csrf
        <p class="text-sm text-ink-muted">Entwurf zur Ansicht</p>
        <h1 class="mt-1 text-2xl leading-tight font-semibold tracking-tight text-balance break-words">{{ $site->name }}</h1>
        <div class="mt-8 grid gap-2">
            <label for="password" class="text-sm font-medium">Passwort</label>
            <input id="password" type="password" name="password" autocomplete="current-password" autofocus required
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                   class="h-10 w-full border border-hairline bg-sheet px-3 text-base hover:border-ink-muted focus-visible:border-ink aria-invalid:border-danger md:text-sm">
            @error('password') <p id="password-error" class="text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="mt-6 h-10 w-full cursor-pointer bg-signal px-4 text-sm font-medium text-signal-ink hover:bg-ink hover:text-sheet">Entwurf öffnen</button>
    </form>
</main>
</body>
</html>
