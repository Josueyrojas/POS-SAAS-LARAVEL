<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'POS Plataforma')</title>
{{-- Tailwind y Alpine por CDN: cero paso de compilación en desarrollo. --}}
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
<style>
    /* Recibo imprimible: oculta todo el chrome de la app (sidebar, banners,
       botones) y deja solo el elemento marcado como ticket en una columna
       angosta, como una impresora térmica de 80mm. */
    @media print {
        aside, .no-print { display: none !important; }
        main { width: 100% !important; }
        #receipt-ticket {
            width: 80mm;
            margin: 0 auto;
            font-size: 12px;
        }
    }
</style>
<script>
    // Al volver con "Atrás/Adelante" del navegador, Chrome puede restaurar la
    // página desde bfcache tal cual quedó (ej. con un modal de "Nuevo…"
    // abierto), sin re-ejecutar Alpine. Forzamos una recarga fresca del
    // servidor para que el formulario siempre arranque cerrado/limpio.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
