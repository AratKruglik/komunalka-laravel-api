<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Komunalka</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function() {
            try {
                const cookieMatch = document.cookie.match(/(?:^|; )theme=([^;]*)/);
                const theme = cookieMatch ? decodeURIComponent(cookieMatch[1]) : 'light';
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const resolved = theme === 'system' ? (prefersDark ? 'dark' : 'light') : theme;
                document.documentElement.setAttribute('data-theme', resolved === 'dark' ? 'dark' : 'light');
            } catch (e) {}
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
    @routes
</head>
<body>
    @inertia
</body>
</html>
