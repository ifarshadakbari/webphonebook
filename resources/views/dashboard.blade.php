@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 text-center mt-5">
        <h1>به دفترچه تلفن سازمانی خوش آمدید</h1>
        <p class="mt-3">شما با نام کاربری <strong>{{ auth()->user()->username }}</strong> وارد شده‌اید.</p>

        <div class="mt-4">
            <a href="{{ route('contacts.index') }}" class="btn btn-primary btn-lg">مشاهده مخاطبین</a>
            @if(auth()->user()->is_admin)
                <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary btn-lg">مدیریت دامین‌ها</a>
            @endif
        </div>
    </div>
</div>
@endsection