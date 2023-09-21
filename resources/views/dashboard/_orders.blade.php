{{--<h2>Order Summary</h2>--}}

<div class="row">
    <div class="col-lg-6 col-xl-4">
        <div class="widget-bg-color-icon card-box">
            <div class="bg-icon bg-icon-success pull-left">
                <i class="text-success">{{ $todays_orders->order_count }}</i>
            </div>
            <div class="text-right">
                <h3 class="text-dark"><b>{{ display_currency($todays_orders->subtotal) }}</b></h3>
                <p class="text-muted mb-0">Today's Order Value<br>
                    {{ \Carbon\Carbon::now()->format('m/d/Y') }}</p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="widget-bg-color-icon card-box fadeInDown animated">
            <div class="bg-icon bg-icon-primary pull-left">
                <i class="text-info">{{ $weeks_orders->order_count }}</i>
            </div>
            <div class="text-right">
                <h3 class="text-dark"><b>{{ display_currency($weeks_orders->subtotal) }}</b></h3>
                <p class="text-muted mb-0">This Weeks Order Value<br>
                    {{ \Carbon\Carbon::now()->startOfWeek()->format('m/d/Y') }} - {{ \Carbon\Carbon::now()->endOfWeek()->format('m/d/Y') }}</p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="col-lg-6 col-xl-4">
        <div class="widget-bg-color-icon card-box">
            <div class="bg-icon bg-icon-danger pull-left">
                <i class="text-pink">{{ $months_orders->order_count }}</i>
            </div>
            <div class="text-right">
                <h3 class="text-dark"><b>{{ display_currency($months_orders->subtotal) }}</b></h3>
                <p class="text-muted mb-0">This Month Order Value<br>
                    {{ \Carbon\Carbon::now()->startOfMonth()->format('m/d/Y') }} - {{ \Carbon\Carbon::now()->endOfMonth()->format('m/d/Y') }}</p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

</div>