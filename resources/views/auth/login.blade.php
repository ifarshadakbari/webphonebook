@extends('layouts.app')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">ورود به سیستم</h4>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="domain" class="form-label">دامین / نوع ورود</label>
                        <select name="domain" id="domain" class="form-select">
                            <option value="local">ورود ادمین محلی (Local)</option>
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">دامین: {{ $domain->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">نام کاربری</label>
                        <input type="text" class="form-control" id="username" name="username" required dir="ltr">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">رمز عبور</label>
                        <input type="password" class="form-control" id="password" name="password" required dir="ltr">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">ورود</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection