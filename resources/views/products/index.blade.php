@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">

                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name" placeholder="From"
                               class="form-control">
                        <input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th colspan="4">Variant</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody id="dataTable">

                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing 1 to 10 out of 100</p>
                </div>
                <div class="col-md-2">

                </div>
            </div>
        </div>
    </div>

@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<script>
    $(document).ready(function () {
        getDatatable();
    });

    function getSearchData() {
        return {};
    }

    function getTableRow(key, item) {
        let html = '';
        html += '<tr>';
        html += '<td>' + key + '</td>';
        html += '<td>' + item?.title + ' <br> Created at : ' + Math.floor(Math.abs(Date.now() - Date.parse(item.created_at)) / (60 * 60 * 1000)) + ' Hours ago</td>';
        html += '<td>' + item.description + '</td>';
        html += '<td>';
        html += '<dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">';

        html += '<dt class="col-sm-3 pb-0">';
        $.each(item?.variants, function (key, variant) {
            html += variant?.variant + '/';
        })
        html += "</dt>"
        html += '<dd class="col-sm-9">';
        html += '<dl class="row mb-0">';

        $.each(item?.product_variant_prices, function (key, price) {
            html += '<dt class="col-sm-6 pb-0">Price : ' + price?.price + '</dt>';
            html += '<dt class="col-sm-6 pb-0">InStock : ' + price?.stock + '</dt>';
        });

        html += '</dl>';
        html += '</dd>';
        html += '</dl>';

        html += '<button class="btn btn-sm btn-link show-more-variant-btn">';
        html += 'show more</button>';

        html += '</td>';
        html += '<td>';
        html += '<div class="btn-group btn-group-sm">';
        html += '<a href="{{ route('product.edit', "__") }}" class="btn btn-success">Edit</a>'.replace('__', item.id);
        html += '</div>';
        html += '</td>';
        html += '</tr>';

        return html;
    }

    function getDatatable() {
        let data = getSearchData();

        $.ajax({
            url: '{{ route('product-datatable') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', data: data},
        }).done(function (response) {
            const data = response?.data?.data;

            let html = '';
            $.each(data, function (key, value) {
                html += getTableRow(key + 1, value);
            })

            $('#dataTable').html(html);


            $('.show-more-variant-btn').on('click', function () {
                const variantEle = $(this).parent().find('#variant');
                variantEle.toggleClass('h-auto');

                let variantClass = variantEle.attr('class');
                if (variantClass.includes('h-auto')) {
                    $(this).html('show less');
                } else {
                    $(this).html('show more');
                }
            })

        }).catch(function (error) {
            console.log('error');
        }).always(function () {

        })
    }

</script>
