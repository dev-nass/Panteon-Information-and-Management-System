<!DOCTYPE html>
<html class="dark scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" href="/images/dasmarinas-logo.png" />
    <link rel="apple-touch-icon" href="/images/dasmarinas-logo.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bona+Nova:ital,wght@0,400;0,700;1,400&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" media="print" onload="this.media='all'" />
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bona+Nova:ital,wght@0,400;0,700;1,400&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" /></noscript>
    @routes <!-- Ziggy routes -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body
    class="bg-white dark:bg-neutral-900 text-gray-800 dark:text-white transition-colors duration-300 m-0 p-0 overflow-x-hidden">
    @inertia
</body>

</html>