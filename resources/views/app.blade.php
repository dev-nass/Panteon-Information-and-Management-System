<!DOCTYPE html>
<html class="dark scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" href="/images/dasmarinas-logo.png" />
    <link rel="apple-touch-icon" href="/images/dasmarinas-logo.png" />
    @routes <!-- Ziggy routes -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body
    class="bg-white dark:bg-neutral-900 text-gray-800 dark:text-white transition-colors duration-300 m-0 p-0 overflow-x-hidden">
    @inertia
</body>

</html>