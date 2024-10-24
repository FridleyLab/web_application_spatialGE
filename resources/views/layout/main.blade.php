<!--
=========================================================
* Material Dashboard 2 - v3.0.4
=========================================================

* Product Page: https://www.creative-tim.com/product/material-dashboard
* Copyright 2022 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (https://www.creative-tim.com/license)
* Coded by Creative Tim

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<!DOCTYPE html>
<html lang="en">

<head>


    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />


    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="/assets/img/logo-ct-moffitt.png">
    <title>
        {{ env('APP_NAME', 'spatialGE') }} {{ strlen(env('APP_LASTNAME', '')) ? ' - ' . env('APP_LASTNAME', '') : '' }}
    </title>
    <!--     Fonts and icons     -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <!-- Nucleo Icons -->
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <!-- CSS Files -->
    <link id="pagestyle" href="/assets/css/material-dashboard.css?v=3.0.4" rel="stylesheet" />

    <!--   Core JS Files   -->

    <script>
        window._token = "{{ csrf_token() }}";
    </script>

    @vite('resources/js/app.js')

    @yield('headers')

</head>

<body class="g-sidenav-show  bg-gray-200">
<section id="app">

    <div id="_body">

        <side-menu></side-menu>


        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

            @include('layout.partials.side-bar')

            @include('layout.partials.nav-bar')

            @yield('content')

        </main>
    </div>


    <show-modal-content></show-modal-content>


{{--    @include('layout.partials.ui-settings')--}}

</section>

</body>



</html>
