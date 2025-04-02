@anypermission('dashboard.revenue_summary', "dashboard.revenue_by_category", "dashboard.top_products_by_category", "dashboard.sales_rep_revenue_by_category")
<div class="row">
    <div class="col-12">
        <div class="card-box">
            @include('_partials._date_range')
        </div>
    </div>
</div>
@endanypermission

@permission('dashboard.revenue_summary')
<div class="row">
    <div class="col-12">
        <div class="card-box">

            <div class="row">
                <div class="col-12">
                    <canvas id="revenue-chart" height="400" aria-label="" role="img"></canvas>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <select id="chart_date" name="chart_date" class="form-control pull-right col-xl-1 col-3">
                        <option value="day">Daily</option>
                        <option value="week">Weekly</option>
                        <option value="month">Monthly</option>
                        <option value="quarter">Quarterly</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <!-- TOTALS if there are more than one location -->
    @if($sales_by_location->count() > 1)
    <div class="col-lg-3 col-md-6">
        <div class="widget-bg-color-icon card-box">
            <div class="bg-icon bg-icon-success pull-left">
                <i class="text-info" style="font-size: 22px">{{ $sales_by_location->sum('count') }}</i>
            </div>
            <div class="text-right">
                <p class="text-muted mb-0 font-16"><strong>Total</strong></p>
                <h3 class="text-success "><b class="counter">{{ display_currency($sales_by_location->sum('total'), 0) }}</b></h3>
                @if($sales_by_location->count())
                    <h5 class="text-">AOV: {{ display_currency($sales_by_location->sum('total') / $sales_by_location->sum('count'), 0) }}</h5>
                @endif
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
    @endif

    @foreach($sales_by_location as $location)

        <div class="col-lg-3 col-md-6">
            <div class="widget-bg-color-icon card-box">
                <div class="bg-icon bg-icon-info pull-left">
                    <i class="text-info" style="font-size: 22px">{{ $location->count }}</i>
                </div>
                <div class="text-right">
                    <p class="text-muted mb-0 font-16"><strong>{{ $location->name }}</strong></p>
                    <h3 class="text-dark "><b class="counter">{{ display_currency($location->total, 0) }}</b></h3>
                    @if($location->count())
                    <h5 class="text-">AOV: {{ display_currency($location->total / $location->count, 0) }}</h5>
                    @endif
                </div>
                <div class="clearfix"></div>
            </div>
        </div>

    @endforeach

</div>

@endpermission

<div class="row">

    @permission('dashboard.revenue_by_category')
    @include('dashboard._revenue_by_category')
    @endpermission

    @permission('dashboard.top_products_by_category')
    @include('dashboard._top_products_by_category')
    @endpermission

    @permission('dashboard.sales_rep_revenue_by_category')
    @include('dashboard._sales_rep_orders_by_category')
    @endpermission

</div>

@anypermission("dashboard.inventory_location", "dashboard.inventory_vendor")
<h3 class="text-dark  header-title m-t-0 m-b-30" style="font-size: 20px">Inventory Value: {{ display_currency($category_location_inventory->sum('location_value'), 0) }}</h3>
@endanypermission

@permission('dashboard.inventory_location')
@include('dashboard.partials._inventory_location')

@include('dashboard.partials._inventory_price_range')

@endpermission

@permission('dashboard.inventory_vendor')
@include('dashboard.partials._inventory_vendor')
@endpermission


@section('css')

    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css">

@endsection

@section('js')

    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

    <script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>

    <script src="{{ asset('plugins/moment/min/moment.min.js') }}"></script>
    <script src="//cdn.datatables.net/plug-ins/1.10.16/sorting/datetime-moment.js"></script>

    <script src="{{ asset('js/plugins/chart.min.js') }}"></script>

    <script type="text/javascript">

        $(document).ready(function() {

            var revChart = revenueChart();

            $('#chart_date').change(function(e) {

                getRevData($(this).find(":selected").val()).then(data => {
                    revChart.config.data.labels = Object.values(data.labels);
                    revChart.config.data.datasets = data.datasets;
                    revChart.options.plugins.title.text = $(this).find(":selected").text();
                    revChart.update();
                });

            });

            $('#chart_date').trigger('change');

            $.fn.dataTable.moment('MM/DD/YYYY');
            var table = $('#sales_by_category').DataTable({
                lengthChange: true,
                paging: false,
                searching: false,
                "order": [[ 1, "desc" ]],
                "displayLength": 25,
                buttons: [],
                columnDefs: [ {
                    "targets": [$('#sales_by_category thead tr').children('th').length - 1],
                    "orderable": true
                } ]
            });

        });

        async function getRevData(time) {
            const url = "/dashboard/revenue/"+time;
            const response = await fetch(url);

            if(response.ok) {
                const data = await response.json();
                return data;
            }
        }

        function sendRequest(from, to, custom) {
            var url = window.location.href.split('?')[0];
            window.location = url+'?'+(custom?'preset=custom&':'')+'from=' + from + '&to=' + to;
        }

        function revenueChart()
        {
            const ctx = $('#revenue-chart');
            const dataset = {
                labels: [],
                datasets: [{
                    label: "",
                    fill: false,
                    data: [],
                    spanGaps: false
                }]
            }
            const config = {
                type: 'line',
                data: dataset,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: ''
                        }
                    },
                    scales: {
                        x: {
                            offset: true
                        }
                    }
                },
            };
            return new Chart(ctx, config);
        }
    </script>
@endsection
