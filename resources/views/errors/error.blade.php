<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Error') — FisioApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="/fisio.webp">
    <link rel="shortcut icon" href="/favicon.ico">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: #0f172a;
            color: #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
        }

        body::before {
            width: 480px;
            height: 480px;
            background: #0ea5e9;
            top: -180px;
            right: -140px;
            opacity: .18;
        }

        body::after {
            width: 420px;
            height: 420px;
            background: #10b981;
            bottom: -200px;
            left: -160px;
            opacity: .14;
        }

        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 30rem;
            background: rgba(30, 41, 59, .72);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 2.75rem 2.25rem;
            text-align: center;
        }

        .brand {
            font-size: 2rem;
            margin-bottom: 1.25rem;
        }

        .code {
            font-size: 3.75rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.03em;
            background: linear-gradient(135deg, #38bdf8, #34d399);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: .75rem;
        }

        h1 {
            font-size: 1.35rem;
            font-weight: 600;
            margin: 0 0 .75rem;
        }

        p {
            color: #94a3b8;
            font-size: .95rem;
            line-height: 1.6;
            margin: 0 0 2rem;
        }

        .acciones {
            display: flex;
            gap: .75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: .7rem 1.4rem;
            border-radius: 10px;
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform .2s, box-shadow .2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #fff;
            box-shadow: 0 2px 10px rgba(14, 165, 233, .3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(14, 165, 233, .4);
            color: #fff;
        }

        .btn-secondary {
            background: #334155;
            color: #f1f5f9;
            border: 1px solid #334155;
        }

        .btn-secondary:hover {
            background: #475569;
            color: #f1f5f9;
        }

        .firma {
            margin-top: 2.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(51, 65, 85, .8);
            font-size: .75rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">💪</div>
        <div class="code">@yield('codigo', '500')</div>
        <h1>@yield('titulo', 'Algo ha fallado')</h1>
        <p>@yield('detalle', '')</p>

        <div class="acciones">
            <a class="btn btn-primary" href="/">Ir al inicio</a>
            <a class="btn btn-secondary" href="javascript:history.back()">Volver atrás</a>
        </div>

        <div class="firma">FisioApp · Gestión de rehabilitación</div>
    </div>
</body>
</html>
