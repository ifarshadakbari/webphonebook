@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">ویرایش دامین</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.domains.update', $domain->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>نام دامین</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $domain->name) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>سرورها (Hosts)</label>
                            <input type="text" name="hosts" class="form-control" dir="ltr" value="{{ old('hosts', $domain->hosts) }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Base DN</label>
                            <input type="text" name="base_dn" class="form-control" dir="ltr" value="{{ old('base_dn', $domain->base_dn) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>نام کاربری (Username)</label>
                            <input type="text" name="username" class="form-control" dir="ltr" value="{{ old('username', $domain->username) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>رمز عبور (در صورت عدم تغییر خالی بگذارید)</label>
                            <input type="password" name="password" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>پورت</label>
                            <input type="number" name="port" class="form-control" dir="ltr" value="{{ old('port', $domain->port) }}" required>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="use_ssl" id="use_ssl" value="1" {{ old('use_ssl', $domain->use_ssl) ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_ssl">استفاده از SSL</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="use_tls" id="use_tls" value="1" {{ old('use_tls', $domain->use_tls) ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_tls">استفاده از TLS</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">بروزرسانی</button>
                    <button type="button" class="btn btn-info text-white" id="test-connection-btn">تست ارتباط</button>
                    <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary">انصراف</a>
                </form>
                <div id="test-result" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#test-connection-btn').click(function() {
            let btn = $(this);
            let form = btn.closest('form');
            let resultDiv = $('#test-result');

            btn.prop('disabled', true).text('در حال تست...');
            resultDiv.html('');

            // For PUT forms we need to trick the data a bit, but serialize includes the _method=PUT.
            // the route is POST, so we can temporarily strip _method or let backend handle it if it doesn't enforce PUT.
            // Wait, if it's sending _method=PUT to a POST endpoint Laravel will block it or redirect.
            // Let's create a custom payload or just remove _method for this ajax call
            let formData = form.serializeArray();
            let data = $.param(formData.filter(function(i) {
                return i.name !== '_method';
            }));

            $.ajax({
                url: "{{ route('admin.domains.test') }}",
                type: 'POST',
                data: data,
                success: function(response) {
                    resultDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                    btn.prop('disabled', false).text('تست ارتباط');
                },
                error: function(xhr) {
                    let msg = 'خطایی رخ داد.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    resultDiv.html('<div class="alert alert-danger">' + msg + '</div>');
                    btn.prop('disabled', false).text('تست ارتباط');
                }
            });
        });
    });
</script>
@endpush