<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفترچه تلفن سازمانی</title>
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Vazirmatn Font -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.0.0/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #FF5841; /* sunset orange */
            --secondary-color: #C53678; /* red-violet */
            --bg-light: #FFFFFF;
        }
        * {
            font-family: 'Vazirmatn', sans-serif !important;
        }
        body {
            font-family: 'Vazirmatn', sans-serif !important;
            background-color: #f4f6f9;
        }
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link, .navbar-custom .text-white {
            color: #FFFFFF !important;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #e04b36;
            border-color: #e04b36;
        }
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-secondary:hover {
            background-color: #a82c65;
            border-color: #a82c65;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 12px 12px 0 0 !important;
        }
        .contact-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
        }
        table.dataTable thead th {
            background-color: #fafafa;
            border-bottom: 2px solid #eaeaea;
        }
        .text-primary {
            color: var(--primary-color) !important;
        }
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        .btn-info {
            background-color: #555555 !important; /* dark gray */
            border-color: #555555 !important;
            color: #fff !important;
        }
        .btn-info:hover {
            background-color: #333333 !important;
            border-color: #333333 !important;
        }
    </style>
    @stack('styles')
</head>
<body>

    @auth
    <nav class="navbar navbar-expand-lg navbar-custom mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">دفترچه تلفن سازمانی</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('contacts.index') }}">مخاطبین</a>
                    </li>
                    @if(auth()->user()->is_admin)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.domains.index') }}">مدیریت دامین‌ها (AD)</a>
                    </li>
                    @endif
                </ul>
                <div class="d-flex align-items-center text-white">
                    <span class="me-3">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">خروج</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    <!-- jQuery & Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    @stack('scripts')
</body>
</html>