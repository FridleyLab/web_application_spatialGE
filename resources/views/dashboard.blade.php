@extends('layout.main')


@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">weekend</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Total samples</p>
                            <h4 class="mb-0">57</h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <p class="mb-0"><span class="text-success text-sm font-weight-bolder">4 </span>projects <span class="text-success text-sm font-weight-bolder">27.4 </span>GB</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">person</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Samples for processing</p>
                            <h4 class="mb-0">16</h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <p class="mb-0"><span class="text-success text-sm font-weight-bolder">4.8 </span>GB</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">person</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Processing time (seconds)</p>
                            <h4 class="mb-0">79,200</h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <p class="mb-0"><span class="text-danger text-sm font-weight-bolder">22%</span> above average</p>
                    </div>
                </div>
            </div>
            <!--        <div class="col-xl-3 col-sm-6">-->
            <!--          <div class="card">-->
            <!--            <div class="card-header p-3 pt-2">-->
            <!--              <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">-->
            <!--                <i class="material-icons opacity-10">weekend</i>-->
            <!--              </div>-->
            <!--              <div class="text-end pt-1">-->
            <!--                <p class="text-sm mb-0 text-capitalize">Sales</p>-->
            <!--                <h4 class="mb-0">$103,430</h4>-->
            <!--              </div>-->
            <!--            </div>-->
            <!--            <hr class="dark horizontal my-0">-->
            <!--            <div class="card-footer p-3">-->
            <!--              <p class="mb-0"><span class="text-success text-sm font-weight-bolder">+5% </span>than yesterday</p>-->
            <!--            </div>-->
            <!--          </div>-->
            <!--        </div>-->
        </div>






        <graficos></graficos>







        <footer class="footer py-4  ">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-lg-between">
                    <div class="col-lg-6 mb-lg-0 mb-4">
                        <div class="copyright text-center text-sm text-muted text-lg-start">
                            © 2023
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
@endsection
