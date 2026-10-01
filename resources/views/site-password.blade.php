<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body>
<main class="relative grid min-h-dvh place-items-center px-10 py-16">
    {{-- same markup as components/locale-switch.tsx; client pages keep their URLs, so the switch sets a cookie --}}
    <nav aria-label="{{ __('nav.language') }}" class="absolute top-4 right-6 flex text-sm">
        @foreach (['en' => ['EN', 'English'], 'de' => ['DE', 'Deutsch']] as $code => [$short, $label])
            <a href="{{ route('locale', $code) }}" lang="{{ $code }}" aria-label="{{ $label }}" @if (app()->getLocale() === $code) aria-current="true" @endif
               class="px-1.5 py-1 text-ink-muted hover:text-ink aria-[current]:font-medium aria-[current]:text-ink">{{ $short }}</a>
        @endforeach
    </nav>
    <form method="post" action="{{ $action }}" class="crop w-full max-w-sm border bg-sheet p-8">
        <span class="crop-mark" aria-hidden="true"></span>
        <span class="crop-mark" aria-hidden="true"></span>
        <span class="crop-mark" aria-hidden="true"></span>
        <span class="crop-mark" aria-hidden="true"></span>
        @csrf
        <p class="text-sm text-ink-muted">{{ $intro }}</p>
        <h1 class="mt-1 text-2xl leading-tight font-semibold tracking-tight text-balance break-words">{{ $name }}</h1>
        <div class="mt-8 grid gap-2">
            <label for="password" class="text-sm font-medium">{{ __('common.password') }}</label>
            <input id="password" type="password" name="password" autocomplete="current-password" autofocus required
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                   class="h-10 w-full border border-hairline bg-sheet px-3 text-base hover:border-ink-muted focus-visible:border-ink aria-invalid:border-danger md:text-sm">
            @error('password') <p id="password-error" class="text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="mt-6 h-10 w-full cursor-pointer bg-signal px-4 text-sm font-medium text-signal-ink hover:bg-ink hover:text-sheet">{{ $button }}</button>
    </form>
</main>
</body>
</html>
