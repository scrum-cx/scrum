{{-- VIEW: public landing page. Shown by HomeController@index to guests only. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Scrum') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col items-center justify-center px-6">
            <h1 class="text-4xl font-semibold">Scrum</h1>
            <p class="mt-3 max-w-xl text-center text-gray-600">
                Teams, projects and tasks in one place. Plan work on a
                To Do / Doing / Done board, meet, share files and keep track of who did what.
            </p>
            <div class="mt-8 flex gap-3">
                <a href="{{ route('login') }}"><x-primary-button type="button">Log in</x-primary-button></a>
                <a href="{{ route('register') }}"><x-secondary-button type="button">Register</x-secondary-button></a>
            </div>
        </div>
    </body>
</html>
