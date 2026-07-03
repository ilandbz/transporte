<!DOCTYPE html>
<html data-bs-theme="light" lang="es" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Shinhua Transportes') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <!-- Falcon simplebar (para scroll sidebar) -->
    <script src="/vendor/falcon/simplebar/simplebar.min.js"></script>

    <!-- Falcon config PRIMERO -->
    <script src="/vendor/falcon/js/config.js"></script>

    <!-- Falcon CSS -->
    <link href="/vendor/falcon/simplebar/simplebar.min.css" rel="stylesheet">
    <link href="/vendor/falcon/css/theme.min.css" rel="stylesheet" id="style-default">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body>
    @inertia

    <script src="/vendor/falcon/popper/popper.min.js"></script>
    <script src="/vendor/falcon/bootstrap/bootstrap.min.js"></script>
    <script src="/vendor/falcon/anchorjs/anchor.min.js"></script>
    <script src="/vendor/falcon/is/is.min.js"></script>
    <script src="/vendor/falcon/lodash/lodash.min.js"></script>
    <script src="/vendor/falcon/list.js/list.min.js"></script>
    <script src="/vendor/falcon/js/theme.js"></script>
    <script src="/vendor/falcon/js/flatpickr.js"></script>
</body>
</html>