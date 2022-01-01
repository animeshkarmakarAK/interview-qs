@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="{{ route('product-datatable') }}" method="POST" class="card-header">
            @csrf
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control" value="{{ old('title') }}">
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
                        <th width="300px">Variant</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($products as $key => $product)

                        <tr>
                            <td>{{  $key + 1 }}</td>
                            <td>{{ $product->title }} <br> Created at : {{ $product->created_at->format('Y-m-d') }}</td>
                            <td>{{ $product->description }}</td>
                            <td>
                                <dl class="row mb-0 variant" style="height: 80px; overflow: hidden">

                                    <dt class="col-sm-3 pb-0">
                                        @foreach($product->variants as $variant)
                                            {{ $variant->variant }}
                                        @endforeach
                                    </dt>
                                    <dd class="col-sm-9">
                                        <dl class="row mb-0">
                                            @foreach($product->productVariantPrices as $variantPrice)
                                                <dt class="col-sm-6 pb-0">Price
                                                    : {{ number_format($variantPrice->price,2) }}</dt>
                                                <dd class="col-sm-6 pb-0">InStock
                                                    : {{ number_format($variantPrice->stock,2) }}</dd>
                                            @endforeach
                                        </dl>
                                    </dd>
                                </dl>
                                <button onclick="$('.variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show
                                    more
                                </button>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>


                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing 1 to 10 out of 100</p>
                </div>
                <div class="col-md-2 float-right pagination-sm" id="pagination">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<script>
    $(document).ready(function () {
        productSearch();

        $(document).on('click', '.pagination .page-link', function (e) {
            e.preventDefault();
            let url = $(this).attr('href');

            if (url) {
                let page = url.slice(url.indexOf('?') + 1);
                page = page.slice(-1);
                // getDatatable(page);
                productSearch(url);
            }
        });
    });

    function getSearchData(page) {
        if (page) {
            return {page: page};
        }

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

    const paginatorLinks = function (link) {
        console.log(link)
        if (link.label == 'pagination.previous') {
            link.label = 'Previous'
        }
        if (link.label == 'pagination.next') {
            link.label = 'Next'
        }
        let html = '';
        if (link.active) {
            html += '<li class="page-item active">' +
                '<a class="page-link">' + link.label + '</a>' +
                '</li>';
        } else if (!link.url) {
            html += '<li class="page-item">' +
                '<a class="page-link">' + link.label + '</a>' +
                '</li>';
        } else {
            html += '<li class="page-item"><a class="page-link" href="' + link.url + '">' + link.label + '</a></li>';
        }
        return html;
    }


    function addPagination(links) {
        let link_html = '<nav> <ul class="pagination">';
        if (links.length > 3) {
            $.each(links, function (i, link) {
                link_html += paginatorLinks(link);
            });
        }
        link_html += '</ul></nav>';
        $('#pagination').html(link_html);
    }

    const searchAPI = function ({model, columns}) {
        return function (url, filters = {}) {
            return $.ajax({
                url: url,
                type: "POST",
                data: {
                    _token: '{{csrf_token()}}',
                    resource: {
                        model: model,
                        columns: columns,
                        paginate: true,
                        page: 1,
                        per_page: 1,
                        filters,
                    }
                }
            }).done(function (response) {
                return response;
            });
        };
    };

    let baseUrl = '{{route('web-api.model-resources')}}';
    const productFetch = searchAPI({
        model: "{{base64_encode(\App\Models\Product::class)}}",
        columns: 'id|title|description|product_variants.variant'
    });

    function productSearch(url = baseUrl) {
        $('.overlay').show();
        let searchQuery = $('#search').val();
        let institute = $('#institute_id').val();
        let videoCategory = $('#video_category_id').val();
        const filters = {};
        if (searchQuery?.toString()?.length) {
            filters['title'] = {
                type: 'contain',
                value: searchQuery
            };
        }
        if (institute?.toString()?.length) {
            filters['institute_id'] = institute;
        }
        if (videoCategory?.toString()?.length) {
            filters['video_category_id'] = videoCategory;
        }

        productFetch(url, filters)?.then(function (response) {
            $('.overlay').hide();
            window.scrollTo(0, 0);
            let html = '';
            if (response?.data?.data.length <= 0) {
                html += '<div class="text-center mt-5" "><i class="fa fa-sad-tear fa-2x text-warning mb-3"></i><div class="text-center text-danger h3">{{__('generic.no_videos_found')}}</div>';
            }
            $.each(response.data?.data, function (i, item) {
                html += template(item);
            });

            $('#dataTable').html(html);

            let link_html = '<nav> <ul class="pagination">';
            let links = response?.data?.links;
            if (links.length > 3) {
                $.each(links, function (i, link) {
                    link_html += paginatorLinks(link);
                });
            }
            link_html += '</ul></nav>';
            $('#pagination').html(link_html);
        });
    }



    function getDatatable(page = null) {
        let data = getSearchData(page);

        $.ajax({
            url: '{{ route('product-datatable') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', data: data,},
        }).done(function (response) {
            let data = response?.data?.data;

            if (page) {
                data = response?.data;
            }

            let html = '';
            $.each(data, function (key, value) {
                html += getTableRow(key + 1, value);
            })

            $('#dataTable').html(html);
            console.log('links', response.links);

            $('#pagination').html(response?.links);

            // addPagination(response?.links);

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
