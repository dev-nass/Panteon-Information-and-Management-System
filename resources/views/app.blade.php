<!DOCTYPE html>
<html class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @vite(['resources/css/app.css' ,'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="bg-white dark:bg-neutral-900 text-gray-800 dark:text-white transition-colors duration-300
        py-3">
        @inertia
    </body>
</html>
