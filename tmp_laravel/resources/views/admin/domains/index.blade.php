@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">مدیریت دامین‌های Active Directory</h5>
                <a href="{{ route('admin.domains.create') }}" class="btn btn-sm btn-primary">افزودن دامین جدید</a>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>نام دامین</th>
                            <th>سرور (Hosts)</th>
                            <th>Base DN</th>
                            <th>پورت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($domains as $domain)
                        <tr>
                            <td>{{ $domain->name }}</td>
                            <td dir="ltr" class="text-end">{{ $domain->hosts }}</td>
                            <td dir="ltr" class="text-end">{{ $domain->base_dn }}</td>
                            <td>{{ $domain->port }}</td>
                            <td>
                                <a href="{{ route('admin.domains.edit', $domain->id) }}" class="btn btn-sm btn-warning">ویرایش</a>
                                <form action="{{ route('admin.domains.destroy', $domain->id) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">هیچ دامینی ثبت نشده است.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection