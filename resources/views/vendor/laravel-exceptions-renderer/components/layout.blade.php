@use('Illuminate\Foundation\Exceptions\Renderer\Renderer')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="data:,">
    {!! Renderer::css() !!}
</head>
<body class="font-sans antialiased overflow-x-hidden bg-neutral-50 dark:bg-neutral-900 dark:text-white scheme-light-dark">
    <div class="min-h-dvh">
        {{ $slot }}
    </div>
    {!! Renderer::js() !!}
</body>
</html>
