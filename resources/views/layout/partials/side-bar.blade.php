<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   moffitt-bg-blue" id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="/">
{{--            <img src="/assets/img/logo-ct-moffitt.png" class="navbar-brand-img h-100" alt="main_logo">--}}
            <span class="ms-1 font-weight-bold {{ app()->isProduction() ? 'text-3xl text-white' : 'text-2xl text-danger' }}">{{ env('APP_NAME', 'spatialGE') }}<span class="ms-1 text-md text-warning">{{ env('APP_LASTNAME', '') }}</span></span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link text-white @if(url()->current() === route('home')) active bg-gradient-info @endif" href="{{ route('home') }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">biotech</i>
                    </div>
                    <span class="nav-link-text ms-1">About spatialGE</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white @if(url()->current() === route('how-to')) active bg-gradient-info @endif" href="{{ route('how-to') }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">map</i>
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
            <li class="nav-item">
                <a class="nav-link text-white @if(url()->current() === route('contact-us')) active bg-gradient-info @endif" href="{{ route('contact-us') }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">contact_mail</i>
                    </div>
                    <span class="nav-link-text ms-1">Contact us</span>
                </a>
            </li>
            @auth
                <li class="nav-item">
                    <a class="nav-link text-white @if(url()->current() === route('my-projects')) active bg-gradient-info @endif" href="{{ route('my-projects') }}">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">format_list_numbered</i>
                        </div>
                        <span class="nav-link-text ms-1">My Projects</span>
                    </a>
                </li>

                <div>
                    @if(getActiveProjectId())
                        <li class="nav-item ps-4 pt-2">
                            <div class="ms-2 text-white me-2 d-flex align-items-center">
                                <span class="btn btn-sm btn-tag btn-rounded bg-white">{{ getShortProjectName(getActiveProject()) }}</span>
                            </div>
                        </li>

                        <li class="nav-item ps-2">
                            <a class="nav-link text-white @if(url()->current() === route('import-data', ['project' => session('project_id')])) active bg-gradient-info @endif" href="{{ route('import-data', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i class="material-icons opacity-10">filter_1</i>
                                </div>
                                <span class="nav-link-text ms-1 text-xs text-bold">Import data</span>
                            </a>
                        </li>

                        <li class="nav-item ps-2">
                            <a class="nav-link text-white @if(url()->current() === route('qc-data-transformation', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 2) disabled @endif" style="@if(getActiveProject()->current_step < 2) background-color: transparent !important @endif" href="{{ route('qc-data-transformation', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i class="material-icons opacity-10 @if(getActiveProject()->current_step < 2) text-secondary @endif">filter_2</i>
                                </div>
                                <span class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 2) text-secondary @endif text-bold">QC & data transformation</span>
                            </a>
                        </li>

                        <li class="nav-item ps-2">
                            <a id="stplot-visualization-a" class="nav-link text-white @if(url()->current() === route('stplot-visualization', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 3) disabled @endif" style="@if(getActiveProject()->current_step < 3) background-color: transparent !important @endif" href="{{ route('stplot-visualization', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="stplot-visualization-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 3) text-secondary @endif">filter_3</i>
                                </div>
                                <span id="stplot-visualization-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 3) text-secondary @endif text-bold">Visualization</span>
                            </a>
                        </li>

                        <li class="nav-item ps-2">
                            <a id="sthet-spatial-het-a" class="nav-link text-white @if(url()->current() === route('sthet-spatial-het', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 4) disabled @endif" style="@if(getActiveProject()->current_step < 4) background-color: transparent !important @endif" href="{{ route('sthet-spatial-het', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="sthet-spatial-het-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 4) text-secondary @endif">filter_4</i>
                                </div>
                                <span id="sthet-spatial-het-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 4) text-secondary @endif text-bold">Spatially variable genes</span>
                            </a>
                        </li>



                        <li class="nav-item ps-2">
                            <a id="spatial-domain-detection-a" class="nav-link text-white @if(url()->current() === route('spatial-domain-detection', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 6) disabled @endif" style="@if(getActiveProject()->current_step < 5) background-color: transparent !important @endif" href="{{ route('spatial-domain-detection', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="spatial-domain-detection-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 6) text-secondary @endif">filter_5</i>
                                </div>
                                <span id="spatial-domain-detection-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 6) text-secondary @endif text-bold">Spatial domain detection</span>
                            </a>
                        </li>



                        <li class="nav-item ps-2">
                            <a id="phenotyping-a" class="nav-link text-white @if(url()->current() === route('phenotyping', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) disabled @endif" style="@if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) background-color: transparent !important @endif" href="{{ route('phenotyping', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="phenotyping-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) text-secondary @endif">filter_6</i>
                                </div>
                                <span id="phenotyping-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) text-secondary @endif text-bold">Phenotyping</span>
                            </a>
                        </li>


                        <li class="nav-item ps-2">
                            <a id="differential-expression-a" class="nav-link text-white @if(url()->current() === route('differential-expression', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 7) disabled @endif" style="@if(getActiveProject()->current_step < 6) background-color: transparent !important @endif" href="{{ route('differential-expression', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="differential-expression-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 7) text-secondary @endif">filter_7</i>
                                </div>
                                <span id="differential-expression-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 7) text-secondary @endif text-bold">Differential expression</span>
                            </a>
                        </li>

                        <li class="nav-item ps-2">
                            <a id="stenrich-a" class="nav-link text-white @if(url()->current() === route('spatial-gene-set-enrichment', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 5) disabled @endif" style="@if(getActiveProject()->current_step < 4) background-color: transparent !important @endif" href="{{ route('spatial-gene-set-enrichment', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="stenrich-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 5) text-secondary @endif">filter_8</i>
                                </div>
                                <span id="stenrich-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 5) text-secondary @endif text-bold">Spatial gene set enrichment</span>
                            </a>
                        </li>

                        <li class="nav-item ps-2">
                            <a id="spatial-gradients-a" class="nav-link text-white @if(url()->current() === route('spatial-gradients', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 8) disabled @endif" style="@if(getActiveProject()->current_step < 7) background-color: transparent !important @endif" href="{{ route('spatial-gradients', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="spatial-gradients-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 8) text-secondary @endif">filter_9</i>
                                </div>
                                <span id="spatial-gradients-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 8) text-secondary @endif text-bold">Spatial gradients</span>
                            </a>
                        </li>



                        <li class="nav-item ps-2">
                            <a id="degas-a" class="nav-link text-white @if(url()->current() === route('degas', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) disabled @endif" style="@if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) background-color: transparent !important @endif" href="{{ route('degas', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <svg id="degas-i" class="opacity-10 @if(getActiveProject()->current_step < 6) text-secondary @endif" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M2 5c-.55 0-1 .45-1 1v15c0 1.1.9 2 2 2h15c.55 0 1-.45 1-1s-.45-1-1-1H4c-.55 0-1-.45-1-1V6c0-.55-.45-1-1-1zm19-4H7c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 16H8c-.55 0-1-.45-1-1V4c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1z"/><text x="14" y="13.5" text-anchor="middle" font-family="Roboto, Arial, sans-serif" font-size="10" font-weight="500" fill="currentColor">10</text></svg>
                                </div>
                                <span id="degas-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) text-secondary @endif text-bold">DEGAS</span>
                            </a>
                        </li>

                        @if(in_array(getActiveProject()->platform_name, ['VISIUM', 'GENERIC']))
                        <li class="nav-item ps-2">
                            <a id="calicost-a" class="nav-link text-white @if(url()->current() === route('calicost', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) disabled @endif" style="@if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) background-color: transparent !important @endif" href="{{ route('calicost', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <svg id="calicost-i" class="opacity-10 @if(getActiveProject()->current_step < 6) text-secondary @endif" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M2 5c-.55 0-1 .45-1 1v15c0 1.1.9 2 2 2h15c.55 0 1-.45 1-1s-.45-1-1-1H4c-.55 0-1-.45-1-1V6c0-.55-.45-1-1-1zm19-4H7c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 16H8c-.55 0-1-.45-1-1V4c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1z"/><text x="14" y="13.5" text-anchor="middle" font-family="Roboto, Arial, sans-serif" font-size="10" font-weight="500" fill="currentColor">11</text></svg>
                                </div>
                                <span id="calicost-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 6 /*|| getActiveProject()->platform_name === 'COSMX'*/) text-secondary @endif text-bold">CalicoST</span>
                            </a>
                        </li>
                        @endif

                        @if(str_contains(request()->fullUrl(), 'localhost') || str_contains(request()->fullUrl(), 'spatialgedev'))
                        <li class="nav-item ps-2">
                            <a id="cell-cell-interaction-a" class="nav-link text-white @if(url()->current() === route('cell-cell-interaction', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 6) disabled @endif" style="@if(getActiveProject()->current_step < 6) background-color: transparent !important @endif" href="{{ route('cell-cell-interaction', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <svg id="cell-cell-interaction-i" class="opacity-10 @if(getActiveProject()->current_step < 6) text-secondary @endif" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M2 5c-.55 0-1 .45-1 1v15c0 1.1.9 2 2 2h15c.55 0 1-.45 1-1s-.45-1-1-1H4c-.55 0-1-.45-1-1V6c0-.55-.45-1-1-1zm19-4H7c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 16H8c-.55 0-1-.45-1-1V4c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1z"/><text x="14" y="13.5" text-anchor="middle" font-family="Roboto, Arial, sans-serif" font-size="10" font-weight="500" fill="currentColor">12</text></svg>
                                </div>
                                <span id="cell-cell-interaction-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 6) text-secondary @endif text-bold">Cell-cell interaction</span>
                            </a>
                        </li>
                        @endif


                        {{-- @if(app()->isLocal())
                        <li class="nav-item ps-2">
                            <a id="sparkx-a" class="nav-link text-white @if(url()->current() === route('sparkx', ['project' => session('project_id')])) active bg-gradient-info @endif @if(getActiveProject()->current_step < 3) disabled @endif" style="@if(getActiveProject()->current_step < 3) background-color: transparent !important @endif" href="{{ route('sparkx', ['project' => session('project_id')]) }}">
                                <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i id="sparkx-i" class="material-icons opacity-10 @if(getActiveProject()->current_step < 3) text-secondary @endif"></i>
                                </div>
                                <span id="sparkx-span" class="nav-link-text ms-1 text-xs @if(getActiveProject()->current_step < 3) text-secondary @endif text-bold">SPARK-X</span>
                            </a>
                        </li>
                        @endif --}}

                    @endif
                </div>








{{--                <li class="nav-item">--}}
{{--                    <a class="nav-link text-white @if(url()->current() === route('new-project')) active bg-gradient-info @endif" href="{{ route('new-project') }}">--}}
{{--                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">--}}
{{--                            <i class="material-icons opacity-10">create_new_folder</i>--}}
{{--                        </div>--}}
{{--                        <span class="nav-link-text ms-1">New Project</span>--}}
{{--                    </a>--}}
{{--                </li>--}}


{{--                <li class="nav-item">--}}
{{--                    <a class="nav-link text-white @if(url()->current() === route('dashboard')) active bg-gradient-info @endif" href="{{ route('dashboard') }}">--}}
{{--                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">--}}
{{--                            <i class="material-icons opacity-10">dashboard</i>--}}
{{--                        </div>--}}
{{--                        <span class="nav-link-text ms-1">Dashboard</span>--}}
{{--                    </a>--}}
{{--                </li>--}}


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
