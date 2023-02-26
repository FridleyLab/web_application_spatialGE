<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   moffitt-bg-blue" id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href=" https://demos.creative-tim.com/material-dashboard/pages/dashboard " target="_blank">
{{--            <img src="/assets/img/logo-ct-moffitt.png" class="navbar-brand-img h-100" alt="main_logo">--}}
            <span class="ms-1 font-weight-bold text-white text-3xl">SpatialGE</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link text-white @if(url()->current() === route('home')) active bg-gradient-info @endif" href="{{ route('home') }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">science</i>
                    </div>
                    <span class="nav-link-text ms-1">About spatialGE</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white @if(url()->current() === route('how-to')) active bg-gradient-info @endif" href="{{ route('how-to') }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">flight</i>
                    </div>
                    <span class="nav-link-text ms-1">How to get started</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white @if(url()->current() === route('faq')) active bg-gradient-info @endif" href="{{ route('faq') }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">help</i>
                    </div>
                    <span class="nav-link-text ms-1">FAQ</span>
                </a>
            </li>
            @auth
                <li class="nav-item">
                    <a class="nav-link text-white @if(url()->current() === route('wizard')) active bg-gradient-info @endif" href="{{ route('wizard') }}">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">create_new_folder</i>
                        </div>
                        <span class="nav-link-text ms-1">New Project</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white @if(url()->current() === route('dashboard')) active bg-gradient-info @endif" href="{{ route('dashboard') }}">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">dashboard</i>
                        </div>
                        <span class="nav-link-text ms-1">Dashboard</span>
                    </a>
                </li>


{{--                <li class="nav-item">--}}
{{--                    <a class="nav-link text-white " href="../pages/tables.html">--}}
{{--                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">--}}
{{--                            <i class="material-icons opacity-10">table_view</i>--}}
{{--                        </div>--}}
{{--                        <span class="nav-link-text ms-1">My projects</span>--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--            --}}
{{--                <li class="nav-item">--}}
{{--                    <a class="nav-link text-white " href="../pages/notifications.html">--}}
{{--                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">--}}
{{--                            <i class="material-icons opacity-10">notifications</i>--}}
{{--                        </div>--}}
{{--                        <span class="nav-link-text ms-1">Notifications</span>--}}
{{--                    </a>--}}
{{--                </li>--}}


            @endauth
        </ul>
    </div>
    <!--    <div class="sidenav-footer position-absolute w-100 bottom-0 ">-->
    <!--      <div class="mx-3">-->
    <!--        <a class="btn bg-gradient-primary mt-4 w-100" href="https://www.creative-tim.com/product/material-dashboard-pro?ref=sidebarfree" type="button">Upgrade to pro</a>-->
    <!--      </div>-->
    <!--    </div>-->
</aside>