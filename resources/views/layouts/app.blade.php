<!DOCTYPE html>
<html lang="en" class="w-full h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="w-full h-full">
    @yield('content')
    @livewireScripts
</body>
</html>