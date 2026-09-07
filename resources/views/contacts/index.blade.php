@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">لیست مخاطبین</h5>
                <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">افزودن مخاطب جدید</a>
            </div>
            <div class="card-body">
                <div class="mb-3 d-flex gap-2">
                    <a href="{{ route('contacts.export') }}" class="btn btn-success btn-sm"><i class="fa fa-file-excel"></i> خروجی اکسل</a>
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#importModal">
                      <i class="fa fa-upload"></i> ورود از اکسل
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped w-100" id="contacts-table">
                        <thead>
                            <tr>
                                <th>عکس</th>
                                <th>نام و نام خانوادگی</th>
                                <th>موبایل</th>
                                <th>شماره‌های ثابت و داخلی</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">ورود مخاطبین از فایل اکسل</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('contacts.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <div class="mb-3">
                <label for="excel_file" class="form-label">فایل اکسل خود را انتخاب کنید (.xlsx)</label>
                <input class="form-control" type="file" id="excel_file" name="file" accept=".xlsx,.xls" required>
            </div>
            <p class="text-muted small">فایل اکسل باید شامل ستون‌های: عنوان (آقای/خانم)، نام، نام خانوادگی، موبایل، تلفن ثابت، داخلی باشد.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
            <button type="submit" class="btn btn-primary">آپلود و درون‌ریزی</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#contacts-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('contacts.index') }}",
            columns: [
                { data: 'photo', name: 'photo', orderable: false, searchable: false },
                { data: 'full_name', name: 'full_name', searchable: true, orderable: true },
                { data: 'mobile', name: 'mobile', className: 'text-end', dir: 'ltr' },
                { data: 'phones', name: 'phones', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fa.json'
            }
        });
    });
</script>
@endpush