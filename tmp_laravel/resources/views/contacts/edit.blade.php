@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">ویرایش مخاطب</h5>
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

                <form action="{{ route('contacts.update', $contact->id) }}" method="POST" enctype="multipart/form-data" id="contact-form">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>عنوان اجتماعی</label>
                            <select name="social_title" class="form-select" required>
                                <option value="آقای" {{ $contact->social_title == 'آقای' ? 'selected' : '' }}>آقای</option>
                                <option value="خانم" {{ $contact->social_title == 'خانم' ? 'selected' : '' }}>خانم</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>نام</label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $contact->first_name) }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>نام خانوادگی</label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $contact->last_name) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>موبایل</label>
                            <input type="text" name="mobile" class="form-control text-end" dir="ltr" value="{{ old('mobile', $contact->mobile) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>تغییر عکس (اختیاری، حداکثر ۲ مگابایت)</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            @if($contact->photo)
                                <img src="{{ asset('storage/photos/' . $contact->photo) }}" class="mt-2 rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                            @endif
                        </div>
                    </div>

                    <hr>
                    <h6>شماره تلفن‌های ثابت و داخلی</h6>
                    <div id="phones-container">
                        @if($contact->phones->count() > 0)
                            @foreach($contact->phones as $index => $phone)
                            <div class="row mb-2 phone-row">
                                <div class="col-md-5">
                                    <input type="text" name="phones[{{ $index }}][number]" class="form-control text-end" dir="ltr" value="{{ $phone->phone_number }}" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="phones[{{ $index }}][internal]" class="form-control text-end" dir="ltr" value="{{ $phone->internal_number }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-remove-phone"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="row mb-2 phone-row">
                                <div class="col-md-5">
                                    <input type="text" name="phones[0][number]" class="form-control text-end" dir="ltr" placeholder="شماره ثابت" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="phones[0][internal]" class="form-control text-end" dir="ltr" placeholder="داخلی (اختیاری)">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-remove-phone" style="display:none;"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-success mb-3" id="btn-add-phone"><i class="fa fa-plus"></i> افزودن شماره دیگر</button>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-3">
                        <button type="submit" class="btn btn-primary" id="btn-submit">بروزرسانی مخاطب</button>
                        <a href="{{ route('contacts.index') }}" class="btn btn-secondary">انصراف</a>
                    </div>
                </form>
                <div id="ajax-message" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#contact-form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#btn-submit');
            let formData = new FormData(this);

            btn.prop('disabled', true).text('در حال بروزرسانی...');
            $('#ajax-message').html('');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#ajax-message').html('<div class="alert alert-success">مخاطب با موفقیت بروزرسانی شد.</div>');
                    btn.prop('disabled', false).text('بروزرسانی مخاطب');
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON?.errors;
                    let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
                    if (errors) {
                        $.each(errors, function(key, value) {
                            errorHtml += '<li>' + value[0] + '</li>';
                        });
                    } else {
                        errorHtml += '<li>خطایی رخ داده است.</li>';
                    }
                    errorHtml += '</ul></div>';
                    $('#ajax-message').html(errorHtml);
                    btn.prop('disabled', false).text('بروزرسانی مخاطب');
                }
            });
        });

        let phoneIndex = {{ $contact->phones->count() > 0 ? $contact->phones->count() : 1 }};

        $('#btn-add-phone').click(function() {
            let row = `
                <div class="row mb-2 phone-row">
                    <div class="col-md-5">
                        <input type="text" name="phones[${phoneIndex}][number]" class="form-control text-end" dir="ltr" placeholder="شماره ثابت" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="phones[${phoneIndex}][internal]" class="form-control text-end" dir="ltr" placeholder="داخلی (اختیاری)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-remove-phone"><i class="fa fa-times"></i></button>
                    </div>
                </div>
            `;
            $('#phones-container').append(row);
            phoneIndex++;
            updateRemoveButtons();
        });

        $(document).on('click', '.btn-remove-phone', function() {
            $(this).closest('.phone-row').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            if ($('.phone-row').length > 1) {
                $('.btn-remove-phone').show();
            } else {
                $('.btn-remove-phone').hide();
            }
        }

        updateRemoveButtons();
    });
</script>
@endpush