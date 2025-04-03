<div class="col-lg-5">

    <div class="card-box">
        <h4 class="text-dark  header-title m-t-0 m-b-30">Sales By Category</h4>

        <div class="row">
            <div class="col-12">
                <table class="table" id="sales_by_category">
                    <thead>
                    <tr>
                        <th>Category</th>
                        <th>Sales</th>
                    </tr>
                    </thead>

                    <tbody>
                        @foreach($category_sales as $category_sale)
                            <tr>
                                <td>{{ $category_sale->name }}</td>
                                <td>{{ display_currency($category_sale->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</div>
