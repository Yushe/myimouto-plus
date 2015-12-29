<!DOCTYPE html>
<html lang="en">
    <head>
        <title>@yield('pageTitle', 'Laravel')</title>

        <link href="{{ url('assets/app.css') }}" rel="stylesheet" type="text/css">

        <style>
        </style>
    </head>
    <body>

@yield('content')

    </body>
    
    <script src="{{ url('assets/app.js') }}"></script>
</html>
