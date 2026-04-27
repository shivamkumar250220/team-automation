@extends('layouts.app')

@section('title', 'Add Review — ' . $client->name)
@section('page_header', 'Add Review Manually')
@section('page_icon', 'mdi mdi-plus-circle-outline')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('gmb.clients.show', $client) }}">{{ $client->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('gmb.reviews.index', $client) }}">AI Drafts</a></li>
    <li class="breadcrumb-item active">Add Review</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">

                <form method="POST" action="{{ route('gmb.reviews.store', $client) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Location <span class="text-danger">*</span></label>
                        <select name="gmb_location_id" class="form-select @error('gmb_location_id') is-invalid @enderror" required>
                            <option value="">— Select Location —</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('gmb_location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->location_name }}{{ $location->city ? ' — ' . $location->city : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('gmb_location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Reviewer Name <span class="text-danger">*</span></label>
                        <input type="text" name="reviewer_name" class="form-control @error('reviewer_name') is-invalid @enderror"
                               value="{{ old('reviewer_name') }}" placeholder="e.g. Rahul Sharma" required>
                        @error('reviewer_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Rating <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2" id="star-picker">
                            @foreach(['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5] as $value => $num)
                                <button type="button" class="btn btn-sm btn-outline-warning star-btn" data-value="{{ $value }}" data-num="{{ $num }}">
                                    <i class="mdi mdi-star me-1"></i>{{ $num }}
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="rating" id="rating-input" value="{{ old('rating') }}">
                        @error('rating')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Review Text</label>
                        <textarea name="comment" class="form-control @error('comment') is-invalid @enderror"
                                  rows="4" placeholder="Enter the review text here...">{{ old('comment') }}</textarea>
                        <div class="form-text">If provided, AI will auto-generate 5 draft responses.</div>
                        @error('comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Review Date</label>
                        <input type="date" name="review_time" class="form-control @error('review_time') is-invalid @enderror"
                               value="{{ old('review_time', now()->format('Y-m-d')) }}">
                        @error('review_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-robot-outline me-1"></i> Add Review & Generate Drafts
                        </button>
                        <a href="{{ route('gmb.reviews.index', $client) }}" class="btn btn-light">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const num = parseInt(this.dataset.num);
        document.getElementById('rating-input').value = this.dataset.value;
        document.querySelectorAll('.star-btn').forEach((b, i) => {
            b.classList.toggle('btn-warning', i < num);
            b.classList.toggle('btn-outline-warning', i >= num);
        });
    });
});
</script>
@endpush