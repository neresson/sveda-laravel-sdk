@php
    $baseUrl = $sidecarBaseUrl();
@endphp

@if ($baseUrl !== '')
    <link rel="stylesheet" href="{{ $baseUrl }}/build/sveda/embed.css">
    <script type="module" src="{{ $baseUrl }}/sveda/sveda-chat.js"></script>
    <sveda-chat
        @if ($token)
            origin="{{ $baseUrl }}"
            token="{{ $token }}"
        @else
            session="{{ $sessionUrl() }}"
        @endif
    ></sveda-chat>
@endif
