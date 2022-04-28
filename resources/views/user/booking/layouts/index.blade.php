<!DOCTYPE html>
<html lang="UTF-8">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('static/user/css/reset.css') }}" media="all">
    <link rel="stylesheet" href="{{ asset('static/user/css/style.css') }}" media="all">
    <link rel="stylesheet" href="{{ asset('static/user/css/search_panel.css') }}" media="all">


    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('static/user/slick/slick-theme.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('static/user/slick/slick.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/user/booking.css') }}">
    <title>{{ !empty($title) ? $title : ''  }}</title>
</head>

<body>
    <div
        id="user__booking__headerMenu"
        data-current_page="{{ $currentPage ?? '' }}"
        data-business_type="{{ $businessType ?? 1 }}"
    ></div>
    <div style="height: calc(100% - 50px); background: #F9FBFE;">
        <div class="common_main_container">
            <div class="inputForm_wrap">
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" script="text/javascript"></script>
    <script src="{{ asset('static/user/slick/slick.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('static/user/js/common/common.js') }}" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
    <script src="{{ mix('/js/user/booking.js') }}"></script>
    @yield('scripts')
</body>
</html>