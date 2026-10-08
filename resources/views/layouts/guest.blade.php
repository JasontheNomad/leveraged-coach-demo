<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Leveraged Coach™</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #111111 inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: #ffffff;
        }
    </style>
</head>
<body class="font-sans antialiased" style="background:#0f0f0f; color:#ffffff; min-height:100vh; display:flex; align-items:center; justify-content:center;">

    <div style="width:100%; max-width:420px; padding:1.5rem;">

        {{-- Brand --}}
        <div class="text-center mb-12">
            <span class="text-2xl font-bold tracking-tight">
                <span style="color:#f5a623;">Leveraged</span><span style="color:#ffffff;"> Coach</span><sup style="color:#f5a623; font-size:0.65rem;">™</sup>
            </span>
        </div>

        {{-- Card --}}
        <div style="background:#1a1a1a; border:1px solid #2a2a2a; border-radius:1rem; padding:2rem;">
            {{ $slot }}
        </div>

    </div>

</body>
</html>
